<?php


namespace Anax\Comments;

/**
 * To attach comments-flow to a page or some content.
 *
 */
class CommentsController implements \Anax\DI\IInjectionAware
{
    use \Anax\DI\TInjectable;

    /**
     * Initialize the controller.
     *
     * @return void
     */
    public function initialize()
    {
        $this->di->session();

        $this->comments = new \Anax\Comments\Comment();
        $this->comments->setDI($this->di);
    }

    public function addAction($id, $title, $controller)
    {
        if ($this->di->session->has('user')) {
            $this->addComment($id, $title, $controller);
        } else {
            $this->pageNotFound();
        }
    }

    private function addComment($id, $title, $controller)
    {
        $user = $this->di->session->get('user', []);
        $form = new \Anax\HTMLForm\Comments\CFormAddComment($id, $user, $controller);
        $form->setDI($this->di);
        $status = $form->check();

        $this->di->theme->setTitle("Kommentar: " . $title);
        $this->di->views->add('comment/commentForm', [
            'title' => "Kommentar: " . $title,
            'content' => $form->getHTML(),
        ], 'main');
    }

    /**
     * Helper method to show page 404, page not found.
     *
     * Shows page 404 with the text that the page could not be found and you
     * must login to get the page you are looking for.
     *
     * @return void
     */
    private function pageNotFound()
    {
        $this->theme->setTitle("Sidan saknas");
        $this->views->add('error/404', [
            'title' => 'Sidan saknas',
        ], 'main-wide');
    }

    public function upVoteAction($commentId)
    {
        $this->dispatcher->forward([
            'controller' => 'comment-votes',
            'action'     => 'increase',
            'params'     => [$commentId]
        ]);
    }

    public function downVoteAction($commentId)
    {
        $this->dispatcher->forward([
            'controller' => 'comment-votes',
            'action'     => 'decrease',
            'params'     => [$commentId]
        ]);
    }
}
