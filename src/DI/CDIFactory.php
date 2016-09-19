<?php

namespace Anax\DI;

/**
 * Anax extended class implementing Dependency Injection / Service Locator
 * of the services used by the framework, using lazy loading.
 */
class CDIFactory extends CDIFactoryDefault
{
    public function __construct()
    {
        parent::__construct();

        $this->set('form', '\Mos\HTMLForm\CForm');

        $this->set('CommentsController', function () {
            $controller = new \Anax\Comments\CommentsController();
            $controller->setDI($this);
            return $controller;
        });

        $this->setShared('db', function () {
            $db = new \Mos\Database\CDatabaseBasic();
            $db->setOptions(require ANAX_APP_PATH . 'config/database_mysql.php');
            $db->connect();
            return $db;
        });

        $this->set('UsersController', function () {
            $controller = new \Anax\Users\UsersController();
            $controller->setDI($this);
            return $controller;
        });

        $this->set('UserLoginController', function () {
            $controller = new \Anax\Users\UserLoginController();
            $controller->setDI($this);
            return $controller;
        });

        $this->set('QuestionsController', function () {
            $controller = new \Anax\Questions\QuestionsController();
            $controller->setDI($this);
            return $controller;
        });

        $this->set('TagsController', function () {
            $controller = new \Anax\Tags\TagsController();
            $controller->setDI($this);
            return $controller;
        });

        $this->set('QuestionTagController', function () {
            $controller = new \Anax\QuestionToTag\QuestionTagController();
            $controller->setDI($this);
            return $controller;
        });

        $this->set('AnswersController', function () {
            $controller = new \Anax\Answers\AnswersController();
            $controller->setDI($this);
            return $controller;
        });

        $this->set('UserQuestionController', function () {
            $controller = new \Anax\UserToQuestion\UserQuestionController();
            $controller->setDI($this);
            return $controller;
        });

        $this->set('UserAnswerController', function () {
            $controller = new \Anax\UserToAnswer\UserAnswerController();
            $controller->setDI($this);
            return $controller;
        });

        $this->set('UserCommentController', function () {
            $controller = new \Anax\UserToComment\UserCommentController();
            $controller->setDI($this);
            return $controller;
        });

        $this->set('QuestionAnswerController', function () {
            $controller = new \Anax\QuestionToAnswer\QuestionAnswerController();
            $controller->setDI($this);
            return $controller;
        });

        $this->set('QuestionCommentController', function () {
            $controller = new \Anax\QuestionToComment\QuestionCommentController();
            $controller->setDI($this);
            return $controller;
        });

        $this->set('AnswerCommentController', function () {
            $controller = new \Anax\AnswerToComment\AnswerCommentController();
            $controller->setDI($this);
            return $controller;
        });

        $this->set('QuestionVotesController', function () {
            $controller = new \Anax\Votes\QuestionVotesController();
            $controller->setDI($this);
            return $controller;
        });

        $this->set('AnswerVotesController', function () {
            $controller = new \Anax\Votes\AnswerVotesController();
            $controller->setDI($this);
            return $controller;
        });

        $this->set('CommentVotesController', function () {
            $controller = new \Anax\Votes\CommentVotesController();
            $controller->setDI($this);
            return $controller;
        });

        $this->set('ErrorsController', function () {
            $controller = new \Anax\Errors\ErrorsController();
            $controller->setDI($this);
            return $controller;
        });
    }
}
