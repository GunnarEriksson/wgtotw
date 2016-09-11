<?php

namespace Anax\HTMLForm\Questions;

class CFormAddQuestion extends \Mos\HTMLForm\CForm
{
    use \Anax\DI\TInjectionAware,
        \Anax\MVC\TRedirectHelpers;

    private $user;

    /**
     * Constructor
     */
    public function __construct($user, $tagNames)
    {
        $this->user = $user;

        parent::__construct([], [
            'title' => [
                'type'        => 'text',
                'label'       => 'Rubrik',
                'required'    => true,
                'validation'  => ['not_empty'],
            ],
            'content' => [
                'type'        => 'textarea',
                'label'       => 'Kommentar',
                'required'    => true,
                'validation'  => ['not_empty'],
                'description' => 'Du kan använda <a target="_blank" href="http://daringfireball.net/projects/markdown/basics">markdown</a> för att formatera texten'
            ],
            "tags" => [
                'type'        => 'checkbox-multiple',
                'values'      => $tagNames,
            ],
            'submit' => [
                'type'      => 'submit',
                'callback'  => [$this, 'callbackSubmit'],
                'value'     => 'Posta',
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
        $this->newQuestion = new \Anax\Questions\Question();
        $this->newQuestion->setDI($this->di);

        $isSaved = $this->newQuestion->save(array(
            'userId'    => $this->user['id'],
            'author'    => $this->user['acronym'],
            'title'     => $this->Value('title'),
            'content'   => $this->Value('content'),
            'score'     => 0,
            'answers'   => 0,
            'created'   => gmdate('Y-m-d H:i:s')
        ));

        if ($isSaved) {
            $this->addTagsToQuestion();
        }

        return $isSaved;
    }

    private function addTagsToQuestion()
    {
        $lastInsertedId = $this->newQuestion->id;

        $this->di->dispatcher->forward([
            'controller' => 'question-tag',
            'action'     => 'add',
            'params'     => [$lastInsertedId, $this->Value('tags'), $this]
        ]);
    }

    /**
     * Callback What to do if the form was submitted?
     *
     */
    public function callbackSuccess()
    {
        $this->redirectTo();
    }



    /**
     * Callback What to do when form could not be processed?
     *
     */
    public function callbackFail()
    {
        $this->AddOutput("<p><i>Frågan kunde inte sparas i databasen!</i></p>");
        $this->redirectTo();
    }
}
