<?php

namespace Anax\HTMLForm\Users;

/**
 * Logout user form
 *
 * Creates a form to log out a user and remove the user from the session.
 */
class CFormLogoutUser extends \Mos\HTMLForm\CForm
{
    use \Anax\DI\TInjectionAware,
        \Anax\MVC\TRedirectHelpers;

    private $logoutMessage = null;

    /**
     * Constructor
     *
     * Creates a form to log out a user.
     */
    public function __construct()
    {
        parent::__construct([], [
            'submit' => [
                'type'      => 'submit',
                'callback'  => [$this, 'callbackSubmit'],
                'value'     => 'Logga ut',
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
     * Logs out a user and sets a log out message about the result.
     *
     * @return boolean true if the user is logged out, false otherwise.
     */
    public function callbackSubmit()
    {
        $this->user = new \Anax\Users\User();
        $this->user->setDI($this->di);

        if (isset($_SESSION["user"])) {
            unset($_SESSION["user"]);
            return $this->isLogoutSuccessful();
        } else {
            $this->logoutMessage = "Du är inte inloggad!";
            return false;
        }
    }

    /**
     * Helper method to check if the log out is successful or not.
     *
     * Checks if the user is saved in session or not. Sets a message about the
     * the result.
     *
     * @return boolean true if user is logged out, false otherwise.
     */
    private function isLogoutSuccessful()
    {
        if (isset($_SESSION["user"])) {
            $this->logoutMessage = "Fel uppstod, du kunde EJ loggas ut!";
            return false;
        } else {
            $this->logoutMessage = "Du har loggat ut. Stäng ner webbläsaren som extra säkerhet!";
            return true;
        }
    }

    /**
     * Callback at success.
     *
     * Prints out the log out message and redirects back to the form.
     *
     * @return void.
     */
    public function callbackSuccess()
    {
        $this->AddOutput("<p><i>" . $this->logoutMessage . "</i></p>");
        $this->redirectTo();
    }



    /**
     * Callback at failure.
     *
     * Prints out a log out message and redirects back to the form.
     *
     * @return void.
     */
    public function callbackFail()
    {
        $this->AddOutput("<p><i>" . $this->logoutMessage . "</i></p>");
        $this->redirectTo();
    }
}
