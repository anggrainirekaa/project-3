# Inventory Management System

Mini Project 3 — Pertemuan 4: Relational Database + Advanced PHP
Anggraini Reka Kalsum (250180025) · Sistem Informasi · Universitas Malikussaleh

Aplikasi inventory berbasis **PHP (PDO) + MySQL/MariaDB**: autentikasi dan role, master data, relasi many-to-many,
transaksi stok dengan commit/rollback, search/filter/sorting/pagination, dashboard, dan pengamanan dasar.

## Fitur
- Login, logout, password hashing, role **admin** dan **staff**
- Master data kategori, produk, supplier (produk ↔ supplier many-to-many)
- Barang masuk / keluar dengan transaction dan histori
- Search, filter kategori, sorting (whitelist), pagination
- Dashboard statistik dan laporan agregat
- Keamanan: prepared statement, validasi server, escaping output, token CSRF

## Business rules
SKU unik · harga/quantity tidak negatif · barang keluar tidak melebihi stok · stok tidak negatif ·
produk berhistori tidak boleh dihapus · halaman internal wajib login · staff tidak boleh mengakses administrasi user ·
sorting hanya kolom yang diizinkan.

## Menjalankan
1. Import `database/schema.sql` (phpMyAdmin atau `mysql -u root < database/schema.sql`)
2. `php database/seed.php`
3. Dari root proyek: `php -S localhost:8080 -t public`, lalu buka http://localhost:8080/login.php

Di XAMPP (folder di `htdocs`), set env `BASE_URL=/inventory-app/public`.
Env opsional: `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`.
Demo tanpa MySQL: `DB_DRIVER=sqlite php database/seed.php`, lalu jalankan server dengan `DB_DRIVER=sqlite`.

Akun demo: `admin` / `admin123` · `staff` / `staff123` (ganti sebelum dipakai sungguhan).

## ERD
![ERD](docs/erd.png)

## Screenshot
| Login | Dashboard |
|---|---|
| ![](docs/screenshots/01-login.png) | ![](docs/screenshots/04-dashboard-admin.png) |
| Search, filter, sorting | Validation error |
| ![](docs/screenshots/06-produk-search-filter-sorting.png) | ![](docs/screenshots/09-produk-validation-error-sku-harga.png) |
| Stok tidak cukup ditolak | Staff akses admin → 403 |
| ![](docs/screenshots/16-barang-keluar-melebihi-stok-ditolak.png) | ![](docs/screenshots/24-staff-akses-user-ditolak-403.png) |

## Struktur
```
config/ helpers/ public/ views/ database/ docs/
```
