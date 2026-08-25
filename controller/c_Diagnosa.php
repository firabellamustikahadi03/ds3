<?php
/**
 * Dempster-Shafer engine for the DASS-21 model.
 * Frame of discernment per subscale: 1=Mild, 2=Moderate, 3=Severe, 4=Extreme.
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
     * Returns [] when K >= 1.0 — fully contradictory evidence with no combinable
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
     * Build the initial mass function (evidence) for one DASS-21 symptom row.
     * Zero-mass entries are omitted.
     */
    function buildEvidence($m_mild_moderate, $m_moderate_severe, $m_severe_extreme, $m_theta)
    {
        $evidence = [];
        if ($m_mild_moderate > 0)   $evidence['1,2']     = (float)$m_mild_moderate;
        if ($m_moderate_severe > 0) $evidence['2,3']     = (float)$m_moderate_severe;
        if ($m_severe_extreme > 0)  $evidence['3,4']     = (float)$m_severe_extreme;
        if ($m_theta > 0)           $evidence['1,2,3,4'] = (float)$m_theta;
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
     * Legacy name kept as a thin alias in case any old call site still resolves to it.
     * NOT a behavioral drop-in for the pre-DASS21 row-pair callers: this delegates to
     * combineMass()'s conflict-key convention ('#CONFLICT#'), not the old '&theta;'
     * sentinel those callers expected. Exists only so the method name still resolves,
     * not so old call sites keep working unmodified.
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
     * the symptoms the patient selected in that subscale.
     *
     * @param string $subscale 'D', 'A', or 'S'
     * @param int[]  $symptomIds ids from ds_symptoms already filtered to this subscale
     * @return array|null null when no matching, active symptom found; otherwise
     *   ['severity_level'=>'Mild'|'Moderate'|'Severe'|'Extreme', 'severity_label'=>string,
     *    'confidence_value'=>float, 'confidence_percentage'=>string, 'recommendation'=>string]
     */
    function hitungSubskala($subscale, array $symptomIds)
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        // __DIR__-relative on purpose: this method is called both from root-level
        // pages (result.php) and from CLI test scripts under tests/, which have
        // different working directories. A bare "connection/connection.php" include
        // only resolves from the first kind of caller.
        include __DIR__ . '/../connection/connection.php';

        if (empty($symptomIds)) return null;

        $inList = implode(',', array_map('intval', $symptomIds));
        $subscaleEsc = mysqli_real_escape_string($con, $subscale);
        $sql = "SELECT m_mild_moderate, m_moderate_severe, m_severe_extreme, m_theta FROM ds_symptoms
                WHERE id IN ($inList) AND subscale = '$subscaleEsc' AND is_active = 1";
        $result = mysqli_query($con, $sql);
        if (!$result || mysqli_num_rows($result) === 0) return null;

        $combined = null;
        while ($row = mysqli_fetch_assoc($result)) {
            $evidence = $this->buildEvidence($row['m_mild_moderate'], $row['m_moderate_severe'], $row['m_severe_extreme'], $row['m_theta']);
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
            $confidenceValue = $topMass;
        } else {
            $pig = $this->pignistic($combined);
            arsort($pig);
            $levelCodeInt = array_key_first($pig);
            $confidenceValue = $pig[$levelCodeInt];
        }

        $levelMap = [1 => 'Mild', 2 => 'Moderate', 3 => 'Severe', 4 => 'Extreme'];
        $severityLevel = $levelMap[$levelCodeInt];

        $validLangs = ['id', 'en', 'tr', 'zh'];
        $lang    = (isset($_SESSION['lang']) && in_array($_SESSION['lang'], $validLangs)) ? $_SESSION['lang'] : 'id';
        $nameCol = 'name_' . $lang;
        $recommendationCol = 'recommendation_' . $lang;

        $sql = "SELECT $nameCol as name, IF($recommendationCol IS NULL OR $recommendationCol='', recommendation_id, $recommendationCol) as recommendation
                FROM ds_severity_levels WHERE subscale = '$subscaleEsc' AND severity_level = '$severityLevel'";
        $result = mysqli_query($con, $sql);
        $obj    = $result ? mysqli_fetch_object($result) : null;

        return [
            'severity_level'        => $severityLevel,
            'severity_label'        => $obj ? $obj->name : $severityLevel,
            'confidence_value'      => $confidenceValue,
            'confidence_percentage' => round($confidenceValue * 100, 2) . '%',
            'recommendation'        => $obj ? $obj->recommendation : '',
        ];
    }
}
