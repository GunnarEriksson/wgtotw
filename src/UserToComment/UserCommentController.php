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
        $this->di->session();

        $this->userToComment = new \Anax\UserToComment\User2Comment();
        $this->userToComment->setDI($this->di);
    }

    public function addAction($userId, $commentId, $pointer)
    {
        $isAdded = $this->addCommentToUser($userId, $commentId);

        if ($isAdded === false) {
            $pointer->AddOutput("<p><i>Varning! Kunde inte knyta användare till kommentar!</i></p>");
        }
    }

    private function addCommentToUser($userId, $commentId)
    {
        $isSaved = $this->userToComment->create(array(
            'idUser'    => intval($userId),
            'idComment'  => $commentId,
        ));

        return $isSaved;
    }
}
