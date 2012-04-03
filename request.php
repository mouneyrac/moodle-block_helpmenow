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
 * Help me now request class.
 *
 * @package     block_helpmenow
 * @copyright   2012 VLACS
 * @author      David Zaharee <dzaharee@vlacs.org>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__) . '/db_object.php');

class helpmenow_request extends helpmenow_db_object {
    /**
     * Table of the object.
     * @var string $table
     */
    private $table = 'request';

    /**
     * Array of required db fields.
     * @var array $required_fields
     */
    private $required_fields = array(
        'id',
        'timecreated',
        'timemodified',
        'modifiedby',
        'userid',
        'last_refresh',
    );

    /**
     * Array of optional db fields.
     * @var array $optional_fields
     */
    private $optional_fields = array(
        'description',
        'queueid',
        'requested_userid',
        'meetingid',
    );

    /**
     * The userid of the user who requested the meeting
     * @var int $userid
     */
    public $userid;

    /**
     * The request description
     * @var string $description
     */
    public $description;

    /**
     * The queue.id this request belongs to, if any
     * @var int $queueid
     */
    public $queueid;

    /**
     * The user.id of the target of the request, if any
     * @var int $requested_userid
     */
    public $requested_userid;

    /**
     * The meeting that is created for this request
     * @var int $meetingid
     */
    public $meetingid;

    /**
     * Time of the last refresh by the requesting user's browser
     * @var in $last_refresh
     */
    public $last_refresh;

    /**
     * Overloeading db_object->check_required_fields() to handle that one of
     * queueid or requested_userid needs to be set.
     * @return boolean success
     */
    function check_required_fields() {
        $success = parent::check_required_fields();
        if (!(isset($this->queueid) xor isset($this->requested_userid))) {
            debugging("One and only one of queueid or requested_userid needs to be set");
            $success = false;
        }
        return $success;
    }

    /**
     * Cleans up abandoned requests
     * @return boolean
     */
    public final static function helpmenow_clean_requests() {
        global $CFG;
        # todo: for now assuming setting will be in number of minutes
        $cutoff = time() - ($CFG->helpmenow_request_timeout * 60);
        return delete_records_select('block_helpmenow_request', "last_refresh < $cutoff");
    }
}

?>
