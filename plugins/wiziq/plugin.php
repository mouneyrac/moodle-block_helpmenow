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

    public static function display() {
        return '';
    }

    public static function on_login() {
        return true;
    }

    public static function api($method, $params) {
        global $CFG;

        $signature = array();
        $signature['access_key'] = $CFG->helpmenow_wiziq_access_key;
        $signature['timestamp'] = time();
        $signature['method'] = $method;
        $signature['signature'] = static::api_signature($signature);

        $params = array_merge($params, $signature);

        $ch = curl_init();
        $curl_setopt_array($ch, array(
            CURLOPT_POSTFIELDS => $params,
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

        return base64_encode(hash_hmac('sha1', $sig_base, $CFG->helpmenow_wiziq_secret_key));
    }
}

?>
