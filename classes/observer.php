<?php
namespace local_grade_notifier;

defined('MOODLE_INTERNAL') || die();

use core_user;

class observer {
    public static function user_graded(\core\event\user_graded $event) {
        global $DB;

        try {
            $gradedata = $event->get_grade();
            $gradeitem = $gradedata->grade_item;

            // Pastikan hanya memproses nilai dari modul Quiz / Kuis
            if ($gradeitem->itemtype !== 'mod' || $gradeitem->itemmodule !== 'quiz') {
                return;
            }

            // Dapatkan kuis terkait
            $quiz = $DB->get_record('quiz', ['id' => $gradeitem->iteminstance]);
            if (!$quiz) {
                return;
            }

            // Dapatkan kursus
            $course = $DB->get_record('course', ['id' => $gradeitem->courseid]);
            $coursename = !empty($course->fullname) ? format_string($course->fullname) : 'Kursus';

            // Dapatkan user peserta
            $user = core_user::get_user($gradedata->userid);
            if (!$user) {
                return;
            }

            // Ambil nilai final yang sudah tuntas dihitung di gradebook
            $finalgrade = round((float)$gradedata->finalgrade, 2);
            $maxgrade = round((float)$gradeitem->grademax, 2);

            // Ambil email atasan dari Custom Profile Field
            $sql = "SELECT d.data 
                      FROM {user_info_data} d 
                      JOIN {user_info_field} f ON d.fieldid = f.id 
                     WHERE f.shortname = :shortname AND d.userid = :userid";
            $supervisor_email = $DB->get_field_sql($sql, [
                'shortname' => 'supervisor_email',
                'userid'    => $user->id
            ]);

            // Format teks
            $quizname   = format_string($quiz->name);
            $fullname   = fullname($user);
            $gradedtime = !empty($gradedata->timemodified) ? userdate($gradedata->timemodified) : userdate(time());

            // Susun Email
            $subject = "Hasil Nilai Kuis: {$quizname} - {$fullname}";
            $messagehtml = "
                <h3>Laporan Hasil Kuis Peserta</h3>
                <p><strong>Nama Peserta:</strong> {$fullname} ({$user->email})</p>
                <p><strong>Pelatihan / Kursus:</strong> {$coursename}</p>
                <p><strong>Kuis:</strong> {$quizname}</p>
                <p><strong>Skor yang Diperoleh:</strong> {$finalgrade} / {$maxgrade}</p>
                <p><strong>Waktu Penilaian:</strong> {$gradedtime}</p>
            ";
            $messagetext = html_to_text($messagehtml);
            $fromuser = core_user::get_noreply_user();

            // 1. Kirim ke Peserta
            email_to_user($user, $fromuser, $subject, $messagetext, $messagehtml);

            // 2. Kirim ke Atasan
            if (!empty($supervisor_email) && validate_email(trim($supervisor_email))) {
                $supervisor = new \stdClass();
                $supervisor->email = trim($supervisor_email);
                $supervisor->firstname = 'Atasan /';
                $supervisor->lastname = 'Supervisor';
                $supervisor->id = -99;
                $supervisor->maildisplay = 1;
                $supervisor->mailformat = 1;

                email_to_user($supervisor, $fromuser, $subject, $messagetext, $messagehtml);
            }
        } catch (\Throwable $e) {
            debugging('Error in local_grade_notifier: ' . $e->getMessage(), DEBUG_DEVELOPER);
        }
    }
}