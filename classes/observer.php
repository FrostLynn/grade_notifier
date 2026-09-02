<?php
namespace local_grade_notifier;

defined('MOODLE_INTERNAL') || die();

use core_user;

/**
 * Event observer for local_grade_notifier.
 *
 * @package    local_grade_notifier
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class observer {

    /**
     * Triggered when a user is graded in Moodle gradebook.
     *
     * @param \core\event\user_graded $event
     */
    public static function user_graded(\core\event\user_graded $event) {
        global $DB;

        try {
            // Check if plugin is enabled.
            $enabled = get_config('local_grade_notifier', 'enabled');
            if ($enabled === false) {
                $enabled = 1;
            }
            if (!$enabled) {
                return;
            }

            $gradedata = $event->get_grade();
            $gradeitem = $gradedata->grade_item;

            // Ensure event originates from a Quiz module.
            if ($gradeitem->itemtype !== 'mod' || $gradeitem->itemmodule !== 'quiz') {
                return;
            }

            // Skip if final grade is null (e.g. essay questions awaiting manual teacher grading).
            if ($gradedata->finalgrade === null) {
                return;
            }

            $finalgrade = round((float)$gradedata->finalgrade, 2);
            $maxgrade   = round((float)$gradeitem->grademax, 2);
            $gradepass  = isset($gradeitem->gradepass) ? (float)$gradeitem->gradepass : 0.0;

            // Check if admin configured to only notify on passing grade.
            $onlypassing = get_config('local_grade_notifier', 'only_passing_grades');
            if ($onlypassing && $gradepass > 0 && $finalgrade < $gradepass) {
                return;
            }

            // Retrieve quiz record.
            $quiz = $DB->get_record('quiz', ['id' => $gradeitem->iteminstance]);
            if (!$quiz) {
                return;
            }

            // Retrieve course record.
            $course = $DB->get_record('course', ['id' => $gradeitem->courseid]);
            if (!$course) {
                return;
            }

            // Retrieve student record.
            $user = core_user::get_user($gradedata->userid);
            if (!$user || $user->deleted) {
                return;
            }

            // Check log to avoid duplicate notifications for identical grade.
            $dbman = $DB->get_manager();
            if ($dbman->table_exists('local_grade_notifier_logs')) {
                $already_notified = $DB->record_exists('local_grade_notifier_logs', [
                    'userid'      => $user->id,
                    'quizid'      => $quiz->id,
                    'gradeitemid' => $gradeitem->id,
                    'finalgrade'  => $finalgrade,
                ]);
                if ($already_notified) {
                    return;
                }
            }

            // Queue Adhoc Task for asynchronous background execution.
            $task = new \local_grade_notifier\task\send_notification_task();
            $task->set_custom_data((object)[
                'userid'       => (int)$user->id,
                'courseid'     => (int)$course->id,
                'quizid'       => (int)$quiz->id,
                'gradeitemid'  => (int)$gradeitem->id,
                'finalgrade'   => $finalgrade,
                'maxgrade'     => $maxgrade,
                'gradepass'    => $gradepass,
                'timemodified' => !empty($gradedata->timemodified) ? (int)$gradedata->timemodified : time(),
            ]);

            \core\task\manager::queue_adhoc_task($task);

        } catch (\Throwable $e) {
            debugging('Error in local_grade_notifier: ' . $e->getMessage(), DEBUG_DEVELOPER);
        }
    }
}
