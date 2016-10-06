<?php

namespace Anax\Errors;

/**
 * Errors controller
 *
 * Creates views to show errors.
 */
class ErrorsController implements \Anax\DI\IInjectionAware
{
    use \Anax\DI\TInjectable;

    /**
     * Initialize the controller.
     *
     * Initializes the session.
     *
     * @return void
     */
    public function initialize()
    {
        $this->session();
    }

    /**
     * Shows error information.
     *
     * Shows error information and creats a return button, if defined.
     * Resets the key lastInsertedId in session. The key is used to prevent
     * access to some controller action methods directly via the browswer
     * address field.
     *
     * @param  [string] $errorInfo  Error information to be shown.
     *
     * @return void
     */
    public function viewAction($errorInfo)
    {
        $this->theme->setTitle(isset($errorInfo['title']) ? $errorInfo['title'] : null);
        $this->views->add('error/errorInfo', [
            'title'         => isset($errorInfo['title']) ? $errorInfo['title'] : null,
            'subtitle'      => isset($errorInfo['subtitle']) ? $errorInfo['subtitle'] : null,
            'message'       => isset($errorInfo['message']) ? $errorInfo['message'] : null,
            'url'           => isset($errorInfo['url']) ? $errorInfo['url'] : null,
            'buttonName'    => isset($errorInfo['buttonName']) ? $errorInfo['buttonName'] : null
        ], 'main');

        if ($this->session->has('lastInsertedId')) {
            unset($_SESSION["lastInsertedId"]);
        }
    }

    /**
     * Shows the error page not found.
     *
     * Shows the specific error page not found (error 404).
     * Resets the key lastInsertedId in session. The key is used to prevent
     * access to some controller action methods directly via the browswer
     * address field.
     *
     * @return void
     */
    public function pageNotFoundAction()
    {
        $this->theme->setTitle("Sidan saknas");
        $this->views->add('error/404', [
            'title' => 'Sidan saknas',
        ], 'main-wide');

        if ($this->session->has('lastInsertedId')) {
            unset($_SESSION["lastInsertedId"]);
        }
    }
}
