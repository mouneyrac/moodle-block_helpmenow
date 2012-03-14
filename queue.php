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
     * @var array $require_fields
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
    
    function update() {
        global $USER;

        if (empty($this->id)) {
            debugging('Can not update queue, no id!');
            return false
        }

        $this->timemodified = time();
        $this->modifiedby = $USER->id;

        if (!$this->check_required_fields()) {
            return false;
        }

        return update_record('block_helpmenow_queue', $this);
    }

    function insert() {
        global $USER;

        $this->timecreated = time();
        $this->timemodified = time();
        $this->modifiedby = $USER->id;

        if (!$this->check_required_fields()) {
            return false;
        }
        return insert_record('block_helpmenow_queue', $this);
    }

    function delete() {
        if (empty($this->id)) {
            debugging('Can not delete queue, no id!');
            return false
        }

        return delete_record('block_helpmenow_quue', 'id', $this->id);
    }

    function check_required_fields() {
        $success = true;
        foreach ($this->required_fields as $f) {
            if ($f = 'id') { continue; } # id is a special case, only mattering in update()
            if (!isset($this->$f) {
                debugging("Can not insert/update queue, no $f!");
                $success = false;
            }
        }
        return $success;
    }
}

?>
