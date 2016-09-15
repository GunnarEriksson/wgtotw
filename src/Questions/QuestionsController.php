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
        $allQuestions = $this->getQuestionsWithUserId('Lf_Question.id desc');

        $this->di->theme->setTitle("Alla frågor");
        $this->views->add('question/questions', [
            'title' => "Alla Frågor",
            'questions'  => $allQuestions,
        ], 'main-wide');
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
    public function idAction($id = null)
    {
        $question = $this->findQuestionFromId($id);

        if ($question) {
            $tags = $this->getTagIdAndLabelFromQuestionId($id);

            $this->theme->setTitle("Fråga");
            $this->views->add('question/question', [
                'title' => 'Fråga',
                'question' => $question[0],
                'tags' => $tags,
            ], 'main-wide');
        } else {
            $content = [
                'subtitle' => 'Hittar ej fråga',
                'message' =>  'Hittar ej fråga med id: ' . $id
            ];

            $this->showNoSuchUserMessage($content);
        }

        $this->di->dispatcher->forward([
            'controller' => 'answers',
            'action'     => 'list',
            'params'     => [$id, 'created asc']
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

    public function addAction()
    {
        if ($this->di->session->has('user')) {
            $this->addQuestion();
        } else {
            $this->pageNotFound();
        }
    }

    private function addQuestion()
    {
        $user = $this->di->session->get('user', []);
        $tagLabels = $this->getAllTagLables();
        $form = new \Anax\HTMLForm\Questions\CFormAddQuestion($user, $tagLabels);
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
        $question = $this->questions->find($questionId);
        $userId = $question === false ? null : $question->userId ;
        if ($this->isUpdateQuestionAllowed($userId)) {
            $this->updateQuestion($question);
        } else {
            $this->pageNotFound();
        }
    }

    private function isUpdateQuestionAllowed($id)
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

    private function updateQuestion($question)
    {
        $tagLabels = $this->getAllTagLables();
        $checkedTags = $this->getCheckedTagsFromQuestionId($question->id);
        $form = new \Anax\HTMLForm\Questions\CFormUpdateQuestion($question->getProperties(), $tagLabels, $checkedTags);
        $form->setDI($this->di);
        $status = $form->check();

        $this->di->theme->setTitle("Uppdatera fråga");
        $this->di->views->add('question/questionForm', [
            'title' => "Uppdatera Fråga",
            'content' => $form->getHTML(),
        ], 'main');
    }

    private function getCheckedTagsFromQuestionId($questionId)
    {
        $tags = $this->getTagIdAndLabelFromQuestionId($questionId);

        return $this->convertToLabelArray($tags);
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
}
