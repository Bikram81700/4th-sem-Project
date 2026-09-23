<?php
require_once __DIR__ . '/../config/functions.php';
$currentUser = is_logged_in() ? current_user() : null;

$dashboardUrl = BASE_URL . '/index.php';
if ($currentUser) {
    if ($currentUser['role'] === 'super') {
        $dashboardUrl = BASE_URL . '/super-admin/dashboard.php';
    } elseif ($currentUser['role'] === 'hospital') {
        $dashboardUrl = BASE_URL . '/admin/dashboard.php';
    } elseif ($currentUser['role'] === 'user') {
        $dashboardUrl = BASE_URL . '/user/dashboard.php';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? esc($pageTitle) . ' — BedTrack' : 'BedTrack — Hospital Bed Booking'; ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css?v=<?php echo time(); ?>">
</head>
<body>

<nav class="topnav">
  <a href="<?php echo $dashboardUrl; ?>" class="logo">
    <i class="fas fa-bed-pulse"></i>
    Bed<span>Track</span>
  </a>
  <div class="nav-right">
    <?php if (!$currentUser): 
      $currentScript = basename($_SERVER['SCRIPT_NAME'] ?? '');
      $isLoginPage   = ($currentScript === 'login.php');
      $isSignupPage  = ($currentScript === 'signup.php');
      $isIndexPage   = ($currentScript === 'index.php');
    ?>
      <?php if (!$isIndexPage): ?>
        <a class="btn" href="<?php echo BASE_URL; ?>/index.php">Home</a>
      <?php endif; ?>
      <?php if (!$isLoginPage && !$isIndexPage): ?>
        <a class="btn" href="<?php echo BASE_URL; ?>/auth/login.php">Log in</a>
      <?php endif; ?>
      <?php if (!$isSignupPage && !$isIndexPage): ?>
        <a class="btn btn-primary" href="<?php echo BASE_URL; ?>/auth/signup.php">Register</a>
      <?php endif; ?>
    <?php else: ?>
      <span class="nav-user-label" style="font-size:13px;color:var(--ink-soft);margin-right:8px;">
        <i class="fas fa-user-circle"></i> <?php echo esc($currentUser['name']); ?>
      </span>
      <a class="btn btn-danger" href="<?php echo BASE_URL; ?>/auth/logout.php">Log out</a>
    <?php endif; ?>
  </div>
</nav>

<?php if ($msg = flash('success')): ?>
  <div class="flash flash-success"><div class="page"><?php echo esc($msg); ?></div></div>
<?php endif; ?>
<?php if ($msg = flash('error')): ?>
  <div class="flash flash-error"><div class="page"><?php echo esc($msg); ?></div></div>
<?php endif; ?>

