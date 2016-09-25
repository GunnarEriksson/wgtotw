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
        $this->session();

        $this->questionToTag = new \Anax\QuestionToTag\Question2Tag();
        $this->questionToTag->setDI($this->di);

        $this->tag = new \Anax\Tags\Tag();
        $this->tag->setDI($this->di);

        $this->tagIDs = $this->createTagToIdArray();
    }

    public function addAction($questionId, $checkedTags)
    {
        if ($this->isAllowedToHandleTags($questionId)) {
            if ($this->addTags($questionId, $checkedTags) === false) {
                $warningMessage = "Frågan kunde kopplas ihop med taggar i DB!";
                $this->flash->warningMessage($warningMessage);
            }
        } else {
            $this->pageNotFound();
        }
    }

    private function isAllowedToHandleTags($id)
    {
        $isAllowed = false;

        if ($this->LoggedIn->isLoggedin()) {
            $isAllowed = $this->isIdLastInserted($id);
        }

        return $isAllowed;
    }

    private function isIdLastInserted($id)
    {
        $isAllowed = false;

        $lastInsertedId = $this->session->get('lastInsertedId');
        if (!empty($lastInsertedId)) {
            if ($lastInsertedId === $id) {
                $isAllowed = true;
            }
        }

        return $isAllowed;
    }

    private function addTags($questionId, $checkedTags)
    {
        if ($checkedTags === false) {
            $isAdded = $this->addDefaultTagToQuestion($questionId);
        } else {
            $isAdded = $this->addTagsToQuestion($questionId, $checkedTags);
        }

        return $isAdded;
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
        foreach ($checkedTags as $tag) {
            $tagId = $this->tagIDs[$tag];
            if ($this->addTagToQuestion($questionId, $tagId)) {
                $this->increaseQuestionConnectionCounter($tagId);
            } else {
                $isAdded = false;
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

    public function updateAction($questionId, $newTags, $oldTags)
    {
        if ($this->isAllowedToHandleTags($questionId)) {
            $this->updateTagsForQuestion($questionId, $newTags, $oldTags);
        } else {
            $this->pageNotFound();
        }

        if ($this->session->has('lastInsertedId')) {
            unset($_SESSION["lastInsertedId"]);
        }
    }

    private function updateTagsForQuestion($questionId, $newTags, $oldTags)
    {
        $tagsToRemove = array_diff($oldTags, $newTags);
        if ($this->removeTagsFromQuestion($questionId, $tagsToRemove) === false) {
            $warningMessage = "Gamla taggar för frågan kunde inte tas bort i DB!";
            $this->flash->warningMessage($warningMessage);
        }

        $tagsToAdd = array_diff($newTags, $oldTags);
        if ($this->addTagsToQuestion($questionId, $tagsToAdd) === false) {
            $warningMessage = "Nya taggar kunde inte läggas till frågan i DB!";
            $this->flash->warningMessage($warningMessage);
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
