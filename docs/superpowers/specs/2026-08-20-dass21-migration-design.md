# Migrasi Sistem Pakar ds3 ke Metode DASS-21

**Status:** Approved — siap masuk implementation plan
**Tanggal:** 2026-08-20

## Latar Belakang

Aplikasi ds3 (sistem pakar kesehatan mental mahasiswa, skripsi Fira Bella Mustikahadi, ITPLN) saat ini
mendiagnosis 1 hasil tunggal — Stadium 1/2/3 depresi — dari 17 gejala umum, menggunakan Dempster-Shafer
dengan basis pengetahuan `ds_gejala` / `ds_penyakit` / `ds_aturan`.

User memberikan dataset baru (`dass21_knowledge_base.sql`) berbasis metode **DASS-21** (Depression Anxiety
Stress Scale, Lovibond & Lovibond 1995): 21 gejala terbagi 3 subskala (Depression/Anxiety/Stress, 7 item
tiap subskala), tiap gejala punya fungsi mass Dempster-Shafer yang menyebar ke pasangan hipotesis
berdekatan (`m_ho`, `m_oa`, `m_aca`) plus ketidakpastian (`m_theta`), dengan 4 kemungkinan level keparahan:
**Hafif (H) → Orta/Sedang (O) → Agir/Berat (A) → Cok Agir/Sangat Berat (CA)**.

Ini bukan update data biasa — metodologi inti sistem pakar berubah total. Dokumen ini adalah desain untuk
migrasi penuh.

## Keputusan yang Sudah Disepakati

| Keputusan | Pilihan |
|---|---|
| Struktur hasil | 3 hasil terpisah (Depresi, Anxiety, Stres), masing-masing dihitung independen |
| Bahasa EN/ZH | Diterjemahkan (bukan fallback kosong) — konsisten 4 bahasa seperti sistem lama |
| Tabel lama (`ds_gejala`, `ds_penyakit`, `ds_aturan`) | Di-rename jadi `*_old` (backup), bukan dihapus |
| Alur input pasien | Tetap checklist ya/tidak per gejala (bukan skala 0-3 DASS-21 asli) |
| Admin CRUD | Tetap full CRUD (tambah/edit/hapus gejala & edit teks rekomendasi tingkat) |
| Pendekatan skema | Approach B — ikuti struktur dataset yang diberikan (1 tabel gejala + 1 tabel tingkat, bukan full-normalized) |

## Skema Database

### `ds_gejala` (replace total — DROP lama sudah di-rename, buat baru)

```sql
CREATE TABLE ds_gejala (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    kode_gejala   VARCHAR(10)  NOT NULL UNIQUE,      -- G-D01, G-A01, G-S01, ...
    subskala      ENUM('D','A','S') NOT NULL,        -- Depression / Anxiety / Stress
    item_dass     TINYINT      NOT NULL,             -- nomor item DASS-21 asli (1-21)
    nama_id       VARCHAR(255) NOT NULL,
    nama_en       VARCHAR(255) NOT NULL,
    nama_tr       VARCHAR(255) NOT NULL,
    nama_zh       VARCHAR(255) NOT NULL,
    m_ho          DECIMAL(4,2) NOT NULL,             -- m({Hafif,Orta})
    m_oa          DECIMAL(4,2) NOT NULL,             -- m({Orta,Agir})
    m_aca         DECIMAL(4,2) NOT NULL,             -- m({Agir,CokAgir})
    m_theta       DECIMAL(4,2) NOT NULL,             -- m(Θ) — ketidakpastian
    tipe_gejala   TINYINT      NOT NULL,             -- 1=khas Hafif-Orta, 2=khas Orta-Agir, 3=overlap
    is_active     TINYINT(1)   DEFAULT 1,
    created_at    TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Constraint aplikatif (dicek di controller, bukan di DB): `m_ho + m_oa + m_aca + m_theta = 1.00` per baris.

### `ds_tingkat` (baru — pengganti peran `ds_penyakit`, 12 baris tetap)

```sql
CREATE TABLE ds_tingkat (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    subskala      ENUM('D','A','S') NOT NULL,
    level         ENUM('H','O','A','CA') NOT NULL,   -- Hafif/Orta/Agir/CokAgir
    urutan        TINYINT NOT NULL,                  -- 1-4, untuk sorting & pignistic mapping
    nama_id       VARCHAR(100) NOT NULL,             -- "Depresi - Ringan"
    nama_en       VARCHAR(100) NOT NULL,
    nama_tr       VARCHAR(100) NOT NULL,
    nama_zh       VARCHAR(100) NOT NULL,
    kett          MEDIUMTEXT NOT NULL,                -- rekomendasi/saran (Indonesia)
    kett_en       MEDIUMTEXT,
    kett_tr       MEDIUMTEXT,
    kett_zh       MEDIUMTEXT,
    UNIQUE KEY uq_subskala_level (subskala, level)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_unicode_ci;
```

12 baris: (D,H) (D,O) (D,A) (D,CA) (A,H) (A,O) (A,A) (A,CA) (S,H) (S,O) (S,A) (S,CA).

### `ds_aturan` — dihapus dari alur aplikasi

Rename ke `ds_aturan_old` untuk backup. Tidak ada penggantinya — mass function sudah menempel langsung di
`ds_gejala`, tidak perlu tabel penghubung gejala↔penyakit lagi.

### `diagnosa_detail` (baru)

```sql
CREATE TABLE diagnosa_detail (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    id_diagnosa   INT NOT NULL,                      -- FK ke diagnosa.id_diagnosa (atau riwayat.id_riwayat)
    sumber        ENUM('diagnosa','riwayat') NOT NULL DEFAULT 'diagnosa',
    subskala      ENUM('D','A','S') NOT NULL,
    level_kode    ENUM('H','O','A','CA') NOT NULL,
    level_nama    VARCHAR(100) NOT NULL,             -- snapshot nama saat diagnosa dibuat
    nilai         DECIMAL(6,4) NOT NULL,              -- belief/pignistic value 0-1
    persentase    VARCHAR(10) NOT NULL,               -- "63.64%"
    INDEX idx_diagnosa (id_diagnosa, sumber)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

Tiap sesi diagnosa akan punya tepat 3 baris di `diagnosa_detail` (satu per subskala). Tabel `diagnosa` dan
`riwayat` yang sudah ada TIDAK diubah strukturnya — kolom `penyakit`/`nilai`/`persentase` di situ tetap diisi
(diringkas jadi 1 string gabungan ketiga hasil, untuk kompatibilitas tampilan lama), tapi sumber kebenaran
detail per-subskala ada di `diagnosa_detail`.

## Algoritma Dempster-Shafer

### Frame of discernment per subskala

`Θ = {H, O, A, CA}` — direpresentasikan sebagai kode integer `1,2,3,4` (mengikuti pola comma-separated
string yang sudah dipakai `perkaliantabel()` sekarang, supaya operasi intersect via `explode`/`array_intersect`
tetap jalan tanpa ubah struktur data dasar).

Untuk tiap gejala DASS-21 yang dicentang pasien (dalam subskala tertentu), evidence-nya:

| Focal set | Kode | Mass |
|---|---|---|
| {H,O} | `"1,2"` | m_ho |
| {O,A} | `"2,3"` | m_oa |
| {A,CA} | `"3,4"` | m_aca |
| {H,O,A,CA} (Θ) | `"1,2,3,4"` | m_theta |

### Perubahan di `c_Diagnosa.php`

`perkaliantabel()` saat ini **hardcoded 2 focal element** per gejala (nilai spesifik ke 1 penyakit + sisa ke
Θ) — lihat loop `for($x=0;$x<2;$x++)`. Ini harus digeneralisasi jadi **N focal element** (sampai 4) supaya
bisa menangani struktur mass DASS-21. Logika inti (intersection antar dua set evidence, akumulasi conflict
mass di key konflik, normalisasi `/(1-K)`) tetap dipakai — cuma bagian iterasi elemen densitas1 yang perlu
diperluas dari fixed-2 jadi dynamic-N.

### Eksekusi per sesi diagnosa

1. Pasien centang gejala dari 21 item (dikelompokkan 3 section D/A/S di UI).
2. Untuk tiap subskala (D, A, S) **secara independen**: ambil gejala yang dicentang di subskala itu,
   kombinasikan mass function-nya berurutan pakai `perkaliantabel()` yang sudah digeneralisasi.
3. Kalau tidak ada gejala dicentang di satu subskala → subskala itu default ke Θ penuh (m_theta=1), hasil
   levelnya dianggap "Hafif"/tidak terdeteksi untuk subskala tersebut (tidak error, tidak divide-by-zero).
4. Hasil kombinasi berupa distribusi mass atas subset dari `{H,O,A,CA}`.

### Aturan keputusan level akhir

- Ambil focal set dengan mass terbesar (`arsort` seperti kode lama).
- Kalau focal set terbesar itu **singleton** (mis. `"2"` = Orta) → itu levelnya, `nilai` = mass-nya.
- Kalau **multi-elemen** (mis. `"2,3"` = masih {Orta,Agir}) → lakukan **pignistic transformation**: bagi
  rata mass tiap focal set multi-elemen ke singleton anggotanya, jumlahkan per singleton across semua focal
  set (termasuk kontribusi dari set lain yang overlap), lalu pilih singleton dengan pignistic probability
  tertinggi. `nilai` yang disimpan = pignistic probability level terpilih.

## Perubahan UI

### `diagnosa.php`

Grid checkbox dipecah jadi 3 section dengan heading (Depresi / Anxiety / Stres), masing-masing render 7
gejala dari `ds_gejala WHERE subskala = ...`. Checkbox tetap ya/tidak (tidak ada perubahan ke skala 0-3).

### `hasil.php`

3 kartu hasil bersebelahan/bertumpuk (1 per subskala), tiap kartu:
- Nama subskala (Depresi/Anxiety/Stres, sesuai bahasa aktif)
- Level (dari `ds_tingkat.nama_{lang}`) + confidence bar (pakai `nilai` dari algoritma)
- Warna sesuai level: Hafif=hijau, Orta=kuning, Agir=oranye, CokAgir=merah (skema warna diperluas dari
  2-warna existing jadi 4-tingkat)
- Teks rekomendasi dari `ds_tingkat.kett_{lang}`

Riwayat (`Admin/riwayatd.php`, panel dokter) menampilkan 3 baris hasil per sesi (join ke `diagnosa_detail`).

## Perubahan Admin Panel

- **`Admin/gejala.php`** — form tambah/edit disesuaikan: field subskala (dropdown D/A/S), item_dass (1-21),
  4 nilai mass (dengan validasi total = 1.00 sebelum submit), deskripsi 4 bahasa.
- **`Admin/penyakit.php`** — berubah fungsi jadi kelola `ds_tingkat`: 12 baris fixed (tidak bisa
  tambah/hapus baris, subskala×level sudah pasti 12 kombinasi), admin cuma edit nama & teks rekomendasi per
  bahasa.
- **`Admin/basisp.php`** (dulu kelola `ds_aturan`) — dihapus dari menu sidebar, route-nya di-redirect atau
  halaman diganti pesan "sudah tidak digunakan, kelola mass function langsung di menu Gejala".

## Migrasi Data

1. Rename `ds_gejala`→`ds_gejala_old`, `ds_penyakit`→`ds_penyakit_old`, `ds_aturan`→`ds_aturan_old`.
2. `CREATE TABLE` untuk `ds_gejala` (skema baru), `ds_tingkat`, `diagnosa_detail`.
3. Insert 21 gejala dari `dass21_knowledge_base.sql`, dengan `deskripsi_tr`→`nama_tr`, `deskripsi_id`→
   `nama_id`, dan **terjemahan baru** untuk `nama_en`/`nama_zh` (dikerjakan sebagai bagian implementasi,
   diverifikasi total mass = 1.00 per baris seperti query verifikasi di SQL sumber).
4. Insert 12 baris `ds_tingkat` — nama level & teks rekomendasi 4 bahasa (adaptasi dari `kett` Stadium
   lama + referensi umum tingkat keparahan DASS-21 per subskala), dibuat sebagai bagian implementasi.
5. Jalankan smoke test: 1 sesi diagnosa penuh (pilih beberapa gejala di tiap subskala → cek 3 hasil keluar
   masuk akal, cek riwayat tersimpan benar).

## Testing

- Unit-level (manual, tidak ada test framework existing di project): verifikasi `perkaliantabel()`
  digeneralisasi masih menghasilkan total mass = 1.00 setelah kombinasi + normalisasi, untuk kasus 1 gejala,
  banyak gejala, dan 0 gejala dicentang di 1 subskala.
- End-to-end manual lewat browser: isi form diagnosa dengan kombinasi gejala berbeda per subskala, pastikan
  3 hasil muncul, warnanya sesuai level, rekomendasi teks muncul di 4 bahasa.
- Cek admin CRUD gejala (tambah/edit dengan validasi total mass) dan CRUD `ds_tingkat` (edit teks
  rekomendasi) berfungsi.
- Cek riwayat diagnosa (Admin & dokter panel) menampilkan 3 hasil per sesi dengan benar.

## Di Luar Cakupan (out of scope)

- Skala 0-3 DASS-21 asli (tetap checklist ya/tidak, sesuai keputusan).
- Kalkulasi skor DASS-21 raw-score klasik (sum × 2) — sistem ini tetap murni Dempster-Shafer, bukan hybrid.
- Migrasi data histori diagnosa lama (Stadium 1/2/3) ke format baru — data lama dibiarkan di tabel `diagnosa`/
  `riwayat` apa adanya (tidak dikonversi ke `diagnosa_detail`), karena tidak ada pemetaan valid dari 1 hasil
  Stadium ke 3 hasil D/A/S.
