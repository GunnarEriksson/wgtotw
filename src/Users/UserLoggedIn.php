<?php

namespace Anax\Users;

class UserLoggedIn
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

    public function isLoggedin()
    {
        return $this->session->has('user');
    }

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

    public function getUserId()
    {
        $user = $this->session->get('user', []);

        return empty($user) ? false : $user['id'];
    }

    public function getAcronym()
    {
        $user = $this->session->get('user', []);

        return empty($user) ? false : $user['acronym'];
    }

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
