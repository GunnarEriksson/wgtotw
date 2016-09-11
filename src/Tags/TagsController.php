<?php
namespace Anax\Tags;

/**
 * A controller for tag related events.
 *
 */
class TagsController implements \Anax\DI\IInjectionAware
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

        $this->tags = new \Anax\Tags\Tag();
        $this->tags->setDI($this->di);
    }

    /**
     * List all tags.
     *
     * @return void
     */
    public function listAction()
    {
        $allTags = $this->tags->findAll();

        $this->di->theme->setTitle("Alla taggar");
        $this->views->add('tag/tags', [
            'title' => "Alla Taggar",
            'tags'  => $allTags,
        ], 'main-wide');
    }

    /**
     * Increase counter
     *
     * Increases the number of connected questions counter
     */
    public function increaseCounterAction($tagId)
    {
        $numberOfQuestions = $this->getNumberOfQuestionConnections($tagId);

        if (!empty($numberOfQuestions)) {
            $numQuestions = $numberOfQuestions[0]->numQuestions;
            $this->saveNumOfQuestionConnections($tagId, ++$numQuestions);
        }
    }

    private function getNumberOfQuestionConnections($tagId)
    {
        $numQuestions = $this->tags->query('numQuestions')
            ->where('id = ?')
            ->execute([$tagId]);

        return $numQuestions;
    }

    private function saveNumOfQuestionConnections($tagId, $numQuestions)
    {
        $isSaved = $this->tags->save(array(
            'id'            => $tagId,
            'numQuestions'  => $numQuestions,
        ));

        return $isSaved;
    }
}
