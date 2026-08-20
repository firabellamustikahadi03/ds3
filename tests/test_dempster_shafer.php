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

// Test 1: combineMass — two evidence sets with full overlap, no conflict
$m1 = ['1,2' => 0.6, '1,2,3,4' => 0.4];
$m2 = ['1,2' => 0.5, '1,2,3,4' => 0.5];
$combined = $dg->combineMass($m1, $m2);
assertClose($combined['1,2'], 0.80, 'combineMass no-conflict {1,2} total', $failures);
assertClose($combined['1,2,3,4'], 0.20, 'combineMass no-conflict theta total', $failures);
assertClose(array_sum($combined), 1.0, 'combineMass no-conflict sums to 1', $failures);

// Test 2: combineMass — with conflict (disjoint sets)
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

// Test 5: buildEvidence skips zero-mass entries and sums to 1
$ev = $dg->buildEvidence(0.35, 0.30, 0, 0.35);
if (isset($ev['3,4'])) {
    echo "FAIL: buildEvidence should omit zero-mass m_aca\n";
    $failures++;
} else {
    echo "PASS: buildEvidence omits zero-mass m_aca\n";
}
assertClose(array_sum($ev), 1.0, 'buildEvidence sums to 1', $failures);

echo "\n" . ($failures === 0 ? "ALL TESTS PASSED" : "$failures TEST(S) FAILED") . "\n";
exit($failures === 0 ? 0 : 1);
