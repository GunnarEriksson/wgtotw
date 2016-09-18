<?php

namespace Anax\Answers;

class AnswersController implements \Anax\DI\IInjectionAware
{
    use \Anax\DI\TInjectable;

    const ACTIVITY_SCORE_ACCEPT = 3;

    /**
     * Initialize the controller.
     *
     * @return void
     */
    public function initialize()
    {
        $this->di->session();

        $this->answers = new \Anax\Answers\Answer();
        $this->answers->setDI($this->di);

        $this->questions = new \Anax\Questions\Question();
        $this->questions->setDI($this->di);
    }

    public function listAction($questionId, $orderBy)
    {
        $allAnswers = $this->listAllAnswersForOneQuestion($questionId, $orderBy);
        if (!empty($allAnswers)) {
            $this->createAnswerHeading($questionId, count($allAnswers), $orderBy);

            foreach ($allAnswers as $answer) {
                $comments = $this->getAllCommentsForSpecificAnswer($answer->id);
                $this->createAnswerView($answer, $comments);
            }
        }
    }

    private function listAllAnswersForOneQuestion($questionId, $orderBy)
    {
        $answers = $this->answers->query('Lf_Answer.*, U.acronym, U.gravatar')
            ->join('Question2Answer AS Q2A', 'Q2A.idAnswer = Lf_Answer.id')
            ->join('Question AS Q', 'Q2A.idQuestion = Q.id')
            ->join('User2Answer AS U2A', 'Lf_Answer.id = U2A.idAnswer')
            ->join('User AS U', 'U2A.idUser = U.id')
            ->orderBy($orderBy)
            ->where('Q.id = ?')
            ->execute([$questionId]);

        return $answers;
    }

    private function createAnswerHeading($questionId, $numOfAnswers, $orderBy)
    {
        $latest = strcmp( $orderBy, 'created desc') === 0 ? 'latest' : null;

        $this->views->add('answer/heading', [
            'questionId'   => $questionId,
            'numOfAnswers'  => $numOfAnswers,
            'latest'  => $latest,
        ], 'main-wide');
    }

    private function getAllCommentsForSpecificAnswer($answerId)
    {
        $comments = $this->answers->query('C.*, U.acronym')
            ->join('Answer2Comment AS A2C', 'A2C.idAnswer = Lf_Answer.id')
            ->join('Comment AS C', 'A2C.idComment = C.id')
            ->join('User2Comment AS U2C', 'C.id = U2C.idComment')
            ->join('User AS U', 'U2C.idUser = U.id')
            ->where('Lf_Answer.id = ?')
            ->orderBy('C.created asc')
            ->execute([$answerId]);

        return $comments;
    }

    private function createAnswerView($answer, $comments)
    {
        $this->views->add('answer/answer', [
            'answer'    => $answer,
            'comments'  => $comments
        ], 'main-wide');
    }

    public function addAction($questionId)
    {
        if ($this->di->session->has('user')) {
            $this->addAnswer($questionId);
        } else {
            $this->pageNotFound();
        }
    }

    private function addAnswer($questionId)
    {
        $user = $this->di->session->get('user', []);
        $form = new \Anax\HTMLForm\Answers\CFormAddAnswer($questionId, $user);
        $form->setDI($this->di);
        $status = $form->check();

        $questionTitle = $this->getQuestionTitleFromId($questionId);

        $this->di->theme->setTitle("Re: " . $questionTitle);
        $this->di->views->add('answer/answerForm', [
            'title' => "Re: " . $questionTitle,
            'content' => $form->getHTML(),
        ], 'main');
    }

    private function getQuestionTitleFromId($questionId)
    {
        $question = $this->questions->find($questionId);
        $title = ($question === false) ? "" : $question->title ;

        return $title;
    }

    /**
     * Helper method to show page 404, page not found.
     *
     * Shows page 404 with the text that the page could not be found and you
     * must login to get the page you are looking for.
     *
     * @return void
     */
    private function pageNotFound()
    {
        $this->theme->setTitle("Sidan saknas");
        $this->views->add('error/404', [
            'title' => 'Sidan saknas',
        ], 'main-wide');
    }

    public function addCommentAction($answerId)
    {
        $content = $this->getAnswerContentFromId($answerId);
        $content = $this->getSubstring($content, 30);

        $this->dispatcher->forward([
            'controller' => 'comments',
            'action'     => 'add',
            'params'     => [$answerId, $content, 'answer-comment']
        ]);
    }

    private function getAnswerContentFromId($answerId)
    {
        $content = $this->answers->query('content')
            ->where('id = ?')
            ->execute([$answerId]);

        return $content === false ? "" : $content[0]->content;
    }

    /**
     * Helper function to get a substring of a string.
     *
     * Returns specified part of an text. A helper function checks the next nearest
     * space in the text for the specified length to prevent a word to be
     * truncated.
     *
     * @param  string $textString   the string to be truncated.
     * @param  int $numOfChar       the maximum length of the text.
     *
     * @return string               the truncated text.
     */
    private function getSubstring($textString, $numOfChar)
    {
        $textEndPos = $this->getSpacePosInString($textString, $numOfChar);
        if ($textEndPos === 0) {
            $text = substr($textString, 0, $numOfChar);
        } else {
            $text = substr($textString, 0, $textEndPos);
            $text .= " ...";
        }

        return $text;
    }

    /**
     * Helper function to find the next space in a string.
     *
     * Finds the next space from the specified position.
     *
     * @param  string $textString   the text string to find a space in.
     * @param  int $offset          the position to find the next space from.
     *
     * @return int the position of the next space from the specified position.
     */
    private function getSpacePosInString($textString, $offset)
    {
        $pos = 0;
        if (strlen($textString) >= $offset) {
            $pos = strpos($textString, ' ', $offset);
        }

        return $pos;
    }

    public function upVoteAction($answerId)
    {
        $this->dispatcher->forward([
            'controller' => 'answer-votes',
            'action'     => 'increase',
            'params'     => [$answerId]
        ]);
    }

    public function downVoteAction($answerId)
    {
        $this->dispatcher->forward([
            'controller' => 'answer-votes',
            'action'     => 'decrease',
            'params'     => [$answerId]
        ]);
    }

    public function acceptAction($answerId)
    {
        $questionInfo = $this->getQuestionInfoForAnswer($answerId);
        if ($this->di->session->has('user')) {
            if ($this->isUserAllowedToAccept($questionInfo)) {
                $this->updateAccept($answerId, $questionInfo);
            }
        }

        $this->dispatcher->forward([
            'controller' => 'questions',
            'action'     => 'id',
            'params'     => [$questionInfo->questionId]
        ]);
    }

    private function getQuestionInfoForAnswer($answerId)
    {
        $questionInfo = $this->answers->query('Q.id AS questionId, U.id AS userId')
            ->join('Question2Answer AS Q2A', 'Q2A.idAnswer = Lf_Answer.id')
            ->join('Question AS Q', 'Q2A.idQuestion = Q.id')
            ->join('User2Question AS U2Q', 'Q.id = U2Q.idQuestion')
            ->join('User AS U', 'U2Q.idUser = U.id')
            ->where('Lf_Answer.id = ?')
            ->execute([$answerId]);

        $questionInfo = empty($questionInfo) ? false : $questionInfo[0];

        return $questionInfo;
    }

    private function isUserAllowedToAccept($questionInfo)
    {
        $isAllowedToAccept = false;
        $userIdInSession = $this->di->session->get('user')['id'];
        if ($questionInfo->userId === $userIdInSession) {
            $isAllowedToAccept = true;
        }

        return $isAllowedToAccept;
    }

    private function updateAccept($answerId, $questionInfo)
    {
        $questionId = $questionInfo->questionId;
        $answerIdAccept = $this->getAcceptedAnswerIdForQuestion($questionId);
        if ($answerIdAccept === false) {
            if ($this->setAnswerToAccepted($answerId)) {
                $this->addActivityScoreToUser();
            }
        } else {
            if ($this->unsetAnswerToAccepted($answerIdAccept)) {
                if ($this->setAnswerToAccepted($answerId)) {
                    $this->addActivityScoreToUser();
                }
            }
        }
    }

    private function getAcceptedAnswerIdForQuestion($questionId)
    {
        $answerId = $this->answers->query('Lf_Answer.id')
            ->join('Question2Answer AS Q2A', 'Q2A.idAnswer = Lf_Answer.id')
            ->join('Question AS Q', 'Q2A.idQuestion = Q.id')
            ->where('Q.id = ?')
            ->andWhere('Lf_Answer.accepted=1')
            ->execute([$questionId]);

        $answerId = empty($answerId) ? false : $answerId[0]->id;

        return $answerId;
    }

    private function setAnswerToAccepted($answerId)
    {
        $isSaved = $this->answers->save(array(
            'id'        => $answerId,
            'accepted'  => 1,
        ));

        return $isSaved;
    }

    private function addActivityScoreToUser()
    {
        $this->di->dispatcher->forward([
            'controller' => 'users',
            'action'     => 'add-score',
            'params'     => [AnswersController::ACTIVITY_SCORE_ACCEPT]
        ]);
    }

    private function unsetAnswerToAccepted($answerIdAccept)
    {
        $isSaved = $this->answers->save(array(
            'id'        => $answerIdAccept,
            'accepted'  => 0,
        ));

        return $isSaved;
    }
}
