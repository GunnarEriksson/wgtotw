<?php

namespace Anax\AnswerToComment;

class AnswerCommentController implements \Anax\DI\IInjectionAware
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

        $this->answerToComment = new \Anax\AnswerToComment\Answer2Comment();
        $this->answerToComment->setDI($this->di);
    }

    public function addAction($answerId, $commentId)
    {
        if ($this->isMandatoryParametersPresent($answerId, $commentId)) {
            $this->addCommentToAnswer($answerId, $commentId);
        } else {
            $errorMessage = "Kan EJ knyta kommentar till svar. Id parametrar saknas!";
            $this->flash->errorMessage($errorMessage);
        }
    }

    private function isMandatoryParametersPresent($answerId, $commentId)
    {
        $isPresent = false;

        if (isset($answerId) && isset($commentId)) {
            $isPresent = true;
        }

        return $isPresent;
    }

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

    private function isAllowedToAddCommentToAnswer($commentId)
    {
        $isAllowed = false;

        if ($this->LoggedIn->isLoggedin()) {
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

    private function addCommentToAnswerInDb($answerId, $commentId)
    {
        $isSaved = $this->answerToComment->create(array(
            'idAnswer'    => intval($answerId),
            'idComment'  => $commentId,
        ));

        return $isSaved;
    }
}
