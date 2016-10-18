<?php

namespace Anax\HTMLForm\Questions;

/**
 * Update question form
 *
 * Creates a question form for the user to update the users question in DB.
 * Dispatches all other related tasks to other controllers.
 */
class CFormUpdateQuestion extends \Mos\HTMLForm\CForm
{
    use \Anax\DI\TInjectionAware,
        \Anax\MVC\TRedirectHelpers;

    private $id;
    private $score;
    private $answers;
    private $created;
    private $oldTags;
    private $newTags;

    /**
     * Constructor
     *
     * Creates a form to update the users question.
     *
     * @param mixed[] $questionData the question data to be updated.
     * @param string[] $tagNames    the name of the question related tags.
     * @param string[] $oldTags     the tag names before the update.
     */
    public function __construct($questionData, $tagNames, $oldTags)
    {
        $this->id = $questionData['id'];
        $this->score = $questionData['score'];
        $this->answers = $questionData['answers'];
        $this->created = $questionData['created'];
        $this->oldTags = $oldTags;
        $this->newTags = null;

        parent::__construct([], [
            'title' => [
                'type'        => 'text',
                'label'       => 'Rubrik',
                'required'    => true,
                'validation'  => ['not_empty'],
                'value'       => htmlentities($questionData['title'], null, 'UTF-8'),
            ],
            'content' => [
                'type'        => 'textarea',
                'label'       => 'Kommentar',
                'required'    => true,
                'validation'  => ['not_empty'],
                'value'       => htmlentities($questionData['content'], null, 'UTF-8'),
                'description' => 'Du kan använda <a target="_blank" href="http://daringfireball.net/projects/markdown/basics">markdown</a> för att formatera texten'
            ],
            "tags" => [
                'type'        => 'checkbox-multiple',
                'values'      => $tagNames,
                "checked"     => $oldTags,
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

        $this->newTags = $this->Value('tags');

        $isSaved = $this->updateQuestion->save(array(
            'id'        => $this->id,
            'title'     => $this->Value('title'),
            'content'   => $this->Value('content'),
            'score'     => $this->score,
            'answers'   => $this->answers,
            'created'   => $this->created
        ));

        return $isSaved;
    }

    /**
     * Callback what to do if the form was sucessfully submitted?
     *
     * Redirects to the related question if the question id is present.
     * Otherwise an warning message is printed out that the related question
     * id is missing.
     *
     * @return void.
     */
    public function callbackSuccess()
    {
        if ($this->newTags != $this->oldTags) {
            $this->di->session->set('lastInsertedId', $this->id);
            $this->updateTagsToQuestion();
        }

        if (isset($this->id)) {
            $this->redirectTo('questions/id/' . $this->id);
        } else {
            $this->AddOutput("<p><i>Varning! Fel inträffade. Fråge id saknas.</i></p>");
            $this->redirectTo();
        }
    }

    /**
     * Helper method to update the related tags to the question.
     *
     * Redirects to the QuestionTag controller to remove superfluous tags and
     * add new ones after the question has been updated.
     *
     * @return void.
     */
    private function updateTagsToQuestion()
    {
        $this->di->dispatcher->forward([
            'controller' => 'question-tag',
            'action'     => 'update',
            'params'     => [$this->id, $this->newTags, $this->oldTags]
        ]);
    }


    /**
     * Callback What to do when form could not be processed?
     *
     * Prints out that the updated question could not be saved in DB.
     *
     * @return void.
     */
    public function callbackFail()
    {
        $this->AddOutput("<p><i>Frågan kunde inte uppdateras i databasen!</i></p>");
        $this->redirectTo();
    }
}
