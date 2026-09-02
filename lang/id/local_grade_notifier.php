<?php
defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Notifikasi Nilai ke Atasan';

// Settings.
$string['setting_enabled'] = 'Aktifkan Plugin';
$string['setting_enabled_desc'] = 'Jika diaktifkan, notifikasi email otomatis akan dikirim saat kuis selesai dinilai.';
$string['setting_profilefield'] = 'Shortname Field Profil Atasan';
$string['setting_profilefield_desc'] = 'Shortname dari custom profile field user yang menyimpan alamat email atasan/supervisor.';
$string['setting_notify_student'] = 'Notifikasi Peserta';
$string['setting_notify_student_desc'] = 'Kirim email notifikasi nilai ke peserta.';
$string['setting_notify_supervisor'] = 'Notifikasi Atasan';
$string['setting_notify_supervisor_desc'] = 'Kirim email notifikasi nilai ke atasan/supervisor.';
$string['setting_show_passfail'] = 'Tampilkan Status Kelulusan';
$string['setting_show_passfail_desc'] = 'Tampilkan badge status LULUS / BELUM LULUS pada email notifikasi jika passing grade kuis diatur.';
$string['setting_only_passing_grades'] = 'Hanya Kirim Jika Nilai Lulus';
$string['setting_only_passing_grades_desc'] = 'Jika diaktifkan, notifikasi hanya dikirim jika peserta mencapai atau melebihi passing grade kuis.';

// Email Content.
$string['supervisor'] = 'Atasan / Supervisor';
$string['email_subject'] = 'Hasil Nilai Kuis: {$a->quizname} - {$a->fullname}';
$string['email_title'] = 'Laporan Hasil Kuis Peserta';
$string['email_student_name'] = 'Nama Peserta';
$string['email_course'] = 'Pelatihan / Kursus';
$string['email_quiz'] = 'Kuis';
$string['email_score'] = 'Skor yang Diperoleh';
$string['email_percentage'] = 'Persentase';
$string['email_grade_date'] = 'Waktu Penilaian';
$string['email_status'] = 'Status Kelulusan';
$string['status_passed'] = 'LULUS';
$string['status_failed'] = 'BELUM LULUS';
$string['email_view_quiz'] = 'Lihat Detail Kuis';
$string['email_footer'] = 'Ini adalah notifikasi otomatis dari {$a->sitename}. Mohon tidak membalas email ini.';

// Task.
$string['task_send_notification'] = 'Kirim email notifikasi nilai kuis';
