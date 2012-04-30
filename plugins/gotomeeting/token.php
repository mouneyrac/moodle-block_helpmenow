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
 * Handles getting OAuth tokens
 *
 * @package     block_helpmenow
 * @copyright   2012 VLACS
 * @author      David Zaharee <dzaharee@vlacs.org>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(dirname(dirname(__FILE__))))) . '/config.php');

require_once(dirname(__FILE__) . '/meeting.php');
require_once(dirname(__FILE__) . '/user2plugin.php');

define('HELPMENOW_G2M_API_KEY', '256d73eed85dc0b50f33562e654f6f02');    # todo: config option
define('HELPMENOW_G2M_OAUTH_EXCHANGE_URI', 'https://api.citrixonline.com/oauth/access_token');
define('HELPMENOW_G2M_OAUTH_AUTH_URI', 'https://api.citrixonline.com/oauth/authorize');

# require login

require_login(0, false);

# get our parameters

$code = optional_param('code', 0, PARAM_TEXT);
$redirect = optional_param('redirect', '', PARAM_TEXT);

$api_key = $CFG->helpmenow_g2m_key;

if ($code) {
    # set up exchanging our response key for an access token
    $citrix_url = HELPMENOW_G2M_OAUTH_EXCHANGE_URI;
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

    # do the exchange
    $ch = curl_init($citrix_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $oauth = json_decode(curl_exec($ch));
    $responsecode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    # save the reponse to user2plugin record
    $record = get_record('block_helpmenow_user2plugin', 'userid', $USER->id, 'plugin', 'gotomeeting');
    $user2plugin = new helpmenow_user2plugin_gotomeeting(null, $record);
    $user2plugin->access_token = $oauth->access_token;
    $user2plugin->token_expiration = $oauth->expires_in + time();
    $user2plugin->refresh_token = $oauth->refresh_token;
    $user2plugin->update();
    redirect($redirect);
}

$title = 'Token';   # todo: language string
$nav = array(array('name' => $title));
print_header($title, $title, build_navigation($nav));

$this_url = new moodle_url();
$this_url->param('redirect', $redirect);
$this_url = $this_url->out();
$citrix_url = HELPMENOW_G2M_OAUTH_AUTH_URI;
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
echo "<p><a href='$citrix_url'>Get an OAuth token</a></p>";     # todo: language string

print_footer();

?>
