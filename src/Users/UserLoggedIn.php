<?php

namespace Anax\Users;

/**
 * User Logged
 *
 * Communicates with the session and handles a user who has logged in.
 */
class UserLoggedIn
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
    }

    /**
     * Checks if a user has logged in.
     *
     * Checks if the parameter "user" has been created in the session and
     * returns the answser.
     *
     * @return boolean true if someone has logged in, false otherwise.
     */
    public function isLoggedin()
    {
        return $this->session->has('user');
    }

    /**
     * Checks if a user has permission.
     *
     * Checks if the user id belongs to the user who has checked in or the
     * user is admin. If so, the user has permission.
     *
     * @param  int  $userId the user id of the user.
     *
     * @return boolean      true if the user has permission, false otherwise.
     */
    public function isAllowed($userId)
    {
        $isAllowed = false;
        $user = $this->session->get('user', []);

        if (!empty($user)) {
            if (strcmp($user['acronym'], "admin") === 0) {
                $isAllowed = true;
            } else if ($user['id'] == $userId) {
                $isAllowed = true;
            }
        }

        return $isAllowed;
    }

    /**
     * Gets the user id of the logged in user.
     *
     * Gets the user id of the user who has logged in. If no user has logged
     * in, false is returned.
     *
     * @return int | false  the user id of the user who has logged in, false
     *                      otherwise.
     */
    public function getUserId()
    {
        $user = $this->session->get('user', []);

        return empty($user) ? false : $user['id'];
    }

    /**
     * Gets the acronym of the logged in user.
     *
     * Gets the acronym of the user who has logged in. If no user has logged
     * in, false is returned.
     *
     * @return int | false  the acronym of the user who has logged in, false
     *                      otherwise.
     */
    public function getAcronym()
    {
        $user = $this->session->get('user', []);

        return empty($user) ? false : $user['acronym'];
    }

    /**
     * Gets the user id and acronym of the logged in user.
     *
     * Gets the user id and acronym of the user who has logged in. If no user
     * has logged in, false is returned.
     *
     * @return int | false  the user id and acronym of the user who has logged
     *                      in, false otherwise.
     */
    public function getUserIdAndAcronym()
    {
        $user = $this->session->get('user', []);

        if (empty($user)) {
            return false;
        } else {
            return array('id' => $user['id'], 'acronym' => $user['acronym']);
        }
    }
}
