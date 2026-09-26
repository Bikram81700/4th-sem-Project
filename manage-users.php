<?php
// manage platform users


require_once __DIR__ . '/../config/functions.php';

// Ensure user has super admin permissions
require_role('super');

$currentUser = current_user();

// Handle deletion of user accounts
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $userId = (int)($_POST['user_id'] ?? 0);
    $action = $_POST['action'] ?? '';

    if ($action === 'delete' && $userId > 0) {
        if ($userId === (int)$currentUser['id']) {
            flash('error', 'You cannot delete your own super admin account while logged in.');
        } else {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $targetUser = $stmt->fetch();

            if ($targetUser) {
                $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$userId]);
                flash('success', "User account '{$targetUser['name']}' ({$targetUser['email']}) deleted.");
            }
        }
    }
    redirect('/super-admin/manage-users.php');
}

$searchQuery = trim($_GET['q'] ?? '');
$roleFilter  = trim($_GET['role'] ?? '');

$sql = "SELECT u.*, h.name AS hospital_name 
        FROM users u
        LEFT JOIN hospitals h ON h.id = u.hospital_id
        WHERE 1=1";
$params = [];

if ($searchQuery !== '') {
    $sql .= " AND (u.name LIKE ? OR u.email LIKE ? OR u.phone LIKE ?)";
    $params[] = "%$searchQuery%";
    $params[] = "%$searchQuery%";
    $params[] = "%$searchQuery%";
}

if (in_array($roleFilter, ['user', 'hospital', 'super'])) {
    $sql .= " AND u.role = ?";
    $params[] = $roleFilter;
}

$sql .= " ORDER BY u.id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$usersList = $stmt->fetchAll();

$active    = 'users';
$role      = 'super';
$pageTitle = 'Manage Users';

include __DIR__ . '/../includes/header.php';
?>

<div class="app-shell">
  <?php include __DIR__ . '/../includes/sidebar.php'; ?>

  <div class="main">
    <h1>Manage Users</h1>
    <p class="lede">Overview of registered patients, hospital administrators, and system users.</p>

    <!-- Filter Bar -->
    <form method="get" style="display: flex; gap: 10px; margin-bottom: 20px; flex-wrap: wrap; align-items: center;">
      <div class="field" style="margin-bottom: 0; flex: 1; min-width: 200px;">
        <input type="text" name="q" placeholder="Search name, email, or phone..." value="<?php echo esc($searchQuery); ?>">
      </div>

      <div class="field" style="margin-bottom: 0; width: 160px;">
        <select name="role">
          <option value="">All Roles</option>
          <option value="user" <?php echo $roleFilter === 'user' ? 'selected' : ''; ?>>Patients (User)</option>
          <option value="hospital" <?php echo $roleFilter === 'hospital' ? 'selected' : ''; ?>>Hospital Admins</option>
          <option value="super" <?php echo $roleFilter === 'super' ? 'selected' : ''; ?>>Super Admins</option>
        </select>
      </div>

      <button type="submit" class="btn btn-primary btn-sm">Filter</button>
      <?php if ($searchQuery !== '' || $roleFilter !== ''): ?>
        <a class="btn btn-ghost btn-sm" href="<?php echo BASE_URL; ?>/super-admin/manage-users.php">Reset</a>
      <?php endif; ?>
    </form>

    <?php if (empty($usersList)): ?>
      <div class="empty">
        <h4>No users found</h4>
        <p>No user accounts match your search parameters.</p>
      </div>
    <?php else: ?>
      <div class="card">
        <table>
          <thead>
            <tr>
              <th>ID</th>
              <th>Name</th>
              <th>Email</th>
              <th>Role</th>
              <th>Associated Hospital / Details</th>
              <th>Joined Date</th>
              <th style="text-align: right;">Action</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($usersList as $usr): ?>
              <tr>
                <td style="font-family: var(--font-mono); font-size: 12px; color: var(--ink-soft);">#<?php echo $usr['id']; ?></td>
                <td><strong><?php echo esc($usr['name']); ?></strong></td>
                <td style="font-family: var(--font-mono); font-size: 12px;"><?php echo esc($usr['email']); ?></td>
                <td>
                  <?php if ($usr['role'] === 'super'): ?>
                    <span class="badge badge-approved">Super Admin</span>
                  <?php elseif ($usr['role'] === 'hospital'): ?>
                    <span class="badge badge-accepted">Hospital Admin</span>
                  <?php else: ?>
                    <span class="badge badge-available">Patient</span>
                  <?php endif; ?>
                </td>
                <td style="font-size: 12px;">
                  <?php if ($usr['role'] === 'hospital'): ?>
                    <?php echo esc($usr['hospital_name'] ?: 'Not assigned'); ?>
                  <?php elseif ($usr['role'] === 'user'): ?>
                    Phone: <?php echo esc($usr['phone'] ?: '—'); ?> | Blood: <?php echo esc($usr['blood_group'] ?: '—'); ?>
                  <?php else: ?>
                    System Administrator
                  <?php endif; ?>
                </td>
                <td style="font-family: var(--font-mono); font-size: 12px; color: var(--ink-soft);">
                  <?php echo date('Y-m-d', strtotime($usr['created_at'])); ?>
                </td>
                <td class="table-actions" style="justify-content: flex-end;">
                  <?php if ((int)$usr['id'] !== (int)$currentUser['id']): ?>
                    <form method="post" style="display: inline;">
                      <?php echo csrf_field(); ?>
                      <input type="hidden" name="user_id" value="<?php echo $usr['id']; ?>">
                      <input type="hidden" name="action" value="delete">
                      <button class="btn btn-sm btn-danger" type="submit" onclick="return confirm('Permanently delete account for \'<?php echo esc(addslashes($usr['name'])); ?>\'?');">
                        <i class="fas fa-trash-alt" style="margin-right: 4px;"></i>Delete
                      </button>
                    </form>
                  <?php else: ?>
                    <span style="font-size: 12px; color: var(--ink-soft); font-weight: 500;">(Current User)</span>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>

  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
