<?php
/**
 * Dempster-Shafer engine for the DASS-21 model.
 * Frame of discernment per subscale: 1=Hafif, 2=Orta, 3=Agir, 4=CokAgir.
 * Focal sets are represented as comma-joined, numerically sorted strings, e.g. "1,2".
 */
class Diagnosa
{
    /**
     * Combine two mass functions via Dempster's rule of combination (unnormalized —
     * conflicting mass is collected under the '#CONFLICT#' key).
     *
     * @param array $m1 ['1,2' => 0.35, '2,3' => 0.30, ...]
     * @param array $m2 same shape
     * @return array combined, unnormalized
     */
    function combineMass($m1, $m2)
    {
        $combined = [];
        foreach ($m1 as $setA => $massA) {
            $a = explode(',', $setA);
            foreach ($m2 as $setB => $massB) {
                $b = explode(',', $setB);
                $intersection = array_values(array_unique(array_intersect($a, $b)));
                sort($intersection, SORT_NUMERIC);
                $key = empty($intersection) ? '#CONFLICT#' : implode(',', $intersection);
                $product = $massA * $massB;
                $combined[$key] = ($combined[$key] ?? 0) + $product;
            }
        }
        return $combined;
    }

    /**
     * Normalize a combined mass function by dividing out total conflict mass K.
     * Returns [] if K >= 1 — fully contradictory evidence with no combinable
     * belief left to normalize, rather than attempting to divide by zero.
     */
    function normalizeMass($combined)
    {
        $conflict = $combined['#CONFLICT#'] ?? 0;
        unset($combined['#CONFLICT#']);
        if ($conflict >= 1.0) {
            return [];
        }
        $normalized = [];
        foreach ($combined as $set => $mass) {
            $normalized[$set] = $mass / (1 - $conflict);
        }
        return $normalized;
    }

    /**
     * Build the initial mass function (evidence) for one DASS-21 gejala row.
     * Zero-mass entries are omitted.
     */
    function buildEvidence($m_ho, $m_oa, $m_aca, $m_theta)
    {
        $evidence = [];
        if ($m_ho > 0)    $evidence['1,2']     = (float)$m_ho;
        if ($m_oa > 0)    $evidence['2,3']     = (float)$m_oa;
        if ($m_aca > 0)   $evidence['3,4']     = (float)$m_aca;
        if ($m_theta > 0) $evidence['1,2,3,4'] = (float)$m_theta;
        return $evidence;
    }

    /**
     * Pignistic transformation: split each multi-element focal set's mass equally
     * across its member singletons, then sum per singleton.
     * Returns [1 => p1, 2 => p2, 3 => p3, 4 => p4] summing to 1.0.
     */
    function pignistic($combined)
    {
        $pig = [1 => 0.0, 2 => 0.0, 3 => 0.0, 4 => 0.0];
        foreach ($combined as $setStr => $mass) {
            $elems = explode(',', $setStr);
            $share = $mass / count($elems);
            foreach ($elems as $e) {
                $pig[(int)$e] += $share;
            }
        }
        return $pig;
    }

    /**
     * Legacy name. Converts the old [ [code, mass], ... ] row-pair format to assoc
     * arrays and delegates to combineMass(). This is NOT a behavioral drop-in for
     * old row-pair callers: combineMass() uses a different conflict-key convention
     * ('#CONFLICT#') than the legacy code's '&theta;' sentinel, so callers written
     * against the old semantics (e.g. hasil.php's old normalization code) will not
     * work correctly against this. It exists only so the method name still resolves
     * if referenced somewhere, not so old call sites keep working unmodified.
     */
    function perkaliantabel($m, $densitas1, $densitas2, $densitas_baru)
    {
        $m1 = [];
        foreach ($densitas1 as $row) $m1[$row[0]] = $row[1];
        $m2 = [];
        foreach ($densitas2 as $row) $m2[$row[0]] = $row[1];
        $raw = $this->combineMass($m1, $m2);
        foreach ($raw as $k => $v) {
            $densitas_baru[$k] = ($densitas_baru[$k] ?? 0) + $v;
        }
        return $densitas_baru;
    }

    /**
     * Run the full Dempster-Shafer combination for one DASS-21 subscale across
     * the gejala the patient selected in that subscale.
     *
     * @param string $subskala 'D', 'A', or 'S'
     * @param int[]  $gejalaIds ids from ds_gejala already filtered to this subskala
     * @return array|null null when no matching, active gejala found; otherwise
     *   ['level_kode'=>'H'|'O'|'A'|'CA', 'level_nama'=>string, 'nilai'=>float,
     *    'persentase'=>string, 'kett'=>string]
     */
    function hitungSubskala($subskala, array $gejalaIds)
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        // __DIR__-relative on purpose: this method is called both from root-level
        // pages (hasil.php) and from CLI test scripts under tests/, which have
        // different working directories. A bare "koneksi/koneksi.php" include only
        // resolves from the first kind of caller.
        include __DIR__ . '/../koneksi/koneksi.php';

        if (empty($gejalaIds)) return null;

        $inList = implode(',', array_map('intval', $gejalaIds));
        $subskalaEsc = mysqli_real_escape_string($con, $subskala);
        $sql = "SELECT m_ho, m_oa, m_aca, m_theta FROM ds_gejala
                WHERE id IN ($inList) AND subskala = '$subskalaEsc' AND is_active = 1";
        $result = mysqli_query($con, $sql);
        if (!$result || mysqli_num_rows($result) === 0) return null;

        $combined = null;
        while ($row = mysqli_fetch_assoc($result)) {
            $evidence = $this->buildEvidence($row['m_ho'], $row['m_oa'], $row['m_aca'], $row['m_theta']);
            if ($combined === null) {
                $combined = $evidence;
            } else {
                $combined = $this->normalizeMass($this->combineMass($combined, $evidence));
            }
        }

        if (empty($combined)) return null;

        arsort($combined);
        $topKey   = array_key_first($combined);
        $topMass  = $combined[$topKey];
        $topElems = explode(',', $topKey);

        if (count($topElems) === 1) {
            $levelCodeInt = (int)$topElems[0];
            $nilai        = $topMass;
        } else {
            $pig = $this->pignistic($combined);
            arsort($pig);
            $levelCodeInt = array_key_first($pig);
            $nilai        = $pig[$levelCodeInt];
        }

        $levelMap  = [1 => 'H', 2 => 'O', 3 => 'A', 4 => 'CA'];
        $levelKode = $levelMap[$levelCodeInt];

        $validLangs = ['id', 'en', 'tr', 'zh'];
        $lang    = (isset($_SESSION['lang']) && in_array($_SESSION['lang'], $validLangs)) ? $_SESSION['lang'] : 'id';
        $namaCol = 'nama_' . $lang;
        $kettCol = ($lang === 'id') ? 'kett' : 'kett_' . $lang;

        $sql = "SELECT $namaCol as nama, IF($kettCol IS NULL OR $kettCol='', kett, $kettCol) as kett
                FROM ds_tingkat WHERE subskala = '$subskalaEsc' AND level = '$levelKode'";
        $result = mysqli_query($con, $sql);
        $obj    = $result ? mysqli_fetch_object($result) : null;

        return [
            'level_kode' => $levelKode,
            'level_nama' => $obj ? $obj->nama : $levelKode,
            'nilai'      => $nilai,
            'persentase' => round($nilai * 100, 2) . '%',
            'kett'       => $obj ? $obj->kett : '',
        ];
    }
}
