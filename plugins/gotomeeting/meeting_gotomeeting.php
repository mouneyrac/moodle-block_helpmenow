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
define('HELPMENOW_G2M_JOIN_URL', 'http://www.gotomeeting.com/join');

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
        'g2m_meetingid',
    );

    /**
     * GoToMeeting meetingid
     * @var int $g2m_meetingid
     */
    public $g2m_meetingid;

    /**
     * Create the meeting. Caller will insert record.
     */
    function create() {
        $params = array(
            'subject' => $this->description,
            'starttime' => gmdate('Y-m-d\TH:M:S\Z'),
            'endtime' => gmdate('Y-m-d\TH:M:S\Z', time() + (60*60)),    # endtime of 1 hour from now, maybe a configuration option? (it might not matter)
            'passwordrequired' => false,
            'conferencecallinfo' => '',
            'timeZoneKey' => '',
            'meetingType' => 'Immediate',
        );
        $this->g2m_meetingid = helpmenow_meeting_gotomeeting::api('meetings', 'POST', $params);
        return true;
    }

    /**
     * Plugin specific function to connect USER to meeting. Caller will insert
     * into db after
     * @return $string url
     */
    function connect() {
        $meeting_url = new moodle_url(HELPMENOW_G2M_JOIN_URL);
        $meeting_url->param('MeetingID', $this->g2m_meetingid);
        return $meeting_url->out();
    }

    /**
     * Returns boolean of meeting completed or not.
     * @return boolean
     */
    function check_completion() {
        # todo: there's probably some way to figure this out, probably via get
        #   attendees by meeting api call
        return parent::check_completion();
    }

    /**
     * Return boolean of meeting full or not.
     * @return boolean
     */
    function check_full() {
        # todo: see if there is a limit to the number of participants (15/25 global?)
        return false;
    }

    /**
     * Cron
     * @return boolean
     */
    function cron() {
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
        # todo: oauth
        $uri = HELPMENOW_G2M_REST_BASE_URI . $uri;

        $ch = curl_init();
        switch ($verb) {
        case 'PUT':
            # todo: we might not need this
            # todo: if we do, figure out how to do it
            # fall through here
        case 'DELETE':
            # todo: we might not need this either
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $verb);
            break;
        case 'POST':
            curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
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
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_URL, $uri);
        $data = curl_exec($ch);

        # todo: handle error codes

        return $data;
    }
}

?>
