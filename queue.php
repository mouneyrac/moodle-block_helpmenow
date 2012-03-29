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
 * Help me now queue class.
 *
 * @package     block_helpmenow
 * @copyright   2012 VLACS
 * @author      David Zaharee <dzaharee@vlacs.org>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__) . '/db_object.php');

# TODO: we need a way to order queues in the block

class helpmenow_queue extends helpmenow_db_object {
    /**
     * Table of the object.
     * @var string $table
     */
    private $table = 'queue';

    /**
     * Array of required db fields.
     * @var array $required_fields
     */
    private $required_fields = array(
        'id',
        'timecreated',
        'timemodified',
        'modifiedby',
        'contextid',
        'name',
        'plugin',
        'weight',
    );

    /**
     * Array of relations, key is relation, element is id used to key relation
     * array.
     * @var array $relations
     */
    private $relations = array(
        'helper' => 'userid',
        'request' => 'userid',
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
     * Weight for queue display order
     * @var int $weight
     */
    public $weight;

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
     * todo: should this take a helper object instead?
     * todo: do we even really need this?
     * Adds a helper to the queue
     * @param int $userid user.id of the helper
     * @return boolean success
     */
    function add_helper($userid) {
        if (record_exists('block_helpmenow_helper', 'queueid', $this->id, 'userid', $userid)) {
            debugging("User already helper for queue");
            return false;
        }

        $helper = new helpmenow_helper();
        $helper->userid = $userid;
        $helper->queueid = $this->id;
        if (!$helper->insert()) {
            return false;
        }

        $this->helper[$helper->userid] = $helper;

        return true;
    }

    /**
     * todo: should this take a helper object instead?
     * todo: do we even really need this?
     * Adds a helper to the queue
     * Removes a helper from the queue
     * @param int $userid user.id of helper
     * @return boolean success
     */
    function remove_helper($userid) {
        if (!isset($this->helper[$userid])) {
            $this->load_relation('helper');
            if (!isset($this->helper[$userid])) {
                debugging("Could not load helper where userid = $userid");
                return false;
            }
        }
        $success = $this->helper[$userid]->delete();
        unset($this->helper[$userid]);

        return $success;
    }

    /**
     * TODO: do we really need this in queueueueueue?
     *
     * Fulfills a request.
     * @param int $requestid request.id
     * @return object meeting object
     */
    function fulfill_request($requestid, $helper_userid) {
        if (!isset($this->request[$requestid])) {
            $this->load_relation('request');
            if (!isset($this->request[$requestid])) {
                debugging("Could not load request where id = $requestid, may not be this queue");
                return false;
            }
        }

        $meeting = $this->request[$requestid]->fulfill_request($helper_userid);

        $this->request[$requestid]->delete();
        unset($this->request[$requestid]);

        return $meeting;
    }

    /**
     * Returns user's privilege given optional userid
     * @param int $userid user.id, if none provided uses $USER->id
     * @return string queue privilege
     */
    function get_privilege($userid=null) {
        if (!isset($userid)) {
            global $USER;
            $userid = $USER->id;
        }

        # if it's not set, try loading helpers
        if (!isset($this->helper[$userid])) {
            $this->load_relation('helper');
        }
        # if it's set now, they're a helper
        if (isset($this->helper[$userid])) {
            return HELPMENOW_QUEUE_HELPER;
        }

        $context = get_context_instance_by_id($this->contextid);
        if (has_capability(HELPMENOW_CAP_QUEUE_REQUEST, $context)) {
            return HELPMENOW_QUEUE_HELPEE;
        }

        return HELPMENOW_NOT_PRIVILEGED;
    }

    /**
     * Returns boolean of helper availability
     * @return boolean
     */
    function check_available() {
        if (!count($this->helpers)) {
            $this->load_relation('helper');
        }
        if (!count($this->helpers)) {
            debugging("Queue has no helpers");
            return false;
        }
        foreach ($this->helpers as $h) {
            if ($h->isloggedin) {
                return true;
            }
        }
        return false;
    }

    /**
     * Gets an array of queues in the current context
     * @return array of queues
     */
    public static function get_queues() {
        global $CFG, $COURSE;

        # get contexts for course and system
        $sitecontext = get_context_instance(CONTEXT_SYSTEM, SITEID);
        $context = get_context_instance(CONTEXT_COURSE, $COURSE->id);

        $sql = "
            SELECT q.*
            FROM {$CFG->prefix}block_helpmenow_queue q
            WHERE q.contextid = $sitecontext->id
            OR q.contextid = $context->id
            ORDER BY q.weight
        ";

        $records = get_records_sql($sql);
        $queues = array();
        foreach ($records as $r) {
            $queues[$r->id] = new helpmenow_queue(null, $r);
        }
        return $queues;
    }
}

?>
