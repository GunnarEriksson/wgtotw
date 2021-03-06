<?php
namespace Anax\QuestionToTag;

/**
 * Model for Question to Tag.
 *
 * Handles the mapping between question and the related tags
 * in the database.
 */
class Question2Tag extends \Anax\MVC\CDatabaseModel
{
    /**
     * Delete row.
     *
     * @param integer $id to delete.
     *
     * @return boolean true or false if deleting went okey.
     */
    public function deleteCombined($idQuestion, $idTag)
    {
        $this->db->delete(
            $this->getSource(),
            'idQuestion = ? AND idTag = ?'
        );

        return $this->db->execute([$idQuestion, $idTag]);
    }
}
