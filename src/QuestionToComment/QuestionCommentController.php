<?php

namespace Anax\QuestionToComment;

/**
 * Question Comment controller
 *
 * Communicates with the mapping table, which maps questions with the related
 * comments in the database.
 * Handles all mapping tasks between question and the related comments.
 */
class QuestionCommentController implements \Anax\DI\IInjectionAware
{
    use \Anax\DI\TInjectable;

    /**
     * Initialize the controller.
     *
     * Initializes the session and the question to
     * comment model.
     *
     * @return void
     */
    public function initialize()
    {
        $this->session();

        $this->questionToComment = new \Anax\QuestionToComment\Question2Comment();
        $this->questionToComment->setDI($this->di);
    }

    /**
     * Adds a connection between a question and a comment.
     *
     * Adds a connection between a question and a comment if the question id and
     * comment id is present, otherwise it creates a flash error message.
     *
     * @param int $questionId   the question id to be mapped to a comment id.
     * @param int $commentId    the comment id to be mapped to a question id.
     *
     * @return void
     */
    public function addAction($questionId, $commentId)
    {
        if ($this->isAllowedToAddCommentToQuestion($commentId)) {
            if ($this->addCommentToQuestion($questionId, $commentId) === false) {
                $warningMessage = "Kunde inte knyta kommentar till fråga i DB!";
                $this->flash->warningMessage($warningMessage);
            }
        } else {
            $this->pageNotFound();
        }
    }

    /**
     * Helper method to check if it is allowed to add a comment to a question.
     *
     * Checks if the user has logged in and the call is from a redirect and not
     * via the browsers addess field.
     *
     * @param  int $commentId the id of the comment to be connected to a question.
     *
     * @return boolean  true if it is allowe to connect a comment to a question,
     *                       false otherwise.
     */
    private function isAllowedToAddCommentToQuestion($commentId)
    {
        $isAllowed = false;

        if ($this->LoggedIn->isLoggedin()) {
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
     * Helper method to add a comment to a question in DB.
     *
     * Connects a comment to a question in DB.
     *
     * @param int $questionId the question id to be connected to a comment id.
     * @param int $commentId the comment id to be connected to a question id.
     *
     * @return boolean true if saved, false otherwise.
     */
    private function addCommentToQuestion($questionId, $commentId)
    {
        $isSaved = $this->questionToComment->create(array(
            'idQuestion'    => intval($questionId),
            'idComment'     => $commentId,
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
