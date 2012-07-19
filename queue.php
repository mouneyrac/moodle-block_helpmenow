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

require_once(dirname(__FILE__) . '/lib.php');

class helpmenow_queue {
    /**
     * queue.id
     * @var int $id
     */
    public $id;

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
     * Description of the queue
     * @var string $desription
     */
    public $description;

    /**
     * Queue helpers
     * @var array $helper
     */
    public $helpers;

    /**
     * Queue sessions
     * @var array $sessions
     */
    public $sessions;

    /**
     * Constructor. If we get an id, load from the database. If we get a object
     * from the db and no id, use that.
     * @param int $id id of the queue in the db
     * @param object $record db record
     */
    public function __construct($id=null, $record=null) {
        if (isset($id)) {
            $record = get_record('block_helpmenow_queue', 'id', $id);
        }
        foreach ($record as $k => $v) {
            $this->$k = $v;
        }
    }

    /**
     * Returns USER's privilege
     * @return string queue privilege
     */
    public function get_privilege() {
        global $USER, $CFG;

        $this->load_helpers();

        # if it's set now, they're a helper
        if (isset($this->helpers[$USER->id])) {
            return HELPMENOW_QUEUE_HELPER;
        }

        $context = get_context_instance(CONTEXT_SYSTEM, SITEID);

        if (has_capability(HELPMENOW_CAP_QUEUE_ASK, $context)) {
            return HELPMENOW_QUEUE_HELPEE;
        }

        return HELPMENOW_NOT_PRIVILEGED;
    }

    /**
     * Returns boolean of helper availability
     * @return boolean
     */
    public function is_open() {
        $this->load_helpers();
        if (!count($this->helpers)) {
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
     * Creates helper for queue using passed userid
     * @param int $userid user.id
     * @return boolean success
     */
    public function add_helper($userid) {
        $this->load_helpers();
        
        if (isset($this->helpers[$userid])) {
            return false;   # already a helper
        }

        $helper = (object) array(
            'queueid' => $this->id,
            'userid' => $userid,
            'isloggedin' => 0,
        );

        if (!$helper->id = insert_record('block_helpmenow_helper', $helper)) {
            return false;
        }
        $this->helpers[$userid] = $helper;

        return true;
    }

    /**
     * Deletes helper
     * @param int $userid user.id
     * @return boolean success
     */
    public function remove_helper($userid) {
        $this->load_helpers();

        if (!isset($this->helpers[$userid])) {
            return false;
        }

        if (!delete_records('block_helpmenow_helper', 'id', $this->helpers[$userid]->id)) {
            return false;
        }
        unset($this->helpers[$userid]);

        return true;
    }

    /**
     * Loads helpers into $this->helpers array
     */
    public function load_helpers() {
        if (isset($this->helpers)) {
            return true;
        }

        if (!$helpers = get_records('block_helpmenow_helper', 'queueid', $this->id)) {
            return false;
        }

        foreach ($helpers as $h) {
            $this->helpers[$h->userid] = $h;
        }

        return true;
    }

    public static function get_queues() {
        global $CFG;
        if (!$records = get_records_sql("SELECT * FROM {$CFG->prefix}block_helpmenow_queue ORDER BY weight ASC")) {
            return false;
        }
        return self::queues_from_recs($records);
    }

    private static function queues_from_recs($records) {
        $queues = array();
        foreach ($records as $r) {
            $queues[$r->id] = new helpmenow_queue(null, $r);
        }
        return $queues;
    }
}

?>
