<?php

namespace Anax\UserToComment;

/**
 * User Comment controller
 *
 * Communicates with the mapping table, which maps user with the related
 * comments in the database.
 * Handles all mapping tasks between user and the related comments.
 */
class UserCommentController implements \Anax\DI\IInjectionAware
{
    use \Anax\DI\TInjectable;

    /**
     * Initialize the controller.
     *
     * Initializes the session, the user to
     * comment model.
     *
     * @return void
     */
    public function initialize()
    {
        $this->session();

        $this->userToComment = new \Anax\UserToComment\User2Comment();
        $this->userToComment->setDI($this->di);
    }

    /**
     * Adds a connection between a user and a comment.
     *
     * Adds a connection between a user and a comment if the user is allowed
     * to add a comment and the request comes from another controller.
     *
     * If it is not allowed to add a comment to a user, the page not found is
     * shown.
     * If the comment could not be mapped to a user, a flash error message is
     * created.
     *
     * @param int $userId the user id to be mapped to a comment id.
     * @param int $commentId  the comment id to be mapped to a user id.
     *
     * @return void
     */
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

    /**
     * Helper method to check if it is allowed to add a comment to a user.
     *
     * Checks if the user is allowed to add a comment and the request is from
     * another controller and not directly from the browsers address bar.
     *
     * @param  int  $userId     the id of the user who wants to add a comment.
     * @param  int  $commentId  the id of the last inserted comment id to check
     *                          against the id in the session.
     *
     * @return boolean          true if it is allowed to map a comment to a
     *                          user, false otherwise.
     */
    private function isAllowedToAddCommentToUser($userId, $commentId)
    {
        $isAllowed = false;

        if ($this->LoggedIn->isAllowed($userId)) {
            $isAllowed = $this->isIdLastInserted($commentId);
        }

        return $isAllowed;
    }

    /**
     * Helper method to check if the comment id is the last inserted id.
     *
     * Checks if the call is from a controller and not via the browsers
     * address field. The controller who redirects saves the comment id in the
     * session. If no id is found, the call to the action method must come
     * from elsewhere.
     *
     * @param  int  $commentId the comment id from the last insterted id.
     *
     * @return boolean  true if call is from a redirect, false otherwise.
     */
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

    /**
     * Helper method to add a comment to a user in DB.
     *
     * Connects a comment to a user in DB.
     *
     * @param int $userId   the user id to be connected to a comment id.
     * @param int $commentId the comment id to be connected to a user id.
     *
     * @return boolean true if mapping is saved, false otherwise.
     */
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
     * Redirects to the Errors controller to show the page 404 with the text
     * that the page could not be found and you must login to get the page
     * you are looking for.
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
