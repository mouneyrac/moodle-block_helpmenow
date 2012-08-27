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

    return $result;
}
?>
