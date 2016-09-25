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
        $this->session();

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
                $questionUserId = $this->getUserIdForParentQuestion($answer->id);
                $comments = $this->getAllCommentsForSpecificAnswer($answer->id);
                $this->createAnswerView($answer, $comments, $questionUserId);
            }
        }
    }

    private function listAllAnswersForOneQuestion($questionId, $orderBy)
    {
        $answers = $this->answers->query('Lf_Answer.*, U.id AS answerUserId, U.acronym, U.gravatar')
            ->join('Question2Answer AS Q2A', 'Q2A.idAnswer = Lf_Answer.id')
            ->join('Question AS Q', 'Q2A.idQuestion = Q.id')
            ->join('User2Answer AS U2A', 'Lf_Answer.id = U2A.idAnswer')
            ->join('User AS U', 'U2A.idUser = U.id')
            ->orderBy($orderBy)
            ->where('Q.id = ?')
            ->execute([$questionId]);

        return $answers;
    }

    private function getUserIdForParentQuestion($answerId)
    {
        $questionUserId = $this->answers->query('U.id')
            ->join('Question2Answer AS Q2A', 'Q2A.idAnswer = Lf_Answer.id')
            ->join('Question AS Q', 'Q2A.idQuestion = Q.id')
            ->join('User2Question AS U2Q', 'U2Q.idQuestion = Q.id')
            ->join('User AS U', 'U2Q.idUser = U.id')
            ->where('Lf_Answer.id = ?')
            ->execute([$answerId]);

        return isset($questionUserId->id) ? $questionUserId->id : false;
    }

    private function createAnswerHeading($questionId, $numOfAnswers, $orderBy)
    {
        $latest = strcmp($orderBy, 'created desc') === 0 ? 'latest' : null;

        $this->views->add('answer/heading', [
            'questionId'    => $questionId,
            'numOfAnswers'  => $numOfAnswers,
            'latest'        => $latest,
        ], 'main-wide');
    }

    private function getAllCommentsForSpecificAnswer($answerId)
    {
        $comments = $this->answers->query('C.*, U.id AS userId, U.acronym')
            ->join('Answer2Comment AS A2C', 'A2C.idAnswer = Lf_Answer.id')
            ->join('Comment AS C', 'A2C.idComment = C.id')
            ->join('User2Comment AS U2C', 'C.id = U2C.idComment')
            ->join('User AS U', 'U2C.idUser = U.id')
            ->where('Lf_Answer.id = ?')
            ->orderBy('C.created asc')
            ->execute([$answerId]);

        return $comments;
    }

    private function createAnswerView($answer, $comments, $questionUserId)
    {
        $this->views->add('answer/answer', [
            'answer'            => $answer,
            'comments'          => $comments,
            'questionUserId'    => $questionUserId
        ], 'main-wide');
    }

    public function addAction($questionId = null)
    {
        if ($this->LoggedIn->isLoggedin()) {
            $this->addAnswer($questionId);
        } else {
            $this->redirectToLoginPage();
        }
    }

    private function addAnswer($questionId)
    {
        $userId = $this->LoggedIn->getUserId();
        if (isset($questionId) && $userId) {
            $this->createAddAnswerForm($questionId, $userId);
        } else {
            if (!isset($questionId)) {
                $subtitle = "Frågans id-nummer saknas";
                $message = "Frågans id-nummer saknas. Kan inte skicka vidare till nästa sida!";
            } else {
                $subtitle = "Användare-id saknas";
                $message = "Användare-id saknas. Kan inte koppla svar till användare!";
            }

            $this->showErrorMessage($subtitle, $message);
        }

    }

    private function createAddAnswerForm($questionId, $userId)
    {
        $form = new \Anax\HTMLForm\Answers\CFormAddAnswer($questionId, $userId);
        $form->setDI($this->di);
        $status = $form->check();

        $questionTitle = $this->getQuestionTitleFromId($questionId);

        $this->theme->setTitle("Re: " . $questionTitle);
        $this->views->add('answer/answerForm', [
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
     * Helper function for initiate no such user view.
     *
     * Initiates a view which shows a message the user with the specfic
     * id is not found. Contains a return button.
     *
     * @param  [] $content the subtitle and the message shown at page.
     *
     * @return void
     */
    private function showErrorMessage($subtitle, $message)
    {
        $defaultUrl = $this->url->create('questions/list/');
        $url = isset($_SERVER["HTTP_REFERER"]) ? $_SERVER["HTTP_REFERER"] : $defaultUrl;

        $content = [
            'title'         => 'Ett fel har uppstått!',
            'subtitle'      => $subtitle,
            'message'       => $message,
            'url'           => $url,
            'buttonName'    => 'Tillbaka'
        ];

        $this->dispatcher->forward([
            'controller' => 'errors',
            'action'     => 'view',
            'params'     => [$content]
        ]);
    }

    private function redirectToLoginPage()
    {
        $this->dispatcher->forward([
            'controller' => 'user-login',
            'action'     => 'login',
        ]);
    }

    public function addCommentAction($answerId)
    {
        $content = $this->getAnswerContentFromId($answerId);
        $content = $this->getSubstring($content, 30);
        $questionInfo = $this->getQuestionInfoForAnswer($answerId);
        $questionId = isset($questionInfo->questionId) ? $questionInfo->questionId : null;

        $this->dispatcher->forward([
            'controller' => 'comments',
            'action'     => 'add',
            'params'     => [$answerId, $questionId, $content, 'answer-comment']
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

    public function updateAction($answerId = null)
    {
        if ($this->isUpdateAllowed($answerId)) {
            $this->updateAnswer($answerId);
        } else {
            $this->handleUpdateIsNotAllowed($answerId);
        }
    }

    private function isUpdateAllowed($answerId)
    {
        $isUpdateAllowed = false;

        if (isset($answerId)) {
            if ($this->LoggedIn->isLoggedin()) {
                $authorId = $this->getAnswerAuthorId($answerId);
                $isUpdateAllowed = $this->LoggedIn->isAllowed($authorId);
            }
        }

        return $isUpdateAllowed;
    }

    private function getAnswerAuthorId($answerId)
    {
        $authorId = $this->answers->query('U.id')
            ->join('User2Answer AS U2A', 'U2A.idAnswer = Lf_Answer.id')
            ->join('User AS U', 'U2A.idUser = U.id')
            ->where('Lf_Answer.id = ?')
            ->execute([$answerId]);

        $authorId = empty($authorId) ? false : $authorId[0]->id;

        return $authorId;
    }

    private function updateAnswer($answerId)
    {
        $questionInfo = $this->getQuestionInfoFromAnswerId($answerId);
        $questionId = isset($questionInfo->id) ? $questionInfo->id : false;
        $questionTitle = isset($questionInfo->title) ? $questionInfo->title : false;

        if ($questionId && $questionTitle) {
            $answerData = $this->answers->find($answerId);
            $answerData = $answerData === false ? $answerData : $answerData->getProperties();
            if ($answerData) {
                $this->createUpdateAnswerForm($answerData, $questionId, $questionTitle);
            } else {
                $subtitle = "Svars data saknas";
                $message = "Svars data saknas. Kan inte koppla svar till fråga!";
                $this->showErrorMessage($subtitle, $message);
            }
        } else {
            $subtitle = "Fråge-id saknas";
            $message = "Fråge-id saknas. Kan inte koppla svar till fråga!";
            $this->showErrorMessage($subtitle, $message);
        }
    }

    private function createUpdateAnswerForm($answerData, $questionId, $questionTitle)
    {
        $form = new \Anax\HTMLForm\Answers\CFormUpdateAnswer($answerData, $questionId);
        $form->setDI($this->di);
        $status = $form->check();

        $this->theme->setTitle("Re: " . $questionTitle);
        $this->views->add('answer/answerForm', [
            'title' => "Re: " . $questionTitle,
            'content' => $form->getHTML(),
        ], 'main');
    }

    private function handleUpdateIsNotAllowed($answerId)
    {
        if (!isset($answerId)) {
            $subtitle = "Svars-id saknas";
            $message = "Svars-id saknas. Kan inte koppla svar till fråga!";
            $this->showErrorMessage($subtitle, $message);
        } else if ($this->LoggedIn->isLoggedin()) {
            $questionInfo = $this->getQuestionInfoFromAnswerId($answerId);
            $questionId = isset($questionInfo->id) ? $questionInfo->id : false;
            $noticeMessage = "Endast egna svar kan uppdateras!";
            $this->flash->noticeMessage($noticeMessage);
            if ($questionId) {
                $this->redirectToQuestion($questionId);
            } else {
                $this->redirectToQuestions();
            }
        } else {
            $this->redirectToLoginPage();
        }
    }



    private function getQuestionInfoFromAnswerId($answerId)
    {
        $questionInfo = $this->answers->query('Q.id, Q.title')
            ->join('Question2Answer AS Q2A', 'Q2A.idAnswer = Lf_Answer.id')
            ->join('Question AS Q', 'Q2A.idQuestion = Q.id')
            ->where('Lf_Answer.id = ?')
            ->execute([$answerId]);

        $questionInfo = empty($questionInfo) ? false : $questionInfo[0];

        return $questionInfo;
    }

    private function redirectToQuestion($questionId)
    {
        $this->dispatcher->forward([
            'controller' => 'questions',
            'action'     => 'id',
            'params'     => [$questionId]
        ]);
    }

    private function redirectToQuestions()
    {
        $this->dispatcher->forward([
            'controller' => 'questions',
            'action'     => 'list'
        ]);
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
        if ($this->LoggedIn->isLoggedin()) {
            if ($this->isUserAllowedToAccept($questionInfo)) {
                $this->updateAccept($answerId, $questionInfo);
            } else {
                $noticeMessage = "Svar kan endast accepteras för egna frågor!";
                $this->flash->noticeMessage($noticeMessage);
            }
        } else {
            $noticeMessage = "Du måste vara inloggad för att kunna acceptera svar på egna frågor!";
            $this->flash->noticeMessage($noticeMessage);
        }

        $this->redirectToQuestion($questionInfo->questionId);
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
        $userIdInSession = $this->LoggedIn->getUserId();
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
                $this->session->set('lastInsertedId', $answerId);
                $this->addActivityScoreToUser($answerId);
                $this->increaseAcceptsCounter($answerId);

                if ($this->session->has('lastInsertedId')) {
                    unset($_SESSION["lastInsertedId"]);
                }
            }
        } else {
            if ($this->unsetAnswerToAccepted($answerIdAccept)) {
                $this->setAnswerToAccepted($answerId);
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

    private function addActivityScoreToUser($answerId)
    {
        $this->session->set('lastInsertedId', $answerId);

        $this->dispatcher->forward([
            'controller' => 'users',
            'action'     => 'add-score',
            'params'     => [AnswersController::ACTIVITY_SCORE_ACCEPT, $answerId]
        ]);
    }

    private function increaseAcceptsCounter($id)
    {
        $this->dispatcher->forward([
            'controller' => 'users',
            'action'     => 'increase-accepts-counter',
            'params'     => [$id]
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

    public function listUserAnswersAction($userId = null)
    {
        if (isset($userId)) {
            $this->listUserAnswers($userId);
        } else {
            $errorMessage = "Användare id saknas. Kan ej lista svar!";
            $this->flash->errorMessage($errorMessage);
        }
    }

    private function listUserAnswers($userId)
    {
        $allAnswers = $this->getAllAnswersForUser($userId);

        $this->views->add('users/itemHeading', [
            'numOfAnswers'  => count($allAnswers),
            'item'          => "Svar",
            'type'          => "answer",
            'userId'        => $userId,
        ], 'main-wide');

        $this->views->add('answer/userAnswers', [
            'answers'  => $allAnswers,
        ], 'main-wide');
    }

    private function getAllAnswersForUser($userId)
    {
        $answerData = $this->answers->query('Lf_Answer.*, Q.id AS questionId, Q.title AS questionTitle, U.id AS userId, U.acronym')
            ->join('User2Answer AS U2A', 'U2A.idAnswer = Lf_Answer.id')
            ->join('User AS U', 'U2A.idUser = U.id')
            ->join('Question2Answer AS Q2A', 'Q2A.idAnswer = Lf_Answer.id')
            ->join('Question AS Q', 'Q2A.idQuestion = Q.id')
            ->where('U.id = ?')
            ->execute([$userId]);

        return $answerData;
    }
}
