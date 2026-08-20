<?php
session_start();
$_SESSION['lang'] = 'id';

require_once __DIR__ . '/../koneksi/koneksi.php';
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
    $res  = mysqli_query($con, "SELECT id FROM ds_gejala WHERE kode_gejala = '$kode'");
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
    $validLevels = ['H', 'O', 'A', 'CA'];
    if (in_array($r['level_kode'], $validLevels)) {
        echo "PASS: hitungSubskala('D', ...) returned a valid level ({$r['level_kode']})\n";
    } else {
        echo "FAIL: hitungSubskala('D', ...) returned invalid level_kode {$r['level_kode']}\n";
        $failures++;
    }
    if ($r['nilai'] >= 0 && $r['nilai'] <= 1) {
        echo "PASS: hitungSubskala('D', ...) nilai is within [0,1] ({$r['nilai']})\n";
    } else {
        echo "FAIL: hitungSubskala('D', ...) nilai out of range: {$r['nilai']}\n";
        $failures++;
    }
    if (!empty($r['kett'])) {
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

// Test 4: a single gejala's own evidence, never combined with anything else,
// forces a multi-element top focal set -> hitungSubskala() must take the
// pignistic() branch, not the singleton shortcut. Test 1's 3-gejala
// combination happens to land on a singleton and never exercises that branch,
// so this test exists specifically to cover it.
$d01Id = fetchIdByKode($con, 'G-D01');
$rowRes = mysqli_query($con, "SELECT m_ho, m_oa, m_aca, m_theta FROM ds_gejala WHERE id = " . (int)$d01Id);
$massRow = mysqli_fetch_assoc($rowRes);
$evidence = $dg->buildEvidence($massRow['m_ho'], $massRow['m_oa'], $massRow['m_aca'], $massRow['m_theta']);

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
    $validLevels = ['H', 'O', 'A', 'CA'];
    if (in_array($r4['level_kode'], $validLevels)) {
        echo "PASS: hitungSubskala('D', [G-D01]) returned a valid level ({$r4['level_kode']})\n";
    } else {
        echo "FAIL: hitungSubskala('D', [G-D01]) returned invalid level_kode {$r4['level_kode']}\n";
        $failures++;
    }
    if ($r4['nilai'] >= 0 && $r4['nilai'] <= 1) {
        echo "PASS: hitungSubskala('D', [G-D01]) nilai is within [0,1] ({$r4['nilai']})\n";
    } else {
        echo "FAIL: hitungSubskala('D', [G-D01]) nilai out of range: {$r4['nilai']}\n";
        $failures++;
    }
    if (!empty($r4['kett'])) {
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
    $levelMap = [1 => 'H', 2 => 'O', 3 => 'A', 4 => 'CA'];
    assertClose($r4['nilai'], $expectedNilai, 'hitungSubskala(D, [G-D01]) nilai matches direct pignistic() computation', $failures);
    if ($r4['level_kode'] === $levelMap[$expectedLevelInt]) {
        echo "PASS: hitungSubskala('D', [G-D01]) level_kode matches direct pignistic() computation ({$r4['level_kode']})\n";
    } else {
        echo "FAIL: hitungSubskala('D', [G-D01]) level_kode {$r4['level_kode']} does not match expected {$levelMap[$expectedLevelInt]}\n";
        $failures++;
    }
}

echo "\n" . ($failures === 0 ? "ALL TESTS PASSED" : "$failures TEST(S) FAILED") . "\n";
exit($failures === 0 ? 0 : 1);
