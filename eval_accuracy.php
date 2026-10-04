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

// ── Why rotations? ───────────────────────────────────────────────────────
// The official DASS-21 score only depends on the SUM of a subscale's item scores,
// so it is identical wherever those scores sit. ds3, however, weights each symptom
// differently (Ringan/Sedang/Berat/Sangat Berat). A fixed pattern such as [3,3,2,...]
// would always put the high scores on the first items, silently confounding "how
// severe is the scenario" with "which items happen to be first". Running every
// scenario under all 7 cyclic rotations holds the official result constant and
// lets only the item identity vary, so the spread across rotations measures that
// effect directly instead of hiding it.
function rotateScores(array $scores, int $k): array
{
    $n = count($scores);
    $out = [];
    for ($i = 0; $i < $n; $i++) $out[($i + $k) % $n] = $scores[$i];
    ksort($out);
    return array_values($out);
}

$ds3Rank   = ['Mild' => 1, 'Moderate' => 2, 'Severe' => 3, 'Extreme' => 4];
$ROTATIONS = 7;

$rows = [];
$stats = [];
foreach ([1, 2] as $t) {
    $stats[$t] = ['exact' => 0, 'comparable' => 0, 'noSymptoms' => 0, 'signed' => []];
}
$normalExcludedCount = 0;

foreach ($scenarios as $label => $subscaleScores) {
    foreach (['D', 'A', 'S'] as $sk) {
        $official = classifyOfficial($subscaleScores[$sk], $sk, $cutoffs);
        $isNormal = ($official === 'Normal');
        if ($isNormal) $normalExcludedCount++;

        $row = ['scenario' => $label, 'subscale' => $sk, 'official' => $official];

        foreach ([1, 2] as $threshold) {
            $tally = [];
            $noSymptoms = 0;

            for ($k = 0; $k < $ROTATIONS; $k++) {
                $scores = rotateScores($subscaleScores[$sk], $k);
                $symptomIds = scenarioToSymptomIds($scores, $idOffset[$sk], $threshold);
                $result = empty($symptomIds) ? null : $dg->hitungSubskala($sk, $symptomIds);

                if ($result === null) { $noSymptoms++; continue; }
                $level = $result['severity_level'];
                $tally[$level] = ($tally[$level] ?? 0) + 1;

                if (!$isNormal) {
                    $stats[$threshold]['comparable']++;
                    $signed = $ds3Rank[$level] - $tierOrder[$official];
                    if ($signed === 0) $stats[$threshold]['exact']++;
                    $stats[$threshold]['signed'][$signed] = ($stats[$threshold]['signed'][$signed] ?? 0) + 1;
                }
            }
            if (!$isNormal) $stats[$threshold]['noSymptoms'] += $noSymptoms;

            $parts = [];
            foreach (['Mild', 'Moderate', 'Severe', 'Extreme'] as $lv) {
                if (isset($tally[$lv])) $parts[] = substr($lv, 0, 3) . 'x' . $tally[$lv];
            }
            if ($noSymptoms) $parts[] = 'nolx' . $noSymptoms;
            $row["t{$threshold}"] = implode(' ', $parts);
        }
        $rows[] = $row;
    }
}

// ── Report ───────────────────────────────────────────────────────────────
echo "Setiap sel = sebaran hasil ds3 di 7 rotasi posisi skor (Mil/Mod/Sev/Ext x jumlah; nolx = tidak ada gejala).\n\n";
echo str_pad('Scenario', 22) . str_pad('Sub', 5) . str_pad('Official', 17) . str_pad('ds3 @>=1', 26) . 'ds3 @>=2' . "\n";
echo str_repeat('-', 95) . "\n";
foreach ($rows as $r) {
    echo str_pad($r['scenario'], 22) . str_pad($r['subscale'], 5) . str_pad($r['official'], 17)
       . str_pad($r['t1'], 26) . $r['t2'] . "\n";
}

echo "\n" . str_repeat('=', 95) . "\n";
echo "RINGKASAN (7 rotasi x 23 skenario x 3 subskala)\n";
echo str_repeat('=', 95) . "\n";
echo "Subskala-skenario dengan hasil resmi 'Normal' (dikecualikan, ds3 tak punya kategori ini): $normalExcludedCount\n\n";
foreach ([1, 2] as $threshold) {
    $s = $stats[$threshold];
    $pct = $s['comparable'] > 0 ? round(($s['exact'] / $s['comparable']) * 100, 1) : 0;
    ksort($s['signed']);
    $over = $under = 0;
    foreach ($s['signed'] as $d => $c) { if ($d > 0) $over += $c; if ($d < 0) $under += $c; }
    echo "Threshold >= $threshold:\n";
    echo "  Bisa dibandingkan : {$s['comparable']} pengamatan  (+ {$s['noSymptoms']} tanpa gejala sama sekali, tidak dihitung)\n";
    echo "  Cocok persis      : {$s['exact']} ({$pct}%)\n";
    if ($s['comparable'] > 0) {
        echo "  ds3 LEBIH TINGGI dari resmi : $over (" . round($over / $s['comparable'] * 100, 1) . "%)\n";
        echo "  ds3 LEBIH RENDAH dari resmi : $under (" . round($under / $s['comparable'] * 100, 1) . "%)\n";
    }
    echo "  Selisih bertanda (ds3 - resmi, + = ds3 lebih berat):\n";
    foreach ($s['signed'] as $d => $c) {
        echo "    " . str_pad(($d > 0 ? '+' : '') . $d, 4) . ": $c\n";
    }
    echo "\n";
}
