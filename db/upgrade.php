<?php
defined('MOODLE_INTERNAL') || die();

/**
 * Execute local_grade_notifier upgrade from the given old version.
 *
 * @param int $oldversion
 * @return bool
 */
function xmldb_local_grade_notifier_upgrade($oldversion) {
    global $DB;
    $dbman = $DB->get_manager();

    if ($oldversion < 2026090200) {
        // Define table local_grade_notifier_logs to be created.
        $table = new xmldb_table('local_grade_notifier_logs');

        // Adding fields to table local_grade_notifier_logs.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('quizid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('gradeitemid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('finalgrade', XMLDB_TYPE_NUMBER, '10, 5', null, null, null, null);
        $table->add_field('timenotified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table local_grade_notifier_logs.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

        // Adding indexes to table local_grade_notifier_logs.
        $table->add_index('user_quiz_grade', XMLDB_INDEX_NOTUNIQUE, ['userid', 'quizid', 'gradeitemid']);

        // Conditionally launch create table for local_grade_notifier_logs.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Grade notifier savepoint reached.
        upgrade_plugin_savepoint(true, 2026090200, 'local', 'grade_notifier');
    }

    return true;
}
