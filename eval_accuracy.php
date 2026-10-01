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
