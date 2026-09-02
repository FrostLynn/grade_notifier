<?php
defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Grade Notifier to Supervisor';

// Settings.
$string['setting_enabled'] = 'Enable Plugin';
$string['setting_enabled_desc'] = 'If enabled, automatic email notifications will be sent when a quiz is graded.';
$string['setting_profilefield'] = 'Supervisor Profile Field';
$string['setting_profilefield_desc'] = 'The shortname of the custom user profile field containing supervisor email address(es).';
$string['setting_notify_student'] = 'Notify Student';
$string['setting_notify_student_desc'] = 'Send grade notification email to the student.';
$string['setting_notify_supervisor'] = 'Notify Supervisor';
$string['setting_notify_supervisor_desc'] = 'Send grade notification email to the supervisor.';
$string['setting_show_passfail'] = 'Show Pass/Fail Status';
$string['setting_show_passfail_desc'] = 'Display pass or fail badge in the notification email if a passing grade is configured on the quiz.';
$string['setting_only_passing_grades'] = 'Only Send on Passing Grades';
$string['setting_only_passing_grades_desc'] = 'If enabled, notifications will only be sent if the student achieved or exceeded the passing grade.';

// Email Content.
$string['supervisor'] = 'Supervisor';
$string['email_subject'] = 'Quiz Grade Report: {$a->quizname} - {$a->fullname}';
$string['email_title'] = 'Participant Quiz Report';
$string['email_student_name'] = 'Participant';
$string['email_course'] = 'Course / Training';
$string['email_quiz'] = 'Quiz';
$string['email_score'] = 'Score Obtained';
$string['email_percentage'] = 'Percentage';
$string['email_grade_date'] = 'Grading Time';
$string['email_status'] = 'Passing Status';
$string['status_passed'] = 'PASSED';
$string['status_failed'] = 'NOT PASSED';
$string['email_view_quiz'] = 'View Quiz Details';
$string['email_footer'] = 'This is an automated notification from {$a->sitename}. Please do not reply to this email.';

// Task.
$string['task_send_notification'] = 'Send quiz grade notification emails';
