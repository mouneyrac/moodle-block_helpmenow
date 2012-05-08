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
    const table = false;

    /**
     * Array of required db fields. This must be overridden by the child. The
     * required fields for all children are below.
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
    protected $optional_fields = array();

    /**
     * Array of extra fields that must be defined by the child if the plugin
     * requires more data be stored in the database. If this anything is
     * defined here, then the child should also define the member variable
     * @var array $extra_fields
     */
    protected $extra_fields = array();

    /**
     * Data simulates database fields in child classes by serializing data.
     * This is only used if extra_fields is used, and does not need to be
     * in the database if it's not being used. Needs to be public for
     * addslashes_recursive.
     * @var string $data
     */
    public $data;

    /**
     * Array of relations, such as meeting2user.
     * @var array $relations
     */
    protected $relations = array();

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
     * Plugin of the object; child should override this, if using a plugin class
     * @var string $plugin
     */
    public $plugin = '';

    /**
     * Constructor. If we get an id, load from the database. If we get a object
     * from the db and no id, use that.
     * @param int $id id of the queue in the db
     * @param object $record db record
     */
    public function __construct($id=null, $record=null) {
        if (isset($id)) {
            $this->id = $id;
            $this->load_from_db();
        } else if (isset($record)) {
            $this->load($record);
        }
        if (isset($id) or isset($record)) {
            $this->load_all_relations();
        }
    }

    /**
     * Load the fields from the database.
     * @return boolean success
     */
    public function load_from_db() {
        if (!$record = get_record("block_helpmenow_" . static::table, 'id', $this->id)) {
            debugging("Could not load " . static::table . " from db.");
            return false;
        }
        $this->load($record);
        return true;
    }

    /**
     * Updates object in db, using object variables. Requires id.
     * @return boolean success
     */
    public function update() {
        global $USER;

        if (empty($this->id)) {
            debugging("Can not update " . static::table . ", no id!");
            return false;
        }

        $this->timemodified = time();
        $this->modifiedby = $USER->id;

        if (!$this->check_required_fields()) {
            return false;
        }

        $this->serialize_extras();

        return update_record("block_helpmenow_" . static::table, addslashes_recursive($this));
    }

    /**
     * Records the object in the db, and sets the id from the return value.
     * @return int PK ID if successful, false otherwise
     */
    public function insert() {
        global $USER;

        if (!empty($this->id)) {
            debugging(static::table . " already exists in db.");
            return false;
        }

        $this->timecreated = time();
        $this->timemodified = time();
        $this->modifiedby = $USER->id;

        if (!$this->check_required_fields()) {
            return false;
        }

        $this->serialize_extras();

        if (!$this->id = insert_record("block_helpmenow_" . static::table, addslashes_recursive($this))) {
            debugging("Could not insert " . static::table);
            return false;
        }

        return $this->id;
    }

    /**
     * Deletes object in db, using object variables. Requires id.
     * @param boolean $delete_relations wether or not to delete relations
     * @return boolean success
     */
    public function delete($delete_relations = true) {
        $success = true;

        # delete relations if necessary
        if ($delete_relations) {
            $this->load_all_relations();
            foreach ($this->relations as $rel => $foo) {
                foreach ($this->$rel as $key => $r) {
                    if (!$r->delete()) {
                        $success = false;
                    }
                }
            }
        }

        if (empty($this->id)) {
            debugging("Can not delete " . static::table . ", no id!");
            return false;
        }

        if (!delete_records("block_helpmenow_" . static::table, 'id', $this->id)) {
            $success = false;
        }

        return $success;
    }

    /**
     * Loads relations into member variable array. Eg.: a queues' helpers.
     * @param string $relation relation to be loaded, this is also the name of
     *      table and the memeber variable
     */
    public function load_relation($relation) {
        $this->$relation = array();
        if (!$tmp = get_records("block_helpmenow_$relation", static::table."id", $this->id)) {
            return;
        }
        $class = "helpmenow_$relation";
        $key = $this->relations[$relation];
        foreach ($tmp as $r) {
            $this->{$relation}[$r->$key] = $class::get_instance(null, $r);
        }
    }

    /**
     * Loads all relations indicated by $this->relations
     */
    public function load_all_relations() {
        foreach ($this->relations as $rel => $key) {
            $this->load_relation($rel);
        }
    }

    /**
     * Factory function to get existing object of the correct child class
     * @param int $id *.id
     * @return object
     */
    public final static function get_instance($id=null, $record=null) {
        global $CFG;

        # we have to get the record instead of passing the id to the
        # constructor as we have no idea what class the record belongs to
        if (isset($id)) {
            if (!$record = get_record("block_helpmenow_" . static::table, 'id', $id)) {
                return false;
            }
        }

        $class = static::get_class($record->plugin);

        return new $class(null, $record);
    }

    /**
     * Factory function to create an object of the correct plugin
     * @param string $plugin optional plugin parameter, if none supplied uses
     *      configured default
     * @return object
     */
    public final static function new_instance($plugin) {
        global $CFG;

        $class = static::get_class($plugin);

        $object = new $class;
        $object->plugin = $plugin;

        return $object;
    }

    /**
     * Returns an array of objects from an array of records
     * @param array $records
     * @return array of objects
     */
    public final static function objects_from_records($records) {
        $objects = array();
        foreach ($records as $r) {
            $objects[$r->id] = static::get_instance(null, $r);
        }
        return $objects;
    }

    /**
     * Return the class name and require_once the file that contains it
     * @param string $plugin
     * @return string classname
     */
    public final static function get_class($plugin) {
        global $CFG;

        $classpath = "$CFG->dirroot/blocks/helpmenow/plugins/$plugin/" . static::table . ".php";
        if (!file_exists($classpath)) {
            return "helpmenow_" . static::table;
        }
        $pluginclass = "helpmenow_" . static::table . "_$plugin";
        require_once($classpath);

        return $pluginclass;
    }

    /**
     * Loads the fields from a passed record. Also unserializes simulated fields
     * @param object $record db record
     */
    protected function load($record) {
        $fields = array_merge($this->required_fields, $this->optional_fields);
        $fields[] = 'data';
        foreach ($fields as $f) {
            if (isset($record->$f)) {
                $this->$f = $record->$f;
            }
        }

        # bail at this point if we don't have extra fields
        if (!count($this->extra_fields)) { return; }

        $extras = unserialize($this->data);
        foreach ($this->extra_fields as $field) {
            $this->$field = $extras[$field];
        }
    }

    /**
     * Returns true if all required db fields are set in the object, false
     * otherwise.
     * @return boolean
     */
    protected function check_required_fields() {
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
     * Serializes simulated fields if necessary
     */
    protected final function serialize_extras() {
        # bail immediately if we don't have any extra fields
        if (!count($this->extra_fields)) { return; }
        $extras = array();
        foreach ($this->extra_fields as $field) {
            $extras[$field] = $this->$field;
        }
        $this->data = serialize($extras);
        return;
    }
}

?>
