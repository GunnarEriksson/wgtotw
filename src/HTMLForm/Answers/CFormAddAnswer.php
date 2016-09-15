<?php

namespace Anax\HTMLForm\Answers;

class CFormAddAnswer extends \Mos\HTMLForm\CForm
{
    use \Anax\DI\TInjectionAware,
        \Anax\MVC\TRedirectHelpers;

    private $user;
    private $questionId;

    /**
     * Constructor
     */
    public function __construct($questionId, $user)
    {
        $this->user = $user;
        $this->questionId = $questionId;

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

        $isSaved = $this->newAnswer->save(array(
            'content'       => $this->Value('content'),
            'score'         => 0,
            'created'       => gmdate('Y-m-d H:i:s')
        ));

        if ($isSaved) {
            $lastInsertedId = $this->newAnswer->id;
            $this->addAnwserToQuestion($lastInsertedId);
            $this->addAnwserToUser($lastInsertedId);
        }

        return $isSaved;
    }

    private function addAnwserToQuestion($answerId)
    {
        $this->di->dispatcher->forward([
            'controller' => 'question-answer',
            'action'     => 'add',
            'params'     => [$this->questionId, $answerId, $this]
        ]);
    }

    private function addAnwserToUser($answerId)
    {
        $this->di->dispatcher->forward([
            'controller' => 'user-answer',
            'action'     => 'add',
            'params'     => [$this->user['id'], $answerId, $this]
        ]);
    }

    /**
     * Callback What to do if the form was submitted?
     *
     */
    public function callbackSuccess()
    {
        $this->AddOutput("<p><i>Svaret har sparats i databasen!</i></p>");
        $this->redirectTo();
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
