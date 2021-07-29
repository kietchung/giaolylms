<?php
defined('MOODLE_INTERNAL') || die;

function xmldb_local_newsvnr_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager(); // Loads ddl manager and xmldb classes.

    if ($oldversion < 2019032724 ) {
        $table = new xmldb_table('course_setup');
        // Conditionally launch add field completionscorerequired.
        if (!$dbman->field_exists($table, 'visible')) {
            $dbman->add_field(
                $table,
                new xmldb_field('visible', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, 1, 'shortname')
            );
        }
        // Main savepoint reached.
        upgrade_plugin_savepoint(true, 2019032724, 'local', 'newsvnr');
    }

    if ($oldversion < 2019032924.01) {

        $table = new xmldb_table('division');

        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('code', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('name', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('shortname', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('visible', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, null);
        $table->add_field('address', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('phone', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('fax', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('website', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, 0);
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, 0);
        $table->add_field('usercreate', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, 0);
        $table->add_field('usermodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, 0);
        
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        $table = new xmldb_table('division_categories');

        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('coursecategorysid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('divisionid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, 0);
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, 0);
        $table->add_field('usercreate', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, 0);
        $table->add_field('usermodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, 0);

        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_index('divisionid', XMLDB_INDEX_NOTUNIQUE, ['divisionid']);

        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }
        
        // Main savepoint reached.
        upgrade_plugin_savepoint(true, 2019032924.01, 'local', 'newsvnr');
    }

    return true;
}