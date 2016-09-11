<?php

namespace Anax\HTMLForm\Users;

class CFormLogoutUser extends \Mos\HTMLForm\CForm
{
    use \Anax\DI\TInjectionAware,
        \Anax\MVC\TRedirectHelpers;

    private $logoutMessage = null;

    /**
     * Constructor
     *
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
     * @return boolean true if data was added in db, false otherwise.
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
     * Callback What to do if the form was submitted?
     *
     */
    public function callbackSuccess()
    {
        $this->AddOutput("<p><i>" . $this->logoutMessage . "</i></p>");
        $this->redirectTo();
    }



    /**
     * Callback What to do when form could not be processed?
     *
     */
    public function callbackFail()
    {
        $this->AddOutput("<p><i>" . $this->logoutMessage . "</i></p>");
        $this->redirectTo();
    }
}
