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
        if ($this->isAllowedToAddQuestionToUser($userId, $questionId)) {
            if ($this->addQuestionToUser($userId, $questionId) === false) {
                $warningMessage = "Kunde inte knyta användare till frågan i DB!";
                $this->flash->warningMessage($warningMessage);
            }
        } else {
            $this->pageNotFound();
        }
    }

    private function isAllowedToAddQuestionToUser($userId, $questionId)
    {
        $isAllowed = false;

        if ($this->LoggedIn->isAllowed($userId)) {
            $isAllowed = $this->isIdLastInserted($questionId);
        }

        return $isAllowed;
    }

    private function isIdLastInserted($questionId)
    {
        $isAllowed = false;

        $lastInsertedId = $this->session->get('lastInsertedId');
        if (!empty($lastInsertedId)) {
            if ($lastInsertedId === $questionId) {
                $isAllowed = true;
            }
        }

        return $isAllowed;
    }

    private function addQuestionToUser($userId, $questionId)
    {
        $isSaved = $this->userToQuestion->create(array(
            'idUser'    => intval($userId),
            'idQuestion'  => $questionId,
        ));

        return $isSaved;
    }

    /**
     * Helper method to show page 404, page not found.
     *
     * Shows page 404 with the text that the page could not be found and you
     * must login to get the page you are looking for.
     *
     * @return void
     */
    private function pageNotFound()
    {
        $this->dispatcher->forward([
            'controller' => 'errors',
            'action'     => 'page-not-found'
        ]);
    }
}
