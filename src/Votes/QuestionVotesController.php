<?php

namespace Anax\Votes;

class QuestionVotesController extends Vote
{
    /**
     * Initialize the controller.
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

    protected function getUserId($id)
    {
        $userId = $this->questions->query('U.id')
            ->join('User2Question AS U2Q', 'U2Q.idQuestion = Lf_Question.id')
            ->join('User AS U', 'U2Q.idUser = U.id')
            ->where('Lf_Question.id = ?')
            ->execute([$id]);

        $userId = empty($userId) ? false : $userId[0]->id;

        return $userId;
    }

    protected function hasUserVoted($id, $userId)
    {
        $id = $this->questionVotes->query('Lf_QuestionVote.id')
            ->where('Lf_QuestionVote.idQuestion = ? AND Lf_QuestionVote.idUser = ?')
            ->execute([$id, $userId]);

        $hasVoted = empty($id) ? false : true;

        return $hasVoted;
    }

    protected function addUserAsVoter($id, $userId)
    {
        $isSaved = $this->questionVotes->create(array(
            'idQuestion'    => $id,
            'idUser'  => $userId,
        ));

        return $isSaved;
    }

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

    protected function getScoreNumber($id)
    {
        $score = $this->questions->query('score')
            ->where('id = ?')
            ->execute([$id]);

        $score = empty($score) ? false : $score[0]->score;

        return $score;
    }

    protected function getQuestionId($id)
    {
        return $id;
    }

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
