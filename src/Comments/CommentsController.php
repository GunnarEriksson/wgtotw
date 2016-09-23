<?php


namespace Anax\Comments;

/**
 * To attach comments-flow to a page or some content.
 *
 */
class CommentsController implements \Anax\DI\IInjectionAware
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

        $this->comments = new \Anax\Comments\Comment();
        $this->comments->setDI($this->di);

        $this->questions = new \Anax\Questions\Question();
        $this->questions->setDI($this->di);
    }

    public function addAction($id = null, $questionId = null, $title = null, $controller = null)
    {
        if ($this->isMandatoryParametersPresent($id, $questionId, $title, $controller)) {
            if ($this->LoggedIn->isLoggedin()) {
                $this->addComment($id, $questionId, $title, $controller);
            } else {
                $this->redirectToLoginPage();
            }
        } else {
            $subtitle = "Parametrar saknas";
            $message = "Kan ej skapa kommentar. Obligatoriska parametrar saknas!";

            $this->showErrorMessage($subtitle, $message);
        }
    }

    private function isMandatoryParametersPresent($id, $questionId, $title, $controller)
    {
        $isPresent = false;

        if (isset($id) && isset($questionId) && isset($title) && isset($controller)) {
            $isPresent = true;
        }

        return $isPresent;
    }

    private function addComment($id, $questionId, $title, $controller)
    {
        $userId = $this->LoggedIn->getUserId();
        $form = new \Anax\HTMLForm\Comments\CFormAddComment($id, $questionId, $userId, $controller);
        $form->setDI($this->di);
        $status = $form->check();

        $this->di->theme->setTitle("Kommentar: " . $title);
        $this->di->views->add('comment/commentForm', [
            'title' => "Kommentar: " . $title,
            'content' => $form->getHTML(),
        ], 'main');
    }

    private function redirectToLoginPage()
    {
        $this->dispatcher->forward([
            'controller' => 'user-login',
            'action'     => 'login',
        ]);
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

    public function updateAction($commentId = null)
    {
        if ($this->isUpdateAllowed($commentId)) {
            $this->updateComment($commentId);
        } else {
            $this->handleUpdateIsNotAllowed($commentId);
        }
    }

    private function isUpdateAllowed($commentId)
    {
        $isUpdateAllowed = false;

        if (isset($commentId)) {
            if ($this->LoggedIn->isLoggedin()) {
                $authorId = $this->getCommentAuthorId($commentId);
                $isUpdateAllowed = $this->LoggedIn->isAllowed($authorId);
            }
        }

        return $isUpdateAllowed;
    }

    private function getCommentAuthorId($commentId)
    {
        $authorId = $this->comments->query('U.id')
            ->join('User2Comment AS U2C', 'U2C.idComment = Lf_Comment.id')
            ->join('User AS U', 'U2C.idUser = U.id')
            ->where('Lf_Comment.id = ?')
            ->execute([$commentId]);

        $authorId = empty($authorId) ? false : $authorId[0]->id;

        return $authorId;
    }

    private function updateComment($commentId)
    {
        $commentParentInfo = $this->getCommentParentInfo($commentId);
        $questionId = isset($commentParentInfo['questionId']) ? $commentParentInfo['questionId'] : null;
        $questionTitle = isset($commentParentInfo['questionTitle']) ? $commentParentInfo['questionTitle'] : null;
        if (!isset($questionTitle)) {
            $questionTitle = isset($commentParentInfo['answerContent']) ? $commentParentInfo['answerContent'] : "";
        }

        $commentData = $this->comments->find($commentId);
        if ($commentData) {
            $this->createUpdateCommentForm($commentData->getProperties(), $questionId, $questionTitle);
        } else {
            $subtitle = "Kommentar data saknas";
            $message = "Kommentar data saknas. Kan inte uppdatera kommentar!";
            $this->showErrorMessage($subtitle, $message);
        }
    }

    private function getCommentParentInfo($commentId)
    {
        $answerInfo = $this->getAnswerInfoFromCommentId($commentId);
        if (isset($answerInfo->id)) {
            $questionInfo = $this->getQuestionIdFromAnswerId($answerInfo->id);
        } else {
            $questionInfo = $this->getQuestionInfoFromCommentId($commentId);
        }

        $questionId = isset($questionInfo->id) ? $questionInfo->id : null;
        $questionTitle = isset($questionInfo->title) ? $questionInfo->title : null;
        $answerContent = isset($answerInfo->content) ? $this->getSubstring($answerInfo->content, 30) : null;

        $commentParentInfo = ['questionId' => $questionId, 'questionTitle' => $questionTitle, 'answerContent' => $answerContent];

        return $commentParentInfo;
    }

    private function getAnswerInfoFromCommentId($commentId)
    {
        $answerInfo = $this->comments->query('A.id, A.content')
            ->join('Answer2Comment AS A2C', 'A2C.idComment = Lf_Comment.id')
            ->join('Answer AS A', 'A2C.idAnswer = A.id')
            ->where('Lf_Comment.id = ?')
            ->execute([$commentId]);

        $answerInfo = empty($answerInfo) ? false : $answerInfo[0];

        return $answerInfo;
    }

    private function getQuestionIdFromAnswerId($answerId)
    {
        $questionInfo = $this->questions->query('Lf_Question.id')
            ->join('Question2Answer AS Q2A', 'Q2A.idQuestion = Lf_Question.id')
            ->join('Answer AS A', 'Q2A.idAnswer = A.id')
            ->where('A.id = ?')
            ->execute([$answerId]);

        $questionInfo = empty($questionInfo) ? false : $questionInfo[0];

        return $questionInfo;
    }

    private function getQuestionInfoFromCommentId($commentId)
    {
        $questionInfo = $this->comments->query('Q.id, Q.title')
            ->join('Question2Comment AS Q2C', 'Q2C.idComment = Lf_Comment.id')
            ->join('Question AS Q', 'Q2C.idQuestion = Q.id')
            ->where('Lf_Comment.id = ?')
            ->execute([$commentId]);

        $questionInfo = empty($questionInfo) ? false : $questionInfo[0];

        return $questionInfo;
    }

    /**
     * Helper function to get a substring of a string.
     *
     * Returns specified part of an text. A helper function checks the next nearest
     * space in the text for the specified length to prevent a word to be
     * truncated.
     *
     * @param  string $textString   the string to be truncated.
     * @param  int $numOfChar       the maximum length of the text.
     *
     * @return string               the truncated text.
     */
    private function getSubstring($textString, $numOfChar)
    {
        $textEndPos = $this->getSpacePosInString($textString, $numOfChar);
        if ($textEndPos === 0) {
            $text = substr($textString, 0, $numOfChar);
        } else {
            $text = substr($textString, 0, $textEndPos);
            $text .= " ...";
        }

        return $text;
    }

    /**
     * Helper function to find the next space in a string.
     *
     * Finds the next space from the specified position.
     *
     * @param  string $textString   the text string to find a space in.
     * @param  int $offset          the position to find the next space from.
     *
     * @return int the position of the next space from the specified position.
     */
    private function getSpacePosInString($textString, $offset)
    {
        $pos = 0;
        if (strlen($textString) >= $offset) {
            $pos = strpos($textString, ' ', $offset);
        }

        return $pos;
    }


    private function createUpdateCommentForm($commentData, $questionId, $title)
    {
        $form = new \Anax\HTMLForm\Comments\CFormUpdateComment($commentData, $questionId);
        $form->setDI($this->di);
        $status = $form->check();

        $this->di->theme->setTitle("Kommentar: " . $title);
        $this->di->views->add('comment/commentForm', [
            'title' => "Kommentar: " . $title,
            'content' => $form->getHTML(),
        ], 'main');
    }

    private function handleUpdateIsNotAllowed($commentId)
    {
        if (!isset($commentId)) {
            $subtitle = "Id nummer saknas";
            $message = "Id nummer för kommentar saknas. Kan inte koppla kommentar!";
            $this->showErrorMessage($subtitle, $message);
        } else if ($this->LoggedIn->isLoggedin()) {
            $questionInfo = $this->getQuestionInfo($commentId);
            $questionId = isset($questionInfo->id) ? $questionInfo->id : false;
            $warningMessage = "Endast egna svar kan uppdateras!";
            $this->flash->warningMessage($warningMessage);
            if ($questionId) {
                $this->redirectToQuestion($questionId);
            } else {
                $this->redirectToQuestions();
            }
        } else {
            $this->redirectToLoginPage();
        }
    }

    private function redirectToQuestion($questionId)
    {
        $this->dispatcher->forward([
            'controller' => 'questions',
            'action'     => 'id',
            'params'     => [$questionId]
        ]);
    }

    private function redirectToQuestions()
    {
        $this->dispatcher->forward([
            'controller' => 'questions',
            'action'     => 'list'
        ]);
    }

    public function upVoteAction($commentId)
    {
        $this->dispatcher->forward([
            'controller' => 'comment-votes',
            'action'     => 'increase',
            'params'     => [$commentId]
        ]);
    }

    public function downVoteAction($commentId)
    {
        $this->dispatcher->forward([
            'controller' => 'comment-votes',
            'action'     => 'decrease',
            'params'     => [$commentId]
        ]);
    }
}
