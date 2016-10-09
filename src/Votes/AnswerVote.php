<?php
namespace Anax\Votes;

/**
 * Model for Answer Vote
 *
 * Handles the mapping between user and the related answers
 * in the database. Used to prevent that a user votes for an
 * answer more than once.
 */
class AnswerVote extends \Anax\MVC\CDatabaseModel
{

}
