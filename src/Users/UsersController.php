<?php
namespace Anax\Users;

/**
 * A controller for users and admin related events.
 *
 */
class UsersController implements \Anax\DI\IInjectionAware
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

    /**
     * List all users.
     *
     * @return void
     */
    public function listAction()
    {
        $all = $this->users->findAll();
        $table = $this->createTable($all);

        $this->theme->setTitle("Användare");
        $this->views->add('users/index', ['content' => $table, 'title' => "Användare"], 'main-wide');
    }

    /**
     * Helper method to create a table of users.
     *
     * Creates a table of all users containing id, name, user active information,
     * and the possiblity to edit and delete a user.
     *
     * @param  [object] $data an array of user objects.
     *
     * @return html the user table
     */
    private function createTable($data)
    {
        $table = new \Guer\HTMLTable\CHTMLTable();

        $tableSpecification = [
            'id'        => 'users',
        ];

        $table = $table->create($tableSpecification, $data, [
            'object1' => [
                'title'    => 'Gravatar',
                'function'    => function ($user) {
                    if ($this->di->session->has('user')) {
                        return '<a href="users/id/'. $user->id . '"><img src="' . $user->gravatar .'?s=40" alt="Gravatar"></a>';
                    } else {
                        return '<img src="' . $user->gravatar .'?s=40" alt="Gravatar">';
                    }
                }
            ],
            'object2' => [
                'title'    => 'Akronym',
                'function'    => function ($user) {
                    if ($this->di->session->has('user')) {
                        return '<a href="users/id/'. $user->id . '">' . $user->acronym . '</a>';
                    } else {
                        return $user->acronym;
                    }
                }
            ],
            'town' => [
                'title'    => 'Ort',
            ],
            'created' => [
                'title'    => 'Medlem',
            ],
            'object3' => [
                'title'    => 'Redigera',
                'function'    => function ($user) {
                    $edit = null;
                    $delete = null;

                    if ($this->di->session->has('user')) {
                        $acronym = $this->di->session->get('user')['acronym'];
                        if (strcmp($acronym, "admin") === 0 || strcmp($acronym, $user->acronym) === 0) {
                            $edit = '<a href="users/update/' . $user->id . '"><i class="fa fa-pencil-square-o" style="color:green" aria-hidden="true"></i></a>';
                        }
                    }

                    return $edit . " " . $delete;
                }
            ],
        ]);

        return $table->getHTMLTable();
    }

    /**
     * List user with id.
     *
     * @param int $id of user to display
     *
     * @return void
     */
    public function idAction($id = null)
    {
        if ($this->di->session->has('user')) {
            $this->showUserProfile($id);
        } else {
            $this->pageNotFound();
        }
    }

    /**
     * Helper method to show a user profile.
     *
     * Shows a user profile based on the user id. If no user could be found, an
     * error message are shown.
     *
     * @param  int $id the id of the user to be shown.
     *
     * @return void.
     */
    private function showUserProfile($id)
    {
        $user = $this->users->find($id);

        if ($user) {
            $this->theme->setTitle("Användareprofil");
            $this->views->add('users/userView', [
                'title' => 'Profil',
                'user' => $user,
            ]);
        } else {
            $content = [
                'subtitle' => 'Hittar ej användare',
                'message' =>  'Hittar ej användare med id: ' . $id
            ];

            $this->showNoSuchUserMessage($content);
        }
    }

    /**
     * Helper function for initiate no such user view.
     *
     * Initiates a view which shows a message the user with the specfic
     * id is not found. Contains a return button.
     *
     * @param  [] $content the subtitle and the message shown at page.
     *
     * @return void
     */
    private function showNoSuchUserMessage($content)
    {
        $this->theme->setTitle("View user with id");
        $this->views->add('error/errorInfo', [
            'title' => 'Användare',
            'subtitle' => $content['subtitle'],
            'message' => $content['message'],
        ], 'main');
    }

    /**
     * Helper method to show page 404, page not found.
     *
     * Shows page 404 with the text that the page could not be found and you
     * must login to get the page you are looking for.
     *
     * @return void
     */
    private function pageNotFound()
    {
        $this->theme->setTitle("Sidan saknas");
        $this->views->add('error/404', [
            'title' => 'Sidan saknas',
        ], 'main-wide');
    }

    /**
     * Add new user.
     *
     * @param string $acronym of user to add.
     *
     * @return void
     */
    public function addAction($acronym = null)
    {
        $form = new \Anax\HTMLForm\Users\CFormAddUser();
        $form->setDI($this->di);
        $status = $form->check();

        $this->di->theme->setTitle("Skapa konto");
        $info = $this->di->fileContent->get('users/registerUserInfo.md');
        $info = $this->di->textFilter->doFilter($info, 'shortcode, markdown');
        $this->di->views->add('users/userInfo', [
            'content' => $info,
        ], 'main');

        $this->di->views->add('users/userForm', [
            'title' => "Skapa Konto",
            'subtitle' => "Formulär",
            'content' => $form->getHTML(),
        ], 'sidebar');
    }

    /**
     * Update user
     *
     * @param integer $id of user to delete.
     *
     * @return void
     */
    public function updateAction($id = null)
    {
        if ($this->isUpdateProfileAllowed($id)) {
            $this->updateUserProfile($id);
        } else {
            $this->pageNotFound();
        }
    }

    /**
     * Helper method to check if the user is allowed to update a user profile.
     *
     * Checks if the user is allowed to update a user profile or not. Admin has
     * the right to update all profiles. Other users has the right to update
     * their own profile.
     *
     * @param  integer  $id the id of the user profile to be updated.
     *
     * @return boolean  true if the user has the right to update the profile, false
     *                       otherwise.
     */
    private function isUpdateProfileAllowed($id)
    {
        $isUpdateAllowed = false;

        if ($this->di->session->has('user') && isset($id)) {
            $user = $this->di->session->get('user', []);
            if (strcmp($user['acronym'], "admin") === 0) {
                $isUpdateAllowed = true;
            } else if ($user['id'] == $id) {
                $isUpdateAllowed = true;
            }
        }

        return $isUpdateAllowed;
    }

    /**
     * Helper method to update a user profile.
     *
     * Updates a user profile if the id could be found in the database. If not
     * an error message are shown.
     *
     * @param  integer $id the id of the user to be updated.
     *
     * @return void.
     */
    private function updateUserProfile($id)
    {
        $user = $this->users->find($id);

        if ($user) {
            $form = new \Anax\HTMLForm\Users\CFormUpdateUser($user->getProperties());
            $form->setDI($this->di);
            $status = $form->check();

            $this->di->theme->setTitle("Uppdatera profil");
            $this->di->views->add('users/userForm', [
                'title' => "Användare",
                'subtitle' => "Uppdatera profil",
                'content' => $form->getHTML(),
            ], 'main');
        } else {
            $content = [
                'subtitle' => 'Hittar ej användare',
                'message' =>  'Hittar ej användare med id: ' . $id
            ];

            $this->showNoSuchUserMessage($content);
        }
    }

    /**
     * Update user
     *
     * @param integer $id of user to delete.
     *
     * @return void
     */
    public function addScoreAction($activityScore, $lastInsertedId)
    {
        if ($this->isAllowedToAddScore($lastInsertedId)) {
            $userId = $this->LoggedIn->getUserId();
            if ($this->updateActivityScore($userId, $activityScore) === false) {
                $warningMessage = "Aktivtetspoäng kunde inte sparas för användare i DB!";
                $this->di->flash->warningMessage($warningMessage);
            }
        } else {
            $this->pageNotFound();
        }

        if ($this->session->has('lastInsertedId')) {
            unset($_SESSION["lastInsertedId"]);
        }
    }

    private function isAllowedToAddScore($id)
    {
        $isAllowed = false;

        if ($this->LoggedIn->isLoggedin()) {
            $lastInsertedId = $this->session->get('lastInsertedId');
            if (!empty($lastInsertedId)) {
                if ($lastInsertedId === $id) {
                    $isAllowed = true;
                }
            }
        }

        return $isAllowed;
    }

    private function updateActivityScore($userId, $activityScore)
    {
        $activityScoreInDb = $this->getActivityScoreFromDb($userId);
        if ($activityScoreInDb === false) {
            $isSaved = false;
        } else {
            $activityScore = $activityScoreInDb + $activityScore;

            $isSaved = $this->users->save(array(
                'id'            => $userId,
                'activityScore' => $activityScore,
            ));
        }

        return $isSaved;
    }

    private function getActivityScoreFromDb($userId)
    {
        $activityScore = $this->users->query('activityScore')
            ->where('id = ?')
            ->execute([$userId]);

        return empty($activityScore) ? 0 : $activityScore[0]->activityScore;
    }

    public function listActiveAction($num)
    {
        $users = $this->getMostActiveUsers($num);

        $this->views->add('index/users', [
            'title'     => "Mest aktiva användare",
            'users' => $users,
        ], 'triptych-3');
    }

    private function getMostActiveUsers($num)
    {
        $users = $this->users->query('Lf_User.id, Lf_User.acronym')
            ->orderBy('activityScore asc')
            ->limit($num)
            ->execute();

        return $users;
    }
}
