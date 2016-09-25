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
        $this->session();

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
                    if ($this->LoggedIn->isLoggedin()) {
                        return '<a href="users/id/'. $user->id . '"><img src="' . $user->gravatar .'?s=40" alt="Gravatar"></a>';
                    } else {
                        return '<img src="' . $user->gravatar .'?s=40" alt="Gravatar">';
                    }
                }
            ],
            'object2' => [
                'title'    => 'Akronym',
                'function'    => function ($user) {
                    if ($this->LoggedIn->isLoggedin()) {
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

                    $acronym = $this->LoggedIn->getAcronym();
                    if ($acronym) {
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
    public function idAction($id = null, $type = "question")
    {
        if ($this->LoggedIn->isLoggedin()) {
            if (isset($id)) {
                $this->showUserProfile($id);
                if (strcmp($type, "comment") === 0) {
                    $this->showUserComments($id);
                } else if (strcmp($type, "answer") === 0) {
                    $this->showUserAnswers($id);
                } else {
                    $this->showUserQuestions($id);
                }
            } else {
                $content = [
                    'subtitle' => 'Användare id saknas',
                    'message' =>  'Användare id saknas, kan EJ visa användareprofil.'
                ];

                $this->showNoSuchUserMessage($content);
            }
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
        $activityInfo = $this->createDefaultUserActivityInfo();
        $activityInfo = $this->getQuestionScores($id, $activityInfo);
        $activityInfo = $this->getAnswerScores($id, $activityInfo);
        $activityInfo = $this->getCommentScores($id, $activityInfo);
        $activityInfo = $this->getNumberOfAccepts($id, $activityInfo);
        $activityInfo = $this->getRankPoints($activityInfo);
        $activityInfo = $this->calculateSum($activityInfo, $user->getProperties());

        if ($user) {
            $this->theme->setTitle("Användareprofil");
            $this->views->add('users/userView', [
                'activity'      => $activityInfo,
                'user'          => $user,
            ], 'main-wide');
        } else {
            $content = [
                'subtitle' => 'Hittar ej användare',
                'message' =>  'Hittar ej användare med id: ' . $id
            ];

            $this->showNoSuchUserMessage($content);
        }
    }

    private function createDefaultUserActivityInfo()
    {
        $userInfoScores = [
            'questions'     => 0,
            'questionScore' => 0,
            'questionRank'  => 0,
            'answers'       => 0,
            'answerScore'   => 0,
            'answerRank'    => 0,
            'comments'      => 0,
            'commentScore'  => 0,
            'commentRank'   => 0,
            'accepts'       => 0,
            'acceptScore'   => 0,
            'rankPoints'    => 0,
            'sum'           => 0,
        ];

        return $userInfoScores;
    }

    private function getQuestionScores($userId, $activityInfo)
    {
        $questionScores = $this->users->query('Q.score')
            ->join('User2Question AS U2Q', 'U2Q.idUser = Lf_User.id')
            ->join('Question AS Q', 'U2Q.idQuestion = Q.id')
            ->where('Lf_User.id = ?')
            ->execute([$userId]);

        $scores = 0;
        foreach ($questionScores as $questionScore) {
            $scores += $questionScore->score;
        }

        $activityInfo['questions'] = count($questionScores);
        $activityInfo['questionScore'] = $activityInfo['questions'] * 5;
        $activityInfo['questionRank'] = $scores;

        return $activityInfo;
    }

    private function getAnswerScores($userId, $activityInfo)
    {
        $answerScores = $this->users->query('A.score')
            ->join('User2Answer AS U2A', 'U2A.idUser = Lf_User.id')
            ->join('Answer AS A', 'U2A.idAnswer = A.id')
            ->where('Lf_User.id = ?')
            ->execute([$userId]);

        $scores = 0;
        foreach ($answerScores as $answerScore) {
            $scores += $answerScore->score;
        }

        $activityInfo['answers'] = count($answerScores);
        $activityInfo['answerScore'] = $activityInfo['answers'] * 3;
        $activityInfo['answerRank'] = $scores;

        return $activityInfo;
    }

    private function getCommentScores($userId, $activityInfo)
    {
        $commentScores = $this->users->query('C.score')
            ->join('User2Comment AS U2C', 'U2C.idUser = Lf_User.id')
            ->join('Comment AS C', 'U2C.idComment = C.id')
            ->where('Lf_User.id = ?')
            ->execute([$userId]);

        $scores = 0;
        foreach ($commentScores as $commentScore) {
            $scores += $commentScore->score;
        }

        $activityInfo['comments'] = count($commentScores);
        $activityInfo['commentScore'] = $activityInfo['comments'] * 2;
        $activityInfo['commentRank'] = $scores;

        return $activityInfo;
    }

    private function getNumberOfAccepts($userId, $activityInfo)
    {
        $acceptedAnswers = $this->users->query('A.id')
            ->join('User2Question AS U2Q', 'U2Q.idUser = Lf_User.id')
            ->join('Question AS Q', 'U2Q.idQuestion = Q.id')
            ->join('Question2Answer AS Q2A', 'Q2A.idQuestion = Q.id')
            ->join('Answer AS A', 'Q2A.idAnswer = A.id')
            ->where('Lf_User.id = ?')
            ->andWhere('A.accepted=1')
            ->execute([$userId]);

        $activityInfo['accepts'] = count($acceptedAnswers);
        $activityInfo['acceptScore'] = $activityInfo['accepts'] * 3;

        return $activityInfo;
    }

    private function getRankPoints($activityInfo)
    {
        $rankPoints = $activityInfo['questionRank'] + $activityInfo['answerRank'] + $activityInfo['commentRank'];
        $activityInfo['rankPoints'] = $rankPoints;

        return $activityInfo;
    }

    private function calculateSum($activityInfo, $user)
    {
        $votesScore = isset($user['numVotes']) ? $user['numVotes'] : 0;

        $sum = $activityInfo['questionScore'] + $activityInfo['answerScore'] +
            $activityInfo['commentScore'] + $activityInfo['acceptScore'] +
            $votesScore + $activityInfo['rankPoints'];

        $activityInfo['sum'] = $sum;

        return $activityInfo;
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
        $this->theme->setTitle("Hittar ej användare");
        $this->views->add('error/errorInfo', [
            'title' => 'Användare',
            'subtitle' => $content['subtitle'],
            'message' => $content['message'],
        ], 'main');
    }

    private function showUserComments($id)
    {
        $this->dispatcher->forward([
            'controller' => 'comments',
            'action'     => 'list-user-comments',
            'params'     => [$id]
        ]);
    }

    private function showUserAnswers($id)
    {
        $this->dispatcher->forward([
            'controller' => 'answers',
            'action'     => 'list-user-answers',
            'params'     => [$id]
        ]);
    }

    private function showUserQuestions($id)
    {
        $this->dispatcher->forward([
            'controller' => 'questions',
            'action'     => 'list-user-questions',
            'params'     => [$id]
        ]);
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

        $this->theme->setTitle("Skapa konto");
        $info = $this->fileContent->get('users/registerUserInfo.md');
        $info = $this->textFilter->doFilter($info, 'shortcode, markdown');
        $this->views->add('users/userInfo', [
            'content' => $info,
        ], 'main');

        $this->views->add('users/userForm', [
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
        $user = $this->LoggedIn->getUserIdAndAcronym();

        if ($user && isset($id)) {
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

            $this->theme->setTitle("Uppdatera profil");
            $this->views->add('users/userForm', [
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
                $this->flash->warningMessage($warningMessage);

                if ($this->session->has('lastInsertedId')) {
                    unset($_SESSION["lastInsertedId"]);
                }
            }
        } else {
            $this->pageNotFound();
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

    /**
     * Update user
     *
     * @param integer $id of user to delete.
     *
     * @return void
     */
    public function increaseQuestionsCounterAction($lastInsertedId)
    {
        if ($this->isAllowedToAddScore($lastInsertedId)) {
            $userId = $this->LoggedIn->getUserId();
            if ($this->increaseQuestionsCounter($userId) === false) {
                $warningMessage = "Antal frågor kunde EJ sparas i DB!";
                $this->flash->warningMessage($warningMessage);
            }
        } else {
            $errorMessage = "Kan EJ stega räknaren för antal frågor!";
            $this->flash->errorMessage($errorMessage);
        }
    }

    private function increaseQuestionsCounter($userId)
    {
        $numOfQuestions = $this->getNumOfQuestionsFromDb($userId);
        if ($numOfQuestions === false) {
            $isSaved = false;
        } else {
            $numOfQuestions++;

            $isSaved = $this->users->save(array(
                'id'            => $userId,
                'numQuestions'  => $numOfQuestions,
            ));
        }

        return $isSaved;
    }

    private function getNumOfQuestionsFromDb($userId)
    {
        $numOfQuestions = $this->users->query('numQuestions')
            ->where('id = ?')
            ->execute([$userId]);

        return empty($numOfQuestions) ? 0 : $numOfQuestions[0]->numQuestions;
    }

    /**
     * Update user
     *
     * @param integer $id of user to delete.
     *
     * @return void
     */
    public function increaseAnswersCounterAction($lastInsertedId)
    {
        if ($this->isAllowedToAddScore($lastInsertedId)) {
            $userId = $this->LoggedIn->getUserId();
            if ($this->increaseAnswersCounter($userId) === false) {
                $warningMessage = "Antal svar kunde EJ sparas i DB!";
                $this->flash->warningMessage($warningMessage);
            }
        } else {
            $errorMessage = "Kan EJ stega räknaren för antal svar!";
            $this->flash->errorMessage($errorMessage);
        }
    }

    private function increaseAnswersCounter($userId)
    {
        $numOfAnswers = $this->getNumOfAnswersFromDb($userId);
        if ($numOfAnswers === false) {
            $isSaved = false;
        } else {
            $numOfAnswers++;

            $isSaved = $this->users->save(array(
                'id'            => $userId,
                'numAnswers'  => $numOfAnswers,
            ));
        }

        return $isSaved;
    }

    private function getNumOfAnswersFromDb($userId)
    {
        $numOfAnswers = $this->users->query('numAnswers')
            ->where('id = ?')
            ->execute([$userId]);

        return empty($numOfAnswers) ? 0 : $numOfAnswers[0]->numAnswers;
    }

    /**
     * Update user
     *
     * @param integer $id of user to delete.
     *
     * @return void
     */
    public function increaseCommentsCounterAction($lastInsertedId)
    {
        if ($this->isAllowedToAddScore($lastInsertedId)) {
            $userId = $this->LoggedIn->getUserId();
            if ($this->increaseCommentsCounter($userId) === false) {
                $warningMessage = "Antal kommentarer kunde EJ sparas i DB!";
                $this->flash->warningMessage($warningMessage);
            }
        } else {
            $errorMessage = "Kan EJ stega räknaren för antal kommentarer!";
            $this->flash->errorMessage($errorMessage);
        }
    }

    private function increaseCommentsCounter($userId)
    {
        $numOfComments = $this->getNumOfCommentsFromDb($userId);
        if ($numOfComments === false) {
            $isSaved = false;
        } else {
            $numOfComments++;

            $isSaved = $this->users->save(array(
                'id'            => $userId,
                'numComments'  => $numOfComments,
            ));
        }

        return $isSaved;
    }

    private function getNumOfCommentsFromDb($userId)
    {
        $numOfComments = $this->users->query('numComments')
            ->where('id = ?')
            ->execute([$userId]);

        return empty($numOfComments) ? 0 : $numOfComments[0]->numComments;
    }

    /**
     * Update user
     *
     * @param integer $id of user to delete.
     *
     * @return void
     */
    public function increaseVotesCounterAction($lastInsertedId)
    {
        if ($this->isAllowedToAddScore($lastInsertedId)) {
            $userId = $this->LoggedIn->getUserId();
            if ($this->increaseVotesCounter($userId) === false) {
                $warningMessage = "Antal röster kunde EJ sparas i DB!";
                $this->flash->warningMessage($warningMessage);
            }
        } else {
            $errorMessage = "Kan EJ stega räknaren för antal röster!";
            $this->flash->errorMessage($errorMessage);
        }
    }

    private function increaseVotesCounter($userId)
    {
        $numOfVotes = $this->getNumOfVotesFromDb($userId);
        if ($numOfVotes === false) {
            $isSaved = false;
        } else {
            $numOfVotes++;

            $isSaved = $this->users->save(array(
                'id'            => $userId,
                'numVotes'  => $numOfVotes,
            ));
        }

        return $isSaved;
    }

    private function getNumOfVotesFromDb($userId)
    {
        $numOfVotes = $this->users->query('numVotes')
            ->where('id = ?')
            ->execute([$userId]);

        return empty($numOfVotes) ? 0 : $numOfVotes[0]->numVotes;
    }

    /**
     * Update user
     *
     * @param integer $id of user to delete.
     *
     * @return void
     */
    public function increaseAcceptsCounterAction($lastInsertedId)
    {
        if ($this->isAllowedToAddScore($lastInsertedId)) {
            $userId = $this->LoggedIn->getUserId();
            if ($this->increaseAcceptsCounter($userId) === false) {
                $warningMessage = "Antal accepterande av svar kunde EJ sparas i DB!";
                $this->flash->warningMessage($warningMessage);
            }
        } else {
            $errorMessage = "Kan EJ stega räknaren för antal accepterande av svar!";
            $this->flash->errorMessage($errorMessage);
        }
    }

    private function increaseAcceptsCounter($userId)
    {
        $numOfAccepts = $this->getNumOfAcceptsFromDb($userId);
        if ($numOfAccepts === false) {
            $isSaved = false;
        } else {
            $numOfAccepts++;

            $isSaved = $this->users->save(array(
                'id'            => $userId,
                'numAccepts'  => $numOfAccepts,
            ));
        }

        return $isSaved;
    }

    private function getNumOfAcceptsFromDb($userId)
    {
        $numOfAccepts = $this->users->query('numAccepts')
            ->where('id = ?')
            ->execute([$userId]);

        return empty($numOfAccepts) ? 0 : $numOfAccepts[0]->numAccepts;
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
        $users = $this->users->query('Lf_User.gravatar, Lf_User.id, Lf_User.acronym')
            ->orderBy('activityScore asc')
            ->limit($num)
            ->execute();

        return $users;
    }
}
