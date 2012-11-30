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
 * Core help me now library.
 *
 * @package     block_helpmenow
 * @copyright   2012 VLACS
 * @author      David Zaharee <dzaharee@vlacs.org>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__) . '/db/access.php');
require_once(dirname(__FILE__) . '/form.php');

/**
 * Some defines for queue privileges. This is disjoint from capabilities, as
 * a user can have the helper cap but still needs to be added as a helper to
 * the appropriate queue.
 */
define('HELPMENOW_QUEUE_HELPER', 'helper');
define('HELPMENOW_QUEUE_HELPEE', 'helpee');
define('HELPMENOW_NOT_PRIVILEGED', 'notprivileged');

/**
 * Defines for email sending. This will probably be settings in the future
 */
define('HELPMENOW_EMAIL_EARLYCUTOFF', 30 * 60);     # earliest missed message should be 30+ minutes ago
define('HELPMENOW_EMAIL_LATECUTOFF', 10 * 60);      # latest missed message should be 10+ minutes ago

/**
 * defines for our javascript client version, so we only have to change one thing
 */
define('HELPMENOW_CLIENT_VERSION', 2012112600);

function helpmenow_verify_session($session) {
    global $CFG, $USER;
    $sql = "
        SELECT 1
        FROM {$CFG->prefix}block_helpmenow_session s
        JOIN {$CFG->prefix}block_helpmenow_session2user s2u ON s2u.sessionid = s.id
        WHERE s2u.userid = $USER->id
        AND s.id = $session
    ";
    return record_exists_sql($sql);
}

function helpmenow_check_privileged($session) {
    global $USER, $CFG;

    if (isset($session->queueid)) {
        $sql = "
            SELECT 1
            FROM {$CFG->prefix}block_helpmenow_queue q
            JOIN {$CFG->prefix}block_helpmenow_helper h ON h.queueid = q.id
            WHERE q.id = $session->queueid
            AND h.userid = $USER->id
        ";
        if (record_exists_sql($sql)) {
            return true;
        }
    } else if (get_field('sis_user', 'privilege', 'sis_user_idstr', $USER->idnumber) == 'TEACHER') {    #todo: change this to a capability
        return true;
    } else if (get_field('sis_user', 'privilege', 'sis_user_idstr', $USER->idnumber) == 'ADMIN') {    #todo: change this to a capability
        return true;
    }
    return false;
}

function helpmenow_get_students() {
    global $CFG, $USER;
    $cutoff = helpmenow_cutoff();
    $sql = "
        SELECT u.*, hu.lastaccess AS hmn_lastaccess
        FROM {$CFG->prefix}classroom c
        JOIN {$CFG->prefix}classroom_enrolment ce ON ce.classroom_idstr = c.classroom_idstr
        JOIN {$CFG->prefix}user u ON u.idnumber = ce.sis_user_idstr
        JOIN {$CFG->prefix}block_helpmenow_user hu ON hu.userid = u.id
        WHERE c.sis_user_idstr = '$USER->idnumber'
        AND ce.status_idstr = 'ACTIVE'
        AND ce.activation_status_idstr IN ('ENABLED', 'CONTACT_INSTRUCTOR')
        AND ce.iscurrent = 1
        AND hu.lastaccess > $cutoff
    ";
    return get_records_sql($sql);
}

function helpmenow_get_admins() {
    global $CFG, $USER;
    $cutoff = helpmenow_cutoff();
    $sql = "
        SELECT u.*, 1 AS isadmin, hu.lastaccess AS hmn_lastaccess
        FROM {$CFG->prefix}sis_user su
        JOIN {$CFG->prefix}user u ON u.idnumber = su.sis_user_idstr
        JOIN {$CFG->prefix}block_helpmenow_user hu ON hu.userid = u.id
        WHERE su.privilege = 'ADMIN'
        AND hu.lastaccess > $cutoff
    ";
    return get_records_sql($sql);
}

function helpmenow_get_instructors() {
    global $CFG, $USER;
    $cutoff = helpmenow_cutoff();
    $sql = "
        SELECT u.*, hu.isloggedin, hu.motd, hu.lastaccess AS hmn_lastaccess
        FROM {$CFG->prefix}classroom_enrolment ce
        JOIN {$CFG->prefix}classroom c ON c.classroom_idstr = ce.classroom_idstr
        JOIN {$CFG->prefix}user u ON c.sis_user_idstr = u.idnumber
        JOIN {$CFG->prefix}block_helpmenow_user hu ON hu.userid = u.id
        WHERE ce.sis_user_idstr = '$USER->idnumber'
        AND ce.status_idstr = 'ACTIVE'
        AND ce.activation_status_idstr IN ('ENABLED', 'CONTACT_INSTRUCTOR')
        AND ce.iscurrent = 1
    ";
    return get_records_sql($sql);
}

function helpmenow_cutoff() {
    global $CFG;
    if (isset($CFG->helpmenow_no_cutoff) and $CFG->helpmenow_no_cutoff) {    # set this to true to see everyone
        return 0;
    }
    return time() - 300;
}

function helpmenow_add_user($userid, $sessionid, $last_refresh = 0) {
    $session2user_rec = (object) array(
        'sessionid' => $sessionid,
        'userid' => $userid,
        'last_refresh' => $last_refresh,
    );
    return insert_record('block_helpmenow_session2user', $session2user_rec);
}

/**
 * prints an error and ends execution
 * @param string $message message to be printed
 * @param bool $print_header print generic helpmenow header or not
 */
function helpmenow_fatal_error($message, $print_header = true, $close = false) {
    if ($print_header) {
        $title = get_string('helpmenow', 'block_helpmenow');
        $nav = array(array('name' => $title));
        print_header($title, $title, build_navigation($nav));
        print_box($message);
        print_footer();
    } else {
        echo $message;
    }
    if ($close and !debugging()) {
        echo "<script type=\"text/javascript\">close();</script>";
    }
    die;
}

/**
 * ensures users have a helpmenow_user record
 */
function helpmenow_ensure_user_exists() {
    global $USER;
    if (record_exists('block_helpmenow_user', 'userid', $USER->id)) {
        return;
    }

    $helpmenow_user = (object) array(
        'userid' => $USER->id,
        'isloggedin' => 0,
        'motd' => '',
    );

    insert_record('block_helpmenow_user', $helpmenow_user);
}

/**
 * inserts a message into block_helpmenow_log
 * @param int $userid user performing action
 * @param string $action action user is performing
 * @param string $details details of the action
 */
function helpmenow_log($userid, $action, $details) {
    $new_record = (object) array(
        'userid' => $userid,
        'action' => $action,
        'details' => $details,
        'timecreated' => time(),
    );
    insert_record('block_helpmenow_log', $new_record);
}

function helpmenow_clean_sessions($all = false) {
    global $CFG, $USER;

    $sql = "";
    if (!$all) {
        $sql = "JOIN {$CFG->prefix}block_helpmenow_session2user s2u ON s2u.sessionid = s.id AND s2u.userid = $USER->id";
    }
    $sql = "
        SELECT s.*
        FROM {$CFG->prefix}block_helpmenow_session s
        $sql
        WHERE s.iscurrent = 1
        ";
    if ($sessions = get_records_sql($sql)) {
        foreach ($sessions as $s) {
            if (!is_null($s->queueid)) {    # queue specific
                # if there are any messages that no helpers have seen, this isn't old
                $sql = "
                    SELECT 1
                    FROM {$CFG->prefix}block_helpmenow_message m
                    WHERE sessionid = $s->id
                    AND notify = 1
                    AND time > (
                        SELECT max(last_refresh)
                        FROM {$CFG->prefix}block_helpmenow_session2user
                        WHERE sessionid = $s->id
                        AND userid <> $s->createdby
                    )
                ";
                if (record_exists_sql($sql)) {
                    continue;
                }
            }
            $session_users = get_records('block_helpmenow_session2user', 'sessionid', $s->id);
            foreach ($session_users as $su) {
                if (!is_null($s->queueid) and $s->createdby == $su->userid and count($session_users) > 1) {
                    continue;
                }
                if (($su->last_refresh + 60) > time()) {
                    continue 2;
                }
            }
            set_field('block_helpmenow_session', 'iscurrent', 0, 'id', $s->id);
        }
    }
}

function helpmenow_autologout_helpers() {
    global $CFG;

    $cutoff = helpmenow_cutoff();
    $sql = "
        SELECT h.*, hu.lastaccess AS lastaccess
        FROM {$CFG->prefix}block_helpmenow_helper h
        JOIN {$CFG->prefix}block_helpmenow_user hu ON hu.userid = h.userid
        WHERE h.isloggedin <> 0
        AND hu.lastaccess < $cutoff
        ";
    if (!$helpers = get_records_sql($sql)) {
        return true;
    }

    $success = true;
    foreach ($helpers as $h) {
        $duration = time() - $h->isloggedin;
        helpmenow_log($h->userid, 'maybe_auto_logged_out', "queueid: $h->queueid, duration: $duration, cutoff: $cutoff, lastaccess: {$h->lastaccess}");
        /*
        $h->isloggedin = 0;
        $success = $success and update_record('block_helpmenow_helper', $h);
         */
    }

    return $success;
}

function helpmenow_autologout_users() {
    global $CFG;

    $cutoff = helpmenow_cutoff();
    $sql = "
        SELECT hu.*
        FROM {$CFG->prefix}block_helpmenow_user hu
        WHERE hu.isloggedin <> 0
        AND hu.lastaccess < $cutoff
        ";
    if (!$users = get_records_sql($sql)) {
        return true;
    }

    $success = true;
    foreach ($users as $u) {
        $duration = time() - $u->isloggedin;
        helpmenow_log($u->userid, 'maybe_auto_logged_out', "duration: $duration, cutoff: $cutoff, lastaccess: {$u->lastaccess}");
        /*
        $u->isloggedin = 0;
        $success = $success and update_record('block_helpmenow_user', addslashes_recursive($u));
         */
    }

    return $success;
}

/**
 * prints hallway lists
 * @param array $users array of users
 */
function helpmenow_print_hallway($users) {
    global $CFG;
    static $admin;
    if (!isset($admin)) {
        $admin = has_capability(HELPMENOW_CAP_MANAGE, get_context_instance(CONTEXT_SYSTEM, SITEID));
    }
    # start setting up the table
    # todo: plugin abstraction
    $head = array(
        get_string('name'),
        get_string('motd', 'block_helpmenow'),
        get_string('loggedin', 'block_helpmenow'),
    );
    if ($admin) {
        $head = array_merge($head, array(
            'GoToMeeting',
            'WizIQ',
        ));
    };
    $table = (object) array(
        'head' => $head,
        'data' => array(),
    );

    usort($users, function($a, $b) {
        if (!($a->isloggedin xor $b->isloggedin)) {
            return strcmp(strtolower("$a->lastname $a->firstname"), strtolower("$b->lastname $b->firstname"));
        }
        return $a->isloggedin ? -1 : 1;
    });

    $na = get_string('na', 'block_helpmenow');
    $yes = get_string('yes');
    $no = get_string('no');
    $not_found = get_string('not_found', 'block_helpmenow');
    $wander = get_string('wander', 'block_helpmenow');

    foreach ($users as $u) {
        $name = fullname($u);
        if ($admin and $u->isloggedin) {
            $connect = new moodle_url("$CFG->wwwroot/blocks/helpmenow/connect.php");
            $connect->param('userid', $u->id);
            $name = link_to_popup_window($connect->out(), $u->id, $name, 400, 500, null, null, true);
        }
        $row = array(
            $name,
            isset($u->motd) ? $u->motd : $na,
        );

        if ($u->isloggedin) {
            $row[] = $yes;

            if ($admin) {
#                # gtm
#                if (!$user2plugin = get_record('block_helpmenow_user2plugin', 'userid', $u->userid, 'plugin', 'gotomeeting')) {
#                    $row[] = $not_found;
#                } else {
#                    $user2plugin = new helpmenow_user2plugin_gotomeeting(null, $user2plugin);
#                    $row[] = "<a href=\"$user2plugin->join_url\" target=\"_blank\">$wander</a>";
#                }

                # wiziq
                if (!$wiziq_u2p = get_record('block_helpmenow_user2plugin', 'userid', $u->userid, 'plugin', 'wiziq')) {
                    $row[] = $not_found;
                } else {
                    $wiziq_u2p = new helpmenow_user2plugin_wiziq(null, $wiziq_u2p);
                    if (isset($wiziq_u2p->class_id)) {
                        $join_url = new moodle_url("$CFG->wwwroot/blocks/helpmenow/plugins/wiziq/join.php");
                        $join_url->param('classid', $wiziq_u2p->class_id);
                        $join_url = $join_url->out();
                        $row[] = "<a href=\"$join_url\" target=\"_blank\">$wander</a>";
                    } else {
                        $row[] = $not_found;
                    }
                }
            }
        } else {
            $row[] = $no;
            if ($admin) {
                $row[] = $na;
                $row[] = $na;
            }
        }
        $table->data[] = $row;
    }

    print_table($table);
}

function helpmenow_block_interface() {
    global $CFG, $USER;

    helpmenow_ensure_user_exists();

    $output = '';

    $output .= <<<EOF
<div id="helpmenow_queue_div"></div>
EOF;

    $privilege = get_field('sis_user', 'privilege', 'sis_user_idstr', $USER->idnumber);
    switch ($privilege) {
    case 'TEACHER':
        $helpmenow_user = get_record('block_helpmenow_user', 'userid', $USER->id);
        $instyle = $outstyle = '';
        if ($helpmenow_user->isloggedin) {
            $outstyle = 'style="display: none;"';
        } else {
            $instyle = 'style="display: none;"';
        }
        $login_url = new moodle_url("$CFG->wwwroot/blocks/helpmenow/login.php");
        $login_url->param('login', 0);
        $logout = link_to_popup_window($login_url->out(), "login", get_string('leave_office', 'block_helpmenow'), 400, 500, null, null, true);
        $login_url->param('login', 1);
        $login = link_to_popup_window($login_url->out(), "login", get_string('enter_office', 'block_helpmenow'), 400, 500, null, null, true);
        $my_office = get_string('my_office', 'block_helpmenow');
        $out_of_office = get_string('out_of_office', 'block_helpmenow');
        $online_students = get_string('online_students', 'block_helpmenow');

        $output .= <<<EOF
<div id="helpmenow_office">
    <div><b>$my_office</b></div>
    <div id="helpmenow_motd" onclick="helpmenowBlock.toggleMOTD(true);" style="border:1px dotted black; width:12em; min-height:1em; padding:.2em; margin-top:.5em;">$helpmenow_user->motd</div>
    <textarea id="helpmenow_motd_edit" onkeypress="return helpmenowBlock.keypressMOTD(event);" onblur="helpmenowBlock.toggleMOTD(false)" style="display:none; margin-top:.5em;" rows="4" cols="22"></textarea>
    <div style="text-align: center; font-size:small; margin-top:.5em;">
        <div id="helpmenow_logged_in_div_0" $instyle>$logout</div>
        <div id="helpmenow_logged_out_div_0" $outstyle>$out_of_office | $login</div>
    </div>
    <div style="margin-top:.5em;">$online_students</div>
    <div id="helpmenow_users_div"></div>
</div>
EOF;
        break;
    case 'STUDENT':
        $output .= '
            <div>'.get_string('instructors', 'block_helpmenow').'</div>
            <div id="helpmenow_users_div"></div>
            ';
        break;
    }
    $jplayer = helpmenow_jplayer();
    $version = HELPMENOW_CLIENT_VERSION;

    $output .= <<<EOF
<hr />
<script src="//ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js" type="text/javascript"></script>
$jplayer
<script src="$CFG->wwwroot/blocks/helpmenow/javascript/lib/jquery.titlealert.js" type="text/javascript"></script>
<script src="$CFG->wwwroot/blocks/helpmenow/javascript/lib/json2.js" type="text/javascript"></script>
<script type="text/javascript" src="$CFG->wwwroot/blocks/helpmenow/javascript/client/$version/lib.js"></script>
<script type="text/javascript" src="$CFG->wwwroot/blocks/helpmenow/javascript/client/$version/block.js"></script>
<script type="text/javascript">
    helpmenow.setServerURL("$CFG->wwwroot/blocks/helpmenow/ajax.php");
</script>
<div id="helpmenow_chime"></div>
EOF;

    return $output;
}

function helpmenow_notify_once($messageid) {
    global $SESSION;
    if (!isset($SESSION->helpmenow_notifications)) {
        $SESSION->helpmenow_notifications = array();
    }
    if (!isset($SESSION->helpmenow_notifications[$messageid])) {
        $SESSION->helpmenow_notifications[$messageid] = true;
        return true;
    }
    return false;
}

function helpmenow_jplayer() {
    global $CFG;
    $root = preg_replace('#^https?://.*?(/|$)#', '\1', $CFG->wwwroot);
    $rval = <<<EOF
<script src="$CFG->wwwroot/blocks/helpmenow/javascript/lib/jquery.jplayer.min.js" type="text/javascript"></script>
<script type="text/javascript">
    $(document).ready(function () {
        $("#helpmenow_chime").jPlayer({
            ready: function () {
                $(this).jPlayer("setMedia", {
                    oga: "$root/blocks/helpmenow/media/cowbell.ogg",
                    mp3: "$root/blocks/helpmenow/media/cowbell.mp3"
                });
            },
            swfPath: "$root/blocks/helpmenow/javascript/lib/Jplayer.swf",
            solution: "html,flash",
            supplied: "mp3,oga"
        });
    });
</script>
EOF;

    return $rval;
}

/**
 * inserts message into session
 * @param int $sessionid session.id
 * @param mixed $userid user.id; null for system messages
 * @param string $message message
 * @param int $notify integer boolean indicating if message should cause client beeps
 * @return boolean success
 */
function helpmenow_message($sessionid, $userid, $message, $notify = 1) {
    $message_rec = (object) array(
        'userid' => $userid,
        'sessionid' => $sessionid,
        'time' => time(),
        'message' => addslashes($message),
        'notify' => $notify,
    );
    if (!$last_message = insert_record('block_helpmenow_message', $message_rec)) {
        return false;
    }

    $session = get_record('block_helpmenow_session', 'id', $sessionid);
    $session->last_message = $last_message;
    $session->iscurrent = 1;
    update_record('block_helpmenow_session', $session);

    return true;
}

/**
 * returns unread messages
 * @param int $sessionid session.id
 * @param int $user user.id
 * @return mixed array of messages or false
 */
function helpmenow_get_unread($sessionid, $userid) {
    global $CFG;
    $sql = "
        SELECT *
        FROM {$CFG->prefix}block_helpmenow_message
        WHERE sessionid = $sessionid
        AND id > (
            SELECT last_message
            FROM {$CFG->prefix}block_helpmenow_session2user
            WHERE userid = $userid
            AND sessionid = $sessionid
        )
        AND (
            userid <> $userid
            OR userid IS NULL
        )
        ORDER BY id ASC
    ";
    return get_records_sql($sql);
}

/**
 * returns entirety of session messages
 * @param int $sessionid
 * @return mixed array of messages or false
 */
function helpmenow_get_history($sessionid) {
    return get_records('block_helpmenow_message', 'sessionid', $sessionid, 'id ASC');
}

/**
 * formats messages
 * todo: move this to the client
 */
function helpmenow_format_messages($messages) {
    global $USER;
    $users = array();
    $output = '';
    foreach ($messages as $m) {
        $msg = $m->message;
        if (is_null($m->userid)) {
            $msg = "<i>$msg</i>";
        } else {
            if ($m->userid == $USER->id) {
                $name = "Me";               # todo: internationalize
            } else {
                if (!isset($users[$m->userid])) {
                    $users[$m->userid] = get_record('user', 'id', $m->userid);
                }
                $name = fullname($users[$m->userid]);
            }
            $msg = "<b>$name:</b> $msg";
        }
        $output .= "<div>$msg</div>";
    }
    return $output;
}

/**
 * email messages users have missed
 */
function helpmenow_email_messages() {
    global $CFG;

    echo "\n";

    # find where we need to email messages
    $earlycutoff = time() - HELPMENOW_EMAIL_EARLYCUTOFF;
    $latecutoff = time() - HELPMENOW_EMAIL_LATECUTOFF;
    $sql = "
        SELECT s2u.id, s2u.userid, s2u.sessionid, (
            SELECT userid
            FROM {$CFG->prefix}block_helpmenow_session2user s2u2
            WHERE s2u2.sessionid = s2u.sessionid
            AND s2u2.userid <> s2u.userid
        ) AS fromuserid
        FROM {$CFG->prefix}block_helpmenow_session2user s2u
        JOIN {$CFG->prefix}block_helpmenow_session s ON s.id = s2u.sessionid
        WHERE s.queueid IS NULL
        AND s.last_message <> 0
        AND s2u.last_message < s.last_message
        AND $latecutoff > (
            SELECT m.time
            FROM {$CFG->prefix}block_helpmenow_message m
            WHERE m.id = s.last_message
        )
        AND $earlycutoff > (
            SELECT min(m2.time)
            FROM {$CFG->prefix}block_helpmenow_message m2
            WHERE m2.sessionid = s2u.sessionid
            AND m2.id > s2u.last_message
        )
    ";
    echo $sql . "\n";
    if (!$session2users = get_records_sql($sql)) {
        echo "we don't have any users to email\n";
        return true;    # we got nothin' to do
    }

    $users = array();
    if (!empty($CFG->helpmenow_title)) {
        $blockname = $CFG->helpmenow_title;
    } else {
        $blockname = get_string('helpmenow', 'block_helpmenow'); 
    }

    # get messages, format and send the email
    foreach ($session2users as $s2u) {
        $rval = true;
        if (!isset($users[$s2u->userid])) {
            $users[$s2u->userid] = get_record('user', 'id', $s2u->userid);
        }
        if (!isset($users[$s2u->fromuserid])) {
            $users[$s2u->fromuserid] = get_record('user', 'id', $s2u->fromuserid);
        }
        $messages = helpmenow_get_unread($s2u->sessionid, $s2u->userid);

        $formatted = '';
        $content = false;
        foreach ($messages as $m) {
            if (!is_null($m->userid)) { $content = true; }
            $formatted .= (is_null($m->userid) ?
                    $m->message :
                    fullname($users[$m->userid]) . ": $m->message")
                . "\n";
            $last_message = $m->id;
        }

        if (!$content) {    # missed messages are only system messages, don't email
            set_field('block_helpmenow_session2user', 'last_message', $last_message, 'id', $s2u->id);   # but do update the last_message so we don't keep catching them
            continue;
        }

        $subject = get_string('default_emailsubject', 'block_helpmenow');
        $subject = str_replace('!blockname!', $blockname, $subject);
        $subject = str_replace('!fromusername!', fullname($users[$s2u->fromuserid]), $subject);

        $text = get_string('default_emailtext', 'block_helpmenow');
        $text = str_replace('!username!', fullname($users[$s2u->userid]), $text);
        $text = str_replace('!blockname!', $blockname, $text);
        $text = str_replace('!fromusername!', fullname($users[$s2u->fromuserid]), $text);
        $text = str_replace('!messages!', $formatted, $text);

        if (email_to_user($users[$s2u->userid], $blockname, $subject, $text)) { #, $messagehtml);
            echo "emailed ".fullname($users[$s2u->userid]).": ".$subject."\n".$text;
            set_field('block_helpmenow_session2user', 'last_message', $last_message, 'id', $s2u->id);
        } else {
            echo "failed to email user $s2u->userid\n";
        }
    }

    return true;
}

/**
 *     _____ _
 *    / ____| |
 *   | |    | | __ _ ___ ___  ___  ___
 *   | |    | |/ _` / __/ __|/ _ \/ __|
 *   | |____| | (_| \__ \__ \  __/\__ \
 *    \_____|_|\__,_|___/___/\___||___/
 */

/**
 * Help me now queue class
 */
class helpmenow_queue {
    /**
     * queue.id
     * @var int $id
     */
    public $id;

    /**
     * The name of the queue.
     * @var string $name
     */
    public $name;

    /**
     * Weight for queue display order
     * @var int $weight
     */
    public $weight;

    /**
     * Description of the queue
     * @var string $desription
     */
    public $description;

    /**
     * Queue helpers
     * @var array $helper
     */
    public $helpers;

    /**
     * Queue sessions
     * @var array $sessions
     */
    public $sessions;

    /**
     * Constructor. If we get an id, load from the database. If we get a object
     * from the db and no id, use that.
     * @param int $id id of the queue in the db
     * @param object $record db record
     */
    public function __construct($id=null, $record=null) {
        if (isset($id)) {
            $record = get_record('block_helpmenow_queue', 'id', $id);
        }
        foreach ($record as $k => $v) {
            $this->$k = $v;
        }
    }

    /**
     * Returns USER's privilege
     * @return string queue privilege
     */
    public function get_privilege() {
        global $USER, $CFG;

        $this->load_helpers();

        # if it's set now, they're a helper
        if (isset($this->helpers[$USER->id])) {
            return HELPMENOW_QUEUE_HELPER;
        }

        $context = get_context_instance(CONTEXT_SYSTEM, SITEID);

        if (has_capability(HELPMENOW_CAP_QUEUE_ASK, $context)) {
            return HELPMENOW_QUEUE_HELPEE;
        }

        return HELPMENOW_NOT_PRIVILEGED;
    }

    /**
     * Returns boolean of helper availability
     * @return boolean
     */
    public function is_open() {
        $this->load_helpers();
        if (!count($this->helpers)) {
            return false;
        }
        foreach ($this->helpers as $h) {
            if ($h->isloggedin) {
                return true;
            }
        }
        return false;
    }

    /**
     * Creates helper for queue using passed userid
     * @param int $userid user.id
     * @return boolean success
     */
    public function add_helper($userid) {
        $this->load_helpers();

        if (isset($this->helpers[$userid])) {
            return false;   # already a helper
        }

        $helper = (object) array(
            'queueid' => $this->id,
            'userid' => $userid,
            'isloggedin' => 0,
        );

        if (!$helper->id = insert_record('block_helpmenow_helper', $helper)) {
            return false;
        }
        $this->helpers[$userid] = $helper;

        return true;
    }

    /**
     * Deletes helper
     * @param int $userid user.id
     * @return boolean success
     */
    public function remove_helper($userid) {
        $this->load_helpers();

        if (!isset($this->helpers[$userid])) {
            return false;
        }

        if (!delete_records('block_helpmenow_helper', 'id', $this->helpers[$userid]->id)) {
            return false;
        }
        unset($this->helpers[$userid]);

        return true;
    }

    /**
     * Loads helpers into $this->helpers array
     */
    public function load_helpers() {
        if (isset($this->helpers)) {
            return true;
        }

        if (!$helpers = get_records('block_helpmenow_helper', 'queueid', $this->id)) {
            return false;
        }

        foreach ($helpers as $h) {
            $this->helpers[$h->userid] = $h;
        }

        return true;
    }

    public static function get_queues() {
        global $CFG;
        if (!$records = get_records_sql("SELECT * FROM {$CFG->prefix}block_helpmenow_queue ORDER BY weight ASC")) {
            return false;
        }
        return self::queues_from_recs($records);
    }

    private static function queues_from_recs($records) {
        $queues = array();
        foreach ($records as $r) {
            $queues[$r->id] = new helpmenow_queue(null, $r);
        }
        return $queues;
    }
}

/**
 * Help me now plugin object abstract class. Base class for plugin, user2plugin,
 * and session2plugin. Lets plugin writers define extra fields to be stored in
 * the database (serialized as a data field, not joinable D: ).
 */
abstract class helpmenow_plugin_object {
    const table = false;

    /**
     * Array of extra fields that must be defined by the child if the plugin
     * requires more data be stored in the database. If this anything is
     * defined here, then the child should also define the member variable
     * @var array $extra_fields
     */
    protected $extra_fields = array();

    /**
     * Data simulates database fields in child classes by serializing data.
     * This is only used if extra_fields is used, and does not need to be
     * in the database if it's not being used. Needs to be public for
     * addslashes_recursive.
     * @var string $data
     */
    public $data;

    /**
     * The id of the object.
     * @var int $id
     */
    public $id;

    /**
     * Plugin of the object; child should override this, if using a plugin class
     * @var string $plugin
     */
    public $plugin = '';

    /**
     * Constructor. If we get an id, load from the database. If we get a object
     * from the db and no id, use that.
     * @param int $id id of the queue in the db
     * @param object $record db record
     */
    public function __construct($id=null, $record=null) {
        if (isset($id)) {
            $record = get_record('block_helpmenow_'.static::table, 'id', $id);
        }
        if (isset($record)) {
            foreach ($record as $k => $v) {
                $this->$k = $v;
            }
            $this->load_extras();
        }
    }

    /**
     * Updates object in db, using object variables. Requires id.
     * @return boolean success
     */
    public function update() {
        global $USER;

        if (empty($this->id)) {
            debugging("Can not update " . static::table . ", no id!");
            return false;
        }

        $this->serialize_extras();

        return update_record("block_helpmenow_" . static::table, addslashes_recursive($this));
    }

    /**
     * Records the object in the db, and sets the id from the return value.
     * @return int PK ID if successful, false otherwise
     */
    public function insert() {
        global $USER;

        if (!empty($this->id)) {
            debugging(static::table . " already exists in db.");
            return false;
        }

        $this->serialize_extras();

        if (!$this->id = insert_record("block_helpmenow_" . static::table, addslashes_recursive($this))) {
            debugging("Could not insert " . static::table);
            return false;
        }

        return $this->id;
    }

    /**
     * Deletes object in db, using object variables. Requires id.
     * @return boolean success
     */
    public function delete() {
        if (empty($this->id)) {
            debugging("Can not delete " . static::table . ", no id!");
            return false;
        }

        return delete_records("block_helpmenow_" . static::table, 'id', $this->id);
    }

    /**
     * Factory function to get existing object of the correct child class
     * @param int $id *.id
     * @return object
     */
    public final static function get_instance($id=null, $record=null) {
        global $CFG;

        # we have to get the record instead of passing the id to the
        # constructor as we have no idea what class the record belongs to
        if (isset($id)) {
            if (!$record = get_record("block_helpmenow_" . static::table, 'id', $id)) {
                return false;
            }
        }

        $class = static::get_class($record->plugin);

        return new $class(null, $record);
    }

    /**
     * Factory function to create an object of the correct plugin
     * @param string $plugin optional plugin parameter, if none supplied uses
     *      configured default
     * @return object
     */
    public final static function new_instance($plugin) {
        global $CFG;

        $class = static::get_class($plugin);

        $object = new $class;
        $object->plugin = $plugin;

        return $object;
    }

    /**
     * Returns an array of objects from an array of records
     * @param array $records
     * @return array of objects
     */
    public final static function objects_from_records($records) {
        $objects = array();
        foreach ($records as $r) {
            $objects[$r->id] = static::get_instance(null, $r);
        }
        return $objects;
    }

    /**
     * Return the class name and require_once the file that contains it
     * @param string $plugin
     * @return string classname
     */
    public final static function get_class($plugin) {
        global $CFG;

        $classpath = "$CFG->dirroot/blocks/helpmenow/plugins/$plugin/" . static::table . ".php";
        if (!file_exists($classpath)) {
            return "helpmenow_" . static::table;
        }
        $pluginclass = "helpmenow_" . static::table . "_$plugin";
        require_once($classpath);

        return $pluginclass;
    }

    /**
     * Loads the fields from a passed record. Also unserializes simulated fields
     * @param object $record db record
     */
    protected function load_extras() {
        # bail at this point if we don't have extra fields
        if (!count($this->extra_fields)) { return; }

        $extras = unserialize($this->data);
        foreach ($this->extra_fields as $field) {
            $this->$field = $extras[$field];
        }
    }

    /**
     * Serializes simulated fields if necessary
     */
    protected final function serialize_extras() {
        # bail immediately if we don't have any extra fields
        if (!count($this->extra_fields)) { return; }
        $extras = array();
        foreach ($this->extra_fields as $field) {
            $extras[$field] = $this->$field;
        }
        $this->data = serialize($extras);
        return;
    }
}

/**
 * Help me now plugin abstract class. Plugins define things like cron and
 * code that is run on installation.
 */
abstract class helpmenow_plugin extends helpmenow_plugin_object {
    const table = 'plugin';

    /**
     * Cron delay in seconds; 0 represents no cron
     * @var int $cron_interval
     */
    public $cron_interval = 0;

    /**
     * Last cron timestamp
     * @var int $last_cron
     */
    public $last_cron;

    /**
     * "Installs" the plugin
     * @return boolean success
     */
    public static function install() {
        $plugin = new static();
        $plugin->last_cron = 0;
        $plugin->insert();
    }

    /**
     * Cron that will run everytime block cron is run.
     * @return boolean
     */
    public static function cron() {
        return true;
    }

    /**
     * Used to define what is displayed in the the plugin section of the chat window
     * @param bool $privileged
     * @return string
     */
    public abstract static function display($sessionid, $privileged = false);

    /**
     * Code to be run when USER logs in
     * @return mixed string url to redirect, true for other success
     */
    public static function on_login() {
        return true;
    }

    /**
     * Code to be run when USER logs out
     * @return mixed string url to redirect, true for other success
     */
    public static function on_logout() {
        return true;
    }

    /**
     * Code to be run when the chat makes a refresh call
     */
    public static function on_chat_refresh($request, &$response) {
        return;
    }

    /**
     * returns array of valid plugin ajax functions
     * @return array
     */
    public static function get_ajax_functions() {
        return array();
    }

    /**
     * returns array of full url paths to needed javascript libraries
     * @return array
     */
    public static function get_js_libs() {
        return array();
    }

    /**
     * Calls install for all plugins
     * @return boolean success
     */
    public final static function install_all() {
        $success = true;
        foreach (self::get_plugins() as $pluginname) {
            $class = "helpmenow_plugin_$pluginname";
            $success = $success and $class::install();
        }
        return $success;
    }

    /**
     * Calls any existing cron functions of plugins
     * @return boolean
     */
    public final static function cron_all() {
        $success = true;
        foreach (self::get_plugins() as $pluginname) {
            $class = "helpmenow_plugin_$pluginname";
            $record = get_record('block_helpmenow_plugin', 'plugin', $pluginname);
            $plugin = new $class(null, $record);
            if (($plugin->cron_interval != 0) and (time() >= $plugin->last_cron + $plugin->cron_interval)) {
                $class = "helpmenow_plugin_$pluginname";
                $success = $success and $class::cron();
                $plugin->last_cron = time();
                $plugin->update();
            }
        }
        return $success;
    }

    /**
     * Handles requiring the necessary files and returns array of plugins
     * @return array of strings that are the plugin classnames
     */
    public final static function get_plugins() {
        global $CFG;
        global $USER;

        $plugins = array();
        foreach (get_list_of_plugins('plugins', '', dirname(__FILE__)) as $pluginname) {
            $enabled = "helpmenow_{$pluginname}_enabled";
            if (!isset($CFG->$enabled) or !$CFG->$enabled) {
                continue;
            }
            require_once("$CFG->dirroot/blocks/helpmenow/plugins/$pluginname/lib.php");
            $plugins[] = "$pluginname";
        }
        return $plugins;
    }
}

/**
 * Help me now user2plugin abstract class.
 */
abstract class helpmenow_user2plugin extends helpmenow_plugin_object {
    const table = 'user2plugin';

    /**
     * user.id
     * @var int $userid
     */
    public $userid;

    /**
     * Returns user2plugin object for USER
     * @return object
     */
    public static function get_user2plugin($userid = null) {
        if (!isset($userid)) {
            global $USER;
            $userid = $USER->id;
        }

        $plugin = preg_replace('/helpmenow_user2plugin_/', '', get_called_class());

        if ($record = get_record('block_helpmenow_user2plugin', 'userid', $userid, 'plugin', $plugin)) {
            return new static(null, $record);
        }
        return false;
    }
}

/**
 * Help me now session2plugin abstract class.
 */
abstract class helpmenow_session2plugin extends helpmenow_plugin_object {
    const table = 's2p';

    /**
     * session.id
     * @var int $sessionid
     */
    public $sessionid;
}

?>
