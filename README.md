# 🚀 API Koperasi Karyawan Kita

Ini adalah APU untuk integrasi MySQL dengan menggunakan Laravel

---

## 📌 Daftar Isi

-   [Fitur](#-fitur)
-   [Tech Stack](#-tech-stack)
-   [Instalasi](#-instalasi)

---

## ✨ Fitur

-   🔑 Autentikasi menggunakan Laravel Sanctum
-   💰 Manajemen simpanan (wajib, pokok, sukarela)
-   📊 Perhitungan dividen otomatis
-   📈 Grafik tren simpanan per bulan
-   📥 Export data ke CSV
-   🎨 UI modern dengan Tailwind + DaisyUI

---

## 🛠 Tech Stack

**Backend**

-   [Laravel](https://laravel.com/)
-   [Laravel Sanctum](https://laravel.com/docs/10.x/sanctum)
-   [MySQL](https://www.mysql.com/)

---

## ⚙ Instalasi

### 1. Clone Repository

```bash
git clone https://github.com/Rezkyindri31/API-Koperasi.git
cd API-Koperasi

```

### 2. Installation

Pastikan kamu sudah menginstall Composer di Perangkat Anda. Jika sudah, run code dibawah ini didalam folder API-Koperasi.

```bash
composer install

```

### 3. Preparation

```bash
Buka XAMPP kalian lalu jalankan Start pada Apache dan MySQL kamu. Setelah ituh kamu buat database baru dengan nama db_koperasi.
Setelah masuk di db_koperasi, kamu import database di folder API-Koperasi ke dalamnya. Untuk database di folder ini dengan nama db_koperasi.sql .

```

### 4. Running

Jika sudah semua step diatas dilakukan, maka kamu cukup menjalankan code dibawah ini bersamaan dengan kamu menjalankan UI Tampilan yang ada di https://github.com/Rezkyindri31/Koperasi-Karyawan.git .

```bash
php artisan serve --host=127.0.0.1 --port=8000

```
