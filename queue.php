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

class helpmenow_queue {
    /**
     * Array of required db fields.
     * @var array $required_fields
     */
    var $required_fields = array(
        'id',
        'context_instanceid',
        'name',
        'timecreated',
        'timemodified',
        'modifiedby'
    );

    /**
     * Array of optional db fields.
     * @var array $optional_fields
     */
    var $optional_fields = array();

    /**
     * The id of the queue.
     * @var int $id
     */
    var $id;

    /**
     * The context the queue belongs to.
     * @var int $context_instanceid
     */
    var $context_instanceid;

    /**
     * The name of the queue.
     * @var string $name
     */
    var $name;

    /**
     * First time queue was created
     * @var int $timecreated
     */
    var $timecreated;

    /**
     * Time of last modification.
     * @var int $timemodified
     */
    var $timemodified;

    /**
     * Id of user who made last modification.
     * @var int $modifiedby
     */
    var $modifiedby;

    /**
     * Array of user ids of helpers
     * @var array $helpers
     */
    var $helpers = array();

    /**
     * Array of meetings awaiting connections
     * @var array $meetings
     */
    var $meetings = array();

    /**
     * Constructor. If we get an id, load from the database. If we get a object
     * from the db and no id, use that. Load meetings and helpers if necessary.
     * @param int $id id of the queue in the db
     * @param object $record db record
     * @param boolean $fetch_related whether to fetch helpers and meetings from the db
     */
    function helpmenow_queue($id=null, $record=null, $fetch_related=true) {
        if (isset($id)) {
            $this->id = $id;
            $this->load_from_db();
        } else if (!empty($record)) {
            $this->load($record);
        }
        if ($fetch_related) {
            $this->load_helpers();
            $this->load_meetings();
        }
    }

    /**
     * Load the fields from the database.
     * @return boolean success
     */
    function load_from_db() {
        if (!$record = get_record('block_helpmenow_queue', 'id', $this->id)) {
            debugging("Could not load queue from db");
            return false;
        }
        $this->load($record);
        return true;
    }

    /**
     * Loads the fields from a passed record.
     * @param object $record db record
     */
    function load($record) {
        $fields = array_merge($this->required_fields, $this->optional_fields);
        foreach ($fields as $f) {
            if (isset($record->$f)) {
                $this->$f = $record->$f;
            }
        }
    }

    /**
     * Updates queue in db, using object variables. Requires id.
     * @return boolean success
     */
    function update() {
        global $USER;

        if (empty($this->id)) {
            debugging('Can not update queue, no id!');
            return false;
        }

        $this->timemodified = time();
        $this->modifiedby = $USER->id;

        if (!$this->check_required_fields()) {
            return false;
        }

        return update_record('block_helpmenow_queue', $this);
    }

    /**
     * Records the queue in the db, and sets the id from the return value.
     * @return int PK ID if successful, false otherwise
     */
    function insert() {
        global $USER;

        if (!empty($this->id)) {
            debugging("Queue already exists in db");
            return false;
        }

        $this->timecreated = time();
        $this->timemodified = time();
        $this->modifiedby = $USER->id;

        if (!$this->check_required_fields()) {
            return false;
        }

        if (!$this->id = insert_record('block_helpmenow_queue', $this)) {
            debugging("Could not insert queue into db");
            return false;
        }

        return $this->id;
    }

    /**
     * Deletes queue in db, using object variables. Requires id.
     * @return boolean success
     */
    function delete() {
        if (empty($this->id)) {
            debugging('Can not delete queue, no id!');
            return false;
        }

        $success = true;

        # delete meetings
        $this->load_meetings();
        foreach ($this->meetings as $key => $m) {
            $success = $m->delete();
            unset($this->meetings[$key]);
        }
        # delete helpers
        $this->load_helpers();
        foreach ($this->helpers as $key => $h) {
            $success = $h->delete();
            unset($this->helpers[$key]);
        }

        $success = delete_record('block_helpmenow_queue', 'id', $this->id);

        return $success;
    }

    /**
     * Returns true if all required db fields are set in the object, false
     * otherwise.
     * @return boolean
     */
    function check_required_fields() {
        $success = true;
        foreach ($this->required_fields as $f) {
            if ($f = 'id') { continue; } # id is a special case, only mattering in update()
            if (!isset($this->$f)) {
                debugging("Can not insert/update queue, no $f!");
                $success = false;
            }
        }
        return $success;
    }

    /**
     * Load meetings into $this->meetings. We want an empty array if there are
     * none.
     */
    function load_meetings() {
        if (!$this->meetings = get_records('block_helpmenow_meetings', 'queueid', $this->id)) {
            $this->meetings = array();
        }
        foreach ($this->meetings as $key => $m) {
            $this->meetings[$key] = new helpmenow_meeting(null, $m);
        }
    }

    /**
     * Load helpers into $this->helpers. We want an empty array if there are
     * none.
     */
    function load_helpers() {
        if (!$this->helpers = get_records('block_helpmenow_helpers', 'queueid', $this->id)) {
            $this->helpers = array();
        }
        foreach ($this->helpers as $key => $h) {
            $this->helpers[$key] = new helpmenow_helper(null, $h);
        }
    }
}

?>
