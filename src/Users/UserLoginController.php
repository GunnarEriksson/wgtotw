<?php
namespace Anax\Users;

/**
 * User Login Controller
 *
 * A controller for users login and logout related events.
 */
class UserLoginController implements \Anax\DI\IInjectionAware
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

        $this->users = new \Anax\Users\User();
        $this->users->setDI($this->di);
    }

    /**
     * Creates a log in action for the user.
     *
     * Creates a log in form and the and accompanying instructions how to create
     * a new account if the user is not a member of the community.
     *
     * @return void.
     */
    public function loginAction()
    {
        $form = new \Anax\HTMLForm\Users\CFormLoginUser();
        $form->setDI($this->di);
        $status = $form->check();

        $this->theme->setTitle("Logga in");
        $this->views->add('users/userLoginLogoutForm', [
            'title' => "Logga in",
            'content' => $form->getHTML(),
        ], 'main');

        $info = $this->fileContent->get('users/noAccountInfo.md');
        $info = $this->textFilter->doFilter($info, 'shortcode, markdown');

        $this->views->add('users/userCreateAccountInfo', [
            'title' => "Skapa konto",
            'content' => $info,
        ], 'sidebar');
    }

    /**
     * Creates a log out action.
     *
     * Creates a log out form for the user to log out.
     *
     * @return void.
     */
    public function logoutAction()
    {
        $form = new \Anax\HTMLForm\Users\CFormLogoutUser();
        $form->setDI($this->di);
        $status = $form->check();

        $this->theme->setTitle("Logga ut");
        $this->views->add('users/userLoginLogoutForm', [
            'title' => "Logga ut",
            'content' => $form->getHTML(),
        ], 'main');
    }
}
