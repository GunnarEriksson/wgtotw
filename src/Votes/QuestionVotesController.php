<?php

namespace Anax\Votes;

class QuestionVotesController implements \Anax\DI\IInjectionAware
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

        $this->questionVotes = new \Anax\Votes\QuestionVote();
        $this->questionVotes->setDI($this->di);

        $this->questions = new \Anax\Questions\Question();
        $this->questions->setDI($this->di);
    }

    public function increaseAction($questionId)
    {
        $userId = $this->getUserIdInSession();
        if ($this->isAllowedToVote($questionId, $userId)) {
            $isSaved = $this->addUserAsVoter($questionId, $userId);
            if ($isSaved) {
                if ($this->increaseScoreCounter($questionId)) {
                    $this->addActivityScoreToUser();
                }
            }
        }

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

    private function isAllowedToVote($questionId, $userId)
    {
        $isAllowedToVote = false;

        if ($userId !== false) {
            $isAllowedToVote = $this->isUserAllowedToVote($questionId, $userId);
        }

        return $isAllowedToVote;
    }

    private function isUserAllowedToVote($questionId, $userId)
    {
        $isAllowedToVote = false;

        if ($this->isUserAuthorOfQuestion($questionId, $userId) === false) {
            if ($this->hasUserVoted($questionId, $userId) === false) {
                $isAllowedToVote = true;
            }
        }

        return $isAllowedToVote;
    }

    private function isUserAuthorOfQuestion($questionId, $userId)
    {
        $isUserTheAuthor = false;
        $authorId = $this->getUserIdOfQuestion($questionId);
        if ($userId === $authorId) {
            $isUserTheAuthor = true;
        }

        return $isUserTheAuthor;
    }

    private function getUserIdOfQuestion($questionId)
    {
        $userId = $this->questions->query('U.id')
            ->join('User2Question AS U2Q', 'U2Q.idQuestion = Lf_Question.id')
            ->join('User AS U', 'U2Q.idUser = U.id')
            ->where('Lf_Question.id = ?')
            ->execute([$questionId]);

        $userId = empty($userId) ? false : $userId[0]->id;

        return $userId;
    }

    private function hasUserVoted($questionId, $userId)
    {
        $id = $this->questionVotes->query('Lf_QuestionVote.id')
            ->where('Lf_QuestionVote.idQuestion = ? AND Lf_QuestionVote.idUser = ?')
            ->execute([$questionId, $userId]);

        $hasVoted = empty($id) ? false : true;

        return $hasVoted;
    }

    private function addUserAsVoter($questionId, $userId)
    {
        $isSaved = $this->questionVotes->create(array(
            'idQuestion'    => $questionId,
            'idUser'  => $userId,
        ));

        return $isSaved;
    }

    private function increaseScoreCounter($questionId)
    {
        $score = $this->getScoreNumber($questionId);
        $score = $score === false ? 0 : ++$score;

        $isSaved = $this->questions->save(array(
            'id'        => $questionId,
            'score'     => $score,
        ));

        return $isSaved;
    }

    private function getScoreNumber($questionId)
    {
        $score = $this->questions->query('score')
            ->where('id = ?')
            ->execute([$questionId]);

        $score = empty($score) ? false : $score[0]->score;

        return $score;
    }

    private function addActivityScoreToUser()
    {
        $this->di->dispatcher->forward([
            'controller' => 'users',
            'action'     => 'add-score',
            'params'     => [QuestionVotesController::ACTIVITY_SCORE_VOTE]
        ]);
    }

    public function decreaseAction($questionId)
    {
        $userId = $this->getUserIdInSession();
        if ($this->isAllowedToVote($questionId, $userId)) {
            $isSaved = $this->addUserAsVoter($questionId, $userId);
            if ($isSaved) {
                if ($this->decreaseScoreCounter($questionId)) {
                    $this->addActivityScoreToUser();
                }
            }
        }

        $this->dispatcher->forward([
            'controller' => 'questions',
            'action'     => 'id',
            'params'     => [$questionId]
        ]);
    }

    private function decreaseScoreCounter($questionId)
    {
        $score = $this->getScoreNumber($questionId);
        $score = $score === false ? 0 : --$score;

        $isSaved = $this->questions->save(array(
            'id'        => $questionId,
            'score'     => $score,
        ));

        return $isSaved;
    }
}
