<?php

namespace Anax\QuestionToAnswer;

/**
 * Question Answer controller
 *
 * Communicates with the mapping table, which maps questions with the related
 * answers in the database.
 * Handles all mapping tasks between question and the related answers.
 */
class QuestionAnswerController implements \Anax\DI\IInjectionAware
{
    use \Anax\DI\TInjectable;

    /**
     * Initialize the controller.
     *
     * Initializes the session and the question to
     * answer model.
     *
     * @return void
     */
    public function initialize()
    {
        $this->session();

        $this->questionToAnswer = new \Anax\QuestionToAnswer\Question2Answer();
        $this->questionToAnswer->setDI($this->di);
    }

    /**
     * Adds a connection between a question and an answer.
     *
     * Adds a connection between a question and an answer if the question id and
     * answer id is present, otherwise it creates a flash error message.
     *
     * @param int $questionId   the question id to be mapped to an answer id.
     * @param int $answerId     the answer id to be mapped to a question id.
     *
     * @return void
     */
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

    /**
     * Helper method to check if it is allowed to add an answer to a question.
     *
     * Checks if the user has logged in and the call is from a redirect and not
     * via the browsers addess field.
     *
     * @param  int $answerId the id of the answer to be connected to a question.
     *
     * @return boolean  true if it is allowe to connect an answer to a question,
     *                       false otherwise.
     */
    private function isAllowedToAddAnswerToQuestion($answerId)
    {
        $isAllowed = false;

        if ($this->LoggedIn->isLoggedin()) {
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
     * Helper method to add an answer to a question in DB.
     *
     * Connects an answer to a question in DB.
     *
     * @param int $questionId the question id to be connected to an answer id.
     * @param int $answerId the answer id to be connected to a question id.
     *
     * @return boolean true if saved, false otherwise.
     */
    private function addAnswerToQuestion($questionId, $answerId)
    {
        $isSaved = $this->questionToAnswer->create(array(
            'idQuestion'    => intval($questionId),
            'idAnswer'  => $answerId,
        ));

        return $isSaved;
    }

    /**
     * Helper method to increase the number of answers to a question.
     *
     * Redirects to the Questions controller to increase the number of answers
     * to a question.
     *
     * @param  int $questionId  the question id.
     *
     * @return void.
     */
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
