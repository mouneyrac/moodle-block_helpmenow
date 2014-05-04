<?php
function xmldb_block_helpmenow_upgrade($oldversion = 0) {
    global $CFG;
    $result = true;

    if ($result && $oldversion < 2012082101) {
    /// Define field notify to be added to block_helpmenow_message
        $table = new xmldb_table('block_helpmenow_message');
        $field = new xmldb_field('notify');
        $field->set_attributes(XMLDB_TYPE_INTEGER, '1', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '1', 'message');

    /// Launch add field notify
        $result = $result && $dbman->add_field($table, $field);
    }

    if ($result && $oldversion < 2012082102) {
    /// system messages are no longer using get_admin()->id for userid, but instead null
        $result = $result && $DB->set_field('block_helpmenow_message', 'userid', null, array('userid' => get_admin()->id));
    }

    if ($result && $oldversion < 2012082400) {
    /// Define field last_message to be added to block_helpmenow_session2user
        $table = new xmldb_table('block_helpmenow_session2user');
        $field = new xmldb_field('last_message');
        $field->set_attributes(XMLDB_TYPE_INTEGER, '20', XMLDB_UNSIGNED, null, null, null, 'last_refresh');

    /// Launch add field last_message
        $result = $result && $dbman->add_field($table, $field);
    }

    if ($result && $oldversion < 2012091200) {
    /// Define field last_message to be added to block_helpmenow_session
        $table = new xmldb_table('block_helpmenow_session');
        $field = new xmldb_field('last_message');
        $field->set_attributes(XMLDB_TYPE_INTEGER, '20', XMLDB_UNSIGNED, null, null, null, 'timecreated');

    /// Launch add field last_message
        $result = $result && $dbman->add_field($table, $field);
    }

    if ($result && $oldversion < 2012091400) {
    /// Changing the default of field last_message on table block_helpmenow_session2user to 0
        $table = new xmldb_table('block_helpmenow_session2user');
        $field = new xmldb_field('last_message');
        $field->set_attributes(XMLDB_TYPE_INTEGER, '20', XMLDB_UNSIGNED, null, null, '0', 'last_refresh');

    /// Launch change of default for field last_message
        $result = $result && $dbman->change_field_default($table, $field);

    /// Changing the default of field last_message on table block_helpmenow_session to 0
        $table = new xmldb_table('block_helpmenow_session');
        $field = new xmldb_field('last_message');
        $field->set_attributes(XMLDB_TYPE_INTEGER, '20', XMLDB_UNSIGNED, null, null, '0', 'timecreated');

    /// Launch change of default for field last_message
        $result = $result && $dbman->change_field_default($table, $field);
    }

    if ($result && $oldversion < 2012092100) {
    /// Define field lastaccess to be added to block_helpmenow_user
        $table = new xmldb_table('block_helpmenow_user');
        $field = new xmldb_field('lastaccess');
        $field->set_attributes(XMLDB_TYPE_INTEGER, '20', XMLDB_UNSIGNED, null, null, null, 'motd');

    /// Launch add field lastaccess
        $result = $result && $dbman->add_field($table, $field);
    }

    if ($result && $oldversion < 2012121900) {
    /// Define field optimistic_last_message to be added to block_helpmenow_session2user
        $table = new xmldb_table('block_helpmenow_session2user');
        $field = new xmldb_field('optimistic_last_message');
        $field->set_attributes(XMLDB_TYPE_INTEGER, '20', XMLDB_UNSIGNED, null, null, null, 'last_message');

    /// Launch add field optimistic_last_message
        $result = $result && $dbman->add_field($table, $field);

    /// Define field cache to be added to block_helpmenow_session2user
        $table = new xmldb_table('block_helpmenow_session2user');
        $field = new xmldb_field('cache');
        $field->set_attributes(XMLDB_TYPE_TEXT, 'big', null, null, null, null, 'optimistic_last_message');

    /// Launch add field cache
        $result = $result && $dbman->add_field($table, $field);
    }

    if ($result && $oldversion < 2012122700) {

    /// Define table block_helpmenow_error_log to be created
        $table = new xmldb_table('block_helpmenow_error_log');

    /// Adding fields to table block_helpmenow_error_log
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('error', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('details', XMLDB_TYPE_TEXT, 'big', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '20', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);

    /// Adding keys to table block_helpmenow_error_log
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

    /// Launch create table for block_helpmenow_error_log
        $result = $result && $dbman->create_table($table);
    }

    if ($result && $oldversion < 2012123100) {

    /// Define field userid to be added to block_helpmenow_error_log
        $table = new xmldb_table('block_helpmenow_error_log');
        $field = new xmldb_field('userid');
        $field->set_attributes(XMLDB_TYPE_INTEGER, '20', XMLDB_UNSIGNED, null, null, null, 'timecreated');

    /// Launch add field userid
        $result = $result && $dbman->add_field($table, $field);
    }

    if ($result && $oldversion < 2013011800) {

    /// Define table block_helpmenow_contact to be created
        $table = new xmldb_table('block_helpmenow_contact');

    /// Adding fields to table block_helpmenow_contact
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '20', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->add_field('contact_userid', XMLDB_TYPE_INTEGER, '20', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '20', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);

    /// Adding keys to table block_helpmenow_contact
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

    /// Adding indexes to table block_helpmenow_contact
        $table->add_index('block_helpmenow_contact_userid_ix', XMLDB_INDEX_NOTUNIQUE, array('userid'));

    /// Launch create table for block_helpmenow_contact
        $result = $result && $dbman->create_table($table);
    }

    if ($result && $oldversion < 2013050200) {

        /// Define index block_helpmenow_log_u_ix (not unique) to be added to block_helpmenow_log
        $table = new xmldb_table('block_helpmenow_log');
        $index = new xmldb_index('block_helpmenow_log_u_ix');
        $index->set_attributes(XMLDB_INDEX_NOTUNIQUE, array('userid'));

        /// Launch add index block_helpmenow_log_u_ix
        $result = $result && $dbman->add_index($table, $index);

        /// Define index block_helpmenow_log_ua_ix (not unique) to be added to block_helpmenow_log
        $table = new xmldb_table('block_helpmenow_log');
        $index = new xmldb_index('block_helpmenow_log_ua_ix');
        $index->set_attributes(XMLDB_INDEX_NOTUNIQUE, array('userid', 'action'));

        /// Launch add index block_helpmenow_log_ua_ix
        $result = $result && $dbman->add_index($table, $index);
    }

    if ($result && $oldversion < 2013050700) {

    /// Define field last_read to be added to block_helpmenow_session2user
        $table = new xmldb_table('block_helpmenow_session2user');
        $field = new xmldb_field('last_read');
        $field->set_attributes(XMLDB_TYPE_INTEGER, '20', XMLDB_UNSIGNED, null, null, '0', 'cache');

        /// Launch add field last_read
        $result = $result && $dbman->add_field($table, $field);
    }

    return $result;
}
?>
