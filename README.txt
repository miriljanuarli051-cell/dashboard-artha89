ARTHA DELAPAN SEMBILAN — PHP + MySQL/MariaDB + XAMPP
=====================================================

FITUR VERSI AWAL
- Login dan role admin/staf
- Admin membuat akun staf
- Informasi nama barang pada akun pengguna
- Staf membuat transaksi dengan nomor nota dan foto nota
- Banyak barang dalam satu transaksi
- Nama barang diisi langsung saat membuat transaksi
- Perhitungan subtotal dan total pembelian
- Admin memverifikasi, meminta revisi, atau menolak transaksi
- Audit log dasar
- Filter akses transaksi berdasarkan role
- Token CSRF, prepared statements, password_hash, validasi upload

PERSYARATAN
- XAMPP dengan PHP 8.1+ dan MySQL/MariaDB
- Ekstensi PDO MySQL dan Fileinfo aktif

CARA INSTALASI
1. Ekstrak folder "monitoring-pembelian" ke:
   C:\xampp\htdocs\monitoring-pembelian
2. Buka XAMPP, jalankan Apache dan MySQL.
3. Buka http://localhost/phpmyadmin
4. Buat database bernama: monitoring_pembelian
   Collation: utf8mb4_unicode_ci
5. Pilih database tersebut, buka tab Import, lalu import file database.sql.
6. Buka config.php dan sesuaikan kredensial database jika berbeda.
   Default XAMPP biasanya user=root dan password kosong.
7. Jika memperbarui instalasi lama, buat backup database terlebih dahulu, lalu import
   migration_remove_product_catalog.sql ke database monitoring_pembelian. Migrasi ini
   menghapus tabel katalog dan harga acuan, mempertahankan rincian barang transaksi,
   serta menambahkan kolom informasi pengguna.
   Kemudian import migration_add_transaction_payment.sql untuk menambahkan status
   pembayaran dan nomor rekening ke data transaksi. Import juga
   migration_freeform_payment_status.sql agar status pembayaran menerima teks bebas.
   Jika database sudah memakai migration_freeform_payment_status.sql versi sebelumnya,
   import migration_clear_unpaid_payment_status.sql untuk mengosongkan nilai default lama.
8. Pastikan folder berikut dapat ditulisi oleh PHP:
   C:\xampp\htdocs\monitoring-pembelian\storage\receipts
   Untuk produksi, pindahkan folder storage ke luar htdocs dan ubah RECEIPT_DIR di config.php.
9. Buka:
   http://localhost/monitoring-pembelian/setup_admin.php
10. Buat akun admin pertama. Setelah berhasil, HAPUS setup_admin.php.
11. Login melalui:
    http://localhost/monitoring-pembelian/login.php

PENGGUNAAN
- Admin: Dashboard, Transaksi, Pengguna, Audit Log.
- Staf: Dashboard, Transaksi Saya, Tambah Pembelian.
- Staf mengisi nomor nota, pemasok, tanggal, nama barang, jumlah, harga satuan, dan foto nota.
- Form tambah pembelian juga mencatat teks status pembayaran dan nomor rekening.
- Admin memeriksa transaksi dan memilih Setujui, Minta Revisi, atau Tolak.
- Admin dapat mengatur nama barang di halaman Pengguna.

PENTING UNTUK KEAMANAN INTERNAL
- Jangan membuka port Apache ke internet.
- Batasi akses server dengan firewall ke jaringan kantor yang diizinkan.
- Ubah password admin, gunakan akun unik untuk setiap staf.
- Jangan gunakan aplikasi ini untuk data nyata sebelum menguji backup/restore dan hak akses.
- Aplikasi ini merupakan starter internal; belum mencakup HTTPS internal, pembatasan percobaan login, antivirus upload, atau hardening server penuh.
- Jangan menyimpan nota yang berisi data sensitif tanpa kebijakan retensi dan akses yang jelas.
