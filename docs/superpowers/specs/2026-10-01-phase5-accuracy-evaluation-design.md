# Phase 5: System Accuracy Evaluation Design

## Goal

Produce a defensible comparison table + summary statistics for the thesis's testing
chapter, measuring how closely the ds3 Dempster-Shafer engine's classifications agree
with the official DASS-21 scoring method (Lovibond & Lovibond, 1995), using a
systematically constructed scenario dataset. This is a **one-off analysis script**, not
a permanent application feature — no new UI, no database writes.

## Background: the instrument mismatch (must be stated plainly in the thesis, not hidden)

The official DASS-21 instrument asks respondents to rate each of 21 items on a 0-3
Likert scale (0 = did not apply, 3 = applied very much), sums each 7-item subscale,
multiplies by 2 (to equate to the full DASS-42 scale), and classifies the result into
one of 5 severity categories per subscale: **Normal, Mild, Moderate, Severe, Extremely
Severe**.

ds3's engine (`controller/c_Diagnosa.php::hitungSubskala()`) is **binary/checkbox
based** — a visitor checks which of the 21 symptoms they experience (present/absent,
no intensity rating) — and produces one of only **4** severity levels per subscale:
**Mild, Moderate, Severe, Extreme**. There is no "Normal" outcome in ds3's model at
all; this has been confirmed repeatedly across this project's prior sessions and is not
something this evaluation tries to paper over.

Because of this structural mismatch, a naive "accuracy = % exact category match"
number would be misleading on its own. This design's comparison methodology (below)
is built to be honest about that limitation rather than hide it.

## Methodology

### 1. Official DASS-21 severity cutoffs

Per subscale, raw 7-item sum × 2, categorized as:

| Category | Depression | Anxiety | Stress |
|---|---|---|---|
| Normal | 0–9 | 0–7 | 0–14 |
| Mild | 10–13 | 8–9 | 15–18 |
| Moderate | 14–20 | 10–14 | 19–25 |
| Severe | 21–27 | 15–19 | 26–33 |
| Extremely Severe | 28+ | 20+ | 34+ |

These are the widely-published standard DASS-21 cutoffs. **The user must verify these
against their own official DASS-21 manual reference before citing them in the thesis**
— they are reproduced here from general knowledge, not re-derived from a primary source
this session can access.

### 2. Likert-to-checkbox conversion rule

Each scenario is authored as 21 raw item scores (0-3, one per DASS-21 item, keyed by
`dass_item` 1-21 to match `ds_symptoms.dass_item`). To feed a scenario into ds3's
binary engine, each item's score is converted to present/absent:

- **Primary rule: score ≥ 1 → present** (checked). Rationale: ds3's checkbox only asks
  "do you experience this," and even "applied to some degree" (score 1) means the
  respondent does experience the symptom to some extent.
- **Secondary rule (sensitivity check): score ≥ 2 → present.** Run the exact same
  scenario set through this stricter threshold too, to show whether the comparison
  result is sensitive to this methodological choice or holds up either way. Both
  threshold's results are reported side by side, not just one.

### 3. Scenario set

~20-25 systematically constructed scenarios (not real patient data, not random):

- For each subscale (D, A, S) independently: scenarios whose item scores are
  back-calculated to land near the midpoint of each of the 5 official categories
  (Normal, Mild, Moderate, Severe, Extremely Severe), holding the other two subscales
  at a low/Normal baseline. This gives full-range, deliberate coverage per subscale
  (5 categories × 3 subscales = 15 scenarios).
- Plus ~5-10 "mixed/comorbid" scenarios where all three subscales vary together
  (closer to a realistic presentation than one subscale moving in isolation).

Every scenario is fully specified as 21 explicit item scores — reproducible, and
presentable in the thesis as an appendix table of exactly what was tested.

### 4. Comparison / accuracy metric

For each scenario and each subscale:
1. Compute the **official category** from the raw item scores (table above).
2. Convert item scores to checkbox selections (both threshold rules) and run the
   **real** `hitungSubskala()` engine (via the real `controller/c_Diagnosa.php` class,
   not reimplemented) to get ds3's category.
3. If official category is **Normal**: excluded from the accuracy calculation, but
   counted and reported separately (e.g. "N scenario-subscales landed Normal
   officially — ds3 has no equivalent category, structural limitation").
4. Over the remaining (non-Normal) scenario-subscales: report
   - **Exact match rate** (official category label == ds3 category label; "Extremely
     Severe" treated as equivalent to ds3's "Extreme").
   - **Off-by-N distribution** (how many are exact, off by one tier, off by two tiers,
     etc.) — gives a fuller picture if exact-match looks low, since a near-miss is a
     very different finding from a wild miss.

### 5. Implementation

New file: `eval_accuracy.php` (project root, CLI-run like `seed_test.php`, but
**writes nothing to the database** — pure computation and report).

Structure:
- Hardcoded `$officialCutoffs` table (section 1 above).
- Hardcoded `$scenarios` array: each entry has a label + 21 item scores
  (`dass_item => score`).
- `classifyOfficial($subscaleScoreSum, $subscale)` — pure function, table lookup.
- For each scenario × each threshold rule (≥1, ≥2): convert scores to a
  `$symptomIds` array (via `dass_item` → `ds_symptoms.id` lookup, same mapping
  `seed_test.php` already established: D01-07=ids 1-7, A01-07=8-14, S01-07=15-21),
  call the real `Diagnosa::hitungSubskala()` per subscale.
- Print a full comparison table (scenario, subscale, official category, ds3 category
  @≥1, ds3 category @≥2, match/mismatch, off-by-N) plus summary statistics at the end
  (exact match rate, off-by-N distribution, Normal-excluded count) — same
  terminal-table-output style as `seed_test.php`, CLI-friendly for copy-paste into the
  thesis.

## Testing

`php -l eval_accuracy.php` for syntax. Spot-check a handful of scenarios' official
category by hand against the cutoff table. Confirm `tests/test_dempster_shafer.php` /
`tests/test_hitung_subskala.php` still pass (this script only *calls* the existing
engine, never modifies it).
