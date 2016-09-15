<?php

namespace Anax\UserToAnswer;

class UserAnswerController implements \Anax\DI\IInjectionAware
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

        $this->userToAnswer = new \Anax\UserToAnswer\User2Answer();
        $this->userToAnswer->setDI($this->di);
    }

    public function addAction($userId, $answerId, $pointer)
    {
        $isAdded = $this->addAnswerToUser($userId, $answerId);

        if ($isAdded === false) {
            $pointer->AddOutput("<p><i>Varning! Kunde inte knyta användare till svaret!</i></p>");
        }
    }

    private function addAnswerToUser($userId, $answerId)
    {
        $isSaved = $this->userToAnswer->create(array(
            'idUser'    => intval($userId),
            'idAnswer'  => $answerId,
        ));

        return $isSaved;
    }
}
