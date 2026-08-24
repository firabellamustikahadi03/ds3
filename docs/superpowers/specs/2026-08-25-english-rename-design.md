# Rename Total ke Bahasa Inggris — Skema Database & Nama File

**Status:** Approved — siap masuk implementation plan
**Tanggal:** 2026-08-25
**Urutan:** Dikerjakan SEBELUM Phase 2 (Admin CRUD & alur dokter), supaya Phase 2 dibangun langsung di atas
penamaan baru — tidak perlu rename ulang.

## Latar Belakang

Codebase ds3 saat ini campur bahasa Indonesia (nama tabel, kolom, file) dan Turki (kode level keparahan sisa
dari `dass21_knowledge_base.sql`: `H`/`O`/`A`/`CA` = Hafif/Orta/Agir/CokAgir, kolom `m_ho`/`m_oa`/`m_aca`).
User ingin seluruh penamaan internal — tabel, kolom, nama file, nama folder — konsisten bahasa Inggris.

**Ini murni refactor kerapian kode, bukan perubahan fungsional atau metodologi.** Tidak ada logika
Dempster-Shafer, tidak ada tampilan UI (teks yang dilihat pasien tetap 4 bahasa seperti sekarang — ID/EN/TR/ZH
— rename ini cuma menyentuh nama internal yang gak pernah dilihat end-user), yang berubah.

## Keputusan yang Sudah Disepakati

| Keputusan | Pilihan |
|---|---|
| Nama database MySQL (`spdempstershafer`) | **Tidak diganti** — cuma tabel & kolom di dalamnya. Rename database butuh recreate+migrate penuh, risiko kehilangan data tidak sepadan dengan manfaat (nama DB tidak terlihat user, cuma di 1 connection string) |
| Nama folder (`Admin/`, `dokter/`, `ProsesA/`) | Diganti juga, bareng dengan file di dalamnya |
| Prefix `ds_` pada tabel inti (gejala, tingkat, dan backup-nya) | **Dipertahankan** — cuma kata setelah prefix yang diterjemahkan (`ds_gejala`→`ds_symptoms`, bukan `symptoms`) |
| Tabel yang dari awal tidak berprefix (`diagnosa`, `pasien`, `riwayat`, `admin`) | Tetap tanpa prefix setelah rename |
| Kode level keparahan Turki (`H/O/A/CA`, kolom `m_ho` dst) | Diganti ke Inggris (`Mild/Moderate/Severe/Extreme`) — ini juga mengubah beberapa nilai hardcoded di `c_Diagnosa.php`, bukan cuma nama kolom |
| Folder dev scratch (`bahan/`, `DATABASE/`, `test perhitungan/`) | Di luar scope — tidak dipakai aplikasi yang jalan, tidak direferensikan file manapun |
| Urutan eksekusi | 2 lapis terpisah, masing-masing diverifikasi penuh sebelum lanjut: (1) skema database, (2) nama file & folder |

## Konvensi Penamaan

- **snake_case** untuk semua nama tabel, kolom, file PHP
- **Plural** untuk tabel berisi banyak baris data entitas (`symptoms`, `patients`); **singular** untuk
  file/halaman aksi (`diagnosis.php`, `result.php`)
- Kolom multibahasa mempertahankan pola suffix yang sudah ada, cuma kata dasarnya diterjemahkan:
  `nama_id/en/tr/zh` → `name_id/en/tr/zh`, `kett/kett_en/kett_tr/kett_zh` → `recommendation_id/en/tr/zh`

## Pemetaan Skema Database

### Tabel

| Lama | Baru |
|---|---|
| `ds_gejala` | `ds_symptoms` |
| `ds_tingkat` | `ds_severity_levels` |
| `ds_gejala_old` | `ds_symptoms_legacy` |
| `ds_penyakit_old` | `ds_diseases_legacy` |
| `ds_aturan_old` | `ds_rules_legacy` |
| `diagnosa` | `diagnoses` |
| `diagnosa_detail` | `diagnosis_details` |
| `pasien` | `patients` |
| `riwayat` | `diagnosis_history` |
| `admin` | `admins` |
| `translations` | `translations` (sudah Inggris, tidak diubah) |

### Kolom — `ds_symptoms` (dari `ds_gejala`)

| Lama | Baru |
|---|---|
| `id` | `id` |
| `kode_gejala` | `symptom_code` |
| `subskala` | `subscale` |
| `item_dass` | `dass_item` |
| `nama_id/en/tr/zh` | `name_id/en/tr/zh` |
| `m_ho` | `m_mild_moderate` |
| `m_oa` | `m_moderate_severe` |
| `m_aca` | `m_severe_extreme` |
| `m_theta` | `m_theta` (simbol matematika standar, tidak diubah) |
| `tipe_gejala` | `symptom_type` |
| `is_active` | `is_active` (sudah Inggris) |
| `created_at` | `created_at` (sudah Inggris) |

### Kolom — `ds_severity_levels` (dari `ds_tingkat`)

| Lama | Baru |
|---|---|
| `subskala` | `subscale` |
| `level` (enum `H,O,A,CA`) | `severity_level` (enum `Mild,Moderate,Severe,Extreme`) |
| `urutan` | `sort_order` |
| `nama_id/en/tr/zh` | `name_id/en/tr/zh` |
| `kett`, `kett_en/tr/zh` | `recommendation_id`, `recommendation_en/tr/zh` |

### Kolom — `diagnoses` (dari `diagnosa`) & `diagnosis_history` (dari `riwayat`)

| Lama | Baru |
|---|---|
| `id_diagnosa` / `id_riwayat` | `id` |
| `tanggal` | `diagnosis_date` |
| `gejala` | `symptoms_text` |
| `penyakit` | `summary` |
| `nilai` | `confidence_value` |
| `persentase` | `confidence_percentage` |
| `id_pasien` (khusus `riwayat`) | `patient_id` |

### Kolom — `diagnosis_details` (dari `diagnosa_detail`)

| Lama | Baru |
|---|---|
| `id_diagnosa` (FK generik) | `diagnosis_id` |
| `sumber` | `source` |
| `subskala` | `subscale` |
| `level_kode` | `severity_level` |
| `level_nama` | `severity_label` |
| `nilai` | `confidence_value` |
| `persentase` | `confidence_percentage` |

### Kolom — `patients` (dari `pasien`) & `admins` (dari `admin`)

| Lama | Baru |
|---|---|
| `id_pasien` / `id_admin` | `id` |
| `nama` | `name` |
| `tgl_lahir` | `date_of_birth` |
| `id_admin` (FK di `patients`) | `admin_id` |
| `nohp` | `phone` |
| `tingkat` (role admin, BUKAN severity level) | `role` |
| `username`, `password`, `email` | tidak diubah (sudah Inggris) |

## Pemetaan File & Folder (representatif)

Daftar lengkap (~80 file) disusun sistematis di task pertama implementation plan — dibaca langsung dari
struktur folder aktual saat itu, bukan diketik dari ingatan sekarang (supaya tidak ada yang kelewat atau
salah tulis). Pola yang dipakai:

| Lama | Baru |
|---|---|
| `Admin/` | `admin/` |
| `dokter/` | `doctor/` |
| `ProsesA/` | `process/` |
| `koneksi/koneksi.php` | `connection/connection.php` |
| `diagnosa.php` | `diagnosis.php` |
| `hasil.php` | `result.php` |
| `pasien.php` | `patients.php` |
| `panduan.php` | `guide.php` |
| `beranda.php` | `home.php` |
| `Admin/gejala.php` | `admin/symptoms.php` |
| `Admin/penyakit.php` | `admin/severity_levels.php` |
| `Admin/basisp.php` | `admin/how_it_works.php` |
| `Admin/riwayatd.php` | `admin/diagnosis_history.php` |
| `dokter/diagnosa.php` | `doctor/diagnosis.php` |
| `dokter/hdiagnosa.php` | `doctor/process_diagnosis.php` |
| `dokter/riwayatrm.php` | `doctor/patient_history.php` |

File yang namanya sudah Inggris (`index.php`, `login.php`, `logout.php`, `set_language.php`, `function.php`,
`whatsapp.php`, `_nav.php`, `_header.php`, `_footer.php`) tidak diubah.

## Strategi Eksekusi — 2 Lapis

### Lapis 1: Skema Database

1. Backup penuh database (`mysqldump`) sebelum mulai — kalau ada yang salah, bisa restore.
2. `RENAME TABLE` untuk 10 tabel sesuai pemetaan di atas (satu statement, atomik).
3. `ALTER TABLE ... CHANGE COLUMN` untuk tiap kolom sesuai pemetaan.
4. Update SEMUA query SQL di codebase (`controller/*.php`, dan file-file yang query langsung tanpa lewat
   controller) yang menyebut nama tabel/kolom lama — dicari sistematis via grep tiap nama lama, dipastikan
   0 sisa referensi sebelum dianggap selesai.
5. Update value hardcoded `H`/`O`/`A`/`CA` → `Mild`/`Moderate`/`Severe`/`Extreme` di `controller/c_Diagnosa.php`
   (level map, kode subset) dan di manapun string itu dibandingkan/dipakai.
6. Jalankan ulang `tests/test_dempster_shafer.php` dan `tests/test_hitung_subskala.php` — harus tetap PASS
   (nilai hasil hitungan tidak boleh berubah, cuma nama internal yang berubah).
7. Verifikasi manual end-to-end lewat browser (alur publik `diagnosa.php`/`hasil.php`) sebelum lanjut ke
   Lapis 2.

### Lapis 2: Nama File & Folder

1. Susun daftar lengkap file yang akan di-rename (baca struktur folder aktual).
2. Untuk tiap file: `git mv` (bukan hapus+buat baru, supaya history git tetap nyambung), lalu grep seluruh
   codebase untuk referensi ke nama lama (`include`, `require`, `<a href>`, `<form action>`,
   `header('Location: ...')`, `.htaccess`) dan update semua.
3. Verifikasi tiap folder yang sudah di-rename (`admin/`, `doctor/`, `process/`) dengan grep nama lama
   (`Admin/`, `dokter/`, `ProsesA/`) di seluruh codebase — pastikan 0 sisa sebelum lanjut ke folder berikutnya.
4. Update `.htaccess` kalau ada rule yang menyebut path lama secara eksplisit.
5. Verifikasi manual: klik-klik semua menu di 3 role (publik, admin, dokter) setelah rename selesai, pastikan
   tidak ada link mati.

## Risiko & Mitigasi

- **Risiko terbesar:** referensi nama file/folder yang kelewat → link mati atau fatal error. Mitigasi: grep
  verifikasi wajib (bukan opsional) sebelum tiap item dianggap selesai, bukan cuma di akhir.
- **Bookmark/browser history lama** yang mengarah ke URL lama akan mati setelah rename (mis. staf admin yang
  sudah bookmark `Admin/gejala.php`). Ini konsekuensi yang disadari, bukan bug — tidak ada redirect
  kompatibilitas yang dibuat (di luar scope, nambah kompleksitas untuk manfaat kecil di app internal/skripsi).
- **`.htaccess`** saat ini punya rule generik (rewrite `.php` opsional) — perlu dicek apakah ada rule spesifik
  yang menyebut nama file lama secara eksplisit.

## Testing

- 2 test suite CLI (`tests/test_dempster_shafer.php`, `tests/test_hitung_subskala.php`) harus tetap PASS
  setelah Lapis 1 — nama berubah, angka hasil hitungan Dempster-Shafer harus identik.
- Manual click-through semua halaman (publik, admin, dokter) setelah Lapis 2 — cek tidak ada 404/500.
- Grep akhir di seluruh codebase untuk tiap nama tabel/kolom/file lama — harus 0 hasil (kecuali di dalam
  dokumentasi `docs/superpowers/` yang secara sengaja mencatat sejarah, dan folder dev-scratch yang di luar
  scope).

## Di Luar Cakupan

- Rename nama database MySQL itu sendiri (lihat "Keputusan yang Sudah Disepakati").
- Folder dev scratch: `bahan/`, `DATABASE/`, `test perhitungan/`.
- Redirect/alias kompatibilitas untuk URL lama.
- Perubahan teks yang dilihat pasien (4 bahasa UI tetap seperti sekarang) — ini rename internal, bukan
  perubahan konten multibahasa.
