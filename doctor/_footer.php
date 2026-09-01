  <!-- Admin footer -->
  <div class="admin-footer">
    Aplikasi by <a href="https://www.instagram.com/firbel.el/">Fira Bella Mustikahadi</a>
  </div>

</div><!-- /.admin-main -->

<!-- jQuery (required for DataTables) -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<!-- Bootstrap 5 -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<!-- DataTables + Bootstrap 5 skin -->
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>

<script>
$(function () {
  if ($('#example3').length)            { $('#example3').DataTable({ ordering: false }); }
  if ($('#bootstrap-data-table').length){ $('#bootstrap-data-table').DataTable({ ordering: false }); }
  if ($('#example23').length)           { $('#example23').DataTable({ ordering: false }); }
});

var sidebarToggle  = document.getElementById('sidebarToggle');
var adminSidebar   = document.getElementById('adminSidebar');
var sidebarOverlay = document.getElementById('sidebarOverlay');

if (sidebarToggle) {
  sidebarToggle.addEventListener('click', function () {
    adminSidebar.classList.toggle('show');
    sidebarOverlay.classList.toggle('show');
  });
}
if (sidebarOverlay) {
  sidebarOverlay.addEventListener('click', function () {
    adminSidebar.classList.remove('show');
    sidebarOverlay.classList.remove('show');
  });
}
</script>
</body>
</html>
