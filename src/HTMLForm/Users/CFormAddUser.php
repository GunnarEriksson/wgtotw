<?php

namespace Anax\HTMLForm\Users;

/**
 * Add user form
 *
 * Creates a user form to add and save a user in DB.
 */
class CFormAddUser extends \Mos\HTMLForm\CForm
{
    use \Anax\DI\TInjectionAware,
        \Anax\MVC\TRedirectHelpers;

    const SQLSTATE = '23000';
    const ERROR_DUPLICATE_KEY = '1062';

    private $acronym = null;
    private $exception = null;
    private $errorMessage = null;



    /**
     * Constructor
     *
     * Creates a form to add a user.
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
            'firstName' => [
                'type'        => 'text',
                'label'       => 'Förnamn',
                'required'    => true,
                'validation'  => ['not_empty'],
            ],
            'lastName' => [
                'type'        => 'text',
                'label'       => 'Efternamn',
                'required'    => true,
                'validation'  => ['not_empty'],
            ],
            'town' => [
                'type'        => 'text',
                'label'       => 'Ort',
                'required'    => true,
                'validation'  => ['not_empty'],
            ],
            'email' => [
                'type'        => 'text',
                'label'       => 'E-post',
                'required'    => true,
                'validation'  => ['not_empty', 'email_adress'],
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
                'value'     => 'Spara',
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
     * Saves a user in DB.
     *
     * @return boolean true if data was added in db, false otherwise.
     */
    public function callbackSubmit()
    {
        $this->exception = null;

        $this->acronym = $this->Value('acronym');
        $now = gmdate('Y-m-d H:i:s');

        $this->newUser = new \Anax\Users\User();
        $this->newUser->setDI($this->di);

        if ($this->isAcronymAvailable($this->newUser, $this->acronym)) {
            try {
                $isSaved = $this->newUser->save(array(
                    'acronym'       => $this->Value('acronym'),
                    'firstName'     => $this->Value('firstName'),
                    'lastName'      => $this->Value('lastName'),
                    'town'          => $this->Value('town'),
                    'email'         => $this->Value('email'),
                    'gravatar'      => 'http://www.gravatar.com/avatar/' . md5(strtolower(trim($this->Value('email')))) . '.jpg',
                    'password'      => password_hash($this->Value('password'), PASSWORD_DEFAULT),
                    'activityScore' => 0,
                    'numVotes'      => 0,
                    'created'       => $now
                ));
            } catch (\Exception $e) {
                $this->exception = $e;
                $isSaved = false;
            }
        } else {
            $this->errorMessage = "<p><i>Akronym är redan upptaget, välj ny akronym!</i></p>";
            $isSaved = false;
        }


        return $isSaved;
    }


    /**
     * Helper method to check if an acronym is available or not.
     *
     * Checks in DB if an acronym is available or not.
     *
     * @param  object  $userDb  the user DB object.
     * @param  string  $acronym the acronym to check if available or not.
     *
     * @return boolean  true if acronym is available, false otherwise.
     */
    private function isAcronymAvailable($userDb, $acronym)
    {
        $isAcronymAvailable = true;

        $user = $this->getUserFromDb($userDb, $acronym);
        if (isset($user[0]) && (strcmp($user[0]->acronym, $acronym) === 0)) {
            $isAcronymAvailable = false;
        }

        return $isAcronymAvailable;

    }

    /**
     * Helper method to get a user in DB via an acronym.
     *
     * Uses the acronym to get the users acronym from DB.
     *
     * @param  object $userDb  the user DB object.
     * @param  string $acronym the acronym to search in DB.
     *
     * @return object[] the array with a user object. If the user is not found, an
     *                  empty array is returned.
     */
    private function getUserFromDb($userDb, $acronym)
    {
        $user = $userDb->query('acronym')
            ->where('acronym = ?')
            ->execute([$acronym]);

        return $user;
    }



    /**
     * Callback at success.
     *
     * Prints out a welcome message for the user who creates a new account.
     * Redirects back to the form.
     */
    public function callbackSuccess()
    {
        $this->AddOutput("<p><i>Välkommen till Allt Om Landskapsfotografering! Ditt användare id är: " . $this->acronym . " </i></p>");
        $this->redirectTo();
    }



    /**
     * Callback at failure.
     *
     * Prints out an error message and redirects back to the form.
     *
     */
    public function callbackFail()
    {
        if (isset($this->exception)) {
            if (strpos($this->exception, CFormAddUser::SQLSTATE) !== false && strpos($this->exception, CFormAddUser::ERROR_DUPLICATE_KEY) !== false) {
                $errorMessage = "<p><i>Fel har uppstått i databasen. Akronym är redan upptaget, försök att välja ny akronym eller kontakta administratör!</i></p>";
            } else {
                $errorMessage = "<p><i>Fel har uppstått i databasen, försök igen eller kontakta administratör!</i></p>";
            }
        } else {
            if (isset($this->errorMessage)) {
                $errorMessage = $this->errorMessage;
            } else {
                $errorMessage = "<p><i>Fel har uppstått i databasen, kunde ej spara information i databasen. Försök igen eller kontakta administratör!</i></p>";
            }
        }

        $this->AddOutput($errorMessage);
        $this->redirectTo();
    }
}
