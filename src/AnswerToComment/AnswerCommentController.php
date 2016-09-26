<?php

namespace Anax\AnswerToComment;

/**
 * Answer Comment controller
 *
 * Communicates with the mapping table, which maps comments with the related
 * answer in the database.
 * Handles all mapping tasks between comments and the related answer.
 */
class AnswerCommentController implements \Anax\DI\IInjectionAware
{
    use \Anax\DI\TInjectable;

    /**
     * Initialize the controller.
     *
     * Initializes the session, the answer to
     * comment model.
     *
     * @return void
     */
    public function initialize()
    {
        $this->session();

        $this->answerToComment = new \Anax\AnswerToComment\Answer2Comment();
        $this->answerToComment->setDI($this->di);
    }

    /**
     * Adds a connection between an answer and a comment.
     *
     * Adds a connection between an answer and a comment if the answer id and
     * comment id is present, otherwise it creates a flash error message.
     *
     * @param int $answerId  the answer id to be mapped to a question id.
     * @param int $commentId the comment id to be mapped to an answer id.
     *
     * @return void
     */
    public function addAction($answerId, $commentId)
    {
        if ($this->isMandatoryParametersPresent($answerId, $commentId)) {
            $this->addCommentToAnswer($answerId, $commentId);
        } else {
            $errorMessage = "Kan EJ knyta kommentar till svar. Id parametrar saknas!";
            $this->flash->errorMessage($errorMessage);
        }
    }

    /**
     * Helper method to check that mandatory parameters are present.
     *
     * Checks if answer id and comment id is present.
     *
     * @param  int  $answerId  the answer id.
     * @param  int  $commentId the comment id.
     *
     * @return boolean true if answer and comment id is present, false otherwise.
     */
    private function isMandatoryParametersPresent($answerId, $commentId)
    {
        $isPresent = false;

        if (isset($answerId) && isset($commentId)) {
            $isPresent = true;
        }

        return $isPresent;
    }

    /**
     * Helper method to connect a comment to a answer.
     *
     * @param int $answerId the answer id to be connected to a comment id.
     * @param  $commentId   the comment id to be connected to an answer id.
     *
     * @return void
     */
    private function addCommentToAnswer($answerId, $commentId)
    {
        if ($this->isAllowedToAddCommentToAnswer($commentId)) {
            if ($this->addCommentToAnswerInDb($answerId, $commentId) === false) {
                $warningMessage = "Kunde inte knyta kommentar till svar i DB!";
                $this->flash->warningMessage($warningMessage);
            }
        } else {
            $errorMessage = "Ej behörig att knyta kommentar till svar!";
            $this->flash->errorMessage($errorMessage);
        }
    }

    /**
     * Helper method to check if it is allowe to add a comment to an answer.
     *
     * Checks if the user has logged in and the call is from a redirect and not
     * via the browsers addess field.
     *
     * @param  int  $commentId the id of the comment to be connected to an answer.
     *
     * @return boolean  true if it is allowe to connect a comment to an answer,
     *                       false otherwise.
     */
    private function isAllowedToAddCommentToAnswer($commentId)
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
     * Helper method to add a comment to an answer in DB.
     *
     * Connects a comment to an answer in DB.
     *
     * @param int $answerId the answer id to be connected to a comment id.
     * @param int $commentId the comment id to be connected to an answer id.
     */
    private function addCommentToAnswerInDb($answerId, $commentId)
    {
        $isSaved = $this->answerToComment->create(array(
            'idAnswer'    => intval($answerId),
            'idComment'  => $commentId,
        ));

        return $isSaved;
    }
}
