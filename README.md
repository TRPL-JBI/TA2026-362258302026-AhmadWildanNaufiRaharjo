# Safety Patrol K3LH

## Deskripsi Sistem

Safety Patrol K3LH adalah sistem informasi web untuk mendukung kegiatan Keselamatan, Kesehatan Kerja, dan Lingkungan Hidup (K3LH) di Politeknik Negeri Banyuwangi.

Sebelumnya, pencatatan patroli, inventaris APAR, dan laporan lingkungan masih dilakukan manual - data tersebar, sulit dilacak, dan laporan harus disusun ulang. Sistem ini menggabungkan semua proses itu dalam satu aplikasi: petugas bisa patroli lewat scan QR, mencatat temuan, memantau IPAM/IPAL/limbah B3, menindaklanjuti masalah, sampai menghasilkan laporan Word/Excel secara otomatis.

Setiap pengguna login dengan role berbeda dan hanya bisa mengakses menu yang sesuai tugasnya. Petugas K3LH mengelola operasional harian, Kalab fokus pada lab dan limbah B3, Satpam melaporkan insiden, dan Pimpinan melihat ringkasan serta mengunduh laporan.

Dibangun dengan Laravel 12, Blade, Alpine.js, Tailwind CSS, dan Vite.

## Peran pengguna

- **Petugas K3LH** — inventaris, patroli, pemantauan, tindak lanjut, laporan, SOP
- **Kalab** — checklist temuan, pemantauan B3, SOP
- **Satpam** — laporan insiden, SOP
- **Pimpinan** — dashboard dan unduh laporan

Akses menu per role ada di `config/role_access.php` dan `app/Support/NavigationMenu.php`.

## Struktur folder

```
app/
  Http/Controllers/   # endpoint per modul
  Services/           # logika bisnis
  Models/
  Support/            # helper periode, menu, dll.
database/
  migrations/
  seeders/            # user demo
resources/
  views/              # tampilan Blade
  js/                 # patroli, scan QR, inventaris
routes/
  web.php
  auth.php
```

## Fitur

**Login multi-role**
Setiap user punya username dan password. Setelah login, menu yang tampil menyesuaikan role - misalnya Satpam tidak bisa masuk ke halaman inventaris, Pimpinan hanya bisa lihat dashboard dan laporan.

**Inventaris**
Tempat mengelola data master: lokasi lab/area, APAR, unit & titik IPAM, checklist temuan patroli, dan akun user. Semua data ini jadi dasar saat patroli dan pemantauan dijalankan.

**QR code lokasi & APAR**
Setiap lokasi dan APAR punya QR code yang bisa dicetak. Saat patroli, petugas scan QR lewat kamera HP/browser untuk langsung membuka form inspeksi lokasi atau APAR yang bersangkutan.

**Patroli temuan & APAR**
Petugas melakukan inspeksi berdasarkan checklist yang sudah diset di inventaris. Temuan dicatat per item beserta foto jika perlu. Untuk APAR, ada form pemeriksaan kondisi alat pemadam. Riwayat patroli dikelompokkan per caturwulan (3 bulan sekali).

**Pemantauan IPAM, IPAL, dan limbah B3**
Modul khusus pencatatan lingkungan. IPAM diisi per bulan, IPAL per triwulan, limbah B3 per semester. Masing-masing punya form dan periode tersendiri, lalu bisa ditandai selesai dan digenerate jadi laporan.

**Tindak lanjut**
Temuan dari patroli dan laporan insiden yang perlu ditindaklanjuti masuk ke sini. Petugas K3LH mencatat tindakan perbaikan dan tanggal selesainya. Periode tindak lanjut mengikuti caturwulan yang sama dengan patroli.

**Laporan insiden**
Satpam bisa mengisi form laporan insiden (kecelakaan, kebakaran, dll.) langsung dari aplikasi. Setelah dikirim, Petugas K3LH mendapat notifikasi dan bisa menindaklanjutinya.

**Generate laporan Word/Excel**
Sistem otomatis menyusun laporan patroli, pemantauan, tindak lanjut, dan insiden ke format `.docx` atau `.xlsx`. File bisa di-preview dan diunduh dari halaman Laporan.

**Upload & preview SOP**
Dokumen Standar Operasional Prosedur (SOP) bisa diunggah ke sistem. Semua role yang berhak bisa membaca dan mem-preview SOP tanpa perlu file fisik.

**Notifikasi**
Ada notifikasi di dalam aplikasi (misalnya saat ada insiden baru atau APAR mendekati masa kedaluwarsa). Petugas K3LH juga bisa menerima web push notification di browser jika diaktifkan.

## Instalasi

**Kebutuhan:** PHP 8.2+, Composer, Node.js 20+, SQLite atau MySQL

```bash
git clone <url-repo>
cd tugas-akhir-2026-wildan2111

composer install
npm install

cp .env.example .env   # kalau ada. kalau belum, buat manual
php artisan key:generate
```

Untuk SQLite, buat file databasenya dulu:

```bash
# Windows
New-Item -ItemType File -Path database/database.sqlite -Force

# Linux/macOS
touch database/database.sqlite
```

Lalu migrasi + seed:

```bash
php artisan migrate --seed
php artisan storage:link
npm run build
php artisan serve
```

Atau pakai skrip bawaan:

```bash
composer setup
composer run dev
```

Buka http://127.0.0.1:8000

### Akun demo

| Username | Password | Role |
|----------|----------|------|
| admin | password | Petugas K3LH |
| kalab | password | Kalab |
| satpam | password | Satpam |
| pimpinan | password | Pimpinan |

### MySQL

Di `.env`:

```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=safety_patrol_k3lh
DB_USERNAME=root
DB_PASSWORD=
```

### Testing

```bash
php artisan test
```
