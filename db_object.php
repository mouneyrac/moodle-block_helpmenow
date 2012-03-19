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
 * Help me now db object class.
 *
 * @package     block_helpmenow
 * @copyright   2012 VLACS
 * @author      David Zaharee <dzaharee@vlacs.org>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

abstract class helpmenow_db_object {
    /**
     * Table of the object. This must be overridden by the child.
     * @var string $table
     */
    private $table;

    /**
     * Array of required db fields.
     * @var array $required_fields
     */
    private $required_fields = array(
        'id',
        'timecreated',
        'timemodified',
        'modifiedby',
    );

    /**
     * Array of optional db fields.
     * @var array $optional_fields
     */
    private $optional_fields = array();

    /**
     * The id of the object.
     * @var int $id
     */
    public $id;

    /**
     * First time object was created
     * @var int $timecreated
     */
    public $timecreated;

    /**
     * Time of last modification.
     * @var int $timemodified
     */
    public $timemodified;

    /**
     * Id of user who made last modification.
     * @var int $modifiedby
     */
    public $modifiedby;

    /**
     * Constructor. If we get an id, load from the database. If we get a object
     * from the db and no id, use that.
     * @param int $id id of the queue in the db
     * @param object $record db record
     * @param boolean $fetch_related whether to fetch helpers and meetings from the db
     */
    function __construct($id=null, $record=null) {
        if (isset($id)) {
            $this->id = $id;
            $this->load_from_db();
        } else if (!empty($record)) {
            $this->load($record);
        }
    }

    /**
     * Load the fields from the database.
     * @return boolean success
     */
    function load_from_db() {
        if (!$record = get_record($this->table, 'id', $this->id)) {
            debugging("Could not load object from $this->table");
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
     * Updates object in db, using object variables. Requires id.
     * @return boolean success
     */
    function update() {
        global $USER;

        if (empty($this->id)) {
            debugging("Can not update $this->table, no id!");
            return false;
        }

        $this->timemodified = time();
        $this->modifiedby = $USER->id;

        if (!$this->check_required_fields()) {
            return false;
        }

        return update_record($this->table, $this);
    }

    /**
     * Records the object in the db, and sets the id from the return value.
     * @return int PK ID if successful, false otherwise
     */
    function insert() {
        global $USER;

        if (!empty($this->id)) {
            debugging("Record already exists in $this->table");
            return false;
        }

        $this->timecreated = time();
        $this->timemodified = time();
        $this->modifiedby = $USER->id;

        if (!$this->check_required_fields()) {
            return false;
        }

        if (!$this->id = insert_record($this->table, $this)) {
            debugging("Could not insert object into $this->table");
            return false;
        }

        return $this->id;
    }

    /**
     * Deletes object in db, using object variables. Requires id.
     * @return boolean success
     */
    function delete() {
        if (empty($this->id)) {
            debugging("Can not delete record in $this->table, no id!");
            return false;
        }

        $success = delete_record($this->table, 'id', $this->id);

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
                debugging("Can not insert/update object, no $f!");
                $success = false;
            }
        }
        return $success;
    }
}

?>
