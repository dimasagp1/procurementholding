# Panduan Implementasi: Role-Based User, Department, & Company Management

Dokumen ini menjelaskan seluruh alur kerja, arsitektur hak akses, serta panduan pengujian dari awal hingga akhir setelah perubahan sistem multi-company pada platform **Procurement Holding**.

---

## 🏗️ 1. Desain Arsitektur & Tingkatan Role

Sistem ini memisahkan pengguna menjadi dua tingkatan utama: **Holding Level** (pusat) dan **Company Level** (anak perusahaan).

```
                  ┌──────────────────────────────┐
                  │    SUPERADMIN (Holding)      │
                  └──────────────┬───────────────┘
                                 │
                 ┌───────────────┴───────────────┐
                 │  PROCUREMENT HOLDING (Holding)│
                 └───────────────┬───────────────┘
                                 │
                 ┌───────────────┴───────────────┐
                 │    COMPANY ADMIN (Company)    │
                 └───────────────┬───────────────┘
                                 │
         ┌───────────────────────┼───────────────────────┐
┌────────┴────────┐     ┌────────┴────────┐     ┌────────┴────────┐
│ GENERAL MANAGER │     │   OPERATIONAL   │     │   PROCUREMENT   │ ...
└─────────────────┘     └─────────────────┘     └─────────────────┘
```

### Detail Spesifikasi Hak Akses:

| Role | Level | Company Scope | Pembatasan Manajemen |
| :--- | :--- | :--- | :--- |
| **`superadmin`** | Holding | `company_id = NULL` | Mengelola semua perusahaan, semua departemen, semua user, dan seluruh konfigurasi sistem (UOM, Settings, dll). |
| **`procurement_holding`**| Holding | `company_id = NULL` | Memiliki akses approval PR lintas perusahaan. Dapat membuat semua user kecuali role `superadmin`. |
| **`company_admin`** | Company | Terikat `company_id` | **Hanya** dapat mengelola departemen dan user di dalam perusahaannya sendiri. Tidak bisa melihat atau memodifikasi data anak perusahaan lain. |
| **Role Lain** (GM, OM, Staff, dll) | Company | Terikat `company_id` | Pengguna operasional yang tidak memiliki akses ke menu manajemen user dan departemen. |

---

## 🔄 2. Alur Penggunaan Sistem (Step-by-Step)

### Langkah 1: Inisialisasi Database & Data Dummy
Gunakan seeder yang telah disediakan untuk mengisi data awal:
```bash
# 1. Buat Perusahaan (PT Maju Konstruksi) beserta 5 departemen & 5 user operasional
php artisan db:seed --class=CompanyDummySeeder

# 2. Buat akun Company Admin untuk PT Maju Konstruksi
php artisan db:seed --class=CompanyAdminDummySeeder
```

---

### Langkah 2: Alur Kerja Superadmin (Membuat Perusahaan Baru secara Praktis)
Ketika Superadmin ingin mendaftarkan anak perusahaan baru ke dalam sistem:

```
Superadmin Login → Ke menu Settings -> Company Management -> Add New Company
                                  │
  ┌───────────────────────────────┴───────────────────────────────┐
  ▼                               ▼                               ▼
Isi Profil Company           Daftarkan Departemen Awal       Buat Akun Company Admin
(Nama, Kode, Integrasi)      (Dapat mengisi beberapa baris)  (Nama, Email, Password)
                                  │
                                  ▼
                        Klik "Create Company"
```
**Hasil di Database:**
- Satu record Perusahaan baru terbuat.
- Departemen-departemen terdaftar dan langsung terikat ke ID Perusahaan baru.
- Satu user dengan role `company_admin` terbuat dan terikat ke ID Perusahaan baru.

---

### Langkah 3: Alur Kerja Company Admin (Manajemen Mandiri)
Setelah akun `company_admin` terbuat pada Langkah 2, ia dapat mengelola perusahaannya secara mandiri:

1. **Login** dengan akun Company Admin (contoh dummy: `admin@konstruksi.com` / `password`).
2. **Kelola Departemen (`/departments`)**:
   - Company Admin dapat menambahkan departemen tambahan.
   - Kolom pilihan **Company** pada form otomatis **terkunci** (readonly & tersembunyi) mengarah ke perusahaannya sendiri untuk mencegah manipulasi data.
3. **Kelola User (`/users`)**:
   - Company Admin dapat mendaftarkan staff baru (Operational Manager, Procurement Staff, General Manager, dll).
   - Kolom **Company** otomatis terkunci.
   - Pilihan **Department** di-load dinamis berdasarkan departemen yang terdaftar di perusahaannya.
   - Pilihan **Role** dibatasi hanya menampilkan role tingkat anak perusahaan (tidak bisa memilih/membuat Superadmin & Procurement Holding).

---

## 💻 3. Ringkasan Perubahan Kode File

### 1. Database & Migrasi
- **`database/migrations/2026_07_12_000001_add_company_admin_role.php`**: Membuat role `company_admin` dan memberikan permission manajemen user & departemen tingkat lokal.

### 2. Controller (Backend Guard)
- **`app/Http/Controllers/CompanyController.php`**: 
  - Memperbarui fungsi `store()` dengan membungkus proses insert ke dalam `DB::transaction`.
  - Memproses input array `departments[]` dan informasi user admin (`admin_name`, `admin_email`, dsb) sekaligus dalam satu form submit.
- **`app/Http/Controllers/UserController.php`**:
  - Menolak akses (`abort(403)`) jika non-holding mencoba mengakses/mengedit user dari company lain.
  - Membatasi dropdown roles & companies saat create/edit berdasarkan role pengguna yang login.
  - Membatasi data index agar tidak menampilkan staff holding kepada company-level admin.
  - Endpoint AJAX: `/api/companies/{companyId}/departments` untuk memuat opsi departemen secara dinamis.
- **`app/Http/Controllers/DepartmentController.php`**:
  - Membatasi index data departemen agar hanya menampilkan data milik perusahaan user yang sedang login.
  - Validasi ketat pada `store()` dan `update()` agar company_id dipaksa sesuai dengan company_id milik `company_admin` yang login.

### 3. Frontend & Navigasi (UI/UX)
- **`resources/views/layouts/app.blade.php`**:
  - **Superadmin View**: Memindahkan dan menyatukan menu **Users**, **Departments**, dan **Data** ke dalam satu dropdown **Settings** untuk menjaga tampilan tetap bersih.
  - **Company Admin View**: Tetap menampilkan menu **Users** dan **Departments** secara mandiri di navbar karena tidak memiliki akses ke Settings.
- **`resources/views/companies/create.blade.php`**: Menambahkan bagian formulir dynamic multi-row untuk departemen menggunakan Vanilla JS serta form isian Company Admin pertama.
- **`resources/views/users/create.blade.php` & `edit.blade.php`**: Mengunci input Company untuk non-holding user dan memperbarui daftar departemen secara dinamis via AJAX.
- **`resources/views/departments/create.blade.php` & `edit.blade.php`**: Mengunci pilihan Company untuk non-holding user.

---

## 🧪 4. Skenario Pengujian Mandiri

### Uji Coba 1: Superadmin (Membuat Perusahaan + Departemen + Admin Sekaligus)
1. Login sebagai Superadmin (`superadmin@prsystem.com` / `password`).
2. Buka menu **Settings** -> **Company Management** -> **Add New Company**.
3. Isi data perusahaan:
   - Name: `PT Eka Properti`
   - Code: `EKAPROP`
4. Di bagian **Departments (opsional)**, klik **Tambah Department** 2 kali dan isi:
   - Baris 1: Kode: `FIN-EP`, Nama: `Finance & Accounting`
   - Baris 2: Kode: `HR-EP`, Nama: `Human Resources`
5. Di bagian **Company Admin (opsional)**, isi:
   - Nama: `Toni Kroos`
   - Email: `toni@ekaproperti.com`
   - Password: `password`
6. Klik **Create Company**.
7. Pastikan berhasil tersimpan, lalu logout.

### Uji Coba 2: Login Sebagai Company Admin Baru
1. Login menggunakan akun yang baru dibuat (`toni@ekaproperti.com` / `password`).
2. Masuk ke menu **User Management** -> **Add New User**.
3. Pastikan kolom **Company** bernilai `PT Eka Properti` dan tidak dapat diubah (Readonly).
4. Pastikan pilihan **Department** hanya menampilkan `Finance & Accounting` dan `Human Resources`.
5. Daftarkan user baru (misal Manager) dan pastikan data tersimpan dengan benar di lingkup perusahaan tersebut.
