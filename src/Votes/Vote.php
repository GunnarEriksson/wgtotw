<?php

namespace Anax\Votes;

abstract class Vote implements \Anax\DI\IInjectionAware
{
    use \Anax\DI\TInjectable;

    const ACTIVITY_SCORE_VOTE = 1;

    /**
     * Initialize the controller.
     *
     * @return void
     */
    public function initialize()
    {
        $this->session();
    }

    public function increaseAction($id)
    {
        $userId = $this->LoggedIn->getUserId();
        if ($this->isAllowedToVote($id, $userId)) {
            $isSaved = $this->addUserAsVoter($id, $userId);
            if ($isSaved) {
                if ($this->increaseScoreCounter($id)) {
                    $this->session->set('lastInsertedId', $id);
                    $this->addActivityScoreToUser($id);
                    $this->increaseVotesCounter($id);

                    if ($this->session->has('lastInsertedId')) {
                        unset($_SESSION["lastInsertedId"]);
                    }
                } else {
                    $this->flash->errorMessage("Röst kunde inte sparas i DB.");
                }
            } else {
                $this->flash->errorMessage("Kunde inte koppla röst till användare.");
            }
        }

        $questionId = $this->getQuestionId($id);

        $this->dispatcher->forward([
            'controller' => 'questions',
            'action'     => 'id',
            'params'     => [$questionId]
        ]);
    }

    private function isAllowedToVote($id, $userId)
    {
        $isAllowedToVote = false;

        if ($userId !== false) {
            $isAllowedToVote = $this->isUserAllowedToVote($id, $userId);
        } else {
            $this->flash->noticeMessage("Du måste vara inloggad för att få rösta.");
        }

        return $isAllowedToVote;
    }

    private function isUserAllowedToVote($id, $userId)
    {
        $isAllowedToVote = false;

        if ($this->isUserAuthor($id, $userId) === false) {
            if ($this->hasUserVoted($id, $userId) === false) {
                $isAllowedToVote = true;
            } else {
                $this->flash->noticeMessage("Du har redan röstat.");
            }
        } else {
            $this->flash->noticeMessage("Får ej rösta på egna frågor, svar och kommentarer.");
        }

        return $isAllowedToVote;
    }

    private function isUserAuthor($id, $userId)
    {
        $isUserTheAuthor = false;
        $authorId = $this->getUserId($id);
        if ($userId === $authorId) {
            $isUserTheAuthor = true;
        }

        return $isUserTheAuthor;
    }

    abstract protected function getUserId($id);

    abstract protected function hasUserVoted($id, $userId);

    abstract protected function addUserAsVoter($id, $userId);

    abstract protected function increaseScoreCounter($id);

    abstract protected function getScoreNumber($id);

    private function addActivityScoreToUser($id)
    {
        $this->dispatcher->forward([
            'controller' => 'users',
            'action'     => 'add-score',
            'params'     => [QuestionVotesController::ACTIVITY_SCORE_VOTE, $id]
        ]);
    }

    private function increaseVotesCounter($id)
    {
        $this->dispatcher->forward([
            'controller' => 'users',
            'action'     => 'increase-votes-counter',
            'params'     => [$id]
        ]);
    }

    abstract protected function getQuestionId($id);

    public function decreaseAction($id)
    {
        $userId = $this->LoggedIn->getUserId();
        if ($this->isAllowedToVote($id, $userId)) {
            $isSaved = $this->addUserAsVoter($id, $userId);
            if ($isSaved) {
                if ($this->decreaseScoreCounter($id)) {
                    $this->session->set('lastInsertedId', $id);
                    $this->addActivityScoreToUser($id);
                    $this->increaseVotesCounter($id);

                    if ($this->session->has('lastInsertedId')) {
                        unset($_SESSION["lastInsertedId"]);
                    }
                }
            }
        }

        $questionId = $this->getQuestionId($id);

        $this->dispatcher->forward([
            'controller' => 'questions',
            'action'     => 'id',
            'params'     => [$questionId]
        ]);
    }

    abstract protected function decreaseScoreCounter($id);
}
