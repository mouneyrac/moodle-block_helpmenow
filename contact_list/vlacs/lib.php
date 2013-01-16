<?php
/**
 * Help me now VLACS contact list plugin
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
class helpmenow_contact_list_vlacs extends helpmenow_contact_list {
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
        $privilege = get_field('sis_user', 'privilege', 'sis_user_idstr', $user->idnumber);
        switch ($privilege) {
        case 'TEACHER':
            # todo: maybe admins?
            $sql = "
                SELECT u.*
                FROM {$CFG->prefix}classroom c
                JOIN {$CFG->prefix}classroom_enrolment ce ON ce.classroom_idstr = c.classroom_idstr
                JOIN {$CFG->prefix}user u ON u.idnumber = ce.sis_user_idstr
                JOIN {$CFG->prefix}block_helpmenow_user hu ON hu.userid = u.id
                WHERE c.sis_user_idstr = '$user->idnumber'
                AND ce.status_idstr = 'ACTIVE'
                AND ce.activation_status_idstr IN ('ENABLED', 'CONTACT_INSTRUCTOR')
                AND ce.iscurrent = 1
            ";
            $new_contacts = get_records_sql($sql);
            break;
        case 'STUDENT':
            $sql = "
                SELECT u.*
                FROM {$CFG->prefix}classroom_enrolment ce
                JOIN {$CFG->prefix}classroom c ON c.classroom_idstr = ce.classroom_idstr
                JOIN {$CFG->prefix}user u ON c.sis_user_idstr = u.idnumber
                JOIN {$CFG->prefix}block_helpmenow_user hu ON hu.userid = u.id
                WHERE ce.sis_user_idstr = '$user->idnumber'
                AND ce.status_idstr = 'ACTIVE'
                AND ce.activation_status_idstr IN ('ENABLED', 'CONTACT_INSTRUCTOR')
                AND ce.iscurrent = 1
            ";
            $new_contacts = get_records_sql($sql);
            break;
        case 'ADMIN':
            # for new this will make it so we can at least hack some contacts
            # into the db and not have them be changed
            # todo: other admins and maybe instructors?
            return true;
            break;
        default:
            $new_contacts = array();
            break;
        }
        # get the current contacts
        $current_contacts = get_records('block_helpmenow_contact', 'userid', $userid, '', 'contact_userid, id');

        $rval = true;
        # remove current contacts we shouldn't have
        foreach ($current_contacts as $cc) {
            if (!isset($new_contacts[$cc->contact_userid])) {
                $rval = true and delete_records('block_helpmenow_contact', 'id', $cc->id);
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
}

?>
