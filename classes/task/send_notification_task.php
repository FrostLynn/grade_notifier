<?php
namespace local_grade_notifier\task;

defined('MOODLE_INTERNAL') || die();

use core_user;
use stdClass;

/**
 * Adhoc task to send quiz grade notification emails asynchronously.
 *
 * @package    local_grade_notifier
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class send_notification_task extends \core\task\adhoc_task {

    /**
     * Get a descriptive name for this task.
     *
     * @return string
     */
    public function get_name(): string {
        return get_string('task_send_notification', 'local_grade_notifier');
    }

    /**
     * Execute the task.
     */
    public function execute() {
        global $DB, $CFG;

        $data = $this->get_custom_data();
        if (!$data || empty($data->userid) || empty($data->quizid) || empty($data->courseid)) {
            return;
        }

        // Defensive check: verify if already logged in database to prevent duplicates.
        $dbman = $DB->get_manager();
        if ($dbman->table_exists('local_grade_notifier_logs')) {
            $already_notified = $DB->record_exists('local_grade_notifier_logs', [
                'userid'      => $data->userid,
                'quizid'      => $data->quizid,
                'gradeitemid' => $data->gradeitemid,
                'finalgrade'  => $data->finalgrade,
            ]);
            if ($already_notified) {
                return;
            }
        }

        // Fetch student user.
        $user = core_user::get_user($data->userid);
        if (!$user || $user->deleted) {
            return;
        }

        // Fetch quiz and course.
        $quiz = $DB->get_record('quiz', ['id' => $data->quizid]);
        if (!$quiz) {
            return;
        }

        $course = $DB->get_record('course', ['id' => $data->courseid]);
        if (!$course) {
            return;
        }

        // Course module URL for direct access to quiz.
        $cm = get_coursemodule_from_instance('quiz', $quiz->id, $course->id);
        $quizurl = $cm ? new \moodle_url('/mod/quiz/view.php', ['id' => $cm->id]) : null;

        $finalgrade = round((float)$data->finalgrade, 2);
        $maxgrade = round((float)$data->maxgrade, 2);
        $gradepass = isset($data->gradepass) ? (float)$data->gradepass : 0.0;

        $showpassfail = get_config('local_grade_notifier', 'show_passfail');
        if ($showpassfail === false) {
            $showpassfail = 1;
        }

        $percentage = ($maxgrade > 0) ? round(($finalgrade / $maxgrade) * 100, 1) : null;
        $haspassfail = ((bool)$showpassfail && $gradepass > 0);
        $ispassed = $haspassfail ? ($finalgrade >= $gradepass) : null;

        $fromuser = core_user::get_noreply_user();
        $sitename = !empty($CFG->sitename) ? format_string($CFG->sitename) : 'Moodle';

        $notifystudent = get_config('local_grade_notifier', 'notify_student');
        if ($notifystudent === false) {
            $notifystudent = 1;
        }

        $notifysupervisor = get_config('local_grade_notifier', 'notify_supervisor');
        if ($notifysupervisor === false) {
            $notifysupervisor = 1;
        }

        // 1. Send notification to student.
        if ($notifystudent) {
            $this->send_notification_email(
                $user,
                $fromuser,
                $user,
                $course,
                $quiz,
                $quizurl,
                $finalgrade,
                $maxgrade,
                $percentage,
                $haspassfail,
                $ispassed,
                $data->timemodified,
                $sitename,
                $user->lang
            );
        }

        // 2. Send notification to supervisor(s).
        if ($notifysupervisor) {
            $supervisor_emails = $this->get_supervisor_emails($user->id);
            foreach ($supervisor_emails as $email) {
                // Check if supervisor has a real account in Moodle.
                $supuser = $DB->get_record('user', ['email' => $email, 'deleted' => 0]);
                if ($supuser) {
                    $suplang = !empty($supuser->lang) ? $supuser->lang : ($CFG->lang ?? 'en');
                } else {
                    $supuser = new stdClass();
                    $supuser->id = -99;
                    $supuser->email = $email;
                    $supuser->firstname = $this->get_lang_string('supervisor', null, $CFG->lang ?? 'en');
                    $supuser->lastname = '';
                    $supuser->maildisplay = 1;
                    $supuser->mailformat = 1;
                    $supuser->deleted = 0;
                    $supuser->suspended = 0;
                    $supuser->auth = 'manual';
                    $supuser->mnethostid = $CFG->mnet_localhost_id;
                    $suplang = $CFG->lang ?? 'en';
                }

                $this->send_notification_email(
                    $supuser,
                    $fromuser,
                    $user,
                    $course,
                    $quiz,
                    $quizurl,
                    $finalgrade,
                    $maxgrade,
                    $percentage,
                    $haspassfail,
                    $ispassed,
                    $data->timemodified,
                    $sitename,
                    $suplang
                );
            }
        }

        // 3. Record log to prevent double-sends in case of regrade/recalculations.
        if ($dbman->table_exists('local_grade_notifier_logs')) {
            $log = new stdClass();
            $log->userid       = $user->id;
            $log->courseid     = $course->id;
            $log->quizid       = $quiz->id;
            $log->gradeitemid  = $data->gradeitemid;
            $log->finalgrade   = $finalgrade;
            $log->timenotified = time();
            $DB->insert_record('local_grade_notifier_logs', $log);
        }
    }

    /**
     * Retrieve and parse supervisor email(s) from custom profile field.
     * Supports multiple comma/semicolon/space separated emails.
     *
     * @param int $userid
     * @return array
     */
    protected function get_supervisor_emails(int $userid): array {
        global $DB;

        $profilefield = get_config('local_grade_notifier', 'profilefield');
        if (empty($profilefield)) {
            $profilefield = 'supervisor_email';
        }

        $sql = "SELECT d.data 
                  FROM {user_info_data} d 
                  JOIN {user_info_field} f ON d.fieldid = f.id 
                 WHERE f.shortname = :shortname AND d.userid = :userid";
        $raw = $DB->get_field_sql($sql, [
            'shortname' => $profilefield,
            'userid'    => $userid,
        ]);

        if (empty($raw)) {
            return [];
        }

        $emails = [];
        $parts = preg_split('/[;,\\s]+/', trim($raw), -1, PREG_SPLIT_NO_EMPTY);
        foreach ($parts as $part) {
            $clean = trim($part);
            if (validate_email($clean)) {
                $emails[] = $clean;
            }
        }

        return array_unique($emails);
    }

    /**
     * Get a localized string for a specific recipient language.
     *
     * @param string $identifier
     * @param mixed $a
     * @param string|null $lang
     * @return string
     */
    protected function get_lang_string(string $identifier, $a = null, ?string $lang = null): string {
        global $CFG;
        $targetlang = !empty($lang) ? $lang : (!empty($CFG->lang) ? $CFG->lang : 'en');
        return get_string_manager()->get_string($identifier, 'local_grade_notifier', $a, $targetlang);
    }

    /**
     * Format and dispatch notification email.
     *
     * @param stdClass $recipient
     * @param stdClass $fromuser
     * @param stdClass $student
     * @param stdClass $course
     * @param stdClass $quiz
     * @param \moodle_url|null $quizurl
     * @param float $finalgrade
     * @param float $maxgrade
     * @param float|null $percentage
     * @param bool $haspassfail
     * @param bool|null $ispassed
     * @param int $timemodified
     * @param string $sitename
     * @param string|null $lang
     */
    protected function send_notification_email(
        $recipient,
        $fromuser,
        $student,
        $course,
        $quiz,
        $quizurl,
        $finalgrade,
        $maxgrade,
        $percentage,
        $haspassfail,
        $ispassed,
        $timemodified,
        $sitename,
        ?string $lang
    ) {
        $studentfullname = fullname($student);
        $quizname = format_string($quiz->name);
        $coursename = !empty($course->fullname) ? format_string($course->fullname) : 'Course';
        $gradedtime = !empty($timemodified) ? userdate($timemodified) : userdate(time());

        // Subject.
        $subjectdata = (object)[
            'quizname' => $quizname,
            'fullname' => $studentfullname,
            'sitename' => $sitename,
        ];
        $subject = $this->get_lang_string('email_subject', $subjectdata, $lang);

        // Labels.
        $titlelabel     = $this->get_lang_string('email_title', null, $lang);
        $studentlabel   = $this->get_lang_string('email_student_name', null, $lang);
        $courselabel    = $this->get_lang_string('email_course', null, $lang);
        $quizlabel      = $this->get_lang_string('email_quiz', null, $lang);
        $scorelabel     = $this->get_lang_string('email_score', null, $lang);
        $datelabel      = $this->get_lang_string('email_grade_date', null, $lang);
        $statuslabel    = $this->get_lang_string('email_status', null, $lang);
        $viewquizlabel  = $this->get_lang_string('email_view_quiz', null, $lang);
        $footerlabel    = $this->get_lang_string('email_footer', (object)['sitename' => $sitename], $lang);

        // Status badge HTML.
        $statusrow = '';
        if ($haspassfail) {
            if ($ispassed) {
                $statusbadge = '<span style="display:inline-block;padding:4px 12px;font-size:12px;font-weight:700;line-height:1;color:#15803d;background-color:#dcfce7;border-radius:9999px;border:1px solid #bbf7d0;">' 
                    . $this->get_lang_string('status_passed', null, $lang) 
                    . '</span>';
            } else {
                $statusbadge = '<span style="display:inline-block;padding:4px 12px;font-size:12px;font-weight:700;line-height:1;color:#b91c1c;background-color:#fee2e2;border-radius:9999px;border:1px solid #fecaca;">' 
                    . $this->get_lang_string('status_failed', null, $lang) 
                    . '</span>';
            }
            $statusrow = "
                <tr>
                    <td style=\"padding:10px 14px;border-bottom:1px solid #f1f5f9;color:#64748b;font-size:14px;font-weight:500;\">{$statuslabel}</td>
                    <td style=\"padding:10px 14px;border-bottom:1px solid #f1f5f9;color:#0f172a;font-size:14px;\">{$statusbadge}</td>
                </tr>";
        }

        // Score display.
        $scoredisplay = "{$finalgrade} / {$maxgrade}";
        if ($percentage !== null) {
            $scoredisplay .= " <span style=\"color:#64748b;font-size:13px;\">({$percentage}%)</span>";
        }

        // View Quiz Button.
        $buttonhtml = '';
        if ($quizurl) {
            $buttonurl = $quizurl->out(false);
            $buttonhtml = "
                <div style=\"text-align:center;margin:28px 0 12px 0;\">
                    <a href=\"{$buttonurl}\" style=\"background-color:#2563eb;color:#ffffff;padding:12px 26px;text-decoration:none;border-radius:6px;font-weight:600;font-size:14px;display:inline-block;\">{$viewquizlabel} &rarr;</a>
                </div>";
        }

        // HTML Message.
        $messagehtml = "
<!DOCTYPE html>
<html>
<head>
<meta charset=\"utf-8\">
</head>
<body style=\"margin:0;padding:24px 0;background-color:#f8fafc;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;\">
  <table role=\"presentation\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">
    <tr>
      <td align=\"center\">
        <table role=\"presentation\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"100%\" style=\"max-width:580px;background-color:#ffffff;border:1px solid #e2e8f0;border-radius:10px;overflow:hidden;box-shadow:0 4px 6px -1px rgba(0,0,0,0.05);\">
          <!-- Header -->
          <tr>
            <td style=\"background:linear-gradient(135deg, #1e40af 0%, #3b82f6 100%);padding:28px 24px;text-align:center;\">
              <h2 style=\"margin:0;color:#ffffff;font-size:20px;font-weight:700;letter-spacing:-0.01em;\">{$titlelabel}</h2>
              <p style=\"margin:6px 0 0 0;color:#e0e7ff;font-size:14px;\">{$sitename}</p>
            </td>
          </tr>
          <!-- Body -->
          <tr>
            <td style=\"padding:28px 24px;\">
              <table role=\"presentation\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"100%\" style=\"border-collapse:collapse;\">
                <tr>
                  <td style=\"padding:10px 14px;border-bottom:1px solid #f1f5f9;color:#64748b;font-size:14px;font-weight:500;width:35%;\">{$studentlabel}</td>
                  <td style=\"padding:10px 14px;border-bottom:1px solid #f1f5f9;color:#0f172a;font-size:14px;font-weight:600;\">{$studentfullname} <span style=\"color:#64748b;font-weight:400;\">({$student->email})</span></td>
                </tr>
                <tr>
                  <td style=\"padding:10px 14px;border-bottom:1px solid #f1f5f9;color:#64748b;font-size:14px;font-weight:500;\">{$courselabel}</td>
                  <td style=\"padding:10px 14px;border-bottom:1px solid #f1f5f9;color:#0f172a;font-size:14px;\">{$coursename}</td>
                </tr>
                <tr>
                  <td style=\"padding:10px 14px;border-bottom:1px solid #f1f5f9;color:#64748b;font-size:14px;font-weight:500;\">{$quizlabel}</td>
                  <td style=\"padding:10px 14px;border-bottom:1px solid #f1f5f9;color:#0f172a;font-size:14px;font-weight:600;\">{$quizname}</td>
                </tr>
                <tr>
                  <td style=\"padding:10px 14px;border-bottom:1px solid #f1f5f9;color:#64748b;font-size:14px;font-weight:500;\">{$scorelabel}</td>
                  <td style=\"padding:10px 14px;border-bottom:1px solid #f1f5f9;color:#0f172a;font-size:15px;font-weight:700;\">{$scoredisplay}</td>
                </tr>
                {$statusrow}
                <tr>
                  <td style=\"padding:10px 14px;border-bottom:1px solid #f1f5f9;color:#64748b;font-size:14px;font-weight:500;\">{$datelabel}</td>
                  <td style=\"padding:10px 14px;border-bottom:1px solid #f1f5f9;color:#0f172a;font-size:14px;\">{$gradedtime}</td>
                </tr>
              </table>
              {$buttonhtml}
            </td>
          </tr>
          <!-- Footer -->
          <tr>
            <td style=\"padding:18px 24px;background-color:#f8fafc;border-top:1px solid #e2e8f0;text-align:center;font-size:12px;color:#94a3b8;line-height:1.5;\">
              {$footerlabel}
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</body>
</html>
";

        $messagetext = html_to_text($messagehtml);

        email_to_user($recipient, $fromuser, $subject, $messagetext, $messagehtml);
    }
}
