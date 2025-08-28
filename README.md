# Koperasi API

API ini digunakan untuk mengelola sistem koperasi sederhana dengan fitur:  
- Autentikasi (Login/Logout)  
- Manajemen User (Admin)  
- Simpanan (Wajib & Pokok)  
- Pinjaman  
- Pelunasan  

Backend menggunakan **Laravel** + **Sanctum**.

---

## 🔹 Persyaratan

- PHP >= 8.2
- Composer  
- MySQL / MariaDB  
- Node.js (opsional untuk frontend)  

---

## 🔹 Setup Laravel

1. Clone repository:

```bash
git clone https://github.com/dianerwansyah/koperasi-api
cd koperasi-api
```

2. Install dependencies:

```bash
composer install
cp .env.example .env
php artisan key:generate
```

3. Konfigurasi `.env`:

```env
APP_NAME=KoperasiAPI
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost

APP_LOCALE=en
APP_FALLBACK_LOCALE=en

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=koperasi_db 
DB_USERNAME=root
DB_PASSWORD=

SESSION_DRIVER=file
SESSION_LIFETIME=120

FILESYSTEM_DISK=public 

SANCTUM_STATEFUL_DOMAINS=localhost
SESSION_DOMAIN=localhost

CACHE_DRIVER=file
QUEUE_CONNECTION=sync
```

4. Migrasi database:

```bash
php artisan migrate
```

5. Jalankan seeder:

```bash
php artisan db:seed
```

6. Jalankan server:

```bash
php artisan serve
```

---

## 🔹 Autentikasi

### Login

```
POST /api/login
```

**Request Body:**

```json
{
  "email": "admin@koperasi.com / karyawan@koperasi.com",
  "password": "password"
}
```

**Response:**

```json
{
  "token": "<token>"
}
```

### Logout

```
POST /api/logout
```

Header:

```
Authorization: Bearer <token>
```

---

## 🔹 User

- `GET /api/user` → Data user login (Admin/Karyawan)  
- `GET /api/user/getuser` → Daftar semua user (Admin)  

Header:

```
Authorization: Bearer <token>
```

---

## 🔹 Simpanan

| Method | Endpoint                 | Role         | Keterangan             |
|--------|-------------------------|--------------|-----------------------|
| GET    | /api/savings            | Admin/Karyawan | List simpanan          |
| POST   | /api/savings            | Admin        | Tambah simpanan        |
| PUT    | /api/savings/{id}       | Admin        | Update simpanan        |
| DELETE | /api/savings/{id}       | Admin        | Hapus simpanan         |
| GET    | /api/savings/calculate  | Admin        | Kalkulasi bagi hasil   |

**Filter GET /api/savings**:  
- `page`, `limit` → pagination  
- `type` → wajib/pokok  
- `start_date`, `end_date` → filter tanggal  
- `nama` → filter nama user (Admin)  

---

## 🔹 Pinjaman

| Method | Endpoint       | Role           | Keterangan         |
|--------|----------------|----------------|------------------|
| GET    | /api/loans     | Admin/Karyawan | List pinjaman      |
| POST   | /api/loans     | Admin/Karyawan | Tambah pinjaman    |
| POST   | /api/loans/{id}| Admin          | Update pinjaman    |

---

## 🔹 Pelunasan / Settlement

| Method | Endpoint            | Role         | Keterangan         |
|--------|--------------------|--------------|------------------|
| GET    | /api/settlement     | Admin        | List settlement   |
| POST   | /api/settlement     | Admin/Karyawan | Tambah settlement |
| POST   | /api/settlement/{id}| Admin        | Update settlement |

**Filter GET /api/settlement**:  
- `status` → filter status pelunasan  
- `start_date`, `end_date` → filter tanggal pelunasan  
- `nama` → filter nama peminjam (Admin)  

---

## 🔹 Notes

- Semua endpoint menggunakan **Sanctum Token** untuk autentikasi  
- Role: `admin` / `karyawan`  
- Semua tanggal menggunakan format `YYYY-MM-DD`  
- Simpanan wajib dihitung otomatis bagi hasil per bulan  
