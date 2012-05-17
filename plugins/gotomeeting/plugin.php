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
 * GoToMeeting helpmenow plugin class
 *
 * @package     block_helpmenow
 * @copyright   2012 VLACS
 * @author      David Zaharee <dzaharee@vlacs.org>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(__FILE__))) . '/plugin.php');
require_once(dirname(__FILE__) . '/user2plugin.php');

define('HELPMENOW_G2M_REST_BASE_URI', 'https://api.citrixonline.com/G2M/rest/');

class helpmenow_plugin_gotomeeting extends helpmenow_plugin {
    /**
     * Plugin name
     * @var string $plugin
     */
    public $plugin = 'gotomeeting';

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
     * @param int $user user.id
     * @return mixed
     */
    public static function api($uri, $verb, $params = array(), $userid = null) {
        global $CFG, $USER;
        if (!isset($userid)) {
            $userid = $USER->id;
        }

        $uri = HELPMENOW_G2M_REST_BASE_URI . $uri;
        if (!$record = get_record('block_helpmenow_user2plugin', 'userid', $userid, 'plugin', 'gotomeeting')) {
            return false;
        }
        $user2plugin = new helpmenow_user2plugin_gotomeeting(null, $record);

        $headers = array(
            "Accept: application/json",
            "Content-Type: application/json",
            "Authorization: OAuth oauth_token={$user2plugin->access_token}"
        );

        $ch = curl_init();
        switch ($verb) {
        case 'POST':
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
            break;
        case 'PUT':
            # todo: we might not need this
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
        curl_close($ch);
        if ($responsecode === 403) {
            if (($USER->id !== $userid) or ($USER->id === get_admin()->id)) {   # call for different user or cron
                # todo: we need a way to handle getting a new oauth token for cron
                return false;
            }
            $token_url = new moodle_url("$CFG->wwwroot/blocks/helpmenow/plugins/gotomeeting/token.php");
            $token_url->param('redirect', qualified_me());
            $token_url = $token_url->out();
            redirect($token_url);
        }

        return json_decode($data);
    }
}

?>
