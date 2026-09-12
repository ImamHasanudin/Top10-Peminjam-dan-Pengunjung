# 📊 Plugin SLiMS - Top Peminjam & Pengunjung (v1.0.0)

Plugin laporan untuk [SLiMS (Senayan Library Management System)](https://slims.web.id/web/) yang menampilkan **3 submenu terpisah** di grup **Laporan**:

1. **10 Peminjam Tersering** — anggota dengan jumlah transaksi peminjaman terbanyak.
2. **10 Pengunjung Tersering** — anggota/pengunjung dengan jumlah kunjungan (check-in) terbanyak.
3. **10 Anggota Teraktif** — gabungan **jumlah peminjaman + jumlah kunjungan** terbanyak.

Setiap laporan punya **filter rentang tanggal sendiri**, plus tombol pintasan **Hari Ini / Minggu Ini / Bulan Ini / Tahun Ini**.

## ✅ Kompatibilitas

- **SLiMS**: versi 9 (Bulian) ke atas.
- **PHP**: 7.4 dan 8.0 ke atas (tidak memakai fitur khusus PHP 8 seperti `match()`, nullsafe `?->`, atau named arguments).

## 🗂️ Struktur File

| File | Fungsi |
|---|---|
| `imh_top_aktivitas.plugin.php` | File registrasi — mendaftarkan **3 menu terpisah** ke grup Laporan |
| `imh_common.php` | Helper bersama (akses/session, filter tanggal, style, script AJAX) — dipakai oleh ketiga laporan, **bukan** file plugin sendiri |
| `index_peminjam.php` | Laporan "10 Peminjam Tersering" |
| `index_pengunjung.php` | Laporan "10 Pengunjung Tersering" |
| `index_aktif.php` | Laporan "10 Anggota Teraktif" |

## 🛠️ Instalasi

1. Download plugin di atas, kemudian extract ke dalam satu folder di dalam direktori `plugins/` instalasi SLiMS Anda, misalnya:
2. Aktifkan plugin di menu sisten -> Plugin -> Top Peminjam & Pengunjung -> on
2. Masuk ke admin SLiMS, buka menu **Laporan**.
3. Tiga menu baru akan otomatis muncul sebagai item terpisah: **"10 Peminjam Tersering"**, **"10 Pengunjung Tersering"**, **"10 Anggota Teraktif"** — 

> ⚠️ **Disclaimer**: uji coba dulu di server/PC pengujian sebelum dipasang di SLiMS operasional. DOWYR - Do With Your Own Risk.

## 🚀 Cara Penggunaan

1. Buka menu **Laporan**, klik salah satu dari 3 sub-menu baru.
2. (Opsional) Atur rentang tanggal pada kolom filter, atau klik tombol pintasan **Hari Ini / Minggu Ini / Bulan Ini / Tahun Ini**.
3. Klik **Terapkan** untuk memuat ulang data sesuai filter. Klik **Setel ulang** untuk kembali menampilkan seluruh data.

## 🗄️ Sumber Data

- `loan_history` — riwayat peminjaman (`member_id`, `member_name`, `loan_date`, dst).
- `visitor_count` — riwayat kunjungan/check-in perpustakaan (`member_id`, `member_name`, `checkin_date`).

Laporan **"10 Anggota Teraktif"** menjumlahkan jumlah baris dari kedua tabel tersebut per anggota (`member_id` + `member_name`), lalu mengambil 10 teratas.

> Catatan: jika struktur tabel `visitor_count` pada instalasi Anda berbeda (hasil kustomisasi), sesuaikan nama kolom pada query di `index_pengunjung.php` / `index_aktif.php`.


## ✍️ Author

**Imam Hasanudin, S.IP.**
