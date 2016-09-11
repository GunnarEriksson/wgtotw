<?php

namespace Anax\HTMLForm\Users;

/**
 * Anax base class for wrapping sessions.
 *
 */
class CFormUpdateUser extends \Mos\HTMLForm\CForm
{
    use \Anax\DI\TInjectionAware,
        \Anax\MVC\TRedirectHelpers;

    private $id;
    private $password;
    private $created;
    private $activeDate;

    /**
     * Constructor
     *
     * @param [] the user data for the user.
     */
    public function __construct($userData)
    {
        $this->id = $userData['id'];
        $this->gravatar = $userData['gravatar'];
        $this->password = $userData['password'];
        $this->created = $userData['created'];

        parent::__construct([], [
            'acronym' => [
                'type'        => 'text',
                'label'       => 'Akronym (kan ej ändras)',
                'required'    => true,
                'validation'  => ['not_empty'],
                'value'       => $userData['acronym'],
                'readonly'    => true,
            ],
            'name' => [
                'type'        => 'text',
                'label'       => 'Namn',
                'required'    => true,
                'validation'  => ['not_empty'],
                'value'       => $userData['name'],
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
        $password = empty($this->Value('password')) ? $this->password : $this->Value('password');

        $this->updateUser = new \Anax\Users\User();
        $this->updateUser->setDI($this->di);
        $isSaved = $this->updateUser->save(array(
            'id'        => $this->id,
            'acronym'   => $this->Value('acronym'),
            'name'      => $this->Value('name'),
            'town'      => $this->Value('town'),
            'email'     => $this->Value('email'),
            'gravatar'  => $this->gravatar,
            'password'  => $password,
            'created'   => $this->created,
        ));

        return $isSaved;
    }



    /**
     * Callback What to do if the form was submitted?
     *
     */
    public function callbackSuccess()
    {
        $this->redirectTo('users/id/' . $this->id);
    }



    /**
     * Callback What to do when form could not be processed?
     *
     */
    public function callbackFail()
    {
        $this->AddOutput("<p><i>Användaren kunde inte uppdateras i databasen!</i></p>");
        $this->redirectTo();
    }
}
