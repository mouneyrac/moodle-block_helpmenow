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
     * Array of required db fields. This must be overridden by the child. The
     * required values for all children are below.
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
     * Array of relations, such as meeting2user.
     * @var array $relations
     */
    private $relations = array();

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
     * @param boolean $fetch_related whether to fetch relations from the db
     */
    function __construct($id=null, $record=null, $fetch_related=true) {
        if (isset($id)) {
            $this->id = $id;
            $this->load_from_db();
        } else if (!empty($record)) {
            $this->load($record);
        }
        if ($fetch_related) {
            $this->load_all_relations();
        }
    }

    /**
     * Load the fields from the database.
     * @return boolean success
     */
    function load_from_db() {
        if (!$record = get_record("block_helpmenow_$this->table", 'id', $this->id)) {
            debugging("Could not load $this->table from db.");
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

        return update_record("block_helpmenow_$this->table", $this);
    }

    /**
     * Records the object in the db, and sets the id from the return value.
     * @return int PK ID if successful, false otherwise
     */
    function insert() {
        global $USER;

        if (!empty($this->id)) {
            debugging("$this->table already exists in db.");
            return false;
        }

        $this->timecreated = time();
        $this->timemodified = time();
        $this->modifiedby = $USER->id;

        if (!$this->check_required_fields()) {
            return false;
        }

        if (!$this->id = insert_record("block_helpmenow_$this->table", $this)) {
            debugging("Could not insert $this->table");
            return false;
        }

        return $this->id;
    }

    /**
     * Deletes object in db, using object variables. Requires id.
     * @param boolean $delete_relations wether or not to delete relations
     * @return boolean success
     */
    function delete($delete_relatoins = true) {
        $success = true;

        # delete relations if necessary
        if ($delete_relations) {
            $this->load_all_relations();
            foreach ($this->relations as $rel => $foo) {
                foreach ($this->$rel as $key => $r) {
                    if (!$r->delete()) {
                        $success = false;
                    }
                    unset($this->$rel[$key]);
                }
            }
        }

        if (empty($this->id)) {
            debugging("Can not delete $this->table, no id!");
            return false;
        }

        if (!delete_record("block_helpmenow_$this->table", 'id', $this->id)) {
            $success = false;
        }

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

    /**
     * Loads relations into member variable array. Eg.: a queues' helpers.
     * @param string $relation relation to be loaded, this is also the name of
     *      table and the memeber variable
     */
    function load_relation($relation) {
        if (!$this->$relation = get_records("block_helpmenow_$relation", "{$this->table}id", $this->id)) {
            $this->$relation = array();
        } else {
            $class = "helpmenow_$relation";
            $key = $this->relation[$relation];
            foreach ($this->$relation as $r) {
                $this->$relation[$r->$key] = new $class(null, $r);
            }
        }
    }

    /**
     * Loads all relations indicated by $this->relations
     */
    function load_all_relations() {
        foreach ($this->relations as $rel => $key) {
            load_relation($rel);
        }
    }
}

?>
