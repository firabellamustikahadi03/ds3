# Phase 2 — Admin CRUD, Dokter Diagnosis Flow, Riwayat Views

**Status:** Approved — siap masuk implementation plan
**Tanggal:** 2026-08-21
**Melanjutkan:** [2026-08-20-dass21-migration-design.md](2026-08-20-dass21-migration-design.md) (Phase 1, sudah shipped & merged ke `main`)

## Latar Belakang

Phase 1 mengganti mesin diagnosa ds3 dari model Stadium 1/2/3 ke DASS-21 (Depresi/Anxiety/Stres, 4 level
keparahan) dan membangun alur diagnosa publik penuh (`diagnosa.php`/`hasil.php`). Empat area sengaja
ditinggalkan sebagai placeholder "sedang migrasi" karena bergantung pada tabel yang sudah diganti skemanya:

1. CRUD Admin untuk `ds_gejala`/`ds_tingkat` (`Admin/gejala.php`, `Admin/penyakit.php`, + sub-halaman
   tambah/edit)
2. Halaman "Basis Pengetahuan" (`Admin/basisp.php`) yang dulu mengelola `ds_aturan`
3. Alur diagnosa panel dokter (`dokter/diagnosa.php` + `dokter/hdiagnosa.php`)
4. Tampilan riwayat (`Admin/riwayatd.php`, `dokter/riwayatrm.php`) yang masih berasumsi 1 hasil per sesi

Phase 2 menyelesaikan keempatnya.

## Keputusan Arah — Penting

**DASS-21 tetap instrumen klinis tetap (fixed).** Selama diskusi brainstorming, sempat muncul pertanyaan
apakah admin harus bisa menambah gejala/"penyakit" di luar cakupan DASS-21. Diputuskan **tidak** — DASS-21
adalah skala tervalidasi (Lovibond & Lovibond 1995) dengan 21 item dan 3 subskala yang sudah ditetapkan
literatur. Menambah item di luar itu merusak validitas instrumen dan mengubah keseluruhan klaim ilmiah
skripsi dari "sistem pakar berbasis DASS-21" menjadi "sistem pakar generik" — scope yang jauh lebih besar dan
di luar cakupan Phase 2 ini.

**Konsekuensi langsung:** CRUD Admin untuk `ds_gejala`/`ds_tingkat` **tidak punya tombol Tambah atau Hapus**.
21 gejala dan 12 kombinasi tingkat itu tetap (fixed), admin hanya bisa:
- Edit teks (nama 4 bahasa, teks rekomendasi 4 bahasa)
- Edit nilai mass gejala (m_ho/m_oa/m_aca/m_theta) — kalau memang perlu dikoreksi, tetap dengan validasi
  total = 1.00
- Toggle `is_active` gejala (nonaktifkan sementara tanpa menghapus baris)

## Keputusan yang Sudah Disepakati

| Keputusan | Pilihan |
|---|---|
| Cakupan sistem | Murni implementasi DASS-21 (fixed instrument), bukan sistem pakar generik |
| CRUD gejala/tingkat | Edit + toggle aktif saja — tidak ada Tambah/Hapus baris |
| Menu "Basis Pengetahuan" | Diganti isinya jadi halaman read-only "Cara Kerja Sistem" |
| Alur diagnosa dokter | Reuse `hitungSubskala()` + checklist 3-subskala yang sama seperti alur publik, tulis ke `riwayat` + `diagnosa_detail` (`sumber='riwayat'`) |
| Tampilan riwayat | 1 baris ringkas per sesi + tombol "Detail" ke halaman terpisah (bukan modal — konsisten dengan pola app yang semuanya server-rendered, tanpa AJAX) |

## Komponen

### 1. `controller/c_Gejala.php` — CRUD gejala (edit-only)

Method baru/diperbarui:
- `TampilSemuaAdmin()` — semua 21 baris, dikelompokkan/diurutkan per subskala+item_dass, untuk list Admin
- `TampilSatuData($id)` — sudah ada pola serupa di controller lain, diperluas ambil semua kolom termasuk 4
  nilai mass dan `is_active`
- `EditGejala($id, nama_id, nama_en, nama_tr, nama_zh, m_ho, m_oa, m_aca, m_theta)` — update teks + mass;
  validasi total mass = 1.00 dilakukan di JS (form) **dan** di PHP (server-side, jangan percaya JS saja)
  sebelum query UPDATE dijalankan
- `ToggleAktif($id)` — flip `is_active` 0/1

Tidak ada `InsertGejala()`/`HapusGejala()` baru — method lama itu dihapus dari class (bukan sekadar tidak
dipakai), supaya gak ada jejak endpoint yang bisa disalahgunakan seperti temuan celah `ProsesA/d_gejala.php`
di Phase 1.

### 2. `controller/c_Tingkat.php` — BARU, CRUD tingkat (edit-only)

- `TampilSemua()` — 12 baris `ds_tingkat`, urut subskala → urutan
- `TampilSatuData($id)`
- `EditTingkat($id, nama_id, nama_en, nama_tr, nama_zh, kett, kett_en, kett_tr, kett_zh)`

### 3. Halaman Admin

| File | Perubahan |
|---|---|
| `Admin/gejala.php` | List 21 gejala per subskala (card/table per subskala), tampilkan mass values + status aktif, link "Edit" per baris + toggle aktif inline |
| `Admin/egejala.php` | Form edit: nama 4 bahasa, 4 input nilai mass dengan live-total di JS (warna merah kalau ≠1.00, submit disabled), tanpa field subskala/item_dass (itu identitas gejala, tidak diedit) |
| `ProsesA/e_gejala.php` | Proses form edit — validasi ulang total mass di PHP sebelum UPDATE |
| `Admin/tgejala.php`, `ProsesA/t_gejala.php`, `ProsesA/d_gejala.php` | **Dihapus dari sidebar/alur** (tidak direbuild) — tidak ada "tambah"/"hapus" gejala. File fisik boleh dihapus total dari repo (bukan sekadar di-guard placeholder lagi) supaya tidak ada endpoint mati yang berpotensi jadi celah lain |
| `Admin/penyakit.php` | List 12 `ds_tingkat`, dikelompokkan per subskala. Label menu sidebar diganti dari "Penyakit" jadi "Tingkat Keparahan" |
| `Admin/epenyakit.php` | Form edit nama + teks rekomendasi 4 bahasa |
| `ProsesA/e_penyakit.php` | Proses form edit tingkat |
| `Admin/tpenyakit.php` | Dihapus (tidak ada tambah tingkat baru — 12 kombinasi itu tetap) |
| `Admin/basisp.php` | Diganti total isinya: halaman read-only berjudul "Cara Kerja Sistem" — penjelasan singkat metode Dempster-Shafer untuk DASS-21 (frame of discernment, 4 level, transformasi pignistik) + tabel referensi 21 gejala dan nilai mass-nya. Link sidebar tetap ada tapi ikon/label disesuaikan |
| `Admin/tbasisp.php`, `Admin/ebasisp.php` | Dihapus (tidak relevan lagi, tidak ada `ds_aturan` untuk diedit) |

### 4. Alur Diagnosa Panel Dokter

- **`dokter/diagnosa.php`** — dibangun ulang: checklist 21 gejala dikelompokkan 3 subskala, struktur sama
  seperti `diagnosa.php` publik (loop per subskala dari `Gejala::TampilBySubskala()`), tapi markup memakai
  komponen panel dokter yang sudah ada (`admin-modern.css`, bukan `modern.css` publik) supaya konsisten
  dengan sisa panel dokter yang sudah di-redesign Bootstrap 5. Form tetap kirim `id_pasien` sebagai hidden
  input, POST ke `hdiagnosa.php`.
- **`dokter/hdiagnosa.php`** — dibangun ulang: proses sama seperti `hasil.php` (kelompokkan gejala per
  subskala → panggil `hitungSubskala()` 3×) tapi:
  - Simpan ke `riwayat` (bukan `diagnosa`) — tetap sertakan `id_pasien`
  - Simpan ke `diagnosa_detail` dengan `sumber='riwayat'` dan `id_diagnosa` = id baris `riwayat` yang baru
    dibuat (kolom `id_diagnosa` di `diagnosa_detail` berfungsi sebagai FK generik ke `diagnosa.id_diagnosa`
    **atau** `riwayat.id_riwayat` tergantung `sumber` — sudah didesain begini sejak Phase 1)
  - Tampilkan 3 kartu hasil (reuse pola visual dari `hasil.php`, disesuaikan ke tema panel dokter)
  - Tombol kembali ke `riwayatrm.php?id_pasien=...`

### 5. Tampilan Riwayat

- **`Admin/riwayatd.php`** — query existing diperluas dengan `LEFT JOIN`/subquery ke `diagnosa_detail`
  (`sumber='diagnosa'`) untuk membangun kolom ringkasan (mis. `D: Sedang · A: Ringan · S: Sedang`, dari
  `level_nama` 3 baris). Tambah kolom aksi "Detail" → link ke halaman detail baru.
- **`dokter/riwayatrm.php`** — sama polanya, tapi filter `id_pasien` dan `sumber='riwayat'`.
- **Halaman detail baru** (mis. `Admin/riwayat_detail.php?id=<id_diagnosa>&sumber=diagnosa`, dan versi dokter
  `dokter/riwayat_detail.php?id=<id_riwayat>&sumber=riwayat`) — render 3 kartu hasil lengkap (level, nilai,
  rekomendasi) persis seperti tampilan `hasil.php`, tapi data diambil dari `diagnosa_detail` yang sudah
  tersimpan (bukan dihitung ulang) plus daftar gejala yang dipilih (kolom `gejala` di tabel header).

## Keamanan

Mengikuti temuan Phase 1 (endpoint delete tanpa auth): semua file baru di `ProsesA/` untuk edit gejala/tingkat
harus lewat pola yang sama seperti file `ProsesA/` lain yang **sudah** benar (cek `session_status()` /
redirect ke login kalau belum authenticated — verifikasi pola ini di kode `Admin/_header.php` dan diterapkan
konsisten, jangan asumsikan `include '_header.php'` di halaman pemanggil cukup melindungi *processor*-nya
juga kalau processor diakses langsung).

## Testing

- Manual: submit form edit gejala dengan total mass ≠ 1.00 → harus ditolak (client dan server).
- Manual: toggle `is_active` gejala → gejala itu hilang dari checklist publik (`diagnosa.php`) tapi baris
  tetap ada di DB dan riwayat lama yang menyebutnya tidak rusak.
- Manual end-to-end: login dokter → jalankan diagnosa untuk 1 pasien → cek 3 baris masuk `diagnosa_detail`
  dengan `sumber='riwayat'` dan `id_diagnosa` merujuk `id_riwayat` yang benar → cek tampil di
  `riwayatrm.php` sebagai 1 baris ringkas → klik Detail → 3 kartu benar.
- Manual: `Admin/riwayatd.php` menampilkan riwayat lama (dari sebelum Phase 1, kalau ada) tanpa error meski
  tidak punya baris `diagnosa_detail` — kolom ringkasan harus fallback ke sesuatu yang masuk akal, bukan
  kosong/error.

## Di Luar Cakupan

- Menambah gejala/tingkat/instrumen baru di luar DASS-21 (lihat "Keputusan Arah" di atas — ini perubahan
  scope besar, bukan bagian Phase 2).
- Migrasi data `riwayat` lama (dari sebelum Phase 1, hasil Stadium 1/2/3) ke format `diagnosa_detail` — sama
  seperti keputusan Phase 1, dibiarkan apa adanya.
- Redesign visual `dokter/diagnosa.php` di luar penyesuaian minimal supaya konsisten dengan `admin-modern.css`
  yang sudah ada — bukan proyek redesign UI baru.
