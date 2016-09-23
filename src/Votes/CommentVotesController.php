<?php

namespace Anax\Votes;

class CommentVotesController extends Vote
{
    /**
     * Initialize the controller.
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

    protected function getUserId($id)
    {
        $userId = $this->comments->query('U.id')
            ->join('User2Comment AS U2C', 'U2C.idComment = Lf_Comment.id')
            ->join('User AS U', 'U2C.idUser = U.id')
            ->where('Lf_Comment.id = ?')
            ->execute([$id]);

        $userId = empty($userId) ? false : $userId[0]->id;

        return $userId;
    }

    protected function hasUserVoted($id, $userId)
    {
        $id = $this->commentVotes->query('Lf_CommentVote.id')
            ->where('Lf_CommentVote.idComment = ? AND Lf_CommentVote.idUser = ?')
            ->execute([$id, $userId]);

        $hasVoted = empty($id) ? false : true;

        return $hasVoted;
    }

    protected function addUserAsVoter($id, $userId)
    {
        $isSaved = $this->commentVotes->create(array(
            'idComment'  => $id,
            'idUser'    => $userId,
        ));

        return $isSaved;
    }

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

    protected function getScoreNumber($id)
    {
        $score = $this->comments->query('score')
            ->where('id = ?')
            ->execute([$id]);

        $score = empty($score) ? false : $score[0]->score;

        return $score;
    }

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

    private function getAnswerIdFromCommentId($commentId)
    {
        $answerId = $this->comments->query('A.id')
            ->join('Answer2Comment AS A2C', 'A2C.idComment = Lf_Comment.id')
            ->join('Answer AS A', 'A2C.idAnswer = A.id')
            ->where('Lf_Comment.id = ?')
            ->execute([$commentId]);

        $answerId = empty($answerId) ? false : $answerId[0]->id;

        return $answerId;
    }

    private function getQuestionIdFromCommentId($commentId)
    {
        $questionId = $this->comments->query('Q.id')
            ->join('Question2Comment AS Q2C', 'Q2C.idComment = Lf_Comment.id')
            ->join('Question AS Q', 'Q2C.idQuestion = Q.id')
            ->where('Lf_Comment.id = ?')
            ->execute([$commentId]);

        $questionId = empty($questionId) ? false : $questionId[0]->id;

        return $questionId;
    }

    private function getQuestionIdFromAnswerId($answerId)
    {
        $questionId = $this->questions->query('Lf_Question.id')
            ->join('Question2Answer AS Q2A', 'Q2A.idQuestion = Lf_Question.id')
            ->join('Answer AS A', 'Q2A.idAnswer = A.id')
            ->where('A.id = ?')
            ->execute([$answerId]);

        $questionId = empty($questionId) ? false : $questionId[0]->id;

        return $questionId;
    }

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
