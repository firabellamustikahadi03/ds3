# Phase 6: BPA Restructure — Nested Focal Sets + Cumulative Belief Decision Rule

## Goal

Make the Dempster-Shafer engine actually discriminate severity levels. Today it
classifies essentially every input as "Moderate" regardless of which or how many
symptoms are selected. This phase replaces the focal-set semantics, the per-symptom BPA
values, and the final decision rule so that (a) more/heavier symptoms escalate the
result, and (b) all four levels are reachable.

## Problems found (both verified empirically, not assumed)

**Problem 1 — mass piles up on "Moderate".** Every one of the 21 symptoms has its two
largest masses on `m_mild_moderate` ({Mild,Moderate}) and `m_moderate_severe`
({Moderate,Severe}). Those two focal sets intersect exactly at {Moderate}, and
Dempster's rule combines through intersection, so belief concentrates there as evidence
accumulates. `eval_accuracy.php` confirmed this: 69 of 69 scenario-subscales returned
"Moderate", including the maximum-severity scenario with all 7 symptoms selected.

**Problem 2 — the structure cannot escalate at all.** Dempster's rule narrows toward
intersections; it never moves belief *upward* to a more severe level. With adjacent-pair
focal sets, combining two symptoms that both indicate {Mild,Moderate} yields
{Mild,Moderate} with higher mass — more confidence in the same band, never a push toward
Severe. The intent "more symptoms → higher severity" is therefore mathematically
unreachable under the current design, independent of what numbers are used.

## Design

### 1. Focal sets become nested ("at least X" semantics)

| Column (new name) | Focal set | Meaning |
|---|---|---|
| `m_theta` | {Mild, Moderate, Severe, Extreme} | Symptom present, level not indicated |
| `m_min_moderate` | {Moderate, Severe, Extreme} | At least Moderate |
| `m_min_severe` | {Severe, Extreme} | At least Severe |
| `m_extreme` | {Extreme} | Extreme |

Because every focal set is a subset of the ones above it, **any two focal sets intersect
in a non-empty set**. Conflict mass (`k`) is therefore structurally always zero, and
Dempster's normalisation step never distorts the result — the division-by-(1−k)
instability that commonly affects DS applications cannot arise here.

### 2. Per-severity BPA values

| Severity category | `m_min_moderate` | `m_min_severe` | `m_extreme` | `m_theta` | Total |
|---|---|---|---|---|---|
| Ringan (mild indicator) | 0.20 | 0.05 | 0.00 | 0.75 | 1.00 |
| Sedang (moderate indicator) | 0.45 | 0.15 | 0.00 | 0.40 | 1.00 |
| Berat (severe indicator) | 0.25 | 0.50 | 0.05 | 0.20 | 1.00 |
| Sangat Berat (extreme indicator) | 0.10 | 0.35 | 0.40 | 0.15 | 1.00 |

Rationale: mild indicators keep most mass on Θ (0.75) so a single mild symptom does not
force the level up, but retain 0.20 on "at least Moderate" so that *many* mild symptoms
still accumulate — mirroring DASS-21's own additive logic. Extreme indicators carry
direct mass on {Extreme} so one clinically critical symptom (e.g. hopelessness) moves
the result meaningfully by itself. Θ is never zero: the model always reserves room for
"not yet certain", which is the core premise of evidence theory.

### 3. Per-symptom severity assignment

Assigned from the clinical connotation of each item's own wording, referencing DSM-5
criteria and the DASS manual's published facet descriptions for each subscale.

| Code | Symptom (as displayed in ds3) | Category | Basis |
|---|---|---|---|
| D01 | Tidak dapat merasakan perasaan positif sama sekali | Berat | Anhedonia — DSM-5 core criterion A2 |
| D02 | Merasa tidak ada hal yang dapat ditunggu | Sedang | Loss of anticipation — early hopelessness |
| D03 | Merasa hidup tidak berarti atau hampa | Berat | Devaluation of life — DASS Depression facet |
| D04 | Sulit untuk bersemangat | Ringan | Inertia — also common in ordinary fatigue |
| D05 | Merasa tidak berharga sebagai manusia | Berat | Self-deprecation — DSM-5 criterion A7 |
| D06 | Putus asa, tidak ada yang membuat lebih baik | Sangat Berat | Hopelessness — strongest psychological predictor of suicidal behaviour |
| D07 | Hidup terasa tidak bernilai atau tanpa tujuan | Sangat Berat | Closest item to passive suicidal ideation |
| A01 | Mulut terasa kering | Ringan | Autonomic arousal, least specific |
| A02 | Kesulitan bernapas | Sedang | Respiratory symptom — panic feature |
| A03 | Tangan gemetar | Ringan | Skeletal muscle effect, non-specific |
| A04 | Khawatir akan panik & mempermalukan diri | Sedang | Anticipatory anxiety — established pattern |
| A05 | Jantung berdebar tanpa sebab fisik | Sedang | Autonomic hyperarousal — panic feature |
| A06 | Takut tanpa alasan jelas | Berat | Free-floating anxiety |
| A07 | Hampir panik atau akan pingsan | Sangat Berat | Describes an active panic episode |
| S01 | Mudah marah hal sepele | Ringan | Mild irritability, very common |
| S02 | Bereaksi berlebihan terhadap situasi | Sedang | Over-reactive — DASS Stress facet |
| S03 | Sulit tenang atau rileks | Sedang | Difficulty relaxing — DASS Stress facet |
| S04 | **Sangat** mudah tersinggung | Berat | Intensifier "sangat" places it above S07 |
| S05 | Banyak energi habis karena kecemasan | Berat | Sustained nervous arousal to depletion |
| S06 | Tidak sabar saat ada penundaan | Ringan | Most common, frequent in healthy people |
| S07 | Mudah tersinggung secara umum | Sedang | General irritability |

Two judgements to flag explicitly for expert review: the Stress subscale contains no
"Sangat Berat" item (defensible — DASS Stress measures chronic nonspecific arousal and
has no item comparable in gravity to suicidal ideation), and S04 vs S07 are separated
only by the intensifier "sangat".

**Provenance for the thesis:** these values are to be described as *determined by the
researcher from a review of DSM-5 criteria and DASS subscale facets, subsequently
validated by an expert*. They must not be presented as having been authored by a
psychologist unless and until a named expert has reviewed them.

### 4. Decision rule: cumulative belief ladder

The current code picks a winner via pignistic transformation (splitting each focal set's
mass equally among its members). With nested sets that is systematically biased upward:
"Severe" draws a share from three focal sets while "Mild" draws only from Θ, so Mild
becomes practically unreachable. Testing confirmed this — a single mild symptom produced
"Severe".

Replace it with a cumulative belief ladder:

```
Bel(at least X) = sum of mass over every focal set whose minimum element is >= X
```

This is not an ad-hoc formula: it is the standard Dempster-Shafer belief function
`Bel(A) = Σ m(B) for all B ⊆ A`, evaluated at `A = {X, …, Extreme}`. A focal set is a
subset of "at least X" precisely when all of its elements are ≥ X, i.e. when its minimum
element is ≥ X. The decision rule is therefore a direct reading of the belief function
over an ordinal hypothesis ladder, which is defensible in the thesis as standard
evidence-theory practice rather than a custom heuristic.

Select the **highest** level whose belief is at least **0.50**; report that belief as the
confidence percentage. `Bel(at least Mild)` is always 1.0 (every focal set includes
Mild), so a result always exists.

This also improves the number shown to patients: "88.8% confident the level is at least
Severe" is interpretable, where the old pignistic share was not.

### 5. Verification evidence

The design was tested before approval across 14 constructed cases. All produced a total
mass of exactly 1.000000 (no value exceeded 1). Behaviour under the new decision rule:

| Case | Old rule | New rule |
|---|---|---|
| 1 mild symptom | Severe (27.9%) | **Mild** |
| 3 mild | Severe | **Moderate** (57.8%) |
| 7 mild | Severe | **Moderate** (86.7%) |
| 1 moderate | Severe | **Moderate** (60.0%) |
| 5 moderate | Severe | **Severe** (55.6%) |
| 1 severe | Extreme | **Severe** (55.0%) |
| 4 severe | Extreme | **Severe** (95.9%) |
| 1 extreme-indicator | Extreme | **Severe** (75.0%) |
| 2 extreme-indicators | Extreme | **Extreme** (64.0%) |
| realistic mix, 7 symptoms | Extreme | **Severe** (88.8%) |
| 7 heavy symptoms | Extreme | **Extreme** (70.7%) |

Escalation is monotonic and every level is reachable. One result cross-validates against
the official instrument: seven mild symptoms yields Moderate, and scoring the same
pattern by the official DASS-21 method (7 items × 1 = 7, ×2 = 14) also yields Moderate
for Depression (range 14–20).

## Scope of change

1. **Migration** — rename `m_mild_moderate`→`m_min_moderate`, `m_moderate_severe`→
   `m_min_severe`, `m_severe_extreme`→`m_extreme` (`m_theta` unchanged), then write the
   new values for all 21 rows.
2. **`controller/c_Diagnosa.php`** — `buildEvidence()` maps to the nested sets
   (`'2,3,4'`, `'3,4'`, `'4'`, `'1,2,3,4'`); `hitungSubskala()` replaces the
   pignistic/argmax step with the belief ladder. `combineMass()` and `normalizeMass()`
   are unchanged. `pignistic()` is retained (still unit-tested, no longer used by
   `hitungSubskala()`).
3. **Tests** — `tests/test_dempster_shafer.php` and `tests/test_hitung_subskala.php`
   updated for the new focal sets and decision rule; add a monotonicity test asserting
   that adding symptoms never lowers the resulting level.
4. **Admin UI** — column headers and edit-form fields in `admin/symptoms.php`,
   `admin/edit_symptom.php`, `process/edit_symptom.php`, `admin/how_it_works.php` follow
   the renamed columns. The per-field `[0,1]` bounds and sum-to-1.00 validation in
   `process/edit_symptom.php` stay as-is (still correct).
5. **Method explanation text** — `hiw_p1`/`hiw_p2`/`hiw_p3` in all four language files
   currently describe the adjacent-pair approach and must be rewritten to describe the
   nested "at least" approach and the belief-ladder decision rule.
6. **Re-verification** — re-run `eval_accuracy.php` to confirm the "always Moderate"
   bias is gone and agreement with official DASS-21 scoring improves.

## Out of scope

Existing historical rows in `diagnoses`/`diagnosis_history`/`diagnosis_details` keep the
severity labels they were recorded with. They are snapshots of what the system concluded
at the time and are not recomputed; the Phase 3 live-retranslation work already handles
their display correctly.

## Testing

`php -l` per file; both engine test suites green; the 14-case verification above
re-runnable; `eval_accuracy.php` before/after comparison; a live walkthrough of the
public diagnosis flow confirming results differ sensibly across light vs heavy symptom
selections.
