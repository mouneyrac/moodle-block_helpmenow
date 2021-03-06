<?php
/**
 * Help me now native contact list plugin
 *
 * @package     block_helpmenow
 * @copyright   2012 VLACS
 * @author      David Zaharee <dzaharee@vlacs.org>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(__FILE__))) . '/lib.php');

/**
 * native contact list plugin class
 *
 * todo: define block_display() to add a link to the block to an interface to
 * manage contacts
 * todo: make the interface to manage contacts
 */
class helpmenow_contact_list_native extends helpmenow_contact_list {
    /**
     * update contacts for user
     *
     * @param in $userid user.id
     * @return bool success
     */
    public static function update_contacts($userid) {
        global $CFG;
        $user = get_record('user', 'id', $userid);

        # get the new contacts
        $new_contacts = array();
        if (self::is_teacher($userid)) {

            $sql = "
                SELECT c.id
                FROM {$CFG->prefix}course c
                INNER JOIN {$CFG->prefix}context cx ON c.id = cx.instanceid AND cx.contextlevel = " . CONTEXT_COURSE . "
                INNER JOIN {$CFG->prefix}role_assignments ra ON cx.id = ra.contextid
                INNER JOIN {$CFG->prefix}role r ON ra.roleid = r.id
                INNER JOIN {$CFG->prefix}user usr ON ra.userid = usr.id AND usr.id = $userid
                WHERE r.shortname in ('teacher', 'editingteacher')
                ";
            $courses = get_records_sql($sql);
            if ($courses) {
                $courseids = array();
                foreach($courses as $c) {
                    $courseids[] = $c->id;
                }
                $courseids = implode(",", $courseids);

                $sql = "
                    SELECT usr.*
                    FROM {$CFG->prefix}course c
                    INNER JOIN {$CFG->prefix}context cx ON c.id = cx.instanceid AND cx.contextlevel = '50'
                    INNER JOIN {$CFG->prefix}role_assignments ra ON cx.id = ra.contextid
                    INNER JOIN {$CFG->prefix}role r ON ra.roleid = r.id
                    INNER JOIN {$CFG->prefix}user usr ON ra.userid = usr.id
                    WHERE r.shortname = 'student'
                    AND c.id in ($courseids)
                    ";
                $new_contacts = get_records_sql($sql);
            }
        } else if (self::is_student($userid)) {

            $sql = "
                SELECT c.id
                FROM {$CFG->prefix}course c
                INNER JOIN {$CFG->prefix}context cx ON c.id = cx.instanceid AND cx.contextlevel = '50'
                INNER JOIN {$CFG->prefix}role_assignments ra ON cx.id = ra.contextid
                INNER JOIN {$CFG->prefix}role r ON ra.roleid = r.id
                INNER JOIN {$CFG->prefix}user usr ON ra.userid = usr.id AND usr.id = $userid
                WHERE r.shortname = 'student'
                ";
            $courses = get_records_sql($sql);
            if ($courses) {
                $courseids = array();
                foreach($courses as $c) {
                    $courseids[] = $c->id;
                }
                $courseids = implode(",", $courseids);

                $sql = "
                    SELECT usr.*
                    FROM {$CFG->prefix}course c
                    INNER JOIN {$CFG->prefix}context cx ON c.id = cx.instanceid AND cx.contextlevel = '50'
                    INNER JOIN {$CFG->prefix}role_assignments ra ON cx.id = ra.contextid
                    INNER JOIN {$CFG->prefix}role r ON ra.roleid = r.id
                    INNER JOIN {$CFG->prefix}user usr ON ra.userid = usr.id
                    WHERE r.shortname in ('teacher', 'editingteacher')
                    AND c.id in ($courseids)
                    ";
                $new_contacts = get_records_sql($sql);
            }
        } else if (self::is_admin($userid)) {
            # for new this will make it so we can at least hack some contacts
            # into the db and not have them be changed
            # todo: other admins and maybe instructors?
            return true;
        } else {
            $new_contacts = array();
        }

        $rval = true;
        # get the current contacts
        if ($current_contacts = get_records('block_helpmenow_contact', 'userid', $userid, '', 'contact_userid, id')) {

            # remove current contacts we shouldn't have
            foreach ($current_contacts as $cc) {
                if (!isset($new_contacts[$cc->contact_userid])) {
                    $rval = $rval and delete_records('block_helpmenow_contact', 'id', $cc->id);
                }
            }
        }

        # add new contacts we don't have
        foreach ($new_contacts as $nc) {
            if (!isset($current_contacts[$nc->id])) {
                $contact_rec = (object) array(
                    'userid' => $userid,
                    'contact_userid' => $nc->id,
                    'timecreated' => time(),
                );
                $rval = $rval and insert_record('block_helpmenow_contact', $contact_rec);
            }
        }
        return $rval;
    }


    public static function is_teacher($userid=null) {
        global $CFG,$USER;
        if ($userid == null) {
            $userid = $USER->id;
        }

        $sql = "
            SELECT usr.id
            FROM {$CFG->prefix}course c
            INNER JOIN {$CFG->prefix}context cx ON c.id = cx.instanceid AND cx.contextlevel = " . CONTEXT_COURSE . "
            INNER JOIN {$CFG->prefix}role_assignments ra ON cx.id = ra.contextid
            INNER JOIN {$CFG->prefix}role r ON ra.roleid = r.id
            INNER JOIN {$CFG->prefix}user usr ON ra.userid = usr.id and usr.id = $userid
            WHERE r.shortname in ('teacher', 'editingteacher')
            ";

        return record_exists_sql($sql);
    }

    public static function is_admin($userid=null) {
        $sitecontext = get_context_instance(CONTEXT_SYSTEM, SITEID);
        return has_capability('moodle/site:doanything', $sitecontext);
    }

    public static function is_admin_or_teacher($userid=null) {
        return self::is_teacher($userid) or self::is_admin($userid);
    }

    public static function is_student($userid=null) {
        global $CFG, $USER;
        if ($userid == null) {
            $userid = $USER->id;
        }

        $sql = "
            SELECT usr.id
            FROM {$CFG->prefix}course c
            INNER JOIN {$CFG->prefix}context cx ON c.id = cx.instanceid AND cx.contextlevel = " . CONTEXT_COURSE ."
            INNER JOIN {$CFG->prefix}role_assignments ra ON cx.id = ra.contextid
            INNER JOIN {$CFG->prefix}role r ON ra.roleid = r.id
            INNER JOIN {$CFG->prefix}user usr ON ra.userid = usr.id and usr.id = $userid
            WHERE r.shortname in ('student')
            ";

        return record_exists_sql($sql);
    }
}

?>
