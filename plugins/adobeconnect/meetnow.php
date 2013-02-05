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
 * This script invites users to adobe connect sessions and then redirects the
 * inviter as well.
 *
 * @package     block_helpmenow
 * @copyright   2012 VLACS
 * @author      David Zaharee <dzaharee@vlacs.org>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(dirname(dirname(__FILE__))))) . '/config.php');
require_once(dirname(__FILE__) . '/lib.php');

$username = required_param('username', PARAM_TEXT);
$url = "http://vlacs.adobeconnect.com/$username";

$heading = 'VLACS-Adobe Connect Redirector';
print_header($heading, $heading);

$me = qualified_me();

print <<<EOF
<div id="message"></div>
<script type="text/javascript">
<!--
if (navigator.userAgent.indexOf("Chrome") != -1) {
    document.getElementById('message').innerHTML = "<p>You are using Google's Chrome browser. We like it, too!</p><p>Unfortunately, Adobe Connect does not yet support Chrome, so we cannot connect you until you switch to a different browser.</p><p>Please open a different browser (Firefox, Safari, and Internet Explorer are recommended) and copy and paste the following link into that browser to continue: <a href=\"$me\">$me</a></p><p>If you need any help, please <a target=\"_blank\" href=\"http://helpdesk.vlacs.org\">contact our technical help desk</a> by email or phone.</p>";

} else {
    window.location = "$url";
}
//-->
</script>
EOF;

print_footer();

?>
