<?php

namespace Anax\HTMLForm\Answers;

/**
 * Add answer form
 *
 * Creates an answer form for the user to add an answer to a question in DB.
 * Dispatches all other related tasks to other controllers.
 */
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
     *
     * Creates a form to add an answer to a question.
     *
     * @param int $questionId   the id of the related question.
     * @param int $userId       the id of the user who wants to add an answer.
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
     * Callback when the form was successfully submitted.
     *
     * Dispatches related tasks to other controllers such as mapping answer to
     * question, mapping answer to user, add activity score to the user and
     * increase the answer counter for the user.
     *
     * Resets the last inserted id in the session, if set. The parameter is
     * used to prevent direct access to other controllers, for example increase
     * activity score via the browser address bar.
     *
     * Prints out a warning if the last inserted id could not be saved.
     *
     * @return void.
     */
    public function callbackSuccess()
    {
        if (isset($this->lastInsertedId)) {
            $this->di->session->set('lastInsertedId', $this->lastInsertedId);

            $this->addAnswerToQuestion();
            $this->addAnswerToUser();
            $this->addActivityScoreToUser();

            if ($this->di->session->has('lastInsertedId')) {
                unset($_SESSION["lastInsertedId"]);
            }

            $this->redirectTo('questions/id/' . $this->questionId);
        } else {
            $this->AddOutput("<p><i>Varning! Fel inträffade när svaret sparandes i databasen.</i></p>");
            $this->redirectTo();
        }
    }

    /**
     * Helper method to map the answer to the question.
     *
     * Redirects to the QuestionAnswer controller to map the id of the answer
     * to the id of the question.
     *
     * @return void
     */
    private function addAnswerToQuestion()
    {
        $this->di->dispatcher->forward([
            'controller' => 'question-answer',
            'action'     => 'add',
            'params'     => [$this->questionId, $this->lastInsertedId]
        ]);
    }

    /**
     * Helper method to map the user to the answer.
     *
     * Redirects to the UserAnswer controller to map the id of the user to the
     * id of the answer.
     *
     * @return void.
     */
    private function addAnswerToUser()
    {
        $this->di->dispatcher->forward([
            'controller' => 'user-answer',
            'action'     => 'add',
            'params'     => [$this->userId, $this->lastInsertedId]
        ]);
    }

    /**
     * Helper method to add the activity score to answer to a question.
     *
     * Redirects to the Users controller to add an activity score to the user.
     * The activity score to answer a question.
     *
     * @return void.
     */
    private function addActivityScoreToUser()
    {
        $this->di->dispatcher->forward([
            'controller' => 'users',
            'action'     => 'add-score',
            'params'     => [CFormAddAnswer::ACTIVITY_SCORE_ANSWER, $this->lastInsertedId]
        ]);
    }

    /**
     * Callback What to do when form could not be processed?
     *
     * Prints out a message that answer could not be saved in DB.
     *
     * @return void.
     */
    public function callbackFail()
    {
        $this->AddOutput("<p><i>Svaret kunde inte sparas i databasen!</i></p>");
        $this->redirectTo();
    }
}
