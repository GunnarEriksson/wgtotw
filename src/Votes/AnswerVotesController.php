<?php

namespace Anax\Votes;

class AnswerVotesController implements \Anax\DI\IInjectionAware
{
    use \Anax\DI\TInjectable;

    const ACTIVITY_SCORE_VOTE = 1;

    private $userIdInSession;

    /**
     * Initialize the controller.
     *
     * @return void
     */
    public function initialize()
    {
        $this->di->session();

        $this->answerVotes = new \Anax\Votes\AnswerVote();
        $this->answerVotes->setDI($this->di);

        $this->answers = new \Anax\Answers\Answer();
        $this->answers->setDI($this->di);
    }

    public function increaseAction($answerId)
    {
        $userId = $this->getUserIdInSession();
        if ($this->isAllowedToVote($answerId, $userId)) {
            $isSaved = $this->addUserAsVoter($answerId, $userId);
            if ($isSaved) {
                if ($this->increaseScoreCounter($answerId)) {
                    $this->addActivityScoreToUser();
                }
            }
        }

        $questionId = $this->getQuestionIdFromAnswerId($answerId);

        $this->dispatcher->forward([
            'controller' => 'questions',
            'action'     => 'id',
            'params'     => [$questionId]
        ]);
    }

    private function getUserIdInSession()
    {
        $userId = false;

        if ($this->di->session->has('user')) {
            $userId = $this->di->session->get('user')['id'];
        }

        return $userId;
    }

    private function isAllowedToVote($answerId, $userId)
    {
        $isAllowedToVote = false;

        if ($userId !== false) {
            $isAllowedToVote = $this->isUserAllowedToVote($answerId, $userId);
        }

        return $isAllowedToVote;
    }

    private function isUserAllowedToVote($answerId, $userId)
    {
        $isAllowedToVote = false;

        if ($this->isUserAuthorOfAnswer($answerId, $userId) === false) {
            if ($this->hasUserVoted($answerId, $userId) === false) {
                $isAllowedToVote = true;
            }
        }

        return $isAllowedToVote;
    }

    private function isUserAuthorOfAnswer($answerId, $userId)
    {
        $isUserTheAuthor = false;
        $authorId = $this->getUserIdOfAnswer($answerId);
        if ($userId === $authorId) {
            $isUserTheAuthor = true;
        }

        return $isUserTheAuthor;
    }

    private function getUserIdOfAnswer($answerId)
    {
        $userId = $this->answers->query('U.id')
            ->join('User2Answer AS U2A', 'U2A.idAnswer = Lf_Answer.id')
            ->join('User AS U', 'U2A.idUser = U.id')
            ->where('Lf_Answer.id = ?')
            ->execute([$answerId]);

        $userId = empty($userId) ? false : $userId[0]->id;

        return $userId;
    }

    private function hasUserVoted($answerId, $userId)
    {
        $id = $this->answerVotes->query('Lf_AnswerVote.id')
            ->where('Lf_AnswerVote.idAnswer = ? AND Lf_AnswerVote.idUser = ?')
            ->execute([$answerId, $userId]);

        $hasVoted = empty($id) ? false : true;

        return $hasVoted;
    }

    private function addUserAsVoter($answerId, $userId)
    {
        $isSaved = $this->answerVotes->create(array(
            'idAnswer'  => $answerId,
            'idUser'    => $userId,
        ));

        return $isSaved;
    }

    private function increaseScoreCounter($answerId)
    {
        $score = $this->getScoreNumber($answerId);
        $score = $score === false ? 0 : ++$score;

        $isSaved = $this->answers->save(array(
            'id'        => $answerId,
            'score'     => $score,
        ));

        return $isSaved;
    }

    private function getScoreNumber($answerId)
    {
        $score = $this->answers->query('score')
            ->where('id = ?')
            ->execute([$answerId]);

        $score = empty($score) ? false : $score[0]->score;

        return $score;
    }

    private function addActivityScoreToUser()
    {
        $this->di->dispatcher->forward([
            'controller' => 'users',
            'action'     => 'add-score',
            'params'     => [AnswerVotesController::ACTIVITY_SCORE_VOTE]
        ]);
    }

    private function getQuestionIdFromAnswerId($answerId)
    {
        $questionId = $this->answers->query('Q.id')
            ->join('Question2Answer AS Q2A', 'Q2A.idAnswer = Lf_Answer.id')
            ->join('Question AS Q', 'Q2A.idQuestion = Q.id')
            ->where('Lf_Answer.id = ?')
            ->execute([$answerId]);

        $questionId = empty($questionId) ? false : $questionId[0]->id;

        return $questionId;
    }

    public function decreaseAction($answerId)
    {
        $userId = $this->getUserIdInSession();
        if ($this->isAllowedToVote($answerId, $userId)) {
            $isSaved = $this->addUserAsVoter($answerId, $userId);
            if ($isSaved) {
                if ($this->decreaseScoreCounter($answerId)) {
                    $this->addActivityScoreToUser();
                }
            }
        }

        $questionId = $this->getQuestionIdFromAnswerId($answerId);

        $this->dispatcher->forward([
            'controller' => 'questions',
            'action'     => 'id',
            'params'     => [$questionId]
        ]);
    }

    private function decreaseScoreCounter($answerId)
    {
        $score = $this->getScoreNumber($answerId);
        $score = $score === false ? 0 : --$score;

        $isSaved = $this->answers->save(array(
            'id'        => $answerId,
            'score'     => $score,
        ));

        return $isSaved;
    }
}
