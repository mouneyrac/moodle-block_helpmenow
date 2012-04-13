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
 * GoToMeeting helpmenow plugin
 *
 * @package     block_helpmenow
 * @copyright   2012 VLACS
 * @author      David Zaharee <dzaharee@vlacs.org>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(__FILE__))) . '/meeting.php');

define('HELPMENOW_G2M_REST_BASE_URI', 'https://api.citrixonline.com/G2M/rest/');

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
    function create() {
        $params = array(
            'subject' => 'foo',
            'starttime' => gmdate('Y-m-d\TH:i:s\Z', time() + (5*60)),
            'endtime' => gmdate('Y-m-d\TH:i:s\Z', time() + (60*60)),    # endtime of 1 hour from now, maybe a configuration option? (it might not matter)
            'passwordrequired' => 'false',
            'conferencecallinfo' => '',
            'timezonekey' => '',
            'meetingtype' => 'Immediate',
        );
        $data = helpmenow_meeting_gotomeeting::api('meetings', 'POST', $params);
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
    function connect() {
        return $this->join_url;
    }

    /**
     * Returns boolean of meeting completed or not.
     * @return boolean
     */
    function check_completion() {
        $attendees = json_decode(helpmenow_meeting_gotomeeting::api("$this->meetingid/attendees", 'GET'));
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
    function check_full() {
        return count($this->meeting2user) >= $this->max_participants;
    }

    /**
     * Cron
     * @return boolean
     */
    public static function cron() {
        return true;
    }

    /**
     * Handles g2m api calls
     * @param string $uri
     * @param string $verb POST, PUT, DELETE, GET
     * @param array $params
     * @return mixed
     */
    public static function api($uri, $verb, $params = array()) {
        global $CFG;

        $uri = HELPMENOW_G2M_REST_BASE_URI . $uri;
        $headers = array(
            "Accept: application/json",
            "Content-Type: application/json",
            "Authorization: OAuth oauth_token={$CFG->helpmenow_g2m_token}"
        );

        $ch = curl_init();
        switch ($verb) {
        case 'POST':
            # $headers[] = json_encode($params);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
        case 'PUT':
            # todo: we might not need this
            # todo: if we do, figure out how to do it
            # fall through here
        case 'DELETE':
            # todo: we might not need this either
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $verb);
            break;
        case 'GET':
            $fields = array();
            foreach ($params as $f => $v) {
                $fields[] = urlencode($f) . '=' . urlencode($v);
            }
            $fields = implode('&', $fields);
            $uri .= "?$fields";
            break;
        default:
            # todo: unkown verb error
        }
        curl_setopt_array($ch, array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => false,
            CURLOPT_HTTPHEADER => $headers,
            CURLINFO_HEADER_OUT => true,
            CURLOPT_URL => $uri,
        ));
        $data = curl_exec($ch);

        # todo: handle error codes
        $responsecode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        debugging("$uri $verb $responsecode");

        return json_decode($data);
    }
}

?>
