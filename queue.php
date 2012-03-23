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

class helpmenow_queue extends helpmenow_db_object {
    /**
     * Table of the object.
     * @var string $table
     */
    private $table = 'block_helpmenow_queue';

    /**
     * Array of required db fields.
     * @var array $required_fields
     */
    private $required_fields = array(
        'id',
        'timecreated',
        'timemodified',
        'modifiedby',
        'context_instanceid',
        'name',
        'plugin',
    );

    /**
     * The context the queue belongs to.
     * @var int $context_instanceid
     */
    public $context_instanceid;

    /**
     * The name of the queue.
     * @var string $name
     */
    public $name;

    /**
     * Array of user ids of helpers
     * @var array $helpers
     */
    public $helpers = array();

    /**
     * Array of meetings awaiting connections
     * @var array $meetings
     */
    public $meetings = array();

    /**
     * Constructor. Load meetings and helpers if necessary.
     * @param int $id id of the queue in the db
     * @param object $record db record
     * @param boolean $fetch_related whether to fetch helpers and meetings from the db
     */
    function __construct($id=null, $record=null, $fetch_related=true) {
        parent::__construct($id, $record);
        if ($fetch_related) {
            $this->load_helpers();
            $this->load_meetings();
        }
    }

    /**
     * Deletes queue in db, using object variables. Requires id. Also deletes
     * associated meetings and helpers.
     * @return boolean success
     */
    function delete() {
        $success = parent::delete();

        # delete meetings
        $this->load_meetings();
        foreach ($this->meetings as $key => $m) {
            if (!$m->delete()) {
                $success = false;
            }
            unset($this->meetings[$key]);
        }
        # delete helpers
        $this->load_helpers();
        foreach ($this->helpers as $key => $h) {
            if (!$h->delete()) {
                $success = false;
            }
            unset($this->helpers[$key]);
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
