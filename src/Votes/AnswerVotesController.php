<?php

namespace Anax\Votes;

/**
 * Answer Votes controller
 *
 * Communicates with the mapping table, which maps user with an answer
 * in the database.
 * Handles all answer voting tasks between user and the related answer.
 *
 * Inherits from Vote class.
 */
class AnswerVotesController extends Vote
{

    /**
     * Initialize the controller.
     *
     * Initializes the answer vote model and the
     * answer model.
     *
     * @return void
     */
    public function initialize()
    {
        $this->answerVotes = new \Anax\Votes\AnswerVote();
        $this->answerVotes->setDI($this->di);

        $this->answers = new \Anax\Answers\Answer();
        $this->answers->setDI($this->di);
    }

    /**
     * Gets the user ID for a specific answer in DB.
     *
     * Gets the ID of the answer writer in DB.
     *
     * @param  int $id  the id of the answer.
     *
     * @return int      the ID of the answer writer.
     */
    protected function getUserId($id)
    {
        $userId = $this->answers->query('U.id')
            ->join('user2answer AS U2A', 'U2A.idAnswer = lf_answer.id')
            ->join('user AS U', 'U2A.idUser = U.id')
            ->where('lf_answer.id = ?')
            ->execute([$id]);

        $userId = empty($userId) ? false : $userId[0]->id;

        return $userId;
    }

    /**
     * Checks if the voter has already voted on the answer.
     *
     * Checks in the mapping table in DB, if voter has already voted on the
     * answer.
     *
     * @param  int  $id         the id of the answer.
     * @param  int  $userId     the user id of the voter.
     *
     * @return boolean          true if user already has voted, false otherwise.
     */
    protected function hasUserVoted($id, $userId)
    {
        $id = $this->answerVotes->query('lf_answervote.id')
            ->where('lf_answervote.idAnswer = ? AND lf_answervote.idUser = ?')
            ->execute([$id, $userId]);

        $hasVoted = empty($id) ? false : true;

        return $hasVoted;
    }

    /**
     * Adds that a user has voted on an answer.
     *
     * Maps a user id to an answer id, which marks that a user has voted on
     * an answer.
     *
     * @param int $id       the id of the answer.
     * @param int $userId   the user id of the voter.
     */
    protected function addUserAsVoter($id, $userId)
    {
        $isSaved = $this->answerVotes->create(array(
            'idAnswer'  => $id,
            'idUser'    => $userId,
        ));

        return $isSaved;
    }

    /**
     * Increases the score counter for an answer.
     *
     * Gets the score in DB and increases the score with one. Saves the new
     * score in DB.
     * The score is used to rank the answer.
     *
     * @param  int $id  the answer id for the answer to rank.
     *
     * @return boolean  true if the increased value could be saved in DB, false
     *                  otherwise.
     */
    protected function increaseScoreCounter($id)
    {
        $score = $this->getScoreNumber($id);
        $score = $score === false ? 0 : ++$score;

        $isSaved = $this->answers->save(array(
            'id'        => $id,
            'score'     => $score,
        ));

        return $isSaved;
    }

    /**
     * Gets the answers ranking score from DB.
     *
     * Gets the answers ranking score from DB, if found. If not, false is
     * returned.
     *
     * @param  int $id  the id of the answer.
     * @return int | false  the ranking score for the answer. False, if not found.
     */
    protected function getScoreNumber($id)
    {
        $score = $this->answers->query('score')
            ->where('id = ?')
            ->execute([$id]);

        $score = empty($score) ? false : $score[0]->score;

        return $score;
    }

    /**
     * Gets the related question id for the answer.
     *
     * Gets the question id for which the answer belongs to.
     *
     * @param  int $id      the id of the answer.
     * @return int | false  the question id which the answer belongs to, if found.
     *                      False otherwise.
     */
    protected function getQuestionId($id)
    {
        $questionId = $this->answers->query('Q.id')
            ->join('question2answer AS Q2A', 'Q2A.idAnswer = lf_answer.id')
            ->join('question AS Q', 'Q2A.idQuestion = Q.id')
            ->where('lf_answer.id = ?')
            ->execute([$id]);

        $questionId = empty($questionId) ? false : $questionId[0]->id;

        return $questionId;
    }

    /**
     * Decreases the score counter for an answer.
     *
     * Gets the score in DB and decreases the score with one. Saves the new
     * score in DB.
     * The score is used to rank the answer.
     *
     * @param  int $id  the answer id for the answer to rank.
     *
     * @return boolean  true if the decreased value could be saved in DB, false
     *                  otherwise.
     */
    protected function decreaseScoreCounter($id)
    {
        $score = $this->getScoreNumber($id);
        $score = $score === false ? 0 : --$score;

        $isSaved = $this->answers->save(array(
            'id'        => $id,
            'score'     => $score,
        ));

        return $isSaved;
    }
}
