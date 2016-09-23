<?php

namespace Anax\Errors;

class ErrorsController implements \Anax\DI\IInjectionAware
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
    }

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
