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
        return true;
    }
}

?>
