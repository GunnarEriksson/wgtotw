<?php
namespace Anax\Questions;

/**
 * Questions controller
 *
 * Communicates with the question and tag table in the database.
 * Handles all question releated tasks and present the results to views.
 */
class QuestionsController implements \Anax\DI\IInjectionAware
{
    use \Anax\DI\TInjectable;

    /**
     * Initialize the controller.
     *
     * Initializes the session, the question and
     * tag models.
     *
     * @return void
     */
    public function initialize()
    {
        $this->session();

        $this->questions = new \Anax\Questions\Question();
        $this->questions->setDI($this->di);

        $this->tags = new \Anax\Tags\Tag();
        $this->tags->setDI($this->di);
    }

    /**
     * Lists all questions in DB.
     *
     * Lists all questions in DB starting with the latest created question.
     * Resets the last inserted id in session. The id is used to prevent access
     * to actions directly via the browsers address bar. Actions that handles
     * a users ranking score.
     *
     * @return void
     */
    public function listAction()
    {
        if ($this->flash->hasMessage()) {
            $this->showFlashMessage();
        }

        $allQuestions = $this->getQuestionsWithUserAcronym('lf_question.id desc');

        $this->theme->setTitle("Alla frågor");

        $this->views->add('question/questionsHeading', [
            'title' => "Alla Frågor",
        ], 'main-wide');

        $this->views->add('question/questions', [
            'questions'  => $allQuestions,
        ], 'main-wide');

        if ($this->session->has('lastInsertedId')) {
            unset($_SESSION["lastInsertedId"]);
        }
    }

    /**
     * Helper method to questions with the author included.
     *
     * Gets all questions from DB with the acronym of the author.
     *
     * @param  string $orderBy the order in which the questions should be presented.
     *
     * @return object[]     All questions in DB, with author acronym included.
     */
    private function getQuestionsWithUserAcronym($orderBy)
    {
        $questionsAndUserIds = $this->questions->query('lf_question.*, lf_user.acronym AS author')
            ->join('user2question', 'lf_question.id = lf_user2question.idQuestion')
            ->join('user', 'lf_user2question.idUser = lf_user.id')
            ->orderBy($orderBy)
            ->execute();

        return $questionsAndUserIds;
    }

    /**
     * Lists a specific question with related answers and comments.
     *
     * Lists a specific question and the related comments. Redirects to the
     * answers controller to list all related answers.
     *
     * @param  int $id          the question id of the question to list.
     * @param  string $orderBy  the order to list the related answers.
     *
     * @return void.
     */
    public function idAction($id, $orderBy = null)
    {
        $question = $this->findQuestionFromId($id);

        if ($question) {
            if ($this->flash->hasMessage()) {
                $this->showFlashMessage();
            }

            $tags = $this->getTagIdAndLabelFromQuestionId($id);
            $comments = $this->getAllCommentsForSpecificQuestion($id);

            $this->theme->setTitle("Fråga");
            $this->views->add('question/question', [
                'title' => 'Fråga',
                'question' => $question[0],
                'tags' => $tags,
                'comments' => $comments,
            ], 'main-wide');
        } else {
            $this->showNoSuchIdMessage($id, 'fråga');
        }

        $orderBy = isset($orderBy) ? 'created desc' : 'score desc' ;

        $this->dispatcher->forward([
            'controller' => 'answers',
            'action'     => 'list',
            'params'     => [$id, $orderBy]
        ]);
    }

    /**
     * Helper method to get a specific question from DB.
     *
     * Gets a specific question with the related authors user id, acronym and
     * gravatar.
     *
     * @param  int $questionId  the id of the question to list.
     *
     * @return object[]     the question data and the authors user id, acronym
     *                      and gravatar.
     */
    private function findQuestionFromId($questionId)
    {
        $questionWithUserInfo = $this->questions->query('lf_question.*, lf_user.id AS userId, lf_user.acronym, lf_user.gravatar')
            ->join('user2question', 'lf_question.id = lf_user2question.idQuestion')
            ->join('user', 'lf_user2question.idUser = lf_user.id')
            ->where('lf_question.id = ?')
            ->execute([$questionId]);

        return $questionWithUserInfo;
    }

    /**
     * Helper method to show a flash message.
     *
     * Redirects to the flash controller to show a flash message.
     *
     * @return void.
     */
    private function showFlashMessage()
    {
        $this->dispatcher->forward([
            'controller' => 'flash',
            'action'     => 'flash',
        ]);
    }

    /**
     * Helper method to get tag id and label for a question in DB.
     *
     * Gets all tag id and labels for a question in DB.
     *
     * @param  int $questionId  the id of the question to get all the tag id and
     *                          tag labels for.
     *
     * @return object[]         All tag id and labels for a question.
     */
    private function getTagIdAndLabelFromQuestionId($questionId)
    {
        $tagIdAndLabels = $this->questions->query('lf_tag.id, lf_tag.label')
            ->join('question2tag', 'lf_question.id = lf_question2tag.idQuestion')
            ->join('tag', 'lf_question2tag.idTag = lf_tag.id')
            ->where('lf_question.id = ?')
            ->orderBy('lf_tag.id asc')
            ->execute([$questionId]);

        return $tagIdAndLabels;
    }

    /**
     * Helper method to get all releated comments for a question in DB.
     *
     * Get all comments with the related authors user id and acronym for one
     * specific question.
     *
     * @param  int $questionId  the question id, which the comments are related to.
     * @return object[]         All comments with the related authors user id
     *                          and acronym.
     */
    private function getAllCommentsForSpecificQuestion($questionId)
    {
        $comments = $this->questions->query('C.*, U.id AS userId, U.acronym')
            ->join('question2comment AS Q2C', 'Q2C.idQuestion = lf_question.id')
            ->join('comment AS C', 'Q2C.idComment = C.id')
            ->join('user2comment AS U2C', 'C.id = U2C.idComment')
            ->join('user AS U', 'U2C.idUser = U.id')
            ->where('lf_question.id = ?')
            ->orderBy('C.created asc')
            ->execute([$questionId]);

        return $comments;
    }

    /**
     * Helper method to show no such id message.
     *
     * Creates content to show no such id message with a return button.
     * Redirects to the errors controller to show the message.
     *
     * @param  int $id      the id that could not be found.
     * @param  string $type the typ of id that could not be found.
     *
     * @return void.
     */
    private function showNoSuchIdMessage($id, $type)
    {
        $defaultUrl = $this->url->create('questions/list/');
        $url = isset($_SERVER["HTTP_REFERER"]) ? $_SERVER["HTTP_REFERER"] : $defaultUrl;

        $content = [
            'title'         => 'Ett fel har uppstått!',
            'subtitle'      => 'Hittar ej ' . $type,
            'message'       => 'Hittar ej ' . $type . ' med id: ' . $id,
            'url'           => $url,
            'buttonName'    => 'Tillbaka'
        ];

        $this->dispatcher->forward([
            'controller' => 'errors',
            'action'     => 'view',
            'params'     => [$content]
        ]);
    }

    /**
     * Adds a question
     *
     * Checks if the user has logged in to be able to create a new question.
     * If not, the user is redirected to the login page.
     *
     * @return void.
     */
    public function addAction()
    {
        if ($this->LoggedIn->isLoggedin()) {
            $this->addQuestion();
        } else {
            $this->redirectToLoginPage();
        }
    }

    /**
     * Helper method to create a new question.
     *
     * Gets the user id and all tags for a question to be able to create a
     * form for creating new questions.
     *
     * If the user id could not be found, a no such id message are shown.
     *
     * @return void.
     */
    private function addQuestion()
    {
        $userId = $this->LoggedIn->getUserId();
        $tagLabels = $this->getAllTagLables();

        if ($userId) {
            $this->createAddForm($userId, $tagLabels);
        } else {
            $this->showNoSuchIdMessage($userId, 'användare');
        }
    }

    /**
     * Helper method to create a form to create a new question.
     *
     * Creates a form for user to create a new question.
     *
     * @param  int $userId          the user id of the author.
     * @param  string[] $tagLabels  All possible tag names.
     *
     * @return void
     */
    private function createAddForm($userId, $tagLabels)
    {
        $form = new \Anax\HTMLForm\Questions\CFormAddQuestion($userId, $tagLabels);
        $form->setDI($this->di);
        $status = $form->check();

        $this->theme->setTitle("Skapa fråga");
        $this->views->add('question/questionForm', [
            'title' => "Skapa Fråga",
            'content' => $form->getHTML(),
        ], 'main');
    }

    /**
     * Helper method to get all tag names in DB.
     *
     * Gets all tag names in DB.
     *
     * @return object[] All tag names in DB.
     */
    private function getAllTagLables()
    {
        $allTagLabels = $this->tags->query('label')
            ->execute();

        return $this->convertToLabelArray($allTagLabels);
    }

    /**
     * Helper method ot convert an object of tag names to an array of tag names.
     *
     * @param  object[] $object the array of tag name objects.
     *
     * @return string[] the array of tag names.
     */
    private function convertToLabelArray($object)
    {
        $tagLabels = [];

        foreach ($object as $value) {
            $tagLabels[] = $value->label;
        }

        return $tagLabels;
    }

    /**
     * Helper method to redirect to the log in page.
     *
     * Redirects to the UserLogin controller to show the log in page.
     *
     * @return void.
     */
    private function redirectToLoginPage()
    {
        $this->dispatcher->forward([
            'controller' => 'user-login',
            'action'     => 'login',
        ]);
    }

    /**
     * Get all questions and the related author user id for a tag in DB.
     *
     * Gets all questions, whith the related author user id, for a tag in DB.
     * Uses the tag name to set the title of the page.
     *
     * @param  int $tagId   the tag id, which is related to questions.
     *
     * @return object[]     All related questions and the authors user id.
     */
    public function tagIdAction($tagId = null)
    {
        $questions = $this->questions->query('lf_question.*, U.acronym AS author')
            ->join('question2tag AS Q2T', 'lf_question.id = Q2T.idQuestion')
            ->join('tag AS T', 'Q2T.idTag = T.id')
            ->join('user2question AS U2Q', 'lf_question.id = U2Q.idQuestion')
            ->join('user AS U', 'U2Q.idUser = U.id')
            ->where('T.id = ?')
            ->orderBy('lf_question.created desc')
            ->execute([$tagId]);

        $label = $this->getTagLabelFromTagId($tagId);
        $title = empty($label) ? "Frågor" : "Frågor om " . $label;

        $this->theme->setTitle($title);

        $this->views->add('question/questionsHeading', [
            'title' => $title,
        ], 'main-wide');

        $this->views->add('question/questions', [
            'questions' => $questions,
        ], 'main-wide');
    }

    /**
     * Helper method to get tag label from tag id.
     *
     * Gets the tag name for a tag id.
     *
     * @param  int $tagId   the tag id for the tag label (name).
     *
     * @return string       if found, the name of the tag. Otherwise an empty
     *                      string.
     */
    private function getTagLabelFromTagId($tagId)
    {
        $label = "";
        $tag = $this->tags->find($tagId);
        if ($tag !== false) {
            if (!empty($tag->label)) {
                $label = $tag->label;
            }
        }

        return $label;
    }

    /**
     * Updates a question.
     *
     * Updates the question if the user is allowed to update. To be able to
     * update, the user must be admin or the author of the question.
     *
     * @param  int $questionId the question id. Default null.
     *
     * @return void.
     */
    public function updateAction($questionId = null)
    {
        if ($this->isUpdateAllowed($questionId)) {
            $this->updateQuestion($questionId);
        } else {
            $this->handleUpdateIsNotAllowed($questionId);
        }
    }

    /**
     * Helper method to check if the user is allowed to update the question.
     *
     * Checks if the user has logged in and the user is admin or the author of
     * the question.
     *
     * @param  int $questionId   the id of the question.
     *
     * @return boolean          true if allowed to update, false otherwise.
     */
    private function isUpdateAllowed($questionId)
    {
        $isUpdateAllowed = false;

        if (isset($questionId)) {
            if ($this->LoggedIn->isLoggedin()) {
                $authorId = $this->getQuestionAuthorId($questionId);
                $isUpdateAllowed = $this->LoggedIn->isAllowed($authorId);
            }
        }

        return $isUpdateAllowed;
    }

    /**
     * Helper method to get the id of the user who wrote the question.
     *
     * Gets the user id of the user who wrote the question from DB.
     *
     * @param  int $questionId    the id of the question.
     *
     * @return int | false  the id of the user who wrote the question, false
     *                      otherwise.
     */
    private function getQuestionAuthorId($questionId)
    {
        $authorId = $this->questions->query('U.id')
            ->join('user2question AS U2Q', 'U2Q.idQuestion = lf_question.id')
            ->join('user AS U', 'U2Q.idUser = U.id')
            ->where('lf_question.id = ?')
            ->execute([$questionId]);

        $authorId = empty($authorId) ? false : $authorId[0]->id;

        return $authorId;
    }

    /**
     * Helper method to update the question.
     *
     * Gets the question info from DB to create a form for updating
     * the question. If information is missing, an error message is shown.
     *
     * @param  int $questionId    the id of the question to update.
     *
     * @return void
     */
    private function updateQuestion($questionId)
    {
        $questionInfo = $this->getQuestionInfo($questionId);

        if ($questionInfo === false) {
            $this->showNoSuchIdMessage($questionId, 'fråga');
        } else {
            $this->showUpdateQuestionForm($questionInfo);
        }
    }

    /**
     * Helper method to get the question data from DB.
     *
     * Gets the question data for a specific question. Returns false if no
     * question could be found for the specific question id.
     *
     * @param  int $questionId  the question id to get data for.
     *
     * @return mixed[] | false  if found, the question data. False otherwise.
     */
    private function getQuestionInfo($questionId)
    {
        $questionInfo = $this->questions->find($questionId);
        $questionInfo = $questionInfo === false ? $questionInfo : $questionInfo->getProperties();

        return $questionInfo;
    }

    /**
     * Helper method to show the update question form.
     *
     * Gets all tag labels and the checked tags related to the question. Together
     * with the question data, creates a form for the user to update the question.
     *
     * @param  mixed[] $questionInfo    the question data to be updated.
     *
     * @return void.
     */
    private function showUpdateQuestionForm($questionInfo)
    {
        $tagLabels = $this->getAllTagLables();
        $checkedTags = $this->getCheckedTagsFromQuestionId($questionInfo['id']);
        $form = new \Anax\HTMLForm\Questions\CFormUpdateQuestion($questionInfo, $tagLabels, $checkedTags);
        $form->setDI($this->di);
        $status = $form->check();

        $this->theme->setTitle("Uppdatera");
        $this->views->add('question/questionForm', [
            'title' => $questionInfo['title'],
            'content' => $form->getHTML(),
        ], 'main');
    }

    /**
     * Helper method to handle update is not allowed.
     *
     * Handles the situation when update is not allowed for some reason.
     *
     * @param  int $questionId  question ID of the question to be updated.
     *
     * @return void.
     */
    private function handleUpdateIsNotAllowed($questionId)
    {
        if (!isset($questionId)) {
            $subtitle = "Fråge-id saknas";
            $message = "Fråge-id saknas. Kan inte uppdatera fråga!";

            $this->showErrorMessage($subtitle, $message);
        } else if ($this->LoggedIn->isLoggedin()) {
            $noticeMessage = "Endast egna frågor kan uppdateras!";
            $this->flash->noticeMessage($noticeMessage);

            $this->redirectToQuestion($questionId);
        } else {
            $this->redirectToLoginPage();
        }
    }

    /**
     * Helper function for initiate an error message.
     *
     * Forwards an error message to the error controller, which displays the
     * message to a view. The error message contains of a subtitle, an error
     * message and a return button.
     *
     * @param string $subtitle  The subtitle of the error.
     * @param string $message   The error message.
     *
     * @return void
     */
    private function showErrorMessage($subtitle, $message)
    {
        $defaultUrl = $this->url->create('questions/list/');
        $url = isset($_SERVER["HTTP_REFERER"]) ? $_SERVER["HTTP_REFERER"] : $defaultUrl;

        $content = [
            'title'         => 'Ett fel har uppstått!',
            'subtitle'      => $subtitle,
            'message'       => $message,
            'url'           => $url,
            'buttonName'    => 'Tillbaka'
        ];

        $this->dispatcher->forward([
            'controller' => 'errors',
            'action'     => 'view',
            'params'     => [$content]
        ]);
    }

    /**
     * Helper method to get all checked tags for a question.
     *
     * Gets all checked tags for a question.
     *
     * @param  int $questionId  the question id of the question containing the
     *                          checked tags.
     *
     * @return string[]         the names of the checked tags.
     */
    private function getCheckedTagsFromQuestionId($questionId)
    {
        $tags = $this->getTagIdAndLabelFromQuestionId($questionId);

        return $this->convertToLabelArray($tags);
    }

    /**
     * Helper method to redirect to show the specified question.
     *
     * Redirects to back to the QuestionController to show the specified
     * question with the related answers and comments.
     *
     * @param  int $questionId  the id of the question to show.
     *
     * @return void.
     */
    private function redirectToQuestion($questionId)
    {
        $this->dispatcher->forward([
            'controller' => 'questions',
            'action'     => 'id',
            'params'     => [$questionId]
        ]);
    }

    /**
     * Increase the answser connections counter.
     *
     * Increases the number of connected answers counter.
     *
     * @param  int $questionId  the question id to the related answers.
     *
     * @return void.
     */
    public function increaseCounterAction($questionId)
    {
        $numberOfAnswers = $this->getNumberOfAnswerConnections($questionId);

        if (!empty($numberOfAnswers)) {
            $numAnswers = $numberOfAnswers[0]->answers;
            $this->saveNumOfAnswerConnections($questionId, ++$numAnswers);
        }
    }

    /**
     * Helper method to get the number of answers to a question.
     *
     * Gets the number of answers to a question in DB.
     *
     * @param  int $questionId  the question id for the questions with the
     *                          number of answers.
     *
     * @return object[]         the number of answers to a question.
     */
    private function getNumberOfAnswerConnections($questionId)
    {
        $numAnswers = $this->questions->query('answers')
            ->where('id = ?')
            ->execute([$questionId]);

        return $numAnswers;
    }

    /**
     * Helper method to save the number of answers to a question in DB.
     *
     * Saves the number of answers to a question in DB.
     *
     * @param  int $questionId      the id of the question.
     * @param  int $numAnswers      the number of answsers to a question.
     *
     * @return boolean              true if the number of answers was saved in
     *                              in DB. False otherwise.
     */
    private function saveNumOfAnswerConnections($questionId, $numAnswers)
    {
        $isSaved = $this->questions->save(array(
            'id'        => $questionId,
            'answers'   => $numAnswers,
        ));

        return $isSaved;
    }

    /**
     * Adds comment to the question.
     *
     * Redirects to the Comments controller to add a comment to the question if
     * question id is present. Otherwise an error message is shown.
     *
     * @param int $questionId   the question id of the question to add a
     *                          comment to. Default null.
     *
     * @return void.
     */
    public function addCommentAction($questionId = null)
    {
        if (isset($questionId)) {
            $title = $this->getTitleFromId($questionId);

            $this->dispatcher->forward([
                'controller' => 'comments',
                'action'     => 'add',
                'params'     => [$questionId, $questionId, $title, 'question-comment']
            ]);
        } else {
            $this->handleAddCommentNotAllowed();
        }
    }

    /**
     * Helper method to handle when add comment is not allowed.
     *
     * Sets subtitle and message and shows an error message when adding a comment
     * to a question is not allowed because of the id number of the question is
     * missing
     *
     * @return void.
     */
    private function handleAddCommentNotAllowed()
    {
        $subtitle = "Id nummer saknas";
        $message = "Id nummer för fråga saknas. Kan inte koppla kommentar till fråga!";
        $this->showErrorMessage($subtitle, $message);
    }

    /**
     * Helper method to get the question title.
     *
     * Gets the question title for a specific question.
     *
     * @param  int $questionId  the question id.
     *
     * @return string | false   question title, if found. False otherwise.
     */
    private function getTitleFromId($questionId)
    {
        $title = $this->questions->query('title')
            ->where('id = ?')
            ->execute([$questionId]);

        return $title === false ? "" : $title[0]->title;
    }

    /**
     * Increases the counter for an up vote action.
     *
     * If question id is present, redirects to the QuestionVotes controller
     * to increase the vote score with one. Otherwise an error message is shown.
     *
     * @param  int $questionId  the question id. Default null.
     *
     * @return void.
     */
    public function upVoteAction($questionId = null)
    {
        if (isset($questionId)) {
            $this->dispatcher->forward([
                'controller' => 'question-votes',
                'action'     => 'increase',
                'params'     => [$questionId]
            ]);
        } else {
            $this->voteIsNotAllowed();
        }
    }

    /**
     * Helper method to handle when voting is not allowed.
     *
     * Sets subtitle and message and shows an error message when voting
     * is not allowed because of the id number of the question is missing
     *
     * @return void.
     */
    private function voteIsNotAllowed()
    {
        $subtitle = "Id nummer saknas";
        $message = "Id nummer för fråga saknas. Röstning inte tillåten!";
        $this->showErrorMessage($subtitle, $message);
    }

    /**
     * Decreases the counter for an down vote action.
     *
     * If question id is present, redirects to the QuestionVotes controller to
     * decrease the vote score with one. Otherwise an error message is shown.
     *
     * @param  int $questionId  the question id.
     *
     * @return void.
     */
    public function downVoteAction($questionId = null)
    {
        if (isset($questionId)) {
            $this->dispatcher->forward([
                'controller' => 'question-votes',
                'action'     => 'decrease',
                'params'     => [$questionId]
            ]);
        } else {
            $this->voteIsNotAllowed();
        }
    }

    /**
     * Lists the latest created questions.
     *
     * Lists the latest created questions according to the specified number.
     *
     * @param  int $num     number of questions to be listed.
     *
     * @return void.
     */
    public function listLatestAction($num)
    {
        $questions = $this->getLatestQuestions($num);

        $this->views->add('index/questions', [
            'title'     => "Senaste frågorna",
            'questions' => $questions,
        ], 'triptych-1');
    }

    /**
     * Helper method to list a number of latest created questions from DB.
     *
     * Lists a number of latest created question in a descending order.
     *
     * @param  int $num     number of questions to be listed.
     *
     * @return object[]     number of latest created questions, together with
     *                      the authors acronyms.
     */
    private function getLatestQuestions($num)
    {
        $questionsAndUserIds = $this->questions->query('lf_question.id, lf_question.title,
                                                        lf_question.created, lf_user.acronym AS author')
            ->join('user2question', 'lf_question.id = lf_user2question.idQuestion')
            ->join('user', 'lf_user2question.idUser = lf_user.id')
            ->orderBy('lf_question.created desc')
            ->limit($num)
            ->execute();

        return $questionsAndUserIds;
    }

    /**
     * Lists a users all created questions.
     *
     * Lists a users all created questions. If the user id is missing, an error
     * message are shown.
     *
     * @param  int $userId  the id of the user.
     *
     * @return void.
     */
    public function listUserQuestionsAction($userId = null)
    {
        if (isset($userId)) {
            $this->lisUserQuestions($userId);
        } else {
            $errorMessage = "Användare id saknas. Kan ej lista frågor!";
            $this->flash->errorMessage($errorMessage);
        }
    }

    /**
     * Helper method to show all questions that is related to a user.
     *
     * Lists all questions, which a user has created, together with the number
     * of questions.
     *
     * @param  int  $userId [description]
     * @return boolean         [description]
     */
    private function lisUserQuestions($userId)
    {
        $allQuestions = $this->getAllQuestionsForUser($userId);
        $item = count($allQuestions) === 1 ? "Fråga" : "Frågor";

        $this->views->add('users/itemHeading', [
            'numOfAnswers'  => count($allQuestions),
            'item'          => $item,
            'type'          => "question",
            'userId'        => $userId,
        ], 'main-wide');

        $this->views->add('question/questions', [
            'questions'  => $allQuestions,
        ], 'main-wide');
    }

    /**
     * Helper method to get all questions related to a user from DB.
     *
     * Gets all user related questions from DB, together with the acronym of
     * the user (author).
     *
     * @param  int $userId  the user id of the user.
     * @return object[]     all questions related to a user and the users acronym.
     */
    private function getAllQuestionsForUser($userId)
    {
        $questionsAndUserId = $this->questions->query('lf_question.*, U.acronym AS author')
            ->join('user2question AS U2C', 'lf_question.id = U2C.idQuestion')
            ->join('user AS U', 'U2C.idUser = U.id')
            ->where('U.id = ?')
            ->orderBy('created desc')
            ->execute([$userId]);

        return $questionsAndUserId;
    }
}
