<?php

namespace Anax\HTMLForm\Questions;

class CFormUpdateQuestion extends \Mos\HTMLForm\CForm
{
    use \Anax\DI\TInjectionAware,
        \Anax\MVC\TRedirectHelpers;

    private $id;
    private $score;
    private $answers;
    private $created;
    private $checkedTags;

    /**
     * Constructor
     */
    public function __construct($questionData, $tagNames, $checkedTags)
    {
        $this->id = $questionData['id'];
        $this->score = $questionData['score'];
        $this->answers = $questionData['answers'];
        $this->created = $questionData['created'];
        $this->checkedTags = $checkedTags;

        parent::__construct([], [
            'title' => [
                'type'        => 'text',
                'label'       => 'Rubrik',
                'required'    => true,
                'validation'  => ['not_empty'],
                'value'       => $questionData['title']
            ],
            'content' => [
                'type'        => 'textarea',
                'label'       => 'Kommentar',
                'required'    => true,
                'validation'  => ['not_empty'],
                'value'       => $questionData['content'],
                'description' => 'Du kan använda <a target="_blank" href="http://daringfireball.net/projects/markdown/basics">markdown</a> för att formatera texten'
            ],
            "tags" => [
                'type'        => 'checkbox-multiple',
                'values'      => $tagNames,
                "checked"     => $checkedTags,
            ],
            'submit' => [
                'type'      => 'submit',
                'callback'  => [$this, 'callbackSubmit'],
                'value'     => 'Uppdatera',
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
        $this->updateQuestion = new \Anax\Questions\Question();
        $this->updateQuestion->setDI($this->di);

        $isSaved = $this->updateQuestion->save(array(
            'id'        => $this->id,
            'title'     => $this->Value('title'),
            'content'   => $this->Value('content'),
            'score'     => $this->score,
            'answers'   => $this->answers,
            'created'   => $this->created
        ));

        if ($isSaved) {
            if ($this->Value('tags') != $this->checkedTags) {
                $this->updateTagsToQuestion();
            }
        }

        return $isSaved;
    }

    private function updateTagsToQuestion()
    {
        $this->di->dispatcher->forward([
            'controller' => 'question-tag',
            'action'     => 'update',
            'params'     => [$this->id, $this->Value('tags'), $this->checkedTags, $this]
        ]);
    }

    /**
     * Callback What to do if the form was submitted?
     *
     */
    public function callbackSuccess()
    {
        $this->AddOutput("<p><i>Frågan har uppdaterats i databasen!</i></p>");
        $this->redirectTo();
    }


    /**
     * Callback What to do when form could not be processed?
     *
     */
    public function callbackFail()
    {
        $this->AddOutput("<p><i>Frågan kunde inte uppdateras i databasen!</i></p>");
        $this->redirectTo();
    }
}
