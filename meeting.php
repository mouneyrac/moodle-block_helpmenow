<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Help me now meeting, and meeting2user classes.
 *
 * @package     block_helpmenow
 * @copyright   2012 VLACS
 * @author      David Zaharee <dzaharee@vlacs.org>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__) . '/db_object.php');

class helpmenow_meeting extends helpmenow_db_object {
    /**
     * Table of the object.
     * @var string $table
     */
    private $table = 'meeting';

    /**
     * Array of required db fields.
     * @var array $required_fields
     */
    private $required_fields = array(
        'id',
        'timecreated',
        'timemodified',
        'modifiedby',
        'plugin',
    );

    /**
     * Array of optional db fields.
     * @var array $optional_fields
     */
    private $optional_fields = array(
        'owner_userid',
        'description',
    );

    /**
     * Array of relations
     * @var array $relations
     */
    private $relations = array(
        'meeting2user' => 'id',
    );

    /**
     * The userid of the user who owns the meeting, usually the queue helper or
     * the instructor of the course.
     * @var int $helper_userid
     */
    public $owner_userid;

    /**
     * Array of meeting2user objects
     * @var array $users
     */
    public $meeting2user = array();
}

class helpmenow_meeting2user extends helpmenow_db_object {
    /**
     * Table of the object.
     * @var string $table
     */
    private $table = 'meeting2user';

    /**
     * Array of required db fields.
     * @var array $required_fields
     */
    private $required_fields = array(
        'id',
        'timecreated',
        'timemodified',
        'modifiedby',
        'meetingid',
        'userid',
    );

    /**
     * userid
     * @var int $userid
     */
    public $userid;

    /**
     * meetingid
     * @var int $meetingid
     */
    public $meetingid;
}

?>
