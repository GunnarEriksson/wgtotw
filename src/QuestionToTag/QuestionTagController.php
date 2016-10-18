<?php

namespace Anax\QuestionToTag;

/**
 * Question Tag controller
 *
 * Communicates with the mapping table, which maps questions with the related
 * tags in the database.
 * Handles all mapping tasks between question and the related tags.
 */
class QuestionTagController implements \Anax\DI\IInjectionAware
{
    use \Anax\DI\TInjectable;

    private $tagIDs = [];
    private $resultMessage = "";

    /**
     * Initialize the controller.
     *
     * Initializes the session, the question to
     * comment model and the tag model.
     *
     * Creates a tag label to id array of all possible question tags.
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

    /**
     * Helper method to create an array with all question related tags labels
     * and the related IDs.
     *
     * @return int[] the tag IDs with the name of the tags as the key.
     */
    private function createTagToIdArray()
    {
        $labelToIdArray = [];

        $allTags = $this->tag->findAll();
        foreach ($allTags as $tag) {
            $labelToIdArray[$tag->label] = $tag->id;
        }

        return $labelToIdArray;
    }

    /**
     * Adds a connection between a question and a checked (ticked) tag.
     *
     * Adds a connection between a question and a checked (ticked) tag if the
     * question id and tag id is present, otherwise it creates a flash error
     * message.
     *
     * @param int $questionId       the question id to be mapped to a comment id.
     * @param string[] | false $checkedTags the related tags for a question, if
     *                         present.
     *
     * @return void
     */
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

    /**
     * Helper method to check if it is allowed to add a tag to a question.
     *
     * Checks if the user has logged in and the call is from a redirect and not
     * via the browsers addess field.
     *
     * @param  int $id the id of the tag to be connected to a question.
     *
     * @return boolean  true if it is allowe to connect a tag to a question,
     *                       false otherwise.
     */
    private function isAllowedToHandleTags($id)
    {
        $isAllowed = false;

        if ($this->LoggedIn->isLoggedin()) {
            $isAllowed = $this->isIdLastInserted($id);
        }

        return $isAllowed;
    }

    /**
     * Helper method to check if the tag id is the last inserted id.
     *
     * Checks if the call is from a controller and not via the browsers
     * address field. The controller who redirects saves the checked tag
     * id in the session. If no id is found, the call to the action method
     * must come from elsewhere.
     *
     * @param  int  $id the checked tag id from the last insterted id.
     *
     * @return boolean  true if call is from a redirect, false otherwise.
     */
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

    /**
     * Helper method to add checked tags to a question.
     *
     * Checks if checked tags is present and maps the tags to the related
     * question. If not present, a default tag is mapped to the question.
     *
     * @param int $questionId   the question id to map the tags to.
     * @param string[] | false $checkedTags the checked tags, if present.
     */
    private function addTags($questionId, $checkedTags)
    {
        if ($checkedTags === false) {
            $isAdded = $this->addDefaultTagToQuestion($questionId);
        } else {
            $isAdded = $this->addTagsToQuestion($questionId, $checkedTags);
        }

        return $isAdded;
    }

    /**
     * Helper method to add a default tag to the question.
     *
     * Adds the last tag in the array (default tag) to the question.
     *
     * @param int $questionId   the question id.
     */
    private function addDefaultTagToQuestion($questionId)
    {
        $tagId = end($this->tagIDs);

        return $this->addTagToQuestion($questionId, $tagId);
    }

    /**
     * Helper method to add checked (ticked) tags to the question.
     *
     * Checks all checked tags to the question and increases the counter that
     * counts number of tag connections.
     *
     * @param int $questionId   the question id.
     * @param string[] $checkedTags the checked tag names.
     *
     * @return boolean true if tags are mapped to the question, false otherwise.
     */
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

    /**
     * Helper method to add a tag to the question.
     *
     * Maps a tag to the question in DB.
     *
     * @param int $questionId   the id of the question to map a tag to.
     * @param int $tagId        the id of the tag to map to the question.
     *
     * @return boolean  true if saved, false otherwise.
     */
    private function addTagToQuestion($questionId, $tagId)
    {
        $isSaved = $this->questionToTag->create(array(
            'idQuestion'    => intval($questionId),
            'idTag'         => $tagId,
        ));

        return $isSaved;
    }

    /**
     * Helper method to increase the number of tags counter.
     *
     * Redirects to the Tags controller to increase the number of tags counter
     * for the specific tag. The tag counter shows how many questions are
     * related to the specific tag.
     *
     * @param  int $tagId   the tag id.
     *
     * @return void.
     */
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

    /**
     * Updates the tags connected to the question.
     *
     * Checks if it is allowed to update the tags connected to the question and
     * updates the tags. If not allowed, a page not found are shown because
     * the action method has been directly accessed from the browsers address
     * bar.
     *
     * Removes the last inserted id from the session, if set. The id is used to
     * prevent direct access from the browsers address bar.
     *
     * @param  int $questionId      the question id.
     * @param  string[] $newTags    added tags to the question.
     * @param  string[] $oldTags    removed tags from the question.
     *
     * @return void.
     */
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

    /**
     * Helper method to update tags to the question.
     *
     * Removes old tags and add the new ones. If old tags could not be removed
     * or new ones be added, a flash error message is shown.
     *
     * @param  int $questionId      the question id.
     * @param  string[] $newTags    added tags to the question.
     * @param  string[] $oldTags    removed tags from the question.
     *
     * @return void.
     */
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

    /**
     * Helper method to remove old tags from a question.
     *
     * Removes old tags from a question and decreases the tag counter for the
     * specific tag.
     *
     * @param  int $questionId          the question id.
     * @param  string[] $tagsToRemove   tags to remove from the question.
     *
     * @return boolean  true if old tags are removed, false otherwise.
     */
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

    /**
     * Helper method to delete the row for the mapping of the question and the
     * tag.
     *
     * Removes the row in the table (DB) where the question and tag is mapped.
     *
     * @param  int $questionId  the question id.
     * @param  int $tagId       the tag id.
     *
     * @return boolean  true if the mapping could be removed in DB, falses otherwise.
     */
    private function removeTagFromQuestion($questionId, $tagId)
    {
        return $this->questionToTag->deleteCombined($questionId, $tagId);
    }

    /**
     * Helper method to decrease the number of question connections for a tag.
     *
     * Redirects to the Tags controller to decrease the number of question
     * connections for a tag with one.
     *
     * @param  int $tagId   the tag id.
     *
     * @return void.
     */
    private function decreaseQuestionConnectionCounter($tagId)
    {
        $this->dispatcher->forward([
            'controller' => 'tags',
            'action'     => 'decreaseCounter',
            'params'     => [$tagId]
        ]);
    }
}
