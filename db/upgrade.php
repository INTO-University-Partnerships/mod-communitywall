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

    // add completion criteria columns to database (e.g. 'completioncreatewall' and 'completionpostonwall')
    if ($oldversion < 2016111500) {
        $table = new xmldb_table('communitywall');

        $completioncreatewall = new xmldb_field('completioncreatewall', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
        if (!$dbman->field_exists($table, $completioncreatewall)) {
            $dbman->add_field($table, $completioncreatewall);
        }
        $completionpostonwall = new xmldb_field('completionpostonwall', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
        if (!$dbman->field_exists($table, $completionpostonwall)) {
            $dbman->add_field($table, $completionpostonwall);
        }

        upgrade_plugin_savepoint(true, 2016111500, 'mod', 'communitywall');
    }

    return true;
}
