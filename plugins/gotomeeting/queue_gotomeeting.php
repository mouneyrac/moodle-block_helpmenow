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
 * Help me now gotomeeting queue class.
 *
 * @package     block_helpmenow
 * @copyright   2012 VLACS
 * @author      David Zaharee <dzaharee@vlacs.org>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(__FILE__))) . '/queue.php');

require_once(dirname(__FILE__) . '/user2plugin_gotomeeting.php');

class helpmenow_queue_gotomeeting extends helpmenow_queue {
    /**
     * plugin queue's meetings use
     * @var string $plugin
     */
    public $plugin = 'gotomeeting';

    /**
     * Overriding login to handle user accounts and tokens
     */
    public function login() {
        global $USER, $CFG;

        $token_url = $CFG->wwwroot . '/blocks/helpmenow/plugins/gotomeeting/token.php';
        # check if we have a user2plugin record
        if ($record = get_record('block_helpmenow_user2plugin', 'userid', $USER->id, 'plugin', 'gotomeeting')) {
            $user2plugin = new helpmenow_user2plugin_gotomeeting(null, $record);
        } else {
            $user2plugin = new helpmenow_user2plugin_gotomeeting();
            $user2plugin->userid = $USER->id;
            $user2plugin->insert();
            redirect($token_url);
        }
        # check to see if the oauth token has expired
        if ($user2plugin->token_expiration < time()) {
            redirect($token_url);
        }
        return parent::login();
    }
}

?>
