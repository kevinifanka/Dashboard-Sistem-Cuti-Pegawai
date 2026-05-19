# 📋 Sistem Manajemen Cuti Pegawai
**Employee Management System (EMS)** — Aplikasi web berbasis PHP murni untuk mengelola pengajuan cuti dan lembur pegawai secara digital.

---

## 👥 Anggota Kelompok

| NIM | Nama |
|---|---|
| 231712009 | Kevin Ifanka |
| 231712025 | Alfonso Hutagalung |
| 231712006 | Sofia Fadhillah Ratsyah |
| 231712011 | Mutia Humaira Siregar |

---

## 📌 Deskripsi Proyek

Sistem ini dirancang untuk mempermudah proses pengajuan dan persetujuan cuti serta lembur pegawai dalam sebuah perusahaan. Admin dan HRD dapat mengelola seluruh permohonan secara real-time melalui dashboard yang responsif.

---

## ✨ Fitur Utama

- 🔐 **Autentikasi** — Login & Register berbasis database (bcrypt)
- 📊 **Dashboard** — Statistik real-time: total pegawai, cuti pending, lembur
- 📅 **Pengajuan Cuti** — Pengajuan dengan validasi jenis & durasi cuti
- ⏰ **Pengajuan Lembur** — Pengajuan lembur dengan perhitungan jam otomatis
- ✅ **Manajemen Permohonan** — Approve / Reject dengan alur single approval
- 👤 **Profil Pegawai** — Edit informasi pribadi langsung ke database
- 🏢 **Status per Departemen** — Realtime siapa yang cuti & lembur hari ini
- ⚙️ **Pengaturan Sistem** — Auto-reject & sesi timeout yang dapat dikonfigurasi
- 📆 **Kalender** — Visualisasi cuti & lembur per bulan

---

## 🛠️ Teknologi

| Layer | Teknologi |
|---|---|
| Backend | PHP 8 (Pure PHP, MVC Pattern) |
| Database | MySQL (PDO) |
| Frontend | HTML5, Vanilla CSS, JavaScript |
| Server | XAMPP (Apache) |
| Icons | Lucide Icons |
| Avatar | DiceBear API |

---

## 🗂️ Struktur Folder

```
Cuti pegawai/
├── app/
│   ├── config/          # Konfigurasi DB & konstanta
│   ├── controllers/     # Logic handler (AdminDashboardController, AuthController)
│   ├── models/          # Model database (Employee, Leave, Overtime, Settings)
│   └── views/           # Tampilan HTML (admin, auth, layouts)
├── database/
│   └── schema.sql       # Struktur tabel database
├── public/
│   ├── assets/          # CSS & JS
│   ├── index.php        # Entry point & router
│   └── api.php          # Endpoint polling realtime
└── .htaccess            # URL rewrite rules
```

---

## 🚀 Cara Instalasi

1. **Clone repository** ke folder `htdocs` XAMPP
   ```bash
   git clone <url-repo> "Cuti pegawai"
   ```

2. **Buat database** MySQL bernama `ems`

3. **Import schema**
   ```bash
   mysql -u root -p ems < database/schema.sql
   ```

4. **Konfigurasi database** di `app/config/database.php`

5. **Jalankan migrasi** (tambah kolom auth ke tabel employees):
   ```
   http://localhost/Cuti%20pegawai/public/setup.php
   ```

6. **Akses aplikasi**
   ```
   http://localhost/Cuti%20pegawai/public/
   ```

---


