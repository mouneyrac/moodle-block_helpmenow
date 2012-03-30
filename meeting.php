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
        'meeting2user' => 'userid',
    );

    /**
     * Plugin of the meeting; child should override this.
     * @var string $plugin
     */
    private $plugin;

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
     * @return $string url
     */
    final function connect() {
        global $USER;

        # todo: logging

        # add the user to the meeting
        $meeting2user = (object) array(
            'meetingid' => $this->id,
            'userid' => $USER->id,
        );
        $meeting2user = new helpmenow_meeting2user(null, $meeting2user);
        $meeting2user->insert();
        $this->meeting2user[$meeting2user->userid] = $meeting2user;

        # call the plugin's connecting user code
        $url = $this->plugin_connect();
        $this->insert();

        return $url;
    }

    /**
     * Plugin specific function to connect USER to meeting. Caller will insert
     * into db after
     * @return $string url
     */
    abstract function plugin_connect();

    /**
     * Returns boolean of meeting completed or not. Default just uses
     * configurable time since timecreated, but if plugin in has a more correct
     * way to determine completion it should override this.
     * @return boolean
     */
    function check_completion() {
        global $CFG;
        return time() > ($this->timecreated + ($CFG->helpmenow_meeting_timeout * 60 * 60));
    }

    /**
     * Factory function to get existing meeting of the correct plugin
     * @param int $meetingid meeting.id
     * @return object plugin meeting
     */
    public final static function get_meeting($meetingid) {
        $meeting = get_record('block_helpmenow_meeting', 'id', $meetingid);

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
        if (!isset($plugin)) {
            $plugin = 'native';
            if (isset($CFG->helpmenow_default_plugin and strlen($CFG->helpmenow_default_plugin) > 0) {
                $plugin = $CFG->helpmenow_default_plugin;
            }
        }
        $class = "helpmenow_meeting_$plugin";
        $classpath = "$CFG->dirroot/blocks/helpmenow/plugins/$plugin/meeting_$plugin.php";

        require_once($classpath);

        $meeting = new $class;
        $meeting->create();

        # save the meeting immediately
        $meeting->insert();

        return $meeting;
    }
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
