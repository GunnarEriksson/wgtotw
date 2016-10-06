<?php

namespace Anax\HTMLForm\Answers;

/**
 * Update answer form
 *
 * Creates an answer form for the user to update the users answer to a question
 * in DB.
 */
class CFormUpdateAnswer extends \Mos\HTMLForm\CForm
{
    use \Anax\DI\TInjectionAware,
        \Anax\MVC\TRedirectHelpers;

    private $id;
    private $score;
    private $accepted;
    private $created;
    private $questionId;

    /**
     * Constructor
     *
     * Creates a form to update the users answer to a question.
     *
     * @param [mixed] $answerData the answer data to be updated.
     * @param int $questionId the id of the related question.
     */
    public function __construct($answerData, $questionId)
    {
        $this->id = $answerData['id'];
        $this->score = $answerData['score'];
        $this->accepted = $answerData['accepted'];
        $this->created = $answerData['created'];
        $this->questionId = $questionId;

        parent::__construct([], [
            'content' => [
                'type'        => 'textarea',
                'label'       => 'Kommentar',
                'required'    => true,
                'validation'  => ['not_empty'],
                'value'       => $answerData['content'],
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
        $this->updateAnswer = new \Anax\Answers\Answer();
        $this->updateAnswer->setDI($this->di);

        $isSaved = $this->updateAnswer->save(array(
            'id'        => $this->id,
            'content'   => $this->Value('content'),
            'score'     => $this->score,
            'accepted'  => $this->accepted,
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
        if (isset($this->questionId)) {
            $this->redirectTo('questions/id/' . $this->questionId);
        } else {
            $this->AddOutput("<p><i>Varning! Kan ej göra skicka vidare till sidan med frågan. Frågans id saknas.</i></p>");
            $this->redirectTo();
        }
    }

    /**
     * Callback What to do when form could not be processed?
     *
     * Prints out that the updated answer could not be saved in DB.
     *
     * @return void.
     */
    public function callbackFail()
    {
        $this->AddOutput("<p><i>Svaret kunde inte uppdateras i databasen!</i></p>");
        $this->redirectTo();
    }
}
