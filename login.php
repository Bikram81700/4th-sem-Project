<?php
require_once __DIR__ . '/../config/functions.php';

if (is_logged_in()) {
    $role = current_role();
    redirect($role === 'user' ? '/user/dashboard.php' : ($role === 'hospital' ? '/admin/dashboard.php' : '/super-admin/dashboard.php'));
}

$role = in_array($_GET['role'] ?? '', ['user', 'hospital', 'super']) ? $_GET['role'] : 'user';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $userRole = $user['role'];

        if ($userRole === 'hospital') {
            $h = hospital_by_id($user['hospital_id']);
            if (!$h || $h['status'] === 'pending') {
                $error = 'Your hospital registration is currently pending review by the super admin. You will be able to log in once your license and details are approved.';
            } elseif ($h['status'] === 'rejected') {
                $error = 'Your hospital registration was not approved. Please contact the platform administration for assistance.';
            } else {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['role']    = $user['role'];
                redirect('/admin/dashboard.php');
            }
        } else {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role']    = $user['role'];
            redirect($userRole === 'user' ? '/user/dashboard.php' : '/super-admin/dashboard.php');
        }
    } else {
        $error = 'Invalid email or password.';
    }
}

$pageTitle = 'Log in';
include __DIR__ . '/../includes/header.php';
?>

<div class="auth-wrap">
  <div class="auth-card">
    <div class="auth-tabs">
      <span class="auth-tab active">Log in</span>
      <a class="auth-tab" href="<?php echo BASE_URL; ?>/auth/signup.php?role=<?php echo esc($role === 'super' ? 'user' : $role); ?>">Register</a>
    </div>

    <form method="post">
      <?php echo csrf_field(); ?>
      <div class="field"><label>Email</label><input type="email" name="email" required placeholder="name@example.com" value="<?php echo esc($_POST['email'] ?? ''); ?>"></div>
      <div class="field">
        <label>Password</label>
        <div class="password-field-wrapper">
          <input type="password" name="password" required placeholder="••••••••">
        </div>
      </div>
      <?php if ($error): ?>
        <div class="field error" style="margin-bottom: 14px; font-size: 13px; line-height: 1.4;">
          <?php echo esc($error); ?>
        </div>
      <?php endif; ?>
      <button class="btn btn-primary" style="width:100%" type="submit">Log in</button>
    </form>

    <div style="text-align: center; margin-top: 20px; font-size: 13px; color: var(--ink-soft);">
      Don't have an account? <a href="<?php echo BASE_URL; ?>/auth/signup.php?role=<?php echo esc($role === 'super' ? 'user' : $role); ?>" style="color: var(--teal); font-weight: 600; text-decoration: none;">Register</a>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>

