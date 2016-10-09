<?php

namespace Anax\UserToAnswer;

/**
 * User Answer controller
 *
 * Communicates with the mapping table, which maps user with the related
 * answer in the database.
 * Handles all mapping tasks between user and the related answer.
 */
class UserAnswerController implements \Anax\DI\IInjectionAware
{
    use \Anax\DI\TInjectable;

    /**
     * Initialize the controller.
     *
     * Initializes the session, the user to
     * answer model.
     *
     * @return void
     */
    public function initialize()
    {
        $this->session();

        $this->userToAnswer = new \Anax\UserToAnswer\User2Answer();
        $this->userToAnswer->setDI($this->di);
    }

    /**
     * Adds a connection between a user and an answer.
     *
     * Adds a connection between a user and an answer if the user is allowed
     * to add an answer and the request comes from another controller.
     *
     * If it is not allowed to add an answer to a user, the page not found is
     * shown.
     * If the answer could not be mapped to a user, a flash error message is
     * created.
     *
     * @param int $userId the user id to be mapped to an answer id.
     * @param int $answerId  the answer id to be mapped to a user id.
     *
     * @return void
     */
    public function addAction($userId, $answerId)
    {
        if ($this->isAllowedToAddAnswerToUser($userId, $answerId)) {
            if ($this->addAnswerToUser($userId, $answerId) === false) {
                $warningMessage = "Kunde inte lägga till svar till användare i DB!";
                $this->flash->warningMessage($warningMessage);
            }
        } else {
            $this->pageNotFound();
        }
    }

    /**
     * Helper method to check if it is allowed to add a answer to an user.
     *
     * Checks if the user is allowed to add an answer and the request is from
     * another controller and not directly from the browsers address bar.
     *
     * @param  int  $userId     the id of the user who wants to add an answer.
     * @param  int  $answerId   the id of the last inserted answer id to check
     *                          against the id in the session.
     *
     * @return boolean          true if it is allowed to map an answer to a
     *                          user, false otherwise.
     */
    private function isAllowedToAddAnswerToUser($userId, $answerId)
    {
        $isAllowed = false;

        if ($this->LoggedIn->isAllowed($userId)) {
            $isAllowed = $this->isIdLastInserted($answerId);
        }

        return $isAllowed;
    }

    /**
     * Helper method to check if the answer id is the last inserted id.
     *
     * Checks if the call is from a controller and not via the browsers
     * address field. The controller who redirects saves the answer id in the
     * session. If no id is found, the call to the action method must come
     * from elsewhere.
     *
     * @param  int  $answerId the answer id from the last insterted id.
     *
     * @return boolean  true if call is from a redirect, false otherwise.
     */
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

    /**
     * Helper method to add an answer to a user in DB.
     *
     * Connects an answer to a user in DB.
     *
     * @param int $userId   the user id to be connected to an answer id.
     * @param int $answerId the answer id to be connected to a user id.
     *
     * @return boolean true if mapping is saved, false otherwise.
     */
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
