<?php

namespace Anax\HTMLForm\Users;

/**
 * Update user form
 *
 * Creates a user form to update and save a user in DB.
 */
class CFormUpdateUser extends \Mos\HTMLForm\CForm
{
    use \Anax\DI\TInjectionAware,
        \Anax\MVC\TRedirectHelpers;

    private $id;
    private $password;
    private $created;
    private $activityScore;
    private $numVotes;


    /**
     * Constructor
     *
     * Creates a form to update a user.
     *
     * @param mixed[] the user data to be updated.
     */
    public function __construct($userData)
    {
        $this->id = $userData['id'];
        $this->gravatar = $userData['gravatar'];
        $this->password = $userData['password'];
        $this->created = $userData['created'];
        $this->activityScore = $userData['activityScore'];
        $this->numVotes = $userData['numVotes'];

        parent::__construct([], [
            'acronym' => [
                'type'        => 'text',
                'label'       => 'Akronym (kan ej ändras)',
                'required'    => true,
                'validation'  => ['not_empty'],
                'value'       => $userData['acronym'],
                'readonly'    => true,
            ],
            'firstName' => [
                'type'        => 'text',
                'label'       => 'Förnamn',
                'required'    => true,
                'validation'  => ['not_empty'],
                'value'       => $userData['firstName'],
            ],
            'lastName' => [
                'type'        => 'text',
                'label'       => 'Efternamn',
                'required'    => true,
                'validation'  => ['not_empty'],
                'value'       => $userData['lastName'],
            ],
            'town' => [
                'type'        => 'text',
                'label'       => 'Ort',
                'required'    => true,
                'validation'  => ['not_empty'],
                'value'       => $userData['town'],
            ],
            'email' => [
                'type'        => 'text',
                'label'       => 'E-post',
                'required'    => true,
                'validation'  => ['not_empty', 'email_adress'],
                'value'       => $userData['email'],
            ],
            'password' => [
                'type'        => 'password',
                'label'       => 'Lösenord (fyll endast i om du vill ändra)',
            ],
            'submit' => [
                'type'      => 'submit',
                'callback'  => [$this, 'callbackSubmit'],
                'value'     => 'Uppdatera',
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
        $password = empty($this->Value('password')) ? $this->password : password_hash($this->Value('password'), PASSWORD_DEFAULT);

        $this->updateUser = new \Anax\Users\User();
        $this->updateUser->setDI($this->di);
        $isSaved = $this->updateUser->save(array(
            'id'            => $this->id,
            'acronym'       => $this->Value('acronym'),
            'firstName'     => $this->Value('firstName'),
            'lastName'      => $this->Value('lastName'),
            'town'          => $this->Value('town'),
            'email'         => $this->Value('email'),
            'gravatar'      => $this->gravatar,
            'password'      => $password,
            'activityScore' => $this->activityScore,
            'numVotes'      => $this->numVotes,
            'created'       => $this->created,
        ));

        return $isSaved;
    }



    /**
     * Callback at success.
     *
     * Redirects to the user information.
     *
     */
    public function callbackSuccess()
    {
        $this->redirectTo('users/id/' . $this->id);
    }

    /**
     * Callback at failure.
     *
     * Prints out an error message that the user data could not be updated in
     * DB.
     *
     */
    public function callbackFail()
    {
        $this->AddOutput("<p><i>Användaren kunde inte uppdateras i databasen!</i></p>");
        $this->redirectTo();
    }
}
