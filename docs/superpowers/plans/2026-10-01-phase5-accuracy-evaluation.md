# Phase 5: System Accuracy Evaluation Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Produce a comparison report (official DASS-21 scoring vs ds3's Dempster-Shafer engine, across 23 systematically constructed scenarios) for the thesis's testing chapter.

**Architecture:** A single self-contained, read-only CLI script (`eval_accuracy.php`, project root) that computes the official DASS-21 category by hand (pure arithmetic, table lookup) and the ds3 category by calling the real `Diagnosa::hitungSubskala()` engine, then prints a comparison table and summary statistics. Makes **zero database writes** — this is analysis/reporting only, unlike `seed_test.php`.

**Tech Stack:** PHP 8.2 CLI, no database writes (reads `ds_symptoms`/`ds_severity_levels` via the existing engine only).

**Project testing convention:** `php -l` for syntax, then run the script itself and hand-verify a handful of its own official-classification outputs against the cutoff table (shown explicitly in Task 2), plus re-running `tests/test_dempster_shafer.php`/`tests/test_hitung_subskala.php` to confirm this script didn't require any engine changes.

---

### Task 1: Write `eval_accuracy.php`

**Files:**
- Create: `eval_accuracy.php`

The symptom-id mapping (`D` ids 1-7, `A` ids 8-14, `S` ids 15-21, in `dass_item`-ascending
order within each subscale) was re-verified against the live `ds_symptoms` table before
writing this plan:

```
D: id 1-7  <- dass_item 3,5,10,13,16,17,21
A: id 8-14 <- dass_item 2,4,7,9,15,19,20
S: id 15-21<- dass_item 1,6,8,11,12,14,18
```

This matches the same `code2id()` convention `seed_test.php` already established
(array index 0-6 within a subscale maps directly to `id = offset + index + 1`), so the
7-element scenario arrays below are already in the correct id order — no separate
dass_item lookup table is needed at runtime.

- [ ] **Step 1: Write the full script**

```php
<?php
/**
 * One-off accuracy evaluation: compares ds3's Dempster-Shafer engine against the
 * official DASS-21 scoring method (Lovibond & Lovibond 1995) on a systematically
 * constructed 23-scenario dataset. Read-only - makes zero writes to the database.
 * See docs/superpowers/specs/2026-10-01-phase5-accuracy-evaluation-design.md.
 *
 * Run: php eval_accuracy.php
 */

if (session_status() === PHP_SESSION_NONE) session_start();

require __DIR__ . '/controller/c_Diagnosa.php';

// ── Official DASS-21 cutoffs (score = 7-item raw sum x2) ───────────────────
$cutoffs = [
    'D' => ['Normal' => [0, 9], 'Mild' => [10, 13], 'Moderate' => [14, 20], 'Severe' => [21, 27], 'ExtremelySevere' => [28, PHP_INT_MAX]],
    'A' => ['Normal' => [0, 7], 'Mild' => [8, 9],  'Moderate' => [10, 14], 'Severe' => [15, 19], 'ExtremelySevere' => [20, PHP_INT_MAX]],
    'S' => ['Normal' => [0, 14],'Mild' => [15, 18],'Moderate' => [19, 25], 'Severe' => [26, 33], 'ExtremelySevere' => [34, PHP_INT_MAX]],
];
$tierOrder = ['Normal' => 0, 'Mild' => 1, 'Moderate' => 2, 'Severe' => 3, 'ExtremelySevere' => 4];
$ds3ToOfficialLabel = ['Mild' => 'Mild', 'Moderate' => 'Moderate', 'Severe' => 'Severe', 'Extreme' => 'ExtremelySevere'];

function classifyOfficial(array $itemScores, string $subscale, array $cutoffs): string
{
    $score = array_sum($itemScores) * 2;
    foreach ($cutoffs[$subscale] as $category => $range) {
        [$low, $high] = $range;
        if ($score >= $low && $score <= $high) return $category;
    }
    return 'ExtremelySevere';
}

function scenarioToSymptomIds(array $scores, int $idOffset, int $threshold): array
{
    $ids = [];
    foreach ($scores as $i => $score) {
        if ($score >= $threshold) $ids[] = $idOffset + $i + 1;
    }
    return $ids;
}

// ── 7-item raw-sum distribution patterns (base=floor(raw/7), remainder gets +1, capped at 3) ──
$P = [
    2  => [1, 1, 0, 0, 0, 0, 0],
    4  => [1, 1, 1, 1, 0, 0, 0],
    6  => [1, 1, 1, 1, 1, 1, 0],
    8  => [2, 1, 1, 1, 1, 1, 1],
    9  => [2, 2, 1, 1, 1, 1, 1],
    11 => [2, 2, 2, 2, 1, 1, 1],
    12 => [2, 2, 2, 2, 2, 1, 1],
    15 => [3, 2, 2, 2, 2, 2, 2],
    16 => [3, 3, 2, 2, 2, 2, 2],
    18 => [3, 3, 3, 3, 2, 2, 2],
];
$D_NORMAL = $P[2];
$A_NORMAL = $P[2];
$S_NORMAL = $P[4];

// ── 23 scenarios: 15 isolated (one subscale varies across its 5 official
// categories, the other two held at their own Normal baseline) + 8 mixed ──
$scenarios = [
    'D-Normal'           => ['D' => $P[2],  'A' => $A_NORMAL, 'S' => $S_NORMAL],
    'D-Mild'             => ['D' => $P[6],  'A' => $A_NORMAL, 'S' => $S_NORMAL],
    'D-Moderate'         => ['D' => $P[8],  'A' => $A_NORMAL, 'S' => $S_NORMAL],
    'D-Severe'           => ['D' => $P[12], 'A' => $A_NORMAL, 'S' => $S_NORMAL],
    'D-ExtremelySevere'  => ['D' => $P[16], 'A' => $A_NORMAL, 'S' => $S_NORMAL],
    'A-Normal'           => ['D' => $D_NORMAL, 'A' => $P[2],  'S' => $S_NORMAL],
    'A-Mild'             => ['D' => $D_NORMAL, 'A' => $P[4],  'S' => $S_NORMAL],
    'A-Moderate'         => ['D' => $D_NORMAL, 'A' => $P[6],  'S' => $S_NORMAL],
    'A-Severe'           => ['D' => $D_NORMAL, 'A' => $P[9],  'S' => $S_NORMAL],
    'A-ExtremelySevere'  => ['D' => $D_NORMAL, 'A' => $P[12], 'S' => $S_NORMAL],
    'S-Normal'           => ['D' => $D_NORMAL, 'A' => $A_NORMAL, 'S' => $P[4]],
    'S-Mild'             => ['D' => $D_NORMAL, 'A' => $A_NORMAL, 'S' => $P[9]],
    'S-Moderate'         => ['D' => $D_NORMAL, 'A' => $A_NORMAL, 'S' => $P[11]],
    'S-Severe'           => ['D' => $D_NORMAL, 'A' => $A_NORMAL, 'S' => $P[15]],
    'S-ExtremelySevere'  => ['D' => $D_NORMAL, 'A' => $A_NORMAL, 'S' => $P[18]],
    'All-Normal'          => ['D' => $P[2],  'A' => $P[2],  'S' => $P[4]],
    'All-Mild'            => ['D' => $P[6],  'A' => $P[4],  'S' => $P[9]],
    'All-Moderate'        => ['D' => $P[8],  'A' => $P[6],  'S' => $P[11]],
    'All-Severe'          => ['D' => $P[12], 'A' => $P[9],  'S' => $P[15]],
    'All-ExtremelySevere' => ['D' => $P[16], 'A' => $P[12], 'S' => $P[18]],
    'Depression-dominant' => ['D' => $P[12], 'A' => $P[4],  'S' => $P[4]],
    'Anxiety-dominant'    => ['D' => $P[6],  'A' => $P[9],  'S' => $P[11]],
    'Stress-dominant'     => ['D' => $P[2],  'A' => $P[6],  'S' => $P[18]],
];

$idOffset = ['D' => 0, 'A' => 7, 'S' => 14];
$dg = new Diagnosa;

$rows = [];
$stats = [
    1 => ['exact' => 0, 'comparable' => 0, 'offByN' => []],
    2 => ['exact' => 0, 'comparable' => 0, 'offByN' => []],
];
$normalExcludedCount = 0;

foreach ($scenarios as $label => $subscaleScores) {
    foreach (['D', 'A', 'S'] as $sk) {
        $scores = $subscaleScores[$sk];
        $official = classifyOfficial($scores, $sk, $cutoffs);
        $isNormal = ($official === 'Normal');
        if ($isNormal) $normalExcludedCount++;

        $row = ['scenario' => $label, 'subscale' => $sk, 'official' => $official];

        foreach ([1, 2] as $threshold) {
            $symptomIds = scenarioToSymptomIds($scores, $idOffset[$sk], $threshold);
            $result = empty($symptomIds) ? null : $dg->hitungSubskala($sk, $symptomIds);
            $ds3Level = $result ? $result['severity_level'] : null;
            $row["ds3_t{$threshold}"] = $ds3Level ?? '(no symptoms)';

            if (!$isNormal && $ds3Level !== null) {
                $stats[$threshold]['comparable']++;
                $officialAsDs3Label = $ds3ToOfficialLabel[$ds3Level] ?? $ds3Level;
                if ($officialAsDs3Label === $official) {
                    $offBy = 0;
                    $stats[$threshold]['exact']++;
                } else {
                    $ds3TierNumeric = array_search($ds3Level, ['Mild', 'Moderate', 'Severe', 'Extreme']) + 1;
                    $offBy = abs($tierOrder[$official] - $ds3TierNumeric);
                }
                $stats[$threshold]['offByN'][$offBy] = ($stats[$threshold]['offByN'][$offBy] ?? 0) + 1;
            }
        }
        $rows[] = $row;
    }
}

// ── Report ───────────────────────────────────────────────────────────────
echo str_pad('Scenario', 22) . str_pad('Sub', 5) . str_pad('Official', 18) . str_pad('ds3 @>=1', 12) . str_pad('ds3 @>=2', 12) . "\n";
echo str_repeat('-', 70) . "\n";
foreach ($rows as $r) {
    echo str_pad($r['scenario'], 22) . str_pad($r['subscale'], 5) . str_pad($r['official'], 18) . str_pad($r['ds3_t1'], 12) . str_pad($r['ds3_t2'], 12) . "\n";
}

echo "\n" . str_repeat('=', 70) . "\n";
echo "RINGKASAN\n";
echo str_repeat('=', 70) . "\n";
echo "Baris dengan hasil resmi 'Normal' (tidak dibandingkan, dicatat terpisah): $normalExcludedCount\n\n";
foreach ([1, 2] as $threshold) {
    $s = $stats[$threshold];
    $pct = $s['comparable'] > 0 ? round(($s['exact'] / $s['comparable']) * 100, 1) : 0;
    echo "Threshold >= $threshold:\n";
    echo "  Cocok persis: {$s['exact']} / {$s['comparable']} ({$pct}%)\n";
    echo "  Distribusi selisih tingkat (off-by-N):\n";
    ksort($s['offByN']);
    foreach ($s['offByN'] as $n => $count) {
        echo "    Selisih $n tingkat: $count baris\n";
    }
    echo "\n";
}
```

- [ ] **Step 2: Syntax-check**

Run:
```bash
cd "C:\xampp\htdocs\ds3"
php -l eval_accuracy.php
```
Expected: `No syntax errors detected`.

- [ ] **Step 3: Commit**

```bash
cd "C:\xampp\htdocs\ds3"
git add eval_accuracy.php
git commit -m "feat(phase5): add eval_accuracy.php - one-off comparison of ds3 vs official DASS-21 scoring"
```

---

### Task 2: Run it, hand-verify, regression-check

**Files:** none (verification only)

- [ ] **Step 1: Run the script**

Run:
```bash
cd "C:\xampp\htdocs\ds3"
php eval_accuracy.php
```
Expected: a table of 69 rows (23 scenarios × 3 subscales) followed by a summary
section. No PHP warnings/errors in the output.

- [ ] **Step 2: Hand-verify 3 official classifications against the cutoff table**

These three are worked out by hand below - confirm the script's `Official` column
matches exactly:

- `D-Normal`, subscale D: raw sum = 1+1+0+0+0+0+0 = 2, score = 2×2 = 4. Depression
  Normal range is 0-9 → expect **Normal**.
- `D-ExtremelySevere`, subscale D: raw sum = 3+3+2+2+2+2+2 = 16, score = 16×2 = 32.
  Depression ExtremelySevere is 28+ → expect **ExtremelySevere**.
- `All-Severe`, subscale A: raw sum = 2+2+1+1+1+1+1 = 9, score = 9×2 = 18. Anxiety
  Severe range is 15-19 → expect **Severe**.

- [ ] **Step 3: Confirm the null-symptom edge case is handled, not crashing**

At threshold ≥2, several "Normal"/"Mild" scenarios have no item scoring 2 or 3 at all
(e.g. `D-Normal`'s D pattern `[1,1,0,0,0,0,0]` has no value ≥2), so ds3 receives zero
checked symptoms for that subscale. Confirm the script's `ds3 @>=2` column prints
`(no symptoms)` for these rows rather than a PHP error or a blank crash - this is an
expected, reportable finding (the stricter threshold under-triggers ds3 on
low-severity scenarios), not a bug to fix.

- [ ] **Step 4: Re-run the DS engine regression tests**

Run:
```bash
cd "C:\xampp\htdocs\ds3"
php tests/test_dempster_shafer.php
php tests/test_hitung_subskala.php
```
Expected: `ALL TESTS PASSED` ×2 (this script only calls the existing engine through its
public method, never modifies it).

- [ ] **Step 5: Save the full output to a file for the thesis**

Run:
```bash
cd "C:\xampp\htdocs\ds3"
php eval_accuracy.php > eval_accuracy_output.txt
cat eval_accuracy_output.txt
```
Report the full contents back to the user in the conversation (not just the file
path) so they can copy it directly into their thesis draft without needing to open
the file themselves.

---

### Task 3: Sync (no push)

**Files:** none (sync only)

- [ ] **Step 1: Sync to the Documents repo**

Run (PowerShell):
```powershell
$src = "C:\xampp\htdocs\ds3"
$dst = "C:\Users\lenovo\Documents\Yüksek Lisans\Mental Health\Indonesia Application\ds3"
robocopy $src $dst /E /XD ".git" /XF "*.git*" /NFL /NDL /NP
```
Expected: exit code 3 (files copied, no failures).

- [ ] **Step 2: Commit the plan file and sync commit in the Documents repo**

```bash
cd "C:\xampp\htdocs\ds3"
git add docs/superpowers/plans/2026-10-01-phase5-accuracy-evaluation.md eval_accuracy_output.txt
git commit -m "docs(phase5): add implementation plan + save evaluation output"

cd "C:\Users\lenovo\Documents\Yüksek Lisans\Mental Health\Indonesia Application\ds3"
git add -A
git commit -m "feat(phase5): system accuracy evaluation vs official DASS-21 scoring

Squash-equivalent sync of the Phase 5 commits from the htdocs repo:
- eval_accuracy.php - one-off, read-only CLI script comparing ds3's
  Dempster-Shafer engine against official DASS-21 scoring (Lovibond &
  Lovibond 1995) across 23 systematically constructed scenarios, at two
  Likert-to-checkbox conversion thresholds (>=1, >=2)
- eval_accuracy_output.txt - the resulting comparison table + summary
  statistics, ready to paste into the thesis testing chapter

Co-Authored-By: Claude Sonnet 5 <noreply@anthropic.com>"
```

**Do not push to GitHub** — the user's prior push approval was one-time, not standing
permission (see `project_ds3_context.md` memory note). Ask again if a push is wanted.

---

## Self-review notes (for whoever executes this plan)

- Spec coverage: official cutoff table ✓, Likert→checkbox conversion at both
  thresholds ✓, 23-scenario systematic construction ✓, Normal-exclusion-but-reported
  handling ✓, exact-match + off-by-N statistics ✓, no database writes ✓, real
  `hitungSubskala()` reused rather than reimplemented ✓.
- The `dass_item`→`id` mapping was re-verified against the live `ds_symptoms` table
  before writing this plan (shown in Task 1's intro), not assumed from memory.
- `classifyOfficial()`'s PHP 7.1+ list-assignment `[$low, $high] = $range;` matches
  this project's confirmed PHP 8.2 runtime.
