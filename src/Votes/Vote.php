<?php

namespace Anax\Votes;

/**
 * Vote
 *
 * An abstract class which communicates with the vote related mapping tables
 * in the database.
 * Handles all voting tasks related to a user.
 *
 * Inherits from Vote class.
 */
abstract class Vote implements \Anax\DI\IInjectionAware
{
    use \Anax\DI\TInjectable;

    const ACTIVITY_SCORE_VOTE = 1;

    /**
     * Initialize the controller.
     *
     * Initializes the session.
     *
     * @return void
     */
    public function initialize()
    {
        $this->session();
    }

    /**
     * Increses the vote counter for an question, answer or comment with one.
     *
     * Checks if the user has logged in and is allowed to vote. A user is not
     * allowed to vote on own questions, answers and comments. A user is only
     * allowed to vote once on another users question, answer or comment.
     *
     * Saves the id in session to prevent the counter to be increased by a
     * direct call via the browsers address bar.
     *
     * At error, creates a flash error message.
     *
     * Redirects to the Question controller to show the question with the
     * related answers and comments.
     *
     * @param  int $id      the id to increase the ranking score on.
     *
     * @return void.
     */
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

    /**
     * Helper method to check if user is allowed to vote.
     *
     * Checks if a user is allowed to vote. The rules are that a user must has
     * logged in, not allowed to vote on own written questions, answers and
     * comments. The user is only allowed to vote once on a question, answer
     * or a comment.
     *
     * Creates a flash error message, if the user has not logged in.
     *
     * @param  int  $id         the ID of the unit to vote on.
     * @param  int  $userId     the user ID of the voter.
     *
     * @return boolean          true, if user is allowed to vote. False otherwise.
     */
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

    /**
     * Helper method to check if user is allowed to vote.
     *
     * Checks if a user is voting on own written questions, answers or
     * comments. Checks if the user has already voted on a question, answer or
     * comment.
     *
     * Creates a flash error message, if the user trying to vote on own written
     * questions, answers, comments or has already voted on the question, answer
     * or comment.
     *
     * @param  int  $id         the ID of the unit to vote on.
     * @param  int  $userId     the user ID of the voter.
     *
     * @return boolean          true, if user is allowed to vote. False otherwise.
     */
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

    /**
     * Helper method to check if the user is the author.
     *
     * Checks if the user is the author.
     *
     * @param  int  $id         the ID of the unit to check if the user is the author.
     * @param  int  $userId     the user ID of the author.
     *
     * @return boolean          true if user is the author, false otherwise.
     */
    private function isUserAuthor($id, $userId)
    {
        $isUserTheAuthor = false;
        $authorId = $this->getUserId($id);
        if ($userId === $authorId) {
            $isUserTheAuthor = true;
        }

        return $isUserTheAuthor;
    }

    /**
     * Abstract method to get the user ID for a specific unit in DB.
     *
     * Gets the ID of the unit writer in DB.
     *
     * @param  int $id  the id of the unit.
     *
     * @return int      the ID of the unit writer.
     */
    abstract protected function getUserId($id);

    /**
     * Abstract method to check if the voter has already voted.
     *
     * Checks in the mapping table in DB, if voter has already voted.
     *
     * @param  int  $id         the id of the unit.
     * @param  int  $userId     the user id of the voter.
     *
     * @return boolean          true if user already has voted, false otherwise.
     */
    abstract protected function hasUserVoted($id, $userId);

    /**
     * Abstract method to add a user as a voter.
     *
     * Maps a user id to a unit id, which marks that a user has voted on
     * a unit.
     *
     * @param int $id       the id of the unit.
     * @param int $userId   the user id of the voter.
     */
    abstract protected function addUserAsVoter($id, $userId);

    /**
     * Abstract method increase the score counter.
     *
     * Gets the score in DB and increases the score with one. Saves the new
     * score in DB.
     * The score is used to rank the unit.
     *
     * @param  int $id  the unit id for the unit to rank.
     *
     * @return boolean  true if the increased value could be saved in DB, false
     *                  otherwise.
     */
    abstract protected function increaseScoreCounter($id);

    /**
     * Abstract method to get the ranking score from DB.
     *
     * Gets the ranking score from DB, if found. If not, false is
     * returned.
     *
     * @param  int $id  the id of the unit.
     * @return int | false  the ranking score for the unit. False, if
     *                      not found.
     */
    abstract protected function getScoreNumber($id);

    /**
     * Helper method to add voting activity score to a user.
     *
     * Redirects to the Users controller to add voting activity score.
     *
     * @param int $id the user id of the voter.
     *
     * @return void.
     */
    private function addActivityScoreToUser($id)
    {
        $this->dispatcher->forward([
            'controller' => 'users',
            'action'     => 'add-score',
            'params'     => [QuestionVotesController::ACTIVITY_SCORE_VOTE, $id]
        ]);
    }

    /**
     * Helper method to increase the vote counter for a user.
     *
     * Redirects to the Users controller to increase the vote counter.
     *
     * @param  int $id  the user id to increase the counter.
     *
     * @return void.
     */
    private function increaseVotesCounter($id)
    {
        $this->dispatcher->forward([
            'controller' => 'users',
            'action'     => 'increase-votes-counter',
            'params'     => [$id]
        ]);
    }

    abstract protected function getQuestionId($id);

    /**
     * Decreses the vote counter for an question, answer or comment with one.
     *
     * Checks if the user has logged in and is allowed to vote. A user is not
     * allowed to vote on own questions, answers and comments. A user is only
     * allowed to vote once on another users question, answer or comment.
     *
     * Saves the id in session to prevent the counter to be decreased by a
     * direct call via the browsers address bar.
     *
     * At error, creates a flash error message.
     *
     * Redirects to the Question controller to show the question with the
     * related answers and comments.
     *
     * @param  int $id      the id to decrease the ranking score on.
     *
     * @return void.
     */
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

    /**
     * Abstract method decrease the score counter.
     *
     * Gets the score in DB and decreases the score with one. Saves the new
     * score in DB.
     * The score is used to rank the unit.
     *
     * @param  int $id  the unit id for the unit to rank.
     *
     * @return boolean  true if the decreased value could be saved in DB, false
     *                  otherwise.
     */
    abstract protected function decreaseScoreCounter($id);
}
