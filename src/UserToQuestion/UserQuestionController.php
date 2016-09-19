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

    public function addAction($userId, $questionId)
    {
        $isAdded = $this->addQuestionToUser($userId, $questionId);

        if ($isAdded === false) {
            $this->showErrorInfo("Varning! Kunde inte knyta användare till frågan!");
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

    private function showErrorInfo($info)
    {
        $content = [
            'title'         => 'Ett fel har uppstått!',
            'subtitle'      => 'Problem med knyta frågeställare till fråga',
            'message'       => $info,
            'url'           => $_SERVER["HTTP_REFERER"],
        ];

        $this->dispatcher->forward([
            'controller' => 'errors',
            'action'     => 'view',
            'params'     => [$content]
        ]);
    }
}
