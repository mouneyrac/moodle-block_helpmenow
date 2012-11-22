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
 * ajax server
 *
 * @package     block_helpmenow
 * @copyright   2012 VLACS
 * @author      David Zaharee <dzaharee@vlacs.org>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

# capture output
ob_start();

require_once((dirname(dirname(dirname(__FILE__)))) . '/config.php');
require_once(dirname(__FILE__) . '/lib.php');

if (!isloggedin()) {
    ob_end_clean();
    header('HTTP/1.1 401 Unauthorized');
    die;
}

$requests = json_decode(file_get_contents('php://input'));
$responses = array();
ob_end_clean();
foreach ($requests->requests as $request) {
    ob_start();
    try {
        $response = (object) array(
            'id' => $request->id,
            'instanceId' => $request->instanceId
        );

        # verify session where applicable
        switch ($request->function) {
        case 'message':
        case 'refresh':
            if (!$session2user = get_record('block_helpmenow_session2user', 'userid', $USER->id, 'sessionid', $request->session)) {
                throw new Exception('Could not get session2user record');
            }
            break;
        }

        # generate response
        switch ($request->function) {
        case 'message':
            if (!helpmenow_message($request->session, $USER->id, stripslashes(clean_param($request->message, PARAM_TEXT)))) {
                throw new Exception('Could insert message record');
            }
            break;
        case 'refresh':
            # update session2user
            $session2user->last_refresh = time();
            $session2user->last_message = $request->last_message;
            if (!update_record('block_helpmenow_session2user', $session2user)) {
                throw new Exception('Could not update session2user record');
            }

            $response->html = '';
            $response->beep = false;    # don't beep by default (oh man, could you imagine?)

            # send info about users in the session so the client has display names
            # haven't moved to the client, so we don't need this yet
            # $response->users = helpmenow_get_session_users($request->session);

            # unread messages
            $messages = helpmenow_get_unread($request->session, $USER->id);

            # todo: move this to the client
            if ($messages) {
                $response->html .= helpmenow_format_messages($messages);

                # determine if we need to beep
                foreach ($messages as $m) {
                    if ($m->notify) {
                        $response->title_flash = format_string($m->message);
                        $response->beep = true;
                    }
                }
            } else {
                $sql = "
                    SELECT *
                    FROM {$CFG->prefix}block_helpmenow_message
                    WHERE id = (
                        SELECT max(id)
                        FROM {$CFG->prefix}block_helpmenow_message
                        WHERE sessionid = {$request->session}
                )";
                if ($last_message = get_record_sql($sql)) {
                    if (!is_null($last_message->userid) and $last_message->time < time() - 30) {
                        $message = 'Sent: '.userdate($last_message->time, '%r');    # todo: internationalize
                        $message_rec = (object) array(
                            'userid' => null,
                            'sessionid' => $request->session,
                            'time' => time(),
                            'message' => addslashes($message),
                            'notify' => 0,
                        );
                        if (!$id = insert_record('block_helpmenow_message', $message_rec)) {
                            debugging("Failed to insert \"sent: _time_\" message.");
                        }
                        $response->html .= "<div>$message</div>";
                    }
                }
            }
            $response->last_message = get_field_sql("
                SELECT max(id) FROM {$CFG->prefix}block_helpmenow_message WHERE sessionid = $request->session
            ");

            # call subplugin on_chat_refresh methods
            foreach (helpmenow_plugin::get_plugins() as $pluginname) {
                $class = "helpmenow_plugin_$pluginname";
                $class::on_chat_refresh($request, $response);
            }
            break;
        case 'block':
            # update our user lastaccess
            set_field('block_helpmenow_user', 'lastaccess', time(), 'userid', $USER->id);

            # datetime for debugging
            $response->last_refresh = 'Updated: '.userdate(time(), '%r');

            # clean up sessions
            helpmenow_clean_sessions();

            $response->pending = 0;
            $response->alert = false;

            # queues
            $response->queues_html = '';
            $connect = new moodle_url("$CFG->wwwroot/blocks/helpmenow/connect.php");
            $queues = helpmenow_queue::get_queues();
            foreach ($queues as $q) {
                $response->queues_html .= '<div>';
                switch ($q->get_privilege()) {
                case HELPMENOW_QUEUE_HELPEE:
                case HELPMENOW_QUEUE_HELPER:
                    $sql = "
                        SELECT s.*, m.message, m.id AS messageid
                        FROM {$CFG->prefix}block_helpmenow_session s
                        JOIN {$CFG->prefix}block_helpmenow_session2user s2u ON s2u.sessionid = s.id AND s2u.userid = s.createdby
                        JOIN {$CFG->prefix}block_helpmenow_message m ON m.id = (
                            SELECT MAX(id) FROM {$CFG->prefix}block_helpmenow_message m2 WHERE m2.sessionid = s.id AND m2.userid <> s.createdby
                        )
                        WHERE s.iscurrent = 1
                        AND s.createdby = $USER->id
                        AND s.queueid = $q->id
                        AND (s2u.last_refresh + 20) < ".time()."
                        AND s2u.last_refresh < m.time
                    ";
                    if ($session = get_record_sql($sql) or $q->is_open()) {
                        $connect->remove_params('sessionid');
                        $connect->param('queueid', $q->id);
                        $style = '';
                        if ($session) {
                            $response->pending++;
                            $style = ' style="background-color:yellow"';
                            $message = '<div style="margin-left: 1em;">' . $session->message . '</div>' . $message;
                            if (helpmenow_notify_once($s->messageid)) {
                                $response->alert = true;
                            }
                        }
                        $response->queues_html .= "<div$style>" . link_to_popup_window($connect->out(), "queue{$q->id}", $q->name, 400, 500, null, null, true) . "$message</div>";
                    } else {
                        $response->queues_html .= "<div>$q->name</div>";
                    }

                    if ($q->get_privilege() == HELPMENOW_QUEUE_HELPEE) {
                        break;
                    }

                    $instyle = $outstyle = '';
                    if ($q->helpers[$USER->id]->isloggedin) {
                        $outstyle = 'style="display: none;"';
                    } else {
                        $instyle = 'style="display: none;"';
                    }
                    $login_url = new moodle_url("$CFG->wwwroot/blocks/helpmenow/login.php");
                    $login_url->param('queueid', $q->id);
                    $login_url->param('login', 0);
                    $logout = link_to_popup_window($login_url->out(), "login", get_string('logout', 'block_helpmenow'), 400, 500, null, null, true);
                    $login_url->param('login', 1);
                    $login = link_to_popup_window($login_url->out(), "login", get_string('login', 'block_helpmenow'), 400, 500, null, null, true);
                    $logout_status = get_string('logout_status', 'block_helpmenow');

                    $response->queues_html .= <<<EOF
    <div style="text-align: center; font-size:small; margin-top:.5em; margin-bottom:.5em;">
        <div id="helpmenow_logged_in_div_$q->id" $instyle>$logout</div>
        <div id="helpmenow_logged_out_div_$q->id" $outstyle>$logout_status | $login</div>
    </div>
EOF;

                    # sessions
                    $sql = "
                        SELECT u.*, s.id AS sessionid, m.message, m.time, m.id AS messageid
                        FROM {$CFG->prefix}block_helpmenow_session s
                        JOIN {$CFG->prefix}user u ON u.id = s.createdby
                        JOIN {$CFG->prefix}block_helpmenow_message m ON m.id = (
                            SELECT MAX(id) FROM {$CFG->prefix}block_helpmenow_message m2 WHERE m2.sessionid = s.id AND m2.userid = s.createdby
                        )
                        WHERE s.queueid = $q->id
                        AND s.iscurrent = 1
                    ";
                    if (!$sessions = get_records_sql($sql)) {
                        break;
                    }
                    $response->queues_html .= '<div style="margin-left: 1em;">';

                    foreach ($sessions as &$s) {
                        $s->pending = true;
                        $sql = "
                            SELECT *
                            FROM {$CFG->prefix}block_helpmenow_session2user s2u
                            JOIN {$CFG->prefix}user u ON u.id = s2u.userid
                            WHERE s2u.sessionid = $s->sessionid
                            AND s2u.userid <> $s->id
                        ";
                        $s->helpers = get_records_sql($sql);
                        foreach ($s->helpers as $h) {
                            if ($s->pending) {
                                if (($h->last_refresh + 20) > time()) {
                                    $s->pending = false;
                                }
                                if (($h->last_refresh > $s->time)) {
                                    $s->pending = false;
                                }
                            }
                            if (!isset($s->helper_names)) {
                                $s->helper_names = fullname($h);
                            } else {
                                $s->helper_names .= ', ' . fullname($h);
                            }
                        }
                    }

                    unset($s);

                    # sort by unseen messages, lastname, firstname
                    usort($sessions, function($a, $b) {
                        if (!($a->pending xor $b->pending)) {
                            return strcmp(strtolower("$a->lastname $a->firstname"), strtolower("$b->lastname $b->firstname"));
                        }
                        return $a->pending ? -1 : 1;
                    });

                    foreach ($sessions as $s) {
                        $connect->remove_params('queueid');
                        $connect->param('sessionid', $s->sessionid);
                        $message = $style = '';
                        if ($s->pending) {
                            $response->pending++;
                            $style = ' style="background-color:yellow"';
                            $message .= '"'.$s->message.'"<br />';
                            if ($q->helpers[$USER->id]->isloggedin) {
                                if (helpmenow_notify_once($s->messageid)) {
                                    $response->alert = true;
                                }
                            }
                        }
                        if (isset($s->helper_names)) {
                            $message .= '<small>'.$s->helper_names.'</small><br />';
                        }
                        $message = '<div style="margin-left: 1em;">'.$message.'</div>';
                        $response->queues_html .= "<div$style>" . link_to_popup_window($connect->out(), $s->sessionid, fullname($s), 400, 500, null, null, true) . "$message</div>";
                    }
                    $response->queues_html .= '</div>';
                    break;
                }
                $desc_message = '<div style="margin-left: 1em; font-size: smaller;">' . $q->description . '</div>';
                $response->queues_html .= '</div>' . $desc_message . '<hr />';
            }

            # user lists for students and instructors
            $privilege = get_field('sis_user', 'privilege', 'sis_user_idstr', $USER->idnumber);
            switch ($privilege) {
            case 'TEACHER':
                $users = helpmenow_get_students();
                if ($admins = helpmenow_get_admins()) {
                    $users = array_merge($users, $admins);
                }
                $isloggedin = get_field('block_helpmenow_user', 'isloggedin', 'userid', $USER->id);
                $response->isloggedin = $isloggedin ? true : false;
                break;
            case 'STUDENT':
                $users = helpmenow_get_instructors();
                $isloggedin = true;
                break;
            default:
                break 2;
            }

            $cutoff = helpmenow_cutoff();

            # get any unseen messages
            foreach ($users as $u) {
                if ($privilege == 'STUDENT') {
                    $u->online = ($u->isloggedin and ($u->hmn_lastaccess > $cutoff));
                } else {
                    $u->online = true;
                }

                $sql = "
                    SELECT s.*, m.message, m.id AS messageid
                    FROM {$CFG->prefix}block_helpmenow_session2user s2u
                    JOIN {$CFG->prefix}block_helpmenow_session s ON s.id = s2u.sessionid
                    JOIN {$CFG->prefix}block_helpmenow_session2user s2u2 ON s2u2.sessionid = s.id AND s2u2.userid <> s2u.userid
                    JOIN {$CFG->prefix}block_helpmenow_message m ON m.id = (
                        SELECT MAX(id) FROM {$CFG->prefix}block_helpmenow_message m2 WHERE m2.sessionid = s.id AND m2.userid <> s2u.userid
                    )
                    WHERE s2u.userid = $USER->id
                    AND s.iscurrent = 1
                    AND s2u2.userid = $u->id
                    AND s.queueid IS NULL
                    AND (s2u.last_refresh + 20) < ".time()."
                    AND s2u.last_refresh < m.time
                ";
                if (!$session = get_record_sql($sql)) {
                    continue;
                }
                $u->sessionid = $session->id;
                $u->message = $session->message;
            }

            # sort by unseen messages, lastname, firstname
            usort($users, function($a, $b) use ($privilege)  {
                if (!(isset($a->sessionid) xor isset($b->sessionid))) {
                    if ($privilege == 'STUDENT') {      # students see offline teachers, therefor we should sort online/offline before alphabetical
                        if (($a->online) xor ($b->online)) {
                            return ($a->online) ? -1 : 1;
                        }
                    }
                    return strcmp(strtolower("$a->lastname $a->firstname"), strtolower("$b->lastname $b->firstname"));
                }
                return isset($a->sessionid) ? -1 : 1;
            });

            # build the list
            $response->users_html = '';
            $connect->remove_params('queueid');
            $connect->remove_params('sessionid');
            foreach ($users as $u) {
                if ($privilege == 'TEACHER') {
                    if (isset($u->isadmin) and $u->isadmin) {
                        if (!isset($u->sessionid)) {
                            continue;
                        }
                    }
                }
                $connect->param('userid', $u->id);
                $message = '';
                $style = 'margin-left: 1em;';
                if (isset($u->motd)) {
                    if ($u->online) {
                        $motd = $u->motd;
                    } else {
                        $motd = "(Offline)";
                    }
                    $message .= '<div style="font-size: smaller;">' . $motd . '</div>';
                }
                if (isset($u->sessionid)) {
                    $response->pending++;
                    $style .= 'background-color:yellow;';
                    $message .= '<div>' . $u->message . '</div>';
                    if (helpmenow_notify_once($s->messageid)) {
                        $response->alert = true;
                    }
                }
                $message = '<div style="margin-left: 1em;">'.$message.'</div>';
                if ($isloggedin and ($u->online)) {
                    $link = link_to_popup_window($connect->out(), $u->id, fullname($u), 400, 500, null, null, true);
                } else {
                    $link = fullname($u);
                }
                $response->users_html .= "<div style=\"$style\">".$link.$message."</div>";
            }
            break;
        case 'motd':
            if (!$helpmenow_user = get_record('block_helpmenow_user', 'userid', $USER->id)) {
                throw new Exception('No helpmenow_user record');
            }
            $helpmenow_user->motd = addslashes($request->motd);
            if (!update_record('block_helpmenow_user', $helpmenow_user)) {
                throw new Exception('Could not update user record');
            }
            $response->motd = $request->motd;
            break;
        case 'plugin':
            $plugin = $request->plugin;
            $class = "helpmenow_plugin_$plugin";
            $plugin_function = $request->plugin_function;
            if (!in_array($plugin, helpmenow_plugin::get_plugins())) {
                throw new Exception('Unknown plugin');
            }
            if (!in_array($plugin_function, $class::get_ajax_functions())) {
                throw new Exception('Unknown function');
            }
            $response = $plugin_function($request);
            break;
        default:
            throw new Exception('Unknown function');
        }
    } catch (Exception $e) {
        $response->error = $e->getMessage();
        // echo json_encode($response);
        // die;
    }
    $debugging = ob_get_clean();
    if (debugging()) {
        $response->debugging = $debugging;
    }
    $responses[] = $response;
}

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache');
header('Expires: '. gmdate('D, d M Y H:i:s', 0) .' GMT');
header('Pragma: no-cache');
echo json_encode($responses);

?>
