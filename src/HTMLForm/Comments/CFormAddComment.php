<?php

namespace Anax\HTMLForm\Comments;

/**
 * Anax base class for wrapping sessions.
 *
 */
class CFormAddComment extends \Mos\HTMLForm\CForm
{
    use \Anax\DI\TInjectionAware,
        \Anax\MVC\TRedirectHelpers;

    const ACTIVITY_SCORE_COMMENT = 2;

    private $id;
    private $user;
    private $controller;


    /**
     * Constructor
     *
     * @param string $pageKey the page name for the comment.
     */
    public function __construct($id, $user, $controller)
    {
        $this->id = $id;
        $this->user = $user;
        $this->controller = $controller;

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
            $lastInsertedId = $this->newComment->id;
            $this->mapComment($lastInsertedId);
            $this->addCommentToUser($lastInsertedId);
            $this->addActivityScoreToUser();
        }

        return $isSaved;
    }

    private function mapComment($commentId)
    {
        $this->di->dispatcher->forward([
            'controller' => $this->controller,
            'action'     => 'add',
            'params'     => [$this->id, $commentId, $this]
        ]);
    }

    private function addCommentToUser($commentId)
    {
        $this->di->dispatcher->forward([
            'controller' => 'user-comment',
            'action'     => 'add',
            'params'     => [$this->user['id'], $commentId, $this]
        ]);
    }

    private function addActivityScoreToUser()
    {
        $this->di->dispatcher->forward([
            'controller' => 'users',
            'action'     => 'add-score',
            'params'     => [CFormAddComment::ACTIVITY_SCORE_COMMENT]
        ]);
    }

    /**
     * Callback What to do if the form was submitted?
     *
     */
    public function callbackSuccess()
    {
        $this->AddOutput("<p><i>Kommentaren har sparats i databasen!</i></p>");
        $this->redirectTo();
    }

    /**
     * Callback What to do when form could not be processed?
     *
     */
    public function callbackFail()
    {
        $this->AddOutput("<p><i>Kommentaren kunde inte sparas i databasen!</i></p>");
        $this->redirectTo();
    }
}
