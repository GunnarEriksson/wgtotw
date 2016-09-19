<?php

namespace Anax\HTMLForm\Answers;

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
     * Callback What to do if the form was submitted?
     *
     */
    public function callbackSuccess()
    {
        $this->redirectTo('questions/id/' . $this->questionId);
    }



    /**
     * Callback What to do when form could not be processed?
     *
     */
    public function callbackFail()
    {
        $this->AddOutput("<p><i>Svaret kunde inte uppdateras i databasen!</i></p>");
        $this->redirectTo();
    }
}
