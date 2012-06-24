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

abstract class helpmenow_plugin_object {
    const table = false;

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
     * The id of the object.
     * @var int $id
     */
    public $id;

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
            $record = get_record('block_helpmenow_'.static::table, 'id', $id);
        }
        if (isset($record)) {
            foreach ($record as $k => $v) {
                $this->$k = $v;
            }
            $this->load_extras();
        }
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

        $this->serialize_extras();

        if (!$this->id = insert_record("block_helpmenow_" . static::table, addslashes_recursive($this))) {
            debugging("Could not insert " . static::table);
            return false;
        }

        return $this->id;
    }

    /**
     * Deletes object in db, using object variables. Requires id.
     * @return boolean success
     */
    public function delete() {
        if (empty($this->id)) {
            debugging("Can not delete " . static::table . ", no id!");
            return false;
        }

        return delete_records("block_helpmenow_" . static::table, 'id', $this->id);
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
    protected function load_extras() {
        # bail at this point if we don't have extra fields
        if (!count($this->extra_fields)) { return; }

        $extras = unserialize($this->data);
        foreach ($this->extra_fields as $field) {
            $this->$field = $extras[$field];
        }
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
