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
    /**
     * Table of the object. This should not be overriden by the child.
     * @var string $table
     */
    protected $table = 'meeting';

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
    );

    /**
     * Array of optional db fields.
     * @var array $optional_fields
     */
    protected $optional_fields = array(
        'owner_userid',
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
     * Plugin of the meeting; child should override this.
     * @var string $plugin
     */
    public $plugin;

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
     * Connects user to the meeting
     * @return mixed false if failed, string url if succeeded
     */
    final function connect_user() {
        global $USER;

        # todo: logging

        # add the user to the meeting, if not already
        if (!isset($this->meeting2user[$USER->id])) {
            if ($this->check_full()) {
                return false;
            }
            $this->add_user();
        }

        # call the plugin's connecting user code
        $url = $this->connect();

        return $url;
    }

    /**
     * Adds a user to the meeting
     */
    final function add_user($userid = null) {
        if (!isset($userid)) {
            global $USER;
            $userid = $USER->id;
        }
        $meeting2user = (object) array(
            'meetingid' => $this->id,
            'userid' => $userid,
        );
        $meeting2user = new helpmenow_meeting2user(null, $meeting2user);
        $meeting2user->insert();
        $this->meeting2user[$userid] = $meeting2user;
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
    function check_completion() {
        global $CFG;
        # todo: right now assuming the setting will be in number of hours
        return time() > ($this->timecreated + ($CFG->helpmenow_meeting_timeout * 60 * 60));
    }

    /**
     * Returns boolean of meeting full or not.
     * @return boolean
     */
    abstract function check_full();

    /**
     * Cron that will run everytime block cron is run.
     * @return boolean
     */
    abstract function cron();

    /**
     * Factory function to get existing meeting of the correct plugin
     * @param int $meetingid meeting.id
     * @return object plugin meeting
     */
    public final static function get_meeting($meetingid=null, $meeting=null) {
        global $CFG;

        # we have to get the meeting instead of passing the meeting id to the
        # constructor as we have no idea what class the meeting belongs to
        if (isset($meetingid)) {
            $meeting = get_record('block_helpmenow_meeting', 'id', $meetingid);
        }

        $plugin = $meeting->plugin;
        $class = "helpmenow_meeting_$plugin";
        $classpath = "$CFG->dirroot/blocks/helpmenow/plugins/$plugin/meeting_$plugin.php";

        require_once($classpath);

        return new $class(null, $meeting);
    }

    /**
     * Factory function to create a meeting of the correct plugin
     * @param string $plugin optional plugin parameter, if none supplied uses
     *      configured default
     * @return object plugin meeting
     */
    public final static function create_meeting($plugin = null) {
        global $CFG;

        if (!isset($plugin)) {
            $plugin = 'native';
            if (isset($CFG->helpmenow_default_plugin) and strlen($CFG->helpmenow_default_plugin) > 0) {
                $plugin = $CFG->helpmenow_default_plugin;
            }
        }

        require_once(dirname(__FILE__) . "/plugins/$plugin/meeting_$plugin.php");
        $class = "helpmenow_meeting_$plugin";

        $meeting = new $class;
        $meeting->create();

        # insert the meeting immediately
        $meeting->insert();

        return $meeting;
    }

    /**
     * Calls any existing cron functions of plugins
     * @return boolean
     */
    public final static function cron_all() {
        $success = true;
        foreach (get_list_of_plugins('plugins', '', dirname(__FILE__)) as $plugin) {
            require_once(dirname(__FILE__) . "/plugins/$plugin/meeting_$plugin.php");
            $class = "helpmenow_meeting_$plugin";
            $success = $success and $class::cron();
        }
        return $success;
    }

    /**
     * Cleans up old meetings
     * @return boolean
     */
    public final static function clean_meetings() {
        $success = true;
        $meetings = get_records('block_helpmenow_meeting');
        foreach ($meetings as $k => $m) {
            $meetings[$k] = helpmenow_meeting::get_meeting(null, $m);
            if ($meetings[$k]->check_completion) {
                $success = $success and $meetings[$k]->delete();
                unset($meetings[$k]);
            }
        }
        return $success;
    }
}

class helpmenow_meeting2user extends helpmenow_db_object {
    /**
     * Table of the object.
     * @var string $table
     */
    protected $table = 'meeting2user';

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
