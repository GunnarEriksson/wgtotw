<?php
namespace Anax\QuestionToTag;

class QuestionTagController implements \Anax\DI\IInjectionAware
{
    use \Anax\DI\TInjectable;

    private $tagIDs = [];
    private $resultMessage = "";

    /**
     * Initialize the controller.
     *
     * @return void
     */
    public function initialize()
    {
        $this->di->session();

        $this->questionToTag = new \Anax\QuestionToTag\Question2Tag();
        $this->questionToTag->setDI($this->di);

        $this->tag = new \Anax\Tags\Tag();
        $this->tag->setDI($this->di);
    }

    public function addAction($questionId, $checkedTags, $pointer = null)
    {
        $this->tagIDs = $this->createTagToIdArray();

        if ($checkedTags === false) {
            $isAdded = $this->addDefaultTagToQuestion($questionId);
        } else {
            $isAdded = $this->addTagsToQuestion($questionId, $checkedTags);
        }

        if ($isAdded) {
            $pointer->AddOutput("<p><i>Frågan har sparas i databasen!</i></p>");
        } else {
            $pointer->AddOutput("<p><i>Varning! Frågan har sparats i databasen men problem med hantering av taggar</i></p>");
        }
    }

    private function createTagToIdArray()
    {
        $labelToIdArray = [];

        $allTags = $this->tag->findAll();
        foreach ($allTags as $tag) {
            $labelToIdArray[$tag->label] = $tag->id;
        }

        return $labelToIdArray;
    }

    private function addDefaultTagToQuestion($questionId)
    {
        $tagId = end($this->tagIDs);

        return $this->addTagToQuestion($questionId, $tagId);
    }

    private function addTagsToQuestion($questionId, $checkedTags)
    {
        $isAdded = true;
        foreach ($checkedTags as $key => $tag) {
            $tagId = $this->tagIDs[$tag];
            $result = $this->addTagToQuestion($questionId, $tagId);
            if ($result === false) {
                $isAdded = $result;
            } else {
                $this->increaseQuestionConnectionCounter($tagId);
            }
        }

        return $isAdded;
    }

    private function addTagToQuestion($questionId, $tagId)
    {
        $isSaved = $this->questionToTag->create(array(
            'idQuestion'    => intval($questionId),
            'idTag'         => $tagId,
        ));

        return $isSaved;
    }

    private function increaseQuestionConnectionCounter($tagId)
    {
        $this->dispatcher->forward([
            'controller' => 'tags',
            'action'     => 'increaseCounter',
            'params'     => [$tagId]
        ]);
    }
}
