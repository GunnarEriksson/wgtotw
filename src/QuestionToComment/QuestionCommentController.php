<?php

namespace Anax\QuestionToComment;

class QuestionCommentController implements \Anax\DI\IInjectionAware
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

        $this->questionToComment = new \Anax\QuestionToComment\Question2Comment();
        $this->questionToComment->setDI($this->di);
    }

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

    private function isAllowedToAddCommentToQuestion($commentId)
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
