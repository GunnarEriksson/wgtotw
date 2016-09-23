<?php

namespace Anax\Votes;

class AnswerVotesController extends Vote
{

    /**
     * Initialize the controller.
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

    protected function getUserId($id)
    {
        $userId = $this->answers->query('U.id')
            ->join('User2Answer AS U2A', 'U2A.idAnswer = Lf_Answer.id')
            ->join('User AS U', 'U2A.idUser = U.id')
            ->where('Lf_Answer.id = ?')
            ->execute([$id]);

        $userId = empty($userId) ? false : $userId[0]->id;

        return $userId;
    }

    protected function hasUserVoted($id, $userId)
    {
        $id = $this->answerVotes->query('Lf_AnswerVote.id')
            ->where('Lf_AnswerVote.idAnswer = ? AND Lf_AnswerVote.idUser = ?')
            ->execute([$id, $userId]);

        $hasVoted = empty($id) ? false : true;

        return $hasVoted;
    }

    protected function addUserAsVoter($id, $userId)
    {
        $isSaved = $this->answerVotes->create(array(
            'idAnswer'  => $id,
            'idUser'    => $userId,
        ));

        return $isSaved;
    }

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

    protected function getScoreNumber($id)
    {
        $score = $this->answers->query('score')
            ->where('id = ?')
            ->execute([$id]);

        $score = empty($score) ? false : $score[0]->score;

        return $score;
    }

    protected function getQuestionId($id)
    {
        $questionId = $this->answers->query('Q.id')
            ->join('Question2Answer AS Q2A', 'Q2A.idAnswer = Lf_Answer.id')
            ->join('Question AS Q', 'Q2A.idQuestion = Q.id')
            ->where('Lf_Answer.id = ?')
            ->execute([$id]);

        $questionId = empty($questionId) ? false : $questionId[0]->id;

        return $questionId;
    }

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
