<?php
function xmldb_block_helpmenow_upgrade($oldversion = 0) {
    global $CFG;
    $result = true;

    if ($result && $oldversion < 2012082101) {
    /// Define field notify to be added to block_helpmenow_message
        $table = new XMLDBTable('block_helpmenow_message');
        $field = new XMLDBField('notify');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '1', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '1', 'message');

    /// Launch add field notify
        $result = $result && add_field($table, $field);
    }

    if ($result && $oldversion < 2012082102) {
    /// system messages are no longer using get_admin()->id for userid, but instead null
        $result = $result && set_field('block_helpmenow_message', 'userid', null, 'userid', get_admin()->id);
    }

    if ($result && $oldversion < 2012082400) {

    /// Define field last_message to be added to block_helpmenow_session2user
        $table = new XMLDBTable('block_helpmenow_session2user');
        $field = new XMLDBField('last_message');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '20', XMLDB_UNSIGNED, null, null, null, null, null, 'last_refresh');

    /// Launch add field last_message
        $result = $result && add_field($table, $field);
    }

    if ($result && $oldversion < 2012091200) {

    /// Define field last_message to be added to block_helpmenow_session
        $table = new XMLDBTable('block_helpmenow_session');
        $field = new XMLDBField('last_message');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '20', XMLDB_UNSIGNED, null, null, null, null, null, 'timecreated');

    /// Launch add field last_message
        $result = $result && add_field($table, $field);
    }

    if ($result && $oldversion < 2012091400) {

    /// Changing the default of field last_message on table block_helpmenow_session2user to 0
        $table = new XMLDBTable('block_helpmenow_session2user');
        $field = new XMLDBField('last_message');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '20', XMLDB_UNSIGNED, null, null, null, null, '0', 'last_refresh');

    /// Launch change of default for field last_message
        $result = $result && change_field_default($table, $field);

    /// Changing the default of field last_message on table block_helpmenow_session to 0
        $table = new XMLDBTable('block_helpmenow_session');
        $field = new XMLDBField('last_message');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '20', XMLDB_UNSIGNED, null, null, null, null, '0', 'timecreated');

    /// Launch change of default for field last_message
        $result = $result && change_field_default($table, $field);
    }

    return $result;
}
?>
