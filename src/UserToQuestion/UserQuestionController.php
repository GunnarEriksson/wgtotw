<?php

namespace Anax\UserToQuestion;

class UserQuestionController implements \Anax\DI\IInjectionAware
{
    use \Anax\DI\TInjectable;

    /**
     * Initialize the controller.
     *
     * @return void
     */
    public function initialize()
    {
        $this->di->session();

        $this->userToQuestion = new \Anax\UserToQuestion\User2Question();
        $this->userToQuestion->setDI($this->di);
    }

    public function addAction($userId, $questionId, $pointer)
    {
        $isAdded = $this->addQuestionToUser($userId, $questionId);

        if ($isAdded === false) {
            $pointer->AddOutput("<p><i>Varning! Kunde inte knyta användare till frågan!</i></p>");
        }
    }

    private function addQuestionToUser($userId, $questionId)
    {
        $isSaved = $this->userToQuestion->create(array(
            'idUser'    => intval($userId),
            'idQuestion'  => $questionId,
        ));

        return $isSaved;
    }
}
