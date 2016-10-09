<?php

namespace Anax\UserToQuestion;

/**
 * User Question controller
 *
 * Communicates with the mapping table, which maps user with the related
 * questions in the database.
 * Handles all mapping tasks between user and the related questions.
 */
class UserQuestionController implements \Anax\DI\IInjectionAware
{
    use \Anax\DI\TInjectable;

    /**
     * Initialize the controller.
     *
     * Initializes the session, the user to
     * question model.
     *
     * @return void
     */
    public function initialize()
    {
        $this->session();

        $this->userToQuestion = new \Anax\UserToQuestion\User2Question();
        $this->userToQuestion->setDI($this->di);
    }

    /**
     * Adds a connection between a user and a question.
     *
     * Adds a connection between a user and a question if the user is allowed
     * to add a question and the request comes from another controller.
     *
     * If it is not allowed to add a question to a user, the page not found is
     * shown.
     * If the question could not be mapped to a user, a flash error message is
     * created.
     *
     * @param int $userId the user id to be mapped to a question id.
     * @param int $questionId the question id to be mapped to a user id.
     *
     * @return void
     */
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

    /**
     * Helper method to check if it is allowed to add a question to a user.
     *
     * Checks if the user is allowed to add a question and the request is from
     * another controller and not directly from the browsers address bar.
     *
     * @param  int  $userId     the id of the user who wants to add a question.
     * @param  int  $questionId the id of the last inserted question id to check
     *                          against the id in the session.
     *
     * @return boolean          true if it is allowed to map a question to a
     *                          user, false otherwise.
     */
    private function isAllowedToAddQuestionToUser($userId, $questionId)
    {
        $isAllowed = false;

        if ($this->LoggedIn->isAllowed($userId)) {
            $isAllowed = $this->isIdLastInserted($questionId);
        }

        return $isAllowed;
    }

    /**
     * Helper method to check if the question id is the last inserted id.
     *
     * Checks if the call is from a controller and not via the browsers
     * address field. The controller who redirects saves the question id in the
     * session. If no id is found, the call to the action method must come
     * from elsewhere.
     *
     * @param  int  $questionId the question id from the last insterted id.
     *
     * @return boolean  true if call is from a redirect, false otherwise.
     */
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

    /**
     * Helper method to add a question to a user in DB.
     *
     * Connects a question to a user in DB.
     *
     * @param int $userId       the user id to be connected to a question id.
     * @param int $questionId   the question id to be connected to a user id.
     *
     * @return boolean true if mapping is saved, false otherwise.
     */
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
