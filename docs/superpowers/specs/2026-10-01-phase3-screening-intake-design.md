# Phase 3: Public Screening Intake + Send-to-Doctor Design

## Goal

Before a public visitor selects symptoms, they optionally give a name and age (each
independently skippable). Admin can then review submitted diagnoses and "promote" one
into the doctor system as a first-screening record, which a doctor sees flagged as such
in their patient's medical record.

This also retires a pre-existing security bug: the current public "Data User" page
(`patients.php`) silently creates a password-less `admins` row with `role='dokter'` on
submit, reachable by anyone with no auth. It is being rewritten as the new intake form,
which removes that code path.

## Background / current state

Two parallel diagnosis-history systems already exist:
- **Public flow:** `diagnosis.php` (pick symptoms, no identity) → `result.php` (runs
  `Diagnosa::hitungSubskala()`, inserts into `diagnoses` + `diagnosis_details`
  (`source='diagnosa'`) + `diagnosis_symptoms` (`source='diagnosa'`)).
- **Doctor flow:** `doctor/diagnosis.php` (pick symptoms for a specific patient) →
  `doctor/process_diagnosis.php` (same engine, inserts into `diagnosis_history` +
  `diagnosis_details` (`source='riwayat'`) + `diagnosis_symptoms` (`source='riwayat'`)).
  Every row in `diagnosis_history` requires a `patient_id` FK into `patients`, which in
  turn requires an `admin_id` FK into `admins` (the owning doctor).

`diagnosis_symptoms` (added earlier this project) stores the actual `symptom_id`s
selected per diagnosis, letting detail pages re-derive the symptom list and severity
label live in whichever language is currently active, instead of relying on text frozen
at diagnosis time. This phase's "send to doctor" copy must populate it too, so promoted
records get the same live-translation behavior on the doctor side.

## Scope

1. Public intake form (name + age, each independently skippable) gating entry to the
   symptom-picker.
2. Admin: new Name/Age columns on the diagnosis history list, plus a one-time "send to
   doctor" action that promotes a diagnosis into the doctor system.
3. Doctor: a visual marker on promoted records so they're distinguishable from
   diagnoses the doctor ran themselves.

Out of scope (explicitly deferred, not requested): re-sending/multi-doctor send,
matching repeat anonymous visitors to an existing patient record, editing a promoted
patient's name/age after the fact, admin fixing the doctor account this exposed (that
pre-existing junk row `admins.id=3` stays untouched per standing instruction).

## Data model changes

New migration: `migrations/2026-10-01_02_screening_intake.sql`

```sql
ALTER TABLE diagnoses
  ADD COLUMN patient_name VARCHAR(100) NOT NULL DEFAULT '-' AFTER symptoms_text,
  ADD COLUMN patient_age VARCHAR(10) NOT NULL DEFAULT '-' AFTER patient_name,
  ADD COLUMN sent_to_doctor_id INT NULL AFTER confidence_percentage,
  ADD COLUMN sent_at VARCHAR(50) NULL AFTER sent_to_doctor_id,
  ADD CONSTRAINT fk_diagnoses_sent_to_doctor FOREIGN KEY (sent_to_doctor_id) REFERENCES admins (id);

ALTER TABLE diagnosis_history
  ADD COLUMN origin ENUM('dokter','screening_mandiri') NOT NULL DEFAULT 'dokter' AFTER patient_id;
```

- `patient_name` / `patient_age`: free-text, `'-'` means the visitor skipped that field.
  Stored as strings (matching `patients.date_of_birth`, which is already `VARCHAR(50)`
  despite the name — no real date type exists anywhere in this schema for either field,
  so no conversion is needed when promoting).
- `sent_to_doctor_id` / `sent_at`: NULL = not yet sent. Once set, the row is locked from
  being sent again (enforced in `process/send_to_doctor.php`, not just hidden in the UI).
- `diagnosis_history.origin`: distinguishes doctor-run diagnoses (`'dokter'`, the
  existing default, so all current rows are unaffected) from promoted public
  screenings (`'screening_mandiri'`).

## Public flow changes

### `patients.php` (rewritten)

Currently: a form mislabeled "Jurusan"/"Nama"/"No HP" that POSTs to
`process/add_doctor.php` with `role=dokter` hardcoded — the security bug described
above. This becomes the new intake form:

- **Nama** text field + checkbox "Saya tidak ingin menyebutkan nama" (disables/clears
  the field when checked).
- **Usia** number field + its own independent checkbox "Saya tidak ingin menyebutkan
  usia".
- Submits to a new processor, `process/save_screening_intake.php`, which:
  - Reads name/age from POST (empty or skipped → `'-'`).
  - Sets `$_SESSION['screening_name']`, `$_SESSION['screening_age']`,
    `$_SESSION['screening_intake_done'] = true`.
  - Redirects to `diagnosis.php`.
- `process/add_doctor.php` itself is **not modified** — it's still the legitimate
  processor for `admin/add_doctor.php` (a logged-in admin actually creating a doctor
  account). Only `patients.php`'s form target changes.

### `diagnosis.php` (guarded)

Add at the top, before any other logic:

```php
if (!isset($_SESSION['screening_intake_done'])) {
    header('Location: patients.php');
    exit;
}
```

This makes the intake step mandatory — there is no other entry point into the symptom
picker.

### `result.php` (extended insert)

The existing `INSERT INTO diagnoses (...)` gains `patient_name` and `patient_age`,
sourced from `$_SESSION['screening_name']` / `$_SESSION['screening_age']` (falling back
to `'-'` if somehow unset, so a missing session never breaks the insert).

### Navigation

The public nav's "Data User" link (`_nav.php`) is relabeled to reflect the new content
(new lang key `nav_mulai_screening`) instead of reusing the generic `data_user` key,
which is already reused elsewhere for unrelated admin-panel text — reusing it here too
would risk the same wrong-text-in-two-places bug documented from the 1 Sept audit.

## Admin flow changes

### `admin/diagnosis_history.php` + `controller/c_Riwayat.php`

`TampilSemuaDenganRingkasan()` selects `patient_name` and `patient_age` alongside the
existing columns (no other query logic changes). The table gains two columns, **Nama**
and **Usia**, rendered directly (already `'-'` when skipped, no extra fallback needed).

A new **Aksi** column addition, per row:
- `sent_to_doctor_id IS NULL` → a "Kirim ke Dokter" control: a small dropdown of
  `SELECT id, name FROM admins WHERE role='dokter'` plus a submit button, POSTing to
  `process/send_to_doctor.php` with `diagnosis_id` and the chosen `doctor_id`.
- `sent_to_doctor_id IS NOT NULL` → static, non-interactive text: "✓ Terkirim ke
  {doctor name}" (join `admins` on `sent_to_doctor_id` to get the name).

### `process/send_to_doctor.php` (new, admin-auth-guarded)

Guard pattern matches other Phase 2 processors (`isset($_SESSION['username'])`, same as
`process/edit_symptom.php`). On POST with `diagnosis_id` + `doctor_id`:

1. Fetch the `diagnoses` row; 404/error if missing.
2. If `sent_to_doctor_id` is already set, abort with an error message (belt-and-braces
   against a resubmitted/bookmarked form — the UI already hides the control once sent).
3. Validate `doctor_id` refers to a real `admins` row with `role='dokter'`.
4. `INSERT INTO patients (name, date_of_birth, admin_id) VALUES (<patient_name>,
   <patient_age>, <doctor_id>)` → capture new `patient_id`.
5. `INSERT INTO diagnosis_history (patient_id, diagnosis_date, symptoms_text, summary,
   confidence_value, confidence_percentage, origin) VALUES (<patient_id>, <copied from
   diagnoses row>, 'screening_mandiri')` → capture new `diagnosis_history.id`.
6. Copy every `diagnosis_details` row where `diagnosis_id = <original> AND
   source='diagnosa'` into new rows with `diagnosis_id = <new id>, source='riwayat'`
   (same subscale/severity_level/severity_label/confidence values — not recomputed).
7. Copy every `diagnosis_symptoms` row the same way (`source='diagnosa'` →
   `source='riwayat'`, new `diagnosis_id`).
8. `UPDATE diagnoses SET sent_to_doctor_id=<doctor_id>, sent_at=<now> WHERE
   id=<diagnosis_id>`.
9. Redirect back to `admin/diagnosis_history.php` with a success flash message.

Steps 4-8 run as a single logical unit; if any insert fails partway the row stays
partially promoted rather than rolled back — acceptable here because every step is
independently idempotent-safe to re-run by hand if it ever happens (no destructive
step), and this matches the rest of the codebase's style (no existing transaction use
anywhere).

## Doctor flow changes

### `doctor/patient_history.php` and `doctor/diagnosis_detail.php`

Both already load rows from `diagnosis_history` — both add a visual badge when
`origin = 'screening_mandiri'`:

```html
🩺 <?php echo $_SESSION['langArray']['first_screening_badge'] ?? 'First Screening (Mandiri)'; ?>
```

Placed next to the per-subscale summary on the list, and near the date on the detail
page. Rows with `origin='dokter'` (every row created before this phase, plus every row
a doctor creates going forward through their own diagnosis flow) render unchanged.

## New language keys (id/en/tr/zh)

- `nama_atau_skip` / equivalents for the "tidak ingin menyebutkan nama" checkbox label
- `usia` / `usia_placeholder` / "tidak ingin menyebutkan usia" checkbox label
- `nav_mulai_screening` (new nav label replacing the `data_user` reuse)
- `kirim_ke_dokter` (button label)
- `pilih_dokter_tujuan` (dropdown prompt)
- `terkirim_ke` (status text template, e.g. "Terkirim ke %s")
- `first_screening_badge`
- `kolom_nama` / `kolom_usia` (table header labels, or reuse existing `nama`)

This is the full set of new keys required; the implementation plan assigns each its
translated value in all 4 languages, same pattern as every prior lang-key addition this
project.

## Testing

- `tests/test_dempster_shafer.php` / `tests/test_hitung_subskala.php`: unaffected
  (engine itself doesn't change), must stay green throughout.
- Manual verification per task: intake form skip-checkboxes behave independently;
  `diagnosis.php` redirects when session flag absent; `result.php` persists
  name/age correctly including the `'-'` default; admin list shows new columns and the
  send button; `process/send_to_doctor.php` produces a fully-formed doctor-side record
  (patient + history + details + symptoms) and blocks a second send; doctor pages show
  the badge only on promoted rows, never on doctor-authored ones.
