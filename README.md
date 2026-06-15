cat > README.md <<'EOF'
# ArinDrive

ArinDrive adalah platform private berbasis Laravel untuk mengelola beberapa akun Google Drive dalam satu dashboard.

Project ini dibuat untuk kebutuhan pribadi/internal, bukan project open source. Sistem menggunakan official Google Drive API untuk membaca kapasitas storage, mengatur upload otomatis ke akun Google Drive yang masih memiliki ruang cukup, dan menyediakan manajemen file terpusat.

## Fitur Utama

- Multi akun Google Drive dalam satu dashboard
- Upload file otomatis ke Drive yang masih memiliki storage cukup
- Folder virtual
- Shareable link
- Laporan penggunaan storage
- Preview / streaming video
- Manajemen file: upload, rename, move, delete
- File tetap tersimpan di Google Drive asli, bukan di server aplikasi

## Tech Stack

- Laravel
- Blade
- Tailwind CSS
- MySQL
- Google Drive API
- Plyr.io

## Konsep Sistem

ArinDrive tidak menyimpan file secara permanen di server. File yang diupload akan dikirim langsung ke salah satu akun Google Drive yang sudah terhubung.

Server hanya menyimpan metadata seperti:

- Nama file
- Ukuran file
- MIME type
- Google Drive file ID
- Akun Drive tujuan
- Folder virtual
- Token share link

## Status Project

Project masih dalam tahap pengembangan awal.

Target versi pertama:

- Login dashboard
- Tambah akun Google Drive
- Sinkron storage usage
- Upload file otomatis
- List file
- Shareable link sederhana

## Instalasi Lokal

Clone repository:

```bash
git clone https://github.com/USERNAME/arindrive.git
cd arindrive