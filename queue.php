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
        'context_instanceid',
        'name',
        'plugin',
    );

    /**
     * Array of relations
     * @var array $relations
     */
    private $relations = array(
        'helper',
        'request',
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
}

?>
