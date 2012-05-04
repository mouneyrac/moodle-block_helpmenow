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
 * Help me now meeting, and meeting2user classes. Meeting is abstract and
 * defines what the plugins will need to have.
 *
 * @package     block_helpmenow
 * @copyright   2012 VLACS
 * @author      David Zaharee <dzaharee@vlacs.org>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__) . '/db_object.php');

abstract class helpmenow_meeting extends helpmenow_db_object {
    const table = 'meeting';

    /**
     * Array of required db fields.
     * @var array $required_fields
     */
    protected $required_fields = array(
        'id',
        'timecreated',
        'timemodified',
        'modifiedby',
        'plugin',
        'owner_userid',
    );

    /**
     * Array of optional db fields.
     * @var array $optional_fields
     */
    protected $optional_fields = array(
        'description',
    );

    /**
     * Array of relations
     * @var array $relations
     */
    protected $relations = array(
        'meeting2user' => 'userid',
    );

    /**
     * The userid of the user who owns the meeting, usually the queue helper or
     * the instructor of the course.
     * @var int $helper_userid
     */
    public $owner_userid;

    /**
     * Description of the meeting.
     * @var string $description
     */
    public $description;

    /**
     * Array of meeting2user objects
     * @var array $users
     */
    public $meeting2user = array();

    /**
     * Create the meeting. Caller will insert record.
     */
    abstract function create();

    /**
     * Adds a user to the meeting
     * @return bool success
     */
    public function add_user($userid = null) {
        if (!isset($userid)) {
            global $USER;
            $userid = $USER->id;
        }
        # check if this user is already in this meeting
        if (isset($this->meeting2user[$userid])) {
            return true;
        }
        # return false if meeting is full
        if ($this->check_full()) {
            return false;
        }

        # add user to meeting and return true
        $meeting2user = (object) array(
            'meetingid' => $this->id,
            'userid' => $userid,
        );
        $meeting2user = new helpmenow_meeting2user(null, $meeting2user);
        $meeting2user->insert();
        $this->meeting2user[$userid] = $meeting2user;
        return true;
    }

    /**
     * Plugin specific function to connect USER to meeting. Caller will insert
     * into db after
     * @return mixed false if failed, string url if succeeded
     */
    abstract function connect();

    /**
     * Returns boolean of meeting completed or not. Default just uses
     * configurable time since timecreated, but if plugin in has a more correct
     * way to determine completion it should override this.
     * @return boolean
     */
    public function check_completion() {
        global $CFG;
        # todo: right now assuming the setting will be in number of hours
        # change to minutes?
        return time() > ($this->timecreated + ($CFG->helpmenow_meeting_timeout * 60));
    }

    /**
     * Returns boolean of meeting full or not.
     * @return boolean
     */
    abstract function check_full();

    /**
     * Cleans up old meetings
     * @return boolean
     */
    public final static function clean_meetings() {
        $success = true;
        if ($meetings = get_records('block_helpmenow_meeting')) {
            foreach ($meetings as $k => $m) {
                $meetings[$k] = helpmenow_meeting::get_instance(null, $m);
                if ($meetings[$k]->check_completion()) {
                    $success = $success and $meetings[$k]->delete();
                }
            }
        }
        return $success;
    }
}

class helpmenow_meeting2user extends helpmenow_db_object {
    const table = 'meeting2user';

    /**
     * Array of required db fields.
     * @var array $required_fields
     */
    protected $required_fields = array(
        'id',
        'timecreated',
        'timemodified',
        'modifiedby',
        'meetingid',
        'userid',
        'plugin',
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
