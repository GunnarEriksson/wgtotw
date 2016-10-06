<?php

namespace Anax\HTMLForm\Comments;

/**
 * Update Comment form
 *
 * Creates a comment form for the user to update the users comment to a question
 * or an answer in DB.
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
     * Creates a form to update the users comment to a question or answer.
     *
     * @param [mixed]   $commentData the comment data to be updated.
     * @param int $questionId the id of the related question.
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
     * Callback What to do if the form was sucessfully submitted?
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
     * Prints out that the updated comment could not be saved in DB.
     *
     * @return void.
     */
    public function callbackFail()
    {
        $this->AddOutput("<p><i>Kommentar kunde inte uppdateras i databasen!</i></p>");
        $this->redirectTo();
    }
}
