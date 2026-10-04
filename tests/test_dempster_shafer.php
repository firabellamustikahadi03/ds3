<?php
require_once __DIR__ . '/../controller/c_Diagnosa.php';

$dg = new Diagnosa();
$failures = 0;

function assertClose($actual, $expected, $label, &$failures, $tolerance = 0.0001) {
    if (abs($actual - $expected) > $tolerance) {
        echo "FAIL: $label — expected $expected, got $actual\n";
        $failures++;
    } else {
        echo "PASS: $label\n";
    }
}

// Test 1: combineMass — two nested evidence sets, no conflict
$m1 = ['2,3,4' => 0.6, '1,2,3,4' => 0.4];
$m2 = ['2,3,4' => 0.5, '1,2,3,4' => 0.5];
$combined = $dg->combineMass($m1, $m2);
assertClose($combined['2,3,4'], 0.80, 'combineMass nested {2,3,4} total', $failures);
assertClose($combined['1,2,3,4'], 0.20, 'combineMass nested theta total', $failures);
assertClose(array_sum($combined), 1.0, 'combineMass nested sums to 1', $failures);

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
assertClose($nestedCombined['4'], 0.20 + 0.20, 'nested combine {4}', $failures);
assertClose($nestedCombined['3,4'], 0.30, 'nested combine {3,4}', $failures);
assertClose($nestedCombined['2,3,4'], 0.30, 'nested combine {2,3,4}', $failures);
assertClose(array_sum($nestedCombined), 1.0, 'nested combine sums to 1', $failures);

// Test 2: combineMass — with conflict (disjoint sets). combineMass() is generic
// math and must still handle conflict correctly even though the Phase 6 focal
// sets never generate any.
$m3 = ['1,2' => 0.7, '1,2,3,4' => 0.3];
$m4 = ['3,4' => 0.6, '1,2,3,4' => 0.4];
$combined2 = $dg->combineMass($m3, $m4);
assertClose($combined2['#CONFLICT#'], 0.42, 'combineMass conflict mass', $failures);
assertClose($combined2['1,2'], 0.28, 'combineMass conflict {1,2}', $failures);
assertClose($combined2['3,4'], 0.18, 'combineMass conflict {3,4}', $failures);
assertClose($combined2['1,2,3,4'], 0.12, 'combineMass conflict theta', $failures);

// Test 3: normalizeMass removes conflict and renormalizes to sum 1
$normalized = $dg->normalizeMass($combined2);
assertClose(array_sum($normalized), 1.0, 'normalizeMass sums to 1 after removing conflict', $failures);
assertClose($normalized['1,2'], 0.28 / (1 - 0.42), 'normalizeMass {1,2} value', $failures);
if (isset($normalized['#CONFLICT#'])) {
    echo "FAIL: normalizeMass should remove the #CONFLICT# key\n";
    $failures++;
} else {
    echo "PASS: normalizeMass removes the #CONFLICT# key\n";
}

// Test 4: pignistic transform splits multi-element mass evenly and sums to 1
$mixed = ['1' => 0.5, '2,3' => 0.3, '1,2,3,4' => 0.2];
$pig = $dg->pignistic($mixed);
assertClose(array_sum($pig), 1.0, 'pignistic sums to 1', $failures);
assertClose($pig[1], 0.5 + 0.2 / 4, 'pignistic singleton 1 gets its own mass plus theta share', $failures);
assertClose($pig[2], 0.3 / 2 + 0.2 / 4, 'pignistic value for level 2', $failures);
assertClose($pig[3], 0.3 / 2 + 0.2 / 4, 'pignistic value for level 3', $failures);
assertClose($pig[4], 0.2 / 4, 'pignistic value for level 4', $failures);

// Test 4b: normalizeMass degenerate case — completely disjoint focal sets with
// no shared Theta mass produce K >= 1.0 (total conflict). Per DS theory this
// means there is no combinable belief left; normalizeMass() must return []
// rather than dividing by (1 - K) = 0.
$m5 = ['1,2' => 1.0];
$m6 = ['3,4' => 1.0];
$combined3 = $dg->combineMass($m5, $m6);
assertClose($combined3['#CONFLICT#'], 1.0, 'combineMass full conflict mass is 1.0', $failures);
$normalizedFullConflict = $dg->normalizeMass($combined3);
if ($normalizedFullConflict === []) {
    echo "PASS: normalizeMass returns [] when K >= 1.0 (full conflict, no shared Theta mass)\n";
} else {
    echo "FAIL: normalizeMass should return [] when K >= 1.0, got " . print_r($normalizedFullConflict, true) . "\n";
    $failures++;
}

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

echo "\n" . ($failures === 0 ? "ALL TESTS PASSED" : "$failures TEST(S) FAILED") . "\n";
exit($failures === 0 ? 0 : 1);
