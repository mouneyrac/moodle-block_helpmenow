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
 * This script handles the main help me now interface.
 *
 * @package     block_helpmenow
 * @copyright   2012 VLACS
 * @author      David Zaharee <dzaharee@vlacs.org>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

# moodle stuff
require_once((dirname(dirname(dirname(__FILE__)))) . '/config.php');

# helpmenow library
require_once(dirname(__FILE__) . '/lib.php');

# require login
require_login(0, false);

# get paramters
$active = optional_param('active', 0, PARAM_INT);

# check for helper
if (!record_exists('block_helpmenow_helper', 'userid', $USER->id)) {
    helpmenow_fatal_error(get_string('permission_error', 'block_helpmenow'));
}

$this_url = new moodle_url();
$refresh_url = $this_url->out();

# title, navbar, and a nice box
$title = get_string('helpmenow', 'block_helpmenow');
$nav = array(array('name' => $title));
$refresh = "<meta http-equiv=\"refresh\" content=\"{$CFG->helpmenow_helper_refresh_rate}; url=$refresh_url\" />";
print_header($title, $title, build_navigation($nav), '', $refresh);
print_box_start('generalbox');

$warning = array();
$grab_attention = false;
$output = '';
$queues = helpmenow_queue::get_queues_by_user();
foreach ($queues as $q) {
    $pending_request = false;
    $output .= print_box_start('generalbox', '', true);
    $output .= print_heading($q->name, '', 2, 'main', true);

    # see if we have any pending requests and handle afk helpers
    foreach ($q->request as $r) {
        if (isset($r->meetingid)) {
            continue;
        }
        $pending_request = true;
        $grab_attention = true;

        # keeping track of activity/logging out helpers who are afk
        if ($q->helper[$USER->id]->isloggedin == 1) {
            if (($q->helper[$USER->id]->last_action == 0) or $q->helper[$USER->id]->is_busy() or $active) {
                $q->helper[$USER->id]->last_action = time();
            } else if ($q->helper[$USER->id]->last_action < (time() - ($CFG->helpmenow_helper_activity_timeout * 60))) {
                # log
                helpmenow_log($USER->id, 'auto_logged_out', "queueid: {$q->id}");

                $q->logout();
            } else if ($q->helper[$USER->id]->last_action < (time() - ($CFG->helpmenow_helper_activity_warning * 60))) {
                $warning[] = $q->name;
            }
        }
        break;
    }

    # log in/out dialogs
    $login = new moodle_url("$CFG->wwwroot/blocks/helpmenow/login.php");
    $login->param('queueid', $q->id);
    if ($q->helper[$USER->id]->isloggedin) {
        $login->param('login', 0);
        $login_status = get_string('loggedin', 'block_helpmenow');
        $login_text = get_string('logout', 'block_helpmenow');
    } else {
        $login->param('login', 1);
        $login_status = get_string('loggedout', 'block_helpmenow');
        $login_text = get_string('login', 'block_helpmenow');
    }
    $login = $login->out();
    $output .= "<p align='center'>$login_status <a href='$login'>$login_text</a></p>";

    # display requests, if any
    if ($pending_request) {
        $output .= "<ul>";
        # requests; these are in ascending order already
        foreach ($q->request as $r) {
            # if a request has a meetingid, another helper has already answered
            if (isset($r->meetingid)) {
                continue;
            }

            $connect = new moodle_url("$CFG->wwwroot/blocks/helpmenow/connect.php");
            $connect->param('requestid', $r->id);
            $connect->param('connect', 1);
            $name = fullname(get_record('user', 'id', $r->userid));
            $output .= "<li>" . link_to_popup_window($connect->out(), 'meeting', $name, 400, 700, null, null, true) . ", " .
                userdate($r->timecreated) . ":<br />";
            $output .= "<b>" . $r->description . "</b></li>";
        }
        $output .= "</ul>";
    }

    # current meetings
    if (count($q->meeting)) {
        $output .= print_box_start('generalbox', '', true) . "<p align='center'>" . get_string('meetings', 'block_helpmenow') . "</p><ul>";
        foreach ($q->meeting as $m) {
            $connect = new moodle_url("$CFG->wwwroot/blocks/helpmenow/connect.php");
            $connect->param('meetingid', $m->id);
            $name = fullname(get_record('user', 'id', $m->owner_userid));
            $output .= "<li>" . link_to_popup_window($connect->out(), 'meeting', $name, 400, 700, null, null, true) . ", " .
                userdate($m->timecreated) . ":<br />";
            $output .= "<b>" . $m->description . "</b></li>";
        }
        $output .= "</ul>" . print_box_end(true);
    }

    # helpers
    $logged_in_helpers = array();
    foreach ($q->helper as $h) {
        if ($h->isloggedin) {
            $logged_in_helpers[] = fullname(get_record('user', 'id', $h->userid));
        }
    }
    $helpers_message = get_string('logged_in_helpers', 'block_helpmenow');
    if (count($logged_in_helpers)) {
        $helpers_message .= implode(', ', $logged_in_helpers);
    } else {
        $helpers_message .= get_string('none', 'block_helpmenow');
    }
    $output .= print_box($helpers_message, 'generalbox', '', true);

    $output .= print_box_end(true);

    # record that the helper has loaded the page
    $q->helper[$USER->id]->last_refresh = time();
    $q->helper[$USER->id]->update();
}

# play a sound and try to get focus
if ($grab_attention) {
    $soundfile = $CFG->wwwroot . '/blocks/helpmenow/cowbell.wav';
    $output .= <<<EOF
<script type='text/javascript'>
    window.focus();
</script>
<embed src="$soundfile" autostart="true" width="0" height="0" id="chime" enablejavascript="true" />
EOF;
}

# print the log out warning if necessary
if (count($warning) !== 0) {
    $this_url->param('active', 1);
    $this_url = $this_url->out();
    $inactive_link = get_string('inactive_link', 'block_helpmenow');
    $warning_message = print_box_start('generalbox', '', true) . "<p align='center'>" .
        get_string('inactive_message', 'block_helpmenow') . "</p><p align='center'>" .
        implode(', ', $warning) . "</p><p align='center'><a href='$this_url'>$inactive_link</a></p>" .
        print_box_end(true);
    $output = $warning_message . $output;
}

echo $output;

print_box_end();

# footer
//print_footer();

?>
