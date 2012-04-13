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
 * Hacked together script to get our oauth token
 *
 * @package     block_helpmenow
 * @copyright   2012 VLACS
 * @author      David Zaharee <dzaharee@vlacs.org>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(dirname(dirname(__FILE__))))) . '/config.php');

require_once(dirname(__FILE__) . '/meeting_gotomeeting.php');

# require login

require_login(0, false);

# get our parameters

$code = optional_param('code', 0, PARAM_TEXT);

$nav = array(array('name' => 'Token'));
print_header($title, $title, build_navigation($nav));

$api_key = '256d73eed85dc0b50f33562e654f6f02';

if ($code) {
    $citrix_url = 'https://api.citrixonline.com/oauth/access_token';
    $params = array(
        'grant_type' => 'authorization_code',
        'code' => $code,
        'client_id' => $api_key,
    );
    $fields = array();
    foreach ($params as $f => $v) {
        $fields[] = urlencode($f) . '=' . urlencode($v);
    }
    $fields = implode('&', $fields);
    $citrix_url .= "?$fields";

    $ch = curl_init($citrix_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $oauth = json_decode(curl_exec($ch));
    $responsecode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    print_object($oauth);
    print_object($responsecode);
} else {
    $this_url = new moodle_url();
    $this_url = urlencode($this_url->out());
    $citrix_url = 'https://api.citrixonline.com/oauth/authorize';
    $params = array(
        'client_id' => $api_key,
        'redirect_uri' => $this_url,
    );
    $fields = array();
    foreach ($params as $f => $v) {
        $fields[] = urlencode($f) . '=' . urlencode($v);
    }
    $fields = implode('&', $fields);
    $citrix_url .= "?$fields";
    echo "<p><a href='$citrix_url'>Get an OAuth token</a></p>";
}

print_footer();

?>
