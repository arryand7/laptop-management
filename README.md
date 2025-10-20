# Sistem Peminjaman Laptop Sekolah

Platform web berbasis **Laravel 12 + Vite (Tailwind)** untuk mendigitalisasi peminjaman laptop di lingkungan sekolah menengah. Sistem ini mencakup pencatatan data siswa & laptop, peminjaman/pengembalian berbasis QR, pengelolaan pelanggaran & sanksi otomatis, dashboard interaktif, laporan ekspor, serta **chatbot AI** untuk interaksi natural language.

---

## 🧰 Fitur Utama

- **Master Data** – CRUD siswa dan laptop, import Excel, cetak QR, dukungan kepemilikan personal siswa.
- **Manajemen Pengguna** – Admin dapat membuat/mengubah/nonaktifkan admin/staff, termasuk foto profil.
- **Peminjaman & Pengembalian** – Form cepat berbasis QR, otomatis menghitung jatuh tempo & keterlambatan.
- **Pelanggaran & Sanksi** – Pelanggaran bertambah saat terlambat, sanksi otomatis bila melebihi batas.
- **Dashboard & Statistik** – Grafik tren 30 hari, status laptop, daftar teratas pelanggar, aktivitas terbaru.
- **Laporan Ekspor** – Filter data & ekspor ke Excel/PDF.
- **Debug Timeline** – Saat `APP_DEBUG=true`, kronologi aktivitas dicatat untuk inspeksi developer.
- **Chatbot AI** – Mendukung perintah singkat (`pinjam/kembalikan`) dan pertanyaan analitis (laporan, statistik); terintegrasi dengan **OpenAI** atau **Google Gemini** dengan alur konfirmasi dua langkah.

---

## 👥 Peran & Akses Modul

| Peran  | Modul Utama | Ringkasan Akses |
|--------|-------------|-----------------|
| **Admin** | `admin.settings.*`, `admin.users`, `admin.students`, `admin.laptops`, `admin.violations`, `admin.sanctions`, `admin.reports`, `chatbot` | Mengelola master data, meninjau permintaan perubahan laptop, mengatur peraturan, mengelola pelanggaran/sanksi, melihat laporan, menggunakan chatbot. |
| **Staff** | `staff.borrow`, `staff.return`, `chatbot` (opsional) | Peminjaman/pengembalian via QR, chatbot jika diizinkan. |
| **Siswa** | `student.history`, `student.laptops` | Melihat riwayat pribadi & ajukan perubahan data laptop milik sendiri. |

Seeder menyediakan akun (password `password`):

| Role  | Email / Identitas |
|-------|-------------------|
| Admin | `admin@school.test` |
| Staff | `staff@school.test` |
| Siswa contoh | Lihat tabel NIS di seeder (mis. `STD2024001` Aisyah Rahma) |

Sampel laptop serta pinjaman aktif/lambat sudah disediakan untuk keperluan demo.

---

## ⚙️ Prasyarat

- PHP 8.2+
- Composer
- Node.js 18+
- SQLite (default) atau database lain yang didukung Laravel (ubah `.env` bila perlu)

---

## 🚀 Instalasi

```bash
git clone <repo>
cd laptop-management
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
npm run dev        # atau npm run build untuk production
php artisan serve # default http://localhost:8000
```

Gunakan `npm run dev` saat pengembangan untuk hot reload.

---

## 🔧 Pengaturan Penting

### Database & Otentikasi
Semua kredensial diatur melalui `.env`. Default menggunakan SQLite (`database/database.sqlite`).

### Peraturan Peminjaman (`.env` / `config/lending.php`)

| Kunci ENV | Default | Keterangan |
|-----------|---------|------------|
| `LENDING_DEFAULT_DUE_HOURS` | 4 | Jatuh tempo default (jam) bila mode relatif tidak diubah di panel. |
| `LENDING_VIOLATION_LIMIT` | 3 | Jumlah pelanggaran untuk memicu sanksi otomatis. |
| `LENDING_SANCTION_LENGTH_DAYS` | 7 | Lama sanksi otomatis (hari). |

### Pengaturan Sistem via UI

Menu **Admin → Pengaturan Sistem** kini memiliki 4 submenu:

1. **Identitas Aplikasi** – Nama aplikasi, deskripsi, kontak, dan logo.
2. **Peraturan Laptop** – Mode batas pengembalian: relatif (n hari), harian (jam tertentu), atau tanggal khusus.
3. **Pengaturan Email (SMTP)** – Host, port, enkripsi, username/password untuk reset password dan notifikasi.
4. **Integrasi AI** – API key & model untuk OpenAI/Gemini/HuggingFace.

Semua nilai disimpan dalam tabel `app_settings` dan dibaca oleh komponen terkait (peminjaman, chatbot, notifikasi).

### Integrasi Email SMTP

Atur via menu pengaturan atau langsung `.env`:

```
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=...
MAIL_PASSWORD=...
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=no-reply@school.test
MAIL_FROM_NAME="Laptop School"
```

### Integrasi AI

`.env.example` sudah menyertakan variabel:

```
OPENAI_API_KEY=
OPENAI_MODEL=gpt-4o-mini

GEMINI_API_KEY=
GEMINI_MODEL=gemini-1.5-flash
GEMINI_BASE_URL=https://generativelanguage.googleapis.com

HUGGINGFACE_API_KEY=
HUGGINGFACE_MODEL=mistralai/Mistral-7B-Instruct-v0.2
```

> Chatbot saat ini mendukung OpenAI dan Gemini secara langsung. HuggingFace disiapkan untuk penyimpanan kredensial dan dapat diintegrasikan kemudian.

---

## 🧭 Alur Operasional

1. **Admin** menginput data siswa & laptop, mencetak QR label/kartu.
2. **Staff** membuka menu *Peminjaman*, scan/ketik identitas siswa dan laptop.
3. Sistem menghitung jatuh tempo berdasarkan pengaturan default. Setelah jatuh tempo, pengembalian otomatis mencatat pelanggaran & sanksi jika melewati batas.
4. **Chatbot** dapat menerima perintah `pinjam/kembalikan` atau pertanyaan analitis. Alur:
   - Parse perintah → tampilkan ringkasan + tombol konfirmasi.
   - Setelah konfirmasi, transaksi dicatat dan token konfirmasi hangus.
   - Pertanyaan bebas dijawab dengan AI. Jika API gagal, chatbot menampilkan ringkasan statistik fallback.
5. **Admin** meninjau data melalui dashboard, pelanggaran & sanksi. Laporan dapat diekspor ke Excel/PDF.

---

## 🤖 Chatbot – Cara Pakai

### Perintah Singkat
- `pinjam 20231023 LPT-AX45`
- `kembalikan 20231023`
- `kembalikan 20231023 LPT-AX45`

### Pertanyaan Analitis
- “Tampilkan statistik peminjaman bulan ini”
- “Siapa saja yang terlambat mengembalikan?”
- “Buat ringkasan laporan minggu ini”

Chatbot meminta konfirmasi sebelum eksekusi permanen dan mencatat audit log (via `debug_event`). Jawaban analitis memerlukan API key provider yang valid.

---

## 📊 Laporan & Ekspor

- Akses **Admin → Laporan** untuk filter rentang tanggal & status.
- **Ekspor Excel** menggunakan `maatwebsite/excel`.
- **Ekspor PDF** memakai mPDF (layout lanskap).
- Audit log penting terekam melalui helper `debug_event()`.

---

## 🧪 Pengujian

```bash
php artisan test
```

Tes bawaan memastikan redirect login dan skenario dasar; tambahkan tes sesuai kebutuhan.

---

## 📁 Struktur Direktori Penting

```
app/
  Services/Ai/              # Klien OpenAI & Gemini
  Support/Chatbot/          # Parser, preview, commit, insights builder
resources/views/admin/settings/
  application.blade.php     # Identitas aplikasi
  lending.blade.php         # Peraturan batas waktu
  mail.blade.php            # Konfigurasi SMTP
  ai.blade.php              # Integrasi AI
resources/views/chatbot/    # UI chatbot
```

---

## 🛠️ Tips Pengembangan & Troubleshooting

- Gunakan panel **Debug Timeline** (pojok kanan bawah) saat `APP_DEBUG=true` untuk melihat query & event penting.
- Cek `storage/logs/laravel.log` jika chatbot menampilkan fallback — pesan `AI Insights request failed` memuat penyebab (API key salah, model tidak tersedia, dll.).
- Jalankan `php artisan config:clear` setelah mengubah `.env` terkait AI/SMTP.

---

## 🧾 Lisensi

Proyek ini memanfaatkan Laravel (MIT). Sertakan atribusi jika dipublikasikan ulang dan periksa lisensi dependensi pihak ketiga.

---

✨ Selamat membangun ekosistem peminjaman laptop yang rapi, transparan, dan modern! Jika menemui kendala, periksa log atau ajukan pertanyaan melalui chatbot untuk mendapatkan ringkasan data cepat.
