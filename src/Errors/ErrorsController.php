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
    }

    public function flashAction($errorInfo)
    {
        $this->views->add('error/warningInfo', [
            'title'         => isset($errorInfo['title']) ? $errorInfo['title'] : null,
            'subtitle'      => isset($errorInfo['subtitle']) ? $errorInfo['subtitle'] : null,
            'message'       => isset($errorInfo['message']) ? $errorInfo['message'] : null,
        ], 'flash');
    }
}
