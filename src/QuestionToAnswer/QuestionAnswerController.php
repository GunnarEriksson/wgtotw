<?php

namespace Anax\QuestionToAnswer;

class QuestionAnswerController implements \Anax\DI\IInjectionAware
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

        $this->questionToAnswer = new \Anax\QuestionToAnswer\Question2Answer();
        $this->questionToAnswer->setDI($this->di);
    }

    public function addAction($questionId, $answerId, $pointer)
    {
        $isAdded = $this->addAnswerToQuestion($questionId, $answerId);

        if ($isAdded) {
            $this->increaseAnswerConnectionCounter($questionId);
        } else {
            $pointer->AddOutput("<p><i>Varning! Kunde inte knyta användare till svaret!</i></p>");
        }
    }

    private function addAnswerToQuestion($questionId, $answerId)
    {
        $isSaved = $this->questionToAnswer->create(array(
            'idQuestion'    => intval($questionId),
            'idAnswer'  => $answerId,
        ));

        return $isSaved;
    }

    private function increaseAnswerConnectionCounter($questionId)
    {
        $this->dispatcher->forward([
            'controller' => 'questions',
            'action'     => 'increaseCounter',
            'params'     => [$questionId]
        ]);
    }
}
