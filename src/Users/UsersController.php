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
                        if (strcmp($acronym, "admin") === 0) {
                            $edit = '<a href="users/update/' . $user->id . '"><i class="fa fa-pencil-square-o" style="color:green" aria-hidden="true"></i></a>';
                            if (strcmp($user->acronym, "admin") !== 0) {
                                $delete = '<a href="users/delete/' . $user->id . '"><i class="fa fa-trash-o" style="color:red" aria-hidden="true"></i></a>';
                            }
                        } else if (strcmp($acronym, $user->acronym) === 0) {
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
     * Delete user.
     *
     * @param integer $id of user to delete.
     *
     * @return void
     */
    public function deleteAction($id = null)
    {
        if ($this->isDeleteUserAllowed($id)) {
            $this->deleteUser($id);
        } else {
            $this->pageNotFound();
        }
    }

    /**
     * Helper method to check if it is allowed to delete a user.
     *
     * Checks if the user is admin and is not trying to delete the admin account.
     * It is only admin who has the rights to delete other users than admin.
     *
     * @return boolean true if the user could be deleted, false otherwise.
     */
    private function isDeleteUserAllowed($id)
    {
        $isAdmin = false;

        if ($this->di->session->has('user') && isset($id)) {
            $user = $this->di->session->get('user', []);
            if (strcmp($user['acronym'], "admin") === 0 && $user['id'] != $id) {
                $isAdmin = true;
            }
        }

        return $isAdmin;
    }


    /**
     * Helper function to delete a user.
     *
     * Deletes a user if the user id is found in the database. If not an error
     * message is shown.
     *
     * @param  integer $id the id of the user to be deleted.
     *
     * @return void.
     */
    private function deleteUser($id)
    {
        $user = $this->users->find($id);

        if ($user) {
            $form = new \Anax\HTMLForm\Users\CFormDeleteUser($user->getProperties());
            $form->setDI($this->di);
            $status = $form->check();

            $this->di->theme->setTitle("Radera användare");
            $this->di->views->add('users/userForm', [
                'title' => "Användare",
                'subtitle' => "Radera användare",
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
     * Delete (soft) user.
     *
     * @param integer $id of user to delete.
     *
     * @return void
     */
    public function softDeleteAction($id = null)
    {
        $user = $this->users->find($id);

        if ($user) {

            $form = new \Anax\HTMLForm\Users\CFormSoftDeleteUser($user->getProperties());
            $form->setDI($this->di);
            $status = $form->check();

            $this->di->theme->setTitle("Ta bort användare");
            $this->di->views->add('users/userForm', [
                'title' => "Användare",
                'subtitle' => "Ta bort användare",
                'content' => $form->getHTML(),
            ], 'main');

            $info = $this->di->fileContent->get('users/softDeleteUserInfo.md');
            $info = $this->di->textFilter->doFilter($info, 'shortcode, markdown');

            $this->di->views->add('users/userInfo', [
                'content' => $info,
            ], 'sidebar');
        } else {
            $content = [
                'subtitle' => 'Hittar ej användare',
                'message' =>  'Hittar ej användare med id: ' . $id
            ];

            $this->showNoSuchUserMessage($content);
        }
    }

    /**
     * List all active and not deleted users.
     *
     * @return void
     */
    public function activeAction()
    {
        $allActive = $this->users->query()
            ->where('active IS NOT NULL')
            ->andWhere('deleted is NULL')
            ->execute();

        $this->theme->setTitle("Aktiva användare");
        $this->views->add('users/index', [
            'users' => $allActive,
            'title' => "Användare",
            'subtitle' => "Visa aktiva användare"
        ], 'main');

        $this->views->add('users/userAdmin', ['title' => "Användare", 'subtitle' => 'Administration'], 'sidebar');
    }

    /**
     * List all inactive users.
     *
     * @return void
     */
    public function inactiveAction()
    {
        $allInactive = $this->users->query()
            ->where('active is NULL')
            ->andWhere('deleted is NULL')
            ->execute();

        $this->theme->setTitle("Inaktiva användare");
        $this->views->add('users/index', [
            'users' => $allInactive,
            'title' => "Användare",
            'subtitle' => "Visa inaktiva användare"
        ], 'main');

        $this->views->add('users/userAdmin', ['title' => "Användare", 'subtitle' => 'Administration'], 'sidebar');
    }

    /**
     * List all discarded users.
     *
     * @return void
     */
    public function discardedAction()
    {
        $allInactive = $this->users->query()
            ->where('deleted IS NOT NULL')
            ->execute();

        $this->theme->setTitle("Användare i papperskorgen");
        $this->views->add('users/index', [
            'users' => $allInactive,
            'title' => "Användare",
            'subtitle' => "Visa användare i papperskorgen"
        ], 'main');

        $this->views->add('users/userAdmin', ['title' => "Användare", 'subtitle' => 'Administration'], 'sidebar');
    }

    /**
     * Reset database to inital values
     *
     *
     */
    public function resetDbAction()
    {
        $this->db->dropTableIfExists('user')->execute();

        $this->db->createTable(
            'user',
            [
                'id' => ['integer', 'primary key', 'not null', 'auto_increment'],
                'acronym' => ['varchar(20)', 'unique', 'not null'],
                'email' => ['varchar(80)'],
                'name' => ['varchar(80)'],
                'password' => ['varchar(255)'],
                'created' => ['datetime'],
                'updated' => ['datetime'],
                'deleted' => ['datetime'],
                'active' => ['datetime'],
            ]
        )->execute();

        $this->db->insert(
            'user',
            [
                'acronym',
                'email',
                'name',
                'password',
                'created',
                'active'
            ]
        );

        $now = gmdate('Y-m-d H:i:s');

        $this->db->execute(
            [
                'admin',
                'admin@dbwebb.se',
                'Administrator',
                password_hash('admin', PASSWORD_DEFAULT),
                $now,
                $now
            ]
        );

        $url = $this->url->create('users');
        $this->response->redirect($url);
    }
}
