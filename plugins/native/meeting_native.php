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
 * Native moodle chat helpmenow meeting plugin
 *
 * @package     block_helpmenow
 * @copyright   2012 VLACS
 * @author      David Zaharee <dzaharee@vlacs.org>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(__FILE__))) . '/db_object.php');

class helpmenow_meeting_native extends helpmenow_meeting {
    /**
     * Plugin name
     * @var string $plugin
     */
    protected $plugin = 'native';

    /**
     * We'll probably need a sessionid or similar
     * @var array $extra_fields
     */
    protected $extra_fields = array(
        'sessionid',
    );

    /**
     * Sessionid of the chat
     * @var int $sessionid
     */
    protected $sessionid;

    /**
     * Create the meeting. Caller will insert record.
     */
    function create() {
        return true;
    }

    /**
     * Plugin specific function to connect USER to meeting. Caller will insert
     * into db after
     * @return $string url
     */
    function connect() {
        return "http://www.google.com";
    }

    /**
     * Returns boolean of meeting completed or not.
     * @return boolean
     */
    function check_completion() {
        return parent::check_completion();
        # todo: there's probably some way to figure this out
    }

    /**
     * Cron
     * @return boolean
     */
    function cron() {
        return true;
    }
}

?>
