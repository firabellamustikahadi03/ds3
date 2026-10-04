# Phase 6: BPA Restructure Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Replace the Dempster-Shafer focal-set semantics, per-symptom BPA values, and winner-selection rule so the engine escalates severity with symptom count/weight instead of collapsing every input to "Moderate".

**Architecture:** Focal sets become nested ("at least X"): Θ={1,2,3,4}, {2,3,4}, {3,4}, {4}. Because each set is a subset of the one above it, every intersection is non-empty — conflict mass is structurally zero. Winner selection moves from the pignistic transform to the standard DS belief function read over an ordinal ladder: pick the highest level whose `Bel(at least X)` ≥ 0.50.

**Tech Stack:** PHP 8.2 + MySQL (mysqli, raw SQL), no framework.

**Project testing convention:** No unit-test framework beyond two hand-written PHP scripts. Each task verifies with `php -l`, then `php tests/test_dempster_shafer.php` and `php tests/test_hitung_subskala.php` (expect `ALL TESTS PASSED`), plus live `curl` against `http://localhost/ds3` and `"C:\xampp\mysql\bin\mysql.exe" -u root spdempstershafer` for data checks.

**Ordering note:** Task 1 renames columns, which breaks every reader until Task 2 lands. Tasks 1 and 2 must be executed back-to-back; the app is expected to be broken in between.

---

## File structure

| File | Responsibility | Task |
|---|---|---|
| `migrations/2026-10-05_01_bpa_restructure.sql` | Column rename + 21 new value rows | 1 |
| `controller/c_Diagnosa.php` | DS engine: focal sets, combination, decision rule | 2 |
| `controller/c_Symptom.php` | Symptom data access (properties, SELECT, UPDATE) | 2 |
| `admin/symptoms.php` | Admin list table | 3 |
| `admin/edit_symptom.php` | Admin edit form | 3 |
| `process/edit_symptom.php` | Edit form processor | 3 |
| `admin/how_it_works.php` | Method reference table | 3 |
| `tests/test_dempster_shafer.php` | Engine unit tests | 4 |
| `tests/test_hitung_subskala.php` | Integration + monotonicity tests | 4 |
| `lang/{id,en,tr,zh}.php` | `hiw_p1`/`hiw_p2`/`hiw_p3` method description | 5 |

---

### Task 1: Migration — rename columns and write new values

**Files:**
- Create: `migrations/2026-10-05_01_bpa_restructure.sql`

Severity assignment from the approved spec. Four value profiles:

| Category | m_min_moderate | m_min_severe | m_extreme | m_theta |
|---|---|---|---|---|
| Ringan | 0.20 | 0.05 | 0.00 | 0.75 |
| Sedang | 0.45 | 0.15 | 0.00 | 0.40 |
| Berat | 0.25 | 0.50 | 0.05 | 0.20 |
| Sangat Berat | 0.10 | 0.35 | 0.40 | 0.15 |

Assignment: **Ringan** = D04, A01, A03, S01, S06 · **Sedang** = D02, A02, A04, A05, S02, S03, S07 · **Berat** = D01, D03, D05, A06, S04, S05 · **Sangat Berat** = D06, D07, A07 (5 + 7 + 6 + 3 = 21).

- [ ] **Step 1: Write the migration**

```sql
-- Phase 6: restructure the BPA model.
-- Focal sets change from adjacent pairs ({Mild,Moderate}, {Moderate,Severe},
-- {Severe,Extreme}) to nested "at least X" sets ({Moderate,Severe,Extreme},
-- {Severe,Extreme}, {Extreme}). The columns are renamed to match the new meaning
-- and every row is rewritten - old values are NOT carried over, because the old
-- numbers describe a different semantics entirely.

ALTER TABLE ds_symptoms
  CHANGE COLUMN m_mild_moderate   m_min_moderate DECIMAL(4,2) NOT NULL DEFAULT 0.00,
  CHANGE COLUMN m_moderate_severe m_min_severe   DECIMAL(4,2) NOT NULL DEFAULT 0.00,
  CHANGE COLUMN m_severe_extreme  m_extreme      DECIMAL(4,2) NOT NULL DEFAULT 0.00;

-- Ringan: mostly Theta so one mild symptom cannot force the level up, but keeps
-- 0.20 on "at least Moderate" so many mild symptoms still accumulate.
UPDATE ds_symptoms SET m_min_moderate=0.20, m_min_severe=0.05, m_extreme=0.00, m_theta=0.75
  WHERE symptom_code IN ('G-D04','G-A01','G-A03','G-S01','G-S06');

-- Sedang
UPDATE ds_symptoms SET m_min_moderate=0.45, m_min_severe=0.15, m_extreme=0.00, m_theta=0.40
  WHERE symptom_code IN ('G-D02','G-A02','G-A04','G-A05','G-S02','G-S03','G-S07');

-- Berat
UPDATE ds_symptoms SET m_min_moderate=0.25, m_min_severe=0.50, m_extreme=0.05, m_theta=0.20
  WHERE symptom_code IN ('G-D01','G-D03','G-D05','G-A06','G-S04','G-S05');

-- Sangat Berat: direct mass on {Extreme} so one clinically critical symptom
-- (hopelessness, life-not-worth-living, active panic) moves the result by itself.
UPDATE ds_symptoms SET m_min_moderate=0.10, m_min_severe=0.35, m_extreme=0.40, m_theta=0.15
  WHERE symptom_code IN ('G-D06','G-D07','G-A07');
```

- [ ] **Step 2: Apply it**

```bash
"C:\xampp\mysql\bin\mysql.exe" -u root spdempstershafer < "C:\xampp\htdocs\ds3\migrations\2026-10-05_01_bpa_restructure.sql"
```
Expected: no output.

- [ ] **Step 3: Verify all 21 rows sum to exactly 1.00**

```bash
"C:\xampp\mysql\bin\mysql.exe" -u root spdempstershafer -e "
SELECT symptom_code, m_min_moderate, m_min_severe, m_extreme, m_theta,
       (m_min_moderate + m_min_severe + m_extreme + m_theta) AS total
FROM ds_symptoms ORDER BY subscale, dass_item;
SELECT COUNT(*) AS rows_not_summing_to_1 FROM ds_symptoms
WHERE ABS(m_min_moderate + m_min_severe + m_extreme + m_theta - 1.00) > 0.001;"
```
Expected: 21 rows listed, every `total` = 1.00, and `rows_not_summing_to_1` = 0.

- [ ] **Step 4: Commit**

```bash
cd "C:\xampp\htdocs\ds3"
git add migrations/2026-10-05_01_bpa_restructure.sql
git commit -m "feat(phase6): migrate ds_symptoms to nested focal-set BPA columns and values"
```

---

### Task 2: Engine + data layer

**Files:**
- Modify: `controller/c_Diagnosa.php`
- Modify: `controller/c_Symptom.php`

- [ ] **Step 1: Update the class docblock in `controller/c_Diagnosa.php`**

Find:
```php
/**
 * Dempster-Shafer engine for the DASS-21 model.
 * Frame of discernment per subscale: 1=Mild, 2=Moderate, 3=Severe, 4=Extreme.
 * Focal sets are represented as comma-joined, numerically sorted strings, e.g. "1,2".
 */
```
Replace with:
```php
/**
 * Dempster-Shafer engine for the DASS-21 model.
 * Frame of discernment per subscale: 1=Mild, 2=Moderate, 3=Severe, 4=Extreme.
 * Focal sets are represented as comma-joined, numerically sorted strings.
 *
 * Focal sets are NESTED, with "at least X" semantics:
 *   "1,2,3,4" = Theta  - symptom present, level not indicated
 *   "2,3,4"            - at least Moderate
 *   "3,4"              - at least Severe
 *   "4"                - Extreme
 * Because each set is a subset of the one above it, every pairwise intersection is
 * non-empty, so conflict mass is structurally always zero and Dempster normalisation
 * never divides by a shrinking (1 - K).
 */
```

- [ ] **Step 2: Rewrite `buildEvidence()` in `controller/c_Diagnosa.php`**

Find:
```php
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
```
Replace with:
```php
    /**
     * Build the initial mass function (evidence) for one DASS-21 symptom row.
     * Maps each column to its nested "at least X" focal set. Zero-mass entries
     * are omitted.
     */
    function buildEvidence($m_min_moderate, $m_min_severe, $m_extreme, $m_theta)
    {
        $evidence = [];
        if ($m_min_moderate > 0) $evidence['2,3,4']   = (float)$m_min_moderate;
        if ($m_min_severe > 0)   $evidence['3,4']     = (float)$m_min_severe;
        if ($m_extreme > 0)      $evidence['4']       = (float)$m_extreme;
        if ($m_theta > 0)        $evidence['1,2,3,4'] = (float)$m_theta;
        return $evidence;
    }
```

- [ ] **Step 3: Add the belief-ladder methods to `controller/c_Diagnosa.php`**

Find:
```php
    /**
     * Legacy name kept as a thin alias in case any old call site still resolves to it.
```
Replace with:
```php
    /**
     * Belief over the ordinal "at least X" ladder.
     *
     * This is the standard DS belief function Bel(A) = sum of m(B) for every B subset
     * of A, evaluated at A = {X, ..., Extreme}. A focal set is a subset of "at least X"
     * exactly when all of its elements are >= X, i.e. when its smallest element is >= X.
     *
     * @return array [1 => Bel(>=Mild), 2 => Bel(>=Moderate), 3 => Bel(>=Severe), 4 => Bel(Extreme)]
     */
    function beliefLadder($combined)
    {
        $belief = [1 => 0.0, 2 => 0.0, 3 => 0.0, 4 => 0.0];
        foreach ($combined as $setStr => $mass) {
            $minElement = min(array_map('intval', explode(',', $setStr)));
            for ($level = 1; $level <= $minElement; $level++) {
                $belief[$level] += $mass;
            }
        }
        return $belief;
    }

    /**
     * Pick the highest severity level whose belief still clears the threshold.
     * Bel(>=Mild) is always 1.0 because every focal set's elements are all >= Mild,
     * so a result always exists and the loop cannot fall through empty-handed.
     *
     * @return array [levelInt, belief at that level]
     */
    function selectLevel($belief, $threshold = 0.50)
    {
        for ($level = 4; $level >= 1; $level--) {
            if ($belief[$level] >= $threshold) return [$level, $belief[$level]];
        }
        return [1, $belief[1]];
    }

    /**
     * Legacy name kept as a thin alias in case any old call site still resolves to it.
```

- [ ] **Step 4: Update the SELECT and the decision step in `hitungSubskala()`**

Find:
```php
        $sql = "SELECT m_mild_moderate, m_moderate_severe, m_severe_extreme, m_theta FROM ds_symptoms
                WHERE id IN ($inList) AND subscale = '$subscaleEsc' AND is_active = 1";
```
Replace with:
```php
        $sql = "SELECT m_min_moderate, m_min_severe, m_extreme, m_theta FROM ds_symptoms
                WHERE id IN ($inList) AND subscale = '$subscaleEsc' AND is_active = 1";
```

Find:
```php
            $evidence = $this->buildEvidence($row['m_mild_moderate'], $row['m_moderate_severe'], $row['m_severe_extreme'], $row['m_theta']);
```
Replace with:
```php
            $evidence = $this->buildEvidence($row['m_min_moderate'], $row['m_min_severe'], $row['m_extreme'], $row['m_theta']);
```

Find:
```php
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
```
Replace with:
```php
        // Decision rule: read the belief function over the ordinal ladder and take the
        // highest level that still clears 50%. The pignistic transform is NOT used here
        // - with nested focal sets it is systematically biased upward, because "Severe"
        // draws a share from three focal sets while "Mild" draws only from Theta.
        $belief = $this->beliefLadder($combined);
        [$levelCodeInt, $confidenceValue] = $this->selectLevel($belief);
```

- [ ] **Step 5: Update `controller/c_Symptom.php` property assignments**

Find:
```php
        $this->m_mild_moderate   = $g->m_mild_moderate;
        $this->m_moderate_severe = $g->m_moderate_severe;
        $this->m_severe_extreme  = $g->m_severe_extreme;
```
Replace with:
```php
        $this->m_min_moderate = $g->m_min_moderate;
        $this->m_min_severe   = $g->m_min_severe;
        $this->m_extreme      = $g->m_extreme;
```

- [ ] **Step 6: Update the admin-list SELECT in `controller/c_Symptom.php`**

Find:
```php
                                             m_mild_moderate, m_moderate_severe, m_severe_extreme, m_theta, is_active
```
Replace with:
```php
                                             m_min_moderate, m_min_severe, m_extreme, m_theta, is_active
```

- [ ] **Step 7: Update `EditGejala()` in `controller/c_Symptom.php`**

Find:
```php
     * Caller must have already validated m_mild_moderate + m_moderate_severe + m_severe_extreme + m_theta == 1.00
     * server-side before calling this — this method does not re-validate.
     */
    function EditGejala($id, $name_id, $name_en, $name_tr, $name_zh, $m_mild_moderate, $m_moderate_severe, $m_severe_extreme, $m_theta) {
        include "../connection/connection.php";
        $id = (int)$id;
        $name_id = mysqli_real_escape_string($con, $name_id);
        $name_en = mysqli_real_escape_string($con, $name_en);
        $name_tr = mysqli_real_escape_string($con, $name_tr);
        $name_zh = mysqli_real_escape_string($con, $name_zh);
        $m_mild_moderate = (float)$m_mild_moderate;
        $m_moderate_severe = (float)$m_moderate_severe;
        $m_severe_extreme = (float)$m_severe_extreme;
        $m_theta = (float)$m_theta;
        mysqli_query($con, "UPDATE ds_symptoms SET
            name_id='$name_id', name_en='$name_en', name_tr='$name_tr', name_zh='$name_zh',
            m_mild_moderate=$m_mild_moderate, m_moderate_severe=$m_moderate_severe,
            m_severe_extreme=$m_severe_extreme, m_theta=$m_theta
            WHERE id=$id");
    }
```
Replace with:
```php
     * Caller must have already validated m_min_moderate + m_min_severe + m_extreme + m_theta == 1.00
     * server-side before calling this — this method does not re-validate.
     */
    function EditGejala($id, $name_id, $name_en, $name_tr, $name_zh, $m_min_moderate, $m_min_severe, $m_extreme, $m_theta) {
        include "../connection/connection.php";
        $id = (int)$id;
        $name_id = mysqli_real_escape_string($con, $name_id);
        $name_en = mysqli_real_escape_string($con, $name_en);
        $name_tr = mysqli_real_escape_string($con, $name_tr);
        $name_zh = mysqli_real_escape_string($con, $name_zh);
        $m_min_moderate = (float)$m_min_moderate;
        $m_min_severe = (float)$m_min_severe;
        $m_extreme = (float)$m_extreme;
        $m_theta = (float)$m_theta;
        mysqli_query($con, "UPDATE ds_symptoms SET
            name_id='$name_id', name_en='$name_en', name_tr='$name_tr', name_zh='$name_zh',
            m_min_moderate=$m_min_moderate, m_min_severe=$m_min_severe,
            m_extreme=$m_extreme, m_theta=$m_theta
            WHERE id=$id");
    }
```

- [ ] **Step 8: Syntax-check**

```bash
cd "C:\xampp\htdocs\ds3"
php -l controller/c_Diagnosa.php && php -l controller/c_Symptom.php
```
Expected: `No syntax errors detected` ×2.

- [ ] **Step 9: Smoke-test the engine directly**

```bash
cd "C:\xampp\htdocs\ds3"
php -r "
session_start(); \$_SESSION['lang']='id';
require 'controller/c_Diagnosa.php';
\$dg = new Diagnosa;
foreach ([[4],[4,2],[4,2,1],[4,2,1,3,5,6,7]] as \$ids) {
  \$r = \$dg->hitungSubskala('D', \$ids);
  echo count(\$ids) . ' gejala -> ' . \$r['severity_level'] . ' (' . \$r['confidence_percentage'] . ')' . PHP_EOL;
}"
```
Expected: four lines, the level never going *down* as symptom count rises, and not every line reading "Moderate". (ids 4,2,1,3,5,6,7 are D04 Ringan, D02 Sedang, D01 Berat, D03 Berat, D05 Berat, D06 Sangat Berat, D07 Sangat Berat.)

- [ ] **Step 10: Commit**

```bash
cd "C:\xampp\htdocs\ds3"
git add controller/c_Diagnosa.php controller/c_Symptom.php
git commit -m "feat(phase6): nested focal sets in buildEvidence + belief-ladder decision rule"
```

---

### Task 3: Admin UI follows the renamed columns

**Files:**
- Modify: `admin/symptoms.php`
- Modify: `admin/edit_symptom.php`
- Modify: `process/edit_symptom.php`
- Modify: `admin/how_it_works.php`

- [ ] **Step 1: `admin/symptoms.php` — headers and cells**

Find:
```php
              <th style="color:#fff;" width="12%">Mild-Moderate</th>
              <th style="color:#fff;" width="12%">Moderate-Severe</th>
              <th style="color:#fff;" width="12%">Severe-Extreme</th>
```
Replace with:
```php
              <th style="color:#fff;" width="12%">Min. Moderate</th>
              <th style="color:#fff;" width="12%">Min. Severe</th>
              <th style="color:#fff;" width="12%">Extreme</th>
```

Find:
```php
              <td><?php echo number_format((float)$row['m_mild_moderate'], 2); ?></td>
              <td><?php echo number_format((float)$row['m_moderate_severe'], 2); ?></td>
              <td><?php echo number_format((float)$row['m_severe_extreme'], 2); ?></td>
```
Replace with:
```php
              <td><?php echo number_format((float)$row['m_min_moderate'], 2); ?></td>
              <td><?php echo number_format((float)$row['m_min_severe'], 2); ?></td>
              <td><?php echo number_format((float)$row['m_extreme'], 2); ?></td>
```

- [ ] **Step 2: `admin/how_it_works.php` — headers and cells**

Find:
```php
              <th style="color:#fff;">Mild-Mod</th>
              <th style="color:#fff;">Mod-Sev</th>
              <th style="color:#fff;">Sev-Ext</th>
```
Replace with:
```php
              <th style="color:#fff;">Min.Mod</th>
              <th style="color:#fff;">Min.Sev</th>
              <th style="color:#fff;">Extreme</th>
```

Find:
```php
              <td><?php echo number_format((float)$row['m_mild_moderate'], 2); ?></td>
              <td><?php echo number_format((float)$row['m_moderate_severe'], 2); ?></td>
              <td><?php echo number_format((float)$row['m_severe_extreme'], 2); ?></td>
```
Replace with:
```php
              <td><?php echo number_format((float)$row['m_min_moderate'], 2); ?></td>
              <td><?php echo number_format((float)$row['m_min_severe'], 2); ?></td>
              <td><?php echo number_format((float)$row['m_extreme'], 2); ?></td>
```

- [ ] **Step 3: `admin/edit_symptom.php` — form fields and labels**

Find:
```php
          <div class="col-md-3">
            <label class="form-label">Mild&ndash;Moderate</label>
            <input type="number" step="0.01" min="0" max="1" class="form-control mass-input" name="m_mild_moderate" value="<?php echo htmlspecialchars((string)($s->m_mild_moderate ?? '0.00')); ?>" required>
          </div>
          <div class="col-md-3">
            <label class="form-label">Moderate&ndash;Severe</label>
            <input type="number" step="0.01" min="0" max="1" class="form-control mass-input" name="m_moderate_severe" value="<?php echo htmlspecialchars((string)($s->m_moderate_severe ?? '0.00')); ?>" required>
          </div>
          <div class="col-md-3">
            <label class="form-label">Severe&ndash;Extreme</label>
            <input type="number" step="0.01" min="0" max="1" class="form-control mass-input" name="m_severe_extreme" value="<?php echo htmlspecialchars((string)($s->m_severe_extreme ?? '0.00')); ?>" required>
          </div>
```
Replace with:
```php
          <div class="col-md-3">
            <label class="form-label">Minimal Moderate</label>
            <input type="number" step="0.01" min="0" max="1" class="form-control mass-input" name="m_min_moderate" value="<?php echo htmlspecialchars((string)($s->m_min_moderate ?? '0.00')); ?>" required>
          </div>
          <div class="col-md-3">
            <label class="form-label">Minimal Severe</label>
            <input type="number" step="0.01" min="0" max="1" class="form-control mass-input" name="m_min_severe" value="<?php echo htmlspecialchars((string)($s->m_min_severe ?? '0.00')); ?>" required>
          </div>
          <div class="col-md-3">
            <label class="form-label">Extreme</label>
            <input type="number" step="0.01" min="0" max="1" class="form-control mass-input" name="m_extreme" value="<?php echo htmlspecialchars((string)($s->m_extreme ?? '0.00')); ?>" required>
          </div>
```

- [ ] **Step 4: `process/edit_symptom.php` — POST field names**

Find:
```php
$m1 = (float)($_POST['m_mild_moderate'] ?? 0);
$m2 = (float)($_POST['m_moderate_severe'] ?? 0);
$m3 = (float)($_POST['m_severe_extreme'] ?? 0);
```
Replace with:
```php
$m1 = (float)($_POST['m_min_moderate'] ?? 0);
$m2 = (float)($_POST['m_min_severe'] ?? 0);
$m3 = (float)($_POST['m_extreme'] ?? 0);
```

(The `[0,1]` per-field bounds check and the sum-to-1.00 server-side re-validation below it stay exactly as they are — both are still correct under the new model.)

- [ ] **Step 5: Syntax-check**

```bash
cd "C:\xampp\htdocs\ds3"
php -l admin/symptoms.php && php -l admin/how_it_works.php && php -l admin/edit_symptom.php && php -l process/edit_symptom.php
```
Expected: `No syntax errors detected` ×4.

- [ ] **Step 6: Live-verify the admin pages render the new values**

```bash
cd /tmp
rm -f p6cookies.txt
curl -s -c p6cookies.txt -d "username=admin&password=admin" http://localhost/ds3/plogin.php -o /dev/null
curl -s -b p6cookies.txt http://localhost/ds3/admin/symptoms.php | grep -E "Min. Moderate|Min. Severe"
curl -s -b p6cookies.txt "http://localhost/ds3/admin/edit_symptom.php?id=1" | grep -E "name=\"m_min_moderate\"|name=\"m_min_severe\"|name=\"m_extreme\""
```
Expected: the new header labels appear on the list page, and all three renamed input fields appear on the edit form.

- [ ] **Step 7: Live-verify the edit form still saves correctly**

```bash
cd /tmp
curl -s -b p6cookies.txt -i -d "id=1&name_id=Tidak dapat merasakan perasaan positif sama sekali (anhedoni)&name_en=Unable to feel any positive emotion at all (anhedonia)&name_tr=Hic olumlu duygu hissedememe (anhedoni)&name_zh=完全无法感受到任何积极情绪(快感缺乏)&m_min_moderate=0.25&m_min_severe=0.50&m_extreme=0.05&m_theta=0.20" http://localhost/ds3/process/edit_symptom.php | grep -i "location"
"C:\xampp\mysql\bin\mysql.exe" -u root spdempstershafer -e "SELECT symptom_code, m_min_moderate, m_min_severe, m_extreme, m_theta FROM ds_symptoms WHERE id=1;"
```
Expected: `Location: ../admin/symptoms.php` (saved, not an `error=` redirect), and the row still reads 0.25 / 0.50 / 0.05 / 0.20.

- [ ] **Step 8: Commit**

```bash
cd "C:\xampp\htdocs\ds3"
git add admin/symptoms.php admin/how_it_works.php admin/edit_symptom.php process/edit_symptom.php
git commit -m "feat(phase6): admin UI follows renamed nested focal-set columns"
```

---

### Task 4: Tests

**Files:**
- Modify: `tests/test_dempster_shafer.php`
- Modify: `tests/test_hitung_subskala.php`

- [ ] **Step 1: Update Test 1 in `tests/test_dempster_shafer.php` to the nested sets**

Find:
```php
// Test 1: combineMass — two evidence sets with full overlap, no conflict
$m1 = ['1,2' => 0.6, '1,2,3,4' => 0.4];
$m2 = ['1,2' => 0.5, '1,2,3,4' => 0.5];
$combined = $dg->combineMass($m1, $m2);
assertClose($combined['1,2'], 0.80, 'combineMass no-conflict {1,2} total', $failures);
assertClose($combined['1,2,3,4'], 0.20, 'combineMass no-conflict theta total', $failures);
assertClose(array_sum($combined), 1.0, 'combineMass no-conflict sums to 1', $failures);
```
Replace with:
```php
// Test 1: combineMass — two nested evidence sets, no conflict
$m1 = ['2,3,4' => 0.6, '1,2,3,4' => 0.4];
$m2 = ['2,3,4' => 0.5, '1,2,3,4' => 0.5];
$combined = $dg->combineMass($m1, $m2);
assertClose($combined['2,3,4'], 0.80, 'combineMass nested {2,3,4} total', $failures);
assertClose($combined['1,2,3,4'], 0.20, 'combineMass nested theta total', $failures);
assertClose(array_sum($combined), 1.0, 'combineMass nested sums to 1', $failures);
```

- [ ] **Step 2: Add a zero-conflict guarantee test after Test 1**

Find:
```php
// Test 2: combineMass — with conflict (disjoint sets)
```
Replace with:
```php
// Test 1b: the nested focal sets can never produce conflict. Every pair of nested
// sets intersects in the smaller of the two, so '#CONFLICT#' must never appear —
// this is the structural property the whole Phase 6 model relies on.
$nestedA = ['3,4' => 0.5, '1,2,3,4' => 0.5];
$nestedB = ['4' => 0.4, '2,3,4' => 0.6];
$nestedCombined = $dg->combineMass($nestedA, $nestedB);
if (isset($nestedCombined['#CONFLICT#'])) {
    echo "FAIL: nested focal sets produced conflict mass, which should be impossible\n";
    $failures++;
} else {
    echo "PASS: nested focal sets produce zero conflict\n";
}
assertClose($nestedCombined['4'], 0.40, 'nested combine {4}', $failures);
assertClose($nestedCombined['3,4'], 0.30, 'nested combine {3,4}', $failures);
assertClose($nestedCombined['2,3,4'], 0.30, 'nested combine {2,3,4}', $failures);
assertClose(array_sum($nestedCombined), 1.0, 'nested combine sums to 1', $failures);

// Test 2: combineMass — with conflict (disjoint sets). combineMass() is generic
// math and must still handle conflict correctly even though the Phase 6 focal
// sets never generate any.
```

- [ ] **Step 3: Update the buildEvidence test (Test 5)**

Find:
```php
// Test 5: buildEvidence skips zero-mass entries and sums to 1
$ev = $dg->buildEvidence(0.35, 0.30, 0, 0.35);
if (isset($ev['3,4'])) {
    echo "FAIL: buildEvidence should omit zero-mass m_aca\n";
    $failures++;
} else {
    echo "PASS: buildEvidence omits zero-mass m_aca\n";
}
assertClose(array_sum($ev), 1.0, 'buildEvidence sums to 1', $failures);
```
Replace with:
```php
// Test 5: buildEvidence maps to nested sets, skips zero-mass entries, sums to 1.
// Arguments are the "Sedang" profile: m_min_moderate, m_min_severe, m_extreme, m_theta.
$ev = $dg->buildEvidence(0.45, 0.15, 0, 0.40);
if (isset($ev['4'])) {
    echo "FAIL: buildEvidence should omit zero-mass m_extreme\n";
    $failures++;
} else {
    echo "PASS: buildEvidence omits zero-mass m_extreme\n";
}
assertClose($ev['2,3,4'], 0.45, 'buildEvidence maps m_min_moderate to {2,3,4}', $failures);
assertClose($ev['3,4'], 0.15, 'buildEvidence maps m_min_severe to {3,4}', $failures);
assertClose($ev['1,2,3,4'], 0.40, 'buildEvidence maps m_theta to Theta', $failures);
assertClose(array_sum($ev), 1.0, 'buildEvidence sums to 1', $failures);

// Test 6: beliefLadder — Bel(>=X) is the total mass of focal sets whose smallest
// element is >= X.
$ladderInput = ['4' => 0.40, '3,4' => 0.30, '2,3,4' => 0.30];
$bel = $dg->beliefLadder($ladderInput);
assertClose($bel[1], 1.00, 'beliefLadder Bel(>=Mild) is always 1', $failures);
assertClose($bel[2], 1.00, 'beliefLadder Bel(>=Moderate)', $failures);
assertClose($bel[3], 0.70, 'beliefLadder Bel(>=Severe)', $failures);
assertClose($bel[4], 0.40, 'beliefLadder Bel(Extreme)', $failures);

// Test 7: selectLevel takes the HIGHEST level still clearing the threshold.
[$lvl, $conf] = $dg->selectLevel($bel);
if ($lvl === 3) {
    echo "PASS: selectLevel picks Severe (highest level with Bel >= 0.50)\n";
} else {
    echo "FAIL: selectLevel should pick level 3, got $lvl\n";
    $failures++;
}
assertClose($conf, 0.70, 'selectLevel returns the belief at the chosen level', $failures);

// Test 8: a vacuous mass function (all Theta) can only support Mild.
[$lvlVacuous, $confVacuous] = $dg->selectLevel($dg->beliefLadder(['1,2,3,4' => 1.0]));
if ($lvlVacuous === 1) {
    echo "PASS: selectLevel falls back to Mild when only Theta carries mass\n";
} else {
    echo "FAIL: selectLevel should return level 1 for a vacuous mass function, got $lvlVacuous\n";
    $failures++;
}
assertClose($confVacuous, 1.0, 'vacuous mass function still has Bel(>=Mild) = 1', $failures);
```

- [ ] **Step 4: Run the engine test suite**

```bash
cd "C:\xampp\htdocs\ds3"
php tests/test_dempster_shafer.php
```
Expected: `ALL TESTS PASSED`.

- [ ] **Step 5: Replace Test 4 in `tests/test_hitung_subskala.php` with a belief-ladder cross-check**

Find (the whole block from the Test 4 comment through the end of its `else` branch):
```php
// Test 4: a single gejala's own evidence, never combined with anything else,
// forces a multi-element top focal set -> hitungSubskala() must take the
// pignistic() branch, not the singleton shortcut. Test 1's 3-gejala
// combination happens to land on a singleton and never exercises that branch,
// so this test exists specifically to cover it.
$d01Id = fetchIdByKode($con, 'G-D01');
$rowRes = mysqli_query($con, "SELECT m_mild_moderate, m_moderate_severe, m_severe_extreme, m_theta FROM ds_symptoms WHERE id = " . (int)$d01Id);
$massRow = mysqli_fetch_assoc($rowRes);
$evidence = $dg->buildEvidence($massRow['m_mild_moderate'], $massRow['m_moderate_severe'], $massRow['m_severe_extreme'], $massRow['m_theta']);

// For the current seed data, G-D01 is m_ho=0.35, m_oa=0.30, m_aca=0.20, m_theta=0.15.
// m_ho dominates, so the top focal set after buildEvidence() is {1,2} (mass 0.35) —
// two elements, not a singleton — which is exactly the case the singleton shortcut
// in hitungSubskala() cannot handle, forcing it into the pignistic() branch.
$checkTop = $evidence;
arsort($checkTop);
$topKey = array_key_first($checkTop);
$topElems = explode(',', $topKey);
if (count($topElems) > 1) {
    echo "PASS: G-D01's own evidence has a multi-element top focal set ('$topKey'), so hitungSubskala() must use the pignistic() branch\n";
} else {
    echo "FAIL: G-D01's evidence unexpectedly has a singleton top focal set ('$topKey') — this test no longer exercises the pignistic branch; pick a different gejala\n";
    $failures++;
}

$r4 = $dg->hitungSubskala('D', [$d01Id]);
if ($r4 === null) {
    echo "FAIL: hitungSubskala('D', [G-D01]) returned null\n";
    $failures++;
} else {
    $validLevels = ['Mild', 'Moderate', 'Severe', 'Extreme'];
    if (in_array($r4['severity_level'], $validLevels)) {
        echo "PASS: hitungSubskala('D', [G-D01]) returned a valid level ({$r4['severity_level']})\n";
    } else {
        echo "FAIL: hitungSubskala('D', [G-D01]) returned invalid level_kode {$r4['severity_level']}\n";
        $failures++;
    }
    if ($r4['confidence_value'] >= 0 && $r4['confidence_value'] <= 1) {
        echo "PASS: hitungSubskala('D', [G-D01]) nilai is within [0,1] ({$r4['confidence_value']})\n";
    } else {
        echo "FAIL: hitungSubskala('D', [G-D01]) nilai out of range: {$r4['confidence_value']}\n";
        $failures++;
    }
    if (!empty($r4['recommendation'])) {
        echo "PASS: hitungSubskala('D', [G-D01]) returned non-empty kett\n";
    } else {
        echo "FAIL: hitungSubskala('D', [G-D01]) kett is empty\n";
        $failures++;
    }

    // Cross-check: hitungSubskala()'s nilai/level_kode must match calling
    // pignistic() directly on this exact evidence. This proves the pignistic
    // branch didn't just avoid crashing — it produced the right numbers.
    $pig = $dg->pignistic($evidence);
    arsort($pig);
    $expectedLevelInt = array_key_first($pig);
    $expectedNilai    = $pig[$expectedLevelInt];
    $levelMap = [1 => 'Mild', 2 => 'Moderate', 3 => 'Severe', 4 => 'Extreme'];
    assertClose($r4['confidence_value'], $expectedNilai, 'hitungSubskala(D, [G-D01]) nilai matches direct pignistic() computation', $failures);
    if ($r4['severity_level'] === $levelMap[$expectedLevelInt]) {
        echo "PASS: hitungSubskala('D', [G-D01]) level_kode matches direct pignistic() computation ({$r4['severity_level']})\n";
    } else {
        echo "FAIL: hitungSubskala('D', [G-D01]) level_kode {$r4['severity_level']} does not match expected {$levelMap[$expectedLevelInt]}\n";
        $failures++;
    }
}
```
Replace with:
```php
// Test 4: hitungSubskala()'s result must match the belief ladder computed directly
// from the same symptom's evidence. This proves the decision rule in the engine is
// really the belief function and not something that merely happens to agree.
$d01Id = fetchIdByKode($con, 'G-D01');
$rowRes = mysqli_query($con, "SELECT m_min_moderate, m_min_severe, m_extreme, m_theta FROM ds_symptoms WHERE id = " . (int)$d01Id);
$massRow = mysqli_fetch_assoc($rowRes);
$evidence = $dg->buildEvidence($massRow['m_min_moderate'], $massRow['m_min_severe'], $massRow['m_extreme'], $massRow['m_theta']);

assertClose(array_sum($evidence), 1.0, "G-D01's evidence sums to 1", $failures);

$r4 = $dg->hitungSubskala('D', [$d01Id]);
if ($r4 === null) {
    echo "FAIL: hitungSubskala('D', [G-D01]) returned null\n";
    $failures++;
} else {
    $validLevels = ['Mild', 'Moderate', 'Severe', 'Extreme'];
    if (in_array($r4['severity_level'], $validLevels)) {
        echo "PASS: hitungSubskala('D', [G-D01]) returned a valid level ({$r4['severity_level']})\n";
    } else {
        echo "FAIL: hitungSubskala('D', [G-D01]) returned invalid level {$r4['severity_level']}\n";
        $failures++;
    }
    if ($r4['confidence_value'] >= 0 && $r4['confidence_value'] <= 1) {
        echo "PASS: hitungSubskala('D', [G-D01]) confidence is within [0,1] ({$r4['confidence_value']})\n";
    } else {
        echo "FAIL: hitungSubskala('D', [G-D01]) confidence out of range: {$r4['confidence_value']}\n";
        $failures++;
    }
    if (!empty($r4['recommendation'])) {
        echo "PASS: hitungSubskala('D', [G-D01]) returned non-empty recommendation\n";
    } else {
        echo "FAIL: hitungSubskala('D', [G-D01]) recommendation is empty\n";
        $failures++;
    }

    $bel = $dg->beliefLadder($evidence);
    [$expectedLevelInt, $expectedConfidence] = $dg->selectLevel($bel);
    $levelMap = [1 => 'Mild', 2 => 'Moderate', 3 => 'Severe', 4 => 'Extreme'];
    assertClose($r4['confidence_value'], $expectedConfidence, 'hitungSubskala(D, [G-D01]) confidence matches direct beliefLadder/selectLevel computation', $failures);
    if ($r4['severity_level'] === $levelMap[$expectedLevelInt]) {
        echo "PASS: hitungSubskala('D', [G-D01]) level matches direct belief-ladder computation ({$r4['severity_level']})\n";
    } else {
        echo "FAIL: hitungSubskala('D', [G-D01]) level {$r4['severity_level']} does not match expected {$levelMap[$expectedLevelInt]}\n";
        $failures++;
    }
}

// Test 5: MONOTONICITY — the whole point of Phase 6. Adding symptoms must never
// lower the resulting severity level. Symptoms are added lightest-first so the
// sequence also demonstrates escalation rather than a flat line.
$sequence = ['G-D04', 'G-D02', 'G-D01', 'G-D03', 'G-D05', 'G-D06', 'G-D07'];
$levelRank = ['Mild' => 1, 'Moderate' => 2, 'Severe' => 3, 'Extreme' => 4];
$accumulated = [];
$previousRank = 0;
$previousLabel = '-';
$monotonic = true;
$observed = [];

foreach ($sequence as $code) {
    $accumulated[] = fetchIdByKode($con, $code);
    $res = $dg->hitungSubskala('D', $accumulated);
    if ($res === null) {
        echo "FAIL: monotonicity sequence returned null at " . count($accumulated) . " symptom(s)\n";
        $failures++;
        $monotonic = false;
        break;
    }
    $rank = $levelRank[$res['severity_level']];
    $observed[] = count($accumulated) . '=' . $res['severity_level'];
    if ($rank < $previousRank) {
        echo "FAIL: level DROPPED from $previousLabel to {$res['severity_level']} when going to " . count($accumulated) . " symptoms\n";
        $failures++;
        $monotonic = false;
    }
    $previousRank  = $rank;
    $previousLabel = $res['severity_level'];
}

if ($monotonic) {
    echo "PASS: severity never decreases as symptoms accumulate (" . implode(', ', $observed) . ")\n";
}

// Test 6: the model must be able to reach more than one level. If every input
// produced the same answer the engine would be useless — this is exactly the
// Phase 5 bug (everything collapsed to "Moderate") in regression-test form.
$distinctLevels = array_unique(array_map(fn($o) => explode('=', $o)[1], $observed));
if (count($distinctLevels) >= 2) {
    echo "PASS: engine produces more than one severity level across the sequence (" . implode('/', $distinctLevels) . ")\n";
} else {
    echo "FAIL: engine collapsed every input to a single level (" . implode('/', $distinctLevels) . ")\n";
    $failures++;
}
```

- [ ] **Step 6: Run both suites**

```bash
cd "C:\xampp\htdocs\ds3"
php tests/test_dempster_shafer.php
php tests/test_hitung_subskala.php
```
Expected: `ALL TESTS PASSED` from both, and the monotonicity line printing a rising sequence such as `1=Mild, 2=Moderate, ...`.

- [ ] **Step 7: Commit**

```bash
cd "C:\xampp\htdocs\ds3"
git add tests/test_dempster_shafer.php tests/test_hitung_subskala.php
git commit -m "test(phase6): cover nested focal sets, belief ladder, monotonicity and level diversity"
```

---

### Task 5: Rewrite the method description (`hiw_p1`/`hiw_p2`/`hiw_p3`)

**Files:**
- Modify: `lang/id.php`, `lang/en.php`, `lang/tr.php`, `lang/zh.php`

These three keys are echoed **raw** (not through `htmlspecialchars`) in
`admin/how_it_works.php` because they intentionally contain `<strong>`, `<em>`, `&rarr;`
and `&ndash;`. Keep the HTML. `hiw_p1` still describes the frame of discernment and
stays correct — only `hiw_p2` and `hiw_p3` change.

- [ ] **Step 1: `lang/id.php`**

Find:
```php
    'hiw_p2' => 'Tiap gejala yang dipilih pasien punya "fungsi mass" sendiri — seberapa besar dia menunjuk ke pasangan tingkat yang berdekatan (Mild&ndash;Moderate, Moderate&ndash;Severe, Severe&ndash;Extreme) plus porsi ketidakpastian (Theta). Saat pasien memilih lebih dari 1 gejala di subskala yang sama, nilai mass dari tiap gejala digabungkan berurutan memakai <strong>aturan kombinasi Dempster</strong>, lalu dinormalisasi untuk membuang bagian yang saling bertentangan.',
    'hiw_p3' => 'Hasil akhirnya adalah tingkat dengan mass/probabilitas terbesar. Kalau hasil kombinasi masih tumpang-tindih di 2 tingkat sekaligus, sistem memakai <strong>transformasi pignistik</strong> — membagi rata mass ke tingkat-tingkat yang tumpang-tindih itu, lalu memilih yang probabilitasnya tertinggi.',
```
Replace with:
```php
    'hiw_p2' => 'Tiap gejala yang dipilih pasien membawa bukti dalam bentuk "fungsi mass" yang menunjuk ke pernyataan berjenjang: <strong>minimal Moderate</strong>, <strong>minimal Severe</strong>, atau <strong>Extreme</strong> &mdash; ditambah porsi ketidakpastian (<em>Theta</em>) yang berarti "gejalanya ada, tapi belum menunjuk tingkat tertentu". Saat pasien memilih lebih dari 1 gejala di subskala yang sama, nilai mass digabungkan berurutan memakai <strong>aturan kombinasi Dempster</strong>. Karena himpunan-himpunan ini bersarang, irisannya selalu mengarah ke tingkat yang lebih tinggi &mdash; jadi makin banyak dan makin berat gejala yang dipilih, makin tinggi pula tingkat keparahan yang dihasilkan.',
    'hiw_p3' => 'Hasil akhir ditentukan lewat <strong>fungsi belief</strong>: sistem menghitung tingkat keyakinan untuk tiap pernyataan berjenjang (minimal Mild &rarr; minimal Moderate &rarr; minimal Severe &rarr; Extreme), lalu memilih tingkat <em>tertinggi</em> yang keyakinannya masih mencapai <strong>50%</strong>. Persentase yang ditampilkan adalah besarnya keyakinan pada tingkat tersebut.',
```

- [ ] **Step 2: `lang/en.php`**

Find:
```php
    'hiw_p2' => 'Every symptom a patient selects has its own "mass function" — how strongly it points to an adjacent pair of levels (Mild&ndash;Moderate, Moderate&ndash;Severe, Severe&ndash;Extreme) plus a share of uncertainty (Theta). When a patient selects more than 1 symptom in the same subscale, each symptom\'s mass values are combined sequentially using the <strong>Dempster combination rule</strong>, then normalized to discard the conflicting portion.',
    'hiw_p3' => 'The final result is the level with the largest mass/probability. If the combined result still overlaps 2 levels at once, the system uses the <strong>pignistic transformation</strong> — splitting the mass evenly across the overlapping levels, then choosing the one with the highest probability.',
```
Replace with:
```php
    'hiw_p2' => 'Every symptom a patient selects carries evidence as a "mass function" pointing at a graded statement: <strong>at least Moderate</strong>, <strong>at least Severe</strong>, or <strong>Extreme</strong> &mdash; plus a share of uncertainty (<em>Theta</em>) meaning "the symptom is present, but it does not pin down a level". When a patient selects more than 1 symptom in the same subscale, the mass values are combined sequentially using the <strong>Dempster combination rule</strong>. Because these sets are nested, their intersections always point upward &mdash; so the more and the heavier the symptoms selected, the higher the resulting severity.',
    'hiw_p3' => 'The final result comes from the <strong>belief function</strong>: the system computes how strongly it believes each graded statement (at least Mild &rarr; at least Moderate &rarr; at least Severe &rarr; Extreme), then picks the <em>highest</em> level whose belief still reaches <strong>50%</strong>. The percentage shown is the belief at that level.',
```

- [ ] **Step 3: `lang/tr.php`**

Find:
```php
    'hiw_p2' => 'Hastanın seçtiği her belirtinin kendine ait bir "kütle fonksiyonu" vardır — birbirine komşu düzey çiftine (Mild&ndash;Moderate, Moderate&ndash;Severe, Severe&ndash;Extreme) ne kadar güçlü işaret ettiği artı bir belirsizlik payı (Theta). Hasta aynı alt ölçekte 1\'den fazla belirti seçtiğinde, her belirtinin kütle değerleri <strong>Dempster birleştirme kuralı</strong> kullanılarak sırayla birleştirilir, ardından çelişen kısmı atmak için normalize edilir.',
    'hiw_p3' => 'Nihai sonuç, en büyük kütle/olasılığa sahip düzeydir. Birleştirme sonucu hâlâ 2 düzeyde aynı anda örtüşüyorsa, sistem <strong>pignistik dönüşümü</strong> kullanır — kütleyi örtüşen düzeyler arasında eşit olarak bölüştürür, ardından en yüksek olasılığa sahip olanı seçer.',
```
Replace with:
```php
    'hiw_p2' => 'Hastanın seçtiği her belirti, kademeli bir ifadeye işaret eden bir "kütle fonksiyonu" olarak kanıt taşır: <strong>en az Moderate</strong>, <strong>en az Severe</strong> veya <strong>Extreme</strong> &mdash; ayrıca "belirti var, ancak düzeyi belirlemiyor" anlamına gelen bir belirsizlik payı (<em>Theta</em>). Hasta aynı alt ölçekte 1\'den fazla belirti seçtiğinde, kütle değerleri <strong>Dempster birleştirme kuralı</strong> ile sırayla birleştirilir. Bu kümeler iç içe olduğundan kesişimleri her zaman yukarıyı gösterir &mdash; yani seçilen belirtiler ne kadar çok ve ağır olursa, ortaya çıkan şiddet düzeyi de o kadar yüksek olur.',
    'hiw_p3' => 'Nihai sonuç <strong>belief (inanç) fonksiyonundan</strong> gelir: sistem her kademeli ifadeye ne kadar inandığını hesaplar (en az Mild &rarr; en az Moderate &rarr; en az Severe &rarr; Extreme), ardından inanç değeri hâlâ <strong>%50</strong>\'ye ulaşan <em>en yüksek</em> düzeyi seçer. Gösterilen yüzde, o düzeydeki inanç değeridir.',
```

- [ ] **Step 4: `lang/zh.php`**

Find:
```php
    'hiw_p2' => '患者选择的每个症状都有自己的"质量函数"——它指向相邻级别对（Mild&ndash;Moderate、Moderate&ndash;Severe、Severe&ndash;Extreme）的强度，以及不确定性份额（Theta）。当患者在同一子量表中选择多个症状时，每个症状的质量值将使用<strong>Dempster组合规则</strong>依次组合，然后归一化以消除冲突部分。',
    'hiw_p3' => '最终结果是质量/概率最大的级别。如果组合结果仍然同时重叠2个级别，系统将使用<strong>pignistic转换</strong>——将质量平均分配到重叠的级别，然后选择概率最高的一个。',
```
Replace with:
```php
    'hiw_p2' => '患者选择的每个症状都以"质量函数"的形式承载证据，指向一个分级陈述：<strong>至少 Moderate</strong>、<strong>至少 Severe</strong> 或 <strong>Extreme</strong>&mdash;&mdash;再加上一份不确定性（<em>Theta</em>），意思是"症状存在，但尚未指明级别"。当患者在同一子量表中选择多个症状时，质量值将使用<strong>Dempster组合规则</strong>依次组合。由于这些集合是嵌套的，它们的交集始终指向更高的级别&mdash;&mdash;因此所选症状越多、越严重，得出的严重程度就越高。',
    'hiw_p3' => '最终结果来自<strong>信任函数（belief function）</strong>：系统计算对每个分级陈述的信任程度（至少 Mild &rarr; 至少 Moderate &rarr; 至少 Severe &rarr; Extreme），然后选择信任度仍达到 <strong>50%</strong> 的<em>最高</em>级别。显示的百分比就是该级别的信任度。',
```

- [ ] **Step 5: Syntax-check and verify live in two languages**

```bash
cd "C:\xampp\htdocs\ds3"
php -l lang/id.php && php -l lang/en.php && php -l lang/tr.php && php -l lang/zh.php
cd /tmp
curl -s -b p6cookies.txt "http://localhost/ds3/set_language.php?lang=id" -o /dev/null
curl -s -b p6cookies.txt http://localhost/ds3/admin/how_it_works.php | grep -o "fungsi belief"
curl -s -b p6cookies.txt "http://localhost/ds3/set_language.php?lang=tr" -o /dev/null
curl -s -b p6cookies.txt http://localhost/ds3/admin/how_it_works.php | grep -o "belief (inanç) fonksiyonundan"
curl -s -b p6cookies.txt "http://localhost/ds3/set_language.php?lang=id" -o /dev/null
```
Expected: 4× `No syntax errors detected`, then both greps matching (the new wording renders in Indonesian and Turkish). The last call resets the session language back to Indonesian.

- [ ] **Step 6: Commit**

```bash
cd "C:\xampp\htdocs\ds3"
git add lang/id.php lang/en.php lang/tr.php lang/zh.php
git commit -m "docs(phase6): rewrite how-it-works method description for nested sets + belief ladder"
```

---

### Task 6: Re-run the accuracy evaluation and compare

**Files:** none (verification only)

`eval_accuracy.php` needs no edit — it calls `hitungSubskala()` through the public API and
converts scenarios to symptom ids, both unchanged by Phase 6.

- [ ] **Step 1: Keep the Phase 5 numbers for comparison**

The committed `eval_accuracy_output.txt` from Phase 5 is the "before" baseline: **8/31
exact matches (25.8%)** at threshold ≥1, **5/22 (22.7%)** at threshold ≥2, and every one
of the 69 rows reading "Moderate".

```bash
cd "C:\xampp\htdocs\ds3"
cp eval_accuracy_output.txt eval_accuracy_output_before_phase6.txt
```

- [ ] **Step 2: Re-run it**

```bash
cd "C:\xampp\htdocs\ds3"
php eval_accuracy.php | tee eval_accuracy_output.txt
```
Expected: the `ds3 @>=1` column now contains more than one distinct level (the Phase 5
run had "Moderate" on all 69 rows), and the summary section prints new match
percentages.

- [ ] **Step 3: Confirm the collapse is gone**

```bash
cd "C:\xampp\htdocs\ds3"
grep -c "Moderate" eval_accuracy_output.txt
awk 'NR>2 && NF>4 {print $(NF-1)}' eval_accuracy_output.txt | sort | uniq -c
```
Expected: the distinct-level tally shows at least two different ds3 levels across the 69
rows. Report the new exact-match percentages to the user alongside the Phase 5 baseline —
**do not** claim the accuracy improved unless the numbers actually show it; if agreement
went down, report that honestly, since the structural fix (escalation working, all levels
reachable) is the goal, and the official-vs-ds3 agreement number is a separate matter
affected by the instrument mismatch already documented in the Phase 5 spec.

- [ ] **Step 4: Live walkthrough of the public flow**

```bash
cd /tmp
rm -f p6pub.txt
curl -s -c p6pub.txt -d "patient_name=Phase6+Ringan&patient_age=25" http://localhost/ds3/process/save_screening_intake.php -o /dev/null
curl -s -b p6pub.txt -c p6pub.txt -d "gejala[]=4&gejala[]=15" http://localhost/ds3/result.php -o /dev/null
rm -f p6pub2.txt
curl -s -c p6pub2.txt -d "patient_name=Phase6+Berat&patient_age=25" http://localhost/ds3/process/save_screening_intake.php -o /dev/null
curl -s -b p6pub2.txt -c p6pub2.txt -d "gejala[]=1&gejala[]=3&gejala[]=5&gejala[]=6&gejala[]=7&gejala[]=2&gejala[]=4" http://localhost/ds3/result.php -o /dev/null
"C:\xampp\mysql\bin\mysql.exe" -u root spdempstershafer -e "
SELECT d.patient_name, dd.subscale, dd.severity_level, dd.confidence_percentage
FROM diagnoses d JOIN diagnosis_details dd ON dd.diagnosis_id = d.id
WHERE d.patient_name LIKE 'Phase6%' ORDER BY d.id, dd.subscale;"
```
Expected: the light case (2 mild symptoms, D04 + S01) and the heavy case (all 7
Depression symptoms) produce **different** severity levels, with the heavy case ranked at
or above the light one.

- [ ] **Step 5: Commit the evaluation output**

```bash
cd "C:\xampp\htdocs\ds3"
git add eval_accuracy_output.txt eval_accuracy_output_before_phase6.txt
git commit -m "docs(phase6): record before/after accuracy evaluation output"
```

---

### Task 7: Sync both repos (no push)

**Files:** none (sync only)

- [ ] **Step 1: Run the full regression once more**

```bash
cd "C:\xampp\htdocs\ds3"
php tests/test_dempster_shafer.php
php tests/test_hitung_subskala.php
```
Expected: `ALL TESTS PASSED` ×2.

- [ ] **Step 2: Sync to the Documents repo**

```powershell
$src = "C:\xampp\htdocs\ds3"
$dst = "C:\Users\lenovo\Documents\Yüksek Lisans\Mental Health\Indonesia Application\ds3"
robocopy $src $dst /E /XD ".git" /XF "*.git*" /NFL /NDL /NP
```
Expected: exit code 3 (files copied, no failures).

- [ ] **Step 3: Commit the plan file and the sync**

```bash
cd "C:\xampp\htdocs\ds3"
git add docs/superpowers/plans/2026-10-05-phase6-bpa-restructure.md
git commit -m "docs(phase6): add implementation plan"

cd "C:\Users\lenovo\Documents\Yüksek Lisans\Mental Health\Indonesia Application\ds3"
git add -A
git commit -m "feat(phase6): restructure BPA model - nested focal sets + belief-ladder decision rule

Squash-equivalent sync of the Phase 6 commits from the htdocs repo:
- ds_symptoms columns renamed to nested 'at least X' semantics
  (m_min_moderate / m_min_severe / m_extreme) with all 21 rows rewritten
- c_Diagnosa.php: buildEvidence() maps to nested focal sets; hitungSubskala()
  now decides via the DS belief function over an ordinal ladder (highest
  level with Bel >= 0.50) instead of the pignistic transform, which is
  systematically biased upward on nested sets
- c_Symptom.php, admin UI and the edit processor follow the renamed columns
- tests cover the zero-conflict property of nested sets, the belief ladder,
  monotonicity (more symptoms never lowers severity) and level diversity
- how-it-works method description rewritten in all 4 languages

Fixes the Phase 5 finding that every input collapsed to 'Moderate'.

Co-Authored-By: Claude Opus 5 <noreply@anthropic.com>"
```

**Do not push to GitHub** — the earlier push approval was one-time, not standing
permission (see `project_ds3_context.md` memory note). Ask before pushing.

---

## Self-review notes

- Spec coverage: nested focal sets (Task 2) ✓, per-symptom values (Task 1) ✓, belief-ladder
  decision rule (Task 2) ✓, tests incl. monotonicity (Task 4) ✓, admin UI (Task 3) ✓,
  `hiw_p*` rewrite (Task 5) ✓, re-verification (Task 6) ✓.
- `controller/c_Symptom.php` was **not** in the spec's scope list but is required: it reads
  and writes the renamed columns in three places. Added to Task 2 rather than discovered
  mid-execution.
- `pignistic()` is deliberately kept in `c_Diagnosa.php` and still unit-tested even though
  `hitungSubskala()` no longer calls it — it is part of the method documented in the thesis.
- Column names are consistent across every task: `m_min_moderate`, `m_min_severe`,
  `m_extreme`, `m_theta`. Method names are consistent: `beliefLadder()`, `selectLevel()`.
