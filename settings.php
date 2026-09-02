<?php
defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $settings = new admin_settingpage('local_grade_notifier', get_string('pluginname', 'local_grade_notifier'));

    // Enable / Disable plugin.
    $settings->add(new admin_setting_configcheckbox(
        'local_grade_notifier/enabled',
        get_string('setting_enabled', 'local_grade_notifier'),
        get_string('setting_enabled_desc', 'local_grade_notifier'),
        1
    ));

    // Custom profile field shortname for supervisor email.
    $settings->add(new admin_setting_configtext(
        'local_grade_notifier/profilefield',
        get_string('setting_profilefield', 'local_grade_notifier'),
        get_string('setting_profilefield_desc', 'local_grade_notifier'),
        'supervisor_email',
        PARAM_ALPHANUMEXT
    ));

    // Notify student toggle.
    $settings->add(new admin_setting_configcheckbox(
        'local_grade_notifier/notify_student',
        get_string('setting_notify_student', 'local_grade_notifier'),
        get_string('setting_notify_student_desc', 'local_grade_notifier'),
        1
    ));

    // Notify supervisor toggle.
    $settings->add(new admin_setting_configcheckbox(
        'local_grade_notifier/notify_supervisor',
        get_string('setting_notify_supervisor', 'local_grade_notifier'),
        get_string('setting_notify_supervisor_desc', 'local_grade_notifier'),
        1
    ));

    // Show pass/fail status badge.
    $settings->add(new admin_setting_configcheckbox(
        'local_grade_notifier/show_passfail',
        get_string('setting_show_passfail', 'local_grade_notifier'),
        get_string('setting_show_passfail_desc', 'local_grade_notifier'),
        1
    ));

    // Only notify if student achieved passing grade.
    $settings->add(new admin_setting_configcheckbox(
        'local_grade_notifier/only_passing_grades',
        get_string('setting_only_passing_grades', 'local_grade_notifier'),
        get_string('setting_only_passing_grades_desc', 'local_grade_notifier'),
        0
    ));

    $ADMIN->add('localplugins', $settings);
}
