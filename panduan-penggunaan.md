# Panduan Penggunaan CRM_SIVMI

Panduan ini ditujukan untuk user operasional, admin cabang, superadmin, dan direksi dalam menggunakan aplikasi CRM_SIVMI.

## 1. Login

1. Buka alamat aplikasi.
2. Masukkan email dan password.
3. Klik **Masuk**.
4. Jika login gagal, pastikan:
   - email benar,
   - password benar,
   - akun masih aktif,
   - role dan cabang sudah diatur oleh superadmin.

## 2. Hak Akses Role

| Role | Akses Lihat | Akses Input/Edit | Menu Khusus |
| --- | --- | --- | --- |
| `superadmin` | Semua data | Tidak input/edit leads | Pengaturan, backup, log aktivitas |
| `admin` | Semua leads | Edit leads cabang sendiri | Dashboard performa cabang |
| `staff` | Semua leads | Edit leads miliknya sendiri | Dashboard target pribadi |
| `direksi` | Semua data | Lihat saja | Ringkasan semua cabang, log aktivitas |

Catatan:

- Superadmin berfungsi sebagai pengatur sistem: kelola master data, user, target, backup, dan log aktivitas. Superadmin tidak melakukan input/edit leads, import leads, hapus leads, atau follow up.
- Admin dapat melihat semua data, tetapi hanya dapat mengubah data pada cabangnya.
- Staff dapat melihat semua data, tetapi hanya dapat mengubah leads yang menjadi miliknya.
- Direksi tidak dapat input, edit, hapus, import, atau mencatat follow up.

## 3. Dashboard

Menu **Dashboard** menampilkan ringkasan performa berdasarkan role.

### Superadmin

- Melihat semua cabang.
- Bisa filter bulan, tahun, cabang, admin, dan staff.
- Cocok untuk monitoring global sistem.

### Admin

- Melihat ringkasan seluruh data input user.
- Bisa memakai filter cabang, admin, dan staff.
- Tetap hanya dapat mengubah data leads sesuai hak akses cabangnya.

### Staff

- Melihat ringkasan seluruh data input user.
- Bisa memakai filter untuk melihat data pribadi atau staff tertentu.
- Tetap hanya dapat mengubah leads miliknya sendiri.

### Direksi

- Ringkasan semua cabang.
- Lihat performa tanpa akses edit.

Dashboard juga memiliki panel **Dashboard Closing** untuk melihat:

- total siswa closing pada periode filter,
- nominal pembayaran yang tercatat,
- closing berdasarkan program final,
- status pembayaran closing,
- closing per cabang.

Panel **Target dan Konversi** menampilkan:

- target leads pada periode filter,
- target closing,
- capaian leads aktif,
- capaian closing,
- rasio konversi leads ke closing.

Secara default dashboard memakai kumpulan data dari seluruh user yang input data. Filter cabang/admin/staff digunakan hanya untuk menyaring tampilan, bukan membatasi akses lihat dashboard.

Panel **Ranking** menampilkan urutan performa:

- default menampilkan ranking cabang,
- jika filter cabang/staff digunakan, data ranking mengikuti filter tersebut.

## 4. Data Leads

Menu **Data Leads** digunakan untuk melihat, menambah, mengedit, menghapus, import, export, dan membuka detail leads.

### Menambah Leads

1. Buka **Data Leads**.
2. Klik **Tambah Leads**.
3. Isi data utama:
   - nama,
   - nomor WA,
   - asal sekolah,
   - tingkatan jenjang,
   - kota asal,
   - program,
   - status,
   - cabang,
   - sumber,
   - tanggal masuk.
4. Klik **Simpan**.

Catatan:

- Nomor WA yang sama akan ditolak untuk mencegah input ganda.
- Field asal sekolah memiliki autosuggest dari `database/sekolahVM.json`, tetapi tetap bisa diisi manual.
- Jika asal sekolah manual belum ada di referensi JSON, sistem akan menyimpannya ke master sekolah baru setelah leads berhasil disimpan.
- Untuk jenjang `SMA`, asal sekolah wajib memakai format `SMAN` jika negeri dan `SMAS` jika swasta. Contoh: `SMAN 1 Bandung`, `SMAS Al Azhar 1`.
- Tingkatan jenjang hanya boleh diisi dengan `SD`, `SMP`, `SMA`, atau `Gapyear`.
- Cabang hanya dapat diubah oleh superadmin. Role lain otomatis memakai cabang akunnya.
- Jika status diubah menjadi `Daftar`, leads akan masuk ke **Data Siswa** dan tidak tampil lagi di daftar leads aktif.
- Saat status `Daftar`, lengkapi field closing: tanggal daftar, program final, nominal pembayaran, status pembayaran, kelas/angkatan, dan catatan administrasi.

### Mengedit Leads

1. Buka **Data Leads**.
2. Klik **Edit** pada leads yang boleh diubah.
3. Perbarui data.
4. Klik **Simpan**.

Jika tombol edit tidak muncul dan hanya tampil **Lihat saja**, berarti user tidak memiliki hak edit untuk data tersebut.

### Detail Leads

Klik **Detail** untuk melihat:

- profil leads,
- nomor WA yang disamarkan sesuai aturan akses,
- jumlah follow up,
- hasil follow up terakhir,
- jadwal follow up aktif berikutnya,
- status overdue,
- riwayat follow up,
- tugas terkait.

### Pilih Banyak Data

1. Centang leads yang akan diproses.
2. Pilih aksi:
   - **Export terpilih**,
   - **Hapus terpilih** jika punya hak akses.
3. Klik **Jalankan**.

## 5. Import Leads CSV

Menu import digunakan untuk memasukkan banyak leads dari file CSV.

### Cara Import

1. Buka **Data Leads**.
2. Klik **Contoh File** untuk mengunduh format CSV.
3. Isi file sesuai kolom contoh.
4. Klik **Import**.
5. Pilih file CSV.
6. Sistem akan memproses baris valid dan menampilkan preview error untuk baris gagal.

### Validasi Import

Saat import, sistem akan memeriksa:

- kolom wajib,
- nama wajib diisi,
- nomor WA duplikat dengan data sistem,
- nomor WA duplikat di file import,
- status tidak valid,
- cabang tidak valid,
- cabang tidak sesuai akses user,
- tujuan `diserahkan_ke` tidak valid,
- tanggal masuk tidak valid.

Jika ada baris gagal, sistem menampilkan **Preview Error Import** yang berisi:

- nomor baris,
- nama,
- nomor WA,
- alasan gagal.

Perbaiki baris yang gagal di CSV, lalu import ulang hanya data yang belum berhasil masuk.

## 6. Export Leads

Menu **Data Leads** menyediakan export:

- **Export** untuk mengunduh data sesuai filter aktif.
- **Export terpilih** untuk mengunduh data yang dicentang.

Catatan:

- Nomor WA disamarkan untuk user yang bukan pemilik leads.
- Export mengikuti hak akses dan filter yang digunakan.

## 7. Follow Up

Menu **Follow Up** digunakan untuk mencatat aktivitas follow up dan memantau jadwal berikutnya.

### Mencatat Follow Up

1. Buka **Follow Up**.
2. Pilih leads.
3. Isi:
   - tanggal follow up,
   - metode,
   - hasil,
   - prioritas,
   - jadwal follow up berikutnya,
   - catatan percakapan,
   - tindak lanjut.
4. Klik **Simpan Follow Up**.

Setelah follow up disimpan:

- jumlah follow up per leads bertambah,
- hasil terakhir diperbarui,
- status leads diperbarui sesuai hasil,
- jadwal berikutnya masuk reminder jika tanggalnya hari ini atau terlewat.

### Status Jadwal

| Status | Arti |
| --- | --- |
| `Belum follow up` | Leads belum pernah dicatat follow up |
| `Belum dijadwalkan` | Sudah pernah follow up, tetapi tidak ada jadwal berikutnya |
| `Terjadwal` | Ada jadwal follow up mendatang |
| `Hari ini` | Jadwal follow up jatuh pada hari ini |
| `Overdue` | Jadwal follow up sudah lewat dan belum selesai |

### Reminder Notifikasi

Sistem membuat reminder notifikasi untuk:

- follow up hari ini,
- follow up overdue.

Reminder muncul di menu **Notifikasi** dan panel **Reminder Follow Up**.

## 8. Data Siswa

Menu **Data Siswa** menampilkan leads dengan status `Daftar`.

Gunakan menu ini untuk melihat data closing, termasuk:

- nama,
- asal sekolah,
- nomor WA tersamarkan sesuai akses,
- program final,
- status pembayaran,
- nominal pembayaran,
- cabang,
- sumber,
- tanggal closing,
- kelas/angkatan,
- catatan administrasi.

Klik **Detail** pada Data Siswa untuk melihat:

- profil siswa dan asal leads,
- administrasi closing,
- riwayat perubahan status,
- riwayat follow up sebelum closing,
- tugas terkait siswa.

Klik **Export Data Siswa** untuk mengunduh data closing sesuai filter aktif. File export berisi data administrasi closing seperti program final, status pembayaran, nominal pembayaran, tanggal closing, kelas/angkatan, dan catatan administrasi.

## 9. Profil User

Menu **Profil User** berisi:

- informasi akun,
- avatar,
- media sosial,
- dashboard personal,
- TIM,
- Tugas,
- Laporan,
- Pembelajaran.

### Media Sosial

User dapat mengisi:

- Facebook,
- Instagram,
- TikTok,
- Blog,
- Channel YouTube.

## 10. TIM

Menu **TIM** digunakan untuk melihat anggota aktif, role, cabang, dan ringkasan kolaborasi tim.

## 11. Tugas

Menu **Tugas** digunakan untuk task management.

Fitur utama:

- membuat tugas,
- menghubungkan tugas ke leads,
- mengatur prioritas,
- mengatur tenggat,
- mengubah status,
- memberi komentar.

Direksi hanya melihat data tanpa mengubah.

## 12. Laporan

Menu **Laporan** menampilkan ringkasan report leads, status, cabang, dan rasio closing milik user login.

Gunakan menu ini untuk evaluasi berkala.

Tombol **Export Leads & Closing Saya** mengunduh data leads aktif dan closing yang menjadi milik user login. Data user lain tidak masuk ke file export laporan.

## 13. Pembelajaran

Menu **Pembelajaran** berisi materi dan sub materi seperti online course.

Fitur:

- daftar materi,
- topik/tema materi,
- sub materi,
- embed video YouTube,
- progress belajar.

Superadmin dapat mengelola materi dan sub materi.

## 14. Notifikasi

Menu **Notifikasi** menampilkan pemberitahuan sistem, seperti:

- leads baru,
- perubahan status leads,
- leads ditugaskan,
- follow up baru,
- reminder follow up,
- import selesai.

User dapat menandai notifikasi sebagai sudah dibaca.

## 15. Pengaturan

Menu **Pengaturan** hanya untuk superadmin.

Fitur:

- CRUD Cabang,
- CRUD Sumber Leads,
- CRUD Program,
- Manajemen Role User,
- tambah user,
- target kinerja bulanan,
- backup data.

### Target Kinerja Bulanan

Superadmin dapat mengatur:

- bulan dan tahun target,
- tipe target `cabang` atau `staff`,
- target leads,
- target closing.

Target ini digunakan di dashboard untuk menghitung capaian dan ranking.

### Manajemen Role User

Superadmin dapat mengatur:

- nama user,
- email,
- password awal,
- role,
- cabang,
- status aktif.

Cabang wajib diisi untuk role:

- admin,
- staff.

Cabang dikosongkan untuk:

- superadmin,
- direksi.

## 16. Backup dan Restore

### Backup

1. Login sebagai `superadmin`.
2. Buka **Pengaturan**.
3. Cari panel **Backup dan Restore Data**.
4. Klik **Export Backup SQL**.
5. Simpan file `.sql` di tempat aman.

Backup berisi data penting:

- master cabang,
- sumber leads,
- program,
- user,
- leads,
- follow up,
- tugas,
- pembelajaran,
- notifikasi,
- log aktivitas.

### Restore

Restore dilakukan manual dari phpMyAdmin atau terminal MySQL.

Ringkasannya:

1. Backup database aktif terlebih dahulu.
2. Jalankan migration jika server baru:

```bash
php artisan migrate --force
```

3. Import file `.sql` melalui phpMyAdmin atau terminal:

```bash
mysql -u DB_USERNAME -p DB_DATABASE < backup-crm-sivmi-YYYYMMDD-HHMMSS.sql
```

4. Bersihkan cache:

```bash
php artisan config:clear
php artisan cache:clear
php artisan view:clear
```

Panduan teknis lebih detail tersedia di `dokumentasi.md`.

## 17. Log Aktivitas

Menu **Log Aktivitas** tersedia untuk superadmin dan direksi.

Log mencatat:

- user,
- role,
- cabang,
- aksi,
- modul,
- route,
- URL,
- status response,
- waktu aktivitas.

## 18. Tips Operasional

- Gunakan nomor WA unik agar tidak terjadi input ganda.
- Catat semua interaksi leads di Follow Up agar histori lengkap.
- Isi jadwal follow up berikutnya setelah setiap aktivitas.
- Periksa notifikasi reminder setiap hari.
- Gunakan filter bulan dan tahun untuk evaluasi performa.
- Lakukan backup sebelum deploy, migrasi, atau import data besar.
- Jangan membagikan file backup karena berisi data sensitif.
