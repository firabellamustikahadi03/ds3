<?php
session_start();
$_SESSION['lang'] = 'id';

require_once __DIR__ . '/../connection/connection.php';
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

function fetchIdByKode($con, $kode) {
    $kode = mysqli_real_escape_string($con, $kode);
    $res  = mysqli_query($con, "SELECT id FROM ds_symptoms WHERE symptom_code = '$kode'");
    $row  = mysqli_fetch_assoc($res);
    return $row ? (int)$row['id'] : null;
}

// Test 1: three Depression gejala selected -> a plausible D result
$ids = array_filter([fetchIdByKode($con, 'G-D01'), fetchIdByKode($con, 'G-D02'), fetchIdByKode($con, 'G-D07')]);
$r = $dg->hitungSubskala('D', $ids);
if ($r === null) {
    echo "FAIL: hitungSubskala('D', 3 gejala) returned null\n";
    $failures++;
} else {
    $validLevels = ['Mild', 'Moderate', 'Severe', 'Extreme'];
    if (in_array($r['severity_level'], $validLevels)) {
        echo "PASS: hitungSubskala('D', ...) returned a valid level ({$r['severity_level']})\n";
    } else {
        echo "FAIL: hitungSubskala('D', ...) returned invalid level_kode {$r['severity_level']}\n";
        $failures++;
    }
    if ($r['confidence_value'] >= 0 && $r['confidence_value'] <= 1) {
        echo "PASS: hitungSubskala('D', ...) nilai is within [0,1] ({$r['confidence_value']})\n";
    } else {
        echo "FAIL: hitungSubskala('D', ...) nilai out of range: {$r['confidence_value']}\n";
        $failures++;
    }
    if (!empty($r['recommendation'])) {
        echo "PASS: hitungSubskala('D', ...) returned non-empty kett\n";
    } else {
        echo "FAIL: hitungSubskala('D', ...) kett is empty\n";
        $failures++;
    }
}

// Test 2: empty gejala list -> null
$r2 = $dg->hitungSubskala('D', []);
if ($r2 === null) {
    echo "PASS: hitungSubskala('D', []) returns null\n";
} else {
    echo "FAIL: hitungSubskala('D', []) should return null, got " . print_r($r2, true) . "\n";
    $failures++;
}

// Test 3: gejala ids from the wrong subskala -> null (no matching rows)
$aIds = array_filter([fetchIdByKode($con, 'G-A01')]);
$r3 = $dg->hitungSubskala('D', $aIds);
if ($r3 === null) {
    echo "PASS: hitungSubskala('D', <Anxiety ids>) returns null\n";
} else {
    echo "FAIL: hitungSubskala('D', <Anxiety ids>) should return null, got " . print_r($r3, true) . "\n";
    $failures++;
}

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

// Test 5: MONOTONICITY - the whole point of Phase 6. Adding symptoms must never
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
// produced the same answer the engine would be useless - this is exactly the
// Phase 5 bug (everything collapsed to "Moderate") in regression-test form.
$distinctLevels = array_unique(array_map(fn($o) => explode('=', $o)[1], $observed));
if (count($distinctLevels) >= 2) {
    echo "PASS: engine produces more than one severity level across the sequence (" . implode('/', $distinctLevels) . ")\n";
} else {
    echo "FAIL: engine collapsed every input to a single level (" . implode('/', $distinctLevels) . ")\n";
    $failures++;
}

echo "\n" . ($failures === 0 ? "ALL TESTS PASSED" : "$failures TEST(S) FAILED") . "\n";
exit($failures === 0 ? 0 : 1);
