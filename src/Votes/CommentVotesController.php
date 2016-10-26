<?php

namespace Anax\Votes;

/**
 * Comment Votes controller
 *
 * Communicates with the mapping table, which maps user with a comment
 * in the database.
 * Handles all comment voting tasks between user and the related comment.
 *
 * Inherits from Vote class.
 */
class CommentVotesController extends Vote
{
    /**
     * Initialize the controller.
     *
     * Initializes the comment vote model, the comment model
     * and the question model.
     *
     * @return void
     */
    public function initialize()
    {
        $this->commentVotes = new \Anax\Votes\CommentVote();
        $this->commentVotes->setDI($this->di);

        $this->comments = new \Anax\Comments\Comment();
        $this->comments->setDI($this->di);

        $this->questions = new \Anax\Questions\Question();
        $this->questions->setDI($this->di);
    }

    /**
     * Gets the user ID for a specific comment in DB.
     *
     * Gets the ID of the comment writer in DB.
     *
     * @param  int $id  the id of the comment.
     *
     * @return int      the ID of the comment writer.
     */
    protected function getUserId($id)
    {
        $userId = $this->comments->query('U.id')
            ->join('user2comment AS U2C', 'U2C.idComment = lf_comment.id')
            ->join('user AS U', 'U2C.idUser = U.id')
            ->where('lf_comment.id = ?')
            ->execute([$id]);

        $userId = empty($userId) ? false : $userId[0]->id;

        return $userId;
    }

    /**
     * Checks if the voter has already voted on the comment.
     *
     * Checks in the mapping table in DB, if voter has already voted on the
     * comment.
     *
     * @param  int  $id         the id of the comment.
     * @param  int  $userId     the user id of the voter.
     *
     * @return boolean          true if user already has voted, false otherwise.
     */
    protected function hasUserVoted($id, $userId)
    {
        $id = $this->commentVotes->query('lf_commentvote.id')
            ->where('lf_commentvote.idComment = ? AND lf_commentvote.idUser = ?')
            ->execute([$id, $userId]);

        $hasVoted = empty($id) ? false : true;

        return $hasVoted;
    }

    /**
     * Adds that a user has voted on a comment.
     *
     * Maps a user id to a comment id, which marks that a user has voted on
     * a comment.
     *
     * @param int $id       the id of the comment.
     * @param int $userId   the user id of the voter.
     */
    protected function addUserAsVoter($id, $userId)
    {
        $isSaved = $this->commentVotes->create(array(
            'idComment'  => $id,
            'idUser'    => $userId,
        ));

        return $isSaved;
    }

    /**
     * Increases the score counter for a comment.
     *
     * Gets the score in DB and increases the score with one. Saves the new
     * score in DB.
     * The score is used to rank the comment.
     *
     * @param  int $id  the comment id for the comment to rank.
     *
     * @return boolean  true if the increased value could be saved in DB, false
     *                  otherwise.
     */
    protected function increaseScoreCounter($id)
    {
        $score = $this->getScoreNumber($id);
        $score = $score === false ? 0 : ++$score;

        $isSaved = $this->comments->save(array(
            'id'        => $id,
            'score'     => $score,
        ));

        return $isSaved;
    }

    /**
     * Gets the comment ranking score from DB.
     *
     * Gets the comment ranking score from DB, if found. If not, false is
     * returned.
     *
     * @param  int $id  the id of the comment.
     * @return int | false  the ranking score for the comment. False, if not found.
     */
    protected function getScoreNumber($id)
    {
        $score = $this->comments->query('score')
            ->where('id = ?')
            ->execute([$id]);

        $score = empty($score) ? false : $score[0]->score;

        return $score;
    }

    /**
     * Gets the related question ID for the comment.
     *
     * Gets the question ID for which the comment belongs to. Handles both if
     * a comment belongs directly to a comment or via an answer (belongs to an
     * answer).
     *
     * @param  int $id      the ID of the comment.
     * @return int | false  the question ID which the comment belongs to, if found.
     *                      False otherwise.
     */
    protected function getQuestionId($id)
    {
        $answerId = $this->getAnswerIdFromCommentId($id);
        if ($answerId === false) {
            $questionId = $this->getQuestionIdFromCommentId($id);
        } else {
            $questionId = $this->getQuestionIdFromAnswerId($answerId);
        }

        return $questionId;
    }

    /**
     * Helper method to get the related answer ID from a comment ID in DB.
     *
     * Gets the related answer ID if the comment belongs to an answer.
     *
     * @param  int $commentId   the comment ID.
     *
     * @return int | false      the answer ID if comment belongs to an
     *                          answer, false otherwise.
     */
    private function getAnswerIdFromCommentId($commentId)
    {
        $answerId = $this->comments->query('A.id')
            ->join('answer2comment AS A2C', 'A2C.idComment = lf_comment.id')
            ->join('answer AS A', 'A2C.idAnswer = A.id')
            ->where('lf_comment.id = ?')
            ->execute([$commentId]);

        $answerId = empty($answerId) ? false : $answerId[0]->id;

        return $answerId;
    }

    /**
     * Helper method to get the related question ID from a comment ID in DB.
     *
     * Gets the related question ID if the comment belongs to a question.
     *
     * @param  int $commentId   the comment ID.
     *
     * @return int | false      the question ID if comment belongs to a
     *                          comment, false otherwise.
     */
    private function getQuestionIdFromCommentId($commentId)
    {
        $questionId = $this->comments->query('Q.id')
            ->join('question2comment AS Q2C', 'Q2C.idComment = lf_comment.id')
            ->join('question AS Q', 'Q2C.idQuestion = Q.id')
            ->where('lf_comment.id = ?')
            ->execute([$commentId]);

        $questionId = empty($questionId) ? false : $questionId[0]->id;

        return $questionId;
    }

    /**
     * Helper function to get related question ID from the answer ID in DB.
     *
     * Gets the related question ID from the answer ID. Used when a comment
     * belongs to an answer.
     *
     * @param  int $answerId    the answer ID.
     * @return int | false      the question ID, if the answer belongs to a
     *                          question. False otherwise.
     */
    private function getQuestionIdFromAnswerId($answerId)
    {
        $questionId = $this->questions->query('lf_question.id')
            ->join('question2answer AS Q2A', 'Q2A.idQuestion = lf_question.id')
            ->join('answer AS A', 'Q2A.idAnswer = A.id')
            ->where('A.id = ?')
            ->execute([$answerId]);

        $questionId = empty($questionId) ? false : $questionId[0]->id;

        return $questionId;
    }

    /**
     * Decreases the score counter for comment.
     *
     * Gets the score in DB and decreases the score with one. Saves the new
     * score in DB.
     * The score is used to rank the comment.
     *
     * @param  int $id  the comment id for the comment to rank.
     *
     * @return boolean  true if the decreased value could be saved in DB, false
     *                  otherwise.
     */
    protected function decreaseScoreCounter($id)
    {
        $score = $this->getScoreNumber($id);
        $score = $score === false ? 0 : --$score;

        $isSaved = $this->comments->save(array(
            'id'        => $id,
            'score'     => $score,
        ));

        return $isSaved;
    }
}
