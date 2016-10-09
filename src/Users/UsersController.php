<?php
namespace Anax\Users;

/**
 * Users controller
 *
 * Communicates with the user table in the database.
 * Handles all user releated tasks and present the results to views.
 */
class UsersController implements \Anax\DI\IInjectionAware
{
    use \Anax\DI\TInjectable;

    /**
     * Initialize the controller.
     *
     * Initializes the session, the user model.
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
     * Lists all questions in DB.
     *
     * Lists all users in DB starting with the latest created user.
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
     * Lists a specific user with related questions, answers and comments.
     *
     * Checks if a user has logged in to be able to show more detailed
     * information about a user.
     *
     * Information such as full name, town, e-mail, date of membership,
     * written questions, answers and comments. A table with the users
     * activities are also shown with the activity scores.
     *
     * @param  int $id          the user id of the user to list, default null.
     * @param  string $type     the type of list to show (question, answer or
     *                          comments)
     *
     * @return void.
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
     * error message are shown. Lists
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

    /**
     * Helper method to create default activity scores.
     *
     * Sets all activity scores to zero.
     *
     * @return [int]    A default activity score array with the score names
     *                  as keys.
     */
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

    /**
     * Helper method to get question scores from DB.
     *
     * Gets the question scores for a user from DB.
     *
     * @param  int $userId          the id of the user.
     * @param  [int] $activityInfo  the activity info array for the user.
     *
     * @return [int]                the activity info array for the user.
     */
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

    /**
     * Helper method to get answers scores from DB.
     *
     * Gets the answers scores for a user from DB.
     *
     * @param  int $userId          the id of the user.
     * @param  [int] $activityInfo  the activity info array for the user.
     *
     * @return [int]                the activity info array for the user.
     */
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

    /**
     * Helper method to get comment scores from DB.
     *
     * Gets the comment scores for a user from DB.
     *
     * @param  int $userId          the id of the user.
     * @param  [int] $activityInfo  the activity info array for the user.
     *
     * @return [int]                the activity info array for the user.
     */
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

    /**
     * Helper method to get number how many answers a user has accepted from DB.
     *
     * Gets the number how many answers a user has accepted from DB.
     *
     * @param  int $userId          the id of the user.
     * @param  [int] $activityInfo  the activity info array for the user.
     *
     * @return [int]                the activity info array for the user.
     */
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

    /**
     * Helper method to calculate the sum of the ranking points.
     *
     * Calculates the sum of the rankingpoints by adding the points for
     * question rank, answer rank and comments rank.
     *
     * @param  [int] $activityInfo  the activity info array for the user.
     *
     * @return [int] $activityInfo  the activity info array for the user.
     */
    private function getRankPoints($activityInfo)
    {
        $rankPoints = $activityInfo['questionRank'] + $activityInfo['answerRank'] + $activityInfo['commentRank'];
        $activityInfo['rankPoints'] = $rankPoints;

        return $activityInfo;
    }

    /**
     * Helper method to calculate the total sum of points.
     *
     * Calculates the total sum of points by adding the points for
     * question score, answer score, comments score, accept score,
     * vote score and ranking points.
     *
     * @param  [int] $activityInfo  the activity info array for the user.
     * @param  int the id of the user.
     *
     * @return [int] $activityInfo  the activity info array for the user.
     */
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

    /**
     * Helper method to show user comments.
     *
     * Redirects to the Comments controller to list all comments created
     * by the user.
     *
     * @param  int $userId  the id of the user.
     *
     * @return void.
     */
    private function showUserComments($userId)
    {
        $this->dispatcher->forward([
            'controller' => 'comments',
            'action'     => 'list-user-comments',
            'params'     => [$userId]
        ]);
    }

    /**
     * Helper method to show user answers.
     *
     * Redirects to the Comments controller to list all answers created
     * by the user.
     *
     * @param  int $userId  the id of the user.
     *
     * @return void.
     */
    private function showUserAnswers($userId)
    {
        $this->dispatcher->forward([
            'controller' => 'answers',
            'action'     => 'list-user-answers',
            'params'     => [$userId]
        ]);
    }

    /**
     * Helper method to show user questions.
     *
     * Redirects to the Comments controller to list all questions created
     * by the user.
     *
     * @param  int $userId  the id of the user.
     *
     * @return void.
     */
    private function showUserQuestions($userId)
    {
        $this->dispatcher->forward([
            'controller' => 'questions',
            'action'     => 'list-user-questions',
            'params'     => [$userId]
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
     * Creates a form to add a new user with and accompanying information about
     * the website.
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
     * Updates the user profile of a user, if the user is allowed to update
     * the profile. If the user is not allowed to update the profile, a page
     * not found is shown.
     *
     * @param integer $id the id of user, which should update the profile.
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
     * @param  integer  $id the id of the user who should update the profile.
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
     * Creates a form to update the user information, if the user could be found
     * in DB.
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
     * Add score action.
     *
     * Checks if it is allowed to update activity score and the score could be
     * saved in DB. If it could not be saved in DB, a flash error message is
     * created.
     *
     * Uses the last inserted id from session to prevent score to be added from
     * the web browsers address bar. The call to the method must come from
     * another controller. If not, page not found is shown.
     *
     * @param int $activityScore    the activity score to be added.
     * @param int $lastInsertedId   the id of the last added type in DB.
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

    /**
     * Helper method to check if it is allowed to add score.
     *
     * Checks if the last inserted id is stored in session.
     *
     * @param  int  $id     the last inserted id to check in session.
     *
     * @return boolean      true if it is allowed to add score, false otherwise.
     */
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

    /**
     * Helper method to update the activity score for a user.
     *
     * Gets the activity score from DB and add the new ones before saving the
     * sum to the DB.
     *
     * @param  int $userId          the user id of the score owner.
     * @param  int $activityScore   the score to add to the existing score.
     *
     * @return boolean              the new score could be saved in DB, false
     *                              otherwise.
     */
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

    /**
     * Helper method to get a users activity score from DB.
     *
     * Gets a users activity score from DB. If no activity score is found, zero
     * is returned.
     *
     * @param  int $userId  the user id of the score owner.
     *
     * @return int  the activity score.
     */
    private function getActivityScoreFromDb($userId)
    {
        $activityScore = $this->users->query('activityScore')
            ->where('id = ?')
            ->execute([$userId]);

        return empty($activityScore) ? 0 : $activityScore[0]->activityScore;
    }

     /**
      * Increase question counter.
      *
      * Checks if it is allowed to increase the question counter and if the
      * new counter number could be saved in DB.
      * If it is not allowed to increase the counter or the new counter number
      * could not be saved in DB, a flash error message is created.
      *
      * Uses the last inserted id in session to prevent score to be increased
      * directly from the browsers address bar. The call must come from an
      * another controller to be valid.
      *
      * @param int $lastInsertedId   the id of the last added type in DB.
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

    /**
     * Helper method to increase the question counter for a user.
     *
     * Gets the number of written questions for a user in DB. Increases the
     * number of with one and saves the new number in DB.
     *
     * @param  int $userId  the id of the user who owns the question counter.
     *
     * @return boolean      true if the new counter number is saved, false otherwise.
     */
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

    /**
     * Helper method to get a users number of written questions from DB.
     *
     * Gets a users number of written questions from DB. If no number of written
     * questions is found, zero is returned.
     *
     * @param  int $userId  the user id of the counter owner.
     *
     * @return int  the number of written questions.
     */
    private function getNumOfQuestionsFromDb($userId)
    {
        $numOfQuestions = $this->users->query('numQuestions')
            ->where('id = ?')
            ->execute([$userId]);

        return empty($numOfQuestions) ? 0 : $numOfQuestions[0]->numQuestions;
    }

    /**
     * Increase answer counter.
     *
     * Checks if it is allowed to increase the answer counter and if the
     * new counter number could be saved in DB.
     * If it is not allowed to increase the counter or the new counter number
     * could not be saved in DB, a flash error message is created.
     *
     * Uses the last inserted id in session to prevent score to be increased
     * directly from the browsers address bar. The call must come from an
     * another controller to be valid.
     *
     * @param int $lastInsertedId   the id of the last added type in DB.
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

    /**
     * Helper method to increase the answer counter for a user.
     *
     * Gets the number of written answers for a user in DB. Increases the number
     * with one and saves the new number in DB.
     *
     * @param  int $userId  the id of the user who owns the answer counter.
     *
     * @return boolean      true if the new counter number is saved, false otherwise.
     */
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

    /**
     * Helper method to get a users number of written answers from DB.
     *
     * Gets a users number of written answers from DB. If no number of written
     * answers is found, zero is returned.
     *
     * @param  int $userId  the user id of the counter owner.
     *
     * @return int  the number of written answers.
     */
    private function getNumOfAnswersFromDb($userId)
    {
        $numOfAnswers = $this->users->query('numAnswers')
            ->where('id = ?')
            ->execute([$userId]);

        return empty($numOfAnswers) ? 0 : $numOfAnswers[0]->numAnswers;
    }

    /**
     * Increase comment counter.
     *
     * Checks if it is allowed to increase the comment counter and if the
     * new counter number could be saved in DB.
     * If it is not allowed to increase the counter or the new counter number
     * could not be saved in DB, a flash error message is created.
     *
     * Uses the last inserted id in session to prevent score to be increased
     * directly from the browsers address bar. The call must come from an
     * another controller to be valid.
     *
     * @param int $lastInsertedId   the id of the last added type in DB.
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

    /**
     * Helper method to increase the comment counter for a user.
     *
     * Gets the number of written comments for a user in DB. Increases the number
     * with one and saves the new number in DB.
     *
     * @param  int $userId  the id of the user who owns the comment counter.
     *
     * @return boolean      true if the new counter number is saved, false otherwise.
     */
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

    /**
     * Helper method to get a users number of written comments from DB.
     *
     * Gets a users number of written comments from DB. If no number of written
     * comments is found, zero is returned.
     *
     * @param  int $userId  the user id of the counter owner.
     *
     * @return int  the number of written comments.
     */
    private function getNumOfCommentsFromDb($userId)
    {
        $numOfComments = $this->users->query('numComments')
            ->where('id = ?')
            ->execute([$userId]);

        return empty($numOfComments) ? 0 : $numOfComments[0]->numComments;
    }

    /**
     * Increase vote counter.
     *
     * Checks if it is allowed to increase the vote counter and if the
     * new counter number could be saved in DB.
     * If it is not allowed to increase the counter or the new counter number
     * could not be saved in DB, a flash error message is created.
     *
     * Uses the last inserted id in session to prevent score to be increased
     * directly from the browsers address bar. The call must come from an
     * another controller to be valid.
     *
     * @param int $lastInsertedId   the id of the last added type in DB.
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

    /**
     * Helper method to increase the vote counter for a user.
     *
     * Gets the number of vote made by the user in DB. Increases the number
     * with one and saves the new number in DB.
     *
     * @param  int $userId  the id of the user who owns the vote counter.
     *
     * @return boolean      true if the new counter number is saved, false otherwise.
     */
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

    /**
     * Helper method to get a users number of votes from DB.
     *
     * Gets a users number of votes from DB. If no number of votes is found, zero
     * is returned.
     *
     * @param  int $userId  the user id of the counter owner.
     *
     * @return int  the number of votes.
     */
    private function getNumOfVotesFromDb($userId)
    {
        $numOfVotes = $this->users->query('numVotes')
            ->where('id = ?')
            ->execute([$userId]);

        return empty($numOfVotes) ? 0 : $numOfVotes[0]->numVotes;
    }

    /**
     * Increase accepted answers counter.
     *
     * Checks if it is allowed to increase the accepted anwers counter and if
     * the new counter number could be saved in DB.
     * If it is not allowed to increase the counter or the new counter number
     * could not be saved in DB, a flash error message is created.
     *
     * Uses the last inserted id in session to prevent score to be increased
     * directly from the browsers address bar. The call must come from an
     * another controller to be valid.
     *
     * @param int $lastInsertedId   the id of the last added type in DB.
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

    /**
     * Helper method to increase the accepted answers counter for a user.
     *
     * Gets the number of accepted answers made by the user in DB. Increases
     * the number with one and saves the new number in DB.
     *
     * @param  int $userId  the id of the user who owns the accepted answers counter.
     *
     * @return boolean      true if the new counter number is saved, false otherwise.
     */
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

    /**
     * Helper method to get a users number accepted answers from DB.
     *
     * Gets a users number of accepted answers from DB. If no number of votes
     * is found, zero is returned.
     *
     * @param  int $userId  the user id of the counter owner.
     *
     * @return int  the number of accepted answers.
     */
    private function getNumOfAcceptsFromDb($userId)
    {
        $numOfAccepts = $this->users->query('numAccepts')
            ->where('id = ?')
            ->execute([$userId]);

        return empty($numOfAccepts) ? 0 : $numOfAccepts[0]->numAccepts;
    }

    /**
     * Lists the most active users.
     *
     * Lists the most active users based on the users activity such as write
     * questions, answers, comments, vote and accepts answers. The question
     * score, answer score and comment score are not included when rating the
     * the most active users. List the most active users first.
     *
     * @param  int $num number of active users to be shown.
     *
     * @return void.
     */
    public function listActiveAction($num)
    {
        $users = $this->getMostActiveUsers($num);

        $this->views->add('index/users', [
            'title'     => "Mest aktiva användare",
            'users' => $users,
        ], 'triptych-3');
    }

    /**
     * Helper method to get the most active users.
     *
     * Gets the users gravatar, user id and acronym. List the users in
     * ascending order based on the activity score.
     *
     * @param  int $num     number of users to list.
     *
     * @return [object]     the most active users.    
     */
    private function getMostActiveUsers($num)
    {
        $users = $this->users->query('Lf_User.gravatar, Lf_User.id, Lf_User.acronym')
            ->orderBy('activityScore asc')
            ->limit($num)
            ->execute();

        return $users;
    }
}
