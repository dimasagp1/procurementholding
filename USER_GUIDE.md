# Panduan Lengkap Penggunaan Sistem Purchase Request (PR)
## PT. Herbatech Innopharma Industry

Dokumen ini adalah panduan komprehensif untuk seluruh pengguna sistem PR, mulai dari staf pengaju hingga administrator.

---

## 1. Peran dan Tanggung Jawab (Roles)

Sistem ini memiliki beberapa tingkatan akses:
*   **User (Staff/Submitter)**: Membuat, mengedit, dan memantau status PR milik sendiri.
*   **Operational Manager (OM)**: Melakukan persetujuan tahap pertama untuk item PR dari departemennya.
*   **General Manager (GM)**: Melakukan persetujuan tahap kedua setelah disetujui OM.
*   **Procurement**: Melakukan persetujuan akhir (tahap ketiga), memperbarui status pembelian (Ordered/Delivered), hingga selesai (Completed).
*   **Superadmin**: Memiliki akses penuh ke seluruh fitur, termasuk manajemen user, departemen, dan pengaturan sistem.

---

## 2. Alur Pengajuan Purchase Request (PR)

### Membuat PR Baru
1.  Masuk ke menu **Purchase Request** -> **Create New**.
2.  Isi data umum (Target Date, Purpose/Tujuan).
3.  Tambahkan item barang yang ingin dibeli (Nama Barang, Deskripsi, Jumlah, UOM, dan Tgl Dibutuhkan).
4.  Klik **Submit**. PR akan berstatus **Pending**.

### Manajemen PR Milik Sendiri
*   **View Detail**: Klik ikon mata untuk melihat status tiap item.
*   **Edit**: Hanya bisa dilakukan jika PR masih dalam status **Draft**.
*   **Delete**: Anda bisa menghapus PR jika berstatus **Draft** atau **Pending** (selama belum ada item yang disetujui oleh atasan).

---

## 3. Alur Persetujuan (Approval Flow)

Persetujuan dilakukan **per item**, bukan per PR. Ini memungkinkan sebagian item diproses lebih cepat sementara yang lain memerlukan diskusi lebih lanjut.

1.  **Tahap OM**: Manager departemen masuk ke menu PR, melihat item yang masuk, dan memilih **Approve** atau **Reject**.
2.  **Tahap GM**: Setelah disetujui OM, GM akan menerima notifikasi/daftar item tersebut untuk disetujui kembali.
3.  **Tahap Procurement**: Setelah disetujui GM, tim Procurement memberikan persetujuan akhir. Item yang sudah disetujui Procurement masuk ke fase pengadaan.

---

## 4. Fase Pengadaan (Execution Phase)

Hanya Procurement yang dapat mengubah status ini setelah persetujuan akhir:
*   **Ready to Process**: Item siap diproses untuk mulai pembelian.
*   **Ordered**: Barang telah dipesan ke vendor.
*   **Delivered**: Barang telah sampai di lokasi.
*   **Completed**: Barang telah dicek dan serah terima selesai.

---

## 5. Fitur Revisi & Perbaikan (Revision Workflow)

Jika item Anda **Ditolak (Rejected)** oleh OM, GM, atau Procurement:
1.  Buka detail PR tersebut.
2.  Cari item yang ditolak, klik tombol kuning **Revise**.
3.  Sistem akan membuatkan **PR Baru** dan memindahkan item tersebut ke sana sebagai **Draft**.
4.  Edit item tersebut (misal: ganti spesifikasi atau harga) dan ajukan kembali.
5.  **Marking**: Item hasil revisi akan ditandai dengan badge **[Revised (x)]** dan history lamanya tetap tersimpan.

---

## 6. Fitur Export & Preview Dokumen

1.  Klik tombol **Export PDF** pada detail PR.
2.  Sebuah jendela **Preview** akan muncul menampilkan format surat jalan/nota.
3.  **Ketentuan Tanda Tangan**: Nama manager hanya muncul di kolom TTD jika item tersebut sudah **Approved**. Jika belum, tertulis *"Pending Approval"*.
4.  Klik **Download PDF** untuk menyimpan/mencetak dokumen.

---

## 7. Manajemen User & Password

### Ganti Password (Untuk Admin/User)
1.  Klik menu **Users** (Admin) atau menu profil Anda.
2.  Pilih **Edit**.
3.  Di bagian bawah, masukkan password baru pada kolom **New Password**.
4.  Konfirmasi password di kolom **Confirm New Password**.
5.  Klik **Update**.
    *Note: Biarkan kosong jika tidak ingin mengganti password.*

---

## 8. Pengaturan Sistem (Admin Only)

Melalui menu **Settings**, administrator dapat:
*   Mengubah Nama Aplikasi.
*   Mengunggah Logo Utama dan **Logo Khusus Export** (untuk tampilan PDF).
*   Mengatur Favicon (ikon browser).
*   Mengatur Tanda Tangan Digital untuk manajer.

---

*Dibuat oleh Tim IT Herbatech - Versi 2.0 (Desember 2025)*
