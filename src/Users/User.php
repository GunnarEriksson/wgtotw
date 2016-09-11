<?php
namespace Anax\Users;

/**
 * Model for Users.
 *
 */
class User extends \Anax\MVC\CDatabaseModel
{
    /**
     * Find acronym and return specific.
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
