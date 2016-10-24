<?php

namespace Anax\HTMLForm\Comments;

/**
 * Add comment form
 *
 * Creates an comment form for the user to add a comment to a question or an
 * answer in DB.
 * Dispatches all other related tasks to other controllers.
 */
class CFormAddComment extends \Mos\HTMLForm\CForm
{
    use \Anax\DI\TInjectionAware,
        \Anax\MVC\TRedirectHelpers;

    const ACTIVITY_SCORE_COMMENT = 2;

    private $id;
    private $questionId;
    private $userId;
    private $controller;
    private $lastInsertedId;


    /**
     * Constructor
     *
     * Creates a form to add a comment to a question or answer.
     *
     * @param int $id               the id of the related question or answer.
     * @param int $questionId       the id of the related question. Used to
     *                              redirect back to the question.
     * @param int $userId           the user id of the answer of the comment.
     * @param string $controller    the name of the controller to redirect to.
     */
    public function __construct($id, $questionId, $userId, $controller)
    {
        $this->id = $id;
        $this->questionId = $questionId;
        $this->userId = $userId;
        $this->controller = $controller;
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
        $this->newComment = new \Anax\Comments\Comment();
        $this->newComment->setDI($this->di);

        date_default_timezone_set('Europe/Stockholm');
        $now = date('Y-m-d H:i:s');

        $isSaved = $this->newComment->save(array(
            'content'       => $this->Value('content'),
            'score'         => 0,
            'created'       => $now
        ));

        if ($isSaved) {
            $this->lastInsertedId = $this->newComment->id;
        }

        return $isSaved;
    }



    /**
     * Callback when the form was successfully submitted.
     *
     * Dispatches related tasks to other controllers such as mapping comment to
     * question or answer, mapping comment to user, add activity score to the
     * user and increase the comment counter for the user.
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

            $this->mapComment();
            $this->addCommentToUser();
            $this->addActivityScoreToUser();

            if ($this->di->session->has('lastInsertedId')) {
                unset($_SESSION["lastInsertedId"]);
            }

            $this->redirectTo('questions/id/' . $this->questionId);
        } else {
            $this->AddOutput("<p><i>Varning! Fel inträffade när kommentaren sparandes i databasen.</i></p>");
            $this->redirectTo();
        }
    }

    /**
     * Helper method to map the comment to the question or answer.
     *
     * Redirects to the QuestionComment or AnswerComment controller to map
     * the id of the question or answer to the id of the comment.
     *
     * @return void
     */
    private function mapComment()
    {
        $this->di->dispatcher->forward([
            'controller' => $this->controller,
            'action'     => 'add',
            'params'     => [$this->id, $this->lastInsertedId]
        ]);
    }

    /**
     * Helper method to map the user to the comment.
     *
     * Redirects to the UserComment controller to map the id of the user to the
     * id of the comment.
     *
     * @return void.
     */
    private function addCommentToUser()
    {
        $this->di->dispatcher->forward([
            'controller' => 'user-comment',
            'action'     => 'add',
            'params'     => [$this->userId, $this->lastInsertedId]
        ]);
    }

    /**
     * Helper method to add the activity score to comment to a question or an answer.
     *
     * Redirects to the Users controller to add an activity score to the user.
     * The activity score to comment a question or an answer.
     *
     * @return void.
     */
    private function addActivityScoreToUser()
    {
        $this->di->dispatcher->forward([
            'controller' => 'users',
            'action'     => 'add-score',
            'params'     => [CFormAddComment::ACTIVITY_SCORE_COMMENT, $this->lastInsertedId]
        ]);
    }

    /**
     * Callback What to do when form could not be processed?
     *
     * Prints out a message that comment could not be saved in DB.
     *
     * @return void.
     */
    public function callbackFail()
    {
        $this->AddOutput("<p><i>Fel! Kommentaren kunde inte sparas i databasen.</i></p>");
        $this->redirectTo();
    }
}
