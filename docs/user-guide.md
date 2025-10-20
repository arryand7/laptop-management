# Pedoman Penggunaan Sistem Peminjaman Laptop

## 1. Gambaran Umum

Aplikasi ini dirancang untuk mengelola peminjaman laptop di lingkungan sekolah dengan dukungan QR code, pelacakan pelanggaran, sanksi otomatis, dan laporan siap ekspor. Semua proses tercatat dan dapat dipantau melalui dashboard.

## 2. Persiapan Awal

1. Login sebagai **Admin** (`admin@school.test` / `password`).
2. Lengkapi data siswa dan laptop:
   - Menu **Data Siswa** untuk tambah/ubah/hapus, import Excel, serta cetak kartu QR. Template import membutuhkan kolom: `name`, `email`, `role`, `student_number`, `card_code`, `classroom`, `phone`, `is_active` (opsional `password`, `qr_code`).
   - Menu **Data Laptop** untuk tambah/ubah/hapus, import Excel, menautkan laptop ke pemilik (siswa), dan cetak label QR. Template import menerima kolom: `name`, `brand`, `model`, `serial_number`, `status`, `owner_student_number`, `spec_cpu`, `spec_ram`, `spec_storage`, `spec_os`.
3. Cetak dan bagikan QR kepada siswa serta tempelkan pada laptop. Data contoh sudah memetakan setiap laptop ke pemilik siswa (mis. Aisyah ↔ Lenovo ThinkBook).

## 3. Alur Peminjaman

1. Petugas (role **Staff**) buka menu **Form Peminjaman**.
2. Scan kartu siswa (`student_qr`) atau ketik NIS/nama/kode-kartu pada kolom Identitas Siswa. Saat mengetik, daftar saran akan muncul dan bisa diklik.
3. Scan QR pada laptop yang ingin dipinjam (`laptop_qr`).
4. Isi keperluan dan atur batas pengembalian (default 4 jam, dapat disesuaikan).
5. Simpan. Sistem otomatis:
   - Membuat kode transaksi unik.
   - Mengubah status laptop menjadi `Dipinjam`.
   - Mencatat waktu pinjam dan jatuh tempo.
   - Menambahkan entri ke debug timeline (jika `APP_DEBUG=true`).

## 4. Alur Pengembalian

1. Petugas buka menu **Form Pengembalian**.
2. Scan atau ketik identitas siswa dan laptop; daftar saran akan muncul otomatis ketika Anda mulai mengetik.
3. Tambahkan catatan kondisi jika perlu.
4. Simpan. Sistem otomatis:
   - Mengembalikan status laptop menjadi tersedia.
   - Menghitung menit keterlambatan jika melewati jatuh tempo.
   - Menambah pelanggaran serta cek batas sanksi (`LENDING_VIOLATION_LIMIT`).
   - Jika melewati batas, membuat sanksi selama `LENDING_SANCTION_LENGTH_DAYS`.

## 5. Pelanggaran & Sanksi

- Setiap keterlambatan menambah kolom `violations_count` pada data siswa dan membuat entri di tabel pelanggaran.
- Admin dapat menandai pelanggaran sebagai selesai dari menu **Pelanggaran** (mengurangi counter siswa).
- Admin dapat melihat dan mengelola sanksi pada menu **Sanksi**:
  - `Selesai`: menandai sanksi berakhir lebih cepat.
  - `Cabut`: mencabut sanksi aktif.
- Debounce otomatis: ketika masa sanksi berakhir, sistem (melalui `SanctionService`) menghapus tanggal sanksi aktif.

## 6. Dashboard

- **Admin/Staff**: melihat ringkasan peminjaman, grafik 7 hari, statistik keterlambatan mingguan, peminjaman aktif, dan top pelanggar.
- **Siswa**: melihat peminjaman aktif, total pelanggaran, status sanksi, serta riwayat lengkap.
- Data yang dipakai dashboard dihitung di `DashboardController` dan dicatat dalam debug timeline.

## 7. Laporan

1. Buka menu **Laporan**.
2. Pilih rentang tanggal dan status data.
3. Klik **Terapkan** untuk menampilkan preview.
4. Gunakan tombol **Export Excel** atau **Export PDF** untuk unduhan cepat.

> **Tips:** Halaman Data Siswa menampilkan jumlah laptop yang dimiliki tiap siswa, dan detail siswa memuat daftar perangkat pribadi lengkap dengan tautan cepat ke detail laptop.

## 11. Manajemen User & Profil

- **Admin → Manajemen User**: Tambah/ubah/nonaktifkan akun admin atau staff. Password default `password` bila tidak diisi saat pembuatan.
- **Import Excel** untuk user tidak tersedia; gunakan form tambah manual agar kontrol peran lebih ketat.
- **Profil Saya**: Admin & staff dapat membuka menu *Profil* di header untuk memperbarui nama, email, telepon, dan foto profil (JPG/PNG maks 1MB) serta mengganti kata sandi (dengan konfirmasi).

## 8. Debug Timeline

- Aktif bila `APP_DEBUG=true`.
- Panel muncul di pojok kanan bawah di semua halaman.
- Klik untuk membuka/menutup daftar log proses, termasuk konteks dan cap waktu.
- Sangat berguna saat pelatihan petugas atau sebelum deployment untuk memastikan alur berjalan sesuai SOP.

## 9. Konfigurasi

Parameter penting dapat diatur melalui `.env`:

| Kunci | Keterangan |
|-------|------------|
| `LENDING_DEFAULT_DUE_HOURS` | Durasi peminjaman default. |
| `LENDING_VIOLATION_LIMIT` | Jumlah pelanggaran untuk memicu sanksi. |
| `LENDING_SANCTION_LENGTH_DAYS` | Lama sanksi otomatis. |

## 10. Pengujian & Pemeliharaan

- Jalankan `php artisan test` setelah melakukan perubahan.
- Gunakan `php artisan migrate --seed` di lingkungan pengujian untuk memulai ulang basis data dengan data contoh.
- Simpan backup QR code setelah dicetak untuk kemudahan re-print.

Selamat menggunakan! Dokumentasi ini dapat diperluas sesuai SOP internal sekolah.
