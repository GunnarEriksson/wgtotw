<?php

namespace Anax\Answers;

/**
 * Answers controller
 *
 * Communicates with the answer and question table in the database.
 * Handles all answer releated tasks and present the results to views.
 */
class AnswersController implements \Anax\DI\IInjectionAware
{
    use \Anax\DI\TInjectable;

    const ACTIVITY_SCORE_ACCEPT = 3;

    /**
     * Initialize the controller.
     *
     * Initializes the session, the answer and
     * question models.
     *
     * @return void
     */
    public function initialize()
    {
        $this->session();

        $this->answers = new \Anax\Answers\Answer();
        $this->answers->setDI($this->di);

        $this->questions = new \Anax\Questions\Question();
        $this->questions->setDI($this->di);
    }

    /**
     * Lists all anwers connected to one question.
     *
     * Lists all answers, with included comments, for one question.
     * Creates a heading to be able to sort the order of the questions
     * according to number of votes or time when the answer was created.
     *
     * @param  int $questionId  the question id, which the answers are releated to.
     * @param  string $orderBy  the order the answer should be sorted.
     *
     * @return void
     */
    public function listAction($questionId, $orderBy)
    {
        $allAnswers = $this->listAllAnswersForOneQuestion($questionId, $orderBy);
        if (!empty($allAnswers)) {

            $this->createAnswerHeading($questionId, count($allAnswers), $orderBy);

            foreach ($allAnswers as $answer) {
                $questionUserId = $this->getUserIdForParentQuestion($answer->id);
                $comments = $this->getAllCommentsForSpecificAnswer($answer->id);
                $this->createAnswerView($answer, $comments, $questionUserId);
            }
        }
    }

    /**
     * Helper method to list all answers for one question.
     *
     * Contacts the DB to get all answers related to the question. The information
     * about the user id, the users acronym and gravatar are included from DB.
     *
     * @param  int $questionId  the question id, which the answers are related to.
     * @param  string $orderBy  the order the answer should be sorted.
     *
     * @return object[]         all answers with user id, user acronym and user
     *                          gravatar connected to the author of the answer.
     */
    private function listAllAnswersForOneQuestion($questionId, $orderBy)
    {
        $answers = $this->answers->query('lf_answer.*, U.id AS answerUserId, U.acronym, U.gravatar')
            ->join('question2answer AS Q2A', 'Q2A.idAnswer = lf_answer.id')
            ->join('question AS Q', 'Q2A.idQuestion = Q.id')
            ->join('user2answer AS U2A', 'lf_answer.id = U2A.idAnswer')
            ->join('user AS U', 'U2A.idUser = U.id')
            ->orderBy($orderBy)
            ->where('Q.id = ?')
            ->execute([$questionId]);

        return $answers;
    }

    /**
     * Helper method to get user id of the author of the question.
     *
     * Contacts DB to get the questions author's user id. Uses the answer id to
     * get the relationship between the answer and question.
     *
     * @param  int $answerId    the id of the answer, which are releated to a question.
     *
     * @return int              the user id for the author of the question.
     */
    private function getUserIdForParentQuestion($answerId)
    {
        $questionUserId = $this->answers->query('U.id')
            ->join('question2answer AS Q2A', 'Q2A.idAnswer = lf_answer.id')
            ->join('question AS Q', 'Q2A.idQuestion = Q.id')
            ->join('user2question AS U2Q', 'U2Q.idQuestion = Q.id')
            ->join('user AS U', 'U2Q.idUser = U.id')
            ->where('lf_answer.id = ?')
            ->execute([$answerId]);

        return isset($questionUserId[0]->id) ? $questionUserId[0]->id : false;
    }

    /**
     * Helper method to create an answer heading.
     *
     * Creates a heading which contains the number of the answers and a tabs, which
     * can be used to sort the answers according to number of votes or the time
     * the answers where created.
     *
     * @param  int $questionId      the id number of the question
     * @param  int $numOfAnswers    the number of the answers for the question.
     * @param  string $orderBy      the order the answers should be sorted.
     *
     * @return void
     */
    private function createAnswerHeading($questionId, $numOfAnswers, $orderBy)
    {
        $latest = strcmp($orderBy, 'created desc') === 0 ? 'latest' : null;

        $this->views->add('answer/heading', [
            'questionId'    => $questionId,
            'numOfAnswers'  => $numOfAnswers,
            'latest'        => $latest,
        ], 'main-wide');
    }

    /**
     * Helper method to get all comments for a specific answer.
     *
     * Gets all comments from DB for a specific answer.
     * Includes the user id, and acronym of the author who wrote the comment.
     *
     * @param  int $answerId    the answer id, which the comments are related to.
     *
     * @return object[]         all comments releated to an anwser. User id and
     *                          acronym of the comment author are included.
     */
    private function getAllCommentsForSpecificAnswer($answerId)
    {
        $comments = $this->answers->query('C.*, U.id AS userId, U.acronym')
            ->join('answer2comment AS A2C', 'A2C.idAnswer = lf_answer.id')
            ->join('comment AS C', 'A2C.idComment = C.id')
            ->join('user2comment AS U2C', 'C.id = U2C.idComment')
            ->join('user AS U', 'U2C.idUser = U.id')
            ->where('lf_answer.id = ?')
            ->orderBy('C.created asc')
            ->execute([$answerId]);

        return $comments;
    }

    /**
     * Helper method to create the view with all anwers releated to a question.
     *
     * Creates an answer view with all answers and included answer comments.
     *
     * @param  object[] $answer     Answer releated to a question.
     * @param  object[] $comments   All comments releated to an anwser.
     * @param  int $questionUserId  the id of the question, which the answer is
     *                              related to.
     * @return void
     */
    private function createAnswerView($answer, $comments, $questionUserId)
    {
        $this->views->add('answer/answer', [
            'answer'            => $answer,
            'comments'          => $comments,
            'questionUserId'    => $questionUserId
        ], 'main-wide');
    }

    /**
     * Adds an answer to a question.
     *
     * Checks if the user has logged in to be able to add an answer. If not, the
     * user is redirected to the log in page.
     *
     * @param int $questionId the id of the question to add an answer to.
     *
     * @return void
     */
    public function addAction($questionId = null)
    {
        if ($this->LoggedIn->isLoggedin()) {
            $this->addAnswer($questionId);
        } else {
            $this->redirectToLoginPage();
        }
    }

    /**
     * Helper method to add answer to a question.
     *
     * Creates an form to add an answer to a question, if question id and user id
     * could be found. If not, it redirects to an error message.
     *
     * @param int $questionId the question id to add an answer to.
     *
     * @return void
     */
    private function addAnswer($questionId)
    {
        $userId = $this->LoggedIn->getUserId();
        if (isset($questionId) && $userId) {
            $this->createAddAnswerForm($questionId, $userId);
        } else {
            if (!isset($questionId)) {
                $subtitle = "Frågans id-nummer saknas";
                $message = "Frågans id-nummer saknas. Kan inte skicka vidare till nästa sida!";
            } else {
                $subtitle = "Användare-id saknas";
                $message = "Användare-id saknas. Kan inte koppla svar till användare!";
            }

            $this->showErrorMessage($subtitle, $message);
        }

    }

    /**
     * Helper method to create an answer form.
     *
     * Creates an answer form and its view. Uses the title of the question for
     * the heading of the form.
     *
     * @param  int $questionId  the id of the question to add an answer to.
     * @param  id $userId       the use id of user who has logged in.
     *
     * @return void
     */
    private function createAddAnswerForm($questionId, $userId)
    {
        $form = new \Anax\HTMLForm\Answers\CFormAddAnswer($questionId, $userId);
        $form->setDI($this->di);
        $status = $form->check();

        $questionTitle = $this->getQuestionTitleFromId($questionId);

        $this->theme->setTitle("Re: " . $questionTitle);
        $this->views->add('answer/answerForm', [
            'title' => "Re: " . $questionTitle,
            'content' => $form->getHTML(),
        ], 'main');
    }

    /**
     * Helper method to get the question title of question.
     *
     * Gets the title of the question from the question id.
     *
     * @param  int $questionId  the id of the question.
     *
     * @return string | false   if found the title of the question, false otherwise.
     */
    private function getQuestionTitleFromId($questionId)
    {
        $question = $this->questions->find($questionId);
        $title = ($question === false) ? "" : $question->title ;

        return $title;
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
     * Helper method to redirect to the log in page.
     *
     * Redirects to the log in page.
     *
     * @return void
     */
    private function redirectToLoginPage()
    {
        $this->dispatcher->forward([
            'controller' => 'user-login',
            'action'     => 'login',
        ]);
    }

    /**
     * Adds a comment to an answer.
     *
     * Redirects answer id, question id, the first 30 letters of the answer and
     * a flag that the comment is releated to an anwser to the comment controller.
     * The comment controller will create an form to add a comment.
     *
     * @param int $answerId     the id of the answer to create a comment to.
     *
     * @return void
     */
    public function addCommentAction($answerId)
    {
        $content = $this->getAnswerContentFromId($answerId);
        $content = $this->getSubstring($content, 30);
        $questionInfo = $this->getQuestionInfoForAnswer($answerId);
        $questionId = isset($questionInfo->questionId) ? $questionInfo->questionId : null;

        $this->dispatcher->forward([
            'controller' => 'comments',
            'action'     => 'add',
            'params'     => [$answerId, $questionId, $content, 'answer-comment']
        ]);
    }

    /**
     * Helper method to get the answer content.
     *
     * Gets the answer content for a specific answer.
     *
     * @param  int $answerId the id of the answer.
     *
     * @return string | false   the answer content if found, false otherwise.
     */
    private function getAnswerContentFromId($answerId)
    {
        $content = $this->answers->query('content')
            ->where('id = ?')
            ->execute([$answerId]);

        return $content === false ? "" : $content[0]->content;
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
     * Updates an answer.
     *
     * Updates the answer if the user is allowed to update. To be able to update,
     * the user must be admin or the author of the answer.
     *
     * @param  int $answerId    the id of the answer. Default null.
     *
     * @return void
     */
    public function updateAction($answerId = null)
    {
        if ($this->isUpdateAllowed($answerId)) {
            $this->updateAnswer($answerId);
        } else {
            $this->handleUpdateIsNotAllowed($answerId);
        }
    }

    /**
     * Helper method to check if the user is allowed to update the answer.
     *
     * Checks if the user has logged in and the user is admin or the author of
     * the answer.
     *
     * @param  int $answerId   the id of the answer.
     *
     * @return boolean          true if allowed to update, false otherwise.
     */
    private function isUpdateAllowed($answerId)
    {
        $isUpdateAllowed = false;

        if (isset($answerId)) {
            if ($this->LoggedIn->isLoggedin()) {
                $authorId = $this->getAnswerAuthorId($answerId);
                $isUpdateAllowed = $this->LoggedIn->isAllowed($authorId);
            }
        }

        return $isUpdateAllowed;
    }

    /**
     * Helper method to get the id of the user who wrote the answer.
     *
     * Gets the user id of the user who wrote the answer from DB.
     *
     * @param  int $answerId    the id of the answer.
     *
     * @return int | false  the id of the user who wrote the answer, false
     *                      otherwise.
     */
    private function getAnswerAuthorId($answerId)
    {
        $authorId = $this->answers->query('U.id')
            ->join('user2answer AS U2A', 'U2A.idAnswer = lf_answer.id')
            ->join('user AS U', 'U2A.idUser = U.id')
            ->where('lf_answer.id = ?')
            ->execute([$answerId]);

        $authorId = empty($authorId) ? false : $authorId[0]->id;

        return $authorId;
    }

    /**
     * Helper method to update the answer.
     *
     * Gets the question and anwer info from DB to create an form for updating
     * the answer. If information is missing, an error message is shown.
     *
     * @param  int $answerId    the id of the answer to update.
     *
     * @return void
     */
    private function updateAnswer($answerId)
    {
        $questionInfo = $this->getQuestionInfoFromAnswerId($answerId);
        $questionId = isset($questionInfo->id) ? $questionInfo->id : false;
        $questionTitle = isset($questionInfo->title) ? $questionInfo->title : false;

        if ($questionId && $questionTitle) {
            $answerData = $this->answers->find($answerId);
            $answerData = $answerData === false ? $answerData : $answerData->getProperties();
            if ($answerData) {
                $this->createUpdateAnswerForm($answerData, $questionId, $questionTitle);
            } else {
                $subtitle = "Svars data saknas";
                $message = "Svars data saknas. Kan inte koppla svar till fråga!";
                $this->showErrorMessage($subtitle, $message);
            }
        } else {
            $subtitle = "Fråge-id saknas";
            $message = "Fråge-id saknas. Kan inte koppla svar till fråga!";
            $this->showErrorMessage($subtitle, $message);
        }
    }

    /**
     * Helper method to create an update answer form and send it to a view.
     *
     * Creates an update answer form and sends it to a view.
     *
     * @param  mixed[] $answerData      All answer information.
     * @param  int $questionId          the question id the answer is related to.
     * @param  string $questionTitle    the title of the question the answer is
     *                                  related to.
     *
     * @return void
     */
    private function createUpdateAnswerForm($answerData, $questionId, $questionTitle)
    {
        $form = new \Anax\HTMLForm\Answers\CFormUpdateAnswer($answerData, $questionId);
        $form->setDI($this->di);
        $status = $form->check();

        $this->theme->setTitle("Re: " . $questionTitle);
        $this->views->add('answer/answerForm', [
            'title' => "Re: " . $questionTitle,
            'content' => $form->getHTML(),
        ], 'main');
    }

    /**
     * Helper method to handle update is not allowed.
     *
     * Handles the action when an update is not allowed. Creates an error message
     * and redirects the user.
     *
     * @param  int $answerId    the id of the answer.
     *
     * @return void
     */
    private function handleUpdateIsNotAllowed($answerId)
    {
        if (!isset($answerId)) {
            $subtitle = "Svars-id saknas";
            $message = "Svars-id saknas. Kan inte koppla svar till fråga!";
            $this->showErrorMessage($subtitle, $message);
        } else if ($this->LoggedIn->isLoggedin()) {
            $questionInfo = $this->getQuestionInfoFromAnswerId($answerId);
            $questionId = isset($questionInfo->id) ? $questionInfo->id : false;
            $noticeMessage = "Endast egna svar kan uppdateras!";
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
     * Helper method to get question information.
     *
     * Gets question information from DB for the question, which is related to
     * an answer.
     *
     * @param  int $answerId    the id of the related question.
     *
     * @return object[] | false the question id and title if found, false otherwise.
     */
    private function getQuestionInfoFromAnswerId($answerId)
    {
        $questionInfo = $this->answers->query('Q.id, Q.title')
            ->join('question2answer AS Q2A', 'Q2A.idAnswer = lf_answer.id')
            ->join('question AS Q', 'Q2A.idQuestion = Q.id')
            ->where('lf_answer.id = ?')
            ->execute([$answerId]);

        $questionInfo = empty($questionInfo) ? false : $questionInfo[0];

        return $questionInfo;
    }

    /**
     * Helper method to redirect to question controller to show a specific
     * question.
     *
     * Redirects to the question controller to show the question with all the
     * answers and comments.
     *
     * @param  int $questionId  the id of the question to show.
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
     * Helper method to redirect to question controller to show all questions.
     *
     * Redirects to the question controller to show all questions.
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
     * Increases the vote counter with one.
     *
     * Redirects to the Vote controller to increase the vote counter, for the
     * answer, with one.
     *
     * @param  int $answerId the answer id, which vote counter should be increased
     *                       with one.
     *
     * @return void.
     */
    public function upVoteAction($answerId)
    {
        $this->dispatcher->forward([
            'controller' => 'answer-votes',
            'action'     => 'increase',
            'params'     => [$answerId]
        ]);
    }

    /**
     * Decreases the vote counter with one.
     *
     * Redirects to the Vote controller to decrease the vote counter, for the
     * answer, with one.
     *
     * @param  int $answerId the answer id, which vote counter should be decreased
     *                       with one.
     *
     * @return void.
     */
    public function downVoteAction($answerId)
    {
        $this->dispatcher->forward([
            'controller' => 'answer-votes',
            'action'     => 'decrease',
            'params'     => [$answerId]
        ]);
    }

    /**
     * Sets the accepted sign for an answer.
     *
     * Sets an accepted sign for an answer if the user is allowed to set the
     * sign. To be allowed to set the sign, the user must be logged in and be
     * either the admin or the author of the related question.
     *
     * If not allowed a flash message is set.
     *
     * Redirects to the question controller to show the question and the
     * related answers and comments.
     *
     * @param  int $answerId the id of the answer.
     *
     * @return void
     */
    public function acceptAction($answerId)
    {
        $questionInfo = $this->getQuestionInfoForAnswer($answerId);
        if ($this->LoggedIn->isLoggedin()) {
            if ($this->isUserAllowedToAccept($questionInfo->userId)) {
                $this->updateAccept($answerId, $questionInfo);
            } else {
                $noticeMessage = "Svar kan endast accepteras för egna frågor!";
                $this->flash->noticeMessage($noticeMessage);
            }
        } else {
            $noticeMessage = "Du måste vara inloggad för att kunna acceptera svar på egna frågor!";
            $this->flash->noticeMessage($noticeMessage);
        }

        $this->redirectToQuestion($questionInfo->questionId);
    }

    /**
     * Helper method to get question info releated to the answer.
     *
     * Gets the related question info from DB.
     *
     * @param  int $answerId    the ansewer id of the related answer.
     *
     * @return object[]         the question id and the id of the question author
     *                          for the related question.
     */
    private function getQuestionInfoForAnswer($answerId)
    {
        $questionInfo = $this->answers->query('Q.id AS questionId, U.id AS userId')
            ->join('question2answer AS Q2A', 'Q2A.idAnswer = lf_answer.id')
            ->join('question AS Q', 'Q2A.idQuestion = Q.id')
            ->join('user2question AS U2Q', 'Q.id = U2Q.idQuestion')
            ->join('user AS U', 'U2Q.idUser = U.id')
            ->where('lf_answer.id = ?')
            ->execute([$answerId]);

        $questionInfo = empty($questionInfo) ? false : $questionInfo[0];

        return $questionInfo;
    }

    /**
     * Helper method to check if a user could accept an answer.
     *
     * Checks if an user could accepts an answer. Only users who has logged in
     * as admin or the author of the related question could accept an answer.
     *
     * @param  int      the user id of the author who wrote the question.
     *
     * @return boolean true if user is allowed to accept, false otherwise.
     */
    private function isUserAllowedToAccept($userId)
    {
        $isAllowedToAccept = false;
        $userIdInSession = $this->LoggedIn->getUserId();
        if ($userId === $userIdInSession) {
            $isAllowedToAccept = true;
        }

        return $isAllowedToAccept;
    }

    /**
     * Helper method to update an answer accept.
     *
     * Gets the id for an accepted answer. If no accepted answer is found, it
     * sets answer with the answer id to accepted. Increases the activity score
     * and number of accepts. Uses the parameter lastInsertedId to prevent
     * increasing the counters by calling the action methods directly from the
     * browsers address field.
     *
     * If an answer is already accepted, it removes the accepted sign and sets
     * the accepted sign to the new accepted answer.
     *
     * @param  int $answerId     the answer id of the accepted answer.
     * @param  $questionInfo     the question info of the related question.
     *
     * @return void
     */
    private function updateAccept($answerId, $questionInfo)
    {
        $questionId = $questionInfo->questionId;
        $answerIdAccept = $this->getAcceptedAnswerIdForQuestion($questionId);
        if ($answerIdAccept === false) {
            if ($this->setAnswerToAccepted($answerId)) {
                $this->session->set('lastInsertedId', $answerId);
                $this->addActivityScoreToUser($answerId);

                if ($this->session->has('lastInsertedId')) {
                    unset($_SESSION["lastInsertedId"]);
                }
            }
        } else {
            if ($this->unsetAnswerToAccepted($answerIdAccept)) {
                $this->setAnswerToAccepted($answerId);
            }
        }
    }

    /**
     * Helper method to get the answer id for an accepted answer.
     *
     * Gets the answer id for an accepted answer from DB via the related
     * question id.
     *
     * @param  int $questionId  the id of the related question.
     *
     * @return int | false  the answer id if found, false otherwise.
     */
    private function getAcceptedAnswerIdForQuestion($questionId)
    {
        $answerId = $this->answers->query('lf_answer.id')
            ->join('question2answer AS Q2A', 'Q2A.idAnswer = lf_answer.id')
            ->join('question AS Q', 'Q2A.idQuestion = Q.id')
            ->where('Q.id = ?')
            ->andWhere('lf_answer.accepted=1')
            ->execute([$questionId]);

        $answerId = empty($answerId) ? false : $answerId[0]->id;

        return $answerId;
    }

    /**
     * Helper method to set an answer to accepted.
     *
     * Sets an answer to accepted in DB.
     *
     * @param int $answerId the answer id of the answer to be accepted.
     *
     * @return boolean  true if answer is accepted, false otherwise.
     */
    private function setAnswerToAccepted($answerId)
    {
        $isSaved = $this->answers->save(array(
            'id'        => $answerId,
            'accepted'  => 1,
        ));

        return $isSaved;
    }

    /**
     * Helper method to add activity score to an user.
     *
     * Redirects to the users controller to add the activity score to the user
     * who accepted the answer.
     *
     * Uses the session to prevent adding score by calling the action method
     * directly from the browser address field.
     *
     * @param int $answerId the answer id of the answer to be accepted.
     *
     * @return void
     */
    private function addActivityScoreToUser($answerId)
    {
        $this->session->set('lastInsertedId', $answerId);

        $this->dispatcher->forward([
            'controller' => 'users',
            'action'     => 'add-score',
            'params'     => [AnswersController::ACTIVITY_SCORE_ACCEPT, $answerId]
        ]);
    }

    /**
     * Helper method to unset an accepted answer.
     *
     * Removes the accepted flag in DB for an accepted answer.
     *
     * @param int $answerIdAccept the id of the accepted answer.
     *
     * @return boolean true if an accepted was removed, false otherwise.
     */
    private function unsetAnswerToAccepted($answerIdAccept)
    {
        $isSaved = $this->answers->save(array(
            'id'        => $answerIdAccept,
            'accepted'  => 0,
        ));

        return $isSaved;
    }

    /**
     * Lists all answers for a user.
     *
     * Lists all answers written by a user, if found. Otherwise sets an flash
     * error message.
     *
     * @param  int $userId  user id of the user who has written the answers.
     *
     * @return void
     */
    public function listUserAnswersAction($userId = null)
    {
        if (isset($userId)) {
            $this->listUserAnswers($userId);
        } else {
            $errorMessage = "Användare id saknas. Kan ej lista svar!";
            $this->flash->errorMessage($errorMessage);
        }
    }

    /**
     * Helper method to list all answers written by the user.
     *
     * Gets all answers for a user and creates a view with a heading.
     *
     * @param  int $userId the user id of the user who has written the answers.
     *
     * @return void
     */
    private function listUserAnswers($userId)
    {
        $allAnswers = $this->getAllAnswersForUser($userId);

        $this->views->add('users/itemHeading', [
            'numOfAnswers'  => count($allAnswers),
            'item'          => "Svar",
            'type'          => "answer",
            'userId'        => $userId,
        ], 'main-wide');

        $this->views->add('answer/userAnswers', [
            'answers'  => $allAnswers,
        ], 'main-wide');
    }

    /**
     * Helper method to get all the answers for a user.
     *
     * Gets all answers for a user in the DB.
     *
     * @param  int $userId the user id of the user who has written the answers.
     *
     * @return object[]     All answers written by a user.
     */
    private function getAllAnswersForUser($userId)
    {
        $answerData = $this->answers->query('lf_answer.*, Q.id AS questionId, Q.title AS questionTitle, U.id AS userId, U.acronym')
            ->join('user2answer AS U2A', 'U2A.idAnswer = lf_answer.id')
            ->join('user AS U', 'U2A.idUser = U.id')
            ->join('question2answer AS Q2A', 'Q2A.idAnswer = lf_answer.id')
            ->join('question AS Q', 'Q2A.idQuestion = Q.id')
            ->where('U.id = ?')
            ->execute([$userId]);

        return $answerData;
    }
}
