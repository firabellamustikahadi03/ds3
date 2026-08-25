# Phase 2 — Admin CRUD, Dokter Diagnosis Flow, Riwayat Views

**Status:** Approved — siap masuk implementation plan
**Tanggal:** 2026-08-21 (diperbarui 2026-08-27 — nama file/folder/kolom disesuaikan setelah proyek English
rename selesai & merge)
**Melanjutkan:** [2026-08-20-dass21-migration-design.md](2026-08-20-dass21-migration-design.md) (Phase 1) dan
[2026-08-25-english-rename-design.md](2026-08-25-english-rename-design.md) (rename total ke Inggris) — keduanya
sudah shipped & merged ke `main`.

## Latar Belakang

Phase 1 mengganti mesin diagnosa ds3 dari model Stadium 1/2/3 ke DASS-21 (Depresi/Anxiety/Stres, 4 level
keparahan) dan membangun alur diagnosa publik penuh (`diagnosis.php`/`result.php`). Empat area sengaja
ditinggalkan sebagai placeholder "sedang migrasi" karena bergantung pada tabel yang sudah diganti skemanya.
Setelah itu, proyek terpisah me-rename seluruh skema database dan semua nama file/folder ke bahasa Inggris —
jadi rincian teknis di bawah ini memakai nama-nama BARU (`admin/`, `doctor/`, `process/`, `ds_symptoms`,
`ds_severity_levels`, dst), bukan nama Indonesia yang disebut di draft awal Phase 2.

Empat area yang masih placeholder:

1. CRUD Admin untuk `ds_symptoms`/`ds_severity_levels` (`admin/symptoms.php`, `admin/severity_levels.php`)
2. Halaman "Cara Kerja Sistem" (`admin/how_it_works.php`) yang dulu mengelola `ds_aturan` (basis pengetahuan)
3. Alur diagnosa panel dokter (`doctor/diagnosis.php` + `doctor/process_diagnosis.php`)
4. Tampilan riwayat (`admin/diagnosis_history.php`, `doctor/patient_history.php`) yang masih berasumsi 1 hasil
   per sesi

Phase 2 menyelesaikan keempatnya.

## Keputusan Arah — Penting

**DASS-21 tetap instrumen klinis tetap (fixed).** Selama diskusi brainstorming, sempat muncul pertanyaan
apakah admin harus bisa menambah gejala/"penyakit" di luar cakupan DASS-21. Diputuskan **tidak** — DASS-21
adalah skala tervalidasi (Lovibond & Lovibond 1995) dengan 21 item dan 3 subskala yang sudah ditetapkan
literatur. Menambah item di luar itu merusak validitas instrumen dan mengubah keseluruhan klaim ilmiah
skripsi dari "sistem pakar berbasis DASS-21" menjadi "sistem pakar generik" — scope yang jauh lebih besar dan
di luar cakupan Phase 2 ini.

**Konsekuensi langsung:** CRUD Admin untuk `ds_symptoms`/`ds_severity_levels` **tidak punya tombol Tambah atau
Hapus**. 21 gejala dan 12 kombinasi tingkat itu tetap (fixed), admin hanya bisa:
- Edit teks (nama 4 bahasa, teks rekomendasi 4 bahasa)
- Edit nilai mass gejala (`m_mild_moderate`/`m_moderate_severe`/`m_severe_extreme`/`m_theta`) — kalau memang
  perlu dikoreksi, tetap dengan validasi total = 1.00
- Toggle `is_active` gejala (nonaktifkan sementara tanpa menghapus baris)

## Keputusan yang Sudah Disepakati

| Keputusan | Pilihan |
|---|---|
| Cakupan sistem | Murni implementasi DASS-21 (fixed instrument), bukan sistem pakar generik |
| CRUD gejala/tingkat | Edit + toggle aktif saja — tidak ada Tambah/Hapus baris |
| Menu "Basis Pengetahuan" | Diganti isinya jadi halaman read-only "Cara Kerja Sistem" |
| Alur diagnosa dokter | Reuse `hitungSubskala()` + checklist 3-subskala yang sama seperti alur publik, tulis ke `diagnosis_history` + `diagnosis_details` (`source='riwayat'`) |
| Tampilan riwayat | 1 baris ringkas per sesi + tombol "Detail" ke halaman terpisah (bukan modal — konsisten dengan pola app yang semuanya server-rendered, tanpa AJAX) |

## Komponen

### 1. `controller/c_Symptom.php` — CRUD gejala (edit-only)

File & class ini sudah ada dari Phase 1/rename (class `Symptom`), sekarang diperluas dengan method baru:
- `TampilSemuaAdmin()` — semua 21 baris `ds_symptoms`, dikelompokkan/diurutkan per `subscale`+`dass_item`,
  untuk list Admin (beda dari `TampilBySubskala()` yang sudah ada — itu buat form publik, cuma `id`+`name`)
- `TampilSatuData($id)` — sudah ada, diperluas ambil semua kolom termasuk 4 nilai mass dan `is_active`
- `EditGejala($id, name_id, name_en, name_tr, name_zh, m_mild_moderate, m_moderate_severe, m_severe_extreme, m_theta)`
  — update teks + mass; validasi total mass = 1.00 dilakukan di JS (form) **dan** di PHP (server-side, jangan
  percaya JS saja) sebelum query UPDATE dijalankan
- `ToggleAktif($id)` — flip `is_active` 0/1

Tidak ada method insert/delete baru — versi lama (`InsertGejala()`/`HapusGejala()`) sudah dihapus dari class
ini sejak rename Phase 1, dan endpoint prosesnya (`process/edit_symptom.php`'s sibling add/delete files) sudah
dihapus juga — jangan dibangun ulang, supaya gak ada jejak endpoint yang bisa disalahgunakan seperti temuan
celah `ProsesA/d_gejala.php` (nama lama) di Phase 1.

### 2. `controller/c_SeverityLevel.php` — BARU, CRUD tingkat keparahan (edit-only)

Controller baru (belum ada sebelumnya), jadi ditulis pakai konvensi Inggris dari awal — class `SeverityLevel`:
- `TampilSemua()` — 12 baris `ds_severity_levels`, urut `subscale` → `sort_order`
- `TampilSatuData($id)`
- `EditTingkat($id, name_id, name_en, name_tr, name_zh, recommendation_id, recommendation_en, recommendation_tr, recommendation_zh)`

### 3. Halaman Admin

| File | Perubahan |
|---|---|
| `admin/symptoms.php` | List 21 gejala per subskala (card/table per subskala), tampilkan mass values + status aktif, link "Edit" per baris + toggle aktif inline |
| `admin/edit_symptom.php` | Form edit: nama 4 bahasa, 4 input nilai mass dengan live-total di JS (warna merah kalau ≠1.00, submit disabled), tanpa field subskala/dass_item (itu identitas gejala, tidak diedit) — file ini sudah ada sebagai stub placeholder, Phase 2 mengisi form-nya |
| `process/edit_symptom.php` | Proses form edit — sudah ada sebagai stub redirect, Phase 2 mengisi validasi ulang total mass di PHP sebelum UPDATE |
| `admin/severity_levels.php` | List 12 `ds_severity_levels`, dikelompokkan per subskala. Label menu sidebar diganti dari "Tingkat Keparahan"/lama jadi konsisten dengan nama baru |
| `admin/edit_severity_level.php` | Form edit nama + teks rekomendasi 4 bahasa — sudah ada sebagai stub, Phase 2 mengisi form-nya |
| `process/edit_severity_level.php` | Proses form edit tingkat — sudah ada sebagai stub, Phase 2 mengisi logikanya |
| `admin/how_it_works.php` | Diganti total isinya: halaman read-only — penjelasan singkat metode Dempster-Shafer untuk DASS-21 (frame of discernment, 4 level, transformasi pignistik) + tabel referensi 21 gejala dan nilai mass-nya. Link sidebar tetap ada tapi ikon/label disesuaikan |

**Catatan:** halaman/file untuk "tambah gejala/tingkat" dan "hapus gejala/tingkat" (dulu `admin/tgejala.php`,
`admin/tpenyakit.php`, `admin/tbasisp.php`, `admin/ebasisp.php`, dan proses-nya) sudah **dihapus total** dari
repo di proyek rename kemarin (bukan sekadar di-guard) — konsisten dengan keputusan "tidak ada
Tambah/Hapus". Jangan dibangun ulang di Phase 2.

### 4. Alur Diagnosa Panel Dokter

- **`doctor/diagnosis.php`** — dibangun ulang: checklist 21 gejala dikelompokkan 3 subskala, struktur sama
  seperti `diagnosis.php` publik (loop per subskala dari `Symptom::TampilBySubskala()`), tapi markup memakai
  komponen panel dokter yang sudah ada (`admin-modern.css`, bukan `modern.css` publik) supaya konsisten
  dengan sisa panel dokter yang sudah di-redesign Bootstrap 5. Form tetap kirim `patient_id` sebagai hidden
  input, POST ke `process_diagnosis.php`.
- **`doctor/process_diagnosis.php`** — dibangun ulang: proses sama seperti `result.php` (kelompokkan gejala per
  subskala → panggil `hitungSubskala()` 3×) tapi:
  - Simpan ke `diagnosis_history` (bukan `diagnoses`) — tetap sertakan `patient_id`
  - Simpan ke `diagnosis_details` dengan `source='riwayat'` dan `diagnosis_id` = id baris `diagnosis_history`
    yang baru dibuat (kolom `diagnosis_id` di `diagnosis_details` berfungsi sebagai FK generik ke
    `diagnoses.id` **atau** `diagnosis_history.id` tergantung `source` — sudah didesain begini sejak Phase 1)
  - Tampilkan 3 kartu hasil (reuse pola visual dari `result.php`, disesuaikan ke tema panel dokter)
  - Tombol kembali ke `patient_history.php?patient_id=...`

### 5. Tampilan Riwayat

- **`admin/diagnosis_history.php`** — query existing diperluas dengan `LEFT JOIN`/subquery ke
  `diagnosis_details` (`source='diagnosa'`) untuk membangun kolom ringkasan (mis. `D: Sedang · A: Ringan ·
  S: Sedang`, dari `severity_label` 3 baris). Tambah kolom aksi "Detail" → link ke halaman detail baru.
- **`doctor/patient_history.php`** — sama polanya, tapi filter `patient_id` dan `source='riwayat'`.
- **Halaman detail baru** (mis. `admin/diagnosis_detail.php?id=<diagnosis_id>&source=diagnosa`, dan versi
  dokter `doctor/diagnosis_detail.php?id=<diagnosis_id>&source=riwayat`) — render 3 kartu hasil lengkap
  (level, nilai, rekomendasi) persis seperti tampilan `result.php`, tapi data diambil dari `diagnosis_details`
  yang sudah tersimpan (bukan dihitung ulang) plus daftar gejala yang dipilih (kolom `symptoms_text` di tabel
  header).

## Keamanan

Mengikuti temuan Phase 1 (endpoint delete tanpa auth) dan proyek rename (form field mismatch yang sempat
ketemu di final review): semua file di `process/` untuk edit gejala/tingkat harus lewat pola yang sama seperti
file `process/` lain yang **sudah** benar (cek `session_status()` / redirect ke login kalau belum
authenticated — verifikasi pola ini di kode `admin/_header.php` dan diterapkan konsisten, jangan asumsikan
`include '_header.php'` di halaman pemanggil cukup melindungi *processor*-nya juga kalau processor diakses
langsung). Juga: pastikan nama field `<input name="...">` di form (`admin/edit_symptom.php`,
`admin/edit_severity_level.php`) persis cocok dengan `$_POST[...]` yang dibaca di processor-nya — ini persis
jenis bug yang ketemu di `patients.php` kemarin (field lama vs kolom baru gak sinkron).

## Testing

- Manual: submit form edit gejala dengan total mass ≠ 1.00 → harus ditolak (client dan server).
- Manual: toggle `is_active` gejala → gejala itu hilang dari checklist publik (`diagnosis.php`) tapi baris
  tetap ada di DB dan riwayat lama yang menyebutnya tidak rusak.
- Manual end-to-end: login dokter → jalankan diagnosa untuk 1 pasien → cek 3 baris masuk `diagnosis_details`
  dengan `source='riwayat'` dan `diagnosis_id` merujuk `diagnosis_history.id` yang benar → cek tampil di
  `patient_history.php` sebagai 1 baris ringkas → klik Detail → 3 kartu benar.
- Manual: `admin/diagnosis_history.php` menampilkan riwayat lama (dari sebelum Phase 1, kalau ada) tanpa error
  meski tidak punya baris `diagnosis_details` — kolom ringkasan harus fallback ke sesuatu yang masuk akal,
  bukan kosong/error.

## Di Luar Cakupan

- Menambah gejala/tingkat/instrumen baru di luar DASS-21 (lihat "Keputusan Arah" di atas — ini perubahan
  scope besar, bukan bagian Phase 2).
- Migrasi data `diagnosis_history` lama (dari sebelum Phase 1, hasil Stadium 1/2/3) ke format
  `diagnosis_details` — sama seperti keputusan Phase 1, dibiarkan apa adanya.
- Redesign visual `doctor/diagnosis.php` di luar penyesuaian minimal supaya konsisten dengan
  `admin-modern.css` yang sudah ada — bukan proyek redesign UI baru.
