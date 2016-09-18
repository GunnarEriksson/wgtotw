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
        $this->di->session();

        $this->questionToComment = new \Anax\QuestionToComment\Question2Comment();
        $this->questionToComment->setDI($this->di);
    }

    public function addAction($questionId, $commentId, $pointer)
    {
        $isAdded = $this->addCommentToQuestion($questionId, $commentId);

        if ($isAdded === false) {
            $pointer->AddOutput("<p><i>Varning! Kunde inte knyta kommentar till frågan!</i></p>");
        }
    }

    private function addCommentToQuestion($questionId, $commentId)
    {
        $isSaved = $this->questionToComment->create(array(
            'idQuestion'    => intval($questionId),
            'idComment'  => $commentId,
        ));

        return $isSaved;
    }
}
