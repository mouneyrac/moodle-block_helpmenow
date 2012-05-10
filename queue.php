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
 * Help me now queue abstract class.
 *
 * @package     block_helpmenow
 * @copyright   2012 VLACS
 * @author      David Zaharee <dzaharee@vlacs.org>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__) . '/db_object.php');

class helpmenow_queue extends helpmenow_db_object {
    const table = 'queue';

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
        'contextid',
        'name',
        'weight',
        'description',
    );

    /**
     * Array of relations, key is relation, element is id used to key relation
     * array.
     * @var array $relations
     */
    protected $relations = array(
        'helper' => 'userid',
        'request' => 'userid',
        'meeting' => 'id',
    );

    /**
     * The context the queue belongs to.
     * @var int $contextid
     */
    public $contextid;

    /**
     * The name of the queue.
     * @var string $name
     */
    public $name;

    /**
     * plugin queue's meetings use
     * @var string $plugin
     */
    public $plugin;

    /**
     * Weight for queue display order
     * @var int $weight
     */
    public $weight = HELPMENOW_DEFAULT_WEIGHT;

    /**
     * Description of the queue
     * @var string $desription
     */
    public $description = '';

    /**
     * Array of user ids of helpers
     * @var array $helper
     */
    public $helper = array();

    /**
     * Array of meeting requests
     * @var array $request
     */
    public $request = array();

    /**
     * Array of meetings
     * @var array $meeting
     */
    public $meeting = array();

    /**
     * Returns user's privilege given optional userid
     * @param int $userid user.id, if none provided uses $USER->id
     * @return string queue privilege
     */
    public function get_privilege() {
        global $USER;

        # if it's not set, try loading helpers
        if (!isset($this->helper[$USER->id])) {
            $this->load_relation('helper');
        }
        # if it's set now, they're a helper
        if (isset($this->helper[$USER->id])) {
            return HELPMENOW_QUEUE_HELPER;
        }

        $context = get_context_instance_by_id($this->contextid);
        if (has_capability(HELPMENOW_CAP_QUEUE_ASK, $context)) {
            return HELPMENOW_QUEUE_HELPEE;
        }

        return HELPMENOW_NOT_PRIVILEGED;
    }

    /**
     * Returns boolean of helper availability
     * @return boolean
     */
    public function check_available() {
        if (!count($this->helper)) {
            $this->load_relation('helper');
        }
        if (!count($this->helper)) {
            return false;
        }
        foreach ($this->helper as $h) {
            if ($h->isloggedin) {
                return true;
            }
        }
        return false;
    }

    /**
     * Logs $USER into the queue
     * @return boolean success
     */
    public function login() {
        global $USER;
        return $this->set_login($USER->id, 1);
    }

    /**
     * Logs $USER into the queue
     * @return boolean success
     */
    public function logout() {
        global $USER;
        $this->helper[$USER->id]->last_action = 0;
        return $this->set_login($USER->id, 0);
    }

    /**
     * Sets login state for passed user
     * @param int $userid user.id
     * @param int $state integer boolean
     * @return boolean success
     */
    public function set_login($userid, $state = 0) {
        if (!isset($this->helper[$userid])) {
            $this->load_relation('helper');
            if (!isset($this->helper[$userid])) {
                debugging("User with userid $userid is not a queue helper");
                return false;
            }
        }
        $this->helper[$userid]->isloggedin = $state;
        return $this->helper[$userid]->update();
    }

    /**
     * Creates helper for queue using passed userid
     * @param int $userid user.id
     * @return boolean success
     */
    public function add_helper($userid) {
        if (isset($this->helper[$userid])) {
            $this->load_relation('helper');
            if (!isset($this->helper[$userid])) {
                return false;   # already a helper
            }
        }
        $helper = helpmenow_helper::new_instance($this->plugin);
        $helper->queueid = $this->id;
        $helper->userid = $userid;
        $rval = $helper->insert();
        $this->helper[$userid] = $helper;
        return $rval;
    }

    /**
     * Deletes helper
     * @param int $userid user.id
     * @return boolean success
     */
    public function remove_helper($userid) {
        if (!isset($queue->helper[$userid])) {
            $this->load_relation('helper');
            if (!isset($this->helper[$userid])) {
                return false;
            }
        }
        $rval = $this->helper[$userid]->delete();
        unset($this->helper[$userid]);
        return $rval;
    }

    /**
     * Overridding load_relation to make sure requests are ordered by
     * timecreated ascending
     */
    public function load_relation($relation) {
        parent::load_relation($relation);
        if ($relation = 'request') {
            uasort($this->request, array('helpmenow_request', 'cmp'));
        }
    }

    /**
     * Returns moodle form to edit/create queue
     * @return object moodle form
     */
    public static function get_form() {
        global $CFG;
        require_once(dirname(__FILE__) . '/form.php');
        return new helpmenow_queue_form();
    }

    /**
     * Process form data
     * @param object $formdata
     * @return mixed false if failed, queue object if successfull
     */
    public static function process_form($formdata) {
        if ($formdata->queueid) {
            $queue = helpmenow_queue::get_instance($formdata->queueid);
        } else {
            $queue = helpmenow_queue::new_instance($formdata->plugin);
            if ($formdata->courseid == SITEID) {
                $context = get_context_instance(CONTEXT_SYSTEM, SITEID);
            } else {
                $context = get_context_instance(CONTEXT_COURSE, $formdata->courseid);
            }
            $queue->contextid = $context->id;
        }

        $queue->name = $formdata->name;
        $queue->description = $formdata->description;
        $queue->weight = $formdata->weight;

        if ($formdata->queueid) {
            if (!$queue->update()) {
                return false;
            }
        } else {
            if (!$queue->insert()) {
                return false;
            }
        }
        return $queue;
    }

    /**
     * Returns array meant for mform's set_data(). If given a valid queueid, use
     * values in the database, otherwise use some predefined defaults
     * @param int $queueid queue.id; if passed 0, return some default values
     *      for a new queue
     * @return array moodle form set_data() array
     */
    public static function get_form_defaults($queueid) {
        global $COURSE;
        $toform = array(
            'queueid' => $queueid,
            'courseid' => $COURSE->id,
        );
        if (!$queueid) {
            return $toform;
        }
        $queue = helpmenow_queue::get_instance($queueid);
        $toform['name'] = $queue->name;
        $toform['description'] = $queue->description;
        $toform['plugin'] = $queue->plugin;
        $toform['weight'] = $queue->weight;
        return $toform;
    }

    /**
     * Gets an array of queues by contexts
     * @param array $contexts array of contexts.id
     * @return array of queues
     */
    public static final function get_queues_by_context($contexts) {
        global $CFG;
        $contexts = implode(',', $contexts);

        $sql = "
            SELECT q.*
            FROM {$CFG->prefix}block_helpmenow_queue q
            WHERE q.contextid IN ($contexts)
            ORDER BY q.weight
        ";

        if (!$records = get_records_sql($sql)) {
            return false;
        }
        return helpmenow_queue::objects_from_records($records);
    }

    /**
     * Gets an array of queues user is a helper for
     * @param int $userid optional user.id, otherwise uses $USER
     * @return array of queues
     */
    public static final function get_queues_by_user($userid = null) {
        global $CFG;
        if (!isset($userid)) {
            global $USER;
            $userid = $USER->id;
        }

        $sql = "
            SELECT q.*
            FROM {$CFG->prefix}block_helpmenow_queue q
            JOIN {$CFG->prefix}block_helpmenow_helper h ON q.id = h.queueid
            WHERE h.userid = $userid
            ORDER BY q.weight
        ";

        if (!$records = get_records_sql($sql)) {
            return false;
        }
        return helpmenow_queue::objects_from_records($records);
    }
}

?>
