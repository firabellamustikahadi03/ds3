<?php
class SeverityLevel
{
    private function getLangCol($prefix = 'name') {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $valid = ['id', 'en', 'tr', 'zh'];
        $lang = (isset($_SESSION['lang']) && in_array($_SESSION['lang'], $valid)) ? $_SESSION['lang'] : 'id';
        return $prefix . '_' . $lang;
    }

    /** All 12 severity levels (3 subscales x 4 levels), ordered for the admin list page. */
    function TampilSemua() {
        include "../connection/connection.php";
        $col = $this->getLangCol();
        $query = mysqli_query($con, "SELECT id, subscale, severity_level, sort_order, $col as name
                                      FROM ds_severity_levels ORDER BY subscale, sort_order");
        $i = 0;
        while ($d = mysqli_fetch_array($query)) {
            $data[$i] = $d;
            $i++;
        }
        return $data;
    }

    function TampilSatuData($id) {
        include "../connection/connection.php";
        $query = mysqli_query($con, "SELECT * FROM ds_severity_levels WHERE id = '$id'");
        $g = mysqli_fetch_object($query);
        $this->id               = $g->id;
        $this->subscale         = $g->subscale;
        $this->severity_level   = $g->severity_level;
        $this->name_id          = $g->name_id;
        $this->name_en          = $g->name_en;
        $this->name_tr          = $g->name_tr;
        $this->name_zh          = $g->name_zh;
        $this->recommendation_id = $g->recommendation_id;
        $this->recommendation_en = $g->recommendation_en;
        $this->recommendation_tr = $g->recommendation_tr;
        $this->recommendation_zh = $g->recommendation_zh;
    }

    /** Update a severity level's translated name + recommendation text. Subscale/level identity is not editable. */
    function EditTingkat($id, $name_id, $name_en, $name_tr, $name_zh, $recommendation_id, $recommendation_en, $recommendation_tr, $recommendation_zh) {
        include "../connection/connection.php";
        $id = (int)$id;
        $name_id = mysqli_real_escape_string($con, $name_id);
        $name_en = mysqli_real_escape_string($con, $name_en);
        $name_tr = mysqli_real_escape_string($con, $name_tr);
        $name_zh = mysqli_real_escape_string($con, $name_zh);
        $recommendation_id = mysqli_real_escape_string($con, $recommendation_id);
        $recommendation_en = mysqli_real_escape_string($con, $recommendation_en);
        $recommendation_tr = mysqli_real_escape_string($con, $recommendation_tr);
        $recommendation_zh = mysqli_real_escape_string($con, $recommendation_zh);
        mysqli_query($con, "UPDATE ds_severity_levels SET
            name_id='$name_id', name_en='$name_en', name_tr='$name_tr', name_zh='$name_zh',
            recommendation_id='$recommendation_id', recommendation_en='$recommendation_en',
            recommendation_tr='$recommendation_tr', recommendation_zh='$recommendation_zh'
            WHERE id=$id");
    }
}
error_reporting(0);
