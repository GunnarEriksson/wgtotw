<?php

namespace Anax\Answers;

class AnswersController implements \Anax\DI\IInjectionAware
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

        $this->answers = new \Anax\Answers\Answer();
        $this->answers->setDI($this->di);

        $this->questions = new \Anax\Questions\Question();
        $this->questions->setDI($this->di);
    }

    public function listAction($questionId, $orderBy)
    {
        $allAnswers = $this->listAllAnswersForOneQuestion($questionId, $orderBy);

        $this->views->add('answer/answers', [
            'numOfAnswers'  => count($allAnswers),
            'answers'       => $allAnswers,
        ], 'main-wide');
    }

    private function listAllAnswersForOneQuestion($questionId, $orderBy)
    {
        $answers = $this->answers->query('Lf_Answer.*, U.acronym, U.gravatar')
            ->join('Question2Answer AS Q2A', 'Q2A.idAnswer = Lf_Answer.id')
            ->join('Question AS Q', 'Q2A.idQuestion = Q.id')
            ->join('User2Answer AS U2A', 'Q.id = U2A.idAnswer')
            ->join('User AS U', 'U2A.idUser = U.id')
            ->orderBy($orderBy)
            ->where('Q.id = ?')
            ->execute([$questionId]);

        return $answers;
    }

    public function addAction($questionId)
    {
        if ($this->di->session->has('user')) {
            $this->addAnswer($questionId);
        } else {
            $this->pageNotFound();
        }
    }

    private function addAnswer($questionId)
    {
        $user = $this->di->session->get('user', []);
        $form = new \Anax\HTMLForm\Answers\CFormAddAnswer($questionId, $user);
        $form->setDI($this->di);
        $status = $form->check();

        $questionTitle = $this->getQuestionTitleFromId($questionId);

        $this->di->theme->setTitle("Re: " . $questionTitle);
        $this->di->views->add('answer/answerForm', [
            'title' => "Re: " . $questionTitle,
            'content' => $form->getHTML(),
        ], 'main');
    }

    private function getQuestionTitleFromId($questionId)
    {
        $question = $this->questions->find($questionId);
        $title = ($question === false) ? "" : $question->title ;

        return $title;
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
}
