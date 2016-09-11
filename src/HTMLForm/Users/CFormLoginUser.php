<?php

namespace Anax\HTMLForm\Users;

/**
 * Anax base class for wrapping sessions.
 *
 */
class CFormLoginUser extends \Mos\HTMLForm\CForm
{
    use \Anax\DI\TInjectionAware,
        \Anax\MVC\TRedirectHelpers;

    private $loginMessage = null;

    /**
     * Constructor
     *
     */
    public function __construct()
    {
        parent::__construct([], [
            'acronym' => [
                'type'        => 'text',
                'label'       => 'Akronym',
                'required'    => true,
                'validation'  => ['not_empty'],
            ],
            'password' => [
                'type'        => 'password',
                'label'       => 'Lösenord',
                'required'    => true,
                'validation'  => ['not_empty'],
            ],
            'submit' => [
                'type'      => 'submit',
                'callback'  => [$this, 'callbackSubmit'],
                'value'     => 'Logga in',
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

        return $this->isUserVerified($this->Value('acronym'), $this->Value('password'));
    }

    /**
     * Helper method to verify a user in DB from a acronym and password.
     *
     * Gets the user in the DB via an acronym. If the acronym is valid, the
     * password is verified. Creats a login message from the result of the login.
     *
     * @param  string  $acronym  the acronym to verify.
     * @param  string  $password the password to verify.
     *
     * @return boolean true if the acronym and password is verified, false otherwise.
     */
    private function isUserVerified($acronym, $password)
    {
        $user = $this->getUserFromDb($acronym);
        if (isset($user[0])) {
            return $this->isPasswordValid($user[0], $password);
        } else {
            $this->loginMessage = "Kunde ej hitta användare med akronym: " . $acronym . "!";
            return false;
        }
    }

    /**
     * Helper method to get a user in DB via an acronym.
     *
     * Uses the acronym to get the users id, acronym and password from DB.
     *
     * @param  string $acronym the acronym to use to get user information.
     *
     * @return [object] the array with a user object. If the user is not found, an
     *                  empty array is returned.
     */
    private function getUserFromDb($acronym)
    {
        $user = $this->user->query('id, acronym, password')
            ->where('acronym = ?')
            ->execute([$acronym]);

        return $user;
    }

    /**
     * Helper method to verify the users password.
     *
     * Verifies the users password and creates a login message.
     * Saves the user acronym in session if the password was verified.
     *
     * @param  object  $user     the user object.
     * @param  string  $password the password to verify.
     *
     * @return boolean true if password is verified, false otherwise.
     */
    private function isPasswordValid($user, $password)
    {
        if (password_verify($password, $user->password)) {
            $userData = ['id' => $user->id, 'acronym' => $user->acronym];
            $this->di->session->set('user', $userData);
            return true;
        } else {
            $this->loginMessage = "Felaktigt lösenord!";
            return false;
        }
    }

    /**
     * Callback What to do if the form was submitted?
     *
     */
    public function callbackSuccess()
    {
        $this->redirectTo('profile');
    }



    /**
     * Callback What to do when form could not be processed?
     *
     */
    public function callbackFail()
    {
        $this->AddOutput("<p><i>" . $this->loginMessage . "</i></p>");
        $this->redirectTo();
    }
}
