<?php

namespace Anax\HTMLForm\Answers;

class CFormAddAnswer extends \Mos\HTMLForm\CForm
{
    use \Anax\DI\TInjectionAware,
        \Anax\MVC\TRedirectHelpers;

    const ACTIVITY_SCORE_ANSWER = 3;

    private $userId;
    private $questionId;
    private $lastInsertedId;

    /**
     * Constructor
     */
    public function __construct($questionId, $userId)
    {
        $this->userId = $userId;
        $this->questionId = $questionId;
        $this->lastInsertedId = null;

        parent::__construct([], [
            'content' => [
                'type'        => 'textarea',
                'label'       => 'Kommentar',
                'required'    => true,
                'validation'  => ['not_empty'],
                'description' => 'Du kan använda <a target="_blank" href="http://daringfireball.net/projects/markdown/basics">markdown</a> för att formatera texten'
            ],
            'submit' => [
                'type'      => 'submit',
                'callback'  => [$this, 'callbackSubmit'],
                'value'     => 'Svara',
            ],
        ]);
    }

    /**
     * Customise the check() method.
     *
     * @param callable $callIfSuccess handler to call if function returns true.
     * @param callable $callIfFail    handler to call if function returns true.
     */
    public function check($callIfSuccess = null, $callIfFail = null)
    {
        return parent::check([$this, 'callbackSuccess'], [$this, 'callbackFail']);
    }

    /**
     * Callback for submit-button.
     *
     * @return boolean true if data was added in db, false otherwise.
     */
    public function callbackSubmit()
    {
        $this->newAnswer = new \Anax\Answers\Answer();
        $this->newAnswer->setDI($this->di);

        date_default_timezone_set('Europe/Stockholm');
        $now = date('Y-m-d H:i:s');

        $isSaved = $this->newAnswer->save(array(
            'content'       => $this->Value('content'),
            'score'         => 0,
            'accepted'      => 0,
            'created'       => $now
        ));

        if ($isSaved) {
            $this->lastInsertedId = $this->newAnswer->id;
        }

        return $isSaved;
    }

    /**
     * Callback What to do if the form was submitted?
     *
     */
    public function callbackSuccess()
    {
        if (isset($this->lastInsertedId)) {
            $this->di->session->set('lastInsertedId', $this->lastInsertedId);

            $this->addAnswerToQuestion();
            $this->addAnswerToUser();
            $this->addActivityScoreToUser();
            $this->increaseAnswersCounter();

            if ($this->di->session->has('lastInsertedId')) {
                unset($_SESSION["lastInsertedId"]);
            }

            $this->redirectTo('questions/id/' . $this->questionId);
        } else {
            $this->AddOutput("<p><i>Varning! Fel inträffade när svaret sparandes i databasen.</i></p>");
            $this->redirectTo();
        }
    }

    private function addAnswerToQuestion()
    {
        $this->di->dispatcher->forward([
            'controller' => 'question-answer',
            'action'     => 'add',
            'params'     => [$this->questionId, $this->lastInsertedId]
        ]);
    }

    private function addAnswerToUser()
    {
        $this->di->dispatcher->forward([
            'controller' => 'user-answer',
            'action'     => 'add',
            'params'     => [$this->userId, $this->lastInsertedId]
        ]);
    }

    private function addActivityScoreToUser()
    {
        $this->di->dispatcher->forward([
            'controller' => 'users',
            'action'     => 'add-score',
            'params'     => [CFormAddAnswer::ACTIVITY_SCORE_ANSWER, $this->lastInsertedId]
        ]);
    }

    private function increaseAnswersCounter()
    {
        $this->di->dispatcher->forward([
            'controller' => 'users',
            'action'     => 'increase-answers-counter',
            'params'     => [$this->lastInsertedId]
        ]);
    }


    /**
     * Callback What to do when form could not be processed?
     *
     */
    public function callbackFail()
    {
        $this->AddOutput("<p><i>Svaret kunde inte sparas i databasen!</i></p>");
        $this->redirectTo();
    }
}
