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

    function EditGejala($id, $name_id, $name_en, $name_tr, $name_zh) {
        include "../connection/connection.php";
        $name_id = mysqli_real_escape_string($con, $name_id);
        $name_en = mysqli_real_escape_string($con, $name_en);
        $name_tr = mysqli_real_escape_string($con, $name_tr);
        $name_zh = mysqli_real_escape_string($con, $name_zh);
        mysqli_query($con, "UPDATE ds_symptoms SET name_id='$name_id', name_en='$name_en', name_tr='$name_tr', name_zh='$name_zh' WHERE id='$id'");
    }

    function TampilSatuData($id) {
        include "../connection/connection.php";
        $query = mysqli_query($con, "SELECT * FROM ds_symptoms WHERE id = '$id'");
        $g = mysqli_fetch_object($query);
        $this->id      = $g->id;
        $this->name_id = $g->name_id;
        $this->name_en = $g->name_en;
        $this->name_tr = $g->name_tr;
        $this->name_zh = $g->name_zh;
    }

    function TampilAngka() {
        include "../connection/connection.php";
        $query = mysqli_query($con, "SELECT max(id) as value FROM ds_symptoms");
        $g = mysqli_fetch_object($query);
        $this->value = $g->value;
    }

    /** Symptoms for one DASS-21 subscale, ordered by item number — used by the public diagnosis form. */
    function TampilBySubskala($subscale) {
        include "connection/connection.php";
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
}
error_reporting(0);
