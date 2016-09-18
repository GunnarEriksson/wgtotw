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
        $this->di->session();

        $this->answerToComment = new \Anax\AnswerToComment\Answer2Comment();
        $this->answerToComment->setDI($this->di);
    }

    public function addAction($answerId, $commentId, $pointer)
    {
        $isAdded = $this->addCommentToAnswer($answerId, $commentId);

        if ($isAdded === false) {
            $pointer->AddOutput("<p><i>Varning! Kunde inte knyta kommentar till svaret!</i></p>");
        }
    }

    private function addCommentToAnswer($answerId, $commentId)
    {
        $isSaved = $this->answerToComment->create(array(
            'idAnswer'    => intval($answerId),
            'idComment'  => $commentId,
        ));

        return $isSaved;
    }
}
