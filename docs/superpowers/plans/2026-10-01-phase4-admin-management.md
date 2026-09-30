# Phase 4: Admin Account Management Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Let a logged-in admin create, list, edit, and delete other admin accounts, with a server-enforced rule blocking editing or deleting one's own account.

**Architecture:** `controller/c_Admin.php`'s existing `TambahDokter()`/`UbahDokter()`/`HapusDokter()` methods are already role-agnostic (they operate on any `admins` row by id); this phase adds admin-specific *pages* that call the same methods with `role='admin'`, plus a new `AdminSemua()` list method, plus a self-protection guard added directly to the shared edit/delete processors.

**Tech Stack:** PHP 8.2 + MySQL (mysqli), session-based auth, existing 4-language `$_SESSION['langArray']` system.

**Project testing convention:** No unit-test framework — every task verifies with `php -l <file>` (syntax) plus a live `curl` check against the running XAMPP server at `http://localhost/ds3`, matching the Phase 3 plan's convention. Re-run `tests/test_dempster_shafer.php` / `tests/test_hitung_subskala.php` after any task touching shared controller code, even though this phase never touches the DS engine itself.

---

### Task 1: Capture the logged-in admin's own id at login

**Files:**
- Modify: `plogin.php:23-27`

- [ ] **Step 1: Add the missing `admin_id` session assignment**

Find:
```php
	if ($data['role']=="admin") {
		$_SESSION['username'] = $username;
		$_SESSION['role'] = "admin";
		header('location:admin/data.php'); //jika berhasil login, maka masuk ke file yang dituju
```
Replace with:
```php
	if ($data['role']=="admin") {
		$_SESSION['username'] = $username;
		$_SESSION['role'] = "admin";
		$_SESSION['admin_id'] = $data['id'];
		header('location:admin/data.php'); //jika berhasil login, maka masuk ke file yang dituju
```

- [ ] **Step 2: Syntax-check**

Run:
```bash
cd "C:\xampp\htdocs\ds3"
php -l plogin.php
```
Expected: `No syntax errors detected`.

- [ ] **Step 3: Live-verify**

Run:
```bash
cd /tmp
rm -f admincookies.txt
curl -s -c admincookies.txt -i -d "username=admin&password=admin" http://localhost/ds3/plogin.php | grep -i "set-cookie"
```
There's no direct way to read another process's `$_SESSION` from curl, so verify indirectly in Task 4's live check instead (the self-protection badge only renders correctly if this session value is present) - note that dependency here and move on.

- [ ] **Step 4: Commit**

```bash
cd "C:\xampp\htdocs\ds3"
git add plogin.php
git commit -m "fix(phase4): capture admin's own id in session on admin login (was only set for doctor logins)"
```

---

### Task 2: Controller changes — `AdminSemua()` + `UbahDokter()` bug fix

**Files:**
- Modify: `controller/c_Admin.php`

- [ ] **Step 1: Add `AdminSemua()`, mirroring the existing `DokterSemua()`**

Find:
```php
	function TambahDokter($name, $username, $password, $email, $phone, $role)
```
Replace with:
```php
	function AdminSemua()
	{
		include '../connection/connection.php';
		$query = mysqli_query($con, "SELECT * FROM admins where role = 'admin'");
		$i = 0;
		while($d = mysqli_fetch_array($query))
		{
			$data[$i]['admin_id'] = $d['id'];
			$data[$i]['username'] = $d['username'];
			$data[$i]['name'] = $d['name'];
			$data[$i]['email'] = $d['email'];
			$data[$i]['phone'] = $d['phone'];
			$i++;
		}
		return $data;
	}

	function TambahDokter($name, $username, $password, $email, $phone, $role)
```

- [ ] **Step 2: Fix `UbahDokter()`'s `phone='phone'` typo and escape every value**

Find:
```php
	function UbahDokter($admin_id, $name, $username, $password, $email, $phone)
	{
		include "../connection/connection.php";
		$query = mysqli_query($con, "UPDATE admins set name='$name',username='$username',password='$password',email='$email',phone='phone' WHERE id='$admin_id'");
	}
```
Replace with:
```php
	function UbahDokter($admin_id, $name, $username, $password, $email, $phone)
	{
		include "../connection/connection.php";
		$admin_id = (int)$admin_id;
		$name     = mysqli_real_escape_string($con, $name);
		$username = mysqli_real_escape_string($con, $username);
		$password = mysqli_real_escape_string($con, $password);
		$email    = mysqli_real_escape_string($con, $email);
		$phone    = mysqli_real_escape_string($con, $phone);
		$query = mysqli_query($con, "UPDATE admins set name='$name',username='$username',password='$password',email='$email',phone='$phone' WHERE id='$admin_id'");
	}
```

- [ ] **Step 3: Syntax-check**

Run:
```bash
cd "C:\xampp\htdocs\ds3"
php -l controller/c_Admin.php
```
Expected: `No syntax errors detected`.

- [ ] **Step 4: Live-verify the bug fix**

This is verified against a *doctor* record since `UbahDokter()` is role-agnostic and no
admin-edit UI exists yet at this point in the plan (that's Task 5). Pick any existing
doctor, edit their phone through the real form, confirm it sticks instead of becoming
the literal text "phone":

```bash
cd /tmp
DOCTOR_ID=$("C:\xampp\mysql\bin\mysql.exe" -u root spdempstershafer -sN -e "SELECT id FROM admins WHERE role='dokter' AND username='pakar' LIMIT 1;")
echo "DOCTOR_ID=$DOCTOR_ID"
curl -s -c admincookies.txt -d "username=admin&password=admin" http://localhost/ds3/plogin.php -o /dev/null
curl -s -b admincookies.txt -d "admin_id=$DOCTOR_ID&name=dr.+Azizman+Saad,+Sp.P+(K)&username=pakar&password=pakar&email=&phone=081234567890" http://localhost/ds3/process/edit_doctor.php -o /dev/null
"C:\xampp\mysql\bin\mysql.exe" -u root spdempstershafer -e "SELECT id, phone FROM admins WHERE id=$DOCTOR_ID;"
```
Expected: `phone` column shows `081234567890`, NOT the literal text `phone`.

- [ ] **Step 5: Re-run the DS engine regression tests**

Run:
```bash
cd "C:\xampp\htdocs\ds3"
php tests/test_dempster_shafer.php
php tests/test_hitung_subskala.php
```
Expected: `ALL TESTS PASSED` ×2.

- [ ] **Step 6: Commit**

```bash
cd "C:\xampp\htdocs\ds3"
git add controller/c_Admin.php
git commit -m "fix(phase4): add AdminSemua() listing method; fix UbahDokter() phone='phone' typo + escape all fields"
```

---

### Task 3: `admin/add_admin.php` — create-admin form

**Files:**
- Create: `admin/add_admin.php`

Structural copy of `admin/add_doctor.php`, submitting to the same existing
`process/add_doctor.php` (already role-agnostic - no new processor needed).

- [ ] **Step 1: Write the file**

```php
<?php include '_header.php';
?>		
		<!-- ============================================================== -->
		<!-- Page wrapper  -->
		<!-- ============================================================== -->
		<div class="page-wrapper">
			<!-- ============================================================== -->
			<!-- Bread crumb and right sidebar toggle -->
			<!-- ============================================================== -->
			<div class="page-breadcrumb">
				<h4 class="page-title"><?php echo isset($_SESSION['langArray']['tambah_admin']) ? htmlspecialchars($_SESSION['langArray']['tambah_admin']) : 'Tambah Admin'; ?></h4>
				<ol class="breadcrumb">
					<li class="breadcrumb-item"><a href="admins.php"><?php echo isset($_SESSION['langArray']['data_admin']) ? htmlspecialchars($_SESSION['langArray']['data_admin']) : 'Data Admin'; ?></a></li>
					<li class="breadcrumb-item active" aria-current="page"><?php echo isset($_SESSION['langArray']['tambah_admin']) ? htmlspecialchars($_SESSION['langArray']['tambah_admin']) : 'Tambah Admin'; ?></li>
				</ol>
			</div>
			<!-- ============================================================== -->
			<!-- End Bread crumb and right sidebar toggle -->
			<!-- ============================================================== -->
			<!-- ============================================================== -->
			<!-- Container fluid  -->
			<!-- ============================================================== -->
			<div class="container-fluid">
				<!-- ============================================================== -->
				<!-- Start Page Content -->
				<!-- ============================================================== -->
				<div class="row">
					<!-- Column -->
					<div class="col-lg-8 col-xlg-9 col-md-7">
						<div class="card">
							<div class="card-body">
								<form method="post" class="form-horizontal form-material" action="../process/add_doctor.php">
									<div class="form-group">
										<label class="col-md-12"><?php echo isset($_SESSION['langArray']['nama']) ? htmlspecialchars($_SESSION['langArray']['nama']) : 'Nama'; ?></label>
										<div class="col-md-12">
											<input type="text" class="form-control form-control-line" name="name" required="">
										</div>
									</div>
									<div class="form-group">
										<label class="col-md-12"><?php echo isset($_SESSION['langArray']['username']) ? htmlspecialchars($_SESSION['langArray']['username']) : 'Username'; ?></label>
										<div class="col-md-12">
											<input type="text" class="form-control form-control-line" name="username" required="">
										</div>
									</div>
									<div class="form-group">
										<label class="col-md-12"><?php echo isset($_SESSION['langArray']['password']) ? htmlspecialchars($_SESSION['langArray']['password']) : 'Password'; ?></label>
										<div class="col-md-12">
											<input type="text" class="form-control form-control-line" name="password" required="">
										</div>
									</div>
									<div class="form-group">
										<label class="col-md-12"><?php echo isset($_SESSION['langArray']['email']) ? htmlspecialchars($_SESSION['langArray']['email']) : 'Email'; ?></label>
										<div class="col-md-12">
											<input type="email" class="form-control form-control-line" name="email">
										</div>
									</div>
									<div class="form-group">
										<label class="col-md-12"><?php echo isset($_SESSION['langArray']['no_hp']) ? htmlspecialchars($_SESSION['langArray']['no_hp']) : 'No HP'; ?></label>
										<div class="col-md-12">
											<input type="number" class="form-control form-control-line" name="phone">
										</div>
									</div>

									<input type="hidden" value="admin" name="role">
									<div class="form-group">
										<div class="col-sm-12">
											<button class="btn btn-success" type="submit"><?php echo isset($_SESSION['langArray']['tambah_data']) ? htmlspecialchars($_SESSION['langArray']['tambah_data']) : 'Tambah Data'; ?></button>
										</div>
									</div>
								</form>
							</div>
						</div>
					</div>
					<!-- Column -->
				</div>
			</div>
		</div>
		<!-- ============================================================== -->
		<!-- End PAge Content -->
		<!-- ============================================================== -->
<?php include '_footer.php'; ?>
```

- [ ] **Step 2: Syntax-check**

Run:
```bash
cd "C:\xampp\htdocs\ds3"
php -l admin/add_admin.php
```
Expected: `No syntax errors detected`.

- [ ] **Step 3: Live-verify by actually creating an admin account**

```bash
cd /tmp
curl -s -b admincookies.txt http://localhost/ds3/admin/add_admin.php | grep -E "name=\"role\" value=\"admin\"|name=\"name\"|name=\"username\""
curl -s -b admincookies.txt -i -d "name=Second+Admin&username=admin2&password=admin2&email=&phone=&role=admin" http://localhost/ds3/process/add_doctor.php | grep -i "location"
"C:\xampp\mysql\bin\mysql.exe" -u root spdempstershafer -e "SELECT id, name, username, role FROM admins WHERE username='admin2';"
```
Expected: the form fields are present with `role` hardcoded to `admin`; the POST redirects to `../diagnosis.php` (that's `process/add_doctor.php`'s existing unconditional redirect target - a pre-existing quirk, not something this phase's scope covers fixing); the DB query shows the new row with `role='admin'`.

Then confirm the new admin account can actually log in:
```bash
curl -s -c admin2cookies.txt -i -d "username=admin2&password=admin2" http://localhost/ds3/plogin.php | grep -i "location"
```
Expected: `Location:admin/data.php` (successful admin login).

- [ ] **Step 4: Commit**

```bash
cd "C:\xampp\htdocs\ds3"
git add admin/add_admin.php
git commit -m "feat(phase4): add admin/add_admin.php - create-admin form reusing the existing generic processor"
```

---

### Task 4: `admin/admins.php` — admin list with self-protection

**Files:**
- Create: `admin/admins.php`

- [ ] **Step 1: Write the file**

```php
<?php include '_header.php';

include "../controller/c_Admin.php";
$p = new Admin;
$data = $p->AdminSemua();
$currentAdminId = (int)($_SESSION['admin_id'] ?? 0);
?>
<!-- ============================================================== -->
<!-- Page wrapper  -->
<!-- ============================================================== -->
<div class="page-wrapper">
    <!-- ============================================================== -->
    <!-- Bread crumb and right sidebar toggle -->
    <!-- ============================================================== -->
    <div class="page-breadcrumb">
        <h4 class="page-title"><?php echo isset($_SESSION['langArray']['data_admin']) ? htmlspecialchars($_SESSION['langArray']['data_admin']) : 'Data Admin'; ?></h4>
        <a href="add_admin.php" class="btn btn-danger text-white"><i class="mdi mdi-plus"></i> <?php echo isset($_SESSION['langArray']['tambah_admin']) ? htmlspecialchars($_SESSION['langArray']['tambah_admin']) : 'Tambah Admin'; ?></a>
    </div>
    <!-- ============================================================== -->
    <!-- End Bread crumb and right sidebar toggle -->
    <!-- ============================================================== -->
    <!-- ============================================================== -->
    <!-- Container fluid  -->
    <!-- ============================================================== -->
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="bootstrap-data-table" class="table table-hover table-bordered">
                                <thead style="background-color: #336699; color: #ffffff;">
                                  <tr>
                                    <th style="color: white;" width="5%">No</th>
                                    <th style="color: white;"><?php echo isset($_SESSION['langArray']['nama']) ? htmlspecialchars($_SESSION['langArray']['nama']) : 'Nama'; ?></th>
                                    <th style="color: white;"><?php echo isset($_SESSION['langArray']['username']) ? htmlspecialchars($_SESSION['langArray']['username']) : 'Username'; ?></th>
                                    <th style="color: white;"><?php echo isset($_SESSION['langArray']['no_hp']) ? htmlspecialchars($_SESSION['langArray']['no_hp']) : 'No Hp'; ?></th>
                                    <th style="color: white;"><?php echo isset($_SESSION['langArray']['aksi']) ? htmlspecialchars($_SESSION['langArray']['aksi']) : 'Aksi'; ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                if (!isset($data)) {
                                    ?>
                                    <tr>
                                        <td></td><td></td><td></td><td></td><td></td>
                                    </tr>
                                    <?php
                                } else {
                                    $i=0;
                                foreach($data as $d){
                                    $i++;
                                    ?>
                                    <tr>
                                        <td><?php print $i; ?></td>
                                        <td><?php print htmlspecialchars($d['name']); ?></td>
                                        <td><?php print htmlspecialchars($d['username']); ?></td>
                                        <td><?php print htmlspecialchars($d['phone']); ?></td>
                                        <td>
                                            <?php if ((int)$d['admin_id'] === $currentAdminId): ?>
                                              <span class="badge bg-secondary"><?php echo isset($_SESSION['langArray']['akun_anda']) ? htmlspecialchars($_SESSION['langArray']['akun_anda']) : 'Akun Anda'; ?></span>
                                            <?php else: ?>
                                              <a href="edit_admin.php?admin_id=<?php print $d['admin_id']; ?>" class="btn btn-info btn-simple btn-xs text-white" title="Edit"><i class="mdi mdi-lead-pencil"></i></a>
                                              <a onclick="if (! confirm('Apakah anda yakin akan menghapus Admin dari daftar ?')) { return false; }" href="../process/delete_admin.php?admin_id=<?php print $d['admin_id']; ?>" class="btn btn-danger btn-simple btn-xs text-white" title="Hapus"><i class="fa fa-times"></i></a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php }} ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include '_footer.php'; ?>
```

- [ ] **Step 2: Syntax-check**

Run:
```bash
cd "C:\xampp\htdocs\ds3"
php -l admin/admins.php
```
Expected: `No syntax errors detected`.

- [ ] **Step 3: Live-verify**

```bash
cd /tmp
curl -s -b admincookies.txt http://localhost/ds3/admin/admins.php | grep -E "Second Admin|admin2|Akun Anda|edit_admin.php|delete_admin.php"
```
Expected: `Second Admin`/`admin2` (the account created in Task 3) appear WITH edit/delete
links (it's not the logged-in session's own account); if the original `admin`/`admin`
account also appears in this list (it should, since it has `role='admin'` too), its row
shows `Akun Anda` instead of edit/delete links - since `admincookies.txt` is logged in
as `admin`.

- [ ] **Step 4: Commit**

```bash
cd "C:\xampp\htdocs\ds3"
git add admin/admins.php
git commit -m "feat(phase4): add admin/admins.php - admin account list with self-row protection"
```

---

### Task 5: `admin/edit_admin.php` + shared processor self-edit guard

**Files:**
- Create: `admin/edit_admin.php`
- Modify: `process/edit_doctor.php`

- [ ] **Step 1: Write `admin/edit_admin.php`**

Structural copy of `admin/edit_doctor.php` with a `return_to` hidden field added so the
shared processor (modified in Step 2) redirects back to `admins.php` instead of its
current hardcoded `doctors.php`:

```php
<?php include '_header.php';

include "../controller/c_Admin.php";
$g = new Admin;
$g->TampilDataAdmin($_GET['admin_id']);
?>		
		<!-- ============================================================== -->
		<!-- Page wrapper  -->
		<!-- ============================================================== -->
		<div class="page-wrapper">
			<!-- ============================================================== -->
			<!-- Bread crumb and right sidebar toggle -->
			<!-- ============================================================== -->
			<div class="page-breadcrumb">
				<h4 class="page-title"><?php echo isset($_SESSION['langArray']['manajemen_ubah_admin']) ? htmlspecialchars($_SESSION['langArray']['manajemen_ubah_admin']) : 'Manajemen Ubah Data Admin'; ?></h4>
				<ol class="breadcrumb">
					<li class="breadcrumb-item"><a href="admins.php"><?php echo isset($_SESSION['langArray']['data_admin']) ? htmlspecialchars($_SESSION['langArray']['data_admin']) : 'Data Admin'; ?></a></li>
					<li class="breadcrumb-item active" aria-current="page"><?php echo isset($_SESSION['langArray']['manajemen_ubah_admin']) ? htmlspecialchars($_SESSION['langArray']['manajemen_ubah_admin']) : 'Ubah Data Admin'; ?></li>
				</ol>
			</div>
			<!-- ============================================================== -->
			<!-- End Bread crumb and right sidebar toggle -->
			<!-- ============================================================== -->
			<!-- ============================================================== -->
			<!-- Container fluid  -->
			<!-- ============================================================== -->
			<div class="container-fluid">
				<div class="row">
					<!-- Column -->
					<div class="col-lg-8 col-xlg-9 col-md-7">
						<div class="card">
							<div class="card-body">
								<form method="post" class="form-horizontal form-material" action="../process/edit_doctor.php">
									<div class="form-group">
										<input type="hidden" value="<?php print $_GET['admin_id'] ?>" name="admin_id" />
										<input type="hidden" value="admins.php" name="return_to" />
										<label class="col-md-12"><?php echo isset($_SESSION['langArray']['nama']) ? htmlspecialchars($_SESSION['langArray']['nama']) : 'Nama'; ?></label>
										<div class="col-md-12">
											<input type="text" value="<?php print $g->name; ?>" class="form-control form-control-line" name="name" required="">
										</div>
									</div>
									<div class="form-group">
										<label class="col-md-12"><?php echo isset($_SESSION['langArray']['username']) ? htmlspecialchars($_SESSION['langArray']['username']) : 'Username'; ?></label>
										<div class="col-md-12">
											<input type="text" value="<?php print $g->username; ?>" class="form-control form-control-line" name="username" required="">
										</div>
									</div>
									<div class="form-group">
										<label class="col-md-12"><?php echo isset($_SESSION['langArray']['password']) ? htmlspecialchars($_SESSION['langArray']['password']) : 'Password'; ?></label>
										<div class="col-md-12">
											<input type="text" value="<?php print $g->password; ?>" class="form-control form-control-line" name="password" required="">
										</div>
									</div>
									<div class="form-group">
										<label class="col-md-12"><?php echo isset($_SESSION['langArray']['email']) ? htmlspecialchars($_SESSION['langArray']['email']) : 'Email'; ?></label>
										<div class="col-md-12">
											<input type="email" value="<?php print $g->email; ?>" class="form-control form-control-line" name="email">
										</div>
									</div>
									<div class="form-group">
										<label class="col-md-12"><?php echo isset($_SESSION['langArray']['no_hp']) ? htmlspecialchars($_SESSION['langArray']['no_hp']) : 'No HP'; ?></label>
										<div class="col-md-12">
											<input type="number" value="<?php print $g->phone; ?>" class="form-control form-control-line" name="phone">
										</div>
									</div>

									<div class="form-group">
										<div class="col-sm-12">
											<button class="btn btn-success" type="submit"><?php echo isset($_SESSION['langArray']['ubah_data']) ? htmlspecialchars($_SESSION['langArray']['ubah_data']) : 'Ubah Data'; ?></button>
										</div>
									</div>
								</form>
							</div>
						</div>
					</div>
					<!-- Column -->
				</div>
			</div>
		</div>
<?php include '_footer.php'; ?>
```

- [ ] **Step 2: Add the self-edit guard + dynamic redirect to the shared processor**

Find (the entire current file):
```php
<?php
include '../controller/c_Admin.php';
$admin_id = $_POST['admin_id'];
$name = $_POST['name'];
$username = $_POST['username'];
$password = $_POST['password'];
$email = $_POST['email'];
$phone = $_POST['phone'];

$update = new Admin;
$update->UbahDokter($admin_id, $name, $username, $password, $email, $phone);

header('location: ../admin/doctors.php');
?>
```
Replace with:
```php
<?php
session_start();
include '../controller/c_Admin.php';

$admin_id = (int)($_POST['admin_id'] ?? 0);
$currentAdminId = (int)($_SESSION['admin_id'] ?? 0);

if ($admin_id > 0 && $admin_id !== $currentAdminId) {
    $name = $_POST['name'];
    $username = $_POST['username'];
    $password = $_POST['password'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];

    $update = new Admin;
    $update->UbahDokter($admin_id, $name, $username, $password, $email, $phone);
}
// Self-edit attempts (or a missing id) silently no-op, same no-error-surfaced style
// as the rest of this app's process/ scripts.

$returnTo = ($_POST['return_to'] ?? 'doctors.php') === 'admins.php' ? 'admins.php' : 'doctors.php';
header('location: ../admin/' . $returnTo);
?>
```

- [ ] **Step 3: Syntax-check**

Run:
```bash
cd "C:\xampp\htdocs\ds3"
php -l admin/edit_admin.php && php -l process/edit_doctor.php
```
Expected: `No syntax errors detected` ×2.

- [ ] **Step 4: Live-verify both the normal edit path and the self-edit block**

First, confirm editing a *different* admin still works (using `admin2` from Task 3):
```bash
cd /tmp
ADMIN2_ID=$("C:\xampp\mysql\bin\mysql.exe" -u root spdempstershafer -sN -e "SELECT id FROM admins WHERE username='admin2';")
echo "ADMIN2_ID=$ADMIN2_ID"
curl -s -b admincookies.txt -i -d "admin_id=$ADMIN2_ID&name=Second+Admin+Edited&username=admin2&password=admin2&email=&phone=&return_to=admins.php" http://localhost/ds3/process/edit_doctor.php | grep -i "location"
"C:\xampp\mysql\bin\mysql.exe" -u root spdempstershafer -e "SELECT id, name FROM admins WHERE id=$ADMIN2_ID;"
```
Expected: `Location: ../admin/admins.php` (return_to honored), and `name` is now
`Second Admin Edited`.

Then confirm self-edit is blocked (using the logged-in `admin` account, id 1):
```bash
SELF_ID=$("C:\xampp\mysql\bin\mysql.exe" -u root spdempstershafer -sN -e "SELECT id FROM admins WHERE username='admin' LIMIT 1;")
echo "SELF_ID=$SELF_ID"
"C:\xampp\mysql\bin\mysql.exe" -u root spdempstershafer -e "SELECT id, name FROM admins WHERE id=$SELF_ID;"
curl -s -b admincookies.txt -i -d "admin_id=$SELF_ID&name=HACKED&username=admin&password=admin&email=&phone=&return_to=admins.php" http://localhost/ds3/process/edit_doctor.php -o /dev/null
"C:\xampp\mysql\bin\mysql.exe" -u root spdempstershafer -e "SELECT id, name FROM admins WHERE id=$SELF_ID;"
```
Expected: the `name` column is IDENTICAL before and after this request (the attempted
self-edit to "HACKED" was silently ignored).

- [ ] **Step 5: Re-run the DS engine regression tests**

Run:
```bash
cd "C:\xampp\htdocs\ds3"
php tests/test_dempster_shafer.php
php tests/test_hitung_subskala.php
```
Expected: `ALL TESTS PASSED` ×2.

- [ ] **Step 6: Commit**

```bash
cd "C:\xampp\htdocs\ds3"
git add admin/edit_admin.php process/edit_doctor.php
git commit -m "feat(phase4): add admin/edit_admin.php; add self-edit guard + return_to redirect to shared process/edit_doctor.php"
```

---

### Task 6: `process/delete_admin.php` — delete with self-delete guard

**Files:**
- Create: `process/delete_admin.php`

- [ ] **Step 1: Write the file**

```php
<?php
session_start();
include '../controller/c_Admin.php';

$admin_id = (int)($_GET['admin_id'] ?? 0);
$currentAdminId = (int)($_SESSION['admin_id'] ?? 0);

if ($admin_id > 0 && $admin_id !== $currentAdminId) {
    $hapus = new Admin;
    $hapus->HapusDokter($admin_id);
}
// Self-delete attempts (or a missing id) silently no-op, same style as the existing
// delete_doctor.php, which also redirects either way without surfacing an error state.
header('location: ../admin/admins.php');
```

- [ ] **Step 2: Syntax-check**

Run:
```bash
cd "C:\xampp\htdocs\ds3"
php -l process/delete_admin.php
```
Expected: `No syntax errors detected`.

- [ ] **Step 3: Live-verify both the self-delete block and a normal delete**

```bash
cd /tmp
SELF_ID=$("C:\xampp\mysql\bin\mysql.exe" -u root spdempstershafer -sN -e "SELECT id FROM admins WHERE username='admin' LIMIT 1;")
curl -s -b admincookies.txt "http://localhost/ds3/process/delete_admin.php?admin_id=$SELF_ID" -o /dev/null
"C:\xampp\mysql\bin\mysql.exe" -u root spdempstershafer -e "SELECT COUNT(*) AS self_still_exists FROM admins WHERE id=$SELF_ID;"
```
Expected: `self_still_exists` is `1` (the logged-in account's self-delete attempt was
ignored).

Then delete the real test account (`admin2` from Task 3) and confirm it's gone:
```bash
ADMIN2_ID=$("C:\xampp\mysql\bin\mysql.exe" -u root spdempstershafer -sN -e "SELECT id FROM admins WHERE username='admin2';")
curl -s -b admincookies.txt -i "http://localhost/ds3/process/delete_admin.php?admin_id=$ADMIN2_ID" | grep -i "location"
"C:\xampp\mysql\bin\mysql.exe" -u root spdempstershafer -e "SELECT COUNT(*) AS admin2_gone FROM admins WHERE id=$ADMIN2_ID;"
```
Expected: `Location: ../admin/admins.php`; `admin2_gone` is `0`.

- [ ] **Step 4: Commit**

```bash
cd "C:\xampp\htdocs\ds3"
git add process/delete_admin.php
git commit -m "feat(phase4): add process/delete_admin.php with server-side self-delete guard"
```

---

### Task 7: Fix `admin/doctors.php`'s swapped header labels

**Files:**
- Modify: `admin/doctors.php:33-40`

- [ ] **Step 1: Fix the header row**

Find:
```php
                                <thead style="background-color: #336699; color: #ffffff;">
                                  <tr>
                                    <th style="color: white;" width="5%">No</th>
                                    <th style="color: white;"><?php echo isset($_SESSION['langArray']['jurusan']) ? htmlspecialchars($_SESSION['langArray']['jurusan']) : 'Jurusan'; ?></th>
                                    <th style="color: white;"><?php echo isset($_SESSION['langArray']['nama']) ? htmlspecialchars($_SESSION['langArray']['nama']) : 'Nama'; ?></th>
                                    <!--<th style="color: white;">Password</th>
                                    <th style="color: white;">Email</th>-->
                                    <th style="color: white;"><?php echo isset($_SESSION['langArray']['no_hp']) ? htmlspecialchars($_SESSION['langArray']['no_hp']) : 'No Hp'; ?></th>
                                    <th style="color: white;"><?php echo isset($_SESSION['langArray']['aksi']) ? htmlspecialchars($_SESSION['langArray']['aksi']) : 'Aksi'; ?></th>
                                </tr>
                            </thead>
```
Replace with:
```php
                                <thead style="background-color: #336699; color: #ffffff;">
                                  <tr>
                                    <th style="color: white;" width="5%">No</th>
                                    <th style="color: white;"><?php echo isset($_SESSION['langArray']['nama']) ? htmlspecialchars($_SESSION['langArray']['nama']) : 'Nama'; ?></th>
                                    <th style="color: white;"><?php echo isset($_SESSION['langArray']['username']) ? htmlspecialchars($_SESSION['langArray']['username']) : 'Username'; ?></th>
                                    <!--<th style="color: white;">Password</th>
                                    <th style="color: white;">Email</th>-->
                                    <th style="color: white;"><?php echo isset($_SESSION['langArray']['no_hp']) ? htmlspecialchars($_SESSION['langArray']['no_hp']) : 'No Hp'; ?></th>
                                    <th style="color: white;"><?php echo isset($_SESSION['langArray']['aksi']) ? htmlspecialchars($_SESSION['langArray']['aksi']) : 'Aksi'; ?></th>
                                </tr>
                            </thead>
```

(The body row already prints `$d['name']` then `$d['username']` then `$d['phone']` -
unchanged. Only the header labels were wrong; this makes them match what's actually
displayed underneath.)

- [ ] **Step 2: Syntax-check**

Run:
```bash
cd "C:\xampp\htdocs\ds3"
php -l admin/doctors.php
```
Expected: `No syntax errors detected`.

- [ ] **Step 3: Live-verify**

Run:
```bash
cd /tmp
curl -s -b admincookies.txt http://localhost/ds3/admin/doctors.php | grep -B1 -A1 "th style=\"color: white;\""
```
Expected: header order reads No / Nama / Username / No Hp / Aksi (no more "Jurusan").

- [ ] **Step 4: Commit**

```bash
cd "C:\xampp\htdocs\ds3"
git add admin/doctors.php
git commit -m "fix(phase4): correct swapped Jurusan/Nama table headers on admin/doctors.php to Nama/Username"
```

---

### Task 8: Sidebar link + new language keys

**Files:**
- Modify: `admin/_header.php:91-95`
- Modify: `lang/id.php`, `lang/en.php`, `lang/tr.php`, `lang/zh.php`

- [ ] **Step 1: Add the "Data Admin" sidebar link, right after the existing doctors link**

Find:
```php
    <a href="doctors.php"
       class="sidebar-link <?php echo adminNavActive($_curPage,'doctor'); ?>">
      <i class="mdi mdi-account-multiple-outline"></i>
      <?php echo isset($_SESSION['langArray']['data_user']) ? htmlspecialchars($_SESSION['langArray']['data_user']) : 'Data User'; ?>
    </a>
    <a href="profile.php"
```
Replace with:
```php
    <a href="doctors.php"
       class="sidebar-link <?php echo adminNavActive($_curPage,'doctor'); ?>">
      <i class="mdi mdi-account-multiple-outline"></i>
      <?php echo isset($_SESSION['langArray']['data_user']) ? htmlspecialchars($_SESSION['langArray']['data_user']) : 'Data User'; ?>
    </a>
    <a href="admins.php"
       class="sidebar-link <?php echo adminNavActive($_curPage,'admin'); ?>">
      <i class="mdi mdi-shield-account-outline"></i>
      <?php echo isset($_SESSION['langArray']['data_admin']) ? htmlspecialchars($_SESSION['langArray']['data_admin']) : 'Data Admin'; ?>
    </a>
    <a href="profile.php"
```

(`adminNavActive()` does a substring match on the current page filename against the
keyword - `'admin'` matches `admins.php`, `add_admin.php`, and `edit_admin.php`, and no
other existing admin-panel filename contains that substring, so this highlights the
sidebar link correctly on all three pages without also lighting up on unrelated pages.)

- [ ] **Step 2: Add the 4 new language keys to all 4 lang files**

Find in `lang/id.php`:
```php
    'mulai_diagnosa' => 'Mulai Diagnosa',
];
```
Replace with:
```php
    'mulai_diagnosa' => 'Mulai Diagnosa',
    'tambah_admin' => 'Tambah Admin',
    'data_admin' => 'Data Admin',
    'manajemen_ubah_admin' => 'Manajemen Ubah Data Admin',
    'akun_anda' => 'Akun Anda',
];
```

Find in `lang/en.php`:
```php
    'mulai_diagnosa' => 'Start Diagnosis',
];
```
Replace with:
```php
    'mulai_diagnosa' => 'Start Diagnosis',
    'tambah_admin' => 'Add Admin',
    'data_admin' => 'Admin Data',
    'manajemen_ubah_admin' => 'Edit Admin Data',
    'akun_anda' => 'Your Account',
];
```

Find in `lang/tr.php`:
```php
    'mulai_diagnosa' => 'Teşhise Başla',
];
```
Replace with:
```php
    'mulai_diagnosa' => 'Teşhise Başla',
    'tambah_admin' => 'Yönetici Ekle',
    'data_admin' => 'Yönetici Verileri',
    'manajemen_ubah_admin' => 'Yönetici Verilerini Düzenle',
    'akun_anda' => 'Hesabınız',
];
```

Find in `lang/zh.php`:
```php
    'mulai_diagnosa' => '开始诊断',
];
```
Replace with:
```php
    'mulai_diagnosa' => '开始诊断',
    'tambah_admin' => '添加管理员',
    'data_admin' => '管理员数据',
    'manajemen_ubah_admin' => '编辑管理员数据',
    'akun_anda' => '您的账户',
];
```

- [ ] **Step 3: Syntax-check**

Run:
```bash
cd "C:\xampp\htdocs\ds3"
php -l admin/_header.php && php -l lang/id.php && php -l lang/en.php && php -l lang/tr.php && php -l lang/zh.php
```
Expected: `No syntax errors detected` ×5.

- [ ] **Step 4: Live-verify the sidebar link and its active-state highlighting**

```bash
cd /tmp
curl -s -b admincookies.txt http://localhost/ds3/admin/admins.php | grep -B2 "Data Admin"
```
Expected: the `admins.php` link's `class="sidebar-link active"` (highlighted, since
we're currently on that exact page) and its label text "Data Admin".

- [ ] **Step 5: Commit**

```bash
cd "C:\xampp\htdocs\ds3"
git add admin/_header.php lang/id.php lang/en.php lang/tr.php lang/zh.php
git commit -m "feat(phase4): add Data Admin sidebar link and language keys"
```

---

### Task 9: Final regression pass + two-repo sync

**Files:** none (verification + sync only)

- [ ] **Step 1: Re-run the DS engine regression tests one final time**

Run:
```bash
cd "C:\xampp\htdocs\ds3"
php tests/test_dempster_shafer.php
php tests/test_hitung_subskala.php
```
Expected: `ALL TESTS PASSED` ×2.

- [ ] **Step 2: Full end-to-end manual walkthrough in Turkish (matches how Phase 3's fixes were caught - verify in a non-default language too, not just Indonesian)**

```bash
cd /tmp
curl -s -b admincookies.txt "http://localhost/ds3/set_language.php?lang=tr" -o /dev/null
curl -s -b admincookies.txt http://localhost/ds3/admin/admins.php | grep -E "Yönetici Verileri|Yönetici Ekle|Hesabınız"
curl -s -b admincookies.txt http://localhost/ds3/admin/add_admin.php | grep -E "Yönetici Ekle"
curl -s -b admincookies.txt "http://localhost/ds3/set_language.php?lang=id" -o /dev/null
```
Expected: all 4 grep matches found (Turkish labels render correctly on both pages);
final curl resets the session back to Indonesian so it doesn't leak into whatever the
user does next in the browser.

- [ ] **Step 3: Sync to the Documents repo**

Run (PowerShell):
```powershell
$src = "C:\xampp\htdocs\ds3"
$dst = "C:\Users\lenovo\Documents\Yüksek Lisans\Mental Health\Indonesia Application\ds3"
robocopy $src $dst /E /XD ".git" /XF "*.git*" /NFL /NDL /NP
```
Expected: exit code 3 (files copied, no failures).

- [ ] **Step 4: Commit the plan file itself and sync commit in the Documents repo**

```bash
cd "C:\xampp\htdocs\ds3"
git add docs/superpowers/plans/2026-10-01-phase4-admin-management.md
git commit -m "docs(phase4): add implementation plan"

cd "C:\Users\lenovo\Documents\Yüksek Lisans\Mental Health\Indonesia Application\ds3"
git add -A
git commit -m "feat(phase4): admin account management (full Phase 4 feature)

Squash-equivalent sync of all Phase 4 commits from the htdocs repo -
see that repo's history for the individual per-task commits:
- plogin.php now captures \$_SESSION['admin_id'] for admin logins too
  (was only set for doctor logins), needed for self-protection checks
- controller/c_Admin.php: new AdminSemua() listing method; UbahDokter()
  phone='phone' typo fixed + all fields now escaped
- admin/add_admin.php, admin/admins.php, admin/edit_admin.php (new) -
  mirror the existing doctor-management pages, reusing the same
  already-generic processors (process/add_doctor.php, process/edit_doctor.php)
- process/edit_doctor.php gained a self-edit guard + return_to-based
  redirect (shared safely with the existing doctor-edit flow, since a
  doctor's id never equals the logged-in admin's own id)
- process/delete_admin.php (new) - self-delete guard, server-enforced
- admin/doctors.php's swapped Jurusan/Nama table headers corrected to
  Nama/Username (matches what was already being displayed underneath)
- New sidebar link + language keys (id/en/tr/zh)

Co-Authored-By: Claude Sonnet 5 <noreply@anthropic.com>"
```

**Do not push to GitHub** — matches the standing instruction for this project (push only
on explicit request).

---

## Self-review notes (for whoever executes this plan)

- Every task's code blocks are complete, copy-pasteable PHP — no `// TODO` or "similar
  to Task N" placeholders.
- `TambahDokter()`, `HapusDokter()`, `TampilDataAdmin()` are never modified - they were
  already role-agnostic. Only `UbahDokter()` needed a fix (the `phone='phone'` typo),
  done in Task 2.
- The self-edit guard lives in the shared `process/edit_doctor.php` (Task 5), not
  duplicated into a separate admin-only processor, because the two callers
  (`admin/edit_doctor.php` editing a doctor, `admin/edit_admin.php` editing an admin)
  can safely share one guard: the condition only ever trips when the target id equals
  the *currently logged in admin's own* id, which structurally can't happen when an
  admin is editing a doctor's record.
- `doctor/profile.php` → `process/edit_doctor_profile.php` (a doctor editing their own
  profile) is a completely separate file pair, untouched by this plan - confirmed by
  reading its form action during the design phase, not assumed.
