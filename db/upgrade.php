<?php
function xmldb_block_helpmenow_upgrade($oldversion = 0) {
    global $CFG;
    $dbman = $DB->get_manager();

    if ($oldversion < 2013050200) {

        /// Define index block_helpmenow_log_u_ix (not unique) to be added to block_helpmenow_log
        $table = new xmldb_table('block_helpmenow_log');
        $index = new xmldb_index('block_helpmenow_log_u_ix');
        $index->set_attributes(XMLDB_INDEX_NOTUNIQUE, array('userid'));

        /// Launch add index block_helpmenow_log_u_ix
        $dbman->add_index($table, $index);
        upgrade_mod_savepoint(true, 2013050200, 'block_helpmenow_logi_u_ix');

        /// Define index block_helpmenow_log_ua_ix (not unique) to be added to block_helpmenow_log
        $table = new xmldb_table('block_helpmenow_log');
        $index = new xmldb_index('block_helpmenow_log_ua_ix');
        $index->set_attributes(XMLDB_INDEX_NOTUNIQUE, array('userid', 'action'));

        /// Launch add index block_helpmenow_log_ua_ix
        $dbman->add_index($table, $index);
        upgrade_mod_savepoint(true, 2013050200, 'block_helpemenow_log_ua_ix');
    }

    if ($oldversion < 2013050700) {

    /// Define field last_read to be added to block_helpmenow_session2user
        $table = new xmldb_table('block_helpmenow_session2user');
        $field = new xmldb_field('last_read');
        $field->set_attributes(XMLDB_TYPE_INTEGER, '20', XMLDB_UNSIGNED, null, null, '0', 'cache');

        /// Launch add field last_read
        $dbman->add_field($table, $field);
        upgrade_mod_savepoint(true, 2013050700, 'block_helpmenow_session2user');
    }

}
?>
