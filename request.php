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
    const table = 'request';

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
        'userid',
        'last_refresh',
        'description',
        'queueid',
    );

    /**
     * Array of optional db fields.
     * @var array $optional_fields
     */
    protected $optional_fields = array(
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
     * Returns moodle form to create new request
     * @return object moodle form
     */
    public static function get_form() {
        global $CFG;
        require_once(dirname(__FILE__) . '/form.php');
        return new helpmenow_request_form();
    }

    /**
     * Process form data
     * @param object $formdata
     * @return mixed false if failed, request object if successful
     */
    public static function process_form($formdata) {
        global $USER;

        $request = helpmenow_request::new_instance($formdata->plugin);
        $request->queueid = $formdata->queueid;
        $request->description = $formdata->description;
        $request->userid = $USER->id;
        $request->last_refresh = time();
        if (!$request->insert()) {
            return false;
        }
        return $request;
    }

    /**
     * Cleans up abandoned requests
     * @return boolean
     */
    public final static function clean_requests() {
        global $CFG;
        $success = true;
        $cutoff = time() - ($CFG->helpmenow_request_timeout * 60);
        if ($records = get_records_select('block_helpmenow_request', "last_refresh < $cutoff")) {
            $requests = helpmenow_request::objects_from_records($records);
            foreach ($requests as $r) {
                # log
                helpmenow_log($r->userid, 'request_abandoned', "requestid: {$r->id}");

                $success = $success and $r->delete();
            }
        }
        return $success;
    }

    /**
     * Comparison functions for sorting requests by timecreated
     * @return integer -1, 0, 1
     */
    public final static function cmp($a, $b) {
        if ($a->timecreated == $b->timecreated) {
            return 0;
        }
        return ($a->timecreated < $b->timecreated) ? -1 : 1;
    }
}

?>
