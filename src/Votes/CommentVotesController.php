<?php

namespace Anax\Votes;

class CommentVotesController implements \Anax\DI\IInjectionAware
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

        $this->commentVotes = new \Anax\Votes\CommentVote();
        $this->commentVotes->setDI($this->di);

        $this->comments = new \Anax\Comments\Comment();
        $this->comments->setDI($this->di);

        $this->questions = new \Anax\Questions\Question();
        $this->questions->setDI($this->di);
    }

    public function increaseAction($commentId)
    {
        $userId = $this->getUserIdInSession();
        if ($this->isAllowedToVote($commentId, $userId)) {
            $isSaved = $this->addUserAsVoter($commentId, $userId);
            if ($isSaved) {
                if ($this->increaseScoreCounter($commentId)) {
                    $this->addActivityScoreToUser();
                }
            }
        }

        $questionId = $this->getQuestionId($commentId);

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

    private function isAllowedToVote($commentId, $userId)
    {
        $isAllowedToVote = false;

        if ($userId !== false) {
            $isAllowedToVote = $this->isUserAllowedToVote($commentId, $userId);
        }

        return $isAllowedToVote;
    }

    private function isUserAllowedToVote($commentId, $userId)
    {
        $isAllowedToVote = false;

        if ($this->isUserAuthorOfComment($commentId, $userId) === false) {
            if ($this->hasUserVoted($commentId, $userId) === false) {
                $isAllowedToVote = true;
            }
        }

        return $isAllowedToVote;
    }

    private function isUserAuthorOfComment($commentId, $userId)
    {
        $isUserTheAuthor = false;
        $authorId = $this->getUserIdOfComment($commentId);
        if ($userId === $authorId) {
            $isUserTheAuthor = true;
        }

        return $isUserTheAuthor;
    }

    private function getUserIdOfComment($commentId)
    {
        $userId = $this->comments->query('U.id')
            ->join('User2Comment AS U2C', 'U2C.idComment = Lf_Comment.id')
            ->join('User AS U', 'U2C.idUser = U.id')
            ->where('Lf_Comment.id = ?')
            ->execute([$commentId]);

        $userId = empty($userId) ? false : $userId[0]->id;

        return $userId;
    }

    private function hasUserVoted($commentId, $userId)
    {
        $id = $this->commentVotes->query('Lf_CommentVote.id')
            ->where('Lf_CommentVote.idComment = ? AND Lf_CommentVote.idUser = ?')
            ->execute([$commentId, $userId]);

        $hasVoted = empty($id) ? false : true;

        return $hasVoted;
    }

    private function addUserAsVoter($commentId, $userId)
    {
        $isSaved = $this->commentVotes->create(array(
            'idComment'  => $commentId,
            'idUser'    => $userId,
        ));

        return $isSaved;
    }

    private function increaseScoreCounter($commentId)
    {
        $score = $this->getScoreNumber($commentId);
        $score = $score === false ? 0 : ++$score;

        $isSaved = $this->comments->save(array(
            'id'        => $commentId,
            'score'     => $score,
        ));

        return $isSaved;
    }

    private function getScoreNumber($commentId)
    {
        $score = $this->comments->query('score')
            ->where('id = ?')
            ->execute([$commentId]);

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

    private function getQuestionId($commentId)
    {
        $answerId = $this->getAnswerIdFromCommentId($commentId);
        if ($answerId === false) {
            $questionId = $this->getQuestionIdFromCommentId($commentId);
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

    public function decreaseAction($commentId)
    {
        $userId = $this->getUserIdInSession();
        if ($this->isAllowedToVote($commentId, $userId)) {
            $isSaved = $this->addUserAsVoter($commentId, $userId);
            if ($isSaved) {
                if ($this->decreaseScoreCounter($commentId)) {
                    $this->addActivityScoreToUser();
                }
            }
        }

        $questionId = $this->getQuestionId($commentId);

        $this->dispatcher->forward([
            'controller' => 'questions',
            'action'     => 'id',
            'params'     => [$questionId]
        ]);
    }

    private function decreaseScoreCounter($commentId)
    {
        $score = $this->getScoreNumber($commentId);
        $score = $score === false ? 0 : --$score;

        $isSaved = $this->comments->save(array(
            'id'        => $commentId,
            'score'     => $score,
        ));

        return $isSaved;
    }
}
