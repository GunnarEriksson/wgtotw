<?php

namespace Anax\HTMLForm\Comments;

/**
 * Anax base class for wrapping sessions.
 *
 */
class CFormUpdateComment extends \Mos\HTMLForm\CForm
{
    use \Anax\DI\TInjectionAware,
        \Anax\MVC\TRedirectHelpers;

    private $id;
    private $score;
    private $created;
    private $questionId;

    /**
     * Constructor
     *
     * @param string $pageKey the page name for the comment.
     */
    public function __construct($commentData, $questionId)
    {
        $this->id = $commentData['id'];
        $this->score = $commentData['score'];
        $this->created = $commentData['created'];
        $this->questionId = $questionId;

        parent::__construct([], [
            'content' => [
                'type'        => 'textarea',
                'label'       => 'Kommentar',
                'required'    => true,
                'validation'  => ['not_empty'],
                'value'       => $commentData['content'],
                'description' => 'Du kan använda <a target="_blank" href="http://daringfireball.net/projects/markdown/basics">markdown</a> för att formatera texten'
            ],
            'submit' => [
                'type'      => 'submit',
                'callback'  => [$this, 'callbackSubmit'],
                'value'     => 'Kommentera',
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
        $this->updateComment = new \Anax\Comments\Comment();
        $this->updateComment->setDI($this->di);

        $isSaved = $this->updateComment->save(array(
            'id'            => $this->id,
            'content'       => $this->Value('content'),
            'score'         => $this->score,
            'created'       => $this->created
        ));

        return $isSaved;
    }

    /**
     * Callback What to do if the form was submitted?
     *
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
     */
    public function callbackFail()
    {
        $this->AddOutput("<p><i>Kommentar kunde inte uppdateras i databasen!</i></p>");
        $this->redirectTo();
    }
}
