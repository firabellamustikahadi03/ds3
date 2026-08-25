<?php
class Symptom
{
    private function getLangCol($prefix = 'name') {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $valid = ['id', 'en', 'tr', 'zh'];
        $lang = (isset($_SESSION['lang']) && in_array($_SESSION['lang'], $valid)) ? $_SESSION['lang'] : 'id';
        return $prefix . '_' . $lang;
    }

    function TampilSemua() {
        include "../connection/connection.php";
        $col = $this->getLangCol();
        $query = mysqli_query($con, "SELECT id, symptom_code, $col as name FROM ds_symptoms");
        $i = 0;
        while ($d = mysqli_fetch_array($query)) {
            $data[$i]['id']   = $d['id'];
            $data[$i]['name'] = $d['name'];
            $i++;
        }
        return $data;
    }

    function TampilSatuData($id) {
        include "../connection/connection.php";
        $query = mysqli_query($con, "SELECT * FROM ds_symptoms WHERE id = '$id'");
        $g = mysqli_fetch_object($query);
        $this->id                = $g->id;
        $this->symptom_code      = $g->symptom_code;
        $this->subscale          = $g->subscale;
        $this->name_id           = $g->name_id;
        $this->name_en           = $g->name_en;
        $this->name_tr           = $g->name_tr;
        $this->name_zh           = $g->name_zh;
        $this->m_mild_moderate   = $g->m_mild_moderate;
        $this->m_moderate_severe = $g->m_moderate_severe;
        $this->m_severe_extreme  = $g->m_severe_extreme;
        $this->m_theta           = $g->m_theta;
        $this->is_active         = $g->is_active;
    }

    function TampilAngka() {
        include "../connection/connection.php";
        $query = mysqli_query($con, "SELECT max(id) as value FROM ds_symptoms");
        $g = mysqli_fetch_object($query);
        $this->value = $g->value;
    }

    /** Symptoms for one DASS-21 subscale, ordered by item number — used by the public and doctor-panel diagnosis forms. */
    function TampilBySubskala($subscale) {
        include __DIR__ . "/../connection/connection.php";
        if (session_status() === PHP_SESSION_NONE) session_start();
        $valid = ['id', 'en', 'tr', 'zh'];
        $lang  = (isset($_SESSION['lang']) && in_array($_SESSION['lang'], $valid)) ? $_SESSION['lang'] : 'id';
        $col   = 'name_' . $lang;
        $subscale = mysqli_real_escape_string($con, $subscale);
        $query = mysqli_query($con, "SELECT id, $col as name FROM ds_symptoms
                                      WHERE subscale = '$subscale' AND is_active = 1
                                      ORDER BY dass_item");
        $i = 0;
        while ($d = mysqli_fetch_array($query)) {
            $data[$i]['id']   = $d['id'];
            $data[$i]['name'] = $d['name'];
            $i++;
        }
        return $data;
    }

    /** All 21 symptoms grouped/ordered by subscale + item number, with mass values and active status — for the admin list page. */
    function TampilSemuaAdmin() {
        include "../connection/connection.php";
        $col = $this->getLangCol();
        $query = mysqli_query($con, "SELECT id, symptom_code, subscale, dass_item, $col as name,
                                             m_mild_moderate, m_moderate_severe, m_severe_extreme, m_theta, is_active
                                      FROM ds_symptoms ORDER BY subscale, dass_item");
        $i = 0;
        while ($d = mysqli_fetch_array($query)) {
            $data[$i] = $d;
            $i++;
        }
        return $data;
    }

    /**
     * Update a symptom's translated names and mass values. Does NOT touch subscale/dass_item/symptom_code
     * (those are the symptom's fixed identity, not editable) or is_active (see ToggleAktif()).
     * Caller must have already validated m_mild_moderate + m_moderate_severe + m_severe_extreme + m_theta == 1.00
     * server-side before calling this — this method does not re-validate.
     */
    function EditGejala($id, $name_id, $name_en, $name_tr, $name_zh, $m_mild_moderate, $m_moderate_severe, $m_severe_extreme, $m_theta) {
        include "../connection/connection.php";
        $id = (int)$id;
        $name_id = mysqli_real_escape_string($con, $name_id);
        $name_en = mysqli_real_escape_string($con, $name_en);
        $name_tr = mysqli_real_escape_string($con, $name_tr);
        $name_zh = mysqli_real_escape_string($con, $name_zh);
        $m_mild_moderate = (float)$m_mild_moderate;
        $m_moderate_severe = (float)$m_moderate_severe;
        $m_severe_extreme = (float)$m_severe_extreme;
        $m_theta = (float)$m_theta;
        mysqli_query($con, "UPDATE ds_symptoms SET
            name_id='$name_id', name_en='$name_en', name_tr='$name_tr', name_zh='$name_zh',
            m_mild_moderate=$m_mild_moderate, m_moderate_severe=$m_moderate_severe,
            m_severe_extreme=$m_severe_extreme, m_theta=$m_theta
            WHERE id=$id");
    }

    /** Flip is_active 0/1 for one symptom. Returns the new value so the caller can confirm what happened. */
    function ToggleAktif($id) {
        include "../connection/connection.php";
        $id = (int)$id;
        mysqli_query($con, "UPDATE ds_symptoms SET is_active = 1 - is_active WHERE id=$id");
        $result = mysqli_query($con, "SELECT is_active FROM ds_symptoms WHERE id=$id");
        $row = mysqli_fetch_assoc($result);
        return $row ? (int)$row['is_active'] : null;
    }
}
error_reporting(0);
