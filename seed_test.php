<?php
/**
 * Seed 30 Dempster-Shafer test scenarios (S01-S30) into diagnoses/diagnosis_details.
 * Runs hitungSubskala() from the real engine (controller/c_Diagnosa.php) for each
 * subscale of each scenario, then persists the results using the exact same insert
 * shape result.php uses, so the seeded rows are indistinguishable from real diagnoses
 * except for the '[SEED ...]' tag prepended to `summary` (used for cleanup/filtering).
 *
 * Run: php seed_test.php [--lang=id|en|tr|zh] [--force]   (CLI)
 *   or open http://localhost/ds3/seed_test.php?lang=tr&force=1   (browser)
 * --lang controls which language the FROZEN historical text (symptom names,
 * severity labels) is captured in - just like a real diagnosis captures
 * whatever language was active in the browser at the time. Default: id.
 * Re-run guard is per-language (separate '[SEED-XX ...]' tag), so seeding
 * multiple languages side by side doesn't collide - only re-running the
 * SAME language without --force is blocked.
 */

if (session_status() === PHP_SESSION_NONE) session_start();

require __DIR__ . '/connection/connection.php';
require __DIR__ . '/controller/c_Diagnosa.php';

$isCli = (php_sapi_name() === 'cli');
$force = $isCli ? in_array('--force', $argv ?? []) : isset($_GET['force']);

$validLangs = ['id', 'en', 'tr', 'zh'];
$rawLang = 'id';
if ($isCli) {
    foreach ($argv as $a) {
        if (str_starts_with($a, '--lang=')) $rawLang = substr($a, 7);
    }
} else {
    $rawLang = $_GET['lang'] ?? 'id';
}
$lang = in_array($rawLang, $validLangs) ? $rawLang : 'id';
$_SESSION['lang'] = $lang;
$langArr = require __DIR__ . "/lang/{$lang}.php";
$nameCol = 'name_' . $lang;
$seedTag = '[SEED-' . strtoupper($lang) . ' ';

function out($line, $isCli)
{
    echo $isCli ? ($line . PHP_EOL) : (htmlspecialchars($line) . "<br>\n");
}

if (!$isCli) {
    echo "<pre style='font-family:monospace;'>\n";
}

// ── Guard: don't silently duplicate seed data on a second run (per-language tag) ──
$existingEsc = mysqli_real_escape_string($con, $seedTag);
$existing = mysqli_query($con, "SELECT COUNT(*) AS n FROM diagnoses WHERE summary LIKE '$existingEsc%'");
$existingCount = $existing ? (int)mysqli_fetch_assoc($existing)['n'] : 0;
if ($existingCount > 0 && !$force) {
    out("Sudah ada $existingCount baris seed bahasa '$lang' sebelumnya (summary diawali '$seedTag').", $isCli);
    out("Jalankan lagi dengan --force (CLI) atau ?force=1 (browser) kalau memang mau nambah lagi.", $isCli);
    out("", $isCli);
    out("Untuk membersihkan seed lama sebelum re-run, jalankan SQL ini dulu:", $isCli);
    out("  DELETE ds FROM diagnosis_symptoms ds JOIN diagnoses d ON ds.diagnosis_id = d.id WHERE d.summary LIKE '$seedTag%';", $isCli);
    out("  DELETE dd FROM diagnosis_details dd JOIN diagnoses d ON dd.diagnosis_id = d.id WHERE d.summary LIKE '$seedTag%';", $isCli);
    out("  DELETE FROM diagnoses WHERE summary LIKE '$seedTag%';", $isCli);
    if (!$isCli) echo "</pre>\n";
    exit;
}

// ── Symptom code -> id mapping (verified against ds_symptoms: D01-07=1-7, A01-07=8-14, S01-07=15-21) ──
function code2id($code)
{
    $sub  = $code[0];
    $num  = (int)substr($code, 1, 2);
    $base = ['D' => 0, 'A' => 7, 'S' => 14];
    return $base[$sub] + $num;
}

function codesToIds(array $codes)
{
    return array_values(array_unique(array_map('code2id', $codes)));
}

$D = ['D01', 'D02', 'D03', 'D04', 'D05', 'D06', 'D07'];
$A = ['A01', 'A02', 'A03', 'A04', 'A05', 'A06', 'A07'];
$S = ['S01', 'S02', 'S03', 'S04', 'S05', 'S06', 'S07'];

// ── 30 test scenarios ────────────────────────────────────────────────────
$scenarios = [
    'S01' => ['label' => '1 gejala per subskala (kasus minimal)',                 'D' => ['D01'],                         'A' => ['A01'],                         'S' => ['S01']],
    'S02' => ['label' => '2 gejala per subskala (berurutan awal)',                'D' => ['D01','D02'],                   'A' => ['A01','A02'],                   'S' => ['S01','S02']],
    'S03' => ['label' => '3 gejala per subskala (berurutan awal)',                'D' => ['D01','D02','D03'],             'A' => ['A01','A02','A03'],             'S' => ['S01','S02','S03']],
    'S04' => ['label' => '4 gejala per subskala (berurutan awal)',                'D' => ['D01','D02','D03','D04'],       'A' => ['A01','A02','A03','A04'],       'S' => ['S01','S02','S03','S04']],
    'S05' => ['label' => '5 gejala per subskala (berurutan awal)',                'D' => ['D01','D02','D03','D04','D05'], 'A' => ['A01','A02','A03','A04','A05'], 'S' => ['S01','S02','S03','S04','S05']],
    'S06' => ['label' => '6 gejala per subskala (berurutan awal)',                'D' => array_slice($D, 0, 6),           'A' => array_slice($A, 0, 6),           'S' => array_slice($S, 0, 6)],
    'S07' => ['label' => '7 gejala per subskala (semua, maksimum)',               'D' => $D,                              'A' => $A,                              'S' => $S],
    'S08' => ['label' => 'Hanya Depresi aktif (gejala ganjil), Anxiety+Stres kosong', 'D' => ['D01','D03','D05','D07'],    'A' => [],                              'S' => []],
    'S09' => ['label' => 'Hanya Anxiety aktif (gejala genap), Depresi+Stres kosong',  'D' => [],                           'A' => ['A02','A04','A06'],             'S' => []],
    'S10' => ['label' => 'Hanya Stres aktif (gejala ganjil), Depresi+Anxiety kosong', 'D' => [],                           'A' => [],                              'S' => ['S01','S03','S05','S07']],
    'S11' => ['label' => 'Gejala genap saja tiap subskala',                       'D' => ['D02','D04','D06'],             'A' => ['A02','A04','A06'],             'S' => ['S02','S04','S06']],
    'S12' => ['label' => 'Gejala ujung (pertama+terakhir) tiap subskala',         'D' => ['D01','D07'],                   'A' => ['A01','A07'],                   'S' => ['S01','S07']],
    'S13' => ['label' => 'Klaster tengah tiap subskala',                         'D' => ['D03','D04','D05'],             'A' => ['A03','A04','A05'],             'S' => ['S03','S04','S05']],
    'S14' => ['label' => '6 gejala terakhir tiap subskala',                      'D' => array_slice($D, 1),              'A' => array_slice($A, 1),              'S' => array_slice($S, 1)],
    'S15' => ['label' => 'Depresi dominan penuh, Anxiety+Stres minimal',         'D' => $D,                              'A' => ['A01'],                         'S' => ['S01']],
    'S16' => ['label' => 'Anxiety dominan penuh, Depresi+Stres minimal',         'D' => ['D01'],                         'A' => $A,                              'S' => ['S01']],
    'S17' => ['label' => 'Stres dominan penuh, Depresi+Anxiety minimal',         'D' => ['D01'],                         'A' => ['A01'],                         'S' => $S],
    'S18' => ['label' => 'Kombinasi acak A',                                     'D' => ['D02','D05','D07'],             'A' => ['A01','A04','A06'],             'S' => ['S03','S05','S07']],
    'S19' => ['label' => 'Kombinasi acak B',                                     'D' => ['D01','D03','D06'],             'A' => ['A02','A05','A07'],             'S' => ['S01','S04','S06']],
    'S20' => ['label' => 'Kombinasi acak C',                                     'D' => ['D04','D05'],                   'A' => ['A03','A04','A05'],             'S' => ['S02','S03','S04','S05']],
    'S21' => ['label' => 'Minimum 2 gejala total (D01+A01), Stres kosong',       'D' => ['D01'],                         'A' => ['A01'],                         'S' => []],
    'S22' => ['label' => 'Minimum 2 gejala total (D01+S01), Anxiety kosong',     'D' => ['D01'],                         'A' => [],                              'S' => ['S01']],
    'S23' => ['label' => 'Minimum 2 gejala total (A01+S01), Depresi kosong',     'D' => [],                              'A' => ['A01'],                         'S' => ['S01']],
    'S24' => ['label' => '6 gejala tidak berurutan (skip gejala tengah)',        'D' => ['D01','D02','D03','D05','D06','D07'], 'A' => ['A01','A02','A03','A05','A06','A07'], 'S' => ['S01','S02','S03','S05','S06','S07']],
    'S25' => ['label' => 'Gejala tunggal keseluruhan: hanya D04',                'D' => ['D04'],                         'A' => [],                              'S' => []],
    'S26' => ['label' => 'Gejala tunggal keseluruhan: hanya A04',                'D' => [],                              'A' => ['A04'],                         'S' => []],
    'S27' => ['label' => 'Gejala tunggal keseluruhan: hanya S04',                'D' => [],                              'A' => [],                              'S' => ['S04']],
    'S28' => ['label' => 'Gejala ekstrem kontras (ujung awal+akhir) - uji konflik kombinasi', 'D' => ['D01','D07'],       'A' => ['A01','A07'],                   'S' => ['S01','S07']],
    'S29' => ['label' => 'Kombinasi acak besar',                                 'D' => ['D01','D02','D04','D06'],       'A' => ['A02','A03','A05','A07'],       'S' => ['S01','S03','S05','S07']],
    'S30' => ['label' => 'Robustness: ID gejala tidak valid dicampur valid',     'D' => ['D01','D02'],                   'A' => ['A01','A02'],                   'S' => ['S01','S02'], 'extraInvalidId' => 9999],
];

$dg = new Diagnosa;
$subskalaFallback = [
    'D' => $langArr['subskala_depresi'] ?? 'Depresi',
    'A' => $langArr['subskala_anxiety'] ?? 'Anxiety',
    'S' => $langArr['subskala_stres'] ?? 'Stres',
];
$summaryRows = [];

out("Bahasa: $lang (tag: {$seedTag}...)", $isCli);

out(str_pad('Kode', 6) . str_pad('D', 22) . str_pad('A', 22) . str_pad('S', 22) . 'diagnosis_id', $isCli);
out(str_repeat('-', 90), $isCli);

foreach ($scenarios as $code => $sc) {
    $bySubskala = [
        'D' => codesToIds($sc['D']),
        'A' => codesToIds($sc['A']),
        'S' => codesToIds($sc['S']),
    ];
    if (!empty($sc['extraInvalidId'])) {
        $bySubskala['D'][] = (int)$sc['extraInvalidId'];
    }

    $results = ['D' => null, 'A' => null, 'S' => null];
    foreach (['D', 'A', 'S'] as $sk) {
        if (!empty($bySubskala[$sk])) {
            $results[$sk] = $dg->hitungSubskala($sk, $bySubskala[$sk]);
        }
    }

    $anyResult = $results['D'] || $results['A'] || $results['S'];
    if (!$anyResult) {
        out("$code  SKIP (tidak ada subskala menghasilkan data - cek scenario)", $isCli);
        continue;
    }

    // Build symptoms_text exactly like result.php: numbered list of symptom names in the
    // active language. $validSymptomIds (invalid id like S30's 9999 excluded) is also what
    // gets persisted into diagnosis_symptoms below, so the detail page can re-derive this
    // same list live in whatever language is active whenever it's opened later.
    $allIds = array_merge($bySubskala['D'], $bySubskala['A'], $bySubskala['S']);
    $invalidId = (int)($sc['extraInvalidId'] ?? -1);
    $validSymptomIds = array_values(array_filter($allIds, fn($id) => $id !== $invalidId));
    $gejalaDbStr = '';
    $i = 0;
    foreach ($validSymptomIds as $id) {
        $q = mysqli_query($con, "SELECT $nameCol AS name FROM ds_symptoms WHERE id = " . (int)$id);
        $obj = $q ? mysqli_fetch_object($q) : null;
        if (!$obj) continue;
        $i++;
        $gejalaDbStr .= $i . '. ' . $obj->name . '<br>';
    }

    $ringkasanNama = [];
    $ringkasanPct  = [];
    foreach (['D', 'A', 'S'] as $sk) {
        if ($results[$sk]) {
            $ringkasanNama[] = $subskalaFallback[$sk] . ': ' . $results[$sk]['severity_label'];
            $ringkasanPct[]  = $subskalaFallback[$sk] . ': ' . $results[$sk]['confidence_percentage'];
        }
    }
    $penyakitStr   = implode(' | ', $ringkasanNama);
    $persentaseStr = implode(' | ', $ringkasanPct);
    $nilaiStr      = $results['D']['confidence_value'] ?? ($results['A']['confidence_value'] ?? ($results['S']['confidence_value'] ?? 0));
    $summary       = "{$seedTag}{$code}] $penyakitStr";
    $tanggal       = date('d-m-Y') . '<br>' . date('h:i:s A');

    mysqli_query($con,
        "INSERT INTO diagnoses (diagnosis_date, symptoms_text, summary, confidence_value, confidence_percentage)
         VALUES ('$tanggal', '" . mysqli_real_escape_string($con, $gejalaDbStr) . "', '" . mysqli_real_escape_string($con, $summary) . "',
                 '$nilaiStr', '" . mysqli_real_escape_string($con, $persentaseStr) . "')"
    );
    $diagnosisId = mysqli_insert_id($con);

    foreach (['D', 'A', 'S'] as $sk) {
        if (!$results[$sk]) continue;
        $r = $results[$sk];
        $nilaiEsc = (float)$r['confidence_value'];
        mysqli_query($con,
            "INSERT INTO diagnosis_details (diagnosis_id, source, subscale, severity_level, severity_label, confidence_value, confidence_percentage)
             VALUES ($diagnosisId, 'diagnosa', '$sk', '{$r['severity_level']}',
                     '" . mysqli_real_escape_string($con, $r['severity_label']) . "',
                     $nilaiEsc, '{$r['confidence_percentage']}')"
        );
    }

    foreach ($validSymptomIds as $symptomId) {
        mysqli_query($con,
            "INSERT INTO diagnosis_symptoms (diagnosis_id, source, symptom_id) VALUES ($diagnosisId, 'diagnosa', " . (int)$symptomId . ")"
        );
    }

    $fmt = fn($r) => $r ? ($r['severity_level'] . ' ' . $r['confidence_percentage']) : '-';
    out(
        str_pad($code, 6) .
        str_pad($fmt($results['D']), 22) .
        str_pad($fmt($results['A']), 22) .
        str_pad($fmt($results['S']), 22) .
        $diagnosisId,
        $isCli
    );

    $summaryRows[] = ['code' => $code, 'label' => $sc['label'], 'diagnosis_id' => $diagnosisId, 'results' => $results];
}

out(str_repeat('-', 90), $isCli);
out('Selesai: ' . count($summaryRows) . ' dari ' . count($scenarios) . ' skenario berhasil di-insert (bahasa: ' . $lang . ').', $isCli);
out("Filter buat lihat semua baris seed bahasa ini: SELECT * FROM diagnoses WHERE summary LIKE '{$seedTag}%';", $isCli);
out("Filter buat lihat SEMUA seed (semua bahasa): SELECT * FROM diagnoses WHERE summary LIKE '[SEED-%';", $isCli);

if (!$isCli) {
    echo "</pre>\n";
}
