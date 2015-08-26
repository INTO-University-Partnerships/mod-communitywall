<?php

defined('MOODLE_INTERNAL') || die;

/**
 * upgrades the database
 * @global moodle_database $DB
 * @param integer $oldversion
 * @return bool
 */
function xmldb_communitywall_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    // add the 'closed' column
    if ($oldversion < 2014031800) {
        $table = new xmldb_table('communitywall');
        $field = new xmldb_field('closed', XMLDB_TYPE_INTEGER, '1');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_plugin_savepoint(true, 2014031800, 'mod', 'communitywall');
    }

    return true;
}
