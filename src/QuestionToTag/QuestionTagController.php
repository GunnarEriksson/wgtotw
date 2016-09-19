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

        $this->tagIDs = $this->createTagToIdArray();
    }

    public function addAction($questionId, $checkedTags)
    {
        if ($checkedTags === false) {
            $isAdded = $this->addDefaultTagToQuestion($questionId);
        } else {
            $isAdded = $this->addTagsToQuestion($questionId, $checkedTags);
        }
        $isAdded = false;
        if ($isAdded === false) {
            $this->showWarningInfo("Varning! Taggar kunde inte läggas till frågan i databasen!");
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

    private function showWarningInfo($info)
    {
        $content = [
            'title'         => 'Ett fel har uppstått!',
            'subtitle'      => 'Problem med frågetaggar',
            'message'       => $info,
        ];

        $this->dispatcher->forward([
            'controller' => 'errors',
            'action'     => 'flash',
            'params'     => [$content]
        ]);
    }

    public function updateAction($questionId, $newTags, $oldTags)
    {
        $tagsToRemove = array_diff($oldTags, $newTags);
        $isRemoved = $this->removeTagsFromQuestion($questionId, $tagsToRemove);

        $tagsToAdd = array_diff($newTags, $oldTags);
        $isAdded = $this->addTagsToQuestion($questionId, $tagsToAdd);

        if ($isRemoved === false) {
            $this->showWarningInfo("Varning! Alla gamla taggar kunde inte tas bort!");
        }

        if ($isAdded === false) {
            $this->showWarningInfo("Varning! Alla nya taggar kunde inte läggas till!");
        }
    }

    private function removeTagsFromQuestion($questionId, $tagsToRemove)
    {
        $isAllTagsRemoved = true;
        foreach ($tagsToRemove as $tag) {
            $tagId = $this->tagIDs[$tag];
            $result = $this->removeTagFromQuestion($questionId, $tagId);
            if ($result === false) {
                $isAllTagsRemoved = $result;
            } else {
                $this->decreaseQuestionConnectionCounter($tagId);
            }
        }

        return $isAllTagsRemoved;
    }

    private function removeTagFromQuestion($questionId, $tagId)
    {
        return $this->questionToTag->deleteCombined($questionId, $tagId);
    }

    private function decreaseQuestionConnectionCounter($tagId)
    {
        $this->dispatcher->forward([
            'controller' => 'tags',
            'action'     => 'decreaseCounter',
            'params'     => [$tagId]
        ]);
    }
}
