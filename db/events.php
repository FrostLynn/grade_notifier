<?php
defined('MOODLE_INTERNAL') || die();

$observers = [
    [
        'eventname'   => '\core\event\user_graded',
        'callback'    => '\local_grade_notifier\observer::user_graded',
    ],
];