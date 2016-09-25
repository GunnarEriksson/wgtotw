<?php

namespace Anax\UserToComment;

class UserCommentController implements \Anax\DI\IInjectionAware
{
    use \Anax\DI\TInjectable;

    /**
     * Initialize the controller.
     *
     * @return void
     */
    public function initialize()
    {
        $this->session();

        $this->userToComment = new \Anax\UserToComment\User2Comment();
        $this->userToComment->setDI($this->di);
    }

    public function addAction($userId, $commentId)
    {
        if ($this->isAllowedToAddCommentToUser($userId, $commentId)) {
            if ($this->addCommentToUser($userId, $commentId) === false) {
                $warningMessage = "Kunde inte knyta användare till svar i DB!";
                $this->flash->warningMessage($warningMessage);
            }
        } else {
            $this->pageNotFound();
        }
    }

    private function isAllowedToAddCommentToUser($userId, $commentId)
    {
        $isAllowed = false;

        if ($this->LoggedIn->isAllowed($userId)) {
            $isAllowed = $this->isIdLastInserted($commentId);
        }

        return $isAllowed;
    }

    private function isIdLastInserted($commentId)
    {
        $isAllowed = false;

        $lastInsertedId = $this->session->get('lastInsertedId');
        if (!empty($lastInsertedId)) {
            if ($lastInsertedId === $commentId) {
                $isAllowed = true;
            }
        }

        return $isAllowed;
    }

    private function addCommentToUser($userId, $commentId)
    {
        $isSaved = $this->userToComment->create(array(
            'idUser'    => intval($userId),
            'idComment'  => $commentId,
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
