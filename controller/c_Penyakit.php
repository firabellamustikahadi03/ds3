<?php
class Penyakit
{
    private function getLangCol($prefix = 'nama') {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $valid = ['id', 'en', 'tr', 'zh'];
        $lang  = (isset($_SESSION['lang']) && in_array($_SESSION['lang'], $valid)) ? $_SESSION['lang'] : 'id';
        // kolom keterangan Indonesian adalah 'kett' (bukan 'kett_id')
        if ($prefix === 'kett' && $lang === 'id') return 'kett';
        return $prefix . '_' . $lang;
    }

    function TampilSemua() {
        include "../connection/connection.php";
        $namaCol = $this->getLangCol('nama');
        $kettCol = $this->getLangCol('kett');
        $query = mysqli_query($con, "SELECT id, $namaCol as nama, IF($kettCol IS NULL OR $kettCol='', kett, $kettCol) as kett FROM ds_penyakit");
        $i = 1;
        while ($d = mysqli_fetch_array($query)) {
            $data[$i]['id']   = $d['id'];
            $data[$i]['nama'] = $d['nama'];
            $data[$i]['kett'] = $d['kett'];
            $i++;
        }
        return $data;
    }

    function InsertPenyakit($nama_id, $nama_en, $nama_tr, $nama_zh, $kett_id, $kett_en, $kett_tr, $kett_zh) {
        include "../connection/connection.php";
        $nama_id = mysqli_real_escape_string($con, $nama_id);
        $nama_en = mysqli_real_escape_string($con, $nama_en);
        $nama_tr = mysqli_real_escape_string($con, $nama_tr);
        $nama_zh = mysqli_real_escape_string($con, $nama_zh);
        $kett_id = mysqli_real_escape_string($con, $kett_id);
        $kett_en = mysqli_real_escape_string($con, $kett_en);
        $kett_tr = mysqli_real_escape_string($con, $kett_tr);
        $kett_zh = mysqli_real_escape_string($con, $kett_zh);
        mysqli_query($con, "INSERT INTO ds_penyakit (nama, nama_id, nama_en, nama_tr, nama_zh, kett, kett_en, kett_tr, kett_zh)
            VALUES ('$nama_id','$nama_id','$nama_en','$nama_tr','$nama_zh','$kett_id','$kett_en','$kett_tr','$kett_zh')");
    }

    function HapusPenyakit($id) {
        include "../connection/connection.php";
        mysqli_query($con, "DELETE FROM ds_penyakit WHERE id = '$id'");
    }

    function EditPenyakit($id, $nama_id, $nama_en, $nama_tr, $nama_zh, $kett_id, $kett_en, $kett_tr, $kett_zh) {
        include "../connection/connection.php";
        $nama_id = mysqli_real_escape_string($con, $nama_id);
        $nama_en = mysqli_real_escape_string($con, $nama_en);
        $nama_tr = mysqli_real_escape_string($con, $nama_tr);
        $nama_zh = mysqli_real_escape_string($con, $nama_zh);
        $kett_id = mysqli_real_escape_string($con, $kett_id);
        $kett_en = mysqli_real_escape_string($con, $kett_en);
        $kett_tr = mysqli_real_escape_string($con, $kett_tr);
        $kett_zh = mysqli_real_escape_string($con, $kett_zh);
        mysqli_query($con, "UPDATE ds_penyakit SET
            nama='$nama_id', nama_id='$nama_id', nama_en='$nama_en', nama_tr='$nama_tr', nama_zh='$nama_zh',
            kett='$kett_id', kett_en='$kett_en', kett_tr='$kett_tr', kett_zh='$kett_zh'
            WHERE id='$id'");
    }

    function TampilSatuData($id) {
        include "../connection/connection.php";
        $query = mysqli_query($con, "SELECT * FROM ds_penyakit WHERE id = '$id'");
        $p = mysqli_fetch_object($query);
        $this->id      = $p->id;
        $this->nama    = $p->nama_id;
        $this->nama_id = $p->nama_id;
        $this->nama_en = $p->nama_en;
        $this->nama_tr = $p->nama_tr;
        $this->nama_zh = $p->nama_zh;
        $this->kett    = $p->kett;
        $this->kett_en = $p->kett_en;
        $this->kett_tr = $p->kett_tr;
        $this->kett_zh = $p->kett_zh;
    }

    function TampilAngka() {
        include "../connection/connection.php";
        $query = mysqli_query($con, "SELECT max(id) as nilai FROM ds_penyakit");
        $g = mysqli_fetch_object($query);
        $this->nilai = $g->nilai;
    }
}
error_reporting(0);
