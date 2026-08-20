<?php
class Gejala
{
    private function getLangCol($prefix = 'nama') {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $valid = ['id', 'en', 'tr', 'zh'];
        $lang = (isset($_SESSION['lang']) && in_array($_SESSION['lang'], $valid)) ? $_SESSION['lang'] : 'id';
        return $prefix . '_' . $lang;
    }

    function TampilSemua() {
        include "../koneksi/koneksi.php";
        $col = $this->getLangCol();
        $query = mysqli_query($con, "SELECT id, kode, $col as nama FROM ds_gejala");
        $i = 0;
        while ($d = mysqli_fetch_array($query)) {
            $data[$i]['id']   = $d['id'];
            $data[$i]['nama'] = $d['nama'];
            $i++;
        }
        return $data;
    }

    function InsertGejala($nama_id, $nama_en, $nama_tr, $nama_zh) {
        include "../koneksi/koneksi.php";
        $nama_id = mysqli_real_escape_string($con, $nama_id);
        $nama_en = mysqli_real_escape_string($con, $nama_en);
        $nama_tr = mysqli_real_escape_string($con, $nama_tr);
        $nama_zh = mysqli_real_escape_string($con, $nama_zh);
        mysqli_query($con, "INSERT INTO ds_gejala (nama, nama_id, nama_en, nama_tr, nama_zh)
            VALUES ('$nama_id', '$nama_id', '$nama_en', '$nama_tr', '$nama_zh')");
    }

    function HapusGejala($id) {
        include "../koneksi/koneksi.php";
        mysqli_query($con, "DELETE FROM ds_gejala WHERE id = '$id'");
    }

    function EditGejala($id, $nama_id, $nama_en, $nama_tr, $nama_zh) {
        include "../koneksi/koneksi.php";
        $nama_id = mysqli_real_escape_string($con, $nama_id);
        $nama_en = mysqli_real_escape_string($con, $nama_en);
        $nama_tr = mysqli_real_escape_string($con, $nama_tr);
        $nama_zh = mysqli_real_escape_string($con, $nama_zh);
        mysqli_query($con, "UPDATE ds_gejala SET nama='$nama_id', nama_id='$nama_id', nama_en='$nama_en', nama_tr='$nama_tr', nama_zh='$nama_zh' WHERE id='$id'");
    }

    function TampilSatuData($id) {
        include "../koneksi/koneksi.php";
        $query = mysqli_query($con, "SELECT * FROM ds_gejala WHERE id = '$id'");
        $g = mysqli_fetch_object($query);
        $this->id      = $g->id;
        $this->nama    = $g->nama_id;
        $this->nama_id = $g->nama_id;
        $this->nama_en = $g->nama_en;
        $this->nama_tr = $g->nama_tr;
        $this->nama_zh = $g->nama_zh;
    }

    function TampilAngka() {
        include "../koneksi/koneksi.php";
        $query = mysqli_query($con, "SELECT max(id) as nilai FROM ds_gejala");
        $g = mysqli_fetch_object($query);
        $this->nilai = $g->nilai;
    }

    function TampilSemuaWeb() {
        include "koneksi/koneksi.php";
        if (session_status() === PHP_SESSION_NONE) session_start();
        $valid = ['id', 'en', 'tr', 'zh'];
        $lang  = (isset($_SESSION['lang']) && in_array($_SESSION['lang'], $valid)) ? $_SESSION['lang'] : 'id';
        $col   = 'nama_' . $lang;
        $query = mysqli_query($con, "SELECT id, $col as nama FROM ds_gejala");
        $i = 0;
        while ($d = mysqli_fetch_array($query)) {
            $data[$i]['id']   = $d['id'];
            $data[$i]['nama'] = $d['nama'];
            $i++;
        }
        return $data;
    }
}
error_reporting(0);
