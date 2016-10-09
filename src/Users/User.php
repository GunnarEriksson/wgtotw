<?php
namespace Anax\Users;

/**
 * Model for User.
 *
 * Handles user data in the tabel user
 * in the database.
 */
class User extends \Anax\MVC\CDatabaseModel
{
    /**
     * Find acronym
     *
     * Searches after an acronym in DB.
     *
     * @return this
     */
    public function findAcronym($acronym)
    {
        $this->db->select()
            ->from($this->getSource())
            ->where("acronym = ?");

        $this->db->execute([$acronym]);

        return $this->db->fetchInto($this);
    }
}
