# Grade Notifier to Supervisor

Plugin Moodle (`local_grade_notifier`) yang mengirimkan notifikasi email hasil kuis secara otomatis ke peserta dan atasan/supervisor mereka.

## Cara Kerja

Plugin ini memanfaatkan event `user_graded` di Moodle. Ketika peserta menyelesaikan kuis dan mendapat nilai, plugin akan:

1. Mendeteksi event penilaian dari modul **Quiz**
2. Mengambil data kuis, kursus, dan peserta
3. Mengirim email notifikasi berisi laporan hasil kuis ke **peserta**
4. Mengirim email yang sama ke **atasan/supervisor** (jika email atasan terisi)

## Format Email

Email notifikasi berisi informasi:

- Nama dan email peserta
- Nama pelatihan/kursus
- Nama kuis
- Skor yang diperoleh (nilai / nilai maksimal)
- Waktu penilaian

## Persyaratan

- Moodle **4.0+** (versi 2022041900 ke atas)
- Custom Profile Field dengan shortname `supervisor_email` untuk menyimpan email atasan

## Instalasi

1. Salin folder plugin ke `local/grade_notifier/` di direktori Moodle
2. Login sebagai admin dan ikuti proses instalasi plugin
3. Buat Custom Profile Field untuk email atasan:
   - Buka **Site administration > Users > User profile fields**
   - Buat field baru dengan shortname `supervisor_email`
   - Isi field ini pada profil setiap peserta dengan email atasan mereka

## Struktur Plugin

```
local/grade_notifier/
├── classes/
│   └── observer.php       # Event observer - logika utama notifikasi
├── db/
│   └── events.php         # Definisi event yang diobservasi
├── lang/
│   └── en/
│       └── local_grade_notifier.php  # String bahasa
├── version.php            # Metadata versi plugin
└── README.md
```
