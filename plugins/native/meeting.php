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
 * Native moodle chat helpmenow meeting class
 *
 * @package     block_helpmenow
 * @copyright   2012 VLACS
 * @author      David Zaharee <dzaharee@vlacs.org>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(__FILE__))) . '/meeting.php');

class helpmenow_meeting_native extends helpmenow_meeting {
    /**
     * Plugin name
     * @var string $plugin
     */
    public $plugin = 'native';

    /**
     * Create the meeting. Caller will insert record.
     */
    public function create() {
        return true;
    }

    /**
     * Plugin specific function to connect USER to meeting. Caller will insert
     * into db after
     * @return $string url
     */
    public function connect() {
        global $CFG, $USER;

        foreach ($this->meeting2user as $u) {
            if ($u->userid == $USER->id) { continue; }
            $userid = $u->userid;
        }
        $meeting_url = new moodle_url("$CFG->wwwroot/message/discussion.php");
        $meeting_url->param('id', $userid);
        return $meeting_url->out();
    }

    /**
     * Return boolean of meeting full or not.
     * @return boolean
     */
    public function check_full() {
        return count($this->meeting2user) >= 2;
    }
}

?>
