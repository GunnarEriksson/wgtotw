<?php


namespace Anax\Comments;

/**
 * Comments controller
 *
 * Communicates with the comment and question table in the database.
 * Handles all comment releated tasks and present the results to views.
 */

class CommentsController implements \Anax\DI\IInjectionAware
{
    use \Anax\DI\TInjectable;

    /**
     * Initialize the controller.
     *
     * Initializes the session, the comment and
     * question models.
     *
     * @return void
     */
    public function initialize()
    {
        $this->session();

        $this->comments = new \Anax\Comments\Comment();
        $this->comments->setDI($this->di);

        $this->questions = new \Anax\Questions\Question();
        $this->questions->setDI($this->di);
    }

    /**
     * Adds an comment to a question or an answer.
     *
     * Checks if mandatory parameters are present and the user has logged in
     * to be able to add an answer. If mandatory parameters are not present, the
     * user is redirected to an error page. If user is not logged in, a flash
     * message is created and the user is directed to the question, if question
     * ID exists, else to the page with all the questions.
     *
     * @param int $id               the question or answer id. Default null.
     * @param int $questionId       the question id. Used for redirecting purpose.
     *                              Default null.
     * @param string $title         The heading of the comment form. Default null.
     * @param string $controller    The name of the controller to redirect to.
     *                              Default null.
     *
     * @return void
     */
    public function addAction($id = null, $questionId = null, $title = null, $controller = null)
    {
        if ($this->isMandatoryParametersPresent($id, $questionId, $title, $controller)) {
            if ($this->LoggedIn->isLoggedin()) {
                $this->addComment($id, $questionId, $title, $controller);
            } else {
                $noticeMessage = "Du måste vara inloggad för att kommentera!";
                $this->flash->noticeMessage($noticeMessage);
                if ($questionId) {
                    $this->redirectToQuestion($questionId);
                } else {
                    $this->redirectToQuestions();
                }
            }
        } else {
            $subtitle = "Parametrar saknas";
            $message = "Kan ej skapa kommentar. Obligatoriska parametrar saknas!";

            $this->showErrorMessage($subtitle, $message);
        }
    }

    /**
     * Helper method to check if mandatory parameters are present.
     *
     * Checks if mandatory parameters are present and returns the result.
     *
     * @param int $id               the question or answer id.
     * @param int $questionId       the question id. Used for redirecting purpose.
     * @param string $title         The heading of the comment form.
     * @param string $controller    The name of the controller to redirect to.
     *
     * @return boolean              true if mandatory parameters are present, false
     *                              otherwise.
     */
    private function isMandatoryParametersPresent($id, $questionId, $title, $controller)
    {
        $isPresent = false;

        if (isset($id) && isset($questionId) && isset($title) && isset($controller)) {
            $isPresent = true;
        }

        return $isPresent;
    }

    /**
     * Helper method to create an comment form.
     *
     * Creates an comment form and its view. Uses the title of the question or
     * the beginning of the answer for the heading of the form.
     *
     * @param int $id               the question or answer id.
     * @param int $questionId       the question id. Used for redirecting purpose.
     * @param string $title         The heading of the comment form.
     * @param string $controller    The name of the controller to redirect to.
     *
     * @return void
     */
    private function addComment($id, $questionId, $title, $controller)
    {
        $userId = $this->LoggedIn->getUserId();
        $form = new \Anax\HTMLForm\Comments\CFormAddComment($id, $questionId, $userId, $controller);
        $form->setDI($this->di);
        $status = $form->check();

        $this->theme->setTitle("Kommentar: " . $title);
        $this->views->add('comment/commentForm', [
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
     * Updates a comment.
     *
     * Updates the comment if the user is allowed to update. To be able to update,
     * the user must be admin or the author of the comment.
     *
     * @param  int $commentId    the id of the comment. Default null.
     *
     * @return void
     */
    public function updateAction($commentId = null)
    {
        if ($this->isUpdateAllowed($commentId)) {
            $this->updateComment($commentId);
        } else {
            $this->handleUpdateIsNotAllowed($commentId);
        }
    }

    /**
     * Helper method to check if the user is allowed to update the comment.
     *
     * Checks if the user has logged in and the user is admin or the author of
     * the comment.
     *
     * @param  int $commentId   the id of the comment.
     *
     * @return boolean          true if allowed to update, false otherwise.
     */
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

    /**
     * Helper method to get the id of the user who wrote the comment.
     *
     * Gets the user id of the user who wrote the comment from DB.
     *
     * @param  int $commentId    the id of the comment.
     *
     * @return int | false  the id of the user who wrote the comment, false
     *                      otherwise.
     */
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

    /**
     * Helper method to update the comment.
     *
     * Gets information about the parent of the comment. The parent could be
     * either a question or an comment. The form uses the title of the
     * question, if the comment is related to a question or the beginning of
     * the answer, if the comment is related to an answer.
     *
     * Creates an update comment form if comment data is found, creates an
     * error message otherwise.
     *
     * @param  int $commentId   the id of the comment.
     * @return void
     */
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

    /**
     * Helper method to get the parent info for a comment.
     *
     * Gets the parent info for a comment. The parent could be either a
     * question or an comment.
     *
     * If the comment is related to an answer, the question id is the id of the
     * question the answer is related to.
     *
     * @param  id $commentId    the id of the comment.
     *
     * @return mixed[]          the question id, the question title if the comment
     *                          is related to a question and answer content if
     *                          comment is related to an answer.
     */
    private function getCommentParentInfo($commentId)
    {
        $answerInfo = $this->getAnswerInfoFromCommentId($commentId);
        if (isset($answerInfo->id)) {
            $questionInfo = $this->getQuestionInfoFromAnswerId($answerInfo->id);
        } else {
            $questionInfo = $this->getQuestionInfoFromCommentId($commentId);
        }

        $questionId = isset($questionInfo->id) ? $questionInfo->id : null;
        $questionTitle = isset($questionInfo->title) ? $questionInfo->title : null;
        $answerContent = isset($answerInfo->content) ? $this->getSubstring($answerInfo->content, 30) : null;

        $commentParentInfo = ['questionId' => $questionId, 'questionTitle' => $questionTitle, 'answerContent' => $answerContent];

        return $commentParentInfo;
    }

    /**
     * Helper method to get answer info from comment id.
     *
     * Gets the answer info of the answer the comment is related to in DB.
     *
     * @param  int $commentId    the id of the comment.
     *
     * @return object[]  the answer info, if found. Otherwise false.
     */
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

    /**
     * Helper method to get question info from answer id.
     *
     * Gets the question info in DB from the answer the question is related to.
     *
     * @param  int $answerId    the id of the answer the question is related to.
     *
     * @return object[]  the question info, if found. Otherwise false.
     */
    private function getQuestionInfoFromAnswerId($answerId)
    {
        $questionInfo = $this->questions->query('Lf_Question.id')
            ->join('Question2Answer AS Q2A', 'Q2A.idQuestion = Lf_Question.id')
            ->join('Answer AS A', 'Q2A.idAnswer = A.id')
            ->where('A.id = ?')
            ->execute([$answerId]);

        $questionInfo = empty($questionInfo) ? false : $questionInfo[0];

        return $questionInfo;
    }

    /**
     * Helper method to get question info from comment id.
     *
     * Gets the question info in DB from the comment the question is related to.
     *
     * @param  int $commentId    the id of the comment the question is related to.
     *
     * @return object[]  the question info, if found. Otherwise false.
     */
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

    /**
     * Helper method to create an update comment form and send it to a view.
     *
     * Creates an update comment form and sends it to a view.
     *
     * @param  mixed[] $commentData     All comment information.
     * @param  int $questionId          the question id the comment is related to.
     * @param  string $title            the title of the question the or the
     *                                  beginning of the answer the comment is
     *                                  related to.
     *
     * @return void
     */
    private function createUpdateCommentForm($commentData, $questionId, $title)
    {
        $form = new \Anax\HTMLForm\Comments\CFormUpdateComment($commentData, $questionId);
        $form->setDI($this->di);
        $status = $form->check();

        $this->theme->setTitle("Kommentar: " . $title);
        $this->views->add('comment/commentForm', [
            'title' => "Kommentar: " . $title,
            'content' => $form->getHTML(),
        ], 'main');
    }

    /**
     * Helper method to handle update is not allowed.
     *
     * Handles the action when an update is not allowed. Creates an error message
     * and redirects the user.
     *
     * @param  int $commentId    the id of the comment.
     *
     * @return void
     */
    private function handleUpdateIsNotAllowed($commentId)
    {
        if (!isset($commentId)) {
            $subtitle = "Id nummer saknas";
            $message = "Id nummer för kommentar saknas. Kan inte koppla kommentar!";
            $this->showErrorMessage($subtitle, $message);
        } else if ($this->LoggedIn->isLoggedin()) {
            $commentParentInfo = $this->getCommentParentInfo($commentId);
            $questionId = $this->getQuestionId($commentParentInfo);
            $noticeMessage = "Endast egna kommentarer kan uppdateras!";
            $this->flash->noticeMessage($noticeMessage);
            if ($questionId) {
                $this->redirectToQuestion($questionId);
            } else {
                $this->redirectToQuestions();
            }
        } else {
            $this->redirectToLoginPage();
        }
    }

    /**
     * Helper method to redirect to the question controllers id action method.
     *
     * Redirects to the controllers id action method, which shows the question
     * with releated answers and comments.
     *
     * @param  int $questionId  the id of the question.
     *
     * @return void
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
     * Helper method to redirect to the question controllers list action method.
     *
     * Redirects to the controllers list action method, which shows all
     * questions in DB.
     *
     * @return void
     */
    private function redirectToQuestions()
    {
        $this->dispatcher->forward([
            'controller' => 'questions',
            'action'     => 'list'
        ]);
    }

    /**
     * Lists all comments for a user.
     *
     * Lists alla comments for user if user id is found. If not, creats a flash
     * error message.
     *
     * @param  int $userId  the user id. Default null.
     *
     * @return void
     */
    public function listUserCommentsAction($userId = null)
    {
        if (isset($userId)) {
            $this->listUserComments($userId);
        } else {
            $errorMessage = "Användare id saknas. Kan ej lista kommentarer!";
            $this->flash->errorMessage($errorMessage);
        }
    }

    /**
     * Helper method to list all comments for a user.
     *
     * Gets all comments for a user in DB. Creats a navigation bar for the user
     * to choose between questions, answers and comments.
     *
     * Gets parent info to set the the title of the related question or answer
     * for the comment. Gets the question id to be able to redirect the user
     * and creates a view for all answers.
     *
     * @param  int $userId  the user id.
     *
     * @return void
     */
    private function listUserComments($userId)
    {
        $allComments = $this->getAllCommentsForUser($userId);

        $this->createItemNavigationBar($userId, $allComments);

        foreach ($allComments as $comment) {
            $commentParentInfo = $this->getCommentParentInfo($comment->id);
            $title = $this->getCommentTitle($commentParentInfo);
            $questionId = $this->getQuestionId($commentParentInfo);
            $this->createCommentView($title, $questionId, $comment);
        }
    }

    /**
     * Helper method to get all comments for a user in DB.
     *
     * Gets all comments for a user in DB.
     *
     * @param  int $userId  the users id.
     *
     * @return object[]     all comment data for a user.
     */
    private function getAllCommentsForUser($userId)
    {
        $commentData = $this->comments->query('Lf_Comment.*, U.id AS userId, U.acronym')
            ->join('User2Comment AS U2C', 'U2C.idComment = Lf_Comment.id')
            ->join('User AS U', 'U2C.idUser = U.id')
            ->where('U.id = ?')
            ->execute([$userId]);

        return $commentData;
    }

    /**
     * Helper method to create a navigation bar.
     *
     * Creats a navigation bar to show number of questions, answers or comments
     * for a user. Contains tabs to show questions, answers or comments for a
     * user. Lists all comments.
     *
     * @param  int $userId              the user id.
     * @param  object[] $allComments    all comments for a user.
     *
     * @return void
     */
    private function createItemNavigationBar($userId, $allComments)
    {
        $item = count($allComments) === 1 ? "Kommentar" : "Kommentarer";
        $this->views->add('users/itemHeading', [
            'numOfAnswers'  => count($allComments),
            'item'          => $item,
            'type'          => "comment",
            'userId'        => $userId,
        ], 'main-wide');
    }

    /**
     * Helper method to create a comment title.
     *
     * Checks if the parent of a comment is a question or an answer. If it is
     * a question, the title is Fråga and the question title.
     * If it is an answer, the title is Svar and the beginning of the answer.
     *
     * @param  string[] $commentParentInfo  the parent information of the comment.
     *
     * @return string   the title of the comment.
     */
    private function getCommentTitle($commentParentInfo)
    {
        $title = isset($commentParentInfo['questionTitle']) ? "Fråga: " . $commentParentInfo['questionTitle'] : null;
        if (!isset($title)) {
            $title = isset($commentParentInfo['answerContent']) ? "Svar: " . $commentParentInfo['answerContent'] : "";
        }

        return $title;
    }

    /**
     * Helper method to get the question id for the related question.
     *
     * @param  mixed[] $commentParentInfo   the parent info to the related comment.
     *
     * @return int | null   the id of the question, if found. Otherwise false.
     */
    private function getQuestionId($commentParentInfo)
    {
        return isset($commentParentInfo['questionId']) ? $commentParentInfo['questionId'] : null;
    }

    /**
     * Helper method to create a comment view.
     *
     * Creates a comment view for comment.
     *
     * @param  string $title    the title for the comment.
     * @param  int $questionId  the id of the related question.
     * @param  string $comment  the comment text.
     *
     * @return void
     */
    private function createCommentView($title, $questionId, $comment)
    {
        $this->views->add('comment/comment', [
            'title'         => $title,
            'questionId'    => $questionId,
            'comment'       => $comment,
        ], 'main-wide');
    }

    /**
     * Increases the votes counter at an up vote action.
     *
     * Redirects to the comment vote controller to increase the vote counter
     * with one when a user push the up vote arrow for a comment.
     *
     * @param  int $commentId   the id of the comment.
     *
     * @return void
     */
    public function upVoteAction($commentId)
    {
        $this->dispatcher->forward([
            'controller' => 'comment-votes',
            'action'     => 'increase',
            'params'     => [$commentId]
        ]);
    }

    /**
     * Decreases the votes counter at a down vote action.
     *
     * Redirects to the comment vote controller to decrease the vote counter
     * with one when a user push the down vote arrow for a comment.
     *
     * @param  int $commentId   the id of the comment.
     *
     * @return void
     */
    public function downVoteAction($commentId)
    {
        $this->dispatcher->forward([
            'controller' => 'comment-votes',
            'action'     => 'decrease',
            'params'     => [$commentId]
        ]);
    }
}
