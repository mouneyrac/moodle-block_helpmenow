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
 * Help me now helper class.
 *
 * @package     block_helpmenow
 * @copyright   2012 VLACS
 * @author      David Zaharee <dzaharee@vlacs.org>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__) . '/db_object.php');

class helpmenow_helper extends helpmenow_db_object {
    const table = 'helper';

    /**
     * Array of required db fields.
     * @var array $required_fields
     */
    protected $required_fields = array(
        'id',
        'timecreated',
        'timemodified',
        'modifiedby',
        'plugin',
        'queueid',
        'userid',
        'isloggedin',
        'last_action',
        'last_refresh',
    );

    /**
     * Array of optional db fields.
     * @var array $optional_fields
     */
    protected $optional_fields = array(
        'meetingid',
    );

    /**
     * The queue the helper belongs to.
     * @var int $queueid
     */
    public $queueid;

    /**
     * The userid of the helper.
     * @var int $userid
     */
    public $userid;

    /**
     * timestamp/0, login status of helper
     * @var int $isloggedin
     */
    public $isloggedin = 0;

    /**
     * Timestamp of last action performed by the user.
     * @var int $last_action
     */
    public $last_action = 0;

    /**
     * Timestamp of last refresh of the the helper interface by user.
     * @var int $last_refresh
     */
    public $last_refresh = 0;

    /**
     * Meetingid of helper's persistant meeting
     * @var int $meetingid
     */
    public $meetingid;

    /**
     * Plugins can override this method to provide status information of helpers
     * @return boolean
     */
    public function is_busy() {
        return false;
    }

    /**
     * cleans up afk helpers
     * @return boolean
     */
    public final static function auto_logout() {
        global $CFG;
        $success = true;
        $cutoff = time() - ($CFG->helpmenow_helper_refresh_timeout * 60);
        if ($records = get_records_select('block_helpmenow_helper', "last_refresh < $cutoff")) {
            $helpers = helpmenow_helper::objects_from_records($records);
            foreach ($helpers as $h) {
                if ($h->isloggedin == 0) {
                    continue;
                }
                # log
                helpmenow_log($h->userid, 'auto_logged_out', "queueid: {$h->queueid}");

                $h->isloggedin = 0;
                $h->last_action = 0;
                $h->meetingid = 0;
                $success = $success and $h->update();
            }
        }
        return $success;
    }
}

?>
