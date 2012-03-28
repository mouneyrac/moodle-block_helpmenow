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
    );

    /**
     * Array of relations
     * @var array $relations
     */
    private $relations = array(
        'helper' => 'userid',
        'request' => 'id',
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

        $this->helper[$helper->id] = $helper;

        return true;
    }

    /**
     * Removes a helper from the queue
     * @param int @helperid helper.id
     * @return boolean success
     */
    function remove_helper($helperid) {
        if (!isset($this->helper[$helperid])) {
            $this->load_relation('helper');
            if (!isset($this->helper[$helperid])) {
                debugging("Could not load helper where id = $helperid, may not be this queue");
                return false;
            }
        }
        $success = $this->helper[$helperid]->delete();
        unset($this->helper[$helperid]);

        return $success;
    }

    /**
     * Adds a request to the queue
     * @param int $userid user.id of the user making the request
     * @param string $description request description
     * @return boolean success
     */
    function add_request($userid, $description) {
        $request = new helpmenow_request();
        $request->userid = $userid;
        $request->description = $description;
        $request->queueid = $this->id;
        if (!$request->insert()) {
            return false;
        }

        $this->request[$request->id] = $request;

        return true;
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
