# Inventory Manager Pro - Database System (Dark Mode)

Aplikasi manajemen inventaris gudang interaktif dengan tampilan **Dark Mode Premium** (berdasarkan layout referensi) dan **Sistem Validasi Database Ketat** (strict database validation).

## Fitur Utama
1. **Validasi Tipe Data Ketat (Server-side)**:
   - `nama_barang` (String): Harus teks non-kosong.
   - `no_barang` (Integer): ID unik barang (Primary Key).
   - `jumlah_barang` (Integer): Jumlah stok, tidak boleh negatif.
   - `jenis_barang` (Enum): Kategori valid: `Electronics`, `Apparel`, `Wellness`, `Home & Living`, `Others`.
   - `tanggal_masuk` dan `tanggal_keluar` (Date): Format ISO `YYYY-MM-DD`.
   - `role` (Enum): `Admin`, `Gudang`, `Manajer`.
   - `session` (Integer): Session ID acak saat login.
   - `timestamp` (Time): Waktu sesi aksi dengan format `HH:MM:SS`.
2. **Dashboard Visual**: Ringkasan metrik total nilai aset, status stok (In Stock, Low Stock, Out of Stock), pencarian barang, filter kategori (Enum), dan pagination data.
3. **Database System Inspector**: Panel real-time untuk memantau isi database (`db.json`) dan penjelas skema tipe data.
4. **Sesi Baru & Role Switch**: Klik profil di bagian bawah sidebar untuk membuat sesi log baru (menyimpan `role`, `session`, `timestamp` ke database).

---

## Cara Menjalankan Aplikasi

### Opsi A: Menggunakan Docker (Direkomendasikan)
Pastikan Docker dan Docker Compose telah terinstal di komputer Anda.

1. **Jalankan container**:
   ```bash
   docker-compose up --build -d
   ```
2. **Akses aplikasi**:
   Buka browser dan akses [http://localhost:3000](http://localhost:3000)

3. **Matikan container**:
   ```bash
   docker-compose down
   ```

*Catatan: File `db.json` dipetakan menggunakan docker volume, sehingga semua perubahan data akan tetap aman tersimpan di komputer host Anda.*

---

### Opsi B: Tanpa Docker (Menjalankan Node.js Lokal)
Pastikan Node.js v14+ sudah terinstal.

1. **Instal Dependensi**:
   ```bash
   npm install
   ```

2. **Jalankan Server**:
   ```bash
   npm start
   ```

3. **Akses aplikasi**:
   Buka [http://localhost:3000](http://localhost:3000)
