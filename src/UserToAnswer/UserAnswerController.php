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

    public function addAction($userId, $answerId)
    {
        if ($this->isAllowedToAddAnswerToUser($userId, $answerId)) {
            if ($this->addAnswerToUser($userId, $answerId) === false) {
                $warningMessage = "Kunde inte användare till svar i DB!";
                $this->flash->warningMessage($warningMessage);
            }
        } else {
            $this->pageNotFound();
        }
    }

    private function isAllowedToAddAnswerToUser($userId, $answerId)
    {
        $isAllowed = false;

        if ($this->LoggedIn->isAllowed($userId)) {
            $isAllowed = $this->isIdLastInserted($answerId);
        }

        return $isAllowed;
    }

    private function isIdLastInserted($answerId)
    {
        $isAllowed = false;

        $lastInsertedId = $this->session->get('lastInsertedId');
        if (!empty($lastInsertedId)) {
            if ($lastInsertedId === $answerId) {
                $isAllowed = true;
            }
        }

        return $isAllowed;
    }

    private function addAnswerToUser($userId, $answerId)
    {
        $isSaved = $this->userToAnswer->create(array(
            'idUser'    => intval($userId),
            'idAnswer'  => $answerId,
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
