# Phase 4: Admin Account Management Design

## Goal

Let a logged-in admin create additional admin accounts (currently impossible — only one
admin account exists, created outside the app). Doctor-account creation already works
(`admin/add_doctor.php`); this phase mirrors that pattern for `role='admin'` and adds a
list/edit/delete flow with a self-protection rule so an admin can't lock themselves out.

## Background / current state

`controller/c_Admin.php::TambahDokter($name, $username, $password, $email, $phone,
$role)` already accepts `$role` as a parameter and uses a prepared statement — despite
its name, it's not doctor-specific. `admin/add_doctor.php`'s form hardcodes
`<input type="hidden" name="role" value="dokter">`; nothing currently calls it with
`role='admin'`. Same story for `UbahDokter()` (edit) and `HapusDokter()` (delete) — both
operate on any `admins` row by id regardless of role.

Two pre-existing bugs live in code this phase touches, fixed as part of this work (user
approved during brainstorming):
1. `admin/doctors.php`'s table header row reads `Jurusan | Nama | No Hp | Aksi` but the
   row data underneath is `$d['name'] | $d['username'] | $d['phone']` — the first two
   headers are swapped relative to what they actually display.
2. `controller/c_Admin.php::UbahDokter()` runs
   `UPDATE admins SET ... phone='phone' WHERE id='$admin_id'` — the literal string
   `'phone'`, not the `$phone` variable — so every doctor edit silently overwrites their
   phone number with the text "phone".

`$_SESSION['admin_id']` is currently only set on the **doctor** login branch in
`plogin.php` (used to scope `patients` to the logged-in doctor). The admin login branch
never sets it. This phase's self-protection rule needs the logged-in admin's own id, so
`plogin.php` gains one line setting it on the admin branch too.

## Scope

1. Admin can create a new admin account.
2. Admin can see a list of all admin accounts, edit them, and delete them — except their
   own account, which is protected from both edit and delete (enforced server-side, not
   just hidden in the UI).
3. Fix the two bugs above.

Out of scope (not requested): password hashing (every account in this app — existing
and new — stores/compares plaintext passwords; changing that touches login.php and every
existing account's stored password, a separate and much larger concern than this phase);
auth guards on `process/edit_doctor.php` style endpoints (pre-existing gap, not
introduced or worsened here); role-based permission tiers beyond admin/dokter (no "super
admin" concept requested).

## Design

### 1. `plogin.php` — capture the logged-in admin's own id

In the `role=="admin"` branch, add the same line the `dokter` branch already has:

```php
if ($data['role']=="admin") {
    $_SESSION['username'] = $username;
    $_SESSION['role'] = "admin";
    $_SESSION['admin_id'] = $data['id'];
    header('location:admin/data.php');
}
```

### 2. `controller/c_Admin.php` — new list method + bug fixes

New method, mirroring `DokterSemua()`:

```php
function AdminSemua()
{
    include '../connection/connection.php';
    $query = mysqli_query($con, "SELECT * FROM admins where role = 'admin'");
    $i = 0;
    while ($d = mysqli_fetch_array($query)) {
        $data[$i]['admin_id'] = $d['id'];
        $data[$i]['username'] = $d['username'];
        $data[$i]['name'] = $d['name'];
        $data[$i]['email'] = $d['email'];
        $data[$i]['phone'] = $d['phone'];
        $i++;
    }
    return $data;
}
```

`UbahDokter()` bug fix — escape every value and fix the `phone='phone'` typo:

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

`TambahDokter()` and `HapusDokter()` are unchanged — both already work for any role.

### 3. `admin/add_admin.php` (new) — create form

Exact structural copy of `admin/add_doctor.php`: same fields (Nama, Username, Password,
Email, No HP), same target (`../process/add_doctor.php` — reused as-is, no new
processor needed since it already passes `$_POST['role']` straight through). Only
differences: page title/breadcrumb text, and the hidden role field:

```html
<input type="hidden" value="admin" name="role">
```

### 4. `admin/admins.php` (new) — "Data Admin" list

Mirrors `admin/doctors.php`'s table structure but calls `AdminSemua()` and uses correct
headers (Nama, Username, No HP, Aksi — no "Jurusan" mislabel repeated here). For each
row:

```php
<?php if ((int)$d['admin_id'] === (int)($_SESSION['admin_id'] ?? 0)): ?>
  <span class="badge bg-secondary"><?php echo isset($_SESSION['langArray']['akun_anda']) ? htmlspecialchars($_SESSION['langArray']['akun_anda']) : 'Akun Anda'; ?></span>
<?php else: ?>
  <a href="edit_admin.php?admin_id=<?php print $d['admin_id']; ?>" class="btn btn-info btn-simple btn-xs text-white" title="Edit"><i class="mdi mdi-lead-pencil"></i></a>
  <a onclick="if (! confirm('...')) { return false; }" href="../process/delete_admin.php?admin_id=<?php print $d['admin_id']; ?>" class="btn btn-danger btn-simple btn-xs text-white" title="Hapus"><i class="fa fa-times"></i></a>
<?php endif; ?>
```

Includes a "Tambah Admin" button linking to `add_admin.php`, same placement pattern as
other list pages' add buttons this project already uses (e.g. `doctor/patients.php`'s
"Tambah Pasien").

### 5. `admin/edit_admin.php` (new) — edit form

Structural copy of `admin/edit_doctor.php`, submitting to the existing
`../process/edit_doctor.php` (reused as-is — `UbahDokter()` doesn't care about role).
Only differences: labels/breadcrumb text and the back-link target (`admins.php` instead
of `doctors.php`).

The approved rule blocks both edit AND delete of one's own account, so
`process/edit_doctor.php` itself gains a self-edit guard (this is safe to add to the
shared processor: it's only ever reached via `admin/edit_doctor.php` — editing a
*doctor*, whose id never equals the logged-in admin's own id — or via
`admin/edit_admin.php`, the one path where the target id could legitimately match the
session's `admin_id`; a doctor editing their own profile goes through the separate
`doctor/profile.php` → `process/edit_doctor_profile.php`, untouched by this change):

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

header('location: ../admin/doctors.php');
?>
```

Redirecting to `doctors.php` unconditionally (its current behavior) is slightly wrong
when the edit came from `admin/edit_admin.php` - fixed in the same edit: redirect target
is chosen from a posted hidden field instead of being hardcoded.

```php
header('location: ../admin/' . (($_POST['return_to'] ?? 'doctors.php') === 'admins.php' ? 'admins.php' : 'doctors.php'));
```

`admin/edit_admin.php`'s form includes `<input type="hidden" name="return_to" value="admins.php">`;
`admin/edit_doctor.php`'s form is unchanged (no `return_to` field posted, so it falls
back to `doctors.php` exactly as today).

### 6. `process/delete_admin.php` (new) — delete with server-side self-protection

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
// Silently no-ops on self-delete attempts or missing id - same style as the existing
// delete_doctor.php, which also redirects either way without surfacing an error state.
header('location: ../admin/admins.php');
```

### 7. Sidebar (`admin/_header.php`)

Add a "Data Admin" link to `admins.php`, placed next to the existing "Data User"
(doctors) link, using a new dedicated lang key (not reusing `data_user`, which the 1
Sept audit already flagged as over-reused).

### 8. New language keys (id/en/tr/zh)

`tambah_admin`, `data_admin`, `manajemen_ubah_admin`, `akun_anda` (the "this is you,
can't edit/delete" badge text).

## Testing

Same convention as every prior phase: `php -l` per file, live `curl` walkthrough
(create an admin account through the real form, confirm it can log in, confirm it
appears in the list, confirm its own row has no edit/delete buttons when viewed as
itself, confirm `process/delete_admin.php?admin_id=<self>` is a no-op, confirm deleting
a *different* admin account works), plus re-running
`tests/test_dempster_shafer.php`/`tests/test_hitung_subskala.php` since this phase
touches shared controller code, even though nothing here changes the DS engine itself.
