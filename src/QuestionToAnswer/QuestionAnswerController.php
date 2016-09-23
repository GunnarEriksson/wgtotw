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

    public function addAction($questionId, $answerId)
    {
        if ($this->isAllowedToAddAnswerToQuestion($answerId)) {
            if ($this->addAnswerToQuestion($questionId, $answerId)) {
                $this->increaseAnswerConnectionCounter($questionId);
            } else {
                $warningMessage = "Kunde inte knyta svar till frågan i DB!";
                $this->flash->warningMessage($warningMessage);
            }
        } else {
            $this->pageNotFound();
        }
    }

    private function isAllowedToAddAnswerToQuestion($answerId)
    {
        $isAllowed = false;

        if ($this->LoggedIn->isLoggedin()) {
            $isAllowed = $this->isIdLastInserted($answerId);
        }

        return $isAllowed;
    }

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
