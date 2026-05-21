# 🛍️ Products REST API

REST API untuk manajemen produk dibangun dengan **Laravel 12**, **PHP 8.2**, **MySQL 8.0**, dan **Nginx** menggunakan Docker.

---

## 📋 Daftar Isi

- [Tech Stack](#tech-stack)
- [Persyaratan](#persyaratan)
- [Instalasi](#instalasi)
- [Struktur Docker](#struktur-docker)
- [Environment Variables](#environment-variables)
- [API Endpoints](#api-endpoints)
- [Autentikasi](#autentikasi)
- [Rate Limiting](#rate-limiting)
- [Caching](#caching)

---

## 🛠 Tech Stack

| Komponen | Versi |
|---|---|
| PHP | 8.2 (FPM) |
| Laravel | 12.x |
| MySQL | 8.0 |
| Nginx | Alpine |
| Laravel Sanctum | Token-based Auth |

---

## ✅ Persyaratan

- [Docker](https://docs.docker.com/get-docker/) >= 24.x
- [Docker Compose](https://docs.docker.com/compose/) >= 2.x
- Git

---

## 🚀 Instalasi

### 1. Clone repository

```bash
git clone https://github.com/rudidrmwn/product_restapi.git
cd product_restapi
```

### 2. Salin file environment

```bash
cp .env.example .env
```

Sesuaikan nilai berikut di `.env`:

```env
DB_DATABASE=laravel
DB_USERNAME=laravel
DB_PASSWORD=laravel_secret
DB_ROOT_PASSWORD=root_secret
```

### 3. Build & jalankan semua container

```bash
docker compose up -d --build
```

### 4. Install dependencies Laravel

```bash
docker compose exec app composer install
```

### 5. Generate application key

```bash
docker compose exec app php artisan key:generate
```

### 6. Jalankan migration

```bash
docker compose exec app php artisan migrate
```

### 7. Akses aplikasi

Buka browser di: **http://localhost:8080**

---

## 🐳 Struktur Docker

```
project-root/
├── docker-compose.yml
├── .env.example
├── php/
│   ├── Dockerfile        # PHP 8.2-FPM + extensions
│   └── php.ini           # Konfigurasi PHP (timezone, upload limit, dll)
└── nginx/
    └── conf.d/
        └── default.conf  # Konfigurasi Nginx untuk Laravel
```

### Container yang berjalan

| Container | Deskripsi | Port |
|---|---|---|
| `laravel_app` | PHP 8.2-FPM | - |
| `laravel_nginx` | Web server | `8080:80` |
| `laravel_db` | MySQL 8.0 | `3306:3306` |

### Perintah Docker berguna

```bash
# Lihat status container
docker compose ps

# Lihat log semua container
docker compose logs -f

# Lihat log container tertentu
docker compose logs -f app

# Masuk ke container PHP
docker compose exec app bash

# Masuk ke MySQL
docker exec -it laravel_db mysql -u root -proot_secret

# Stop semua container
docker compose down

# Stop & hapus volume (reset database)
docker compose down -v
```

---

## ⚙️ Environment Variables

| Variable | Default | Keterangan |
|---|---|---|
| `APP_NAME` | Laravel | Nama aplikasi |
| `APP_ENV` | local | Environment (local/production) |
| `APP_DEBUG` | true | Mode debug |
| `DB_HOST` | db | Nama service MySQL di Docker |
| `DB_DATABASE` | laravel | Nama database |
| `DB_USERNAME` | laravel | Username database |
| `DB_PASSWORD` | laravel_secret | Password database |
| `DB_ROOT_PASSWORD` | root_secret | Password root MySQL |

---

## 📡 API Endpoints

Base URL: `http://localhost:8080/api`

### 🔐 Auth

| Method | Endpoint | Deskripsi | Auth |
|---|---|---|---|
| POST | `/auth/register` | Registrasi user baru | ❌ |
| POST | `/auth/login` | Login & dapatkan token | ❌ |
| POST | `/auth/logout` | Logout & hapus token | ✅ |

#### Register

```http
POST /api/auth/register
Content-Type: application/json

{
  "name": "John Doe",
  "email": "john@example.com",
  "password": "supersecret",
  "password_confirmation": "supersecret"
}
```

#### Login

```http
POST /api/auth/login
Content-Type: application/json

{
  "email": "john@example.com",
  "password": "supersecret"
}
```

Response:

```json
{
  "success": true,
  "message": "Login successful",
  "data": {
    "user": { ... },
    "authentication_token": "1|abc123...",
    "refresh_token": "2|xyz789..."
  }
}
```

---

### 🛍️ Products

| Method | Endpoint | Deskripsi | Auth |
|---|---|---|---|
| GET | `/products` | Ambil semua produk | ❌ |
| GET | `/products/{id}` | Ambil detail produk | ❌ |
| POST | `/products` | Tambah produk baru | ✅ |
| PUT | `/products/{id}` | Update produk | ✅ |
| DELETE | `/products/{id}` | Hapus produk | ✅ |

#### GET /products — Query Parameters

| Parameter | Contoh | Keterangan |
|---|---|---|
| `search` | `?search=shirt` | Cari berdasarkan nama produk |
| `category` | `?category=Clothes` | Filter berdasarkan kategori |
| `limit` | `?limit=10` | Jumlah item per halaman |
| `page` | `?page=2` | Halaman yang ditampilkan |

Contoh:
```
GET /api/products?search=shirt&category=Clothes&limit=10&page=1
```

#### POST /products

```http
POST /api/products
Authorization: Bearer {authentication_token}
Content-Type: application/json

{
  "title": "Awesome T-Shirt",
  "price": 99.99,
  "description": "High-quality cotton t-shirt",
  "category": "Clothes",
  "images": ["https://example.com/image.jpg"]
}
```

---

## 🔑 Autentikasi

API ini menggunakan **Laravel Sanctum** (token-based).

Setelah login, gunakan `authentication_token` di setiap request yang membutuhkan autentikasi:

```http
Authorization: Bearer 1|abc123xyzTOKEN
```

---

## ⏱️ Rate Limiting

| Endpoint | Batas | Per |
|---|---|---|
| `POST /auth/register` | 3 request | 60 detik |
| `POST /auth/login` | 3 request | 60 detik |
| `POST /products` | 1 request | 5 detik |
| `PUT /products/{id}` | 1 request | 5 detik |
| `DELETE /products/{id}` | 1 request | 5 detik |

Jika melebihi batas, API mengembalikan:

```json
{
  "success": false,
  "message": "Too many requests. Please wait 5 seconds before trying again."
}
```

---

## 💾 Caching

- Data produk di-cache selama **5 menit**
- Cache otomatis di-invalidate saat ada operasi **create**, **update**, atau **delete**
- Cache key berbasis query parameter sehingga setiap kombinasi filter tersimpan terpisah

---

## 📁 Struktur Project

```
app/
├── Http/
│   ├── Controllers/Api/
│   │   ├── AuthController.php
│   │   └── ProductController.php
│   └── Requests/
│       ├── Auth/
│       │   ├── LoginRequest.php
│       │   └── RegisterRequest.php
│       └── Product/
│           ├── StoreProductRequest.php
│           └── UpdateProductRequest.php
├── Models/
│   ├── Product.php
│   └── User.php
routes/
└── api.php
database/
└── migrations/
```

---

## 👤 Author

**Rudi** — [github.com/rudidrmwn](https://github.com/rudidrmwn)
