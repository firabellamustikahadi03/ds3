<?php
// Emits the DataTables UI strings (search box, paging, counters) in the active language.
// Included by admin/_footer.php and doctor/_footer.php before the DataTable() calls.
$_dtStrings = [
    'id' => ['lengthMenu' => 'Tampilkan _MENU_ data', 'search' => 'Cari:', 'info' => 'Menampilkan _START_ - _END_ dari _TOTAL_ data',
             'infoEmpty' => 'Tidak ada data', 'infoFiltered' => '(disaring dari _MAX_ data)', 'zeroRecords' => 'Data tidak ditemukan',
             'emptyTable' => 'Belum ada data', 'paginate' => ['first' => 'Awal', 'last' => 'Akhir', 'next' => 'Berikutnya', 'previous' => 'Sebelumnya']],
    'en' => ['lengthMenu' => 'Show _MENU_ entries', 'search' => 'Search:', 'info' => 'Showing _START_ to _END_ of _TOTAL_ entries',
             'infoEmpty' => 'No entries', 'infoFiltered' => '(filtered from _MAX_ entries)', 'zeroRecords' => 'No matching records found',
             'emptyTable' => 'No data available', 'paginate' => ['first' => 'First', 'last' => 'Last', 'next' => 'Next', 'previous' => 'Previous']],
    'tr' => ['lengthMenu' => '_MENU_ kayıt göster', 'search' => 'Ara:', 'info' => '_TOTAL_ kayıttan _START_ - _END_ arası gösteriliyor',
             'infoEmpty' => 'Kayıt yok', 'infoFiltered' => '(_MAX_ kayıt içinden filtrelendi)', 'zeroRecords' => 'Eşleşen kayıt bulunamadı',
             'emptyTable' => 'Veri yok', 'paginate' => ['first' => 'İlk', 'last' => 'Son', 'next' => 'Sonraki', 'previous' => 'Önceki']],
    'zh' => ['lengthMenu' => '显示 _MENU_ 条', 'search' => '搜索：', 'info' => '显示第 _START_ 至 _END_ 条，共 _TOTAL_ 条',
             'infoEmpty' => '暂无记录', 'infoFiltered' => '(从 _MAX_ 条记录中筛选)', 'zeroRecords' => '未找到匹配记录',
             'emptyTable' => '暂无数据', 'paginate' => ['first' => '首页', 'last' => '末页', 'next' => '下一页', 'previous' => '上一页']],
];
$_dtLang = (isset($_SESSION['lang']) && isset($_dtStrings[$_SESSION['lang']])) ? $_SESSION['lang'] : 'id';
echo '<script>var DT_LANG = ' . json_encode($_dtStrings[$_dtLang]) . ';</script>' . "\n";
