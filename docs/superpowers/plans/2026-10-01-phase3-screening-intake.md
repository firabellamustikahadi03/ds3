# Phase 3: Public Screening Intake + Send-to-Doctor Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Gate the public diagnosis flow behind an optional name+age intake, let admin promote a public diagnosis into the doctor system with one click, and mark promoted records as "first screening" on the doctor side.

**Architecture:** Two existing parallel history systems (`diagnoses`/`diagnosis_details(source='diagnosa')` for the public flow, `diagnosis_history`/`diagnosis_details(source='riwayat')`+`patients` for the doctor flow) gain 5 new columns total and one new bridging processor (`process/send_to_doctor.php`) that copies a public diagnosis's rows across into the doctor-flow shape. No existing table is restructured; everything is additive.

**Tech Stack:** PHP 8.2 + MySQL (mysqli, raw SQL, no ORM), session-based state (no user accounts for public visitors), existing 4-language `$_SESSION['langArray']` system.

**Project testing convention (read before Task 1):** This codebase has no PHPUnit/unit-test framework. Every task's "verify" step is `php -l <file>` (syntax) plus a live `curl` check against the running XAMPP server at `http://localhost/ds3`, plus re-running `tests/test_dempster_shafer.php` and `tests/test_hitung_subskala.php` (the Dempster-Shafer engine's manual regression scripts) after any task that could touch the diagnosis pipeline. This matches how every prior task in this project has been verified — do not introduce a new test framework.

---

### Task 1: Schema migration

**Files:**
- Create: `migrations/2026-10-01_02_screening_intake.sql`

- [ ] **Step 1: Write the migration**

```sql
-- Phase 3: public screening intake (name+age) + send-to-doctor promotion.
-- Additive only - no existing column is changed or dropped.

ALTER TABLE diagnoses
  ADD COLUMN patient_name VARCHAR(100) NOT NULL DEFAULT '-' AFTER symptoms_text,
  ADD COLUMN patient_age VARCHAR(10) NOT NULL DEFAULT '-' AFTER patient_name,
  ADD COLUMN sent_to_doctor_id INT NULL AFTER confidence_percentage,
  ADD COLUMN sent_at VARCHAR(50) NULL AFTER sent_to_doctor_id,
  ADD CONSTRAINT fk_diagnoses_sent_to_doctor FOREIGN KEY (sent_to_doctor_id) REFERENCES admins (id);

ALTER TABLE diagnosis_history
  ADD COLUMN origin ENUM('dokter','screening_mandiri') NOT NULL DEFAULT 'dokter' AFTER patient_id;
```

- [ ] **Step 2: Apply the migration**

Run:
```bash
"C:\xampp\mysql\bin\mysql.exe" -u root spdempstershafer < "C:\xampp\htdocs\ds3\migrations\2026-10-01_02_screening_intake.sql"
```
Expected: no output (success).

- [ ] **Step 3: Verify the schema**

Run:
```bash
"C:\xampp\mysql\bin\mysql.exe" -u root spdempstershafer -e "SHOW CREATE TABLE diagnoses\G SHOW CREATE TABLE diagnosis_history\G"
```
Expected: `diagnoses` shows `patient_name`, `patient_age`, `sent_to_doctor_id`, `sent_at` and the new FK constraint; `diagnosis_history` shows `origin` as an enum defaulting to `'dokter'`.

- [ ] **Step 4: Commit**

```bash
cd "C:\xampp\htdocs\ds3"
git add migrations/2026-10-01_02_screening_intake.sql
git commit -m "feat(phase3): add screening-intake + send-to-doctor schema columns"
```

---

### Task 2: New language keys

**Files:**
- Modify: `lang/id.php`, `lang/en.php`, `lang/tr.php`, `lang/zh.php`

All 4 files currently end with the same last line: `'tambah_pasien' => '<value>',` right before the closing `];`. Insert the following block right after that line, in each file (values below, one set per language).

- [ ] **Step 1: Add keys to `lang/id.php`**

Find:
```php
    'tambah_pasien' => 'Tambah Pasien',
];
```
Replace with:
```php
    'tambah_pasien' => 'Tambah Pasien',
    'skip_nama' => 'Saya tidak ingin menyebutkan nama',
    'usia' => 'Usia',
    'usia_placeholder' => 'Usia Anda',
    'skip_usia' => 'Saya tidak ingin menyebutkan usia',
    'mulai_screening' => 'Isi Data & Mulai',
    'nav_mulai_screening' => 'Mulai Diagnosa',
    'kirim_ke_dokter' => 'Kirim ke Dokter',
    'pilih_dokter_tujuan' => 'Pilih dokter tujuan',
    'terkirim_ke' => 'Terkirim ke %s',
    'first_screening_badge' => 'First Screening (Mandiri)',
    'th_tanggal_waktu' => 'Tanggal dan Waktu',
    'th_hasil_das' => 'Hasil (Depresi / Anxiety / Stres)',
];
```

- [ ] **Step 2: Add keys to `lang/en.php`**

Find:
```php
    'tambah_pasien' => 'Add Patient',
];
```
Replace with:
```php
    'tambah_pasien' => 'Add Patient',
    'skip_nama' => 'I prefer not to share my name',
    'usia' => 'Age',
    'usia_placeholder' => 'Your age',
    'skip_usia' => 'I prefer not to share my age',
    'mulai_screening' => 'Submit & Start',
    'nav_mulai_screening' => 'Start Diagnosis',
    'kirim_ke_dokter' => 'Send to Doctor',
    'pilih_dokter_tujuan' => 'Choose a doctor',
    'terkirim_ke' => 'Sent to %s',
    'first_screening_badge' => 'First Screening (Self-reported)',
    'th_tanggal_waktu' => 'Date and Time',
    'th_hasil_das' => 'Result (Depression / Anxiety / Stress)',
];
```

- [ ] **Step 3: Add keys to `lang/tr.php`**

Find:
```php
    'tambah_pasien' => 'Hasta Ekle',
];
```
Replace with:
```php
    'tambah_pasien' => 'Hasta Ekle',
    'skip_nama' => 'Adımı paylaşmak istemiyorum',
    'usia' => 'Yaş',
    'usia_placeholder' => 'Yaşınız',
    'skip_usia' => 'Yaşımı paylaşmak istemiyorum',
    'mulai_screening' => 'Gönder ve Başla',
    'nav_mulai_screening' => 'Teşhise Başla',
    'kirim_ke_dokter' => 'Doktora Gönder',
    'pilih_dokter_tujuan' => 'Doktor seçin',
    'terkirim_ke' => '%s adlı doktora gönderildi',
    'first_screening_badge' => 'İlk Tarama (Kendi Bildirimi)',
    'th_tanggal_waktu' => 'Tarih ve Saat',
    'th_hasil_das' => 'Sonuç (Depresyon / Anksiyete / Stres)',
];
```

- [ ] **Step 4: Add keys to `lang/zh.php`**

Find:
```php
    'tambah_pasien' => '添加患者',
];
```
Replace with:
```php
    'tambah_pasien' => '添加患者',
    'skip_nama' => '我不想透露姓名',
    'usia' => '年龄',
    'usia_placeholder' => '您的年龄',
    'skip_usia' => '我不想透露年龄',
    'mulai_screening' => '提交并开始',
    'nav_mulai_screening' => '开始诊断',
    'kirim_ke_dokter' => '发送给医生',
    'pilih_dokter_tujuan' => '选择医生',
    'terkirim_ke' => '已发送给%s',
    'first_screening_badge' => '初步筛查(自我报告)',
    'th_tanggal_waktu' => '日期和时间',
    'th_hasil_das' => '结果(抑郁/焦虑/压力)',
];
```

- [ ] **Step 5: Syntax-check all 4 files**

Run:
```bash
cd "C:\xampp\htdocs\ds3"
php -l lang/id.php && php -l lang/en.php && php -l lang/tr.php && php -l lang/zh.php
```
Expected: `No syntax errors detected` ×4.

- [ ] **Step 6: Commit**

```bash
git add lang/id.php lang/en.php lang/tr.php lang/zh.php
git commit -m "feat(phase3): add language keys for screening intake, send-to-doctor, first-screening badge"
```

---

### Task 3: Public intake form

**Files:**
- Modify: `patients.php` (full rewrite of the form section, lines 46-143)
- Create: `process/save_screening_intake.php`

`patients.php` currently POSTs to `process/add_doctor.php` with `role=dokter` hardcoded — this is the pre-existing security bug described in the spec (an unauthenticated public form silently creating an `admins` row with `role='dokter'`). This task replaces that target entirely; `process/add_doctor.php` itself is untouched (it's still legitimately used by the logged-in-admin flow in `admin/add_doctor.php`).

- [ ] **Step 1: Replace the registration form section in `patients.php`**

Find (the `<!-- Registration form -->` section through its closing `</section>`, i.e. current lines 46-143):
```php
<!-- ── Registration form ─────────────────── -->
<section class="py-5">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-lg-6 col-md-8">

        <?php if (isset($_GET['success'])): ?>
        <div class="card-modern mb-4" style="border-left:5px solid var(--accent); background:rgba(67,217,173,.07);">
          <div class="d-flex align-items-center gap-3">
            <span style="font-size:1.8rem;">✅</span>
            <div>
              <h6 class="fw-700 mb-0">
                <?php echo isset($_SESSION['langArray']['data_berhasil'])
                    ? htmlspecialchars($_SESSION['langArray']['data_berhasil'])
                    : 'Data berhasil ditambahkan!'; ?>
              </h6>
            </div>
          </div>
        </div>
        <?php endif; ?>

        <div class="card-modern">
          <div class="text-center mb-4">
            <span style="font-size:2.5rem;">📝</span>
            <h4 class="fw-700 mt-2">
              <?php echo isset($_SESSION['langArray']['tambah_data'])
                  ? htmlspecialchars($_SESSION['langArray']['tambah_data'])
                  : 'Tambah Data'; ?>
            </h4>
          </div>

          <form method="post" action="process/add_doctor.php">
            <input type="hidden" name="role" value="dokter">

            <!-- Jurusan / Prodi -->
            <div class="mb-3">
              <label class="form-label-mod" for="nama">
                <?php echo isset($_SESSION['langArray']['jurusan'])
                    ? htmlspecialchars($_SESSION['langArray']['jurusan'])
                    : 'Jurusan'; ?>
              </label>
              <input type="text" class="form-mod" name="name" id="nama"
                     placeholder="<?php echo isset($_SESSION['langArray']['jurusan_placeholder'])
                         ? htmlspecialchars($_SESSION['langArray']['jurusan_placeholder'])
                         : 'Teknik Informatika'; ?>" required>
            </div>

            <!-- Nama / Username -->
            <div class="mb-3">
              <label class="form-label-mod" for="username">
                <?php echo isset($_SESSION['langArray']['nama'])
                    ? htmlspecialchars($_SESSION['langArray']['nama'])
                    : 'Nama'; ?>
              </label>
              <input type="text" class="form-mod" name="username" id="username"
                     placeholder="<?php echo isset($_SESSION['langArray']['nama_placeholder'])
                         ? htmlspecialchars($_SESSION['langArray']['nama_placeholder'])
                         : 'Nama lengkap Anda'; ?>" required>
            </div>

            <!-- No. HP -->
            <div class="mb-4">
              <label class="form-label-mod" for="nohp">
                <?php echo isset($_SESSION['langArray']['no_hp'])
                    ? htmlspecialchars($_SESSION['langArray']['no_hp'])
                    : 'Nomor Handphone'; ?>
              </label>
              <input type="number" class="form-mod" name="phone" id="nohp"
                     placeholder="08xxxxxxxxxx">
            </div>

            <button type="submit" class="btn-primary-mod w-100" style="padding:.85rem;">
              <?php echo isset($_SESSION['langArray']['tambah_data'])
                  ? htmlspecialchars($_SESSION['langArray']['tambah_data'])
                  : 'Simpan Data'; ?>
            </button>
          </form>
        </div>

        <!-- Quick diagnosa link -->
        <div class="text-center mt-4">
          <p class="text-muted-mod" style="font-size:.9rem;">
            <?php echo isset($_SESSION['langArray']['sudah_mendaftar'])
                ? htmlspecialchars($_SESSION['langArray']['sudah_mendaftar'])
                : 'Sudah mendaftar?'; ?>
            <a href="diagnosis.php" class="fw-700 text-primary-mod text-decoration-none">
              <?php echo isset($_SESSION['langArray']['diagnosa'])
                  ? htmlspecialchars($_SESSION['langArray']['diagnosa'])
                  : 'Mulai Diagnosa'; ?>
              &nbsp;→
            </a>
          </p>
        </div>

      </div>
    </div>
  </div>
</section>
```

Replace with:
```php
<!-- ── Screening intake form ─────────────── -->
<section class="py-5">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-lg-6 col-md-8">

        <div class="card-modern">
          <div class="text-center mb-4">
            <span style="font-size:2.5rem;">📝</span>
            <h4 class="fw-700 mt-2">
              <?php echo isset($_SESSION['langArray']['mulai_screening'])
                  ? htmlspecialchars($_SESSION['langArray']['mulai_screening'])
                  : 'Isi Data & Mulai'; ?>
            </h4>
          </div>

          <form method="post" action="process/save_screening_intake.php">

            <!-- Nama -->
            <div class="mb-2">
              <label class="form-label-mod" for="nama">
                <?php echo isset($_SESSION['langArray']['nama'])
                    ? htmlspecialchars($_SESSION['langArray']['nama'])
                    : 'Nama'; ?>
              </label>
              <input type="text" class="form-mod" name="patient_name" id="nama"
                     placeholder="<?php echo isset($_SESSION['langArray']['nama_placeholder'])
                         ? htmlspecialchars($_SESSION['langArray']['nama_placeholder'])
                         : 'Nama lengkap Anda'; ?>">
            </div>
            <div class="mb-3 form-check">
              <input type="checkbox" class="form-check-input" id="skipNama" name="skip_name"
                     onchange="document.getElementById('nama').disabled = this.checked; if (this.checked) document.getElementById('nama').value = '';">
              <label class="form-check-label" for="skipNama" style="font-size:.9rem;">
                <?php echo isset($_SESSION['langArray']['skip_nama'])
                    ? htmlspecialchars($_SESSION['langArray']['skip_nama'])
                    : 'Saya tidak ingin menyebutkan nama'; ?>
              </label>
            </div>

            <!-- Usia -->
            <div class="mb-2">
              <label class="form-label-mod" for="usia">
                <?php echo isset($_SESSION['langArray']['usia'])
                    ? htmlspecialchars($_SESSION['langArray']['usia'])
                    : 'Usia'; ?>
              </label>
              <input type="number" min="1" max="120" class="form-mod" name="patient_age" id="usia"
                     placeholder="<?php echo isset($_SESSION['langArray']['usia_placeholder'])
                         ? htmlspecialchars($_SESSION['langArray']['usia_placeholder'])
                         : 'Usia Anda'; ?>">
            </div>
            <div class="mb-4 form-check">
              <input type="checkbox" class="form-check-input" id="skipUsia" name="skip_age"
                     onchange="document.getElementById('usia').disabled = this.checked; if (this.checked) document.getElementById('usia').value = '';">
              <label class="form-check-label" for="skipUsia" style="font-size:.9rem;">
                <?php echo isset($_SESSION['langArray']['skip_usia'])
                    ? htmlspecialchars($_SESSION['langArray']['skip_usia'])
                    : 'Saya tidak ingin menyebutkan usia'; ?>
              </label>
            </div>

            <button type="submit" class="btn-primary-mod w-100" style="padding:.85rem;">
              <?php echo isset($_SESSION['langArray']['mulai_screening'])
                  ? htmlspecialchars($_SESSION['langArray']['mulai_screening'])
                  : 'Isi Data & Mulai'; ?>
            </button>
          </form>
        </div>

      </div>
    </div>
  </div>
</section>
```

- [ ] **Step 2: Create `process/save_screening_intake.php`**

```php
<?php
session_start();

$skipName = isset($_POST['skip_name']);
$skipAge  = isset($_POST['skip_age']);

$name = trim($_POST['patient_name'] ?? '');
$age  = trim($_POST['patient_age'] ?? '');

$_SESSION['screening_name'] = ($skipName || $name === '') ? '-' : $name;
$_SESSION['screening_age']  = ($skipAge || $age === '') ? '-' : $age;
$_SESSION['screening_intake_done'] = true;

header('Location: ../diagnosis.php');
exit;
```

- [ ] **Step 3: Syntax-check**

Run:
```bash
cd "C:\xampp\htdocs\ds3"
php -l patients.php && php -l process/save_screening_intake.php
```
Expected: `No syntax errors detected` ×2.

- [ ] **Step 4: Live-verify the intake form renders and submits**

Run:
```bash
cd /tmp
curl -s -c cookies.txt http://localhost/ds3/patients.php | grep -E "patient_name|patient_age|skip_name|skip_age"
curl -s -b cookies.txt -c cookies.txt -i -d "patient_name=Test+User&patient_age=30" http://localhost/ds3/process/save_screening_intake.php | grep -i "location"
```
Expected: the first curl shows the 4 form field names present; the second shows `Location: ../diagnosis.php` (redirect confirms the processor ran and set session state).

- [ ] **Step 5: Commit**

```bash
cd "C:\xampp\htdocs\ds3"
git add patients.php process/save_screening_intake.php
git commit -m "feat(phase3): replace patients.php with name+age screening intake form

Retires the pre-existing security bug where this public, unauthenticated
page silently created a password-less admins row with role='dokter' by
POSTing to process/add_doctor.php. That processor is untouched - only
this page's form target changes. Each field (name, age) is independently
skippable via its own checkbox."
```

---

### Task 4: Gate the symptom picker, persist name+age with the result

**Files:**
- Modify: `diagnosis.php:1-12`
- Modify: `result.php` (insert block around line 83-88)

- [ ] **Step 1: Add the intake gate to `diagnosis.php`**

Find:
```php
<?php
include 'controller/c_Riwayat.php';
$cl = new Riwayat;
$cl->Count();

session_start();
include('function.php');
loadLanguage();
```
Replace with:
```php
<?php
include 'controller/c_Riwayat.php';
$cl = new Riwayat;
$cl->Count();

session_start();
if (!isset($_SESSION['screening_intake_done'])) {
    header('Location: patients.php');
    exit;
}
include('function.php');
loadLanguage();
```

- [ ] **Step 2: Persist `patient_name`/`patient_age` in `result.php`'s insert**

Find:
```php
            mysqli_query($con,
                "INSERT INTO diagnoses (diagnosis_date, symptoms_text, summary, confidence_value, confidence_percentage)
                 VALUES ('$tanggal', '" . mysqli_real_escape_string($con, $gejalaDbStr) . "', '" . mysqli_real_escape_string($con, $penyakitStr) . "',
                         '$nilaiStr', '" . mysqli_real_escape_string($con, $persentaseStr) . "')"
            );
```
Replace with:
```php
            $patientNameEsc = mysqli_real_escape_string($con, $_SESSION['screening_name'] ?? '-');
            $patientAgeEsc  = mysqli_real_escape_string($con, $_SESSION['screening_age'] ?? '-');

            mysqli_query($con,
                "INSERT INTO diagnoses (diagnosis_date, symptoms_text, patient_name, patient_age, summary, confidence_value, confidence_percentage)
                 VALUES ('$tanggal', '" . mysqli_real_escape_string($con, $gejalaDbStr) . "', '$patientNameEsc', '$patientAgeEsc', '" . mysqli_real_escape_string($con, $penyakitStr) . "',
                         '$nilaiStr', '" . mysqli_real_escape_string($con, $persentaseStr) . "')"
            );
```

- [ ] **Step 3: Syntax-check**

Run:
```bash
cd "C:\xampp\htdocs\ds3"
php -l diagnosis.php && php -l result.php
```
Expected: `No syntax errors detected` ×2.

- [ ] **Step 4: Live-verify the gate and the persisted data**

Run:
```bash
cd /tmp
rm -f cookies.txt
echo "--- without intake, diagnosis.php must redirect to patients.php ---"
curl -s -i http://localhost/ds3/diagnosis.php | grep -i "location"
echo "--- after intake, diagnosis.php must load normally ---"
curl -s -c cookies.txt -d "patient_name=Verify+Gate&patient_age=42" http://localhost/ds3/process/save_screening_intake.php -o /dev/null
curl -s -b cookies.txt -w "http_code=%{http_code}\n" http://localhost/ds3/diagnosis.php -o /dev/null
```
Expected: the first curl shows `Location: patients.php`; the second shows `http_code=200` (no redirect this time, since the session now has `screening_intake_done`).

Then verify the saved row directly:
```bash
"C:\xampp\mysql\bin\mysql.exe" -u root spdempstershafer -e "SELECT id, patient_name, patient_age FROM diagnoses ORDER BY id DESC LIMIT 1;"
```
(This will only show "Verify Gate"/42 if a symptom-selection POST was also completed in the same cookie session - if the row shown is older, that's expected since this step only verified the gate, not a full diagnosis submission. Full end-to-end submission is verified in Task 9.)

- [ ] **Step 5: Re-run the DS engine regression tests**

Run:
```bash
cd "C:\xampp\htdocs\ds3"
php tests/test_dempster_shafer.php
php tests/test_hitung_subskala.php
```
Expected: `ALL TESTS PASSED` ×2 (this task didn't touch the engine, but `result.php` calls it - confirm no regression).

- [ ] **Step 6: Commit**

```bash
cd "C:\xampp\htdocs\ds3"
git add diagnosis.php result.php
git commit -m "feat(phase3): gate diagnosis.php behind screening intake, persist name+age with results"
```

---

### Task 5: Relabel the nav link

**Files:**
- Modify: `_nav.php:41-46`

- [ ] **Step 1: Swap the reused `data_user` key for the new dedicated key**

Find:
```php
        <li class="nav-item">
          <a class="nav-link <?php echo navActive($_navPage,'patients'); ?>"
             href="patients.php">
            <?php echo isset($_SESSION['langArray']['data_user']) ? $_SESSION['langArray']['data_user'] : 'Data User'; ?>
          </a>
        </li>
```
Replace with:
```php
        <li class="nav-item">
          <a class="nav-link <?php echo navActive($_navPage,'patients'); ?>"
             href="patients.php">
            <?php echo isset($_SESSION['langArray']['nav_mulai_screening']) ? $_SESSION['langArray']['nav_mulai_screening'] : 'Mulai Diagnosa'; ?>
          </a>
        </li>
```

- [ ] **Step 2: Syntax-check**

Run:
```bash
cd "C:\xampp\htdocs\ds3"
php -l _nav.php
```
Expected: `No syntax errors detected`.

- [ ] **Step 3: Live-verify**

Run:
```bash
curl -s http://localhost/ds3/index.php | grep -A2 'href="patients.php"'
```
Expected: shows "Mulai Diagnosa" (not "Data User").

- [ ] **Step 4: Commit**

```bash
cd "C:\xampp\htdocs\ds3"
git add _nav.php
git commit -m "feat(phase3): relabel public nav link from reused 'Data User' key to dedicated screening-intake label"
```

---

### Task 6: Admin list — Nama/Usia columns + Kirim ke Dokter

**Files:**
- Modify: `controller/c_Riwayat.php:29-55` (`TampilSemuaDenganRingkasan()`)
- Modify: `admin/diagnosis_history.php` (table header + body + new processor form)

- [ ] **Step 1: Expose `patient_name`, `patient_age`, and send-status in the controller**

Find:
```php
	/** All diagnoses with a per-subscale summary built from diagnosis_details, for the admin history list. */
	function TampilSemuaDenganRingkasan()
	{
		include "../connection/connection.php";
		$query = mysqli_query($con, "SELECT * FROM diagnoses ORDER BY id DESC");
		$i = 0;
		while ($d = mysqli_fetch_array($query)) {
			$data[$i]['id']                     = $d['id'];
			$data[$i]['diagnosis_date']         = $d['diagnosis_date'];
			$data[$i]['symptoms_text']          = $d['symptoms_text'];
			$data[$i]['summary']                = $d['summary'];
			$data[$i]['confidence_value']       = $d['confidence_value'];
			$data[$i]['confidence_percentage']  = $d['confidence_percentage'];
```
Replace with:
```php
	/** All diagnoses with a per-subscale summary built from diagnosis_details, for the admin history list. */
	function TampilSemuaDenganRingkasan()
	{
		include "../connection/connection.php";
		$query = mysqli_query($con, "SELECT d.*, doc.name AS sent_to_doctor_name
		                              FROM diagnoses d
		                              LEFT JOIN admins doc ON doc.id = d.sent_to_doctor_id
		                              ORDER BY d.id DESC");
		$i = 0;
		while ($d = mysqli_fetch_array($query)) {
			$data[$i]['id']                     = $d['id'];
			$data[$i]['diagnosis_date']         = $d['diagnosis_date'];
			$data[$i]['symptoms_text']          = $d['symptoms_text'];
			$data[$i]['patient_name']           = $d['patient_name'];
			$data[$i]['patient_age']            = $d['patient_age'];
			$data[$i]['summary']                = $d['summary'];
			$data[$i]['confidence_value']       = $d['confidence_value'];
			$data[$i]['confidence_percentage']  = $d['confidence_percentage'];
			$data[$i]['sent_to_doctor_id']      = $d['sent_to_doctor_id'];
			$data[$i]['sent_to_doctor_name']    = $d['sent_to_doctor_name'];
```

(Leave the rest of the method - the `diagnosis_details` sub-query loop and `$data[$i]['ringkasan']` assignment - unchanged.)

- [ ] **Step 2: Syntax-check the controller**

Run:
```bash
cd "C:\xampp\htdocs\ds3"
php -l controller/c_Riwayat.php
```
Expected: `No syntax errors detected`.

- [ ] **Step 3: Rewrite the admin list page**

Find (the entire `<?php include '_header.php';` through `$data = $r->TampilSemuaDenganRingkasan();` block, i.e. current lines 1-5):
```php
<?php include '_header.php'; 

include "../controller/c_Riwayat.php";
$r = new Riwayat;
$data = $r->TampilSemuaDenganRingkasan();
?>
```
Replace with:
```php
<?php include '_header.php'; 

include "../controller/c_Riwayat.php";
$r = new Riwayat;
$data = $r->TampilSemuaDenganRingkasan();

include "../connection/connection.php";
$doctorsResult = mysqli_query($con, "SELECT id, name FROM admins WHERE role = 'dokter' ORDER BY name");
$doctors = [];
while ($row = mysqli_fetch_assoc($doctorsResult)) $doctors[] = $row;
?>
```

Find (the table header row):
```php
                                <thead style="background-color: #336699; color: #ffffff;">
                                  <tr>
                                    <th style="color: white;" width="3%">ID</th>
                                    <th style="color: white;" width="14%">Tanggal dan Waktu</th>
                                    <th style="color: white;">Hasil (Depresi / Anxiety / Stres)</th>
                                    <th style="color: white;" width="4%"><?php echo isset($_SESSION['langArray']['aksi']) ? htmlspecialchars($_SESSION['langArray']['aksi']) : 'Aksi'; ?></th>
                                </tr>
                            </thead>
```
Replace with:
```php
                                <thead style="background-color: #336699; color: #ffffff;">
                                  <tr>
                                    <th style="color: white;" width="3%">ID</th>
                                    <th style="color: white;" width="12%"><?php echo isset($_SESSION['langArray']['th_tanggal_waktu']) ? htmlspecialchars($_SESSION['langArray']['th_tanggal_waktu']) : 'Tanggal dan Waktu'; ?></th>
                                    <th style="color: white;" width="10%"><?php echo isset($_SESSION['langArray']['nama']) ? htmlspecialchars($_SESSION['langArray']['nama']) : 'Nama'; ?></th>
                                    <th style="color: white;" width="6%"><?php echo isset($_SESSION['langArray']['usia']) ? htmlspecialchars($_SESSION['langArray']['usia']) : 'Usia'; ?></th>
                                    <th style="color: white;"><?php echo isset($_SESSION['langArray']['th_hasil_das']) ? htmlspecialchars($_SESSION['langArray']['th_hasil_das']) : 'Hasil (Depresi / Anxiety / Stres)'; ?></th>
                                    <th style="color: white;" width="16%"><?php echo isset($_SESSION['langArray']['aksi']) ? htmlspecialchars($_SESSION['langArray']['aksi']) : 'Aksi'; ?></th>
                                </tr>
                            </thead>
```

Find (the table body row, including the empty-state row above it since column count changes):
```php
                                <?php
                                if (!isset($data)) {
                                    ?>
                                    <tr>
                                        <td></td>
                                        <td></td>
                                        <td></td>
                                        <td></td>
                                    </tr>
                                    <?php
                                } else {
                                    $i=0;
                                foreach($data as $d){
                                    $i++;
                                    ?>
                                    <tr>
                                        <td><?php print $i; ?></td>
                                        <td><?php print $d['diagnosis_date']; ?></td>
                                        <td><?php print htmlspecialchars($d['ringkasan']); ?></td>
                                        <td>
                                            <a href="diagnosis_detail.php?id=<?php print $d['id']; ?>&source=diagnosa" class="btn btn-primary btn-xs text-white" title="Detail"><i class="mdi mdi-eye-outline"></i></a>
                                            <a onclick="if (! confirm('Apakah anda yakin akan menghapus riwayat diagnosa dari daftar ?')) { return false; }" href="../process/delete_diagnosis_history.php?id=<?php print $d['id']; ?>" class="btn btn-danger btn-simple btn-xs text-white" title="Hapus Riwayat"><i class="fa fa-times"></i></a>
                                        </td>
                                    </tr>
                                <?php }} ?>
```
Replace with:
```php
                                <?php
                                if (!isset($data)) {
                                    ?>
                                    <tr>
                                        <td></td><td></td><td></td><td></td><td></td><td></td>
                                    </tr>
                                    <?php
                                } else {
                                    $i=0;
                                foreach($data as $d){
                                    $i++;
                                    ?>
                                    <tr>
                                        <td><?php print $i; ?></td>
                                        <td><?php print $d['diagnosis_date']; ?></td>
                                        <td><?php print htmlspecialchars($d['patient_name']); ?></td>
                                        <td><?php print htmlspecialchars($d['patient_age']); ?></td>
                                        <td><?php print htmlspecialchars($d['ringkasan']); ?></td>
                                        <td>
                                            <a href="diagnosis_detail.php?id=<?php print $d['id']; ?>&source=diagnosa" class="btn btn-primary btn-xs text-white" title="Detail"><i class="mdi mdi-eye-outline"></i></a>
                                            <a onclick="if (! confirm('Apakah anda yakin akan menghapus riwayat diagnosa dari daftar ?')) { return false; }" href="../process/delete_diagnosis_history.php?id=<?php print $d['id']; ?>" class="btn btn-danger btn-simple btn-xs text-white" title="Hapus Riwayat"><i class="fa fa-times"></i></a>
                                            <?php if ($d['sent_to_doctor_id']): ?>
                                              <span class="badge bg-success" title="<?php echo sprintf(isset($_SESSION['langArray']['terkirim_ke']) ? $_SESSION['langArray']['terkirim_ke'] : 'Terkirim ke %s', htmlspecialchars($d['sent_to_doctor_name'])); ?>">
                                                ✓ <?php echo htmlspecialchars($d['sent_to_doctor_name']); ?>
                                              </span>
                                            <?php else: ?>
                                              <form method="post" action="../process/send_to_doctor.php" class="d-inline-flex align-items-center gap-1" style="vertical-align:middle;">
                                                <input type="hidden" name="diagnosis_id" value="<?php echo (int)$d['id']; ?>">
                                                <select name="doctor_id" class="form-select form-select-sm d-inline-block" style="width:auto;" required>
                                                  <option value=""><?php echo isset($_SESSION['langArray']['pilih_dokter_tujuan']) ? htmlspecialchars($_SESSION['langArray']['pilih_dokter_tujuan']) : 'Pilih dokter tujuan'; ?></option>
                                                  <?php foreach ($doctors as $doc): ?>
                                                    <option value="<?php echo (int)$doc['id']; ?>"><?php echo htmlspecialchars($doc['name']); ?></option>
                                                  <?php endforeach; ?>
                                                </select>
                                                <button type="submit" class="btn btn-success btn-xs text-white"><?php echo isset($_SESSION['langArray']['kirim_ke_dokter']) ? htmlspecialchars($_SESSION['langArray']['kirim_ke_dokter']) : 'Kirim ke Dokter'; ?></button>
                                              </form>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php }} ?>
```

- [ ] **Step 4: Syntax-check**

Run:
```bash
cd "C:\xampp\htdocs\ds3"
php -l admin/diagnosis_history.php
```
Expected: `No syntax errors detected`.

- [ ] **Step 5: Live-verify**

Run:
```bash
cd /tmp
curl -s -c admincookies.txt -d "username=admin&password=admin" http://localhost/ds3/plogin.php -o /dev/null
curl -s -b admincookies.txt http://localhost/ds3/admin/diagnosis_history.php | grep -E "Nama|Usia|Kirim ke Dokter|doctor_id"
```
Expected: the Nama/Usia header text and at least one "Kirim ke Dokter" button/select markup appear (this task doesn't create `process/send_to_doctor.php` yet, so the form's target 404s if actually submitted - that's fixed in Task 7, don't submit it yet).

- [ ] **Step 6: Commit**

```bash
cd "C:\xampp\htdocs\ds3"
git add controller/c_Riwayat.php admin/diagnosis_history.php
git commit -m "feat(phase3): show patient name/age and add Kirim ke Dokter control to admin diagnosis history"
```

---

### Task 7: `process/send_to_doctor.php` — the promotion processor

**Files:**
- Create: `process/send_to_doctor.php`

- [ ] **Step 1: Write the processor**

```php
<?php
session_start();
if (!isset($_SESSION['username'])) {
    header('Location: ../login.php');
    exit;
}

include '../connection/connection.php';

$diagnosisId = (int)($_POST['diagnosis_id'] ?? 0);
$doctorId    = (int)($_POST['doctor_id'] ?? 0);

$diagResult = mysqli_query($con, "SELECT * FROM diagnoses WHERE id = $diagnosisId");
$diagnosis  = $diagResult ? mysqli_fetch_assoc($diagResult) : null;

if (!$diagnosis) {
    header('Location: ../admin/diagnosis_history.php?error=not_found');
    exit;
}
if ($diagnosis['sent_to_doctor_id']) {
    // Already promoted - the UI already hides the control once sent, this
    // guards against a resubmitted/bookmarked form doing it twice.
    header('Location: ../admin/diagnosis_history.php?error=already_sent');
    exit;
}

$doctorCheck = mysqli_query($con, "SELECT id FROM admins WHERE id = $doctorId AND role = 'dokter'");
if (!$doctorCheck || mysqli_num_rows($doctorCheck) === 0) {
    header('Location: ../admin/diagnosis_history.php?error=invalid_doctor');
    exit;
}

// 1. Create the patient record under the chosen doctor.
$nameEsc = mysqli_real_escape_string($con, $diagnosis['patient_name']);
$ageEsc  = mysqli_real_escape_string($con, $diagnosis['patient_age']);
mysqli_query($con,
    "INSERT INTO patients (name, date_of_birth, admin_id) VALUES ('$nameEsc', '$ageEsc', $doctorId)"
);
$patientId = mysqli_insert_id($con);

// 2. Create the doctor-flow history header, tagged as a self-reported screening.
$dateEsc    = mysqli_real_escape_string($con, $diagnosis['diagnosis_date']);
$symEsc     = mysqli_real_escape_string($con, $diagnosis['symptoms_text']);
$summaryEsc = mysqli_real_escape_string($con, $diagnosis['summary']);
$valEsc     = mysqli_real_escape_string($con, $diagnosis['confidence_value']);
$pctEsc     = mysqli_real_escape_string($con, $diagnosis['confidence_percentage']);
mysqli_query($con,
    "INSERT INTO diagnosis_history (patient_id, diagnosis_date, symptoms_text, summary, confidence_value, confidence_percentage, origin)
     VALUES ($patientId, '$dateEsc', '$symEsc', '$summaryEsc', '$valEsc', '$pctEsc', 'screening_mandiri')"
);
$newHistoryId = mysqli_insert_id($con);

// 3. Copy the per-subscale results (not recomputed - same evidence, same result).
$detailsResult = mysqli_query($con, "SELECT * FROM diagnosis_details WHERE diagnosis_id = $diagnosisId AND source = 'diagnosa'");
while ($row = mysqli_fetch_assoc($detailsResult)) {
    $subEsc   = mysqli_real_escape_string($con, $row['subscale']);
    $sevEsc   = mysqli_real_escape_string($con, $row['severity_level']);
    $labelEsc = mysqli_real_escape_string($con, $row['severity_label']);
    $confVal  = (float)$row['confidence_value'];
    $confPct  = mysqli_real_escape_string($con, $row['confidence_percentage']);
    mysqli_query($con,
        "INSERT INTO diagnosis_details (diagnosis_id, source, subscale, severity_level, severity_label, confidence_value, confidence_percentage)
         VALUES ($newHistoryId, 'riwayat', '$subEsc', '$sevEsc', '$labelEsc', $confVal, '$confPct')"
    );
}

// 4. Copy the selected symptom ids, so the doctor-side detail page can also
//    re-derive the symptom list live in whatever language is active.
$symptomsResult = mysqli_query($con, "SELECT symptom_id FROM diagnosis_symptoms WHERE diagnosis_id = $diagnosisId AND source = 'diagnosa'");
while ($row = mysqli_fetch_assoc($symptomsResult)) {
    $symptomId = (int)$row['symptom_id'];
    mysqli_query($con,
        "INSERT INTO diagnosis_symptoms (diagnosis_id, source, symptom_id) VALUES ($newHistoryId, 'riwayat', $symptomId)"
    );
}

// 5. Lock the source diagnosis from being sent again.
mysqli_query($con,
    "UPDATE diagnoses SET sent_to_doctor_id = $doctorId, sent_at = NOW() WHERE id = $diagnosisId"
);

header('Location: ../admin/diagnosis_history.php?sent=1');
exit;
```

- [ ] **Step 2: Syntax-check**

Run:
```bash
cd "C:\xampp\htdocs\ds3"
php -l process/send_to_doctor.php
```
Expected: `No syntax errors detected`.

- [ ] **Step 3: Live end-to-end verify**

First, produce a fresh public diagnosis with real symptom data to send (reusing the seed script's engine call pattern, but through the actual public pipeline this time via curl, so it also has real `diagnosis_symptoms` rows):

```bash
cd /tmp
rm -f pubcookies.txt
curl -s -c pubcookies.txt -d "patient_name=Kirim+Test&patient_age=27" http://localhost/ds3/process/save_screening_intake.php -o /dev/null
curl -s -b pubcookies.txt -c pubcookies.txt "http://localhost/ds3/diagnosis.php" -o /dev/null
curl -s -b pubcookies.txt -c pubcookies.txt -d "gejala[]=1&gejala[]=2&gejala[]=8&gejala[]=9" http://localhost/ds3/result.php -o /dev/null
"C:\xampp\mysql\bin\mysql.exe" -u root spdempstershafer -e "SELECT id, patient_name, patient_age, sent_to_doctor_id FROM diagnoses WHERE patient_name='Kirim Test' ORDER BY id DESC LIMIT 1;"
```
Expected: one row, `patient_name='Kirim Test'`, `patient_age='27'`, `sent_to_doctor_id` NULL. Note the `id` printed - call it `<DIAG_ID>` below.

Now find a real doctor's `admins.id` to send to, then send:
```bash
"C:\xampp\mysql\bin\mysql.exe" -u root spdempstershafer -e "SELECT id, name FROM admins WHERE role='dokter';"
curl -s -c admincookies.txt -d "username=admin&password=admin" http://localhost/ds3/plogin.php -o /dev/null
curl -s -b admincookies.txt -i -d "diagnosis_id=<DIAG_ID>&doctor_id=<DOCTOR_ID>" http://localhost/ds3/process/send_to_doctor.php | grep -i "location"
```
Expected: `Location: ../admin/diagnosis_history.php?sent=1`.

Then verify the full promotion landed correctly:
```bash
"C:\xampp\mysql\bin\mysql.exe" -u root spdempstershafer -e "
SELECT d.id, d.sent_to_doctor_id, d.sent_at FROM diagnoses d WHERE d.patient_name='Kirim Test';
SELECT p.id, p.name, p.date_of_birth, p.admin_id FROM patients p ORDER BY p.id DESC LIMIT 1;
SELECT h.id, h.patient_id, h.origin, h.summary FROM diagnosis_history h ORDER BY h.id DESC LIMIT 1;
SELECT COUNT(*) AS detail_rows FROM diagnosis_details WHERE diagnosis_id = (SELECT MAX(id) FROM diagnosis_history) AND source='riwayat';
SELECT COUNT(*) AS symptom_rows FROM diagnosis_symptoms WHERE diagnosis_id = (SELECT MAX(id) FROM diagnosis_history) AND source='riwayat';
"
```
Expected: `diagnoses.sent_to_doctor_id` is set and `sent_at` is populated; the new `patients` row has `name='Kirim Test'`, `date_of_birth='27'`; the new `diagnosis_history` row has `origin='screening_mandiri'`; `detail_rows` is exactly 2 (ids 1,2 are D01/D02 → a D result; ids 8,9 are A01/A02 → an A result; no S-subscale id was posted, so no S row exists to copy); `symptom_rows` is 4 (matching the 4 `gejala[]` ids posted).

Then verify the one-time-send guard:
```bash
curl -s -b admincookies.txt -i -d "diagnosis_id=<DIAG_ID>&doctor_id=<DOCTOR_ID>" http://localhost/ds3/process/send_to_doctor.php | grep -i "location"
```
Expected: `Location: ../admin/diagnosis_history.php?error=already_sent` (no new patient/history rows created this second time - spot-check row counts stayed the same if in doubt).

- [ ] **Step 4: Re-run the DS engine regression tests**

Run:
```bash
cd "C:\xampp\htdocs\ds3"
php tests/test_dempster_shafer.php
php tests/test_hitung_subskala.php
```
Expected: `ALL TESTS PASSED` ×2.

- [ ] **Step 5: Commit**

```bash
cd "C:\xampp\htdocs\ds3"
git add process/send_to_doctor.php
git commit -m "feat(phase3): add process/send_to_doctor.php to promote a public diagnosis into the doctor system"
```

---

### Task 8: Doctor-side "First Screening" badge

**Files:**
- Modify: `controller/c_Rekam.php:28-54` (`TampilRPasienDenganRingkasan()`)
- Modify: `doctor/patient_history.php` (table body)
- Modify: `doctor/diagnosis_detail.php` (header card)

- [ ] **Step 1: Expose `origin` in the controller**

Find:
```php
			$data[$i]['confidence_value']      = $d['confidence_value'];
			$data[$i]['confidence_percentage'] = $d['confidence_percentage'];

			$detailQuery = mysqli_query($con, "SELECT subscale, severity_label FROM diagnosis_details
```
Replace with:
```php
			$data[$i]['confidence_value']      = $d['confidence_value'];
			$data[$i]['confidence_percentage'] = $d['confidence_percentage'];
			$data[$i]['origin']                = $d['origin'];

			$detailQuery = mysqli_query($con, "SELECT subscale, severity_label FROM diagnosis_details
```

- [ ] **Step 2: Syntax-check the controller**

Run:
```bash
cd "C:\xampp\htdocs\ds3"
php -l controller/c_Rekam.php
```
Expected: `No syntax errors detected`.

- [ ] **Step 3: Show the badge in the patient history list**

Find:
```php
                                            <td><?php print htmlspecialchars($r['ringkasan']); ?></td>
```
Replace with:
```php
                                            <td>
                                                <?php print htmlspecialchars($r['ringkasan']); ?>
                                                <?php if (($r['origin'] ?? 'dokter') === 'screening_mandiri'): ?>
                                                  <span class="badge bg-info text-dark d-block mt-1" style="width:fit-content;">
                                                    🩺 <?php echo isset($_SESSION['langArray']['first_screening_badge']) ? htmlspecialchars($_SESSION['langArray']['first_screening_badge']) : 'First Screening (Mandiri)'; ?>
                                                  </span>
                                                <?php endif; ?>
                                            </td>
```

- [ ] **Step 4: Show the badge on the detail page**

Find:
```php
        <div class="card mb-3">
          <div class="card-body">
            <p class="text-muted-mod mb-1"><?php echo isset($_SESSION['langArray']['tanggal']) ? htmlspecialchars($_SESSION['langArray']['tanggal']) : 'Tanggal'; ?>: <?php echo $header['diagnosis_date']; ?></p>
            <p class="text-muted-mod mb-0"><?php echo isset($_SESSION['langArray']['gejala_dipilih']) ? htmlspecialchars($_SESSION['langArray']['gejala_dipilih']) : 'Gejala yang dipilih:'; ?><br><?php echo $symptomsTextDisplay; ?></p>
          </div>
        </div>
```
Replace with:
```php
        <div class="card mb-3">
          <div class="card-body">
            <p class="text-muted-mod mb-1">
              <?php echo isset($_SESSION['langArray']['tanggal']) ? htmlspecialchars($_SESSION['langArray']['tanggal']) : 'Tanggal'; ?>: <?php echo $header['diagnosis_date']; ?>
              <?php if (($header['origin'] ?? 'dokter') === 'screening_mandiri'): ?>
                <span class="badge bg-info text-dark ms-2">
                  🩺 <?php echo isset($_SESSION['langArray']['first_screening_badge']) ? htmlspecialchars($_SESSION['langArray']['first_screening_badge']) : 'First Screening (Mandiri)'; ?>
                </span>
              <?php endif; ?>
            </p>
            <p class="text-muted-mod mb-0"><?php echo isset($_SESSION['langArray']['gejala_dipilih']) ? htmlspecialchars($_SESSION['langArray']['gejala_dipilih']) : 'Gejala yang dipilih:'; ?><br><?php echo $symptomsTextDisplay; ?></p>
          </div>
        </div>
```

- [ ] **Step 5: Syntax-check**

Run:
```bash
cd "C:\xampp\htdocs\ds3"
php -l doctor/patient_history.php && php -l doctor/diagnosis_detail.php
```
Expected: `No syntax errors detected` ×2.

- [ ] **Step 6: Live-verify against the record created in Task 7**

Run:
```bash
cd /tmp
curl -s -c doccookies.txt -d "username=pakar&password=pakar" http://localhost/ds3/plogin.php -o /dev/null
PATIENT_ID=$("C:\xampp\mysql\bin\mysql.exe" -u root spdempstershafer -sN -e "SELECT id FROM patients WHERE name='Kirim Test' ORDER BY id DESC LIMIT 1;")
curl -s -b doccookies.txt "http://localhost/ds3/doctor/patient_history.php?patient_id=$PATIENT_ID" | grep -i "first_screening_badge\|First Screening"
HISTORY_ID=$("C:\xampp\mysql\bin\mysql.exe" -u root spdempstershafer -sN -e "SELECT id FROM diagnosis_history WHERE patient_id=$PATIENT_ID ORDER BY id DESC LIMIT 1;")
curl -s -b doccookies.txt "http://localhost/ds3/doctor/diagnosis_detail.php?id=$HISTORY_ID" | grep -i "First Screening"
```
Expected: both curls show "First Screening (Mandiri)" text present.

- [ ] **Step 7: Commit**

```bash
cd "C:\xampp\htdocs\ds3"
git add controller/c_Rekam.php doctor/patient_history.php doctor/diagnosis_detail.php
git commit -m "feat(phase3): show First Screening badge on doctor-side records promoted from the public flow"
```

---

### Task 9: Full regression pass + two-repo sync

**Files:** none (verification + sync only)

- [ ] **Step 1: Re-run the DS engine regression tests one final time**

Run:
```bash
cd "C:\xampp\htdocs\ds3"
php tests/test_dempster_shafer.php
php tests/test_hitung_subskala.php
```
Expected: `ALL TESTS PASSED` ×2.

- [ ] **Step 2: Full end-to-end manual walkthrough**

Run:
```bash
cd /tmp
rm -f e2e.txt
curl -s -c e2e.txt http://localhost/ds3/patients.php -o /dev/null -w "1. patients.php loads: %{http_code}\n"
curl -s -b e2e.txt -c e2e.txt -i -d "patient_name=E2E+Final&skip_age=1" http://localhost/ds3/process/save_screening_intake.php | grep -i "location" | sed 's/^/2. intake redirect: /'
curl -s -b e2e.txt -c e2e.txt "http://localhost/ds3/diagnosis.php" -o /dev/null -w "3. diagnosis.php loads (gate passed): %{http_code}\n"
curl -s -b e2e.txt -c e2e.txt -d "gejala[]=15&gejala[]=16" http://localhost/ds3/result.php -o /dev/null -w "4. result.php submits: %{http_code}\n"
"C:\xampp\mysql\bin\mysql.exe" -u root spdempstershafer -e "SELECT id, patient_name, patient_age FROM diagnoses WHERE patient_name='E2E Final' ORDER BY id DESC LIMIT 1;"
```
Expected: steps 1/3/4 show `200`, step 2 shows `Location: ../diagnosis.php`, the final query shows one row with `patient_name='E2E Final'`, `patient_age='-'` (age was skipped).

- [ ] **Step 3: Sync to the Documents repo**

Run (PowerShell):
```powershell
$src = "C:\xampp\htdocs\ds3"
$dst = "C:\Users\lenovo\Documents\Yüksek Lisans\Mental Health\Indonesia Application\ds3"
robocopy $src $dst /E /XD ".git" /XF "*.git*" /NFL /NDL /NP
```
Expected: exit code 3 (files copied, no failures).

- [ ] **Step 4: Commit the plan file itself and sync commit in the Documents repo**

```bash
cd "C:\xampp\htdocs\ds3"
git add docs/superpowers/plans/2026-10-01-phase3-screening-intake.md
git commit -m "docs(phase3): add implementation plan"

cd "C:\Users\lenovo\Documents\Yüksek Lisans\Mental Health\Indonesia Application\ds3"
git add -A
git commit -m "feat(phase3): public screening intake + send-to-doctor (full Phase 3 feature)

Squash-equivalent sync of all Phase 3 commits from the htdocs repo -
see that repo's history for the individual per-task commits:
- Schema: diagnoses.{patient_name,patient_age,sent_to_doctor_id,sent_at},
  diagnosis_history.origin
- Public: patients.php rewritten as name+age intake (each field
  independently skippable), retiring the security bug where it silently
  created role=dokter admins rows; diagnosis.php gated behind intake
- Admin: diagnosis_history.php gains Nama/Usia columns + Kirim ke Dokter;
  new process/send_to_doctor.php promotes a public diagnosis into
  patients+diagnosis_history+diagnosis_details+diagnosis_symptoms
- Doctor: patient_history.php + diagnosis_detail.php show a First
  Screening badge on promoted (origin='screening_mandiri') records

Co-Authored-By: Claude Sonnet 5 <noreply@anthropic.com>"
```

**Do not push to GitHub** — matches the standing instruction for this project (push only on explicit request).

---

## Self-review notes (for whoever executes this plan)

- Every task's code blocks are complete, copy-pasteable PHP/SQL — no `// TODO` or
  "similar to Task N" placeholders.
- `process/add_doctor.php` is never modified — only what calls it (`patients.php`'s
  form target) changes, so `admin/add_doctor.php`'s legitimate use keeps working
  untouched.
- Every new DB write path (`result.php`, `process/send_to_doctor.php`) uses
  `mysqli_real_escape_string()` on every interpolated string value, matching the
  existing codebase convention (no prepared statements anywhere in this app - this
  plan does not introduce a new pattern, it follows the established one).
- `diagnosis_symptoms` (added just before this phase) is deliberately kept in sync by
  Task 7 Step 1 - the whole point of Phase 3's doctor-side badge working correctly
  with live language switching depends on it.
