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
 * GoToMeeting helpmenow meeting class
 *
 * @package     block_helpmenow
 * @copyright   2012 VLACS
 * @author      David Zaharee <dzaharee@vlacs.org>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(__FILE__))) . '/meeting.php');
require_once(dirname(__FILE__) . '/plugin_gotomeeting.php');

class helpmenow_meeting_gotomeeting extends helpmenow_meeting {
    /**
     * Plugin name
     * @var string $plugin
     */
    public $plugin = 'gotomeeting';

    /**
     * Extra fields
     * @var array $extra_fields
     */
    protected $extra_fields = array(
        'join_url',
        'max_participants',
        'unique_meetingid',
        'meetingid',
    );

    /**
     * GoToMeeting joinURL
     * @var string $join_url
     */
    public $join_url;

    /**
     * GoToMeeting maxParticipants
     * @var int $max_participants
     */
    public $max_participants;

    /**
     * GoToMeeting uniquemeetingid
     * @var int $unique_meetingid
     */
    public $unique_meetingid;

    /**
     * GoToMeeting meetingid
     * @var int $meetingid
     */
    public $meetingid;

    /**
     * Create the meeting. Caller will insert record.
     */
    public function create() {
        $params = array(
            'subject' => 'foo',
            'starttime' => gmdate('Y-m-d\TH:i:s\Z', time() + (5*60)),
            'endtime' => gmdate('Y-m-d\TH:i:s\Z', time() + (60*60)),    # endtime of 1 hour from now, maybe a configuration option? (it might not matter)
            'passwordrequired' => 'false',
            'conferencecallinfo' => '',
            'timezonekey' => '',
            'meetingtype' => 'Immediate',
        );
        $data = helpmenow_plugin_gotomeeting::api('meetings', 'POST', $params);
        $data = reset($data);
        print_object($data);
        $this->join_url = $data->joinURL;
        $this->max_participants = $data->maxParticipants;
        $this->unique_meetingid = $data->uniqueMeetingId;
        $this->meetingid = $data->meetingid;
        return true;
    }

    /**
     * Plugin specific function to connect USER to meeting. Caller will insert
     * into db after
     * @return $string url
     */
    public function connect() {
        return $this->join_url;
    }

    /**
     * Returns boolean of meeting completed or not.
     * @return boolean
     */
    public function check_completion() {
        $attendees = helpmenow_plugin_gotomeeting::api("$this->meetingid/attendees", 'GET');
        foreach ($attendees as $a) {
            if (!isset($a->endTime)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Return boolean of meeting full or not.
     * @return boolean
     */
    public function check_full() {
        return count($this->meeting2user) >= $this->max_participants;
    }
}

?>
