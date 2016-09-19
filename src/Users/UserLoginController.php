<?php
namespace Anax\Users;

/**
 * A controller for users login and logout related events.
 */
class UserLoginController implements \Anax\DI\IInjectionAware
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

        $this->users = new \Anax\Users\User();
        $this->users->setDI($this->di);
    }

    public function loginAction()
    {
        $form = new \Anax\HTMLForm\Users\CFormLoginUser();
        $form->setDI($this->di);
        $status = $form->check();

        $this->theme->setTitle("Logga in");
        $this->di->views->add('users/userLoginLogoutForm', [
            'title' => "Logga in",
            'content' => $form->getHTML(),
        ], 'main');

        $info = $this->di->fileContent->get('users/noAccountInfo.md');
        $info = $this->di->textFilter->doFilter($info, 'shortcode, markdown');

        $this->di->views->add('users/userCreateAccountInfo', [
            'title' => "Skapa konto",
            'content' => $info,
        ], 'sidebar');
    }

    public function logoutAction()
    {
        $form = new \Anax\HTMLForm\Users\CFormLogoutUser();
        $form->setDI($this->di);
        $status = $form->check();

        $this->theme->setTitle("Logga ut");
        $this->di->views->add('users/userLoginLogoutForm', [
            'title' => "Logga ut",
            'content' => $form->getHTML(),
        ], 'main');
    }
}
