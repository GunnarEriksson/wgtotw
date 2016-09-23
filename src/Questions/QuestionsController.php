<?php
namespace Anax\Questions;

class QuestionsController implements \Anax\DI\IInjectionAware
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

        $this->questions = new \Anax\Questions\Question();
        $this->questions->setDI($this->di);

        $this->tags = new \Anax\Tags\Tag();
        $this->tags->setDI($this->di);
    }

    public function listAction()
    {
        if ($this->di->flash->hasMessage()) {
            $this->showFlashMessage();
        }

        $allQuestions = $this->getQuestionsWithUserId('Lf_Question.id desc');

        $this->di->theme->setTitle("Alla frågor");
        $this->views->add('question/questions', [
            'title' => "Alla Frågor",
            'questions'  => $allQuestions,
        ], 'main-wide');

        if ($this->session->has('lastInsertedId')) {
            unset($_SESSION["lastInsertedId"]);
        }
    }

    private function getQuestionsWithUserId($orderBy)
    {
        $questionsAndUserIds = $this->questions->query('Lf_Question.*, Lf_User.acronym AS author')
            ->join('User2Question', 'Lf_Question.id = Lf_User2Question.idQuestion')
            ->join('User', 'Lf_User2Question.idUser = Lf_User.id')
            ->orderBy($orderBy)
            ->execute();

        return $questionsAndUserIds;
    }

    /**
     * List user with id.
     *
     * @param int $id of user to display
     *
     * @return void
     */
    public function idAction($id, $orderBy = null)
    {
        $question = $this->findQuestionFromId($id);

        if ($question) {
            if ($this->di->flash->hasMessage()) {
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

        $this->di->dispatcher->forward([
            'controller' => 'answers',
            'action'     => 'list',
            'params'     => [$id, $orderBy]
        ]);
    }

    private function findQuestionFromId($questionId)
    {
        $questionWithUserInfo = $this->questions->query('Lf_Question.*, Lf_User.acronym, Lf_User.gravatar')
            ->join('User2Question', 'Lf_Question.id = Lf_User2Question.idQuestion')
            ->join('User', 'Lf_User2Question.idUser = Lf_User.id')
            ->where('Lf_Question.id = ?')
            ->execute([$questionId]);

        return $questionWithUserInfo;
    }

    private function showFlashMessage()
    {
        $this->di->dispatcher->forward([
            'controller' => 'flash',
            'action'     => 'flash',
        ]);
    }

    private function getTagIdAndLabelFromQuestionId($questionId)
    {
        $tagIdAndLabels = $this->questions->query('Lf_Tag.id, Lf_Tag.label')
            ->join('Question2Tag', 'Lf_Question.id = Lf_Question2Tag.idQuestion')
            ->join('Tag', 'Lf_Question2Tag.idTag = Lf_Tag.id')
            ->where('Lf_Question.id = ?')
            ->orderBy('Lf_Tag.id asc')
            ->execute([$questionId]);

        return $tagIdAndLabels;
    }

    private function getAllCommentsForSpecificQuestion($questionId)
    {
        $comments = $this->questions->query('C.*, U.acronym')
            ->join('Question2Comment AS Q2C', 'Q2C.idQuestion = Lf_Question.id')
            ->join('Comment AS C', 'Q2C.idComment = C.id')
            ->join('User2Comment AS U2C', 'C.id = U2C.idComment')
            ->join('User AS U', 'U2C.idUser = U.id')
            ->where('Lf_Question.id = ?')
            ->orderBy('C.created asc')
            ->execute([$questionId]);

        return $comments;
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

    public function addAction()
    {
        if ($this->LoggedIn->isLoggedin()) {
            $this->addQuestion();
        } else {
            $this->redirectToLoginPage();
        }
    }

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

    private function createAddForm($userId, $tagLabels)
    {
        $form = new \Anax\HTMLForm\Questions\CFormAddQuestion($userId, $tagLabels);
        $form->setDI($this->di);
        $status = $form->check();

        $this->di->theme->setTitle("Skapa fråga");
        $this->di->views->add('question/questionForm', [
            'title' => "Skapa Fråga",
            'content' => $form->getHTML(),
        ], 'main');
    }

    private function getAllTagLables()
    {
        $allTagLabels = $this->tags->query('label')
            ->execute();

        return $this->convertToLabelArray($allTagLabels);
    }

    private function convertToLabelArray($object)
    {
        $tagLabels = [];

        foreach ($object as $value) {
            $tagLabels[] = $value->label;
        }

        return $tagLabels;
    }

    private function redirectToLoginPage()
    {
        $this->dispatcher->forward([
            'controller' => 'user-login',
            'action'     => 'login',
        ]);
    }

    public function tagIdAction($tagId = null)
    {
        $questions = $this->questions->query('Lf_Question.*, U.acronym AS author')
            ->join('Question2Tag AS Q2T', 'Lf_Question.id = Q2T.idQuestion')
            ->join('Tag AS T', 'Q2T.idTag = T.id')
            ->join('User2Question AS U2Q', 'Lf_Question.id = U2Q.idQuestion')
            ->join('User AS U', 'U2Q.idUser = U.id')
            ->where('T.id = ?')
            ->orderBy('Lf_Question.created desc')
            ->execute([$tagId]);

        $label = $this->getTagLabelFromTagId($tagId);
        $title = empty($label) ? "Frågor" : "Frågor om " . $label;

        $this->di->theme->setTitle($title);
        $this->views->add('question/questions', [
            'title' => $title,
            'questions'  => $questions,
        ], 'main-wide');
    }

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

    public function updateAction($questionId)
    {
        if ($this->isUpdateAllowed($questionId)) {
            $this->updateQuestion($questionId);
        } else {
            $this->handleUpdateIsNotAllowed($questionId);
            if ($this->LoggedIn->isLoggedin()) {
                $warningMessage = "Endast egna frågor kan uppdateras!";
                $this->flash->warningMessage($warningMessage);

                $this->redirectToQuestion($questionId);
            } else {
                $this->redirectToLoginPage();
            }
        }
    }

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

    private function getQuestionAuthorId($questionId)
    {
        $authorId = $this->questions->query('U.id')
            ->join('User2Question AS U2Q', 'U2Q.idQuestion = Lf_Question.id')
            ->join('User AS U', 'U2Q.idUser = U.id')
            ->where('Lf_Question.id = ?')
            ->execute([$questionId]);

        $authorId = empty($authorId) ? false : $authorId[0]->id;

        return $authorId;
    }

    private function updateQuestion($questionId)
    {
        $questionInfo = $this->getQuestionInfo($questionId);

        if ($questionInfo === false) {
            $this->showNoSuchIdMessage($questionId, 'fråga');
        } else {
            $this->showUpdateQuestionForm($questionInfo);
        }
    }

    private function getQuestionInfo($questionId)
    {
        $questionInfo = $this->questions->find($questionId);
        $questionInfo = $questionInfo === false ? $questionInfo : $questionInfo->getProperties();

        return $questionInfo;
    }

    private function showUpdateQuestionForm($questionInfo)
    {
        $tagLabels = $this->getAllTagLables();
        $checkedTags = $this->getCheckedTagsFromQuestionId($questionInfo['id']);
        $form = new \Anax\HTMLForm\Questions\CFormUpdateQuestion($questionInfo, $tagLabels, $checkedTags);
        $form->setDI($this->di);
        $status = $form->check();

        $this->di->theme->setTitle("Uppdatera");
        $this->di->views->add('question/questionForm', [
            'title' => $questionInfo['title'],
            'content' => $form->getHTML(),
        ], 'main');
    }

    private function handleUpdateIsNotAllowed($questionId)
    {
        if (!isset($questionId)) {
            $subtitle = "Fråge-id saknas";
            $message = "Fråge-id saknas. Kan inte uppdatera fråga!";

            $this->showErrorMessage($subtitle, $message);
        } else if ($this->LoggedIn->isLoggedin()) {
            $warningMessage = "Endast egna svar kan uppdateras!";
            $this->flash->warningMessage($warningMessage);

            $this->redirectToQuestion($questionId);
        } else {
            $this->redirectToLoginPage();
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

    private function getCheckedTagsFromQuestionId($questionId)
    {
        $tags = $this->getTagIdAndLabelFromQuestionId($questionId);

        return $this->convertToLabelArray($tags);
    }

    private function redirectToQuestion($questionId)
    {
        $this->dispatcher->forward([
            'controller' => 'questions',
            'action'     => 'id',
            'params'     => [$questionId]
        ]);
    }

    /**
     * Increase counter
     *
     * Increases the number of connected questions counter
     */
    public function increaseCounterAction($questionId)
    {
        $numberOfAnswers = $this->getNumberOfAnswerConnections($questionId);

        if (!empty($numberOfAnswers)) {
            $numAnswers = $numberOfAnswers[0]->answers;
            $this->saveNumOfAnswerConnections($questionId, ++$numAnswers);
        }
    }

    private function getNumberOfAnswerConnections($questionId)
    {
        $numAnswers = $this->questions->query('answers')
            ->where('id = ?')
            ->execute([$questionId]);

        return $numAnswers;
    }

    private function saveNumOfAnswerConnections($questionId, $numAnswers)
    {
        $isSaved = $this->questions->save(array(
            'id'        => $questionId,
            'answers'   => $numAnswers,
        ));

        return $isSaved;
    }

    public function addCommentAction($questionId)
    {
        $title = $this->getTitleFromId($questionId);

        $this->dispatcher->forward([
            'controller' => 'comments',
            'action'     => 'add',
            'params'     => [$questionId, $questionId, $title, 'question-comment']
        ]);
    }

    private function getTitleFromId($questionId)
    {
        $title = $this->questions->query('title')
            ->where('id = ?')
            ->execute([$questionId]);

        return $title === false ? "" : $title[0]->title;
    }

    public function upVoteAction($questionId)
    {
        $this->dispatcher->forward([
            'controller' => 'question-votes',
            'action'     => 'increase',
            'params'     => [$questionId]
        ]);
    }

    public function downVoteAction($questionId)
    {
        $this->dispatcher->forward([
            'controller' => 'question-votes',
            'action'     => 'decrease',
            'params'     => [$questionId]
        ]);
    }

    public function listLatestAction($num)
    {
        $questions = $this->getLatestQuestions($num);

        $this->views->add('index/questions', [
            'title'     => "Senaste frågorna",
            'questions' => $questions,
        ], 'triptych-1');
    }

    private function getLatestQuestions($num)
    {
        $questions = $this->questions->query('Lf_Question.id, Lf_Question.title')
            ->orderBy('created asc')
            ->limit($num)
            ->execute();

        return $questions;
    }
}
