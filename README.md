# Grade Notifier to Supervisor

Plugin Moodle (`local_grade_notifier`) yang mengirimkan notifikasi email hasil kuis secara otomatis ke peserta dan atasan/supervisor mereka.

## Fitur Utama

- **Pengiriman Asinkron (Adhoc Task):** Menggunakan antrean background task resmi Moodle (`\core\task\adhoc_task`) sehingga halaman submit kuis peserta tetap cepat dan tidak terhambat proses SMTP.
- **Pencegahan Email Duplikat:** Menyimpan riwayat notifikasi pada tabel database `{local_grade_notifier_logs}` untuk mencegah pengiriman email berulang pada regrade/penghitungan ulang gradebook.
- **Penanganan Nilai Esai (`null` grade):** Tidak mengirimkan nilai 0 prematur jika kuis masih memerlukan penilaian manual oleh pengajar.
- **Dukungan Banyak Email Supervisor:** Mendukung pengisian lebih dari satu email atasan (dipisahkan koma `,`, titik koma `;`, atau spasi).
- **Deteksi Akun Moodle Supervisor:** Jika email atasan terdaftar di Moodle, notifikasi akan dikirimkan dengan preferensi dan nama akun aslinya.
- **Status Kelulusan (Pass / Fail):** Menampilkan badge status **LULUS** (hijau) atau **BELUM LULUS** (merah) jika kuis memiliki passing grade (*Grade to pass*).
- **Tampilan Email Responsif & Modern:** Template HTML berbentuk card dengan tombol tautan langsung ke halaman kuis.
- **Menu Konfigurasi Admin (`settings.php`):** Pengaturan fleksibel melalui UI Moodle:
  - Toggle aktif/nonaktif plugin
  - Kustomisasi shortname custom profile field (default: `supervisor_email`)
  - Pilihan kirim notifikasi ke peserta (Ya/Tidak)
  - Pilihan kirim notifikasi ke atasan (Ya/Tidak)
  - Tampilkan/sembunyikan status kelulusan
  - Opsi hanya kirim jika nilai lulus
- **Multi-Bahasa (Lokalisasi):** Tersedia dalam Bahasa Inggris (`en`) dan Bahasa Indonesia (`id`).

---

## Cara Kerja

1. Pengguna menyelesaikan kuis dan nilai tersimpan di Moodle gradebook (memicu event `\core\event\user_graded`).
2. Observer ([`classes/observer.php`](classes/observer.php)) memvalidasi tipe modul kuis, mengecek apakah nilai bukan `null`, memeriksa kriteria kelulusan, dan memastikan belum pernah dinotifikasi.
3. Observer mendaftarkan Adhoc Task ([`classes/task/send_notification_task.php`](classes/task/send_notification_task.php)) ke antrean background cron Moodle.
4. Worker Moodle cron mengeksekusi pengiriman email ke peserta dan supervisor secara asinkron lalu mencatat riwayat pengiriman ke tabel log.

---

## Persyaratan

- Moodle **4.0+** (versi 2022041900 ke atas)
- Custom Profile Field bertipe teks untuk menyimpan email atasan (default shortname: `supervisor_email`)

---

## Instalasi & Pengaturan

1. Salin folder plugin ke direktori `local/grade_notifier/` di instalasi Moodle Anda:
   ```bash
   cp -r grade_notifier /path/to/moodle/local/grade_notifier
   ```
2. Login sebagai admin dan buka **Site administration > Notifications** untuk menyelesaikan proses instalasi/upgrade database.
3. Buat Custom Profile Field untuk email atasan:
   - Buka **Site administration > Users > User profile fields**
   - Buat field baru (tipe Text Input) dengan shortname `supervisor_email` (atau sesuaikan sesuai preferensi)
   - Isi field ini pada profil peserta dengan email atasan mereka (dapat diisi lebih dari satu email dipisah koma)
4. Konfigurasi Plugin:
   - Buka **Site administration > Plugins > Local plugins > Grade Notifier to Supervisor**
   - Sesuaikan opsi pengiriman email sesuai kebutuhan organisasi Anda.

---

## Struktur File Plugin

```
local/grade_notifier/
├── classes/
│   ├── observer.php                  # Event observer user_graded
│   └── task/
│       └── send_notification_task.php # Adhoc task pengiriman email asinkron
├── db/
│   ├── events.php                    # Registrasi event observer
│   ├── install.xml                   # Skema tabel log database
│   └── upgrade.php                   # Skrip upgrade skema database
├── lang/
│   ├── en/
│   │   └── local_grade_notifier.php  # String bahasa Inggris
│   └── id/
│       └── local_grade_notifier.php  # String bahasa Indonesia
├── settings.php                      # Menu pengaturan Site Administration
├── version.php                       # Versi dan metadata plugin
└── README.md                         # Dokumentasi plugin
```

