<?php
require_once __DIR__ . '/../config/functions.php';

if (is_logged_in()) {
    $role = current_role();
    redirect($role === 'user' ? '/user/dashboard.php' : ($role === 'hospital' ? '/admin/dashboard.php' : '/super-admin/dashboard.php'));
}

$role = in_array($_GET['role'] ?? '', ['user', 'hospital']) ? $_GET['role'] : 'user';
$errors = [];
$hospitalSubmitted = false;
$submittedHospName   = '';
$submittedAdminEmail = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $role     = ($_POST['role'] ?? '') === 'hospital' ? 'hospital' : 'user';
    $name     = trim($_POST['name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    /* ---- basic validation ---- */
    if ($name === '') {
        $errors[] = 'Full name is required.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }
    if (strlen($password) < 6) {
        $errors[] = 'Password must be at least 6 characters.';
    }

    /* ---- duplicate email check ---- */
    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $errors[] = 'An account with that email already exists.';
        }
    }

    /* ---- patient-specific validation ---- */
    if ($role === 'user' && empty($errors)) {
        $phone     = trim($_POST['phone'] ?? '');
        $blood     = trim($_POST['blood_group'] ?? '');
        $address   = trim($_POST['address'] ?? '');
        $emergency = trim($_POST['emergency'] ?? '');

        if ($phone !== '' && !validate_phone($phone)) {
            $errors[] = 'Please enter a valid 10-digit primary phone number.';
        }
        if ($emergency !== '' && !validate_phone($emergency)) {
            $errors[] = 'Please enter a valid 10-digit emergency contact number.';
        }
        if ($phone !== '' && $emergency !== '' && $phone === $emergency) {
            $errors[] = 'Emergency contact number must be different from your primary phone number.';
        }
        if ($blood !== '' && !validate_blood_group($blood)) {
            $errors[] = 'Invalid blood group selected. Valid options: A+, A-, B+, B-, AB+, AB-, O+, O-.';
        }
    }

    /* ---- hospital-specific fields ---- */
    $hospName = $city = $type = $specs = $licenseFileName = '';
    $coverPhotoName = '';
    $coverPhotoTmp  = null;

    if ($role === 'hospital' && empty($errors)) {
        $hospName = trim($_POST['hospital_name'] ?? '');
        $city     = trim($_POST['city'] ?? '');
        $type     = trim($_POST['type'] ?? 'General');
        $specs    = trim($_POST['specializations'] ?? '');

        if ($hospName === '') $errors[] = 'Hospital name is required.';
        if ($city === '')     $errors[] = 'City is required.';

        /* License upload validation */
        $licenseErr = validate_file_upload(
            $_FILES['license'] ?? [],
            ['pdf', 'jpg', 'jpeg', 'png', 'webp'],
            5242880,
            ['application/pdf', 'image/jpeg', 'image/png', 'image/webp']
        );
        if ($licenseErr !== null) {
            $errors[] = 'License file error: ' . $licenseErr;
        } else {
            $ext = strtolower(pathinfo($_FILES['license']['name'], PATHINFO_EXTENSION));
            $licenseFileName = 'license_' . time() . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
            $uploadDir = __DIR__ . '/../assets/images/uploads/license_docs';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            if (!move_uploaded_file($_FILES['license']['tmp_name'], $uploadDir . '/' . $licenseFileName)) {
                $errors[] = 'Failed to save uploaded license document.';
            }
        }

        /* Cover photo upload validation */
        if (empty($errors)) {
            $coverErr = validate_file_upload(
                $_FILES['cover_photo'] ?? [],
                ['jpg', 'jpeg', 'png', 'webp'],
                5242880,
                ['image/jpeg', 'image/png', 'image/webp']
            );
            if ($coverErr !== null) {
                $errors[] = 'Cover photo error: ' . $coverErr;
            } else {
                $ext = strtolower(pathinfo($_FILES['cover_photo']['name'], PATHINFO_EXTENSION));
                $coverPhotoName = 'cover_' . time() . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
                $coverPhotoTmp  = $_FILES['cover_photo']['tmp_name'];
            }
        }
    }

    /* ---- insert into DB ---- */
    if (empty($errors)) {
        $pdo->beginTransaction();
        try {
            if ($role === 'hospital') {
                /* 1. Create hospital record */
                $stmt = $pdo->prepare("INSERT INTO hospitals (name, city, type, specializations, status, license_file, created_at) VALUES (?, ?, ?, ?, 'pending', ?, NOW())");
                $stmt->execute([$hospName, $city, $type, $specs, $licenseFileName]);
                $hospitalId = $pdo->lastInsertId();

                /* 2. Move cover photo */
                if ($coverPhotoTmp && $coverPhotoName !== '') {
                    $coverDir = __DIR__ . '/../assets/images/hospital_photos/' . $hospitalId;
                    if (!is_dir($coverDir)) {
                        mkdir($coverDir, 0777, true);
                    }
                    if (move_uploaded_file($coverPhotoTmp, $coverDir . '/' . $coverPhotoName)) {
                        $pdo->prepare("UPDATE hospitals SET cover_photo = ? WHERE id = ?")
                            ->execute([$coverPhotoName, $hospitalId]);
                    }
                }

                /* 3. Create hospital admin user */
                $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role, hospital_id, created_at) VALUES (?, ?, ?, 'hospital', ?, NOW())");
                $stmt->execute([$name, $email, password_hash($password, PASSWORD_DEFAULT), $hospitalId]);

                $pdo->commit();

                $hospitalSubmitted   = true;
                $submittedHospName   = $hospName;
                $submittedAdminEmail = $email;

            } else {
                /* Patient / user account */
                $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role, phone, blood_group, address, emergency_contact, created_at) VALUES (?, ?, ?, 'user', ?, ?, ?, ?, NOW())");
                $stmt->execute([$name, $email, password_hash($password, PASSWORD_DEFAULT), $phone, $blood, $address, $emergency]);
                $newUserId = $pdo->lastInsertId();

                $pdo->commit();

                /* Log in and redirect immediately — no JS redirect needed */
                $_SESSION['user_id'] = $newUserId;
                $_SESSION['role']    = 'user';
                flash('success', 'Welcome to BedTrack, ' . $name . '! Your account has been created.');
                redirect('/user/dashboard.php');
            }

        } catch (Exception $ex) {
            $pdo->rollBack();
            $errors[] = 'Registration failed: ' . $ex->getMessage();
        }
    }
}

$pageTitle = 'Register';
include __DIR__ . '/../includes/header.php';
?>

<div class="auth-wrap">
  <div class="auth-card">
    <?php if ($hospitalSubmitted): ?>
      <div style="text-align:center;padding:24px 10px;">
        <div style="width:72px;height:72px;background:#e8f5e9;border:3px solid #66bb6a;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;color:#2e7d32;font-size:34px;margin-bottom:18px;">
          <i class="fas fa-check"></i>
        </div>
        <h2 style="font-size:22px;font-weight:700;color:var(--ink);margin-bottom:8px;">Submitted for Review!</h2>
        <p style="font-size:14px;color:var(--ink-soft);line-height:1.5;margin-bottom:20px;">
          Your hospital <strong><?php echo esc($submittedHospName); ?></strong> details and license document have been successfully submitted to the super admin for review.
        </p>
        <div style="background:#fdfbf7;border:1px solid #f5d99b;border-radius:10px;padding:14px;text-align:left;margin-bottom:22px;">
          <p style="font-size:13px;color:#7a520a;margin:0;line-height:1.4;">
            <i class="fas fa-clock" style="color:#b4780f;margin-right:6px;"></i>
            Once approved by the super admin, you can log in to your Hospital Admin portal with <strong><?php echo esc($submittedAdminEmail); ?></strong>.
          </p>
        </div>
        <div style="display:flex;gap:10px;">
          <a class="btn btn-primary" href="<?php echo BASE_URL; ?>/auth/login.php?role=hospital" style="flex:1;">Go to Log in</a>
          <a class="btn" href="<?php echo BASE_URL; ?>/index.php" style="flex:1;border:1px solid var(--border);">Home</a>
        </div>
      </div>
    <?php else: ?>
      <div class="auth-tabs">
        <a class="auth-tab" href="<?php echo BASE_URL; ?>/auth/login.php?role=<?php echo esc($role); ?>">Log in</a>
        <span class="auth-tab active">Register</span>
      </div>
      <div class="role-select">
        <a class="role-pill <?php echo $role === 'user' ? 'active' : ''; ?>" href="?role=user">Patient</a>
        <a class="role-pill <?php echo $role === 'hospital' ? 'active' : ''; ?>" href="?role=hospital">Hospital admin</a>
      </div>

      <?php if ($errors): ?>
        <div class="field error" style="margin-bottom:14px;background:var(--rust-tint);border:1px solid var(--rust);border-radius:8px;padding:10px 14px;color:var(--rust);font-size:13px;line-height:1.6;">
          <?php foreach ($errors as $err) echo esc($err) . '<br>'; ?>
        </div>
      <?php endif; ?>

      <form method="post" enctype="multipart/form-data">
        <?php echo csrf_field(); ?>
        <input type="hidden" name="role" value="<?php echo esc($role); ?>">

        <div class="field"><label>Full name</label><input type="text" name="name" required minlength="2" value="<?php echo esc($_POST['name'] ?? ''); ?>"></div>
        <div class="field"><label>Email</label><input type="email" name="email" required value="<?php echo esc($_POST['email'] ?? ''); ?>"></div>
        <div class="field">
          <label>Password</label>
          <div class="password-field-wrapper">
            <input type="password" name="password" required placeholder="At least 6 characters">
          </div>
        </div>

        <?php if ($role === 'user'): ?>
          <div class="grid-2">
            <div class="field"><label>Phone (10 digits)</label><input type="text" name="phone" maxlength="10" pattern="[0-9]{10}" placeholder="e.g. 98XXXXXXXX" value="<?php echo esc($_POST['phone'] ?? ''); ?>"></div>
            <div class="field"><label>Blood group</label><input type="text" name="blood_group" placeholder="e.g. B+" value="<?php echo esc($_POST['blood_group'] ?? ''); ?>"></div>
          </div>
          <div class="field"><label>Address</label><input type="text" name="address" value="<?php echo esc($_POST['address'] ?? ''); ?>"></div>
          <div class="field"><label>Emergency contact (10 digits)</label><input type="text" name="emergency" maxlength="10" pattern="[0-9]{10}" placeholder="e.g. 98XXXXXXXX" value="<?php echo esc($_POST['emergency'] ?? ''); ?>"></div>
        <?php else: ?>
          <div class="field"><label>Hospital name</label><input type="text" name="hospital_name" required value="<?php echo esc($_POST['hospital_name'] ?? ''); ?>"></div>
          <div class="grid-2">
            <div class="field"><label>City</label><input type="text" name="city" required value="<?php echo esc($_POST['city'] ?? ''); ?>"></div>
            <div class="field">
              <label>Type</label>
              <select name="type" required>
                <?php foreach (['General', 'Multi-specialty', 'Specialty', 'Community'] as $t): ?>
                  <option value="<?php echo $t; ?>" <?php echo (($_POST['type'] ?? '') === $t) ? 'selected' : ''; ?>><?php echo $t; ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
          <div class="field"><label>Specializations</label><input type="text" name="specializations" placeholder="e.g. Cardiology, Trauma, ICU" value="<?php echo esc($_POST['specializations'] ?? ''); ?>"></div>
          <div class="field">
            <label>Hospital cover photo (JPG/PNG/WEBP)</label>
            <input type="file" name="cover_photo" accept=".jpg,.jpeg,.png,.webp" required>
            <small>Use one cover image so patients can see the hospital.</small>
          </div>
          <div class="field">
            <label>License document (PDF/JPG/PNG)</label>
            <input type="file" name="license" accept=".pdf,.jpg,.jpeg,.png" required>
            <small>Reviewed by a super admin before your hospital account can log in.</small>
          </div>
        <?php endif; ?>

        <button class="btn btn-primary" style="width:100%;margin-top:6px;" type="submit">
          <?php echo $role === 'hospital' ? 'Submit for review' : 'Create account'; ?>
        </button>
      </form>

      <div style="text-align: center; margin-top: 20px; font-size: 13px; color: var(--ink-soft);">
        Already have an account? <a href="<?php echo BASE_URL; ?>/auth/login.php?role=<?php echo esc($role); ?>" style="color: var(--teal); font-weight: 600; text-decoration: none;">Log in</a>
      </div>
    <?php endif; ?>
  </div>
</div>

<?php if ($hospitalSubmitted): ?>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
  Swal.fire({ title: "Submitted successfully!", icon: "success", draggable: true });
</script>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
