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
 * wiziq helpmenow plugin class
 *
 * @package     block_helpmenow
 * @copyright   2012 VLACS
 * @author      David Zaharee <dzaharee@vlacs.org>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(__FILE__))) . '/plugin.php');
require_once(dirname(__FILE__) . '/user2plugin.php');

define('HELPMENOW_WIZIQ_API_URL', 'http://class.api.wiziq.com/');

class helpmenow_plugin_wiziq extends helpmenow_plugin {
    /**
     * Plugin name
     * @var string $plugin
     */
    public $plugin = 'wiziq';

    /**
     * Cron
     * @return boolean
     */
    public static function cron() {
        return true;
    }

    public static function display($privileged = false) {
        if ($privileged) {
            return '<a href="javascript:void(0)" onclick="helpmenow_wiziq_invite();">Invite To My WizIQ</a>';
        }
        return '';
    }

    public static function on_login() {
        global $CFG, $USER;

        $user2plugin = helpmenow_user2plugin_wiziq::get_user2plugin();
        # if we don't have a user2plugin record or we don't have a current meeting for the user, redirect to the create meeting script
        if (!$user2plugin) {
            $user2plugin = new helpmenow_user2plugin_wiziq();
            $user2plugin->userid = $USER->id;
            $user2plugin->insert();
        }
        if (!isset($user2plugin->class_id)) {
            $user2plugin->create_meeting();
            $user2plugin->update();
            return $user2plugin->presenter_url;
        }
        return true;
    }

    public static function on_logout() {
        global $CFG, $USER;

        $user2plugin = helpmenow_user2plugin_wiziq::get_user2plugin();

        # see if the user is still logged in to a different queue/office
        $sql = "
            SELECT 1
            WHERE EXISTS (
                SELECT 1
                FROM {$CFG->prefix}block_helpmenow_helper
                WHERE userid = $USER->id
                AND isloggedin <> 0
            )
            OR EXISTS (
                SELECT 1
                FROM {$CFG->prefix}block_helpmenow_user
                WHERE userid = $USER->id
                AND isloggedin <> 0
            )
        ";
        # if they aren't, delete the meeting info from user2plugin record and update the db
        if (!record_exists_sql($sql)) {
            foreach (array('class_id', 'presenter_url', 'duration', 'timecreated') as $attribute) {
                unset($user2plugin->$attribute);
            }
            return $user2plugin->update();
        }
        return true;
    }

    /**
     * returns array of valid plugin ajax methods
     * @return array
     */
    public static function get_ajax_methods() {
        return array('invite');
    }

    /**
     * ajax method for inviting users to wiziq session
     * @param object $request ajax request
     * @return object
     */
    public static function invite($request) {
        global $USER, $CFG;

        # verify sesion
        if (!helpmenow_verify_session($request->session)) {
            throw new Exception('Invalid session');
        }

        if (!$u2p_rec = get_record('block_helpmenow_user2plugin', 'userid', $USER->id, 'plugin', 'wiziq')) {
            throw new Exception('No u2p record');
        }
        $user2plugin = new helpmenow_user2plugin_wiziq(null, $u2p_rec);

        if ($s2p_rec = get_record('block_helpmenow_s2p', 'sessionid', $request->session, 'plugin', 'wiziq')) {
            $s2p = new helpmenow_session2plugin_wiziq(null, $s2p_rec);
        } else {
            $s2p = new helpmenow_session2plugin_wiziq(null, (object) array('sessionid' => $request->session));
            $s2p->insert();
        }
        if (!in_array($user2plugin->class_id, $s2p->classes)) {
            $s2p->classes[] = $user2plugin->class_id;
            $s2p->update();
        }

        $join_url = new moodle_url("$CFG->wwwroot/blocks/helpmenow/plugins/wiziq/join.php");
        $join_url->param('classid', $user2plugin->class_id);
        $join_url->param('sessionid', $request->session);
        $join_url = $join_url->out();

        $message = fullname($USER) . ' has invited you to WizIQ, <a target="_blank" href="'.$join_url.'">click here</a> to join.';
        $message_rec = (object) array(
            'userid' => get_admin()->id,
            'sessionid' => $request->session,
            'time' => time(),
            'message' => addslashes($message),
        );
        insert_record('block_helpmenow_message', $message_rec);

        return new stdClass;
    }


    public static function add_attendee($class_id) {
        global $USER;

        $attendee_list = new SimpleXMLElement('<attendee_list/>');
        $attendee = $attendee_list->addChild('attendee');
        $attendee->addChild('attendee_id', $USER->id);
        $attendee->addChild('screen_name', fullname($USER));
        $attendee->addChild('language_culture_name', 'en-us');

        $params = array(
            'class_id' => $class_id,
            'attendee_list' => $attendee_list->asXML(),
        );
        return static::api('add_attendees', $params);
    }

    public static function api($method, $params) {
        global $CFG;

        $signature = array();
        $signature['access_key'] = $CFG->helpmenow_wiziq_access_key;
        $signature['timestamp'] = time();
        $signature['method'] = $method;
        $signature['signature'] = static::api_signature($signature);

        $params = array_merge($signature, $params);

        if (debugging()) {
            print_object($params);
        }

        $ch = curl_init();
        curl_setopt_array($ch, array(
            CURLOPT_POSTFIELDS => http_build_query($params, '', '&'),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => false,
            CURLINFO_HEADER_OUT => true,
            CURLOPT_URL => HELPMENOW_WIZIQ_API_URL . "?method=$method",
        ));
        $response = curl_exec($ch);
        curl_close($ch);

        if (debugging()) {
            print_object($response);
        }

        return new SimpleXMLElement($response);
    }

    private static function api_signature($sig_params) {
        global $CFG;

        $sig_base = array();
        foreach ($sig_params as $f => $v) {
            $sig_base[] = "$f=$v";
        }
        $sig_base = implode('&', $sig_base);

        if (debugging()) {
            print_object($sig_params);
            print_object($sig_base);
        }

        return base64_encode(static::wiziq_hmacsha1(urlencode($CFG->helpmenow_wiziq_secret_key), $sig_base));
    }

    /**
     * using wiziq's "hmac_sha1" function, as it doesn't match php's hash_hmac
     */
    private static function wiziq_hmacsha1($key, $data) {
        $blocksize = 64;
        $hashfunc = 'sha1';
        if (strlen($key)>$blocksize) {
            $key = pack('H*', $hashfunc($key));
        }
        $key = str_pad($key, $blocksize,chr(0x00));
        $ipad = str_repeat(chr(0x36), $blocksize);
        $opad = str_repeat(chr(0x5c), $blocksize);
        $hmac = pack(
                    'H*',$hashfunc(
                        ($key^$opad).pack(
                            'H*',$hashfunc(
                                ($key^$ipad).$data
                            )
                        )
                    )
                );
        return $hmac;
    }
}

?>
