# English Rename Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Rename every Indonesian/Turkish table, column, file, and folder name in ds3 to English, without
changing any behavior — the app must work identically before and after, just with English internal names.

**Architecture:** Two independent layers, each fully verified before the next starts. Layer 1 (Tasks 1–9)
renames the database schema and updates every PHP file that queries it. Layer 2 (Tasks 10–16) renames files
and folders and updates every reference to them (includes, links, form actions, redirects). This mirrors
`docs/superpowers/specs/2026-08-25-english-rename-design.md` — read that file for the full rationale behind
every naming choice below.

**Tech Stack:** PHP 8.2, MySQL/MariaDB via XAMPP, no framework. No test framework installed —
`tests/test_dempster_shafer.php` and `tests/test_hitung_subskala.php` are plain PHP CLI assertion scripts.

**Working directory for all file paths:** `C:\xampp\htdocs\ds3\` (the live XAMPP-served copy). The last task
syncs into the git repo at `C:\Users\lenovo\Documents\Yüksek Lisans\Mental Health\Indonesia Application\ds3\`
and commits there.

**Before Task 1:** confirm you're on a dedicated branch (not `master`) in `C:\xampp\htdocs\ds3` — run
`git branch` and if the current branch is `master`, run `git checkout -b english-rename` first.

---

## PART 1 — Database Schema

### Task 1: Backup database and rename tables

**Files:** none (database operation only)

- [ ] **Step 1: Take a full backup**

Run:
```
"C:\xampp\mysql\bin\mysql.exe" -u root -e "SHOW TABLES;" spdempstershafer
"C:\xampp\mysqldump\bin\mysqldump.exe" -u root spdempstershafer > "C:\xampp\htdocs\ds3\backup_pre_rename_2026-08-25.sql"
```
(If `mysqldump.exe` isn't at that path, find it: it ships alongside `mysql.exe` in `C:\xampp\mysql\bin\`.)

Expected: the dump file exists and is non-trivial in size (the app has real seed + test data in it —
should be at least a few hundred KB given `ds_gejala`/`ds_tingkat` seed data plus test diagnosis rows).

Run: `dir "C:\xampp\htdocs\ds3\backup_pre_rename_2026-08-25.sql"` and confirm the file size is > 50KB.

- [ ] **Step 2: Rename all 10 tables in one atomic statement**

Run:
```
"C:\xampp\mysql\bin\mysql.exe" -u root spdempstershafer -e "RENAME TABLE ds_gejala TO ds_symptoms, ds_tingkat TO ds_severity_levels, ds_gejala_old TO ds_symptoms_legacy, ds_penyakit_old TO ds_diseases_legacy, ds_aturan_old TO ds_rules_legacy, diagnosa TO diagnoses, diagnosa_detail TO diagnosis_details, pasien TO patients, riwayat TO diagnosis_history, admin TO admins;"
```

- [ ] **Step 3: Verify**

Run: `"C:\xampp\mysql\bin\mysql.exe" -u root -e "SHOW TABLES;" spdempstershafer`

Expected: `ds_symptoms`, `ds_severity_levels`, `ds_symptoms_legacy`, `ds_diseases_legacy`, `ds_rules_legacy`,
`diagnoses`, `diagnosis_details`, `patients`, `diagnosis_history`, `admins`, `translations` — 11 tables, no
old names remaining.

---

### Task 2: Remap ENUM values before changing column definitions

**Files:** none (database operation only)

**Revised after a real failure found during execution:** the original version of this task tried to `UPDATE`
`'H'`/`'O'`/`'A'`/`'CA'` directly to `'Mild'`/`'Moderate'`/`'Severe'`/`'Extreme'` while the column was still
defined as `ENUM('H','O','A','CA')`. That's impossible — a MySQL ENUM column can only ever hold a value that's
already a member of its *current* definition, so no `UPDATE` can write a value that isn't in the list yet.
Depending on `sql_mode`, this either errors outright (if a `UNIQUE` constraint catches the resulting collision,
as happened on `ds_severity_levels`) or — worse — silently coerces every rejected value to the enum's index-0
placeholder (empty string), which is exactly the silent corruption this task exists to prevent (this is what
would have happened on `diagnosis_details.level_kode`, which has no unique constraint to catch it).

The correct sequence: widen the column to `VARCHAR` first (accepts any string, including the new English
words, without touching existing data), remap the values while it's a plain string column, verify, then
narrow it to the final `ENUM('Mild','Moderate','Severe','Extreme')`. Because this task now also changes the
column's TYPE (not just its values), it fully completes what Task 3 originally planned to do for these two
specific columns — Task 3's corresponding `CHANGE COLUMN` lines for `level`/`level_kode` becomes a pure rename
(the type is already correct by the time Task 3 runs).

- [ ] **Step 1: Widen `ds_severity_levels.level` to accept any string temporarily**

Run:
```
"C:\xampp\mysql\bin\mysql.exe" -u root spdempstershafer -e "ALTER TABLE ds_severity_levels MODIFY COLUMN level VARCHAR(20) NOT NULL;"
```

- [ ] **Step 2: Remap the values**

Run:
```
"C:\xampp\mysql\bin\mysql.exe" -u root spdempstershafer -e "UPDATE ds_severity_levels SET level = CASE level WHEN 'H' THEN 'Mild' WHEN 'O' THEN 'Moderate' WHEN 'A' THEN 'Severe' WHEN 'CA' THEN 'Extreme' END;"
```

- [ ] **Step 3: Verify no rows were missed**

Run:
```
"C:\xampp\mysql\bin\mysql.exe" -u root spdempstershafer -e "SELECT COUNT(*) FROM ds_severity_levels WHERE level NOT IN ('Mild','Moderate','Severe','Extreme');"
```
Expected: `0`. If not zero, STOP — do not proceed to Step 4 until this is 0 (some row has a value the CASE
didn't cover; find it with `SELECT * FROM ds_severity_levels WHERE level NOT IN ('Mild','Moderate','Severe','Extreme');`
and add a matching `WHEN` before re-running Step 2 — this is now safe to re-run since the column is a plain
VARCHAR at this point, no enum-membership constraint to fight).

- [ ] **Step 4: Narrow back to the final ENUM**

Run:
```
"C:\xampp\mysql\bin\mysql.exe" -u root spdempstershafer -e "ALTER TABLE ds_severity_levels MODIFY COLUMN level ENUM('Mild','Moderate','Severe','Extreme') NOT NULL;"
```

- [ ] **Step 5: Repeat the same 4-step sequence for `diagnosis_details.level_kode`**

Run in order:
```
"C:\xampp\mysql\bin\mysql.exe" -u root spdempstershafer -e "ALTER TABLE diagnosis_details MODIFY COLUMN level_kode VARCHAR(20) NOT NULL;"
"C:\xampp\mysql\bin\mysql.exe" -u root spdempstershafer -e "UPDATE diagnosis_details SET level_kode = CASE level_kode WHEN 'H' THEN 'Mild' WHEN 'O' THEN 'Moderate' WHEN 'A' THEN 'Severe' WHEN 'CA' THEN 'Extreme' END;"
```
Then verify:
```
"C:\xampp\mysql\bin\mysql.exe" -u root spdempstershafer -e "SELECT COUNT(*) FROM diagnosis_details WHERE level_kode NOT IN ('Mild','Moderate','Severe','Extreme');"
```
Expected: `0` — same STOP-and-investigate rule as Step 3 if not. Then narrow:
```
"C:\xampp\mysql\bin\mysql.exe" -u root spdempstershafer -e "ALTER TABLE diagnosis_details MODIFY COLUMN level_kode ENUM('Mild','Moderate','Severe','Extreme') NOT NULL;"
```

- [ ] **Step 6: Final verification of both columns' definitions**

Run:
```
"C:\xampp\mysql\bin\mysql.exe" -u root spdempstershafer -e "DESCRIBE ds_severity_levels; DESCRIBE diagnosis_details;"
```
Expected: `level` shows `enum('Mild','Moderate','Severe','Extreme')` and `level_kode` shows
`enum('Mild','Moderate','Severe','Extreme')` — column NAMES are still the old `level`/`level_kode` at this
point (that rename happens in Task 3), only the TYPE/allowed-values changed here.

---

### Task 3: Rename columns and change ENUM definitions

**Files:** none (database operation only)

**Note:** Task 2 already changed `ds_severity_levels.level` and `diagnosis_details.level_kode` from
`ENUM('H','O','A','CA')` to `ENUM('Mild','Moderate','Severe','Extreme')` (it had to, for reasons explained in
Task 2's revision note — a plain value-remap wasn't possible without a type change). Step 2 below still
includes those two `CHANGE COLUMN` lines targeting the same `ENUM('Mild','Moderate','Severe','Extreme')` — at
this point they're pure renames (old column name → new column name, same type), not corruption risks. This is
expected and correct: don't skip them, they're still needed to fix the column NAMES.

- [ ] **Step 1: `ds_symptoms` (formerly `ds_gejala`)**

**Correction found during code review:** the original version of this step claimed `nama_id/en/tr/zh` were
"already-correct" English names and needed no change. That's wrong — only the `_id/_en/_tr/_zh` suffix is
English; the base word `nama` (Indonesian for "name") is not. These 4 columns need renaming too, added below.

Run:
```sql
ALTER TABLE ds_symptoms
  CHANGE COLUMN kode_gejala symptom_code VARCHAR(10) NOT NULL,
  CHANGE COLUMN subskala subscale ENUM('D','A','S') NOT NULL,
  CHANGE COLUMN item_dass dass_item TINYINT NOT NULL,
  CHANGE COLUMN nama_id name_id VARCHAR(255) NOT NULL,
  CHANGE COLUMN nama_en name_en VARCHAR(255) NOT NULL,
  CHANGE COLUMN nama_tr name_tr VARCHAR(255) NOT NULL,
  CHANGE COLUMN nama_zh name_zh VARCHAR(255) NOT NULL,
  CHANGE COLUMN m_ho m_mild_moderate DECIMAL(4,2) NOT NULL,
  CHANGE COLUMN m_oa m_moderate_severe DECIMAL(4,2) NOT NULL,
  CHANGE COLUMN m_aca m_severe_extreme DECIMAL(4,2) NOT NULL,
  CHANGE COLUMN tipe_gejala symptom_type TINYINT NOT NULL;
```
(`m_theta`, `is_active`, `created_at`, `id` are unchanged — already-correct names, no `CHANGE COLUMN` needed
for those.)

Save this and every block below into one file, e.g. `C:\xampp\htdocs\ds3\_rename_columns.sql`, then run:
```
"C:\xampp\mysql\bin\mysql.exe" -u root spdempstershafer < "C:\xampp\htdocs\ds3\_rename_columns.sql"
```

- [ ] **Step 2: `ds_severity_levels` (formerly `ds_tingkat`)**

Same correction applies here — `nama_id/en/tr/zh` need renaming too, added below.

Append to the same file:
```sql
ALTER TABLE ds_severity_levels
  CHANGE COLUMN subskala subscale ENUM('D','A','S') NOT NULL,
  CHANGE COLUMN level severity_level ENUM('Mild','Moderate','Severe','Extreme') NOT NULL,
  CHANGE COLUMN urutan sort_order TINYINT NOT NULL,
  CHANGE COLUMN nama_id name_id VARCHAR(100) NOT NULL,
  CHANGE COLUMN nama_en name_en VARCHAR(100) NOT NULL,
  CHANGE COLUMN nama_tr name_tr VARCHAR(100) NOT NULL,
  CHANGE COLUMN nama_zh name_zh VARCHAR(100) NOT NULL,
  CHANGE COLUMN kett recommendation_id MEDIUMTEXT NOT NULL,
  CHANGE COLUMN kett_en recommendation_en MEDIUMTEXT,
  CHANGE COLUMN kett_tr recommendation_tr MEDIUMTEXT,
  CHANGE COLUMN kett_zh recommendation_zh MEDIUMTEXT;
```

- [ ] **Step 3: `diagnoses` (formerly `diagnosa`) and `diagnosis_history` (formerly `riwayat`)**

Append:
```sql
ALTER TABLE diagnoses
  CHANGE COLUMN id_diagnosa id INT NOT NULL AUTO_INCREMENT,
  CHANGE COLUMN tanggal diagnosis_date VARCHAR(50) NOT NULL,
  CHANGE COLUMN gejala symptoms_text TEXT NOT NULL,
  CHANGE COLUMN penyakit summary VARCHAR(250) NOT NULL,
  CHANGE COLUMN nilai confidence_value VARCHAR(50) NOT NULL,
  CHANGE COLUMN persentase confidence_percentage VARCHAR(50) NOT NULL;

ALTER TABLE diagnosis_history
  CHANGE COLUMN id_riwayat id INT NOT NULL AUTO_INCREMENT,
  CHANGE COLUMN id_pasien patient_id INT NOT NULL,
  CHANGE COLUMN tanggal diagnosis_date VARCHAR(50) NOT NULL,
  CHANGE COLUMN gejala symptoms_text TEXT NOT NULL,
  CHANGE COLUMN penyakit summary VARCHAR(200) NOT NULL,
  CHANGE COLUMN nilai confidence_value VARCHAR(20) NOT NULL,
  CHANGE COLUMN persentase confidence_percentage VARCHAR(20) NOT NULL;
```

- [ ] **Step 4: `diagnosis_details` (formerly `diagnosa_detail`)**

Append:
```sql
ALTER TABLE diagnosis_details
  CHANGE COLUMN id_diagnosa diagnosis_id INT NOT NULL,
  CHANGE COLUMN sumber source ENUM('diagnosa','riwayat') NOT NULL DEFAULT 'diagnosa',
  CHANGE COLUMN subskala subscale ENUM('D','A','S') NOT NULL,
  CHANGE COLUMN level_kode severity_level ENUM('Mild','Moderate','Severe','Extreme') NOT NULL,
  CHANGE COLUMN level_nama severity_label VARCHAR(100) NOT NULL,
  CHANGE COLUMN nilai confidence_value DECIMAL(6,4) NOT NULL,
  CHANGE COLUMN persentase confidence_percentage VARCHAR(10) NOT NULL;
```

- [ ] **Step 5: `patients` (formerly `pasien`) and `admins` (formerly `admin`)**

Append:
```sql
ALTER TABLE patients
  CHANGE COLUMN id_pasien id INT NOT NULL AUTO_INCREMENT,
  CHANGE COLUMN nama name VARCHAR(100) NOT NULL,
  CHANGE COLUMN tgl_lahir date_of_birth VARCHAR(50) NOT NULL,
  CHANGE COLUMN id_admin admin_id INT NOT NULL;

ALTER TABLE admins
  CHANGE COLUMN id_admin id INT NOT NULL AUTO_INCREMENT,
  CHANGE COLUMN nama name VARCHAR(40) NOT NULL,
  CHANGE COLUMN nohp phone VARCHAR(20) NOT NULL,
  CHANGE COLUMN tingkat role VARCHAR(250) NOT NULL;
```

- [ ] **Step 6: Run the whole file and verify**

Run:
```
"C:\xampp\mysql\bin\mysql.exe" -u root spdempstershafer < "C:\xampp\htdocs\ds3\_rename_columns.sql"
"C:\xampp\mysql\bin\mysql.exe" -u root spdempstershafer -e "DESCRIBE ds_symptoms; DESCRIBE ds_severity_levels; DESCRIBE diagnoses; DESCRIBE diagnosis_history; DESCRIBE diagnosis_details; DESCRIBE patients; DESCRIBE admins;"
```
Expected: every column listed above appears with its new name; no old Indonesian/Turkish column names remain
in any of these 7 tables. Delete `C:\xampp\htdocs\ds3\_rename_columns.sql` once confirmed (it was a scratch
file, not part of the codebase).

---

### Task 4: Update `controller/c_Diagnosa.php`

**Files:** Modify `C:\xampp\htdocs\ds3\controller\c_Diagnosa.php` (full replace)

- [ ] **Step 1: Replace the file contents**

```php
<?php
/**
 * Dempster-Shafer engine for the DASS-21 model.
 * Frame of discernment per subscale: 1=Mild, 2=Moderate, 3=Severe, 4=Extreme.
 * Focal sets are represented as comma-joined, numerically sorted strings, e.g. "1,2".
 */
class Diagnosa
{
    /**
     * Combine two mass functions via Dempster's rule of combination (unnormalized —
     * conflicting mass is collected under the '#CONFLICT#' key).
     *
     * @param array $m1 ['1,2' => 0.35, '2,3' => 0.30, ...]
     * @param array $m2 same shape
     * @return array combined, unnormalized
     */
    function combineMass($m1, $m2)
    {
        $combined = [];
        foreach ($m1 as $setA => $massA) {
            $a = explode(',', $setA);
            foreach ($m2 as $setB => $massB) {
                $b = explode(',', $setB);
                $intersection = array_values(array_unique(array_intersect($a, $b)));
                sort($intersection, SORT_NUMERIC);
                $key = empty($intersection) ? '#CONFLICT#' : implode(',', $intersection);
                $product = $massA * $massB;
                $combined[$key] = ($combined[$key] ?? 0) + $product;
            }
        }
        return $combined;
    }

    /**
     * Normalize a combined mass function by dividing out total conflict mass K.
     * Returns [] when K >= 1.0 — fully contradictory evidence with no combinable
     * belief left to normalize, rather than attempting to divide by zero.
     */
    function normalizeMass($combined)
    {
        $conflict = $combined['#CONFLICT#'] ?? 0;
        unset($combined['#CONFLICT#']);
        if ($conflict >= 1.0) {
            return [];
        }
        $normalized = [];
        foreach ($combined as $set => $mass) {
            $normalized[$set] = $mass / (1 - $conflict);
        }
        return $normalized;
    }

    /**
     * Build the initial mass function (evidence) for one DASS-21 symptom row.
     * Zero-mass entries are omitted.
     */
    function buildEvidence($m_mild_moderate, $m_moderate_severe, $m_severe_extreme, $m_theta)
    {
        $evidence = [];
        if ($m_mild_moderate > 0)   $evidence['1,2']     = (float)$m_mild_moderate;
        if ($m_moderate_severe > 0) $evidence['2,3']     = (float)$m_moderate_severe;
        if ($m_severe_extreme > 0)  $evidence['3,4']     = (float)$m_severe_extreme;
        if ($m_theta > 0)           $evidence['1,2,3,4'] = (float)$m_theta;
        return $evidence;
    }

    /**
     * Pignistic transformation: split each multi-element focal set's mass equally
     * across its member singletons, then sum per singleton.
     * Returns [1 => p1, 2 => p2, 3 => p3, 4 => p4] summing to 1.0.
     */
    function pignistic($combined)
    {
        $pig = [1 => 0.0, 2 => 0.0, 3 => 0.0, 4 => 0.0];
        foreach ($combined as $setStr => $mass) {
            $elems = explode(',', $setStr);
            $share = $mass / count($elems);
            foreach ($elems as $e) {
                $pig[(int)$e] += $share;
            }
        }
        return $pig;
    }

    /**
     * Legacy name kept as a thin alias in case any old call site still resolves to it.
     * NOT a behavioral drop-in for the pre-DASS21 row-pair callers: this delegates to
     * combineMass()'s conflict-key convention ('#CONFLICT#'), not the old '&theta;'
     * sentinel those callers expected. Exists only so the method name still resolves,
     * not so old call sites keep working unmodified.
     */
    function perkaliantabel($m, $densitas1, $densitas2, $densitas_baru)
    {
        $m1 = [];
        foreach ($densitas1 as $row) $m1[$row[0]] = $row[1];
        $m2 = [];
        foreach ($densitas2 as $row) $m2[$row[0]] = $row[1];
        $raw = $this->combineMass($m1, $m2);
        foreach ($raw as $k => $v) {
            $densitas_baru[$k] = ($densitas_baru[$k] ?? 0) + $v;
        }
        return $densitas_baru;
    }

    /**
     * Run the full Dempster-Shafer combination for one DASS-21 subscale across
     * the symptoms the patient selected in that subscale.
     *
     * @param string $subscale 'D', 'A', or 'S'
     * @param int[]  $symptomIds ids from ds_symptoms already filtered to this subscale
     * @return array|null null when no matching, active symptom found; otherwise
     *   ['severity_level'=>'Mild'|'Moderate'|'Severe'|'Extreme', 'severity_label'=>string,
     *    'confidence_value'=>float, 'confidence_percentage'=>string, 'recommendation'=>string]
     */
    function hitungSubskala($subscale, array $symptomIds)
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        // __DIR__-relative on purpose: this method is called both from root-level
        // pages (hasil.php) and from CLI test scripts under tests/, which have
        // different working directories. A bare "connection/connection.php" include
        // only resolves from the first kind of caller.
        include __DIR__ . '/../connection/connection.php';

        if (empty($symptomIds)) return null;

        $inList = implode(',', array_map('intval', $symptomIds));
        $subscaleEsc = mysqli_real_escape_string($con, $subscale);
        $sql = "SELECT m_mild_moderate, m_moderate_severe, m_severe_extreme, m_theta FROM ds_symptoms
                WHERE id IN ($inList) AND subscale = '$subscaleEsc' AND is_active = 1";
        $result = mysqli_query($con, $sql);
        if (!$result || mysqli_num_rows($result) === 0) return null;

        $combined = null;
        while ($row = mysqli_fetch_assoc($result)) {
            $evidence = $this->buildEvidence($row['m_mild_moderate'], $row['m_moderate_severe'], $row['m_severe_extreme'], $row['m_theta']);
            if ($combined === null) {
                $combined = $evidence;
            } else {
                $combined = $this->normalizeMass($this->combineMass($combined, $evidence));
            }
        }

        if (empty($combined)) return null;

        arsort($combined);
        $topKey   = array_key_first($combined);
        $topMass  = $combined[$topKey];
        $topElems = explode(',', $topKey);

        if (count($topElems) === 1) {
            $levelCodeInt = (int)$topElems[0];
            $confidenceValue = $topMass;
        } else {
            $pig = $this->pignistic($combined);
            arsort($pig);
            $levelCodeInt = array_key_first($pig);
            $confidenceValue = $pig[$levelCodeInt];
        }

        $levelMap = [1 => 'Mild', 2 => 'Moderate', 3 => 'Severe', 4 => 'Extreme'];
        $severityLevel = $levelMap[$levelCodeInt];

        $validLangs = ['id', 'en', 'tr', 'zh'];
        $lang    = (isset($_SESSION['lang']) && in_array($_SESSION['lang'], $validLangs)) ? $_SESSION['lang'] : 'id';
        $nameCol = 'name_' . $lang;
        $recommendationCol = 'recommendation_' . $lang;

        $sql = "SELECT $nameCol as name, IF($recommendationCol IS NULL OR $recommendationCol='', recommendation_id, $recommendationCol) as recommendation
                FROM ds_severity_levels WHERE subscale = '$subscaleEsc' AND severity_level = '$severityLevel'";
        $result = mysqli_query($con, $sql);
        $obj    = $result ? mysqli_fetch_object($result) : null;

        return [
            'severity_level'        => $severityLevel,
            'severity_label'        => $obj ? $obj->name : $severityLevel,
            'confidence_value'      => $confidenceValue,
            'confidence_percentage' => round($confidenceValue * 100, 2) . '%',
            'recommendation'        => $obj ? $obj->recommendation : '',
        ];
    }
}
```

Note the column name change `name_id/en/tr/zh` stayed the same as before (already-English pattern), but the
recommendation columns went from `kett`/`kett_en/tr/zh` to `recommendation_id/en/tr/zh` — every `_id` variant
(not just the others) needed the explicit `recommendation_id` name since there's no bare `kett`-equivalent
column anymore.

- [ ] **Step 2: Syntax check**

Run: `"C:\xampp\php\php.exe" -l "C:\xampp\htdocs\ds3\controller\c_Diagnosa.php"`
Expected: `No syntax errors detected in ...`

---

### Task 5: Update `controller/c_Gejala.php` → rename to `controller/c_Symptom.php`

**Files:**
- Create: `C:\xampp\htdocs\ds3\controller\c_Symptom.php`
- Delete: `C:\xampp\htdocs\ds3\controller\c_Gejala.php`

- [ ] **Step 1: Create the new file**

```php
<?php
class Gejala
{
    private function getLangCol($prefix = 'name') {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $valid = ['id', 'en', 'tr', 'zh'];
        $lang = (isset($_SESSION['lang']) && in_array($_SESSION['lang'], $valid)) ? $_SESSION['lang'] : 'id';
        return $prefix . '_' . $lang;
    }

    function TampilSemua() {
        include "../connection/connection.php";
        $col = $this->getLangCol();
        $query = mysqli_query($con, "SELECT id, symptom_code, $col as name FROM ds_symptoms");
        $i = 0;
        while ($d = mysqli_fetch_array($query)) {
            $data[$i]['id']   = $d['id'];
            $data[$i]['name'] = $d['name'];
            $i++;
        }
        return $data;
    }

    function EditGejala($id, $name_id, $name_en, $name_tr, $name_zh) {
        include "../connection/connection.php";
        $name_id = mysqli_real_escape_string($con, $name_id);
        $name_en = mysqli_real_escape_string($con, $name_en);
        $name_tr = mysqli_real_escape_string($con, $name_tr);
        $name_zh = mysqli_real_escape_string($con, $name_zh);
        mysqli_query($con, "UPDATE ds_symptoms SET name_id='$name_id', name_en='$name_en', name_tr='$name_tr', name_zh='$name_zh' WHERE id='$id'");
    }

    function TampilSatuData($id) {
        include "../connection/connection.php";
        $query = mysqli_query($con, "SELECT * FROM ds_symptoms WHERE id = '$id'");
        $g = mysqli_fetch_object($query);
        $this->id      = $g->id;
        $this->name_id = $g->name_id;
        $this->name_en = $g->name_en;
        $this->name_tr = $g->name_tr;
        $this->name_zh = $g->name_zh;
    }

    function TampilAngka() {
        include "../connection/connection.php";
        $query = mysqli_query($con, "SELECT max(id) as value FROM ds_symptoms");
        $g = mysqli_fetch_object($query);
        $this->value = $g->value;
    }

    /** Symptoms for one DASS-21 subscale, ordered by item number — used by the public diagnosis form. */
    function TampilBySubskala($subscale) {
        include "connection/connection.php";
        if (session_status() === PHP_SESSION_NONE) session_start();
        $valid = ['id', 'en', 'tr', 'zh'];
        $lang  = (isset($_SESSION['lang']) && in_array($_SESSION['lang'], $valid)) ? $_SESSION['lang'] : 'id';
        $col   = 'name_' . $lang;
        $subscale = mysqli_real_escape_string($con, $subscale);
        $query = mysqli_query($con, "SELECT id, $col as name FROM ds_symptoms
                                      WHERE subscale = '$subscale' AND is_active = 1
                                      ORDER BY dass_item");
        $i = 0;
        while ($d = mysqli_fetch_array($query)) {
            $data[$i]['id']   = $d['id'];
            $data[$i]['name'] = $d['name'];
            $i++;
        }
        return $data;
    }
}
error_reporting(0);
```

`InsertGejala()` and `HapusGejala()` are dropped entirely (not carried over) — Task 10 deletes every caller
of those two methods (`Admin/tgejala.php`, `ProsesA/t_gejala.php`, `ProsesA/d_gejala.php`), so keeping unused
insert/delete methods around would just be dead surface area, consistent with the Phase 1 lesson about
unguarded delete endpoints. `TampilSemua()` and `TampilSatuData()` now return `name`-keyed data instead of
`nama`-keyed — confirmed via `grep -rn "TampilSemua()\|TampilSatuData(" Admin/` that neither method currently
has any live caller (`Admin/gejala.php` and `Admin/egejala.php` are still Phase-1 placeholders, not real
forms), so there is no caller to update right now. This will matter once Phase 2 builds real CRUD forms on
top of this renamed controller — its implementation plan (written after this one) will use the `name`-keyed
shape directly, since it doesn't exist yet to depend on the old shape.

- [ ] **Step 2: Rename the `Gejala` class to `Symptom` inside the new file**

In `C:\xampp\htdocs\ds3\controller\c_Symptom.php`, change `class Gejala` (line 2) to `class Symptom`.

- [ ] **Step 3: Delete the old file and verify the new one parses**

Run:
```
del "C:\xampp\htdocs\ds3\controller\c_Gejala.php"
"C:\xampp\php\php.exe" -l "C:\xampp\htdocs\ds3\controller\c_Symptom.php"
```
Expected: `No syntax errors detected in ...`

- [ ] **Step 4: Update all 3 call sites (confirmed exhaustive via grep — `grep -rn "c_Gejala.php\|new Gejala" --include="*.php" .` finds exactly these and nothing else)**

`C:\xampp\htdocs\ds3\diagnosa.php` (lines 10-11):
```php
include "controller/c_Symptom.php";
$pt = new Symptom;
```

`C:\xampp\htdocs\ds3\pasien.php` (lines 10-11):
```php
include "controller/c_Symptom.php";
$pt = new Symptom;
```

`C:\xampp\htdocs\ds3\dokter\diagnosa.php` (lines 3-4):
```php
include "../controller/c_Symptom.php";
$pt = new Symptom;
```

- [ ] **Step 5: Verify**

Run: `grep -rn "c_Gejala\.php\|new Gejala" --include="*.php" .` from `C:\xampp\htdocs\ds3` — expect no matches.
Run `"C:\xampp\php\php.exe" -l` on all 3 files above — expect no syntax errors.

---

### Task 6: Update `hasil.php` and `diagnosa.php`

**Files:**
- Modify: `C:\xampp\htdocs\ds3\hasil.php`
- Modify: `C:\xampp\htdocs\ds3\diagnosa.php`

- [ ] **Step 1: Replace `hasil.php`'s processing block (top of file through the closing `?>` before the HTML)**

```php
<?php
include 'controller/c_Riwayat.php';
$cl = new Riwayat;
$cl->Count();

session_start();
include('function.php');
loadLanguage();

include "controller/c_Diagnosa.php";
$dg = new Diagnosa;
include "connection/connection.php";

// ── Computation variables ─────────────────────────────────────
$hasDiagnosis     = false;
$errorMinGejala   = false;
$results          = ['D' => null, 'A' => null, 'S' => null];
$selectedSymptoms = [];

$subskalaLabelKey = ['D' => 'subskala_depresi', 'A' => 'subskala_anxiety', 'S' => 'subskala_stres'];
$subskalaFallback = ['D' => 'Depresi', 'A' => 'Anxiety', 'S' => 'Stres'];

if (isset($_POST['gejala'])) {
    if (count($_POST['gejala']) < 2) {
        $errorMinGejala = true;
    } else {
        $hasDiagnosis = true;

        $inList = implode(',', array_map('intval', $_POST['gejala']));

        // Group the selected symptom ids by subscale
        $sql    = "SELECT id, subscale FROM ds_symptoms WHERE id IN ($inList) AND is_active = 1";
        $result = mysqli_query($con, $sql);
        $bySubskala = ['D' => [], 'A' => [], 'S' => []];
        while ($row = mysqli_fetch_assoc($result)) {
            $bySubskala[$row['subscale']][] = (int)$row['id'];
        }

        foreach (['D', 'A', 'S'] as $sk) {
            $results[$sk] = $dg->hitungSubskala($sk, $bySubskala[$sk]);
        }

        // Language column for symptom names
        $_validLangs = ['id', 'en', 'tr', 'zh'];
        $_lang       = (isset($_SESSION['lang']) && in_array($_SESSION['lang'], $_validLangs)) ? $_SESSION['lang'] : 'id';
        $_nameCol    = 'name_' . $_lang;

        // Selected symptoms list (for display + history text)
        $gejalaDbStr = '';
        $i = 0;
        foreach ($_POST['gejala'] as $item) {
            $query   = "SELECT $_nameCol as name FROM ds_symptoms WHERE id = " . (int)$item;
            $result  = mysqli_query($con, $query);
            $obj     = mysqli_fetch_object($result);
            $i++;
            $namaGejala         = $obj ? $obj->name : '';
            $selectedSymptoms[] = $namaGejala;
            $gejalaDbStr       .= $i . '. ' . $namaGejala . '<br>';
        }

        // Persist header row — keeps legacy diagnoses.summary/confidence_percentage columns
        // populated with a readable summary so pages that still read them directly
        // (e.g. an un-migrated riwayat view) show something sensible.
        // Skip entirely when no subscale produced a result (e.g. all posted symptom
        // ids were inactive/invalid) to avoid an orphan diagnoses header row with no
        // corresponding diagnosis_details rows.
        $anyResult = $results['D'] || $results['A'] || $results['S'];

        if ($anyResult) {
            $tanggal       = date('d-m-Y') . '<br>' . date('h:i:s A');
            $ringkasanNama = [];
            $ringkasanPct  = [];
            foreach (['D', 'A', 'S'] as $sk) {
                if ($results[$sk]) {
                    $ringkasanNama[] = $subskalaFallback[$sk] . ': ' . $results[$sk]['severity_label'];
                    $ringkasanPct[]  = $subskalaFallback[$sk] . ': ' . $results[$sk]['confidence_percentage'];
                }
            }
            $penyakitStr   = implode(' | ', $ringkasanNama);
            $persentaseStr = implode(' | ', $ringkasanPct);
            $nilaiStr      = $results['D']['confidence_value'] ?? ($results['A']['confidence_value'] ?? ($results['S']['confidence_value'] ?? 0));

            mysqli_query($con,
                "INSERT INTO diagnoses (diagnosis_date, symptoms_text, summary, confidence_value, confidence_percentage)
                 VALUES ('$tanggal', '" . mysqli_real_escape_string($con, $gejalaDbStr) . "', '" . mysqli_real_escape_string($con, $penyakitStr) . "',
                         '$nilaiStr', '" . mysqli_real_escape_string($con, $persentaseStr) . "')"
            );
            $idDiagnosa = mysqli_insert_id($con);

            // Persist per-subscale detail rows
            foreach (['D', 'A', 'S'] as $sk) {
                if (!$results[$sk]) continue;
                $r = $results[$sk];
                $nilaiEsc = (float)$r['confidence_value'];
                mysqli_query($con,
                    "INSERT INTO diagnosis_details (diagnosis_id, source, subscale, severity_level, severity_label, confidence_value, confidence_percentage)
                     VALUES ($idDiagnosa, 'diagnosa', '$sk', '{$r['severity_level']}',
                             '" . mysqli_real_escape_string($con, $r['severity_label']) . "',
                             $nilaiEsc, '{$r['confidence_percentage']}')"
                );
            }
        }
    }
}
?>
```

- [ ] **Step 2: Update the display block's array-key references**

Find every use of `$r['level_kode']`, `$r['level_nama']`, `$r['persentase']`, `$r['kett']` in the HTML section
below (the 3 result-card loop) and rename to `$r['severity_level']`, `$r['severity_label']`,
`$r['confidence_percentage']`, `$r['recommendation']` respectively — same 4 spots as before, just the key
names change:

```php
              <div class="result-card level-<?php echo strtolower($r['severity_level']); ?>" style="padding:1.75rem;">
                <p class="mb-1" style="font-size:.95rem; color:#888;"><?php echo $subskalaLabel; ?></p>
                <h3 class="mb-1"><?php echo htmlspecialchars($r['severity_label']); ?></h3>
                <p class="mb-2" style="font-size:.85rem; color:#666;">
                  <?php echo isset($_SESSION['langArray']['dengan_derajat'])
                      ? htmlspecialchars($_SESSION['langArray']['dengan_derajat'])
                      : 'derajat kepercayaan'; ?>
                  <strong><?php echo $r['confidence_percentage']; ?></strong>
                </p>
                <div class="confidence-bar mb-2">
                  <div class="confidence-fill level-<?php echo strtolower($r['severity_level']); ?>"
                       data-w="<?php echo (float)str_replace('%', '', $r['confidence_percentage']); ?>"></div>
                </div>
                <?php if (!empty($r['recommendation'])): ?>
                <p class="text-muted-mod mb-0" style="font-size:.85rem; line-height:1.7; text-align:left;">
                  <?php echo nl2br(htmlspecialchars($r['recommendation'])); ?>
                </p>
                <?php endif; ?>
```

Everything else in `hasil.php` (the HTML shell, footer, script block, the `$errorMinGejala` and no-POST
branches) is untouched.

- [ ] **Step 3: `diagnosa.php` needs no query changes**

`diagnosa.php` has no raw SQL of its own (confirmed by grep) — it only calls `$pt->TampilBySubskala($sk)`,
whose signature and return shape (`['id'=>..., 'name'=>...]`, was `['id'=>..., 'nama'=>...]`) changed in
Task 5. Find the checkbox-rendering loop (`foreach ($sectionData as $d): ... echo htmlspecialchars($d['nama']); ...`)
and change `$d['nama']` to `$d['name']` — that's the only change this file needs for Task 6.

- [ ] **Step 4: Syntax check both files**

Run:
```
"C:\xampp\php\php.exe" -l "C:\xampp\htdocs\ds3\hasil.php"
"C:\xampp\php\php.exe" -l "C:\xampp\htdocs\ds3\diagnosa.php"
```
Expected: `No syntax errors detected in ...` for both.

---

### Task 7: Update the test suites

**Files:**
- Modify: `C:\xampp\htdocs\ds3\tests\test_dempster_shafer.php`
- Modify: `C:\xampp\htdocs\ds3\tests\test_hitung_subskala.php`

- [ ] **Step 1: `test_dempster_shafer.php`**

This file tests `combineMass`/`normalizeMass`/`pignistic`/`buildEvidence` directly — none of those method
names or their parameter shapes changed (only the DB-facing `hitungSubskala()` did). Open the file and confirm
it still requires `controller/c_Diagnosa.php` (unchanged path) — no edits needed to this file's logic, but
re-run it after Task 4 to confirm:

Run: `"C:\xampp\php\php.exe" "C:\xampp\htdocs\ds3\tests\test_dempster_shafer.php"`
Expected: `ALL TESTS PASSED` (all 19 assertions).

- [ ] **Step 2: `test_hitung_subskala.php` — update the assertions**

This file DOES need updates: it asserts against `kode_gejala` values (unaffected — those are `symptom_code`
now in the DB but the test's `fetchIdByKode()` helper function queries by the OLD column name), and against
`level_kode`/`nilai`/`kett` return keys (now `severity_level`/`confidence_value`/`recommendation`), and
expects `level_kode === 'O'` (now `severity_level === 'Moderate'`).

Read the current file, then apply these changes:
1. In `fetchIdByKode($con, $kode)`: change `WHERE kode_gejala = '$kode'` to `WHERE symptom_code = '$kode'`.
2. Everywhere the test checks `$r['level_kode']`, change to `$r['severity_level']`.
3. Everywhere the test checks `$r['nilai']`, change to `$r['confidence_value']`.
4. Everywhere the test checks `$r['kett']`, change to `$r['recommendation']`.
5. The `$validLevels = ['H', 'O', 'A', 'CA']` array becomes `$validLevels = ['Mild', 'Moderate', 'Severe', 'Extreme']`.
6. The Test 4 assertion (pignistic branch, currently expects `level_kode` `'O'`) — update its expected value
   to `'Moderate'` and its key to `severity_level`.
7. `hitungSubskala('D', ...)` calls stay as-is (subscale codes `D`/`A`/`S` are unchanged — only the severity
   level codes changed, not the subscale codes).

- [ ] **Step 3: Run and confirm**

Run: `"C:\xampp\php\php.exe" "C:\xampp\htdocs\ds3\tests\test_hitung_subskala.php"`
Expected: `ALL TESTS PASSED` (all 11 assertions), and the reported `nilai`/`confidence_value` numbers are
IDENTICAL to before the rename (e.g. `0.3625` for the single-symptom G-D01 case) — same math, new names.

---

### Task 8: Sweep every remaining file that queries the renamed tables/columns

**Files to check and update** (grep-confirmed to reference one or more of: `ds_gejala`, `ds_tingkat`,
`diagnosa` as a table name, `pasien`, `riwayat`, `admin` as a table name, or any renamed column like
`id_pasien`, `id_admin`, `nama`, `nilai`, `persentase`, `tanggal`, `nohp`, `tgl_lahir`, `tingkat`):

- `controller/c_Pasien.php`
- `controller/c_Admin.php`
- `controller/c_Rekam.php`
- `controller/c_Riwayat.php`
- **Do NOT update `controller/c_BasisP.php`** — it still exists on disk at this point in the plan, but Task 10
  (Layer 2) deletes it entirely, and nothing currently reachable in the live app calls it (`Admin/basisp.php`
  is already a Phase-1 placeholder that doesn't invoke it; its only 3 callers — `ProsesA/{t,e,d}_basisp.php` —
  are deleted in that same Task 10). Updating its SQL now just to delete the file two tasks later is wasted
  work; leave it with old table/column names until Task 10 removes it.
- `Admin/riwayatd.php`
- `dokter/riwayatrm.php`
- `dokter/hdiagnosa.php` (currently a Phase-1 placeholder with no real SQL — confirm it stays that way,
  nothing to change)
- `dokter/diagnosa.php` (same — Phase-1 placeholder, confirm no SQL to change)
- Any other file `grep -rl "ds_gejala\|ds_tingkat\|ds_aturan\| pasien \|from pasien\| admin \|from admin\|id_pasien\|id_admin"` finds beyond the list above

**Do NOT touch** `lang/id.php`, `lang/en.php`, `lang/tr.php`, `lang/zh.php` even though they contain the
literal text `'diagnosa'` — those are UI translation dictionary keys (e.g. `'diagnosa' => 'Diagnosa'`), not
database references. Renaming those keys is out of scope for this plan (would require updating every place
that reads `$_SESSION['langArray']['diagnosa']` too, which is a much larger UI-text-key refactor, not part of
"schema and file names").

- [ ] **Step 1: Confirm the exact scope with a fresh grep**

Run (from `C:\xampp\htdocs\ds3`):
```
grep -rln "ds_gejala\|ds_tingkat\|ds_aturan" --include="*.php" .
grep -rln "FROM pasien\|from pasien\|INTO pasien\|UPDATE pasien\|id_pasien\|tgl_lahir" --include="*.php" .
grep -rln "FROM admin \|from admin \|UPDATE admin \|id_admin\| nohp\b" --include="*.php" .
grep -rln "FROM diagnosa\b\|from diagnosa\b\|INTO diagnosa\b\|id_diagnosa" --include="*.php" .
grep -rln "FROM riwayat\|from riwayat\|INTO riwayat\|id_riwayat" --include="*.php" .
```
(Use PowerShell `Select-String` if `grep` isn't on PATH: `Get-ChildItem -Recurse -Filter *.php | Select-String "ds_gejala"`.)

Cross-check the result against the file list above — if new files show up that weren't listed, include them
too; if a listed file no longer matches (e.g. because Task 4-7 already touched it), that's expected, skip it.

- [ ] **Step 2: For each remaining file, apply the exact column/table renames**

Use this complete old→new mapping (every identifier renamed anywhere in this plan) as the find/replace list
for whatever raw SQL or array-key access you find in each file:

| Old | New |
|---|---|
| `ds_gejala` (table) | `ds_symptoms` |
| `ds_tingkat` (table) | `ds_severity_levels` |
| `diagnosa` (table) | `diagnoses` |
| `diagnosa_detail` (table) | `diagnosis_details` |
| `pasien` (table) | `patients` |
| `riwayat` (table) | `diagnosis_history` |
| `admin` (table) | `admins` |
| `id_diagnosa` | `id` (on `diagnoses`) or `diagnosis_id` (FK on `diagnosis_details`) |
| `id_riwayat` | `id` |
| `id_pasien` | `id` (on `patients`) or `patient_id` (FK elsewhere) |
| `id_admin` | `id` (on `admins`) or `admin_id` (FK elsewhere) |
| `nama` (on patients/admins) | `name` |
| `tgl_lahir` | `date_of_birth` |
| `nohp` | `phone` |
| `tingkat` (admin role column) | `role` |
| `tanggal` | `diagnosis_date` |
| `gejala` (text column on diagnoses/riwayat) | `symptoms_text` |
| `penyakit` (text column on diagnoses/riwayat) | `summary` |
| `nilai` | `confidence_value` |
| `persentase` | `confidence_percentage` |
| `usernih` | leave as-is — this column doesn't exist in either the old or new schema; it's pre-existing dead code in `c_Riwayat.php::TampilSemua()`, out of scope for this rename |

For each file, apply exactly the renames relevant to what that file actually queries — e.g.
`controller/c_Pasien.php` only touches the `patients` table renames, `controller/c_Admin.php` only the
`admins` table renames.

- [ ] **Step 3: Verify zero old references remain**

Run:
```
grep -rn "ds_gejala\|ds_tingkat\|ds_aturan\b" --include="*.php" .
grep -rn "\bid_diagnosa\b\|\bid_riwayat\b\|\bid_pasien\b\|\bid_admin\b\|\btgl_lahir\b" --include="*.php" .
```
Expected: no matches (empty output) across the whole `C:\xampp\htdocs\ds3` tree, EXCLUDING
`docs/superpowers/` (historical spec/plan docs are allowed to mention old names) and
`backup_pre_rename_2026-08-25.sql` (the backup, also allowed).

- [ ] **Step 4: Syntax check every file touched in this task**

Run `"C:\xampp\php\php.exe" -l <file>` on every file you edited in Step 2. All must report
`No syntax errors detected`.

---

### Task 9: Full Layer-1 verification and commit

- [ ] **Step 1: Re-run both test suites one more time**

```
"C:\xampp\php\php.exe" "C:\xampp\htdocs\ds3\tests\test_dempster_shafer.php"
"C:\xampp\php\php.exe" "C:\xampp\htdocs\ds3\tests\test_hitung_subskala.php"
```
Expected: both `ALL TESTS PASSED`.

- [ ] **Step 2: Manual browser check**

1. `http://localhost/ds3/diagnosa.php` — confirm 21 checkboxes render across 3 sections, in all 4 languages.
2. Submit 2+ symptoms spanning D/A/S — confirm `hasil.php` shows 3 result cards correctly.
3. Check the database: `SELECT * FROM diagnoses ORDER BY id DESC LIMIT 1;` and
   `SELECT * FROM diagnosis_details WHERE diagnosis_id = (SELECT MAX(id) FROM diagnoses);` — confirm rows
   look correct with the new column names populated.

- [ ] **Step 3: Commit**

```bash
cd "C:\xampp\htdocs\ds3"
git add -A
git commit -m "refactor: rename database schema to English (Layer 1)

Renames all 10 tables and their columns from Indonesian/Turkish to
English, remapping ENUM level codes (H/O/A/CA -> Mild/Moderate/Severe/
Extreme) safely before changing column definitions. Updates every PHP
file that queries the renamed schema, including array-key names in
c_Diagnosa.php's hitungSubskala() return value and all its callers.
Diagnosis math is unchanged — verified via both CLI test suites.

Co-Authored-By: Claude Sonnet 5 <noreply@anthropic.com>"
```

---

## PART 2 — Files & Folders

### Task 10: Delete files already slated for removal in Phase 2

**Files to delete:**
- `C:\xampp\htdocs\ds3\controller\c_BasisP.php`
- `C:\xampp\htdocs\ds3\ProsesA\t_basisp.php`
- `C:\xampp\htdocs\ds3\ProsesA\e_basisp.php`
- `C:\xampp\htdocs\ds3\ProsesA\d_basisp.php`
- `C:\xampp\htdocs\ds3\Admin\tbasisp.php`
- `C:\xampp\htdocs\ds3\Admin\ebasisp.php`
- `C:\xampp\htdocs\ds3\Admin\tgejala.php`
- `C:\xampp\htdocs\ds3\ProsesA\t_gejala.php`
- `C:\xampp\htdocs\ds3\ProsesA\d_gejala.php`
- `C:\xampp\htdocs\ds3\Admin\tpenyakit.php`
- `C:\xampp\htdocs\ds3\ProsesA\t_penyakit.php`
- `C:\xampp\htdocs\ds3\ProsesA\d_penyakit.php`

Per `docs/superpowers/specs/2026-08-21-dass21-phase2-admin-dokter-design.md`, DASS-21's 21 symptoms and 12
severity levels are fixed (no add/delete CRUD) and the basis-pengetahuan concept no longer exists as a
separate table. These files are 100% of `c_BasisP.php`'s callers and all the "add"/"delete" gejala/tingkat
processors — renaming them now just to delete them when Phase 2 lands is wasted work.

- [ ] **Step 1: Confirm nothing else references these files**

Run (from `C:\xampp\htdocs\ds3`):
```
grep -rln "c_BasisP\|new BasisP" --include="*.php" .
grep -rln "tbasisp\.php\|ebasisp\.php\|dbasisp\.php\|d_basisp\.php" --include="*.php" .
grep -rln "tgejala\.php\|t_gejala\.php\|d_gejala\.php" --include="*.php" .
grep -rln "tpenyakit\.php\|t_penyakit\.php\|d_penyakit\.php" --include="*.php" .
```
Expected: only the files being deleted themselves show up (a file referencing itself doesn't count) — no
OTHER file links to or includes any of these 12 files. If something unexpected shows up, stop and investigate
before deleting (that file would break).

- [ ] **Step 2: Delete them**

```bash
cd "C:\xampp\htdocs\ds3"
git rm controller/c_BasisP.php ProsesA/t_basisp.php ProsesA/e_basisp.php ProsesA/d_basisp.php Admin/tbasisp.php Admin/ebasisp.php Admin/tgejala.php ProsesA/t_gejala.php ProsesA/d_gejala.php Admin/tpenyakit.php ProsesA/t_penyakit.php ProsesA/d_penyakit.php
```

- [ ] **Step 3: Verify the app still loads without fatal errors**

Run: `"C:\xampp\php\php.exe" -l "C:\xampp\htdocs\ds3\Admin\gejala.php"` and
`"C:\xampp\php\php.exe" -l "C:\xampp\htdocs\ds3\Admin\penyakit.php"` and
`"C:\xampp\php\php.exe" -l "C:\xampp\htdocs\ds3\Admin\basisp.php"` — these 3 pages are the Phase-1 placeholders
that don't reference any of the 12 deleted files, so they should still be fine.

- [ ] **Step 4: Commit**

```bash
git commit -m "chore: remove gejala/tingkat/basisp CRUD files retired by the Phase 2 design

DASS-21's 21 symptoms and 12 severity levels are fixed per
docs/superpowers/specs/2026-08-21-dass21-phase2-admin-dokter-design.md
(no add/delete). Removing now instead of renaming-then-deleting later.

Co-Authored-By: Claude Sonnet 5 <noreply@anthropic.com>"
```

---

### Task 11: Rename root-level files

**Renames (use `git mv` for every one, to preserve history):**

| Old | New |
|---|---|
| `diagnosa.php` | `diagnosis.php` |
| `hasil.php` | `result.php` |
| `pasien.php` | `patients.php` |
| `panduan.php` | `guide.php` |
| `beranda.php` | `home.php` |

`index.php`, `login.php`, `logout.php`, `plogin.php`, `set_language.php`, `function.php`, `whatsapp.php`,
`_nav.php` are already English — not renamed.

- [ ] **Step 1: Rename the files**

```bash
cd "C:\xampp\htdocs\ds3"
git mv diagnosa.php diagnosis.php
git mv hasil.php result.php
git mv pasien.php patients.php
git mv panduan.php guide.php
git mv beranda.php home.php
```

- [ ] **Step 2: Find every reference to the old names and update**

Run:
```
grep -rln "diagnosa\.php\|hasil\.php\|pasien\.php\|panduan\.php\|beranda\.php" --include="*.php" .
```
For each file found, replace every occurrence of `diagnosa.php` with `diagnosis.php`, `hasil.php` with
`result.php`, `pasien.php` with `patients.php`, `panduan.php` with `guide.php`, `beranda.php` with `home.php`
— in `<a href="">`, `<form action="">`, `header('Location: ...')`, and `include`/`require` statements alike.

Known call sites from this session's earlier work (not exhaustive — trust the grep above for the full list):
`_nav.php` (nav links), `index.php` (hero CTA links), `diagnosis.php` itself (`action="hasil.php"` →
`action="result.php"`), `result.php` itself (`href="diagnosa.php"` → `href="diagnosis.php"`, appears 3 times),
`login.php`/`Admin/_header.php`/`dokter/_header.php` (any "kembali ke beranda" links).

- [ ] **Step 3: Verify zero old references remain**

Run:
```
grep -rn "\bdiagnosa\.php\b\|\bhasil\.php\b\|\bpasien\.php\b\|\bpanduan\.php\b\|\bberanda\.php\b" --include="*.php" .
```
Expected: no matches outside `docs/superpowers/` (allowed — historical docs) and `dokter/pasien.php`,
`dokter/tpasien.php`, `dokter/epasien.php` (those are DIFFERENT files in the `dokter/` folder — Task 14
handles those, don't touch them here; the grep pattern above is anchored to the bare filename so it will
correctly NOT match `dokter/pasien.php` since that's a different path — if it does show up, that's fine, it's
a distinct file, not a leftover reference to the root-level one).

- [ ] **Step 4: Syntax check and browser smoke test**

Run `"C:\xampp\php\php.exe" -l <file>` on `diagnosis.php`, `result.php`, `patients.php`, `guide.php`,
`home.php`. Then in a browser: click every nav link on `http://localhost/ds3/index.php`, confirm none 404.

- [ ] **Step 5: Commit**

```bash
git add -A
git commit -m "refactor: rename root-level pages to English (Layer 2)

diagnosa.php -> diagnosis.php, hasil.php -> result.php, pasien.php ->
patients.php, panduan.php -> guide.php, beranda.php -> home.php.
Updated every include/link/form-action/redirect referencing them.

Co-Authored-By: Claude Sonnet 5 <noreply@anthropic.com>"
```

---

### Task 12: Rename `koneksi/` to `connection/`

- [ ] **Step 1: Rename**

```bash
cd "C:\xampp\htdocs\ds3"
git mv koneksi connection
git mv connection/koneksi.php connection/connection.php
```

- [ ] **Step 2: Find and update every reference**

Run:
```
grep -rln "koneksi/koneksi\.php\|\.\./koneksi/koneksi\.php\|koneksi\.php" --include="*.php" .
```
Every `include "koneksi/koneksi.php";` and `include "../koneksi/koneksi.php";` becomes
`include "connection/connection.php";` / `include "../connection/connection.php";` respectively — this
pattern appears in nearly every controller file (`c_Symptom.php`, `c_Diagnosa.php`, `c_Pasien.php`,
`c_Admin.php`, `c_Rekam.php`, `c_Riwayat.php`) and several root/Admin/dokter pages. Note: Task 4 and Task 5
already updated `c_Diagnosa.php` and `c_Symptom.php` to the new path (they were written that way from the
start in those tasks) — this task's grep will correctly show 0 remaining references in those two files;
everything else on the list still needs the literal string replaced.

- [ ] **Step 3: Verify zero old references remain**

Run: `grep -rn "koneksi" --include="*.php" .`
Expected: no matches outside `docs/superpowers/` and the backup `.sql` file.

- [ ] **Step 4: Syntax check and smoke test**

Run `"C:\xampp\php\php.exe" -l` on every file touched. Then load `http://localhost/ds3/index.php` and
`http://localhost/ds3/diagnosis.php` in a browser — both need a working DB connection to render correctly, so
if the connection path broke anywhere, one of these two pages will show a blank/broken page.

- [ ] **Step 5: Commit**

```bash
git add -A
git commit -m "refactor: rename koneksi/ to connection/ (Layer 2)

Co-Authored-By: Claude Sonnet 5 <noreply@anthropic.com>"
```

---

### Task 13: Rename `Admin/` → `admin/` and its files

**Folder + file renames (`git mv` each):**

| Old | New |
|---|---|
| `Admin/_header.php` | `admin/_header.php` |
| `Admin/_footer.php` | `admin/_footer.php` |
| `Admin/gejala.php` | `admin/symptoms.php` |
| `Admin/egejala.php` | `admin/edit_symptom.php` |
| `Admin/penyakit.php` | `admin/severity_levels.php` |
| `Admin/epenyakit.php` | `admin/edit_severity_level.php` |
| `Admin/basisp.php` | `admin/how_it_works.php` |
| `Admin/riwayatd.php` | `admin/diagnosis_history.php` |
| `Admin/dokter.php` | `admin/doctors.php` |
| `Admin/tdokter.php` | `admin/add_doctor.php` |
| `Admin/edokter.php` | `admin/edit_doctor.php` |
| `Admin/data.php` | `admin/data.php` (already English, folder-level rename only) |
| `Admin/profil.php` | `admin/profile.php` |

- [ ] **Step 1: Rename the folder first, then the files inside it**

```bash
cd "C:\xampp\htdocs\ds3"
git mv Admin admin
git mv admin/gejala.php admin/symptoms.php
git mv admin/egejala.php admin/edit_symptom.php
git mv admin/penyakit.php admin/severity_levels.php
git mv admin/epenyakit.php admin/edit_severity_level.php
git mv admin/basisp.php admin/how_it_works.php
git mv admin/riwayatd.php admin/diagnosis_history.php
git mv admin/dokter.php admin/doctors.php
git mv admin/tdokter.php admin/add_doctor.php
git mv admin/edokter.php admin/edit_doctor.php
git mv admin/profil.php admin/profile.php
```
(`git mv` on Windows/XAMPP is case-insensitive at the filesystem level for the folder itself — if
`git mv Admin admin` reports "already exists" due to case-insensitivity, use the two-step form instead:
`git mv Admin admin_tmp` then `git mv admin_tmp admin`.)

- [ ] **Step 2: Find every reference to the old paths**

Run:
```
grep -rln "Admin/" --include="*.php" .
grep -rln "gejala\.php\|egejala\.php\|penyakit\.php\|epenyakit\.php\|basisp\.php\|riwayatd\.php\|tdokter\.php\|edokter\.php\|profil\.php" --include="*.php" .
```
Update every match: `Admin/` → `admin/` in any path (includes, `href`, `action`, `Location:`), plus the
per-file renames from the table above wherever they appear (sidebar nav links in `admin/_header.php` itself,
cross-links between the Admin pages, `ProsesA/*.php` redirects that currently point back to
`Admin/gejala.php` etc — Task 15 will re-verify these once `ProsesA/` itself is renamed, but fix the `Admin/`
part now).

Also check `login.php` and any role-based redirect logic (`header('Location: Admin/...')` after a successful
admin login) — this is a common spot for a hardcoded old path.

- [ ] **Step 3: Verify zero old references remain**

Run: `grep -rn "\bAdmin/" --include="*.php" .`
Expected: no matches outside `docs/superpowers/`.

- [ ] **Step 4: Syntax check and browser smoke test**

`"C:\xampp\php\php.exe" -l` on every renamed/touched file. Then in a browser: log in as `admin`/`admin`,
click every sidebar link, confirm none 404 and the shell (sidebar/topbar from `admin/_header.php`) still
renders on every page.

- [ ] **Step 5: Commit**

```bash
git add -A
git commit -m "refactor: rename Admin/ to admin/ and its files to English (Layer 2)

Co-Authored-By: Claude Sonnet 5 <noreply@anthropic.com>"
```

---

### Task 14: Rename `dokter/` → `doctor/` and its files

**Folder + file renames:**

| Old | New |
|---|---|
| `dokter/_header.php` | `doctor/_header.php` |
| `dokter/_footer.php` | `doctor/_footer.php` |
| `dokter/diagnosa.php` | `doctor/diagnosis.php` |
| `dokter/hdiagnosa.php` | `doctor/process_diagnosis.php` |
| `dokter/riwayatrm.php` | `doctor/patient_history.php` |
| `dokter/pasien.php` | `doctor/patients.php` |
| `dokter/tpasien.php` | `doctor/add_patient.php` |
| `dokter/epasien.php` | `doctor/edit_patient.php` |
| `dokter/profil.php` | `doctor/profile.php` |

- [ ] **Step 1: Rename**

```bash
cd "C:\xampp\htdocs\ds3"
git mv dokter doctor
git mv doctor/diagnosa.php doctor/diagnosis.php
git mv doctor/hdiagnosa.php doctor/process_diagnosis.php
git mv doctor/riwayatrm.php doctor/patient_history.php
git mv doctor/pasien.php doctor/patients.php
git mv doctor/tpasien.php doctor/add_patient.php
git mv doctor/epasien.php doctor/edit_patient.php
git mv doctor/profil.php doctor/profile.php
```

- [ ] **Step 2: Find and update every reference**

Run:
```
grep -rln "dokter/" --include="*.php" .
grep -rln "diagnosa\.php\|hdiagnosa\.php\|riwayatrm\.php\|tpasien\.php\|epasien\.php\|profil\.php" --include="*.php" .
```
Update `dokter/` → `doctor/` everywhere, plus each per-file rename. Same categories of call site as Task 13:
login redirect after dokter login, sidebar nav in `doctor/_header.php`, cross-links between doctor pages,
`ProsesA/` processors that redirect back into this folder.

- [ ] **Step 3: Verify**

Run: `grep -rn "\bdokter/" --include="*.php" .`
Expected: no matches outside `docs/superpowers/`.

- [ ] **Step 4: Syntax check and browser smoke test**

Log in as `pakar`/`pakar`, click every link in the doctor panel, confirm none 404.

- [ ] **Step 5: Commit**

```bash
git add -A
git commit -m "refactor: rename dokter/ to doctor/ and its files to English (Layer 2)

Co-Authored-By: Claude Sonnet 5 <noreply@anthropic.com>"
```

---

### Task 15: Rename `ProsesA/` → `process/` and its files

**Folder + file renames** (12 files remain after Task 10 deleted the other 12 — the surviving set):

| Old | New |
|---|---|
| `ProsesA/d_dokter.php` | `process/delete_doctor.php` |
| `ProsesA/d_pasien.php` | `process/delete_patient.php` |
| `ProsesA/d_rekam.php` | `process/delete_record.php` |
| `ProsesA/d_riwayat.php` | `process/delete_diagnosis_history.php` |
| `ProsesA/diagnosa.php` | `process/legacy_diagnosis.php` (dead/orphaned file per earlier session finding — nothing
  links to it; rename it anyway for consistency, don't delete since deletion wasn't part of this plan's scope) |
| `ProsesA/e_dokter.php` | `process/edit_doctor.php` |
| `ProsesA/e_dprofil.php` | `process/edit_doctor_profile.php` |
| `ProsesA/e_gejala.php` | `process/edit_symptom.php` |
| `ProsesA/e_pasien.php` | `process/edit_patient.php` |
| `ProsesA/e_penyakit.php` | `process/edit_severity_level.php` |
| `ProsesA/e_profil.php` | `process/edit_profile.php` |
| `ProsesA/p_login.php` | `process/login.php` |
| `ProsesA/t_dokter.php` | `process/add_doctor.php` |
| `ProsesA/t_pasien.php` | `process/add_patient.php` |

- [ ] **Step 1: Rename**

```bash
cd "C:\xampp\htdocs\ds3"
git mv ProsesA process
git mv process/d_dokter.php process/delete_doctor.php
git mv process/d_pasien.php process/delete_patient.php
git mv process/d_rekam.php process/delete_record.php
git mv process/d_riwayat.php process/delete_diagnosis_history.php
git mv process/diagnosa.php process/legacy_diagnosis.php
git mv process/e_dokter.php process/edit_doctor.php
git mv process/e_dprofil.php process/edit_doctor_profile.php
git mv process/e_gejala.php process/edit_symptom.php
git mv process/e_pasien.php process/edit_patient.php
git mv process/e_penyakit.php process/edit_severity_level.php
git mv process/e_profil.php process/edit_profile.php
git mv process/p_login.php process/login.php
git mv process/t_dokter.php process/add_doctor.php
git mv process/t_pasien.php process/add_patient.php
```

- [ ] **Step 2: Find and update every reference**

Run:
```
grep -rln "ProsesA/" --include="*.php" .
```
This is the highest-traffic rename in Layer 2 — every `<form action="...">` in `admin/`, `doctor/`, and
`login.php` that submits to one of these processors needs its `action` attribute updated to the new
`process/...` path and filename. Go through each matched file, update every `ProsesA/xxx.php` reference to
`process/<new-name>.php` per the table above.

Special case: `process/edit_symptom.php` (from `e_gejala.php`) and `process/edit_severity_level.php` (from
`e_penyakit.php`) — these two file NAMES collide conceptually with nothing else, but double-check
`admin/edit_symptom.php` (the FORM, from Task 13) and `process/edit_symptom.php` (the PROCESSOR, this task)
don't get confused with each other when updating `<form action="edit_symptom.php">` — the form's action
should point to `../process/edit_symptom.php` (relative path crossing from `admin/` into `process/`), not to
itself.

- [ ] **Step 3: Verify**

Run: `grep -rn "\bProsesA/" --include="*.php" .`
Expected: no matches outside `docs/superpowers/`.

- [ ] **Step 4: Syntax check and full smoke test**

`"C:\xampp\php\php.exe" -l` on every renamed file. Then: submit the edit-symptom form in admin, submit the
edit-severity-level form, log in/out as admin and dokter (exercises `process/login.php`), confirm none error.

- [ ] **Step 5: Commit**

```bash
git add -A
git commit -m "refactor: rename ProsesA/ to process/ and its files to English (Layer 2)

Co-Authored-By: Claude Sonnet 5 <noreply@anthropic.com>"
```

---

### Task 16: `.htaccess` check, final full verification, and sync to the git repo

- [ ] **Step 1: Read and check `.htaccess`**

Run: `type "C:\xampp\htdocs\ds3\.htaccess"` (or open it). Per the Phase 1 memory, it contains a generic
rewrite rule (`RewriteBase /ds3/`, `.php` extension handling) — confirm it does NOT hardcode any of the
renamed file/folder names (`Admin`, `dokter`, `ProsesA`, `diagnosa.php`, etc.) inside a specific `RewriteRule`.
If it's purely generic (as expected), report "no changes needed to .htaccess" explicitly in the task output.
If it does hardcode something, update it and note what changed.

- [ ] **Step 2: Whole-tree final grep sweep**

Run:
```
grep -rn "Admin/\|dokter/\|ProsesA/\|koneksi" --include="*.php" --include="*.css" --include="*.js" --include=".htaccess" C:\xampp\htdocs\ds3 | grep -v "docs/superpowers"
```
Expected: empty (no matches). This is the final safety net catching anything the per-task verifications
missed (e.g. a reference inside a `.css`/`.js` file, which the per-task greps didn't check).

- [ ] **Step 3: Full click-through, all 3 roles**

1. Public: `index.php` → `guide.php` → `diagnosis.php` → submit → `result.php`. Switch language to each of
   id/en/tr/zh at each step.
2. Admin: log in as `admin`/`admin`, click every sidebar link, open the edit-symptom and
   edit-severity-level forms, submit an edit, confirm it saves.
3. Doctor: log in as `pakar`/`pakar`, click every sidebar link.

- [ ] **Step 4: Sync into the git repo and commit there**

```powershell
$src = "C:\xampp\htdocs\ds3"
$dst = "C:\Users\lenovo\Documents\Yüksek Lisans\Mental Health\Indonesia Application\ds3"
robocopy $src $dst /E /XD ".git" /XF "*.git*" /NFL /NDL /NP
```

Then in `C:\Users\lenovo\Documents\Yüksek Lisans\Mental Health\Indonesia Application\ds3`:
```bash
git checkout -b english-rename
git add -A
git status
```

Review the diff shows the expected renames (`git status` should show mostly `renamed:` entries, not
`deleted:`+`new file:` pairs — if it shows the latter, git's rename detection didn't kick in, which is
cosmetic/history-tracking only, not a functional problem, but worth noting).

```bash
git commit -m "refactor: rename entire codebase to English (schema + files/folders)

Squashed history of the English rename project — see
docs/superpowers/plans/2026-08-25-english-rename.md for the full
task-by-task breakdown and docs/superpowers/specs/2026-08-25-english-rename-design.md
for the design rationale. Database tables/columns and every PHP
file/folder name are now English; the DASS-21 Dempster-Shafer math and
all UI text (still 4 languages: id/en/tr/zh) are unchanged.

Co-Authored-By: Claude Sonnet 5 <noreply@anthropic.com>"
```

Do NOT merge to `main` yet — leave this on the `english-rename` branch for review, matching how Phase 1 was
handled (branch → review → merge only after explicit go-ahead).
