<?php

namespace Anax\Votes;

/**
 * Question Votes controller
 *
 * Communicates with the mapping table, which maps user with a question
 * in the database.
 * Handles all question voting tasks between user and the related question.
 *
 * Inherits from Vote class.
 */
class QuestionVotesController extends Vote
{
    /**
     * Initialize the controller.
     *
     * Initializes the question vote model and the
     * question model.
     *
     * @return void
     */
    public function initialize()
    {
        $this->questionVotes = new \Anax\Votes\QuestionVote();
        $this->questionVotes->setDI($this->di);

        $this->questions = new \Anax\Questions\Question();
        $this->questions->setDI($this->di);
    }

    /**
     * Gets the user ID for a specific question in DB.
     *
     * Gets the ID of the question writer in DB.
     *
     * @param  int $id  the id of the question.
     *
     * @return int      the ID of the question writer.
     */
    protected function getUserId($id)
    {
        $userId = $this->questions->query('U.id')
            ->join('user2question AS U2Q', 'U2Q.idQuestion = lf_question.id')
            ->join('user AS U', 'U2Q.idUser = U.id')
            ->where('lf_question.id = ?')
            ->execute([$id]);

        $userId = empty($userId) ? false : $userId[0]->id;

        return $userId;
    }

    /**
     * Checks if the voter has already voted on the question.
     *
     * Checks in the mapping table in DB, if voter has already voted on the
     * question.
     *
     * @param  int  $id         the id of the question.
     * @param  int  $userId     the user id of the voter.
     *
     * @return boolean          true if user already has voted, false otherwise.
     */
    protected function hasUserVoted($id, $userId)
    {
        $id = $this->questionVotes->query('lf_questionvote.id')
            ->where('lf_questionvote.idQuestion = ? AND lf_questionvote.idUser = ?')
            ->execute([$id, $userId]);

        $hasVoted = empty($id) ? false : true;

        return $hasVoted;
    }

    /**
     * Adds that a user has voted on a question.
     *
     * Maps a user id to a question id, which marks that a user has voted on
     * a question.
     *
     * @param int $id       the id of the question.
     * @param int $userId   the user id of the voter.
     */
    protected function addUserAsVoter($id, $userId)
    {
        $isSaved = $this->questionVotes->create(array(
            'idQuestion'    => $id,
            'idUser'  => $userId,
        ));

        return $isSaved;
    }

    /**
     * Increases the score counter for a question.
     *
     * Gets the score in DB and increases the score with one. Saves the new
     * score in DB.
     * The score is used to rank the question.
     *
     * @param  int $id  the question id for the question to rank.
     *
     * @return boolean  true if the increased value could be saved in DB, false
     *                  otherwise.
     */
    protected function increaseScoreCounter($id)
    {
        $score = $this->getScoreNumber($id);
        $score = $score === false ? 0 : ++$score;

        $isSaved = $this->questions->save(array(
            'id'        => $id,
            'score'     => $score,
        ));

        return $isSaved;
    }

    /**
     * Gets the questions ranking score from DB.
     *
     * Gets the questions ranking score from DB, if found. If not, false is
     * returned.
     *
     * @param  int $id  the id of the question.
     * @return int | false  the ranking score for the question. False, if
     *                      not found.
     */
    protected function getScoreNumber($id)
    {
        $score = $this->questions->query('score')
            ->where('id = ?')
            ->execute([$id]);

        $score = empty($score) ? false : $score[0]->score;

        return $score;
    }

    /**
     * Gets the question id.
     *
     * Gets the question id. Method needed because of heritage from the
     * Vote class.
     *
     * @param  int $id  the question id.
     *
     * @return int      the question id.
     */
    protected function getQuestionId($id)
    {
        return $id;
    }

    /**
     * Decreases the score counter for a question.
     *
     * Gets the score in DB and decreases the score with one. Saves the new
     * score in DB.
     * The score is used to rank the question.
     *
     * @param  int $id  the question id for the question to rank.
     *
     * @return boolean  true if the decreased value could be saved in DB, false
     *                  otherwise.
     */
    protected function decreaseScoreCounter($id)
    {
        $score = $this->getScoreNumber($id);
        $score = $score === false ? 0 : --$score;

        $isSaved = $this->questions->save(array(
            'id'        => $id,
            'score'     => $score,
        ));

        return $isSaved;
    }
}
