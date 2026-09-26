<?php
// manage all registered hospitals


require_once __DIR__ . '/../config/functions.php';

// Ensure user has super admin permissions
require_role('super');

// Handle status updates or deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $hospitalId = (int)($_POST['hospital_id'] ?? 0);
    $action     = $_POST['action'] ?? '';

    if ($hospitalId > 0) {
        $stmt = $pdo->prepare("SELECT * FROM hospitals WHERE id = ?");
        $stmt->execute([$hospitalId]);
        $hospital = $stmt->fetch();

        if ($hospital) {
            if ($action === 'approve') {
                $pdo->prepare("UPDATE hospitals SET status = 'approved' WHERE id = ?")->execute([$hospitalId]);
                flash('success', "Hospital '{$hospital['name']}' approved.");
            } elseif ($action === 'reject') {
                $pdo->prepare("UPDATE hospitals SET status = 'rejected' WHERE id = ?")->execute([$hospitalId]);
                flash('success', "Hospital '{$hospital['name']}' status set to rejected.");
            } elseif ($action === 'delete') {
                delete_hospital($hospitalId);
                flash('success', "Hospital '{$hospital['name']}' and its associated beds/data have been removed.");
            }
        }
    }
    redirect('/super-admin/manage-hospitals.php');
}

$searchQuery  = trim($_GET['q'] ?? '');
$statusFilter = trim($_GET['status'] ?? '');

$sql = "SELECT h.*, 
               COUNT(b.id) AS total_beds,
               COALESCE(SUM(CASE WHEN b.status = 'available' THEN 1 ELSE 0 END), 0) AS available_beds
        FROM hospitals h
        LEFT JOIN beds b ON h.id = b.hospital_id
        WHERE 1=1";
$params = [];

if ($searchQuery !== '') {
    $sql .= " AND (h.name LIKE ? OR h.city LIKE ? OR h.type LIKE ?)";
    $params[] = "%$searchQuery%";
    $params[] = "%$searchQuery%";
    $params[] = "%$searchQuery%";
}

if (in_array($statusFilter, ['pending', 'approved', 'rejected'])) {
    $sql .= " AND h.status = ?";
    $params[] = $statusFilter;
}

$sql .= " GROUP BY h.id ORDER BY h.id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$hospitals = $stmt->fetchAll();

$active    = 'hospitals';
$role      = 'super';
$pageTitle = 'Manage Hospitals';

include __DIR__ . '/../includes/header.php';
?>

<div class="app-shell">
  <?php include __DIR__ . '/../includes/sidebar.php'; ?>

  <div class="main">
    <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 12px; flex-wrap: wrap;">
      <div>
        <h1>Manage Hospitals</h1>
        <p class="lede">System directory of all registered hospital partners.</p>
      </div>
    </div>

    <!-- Filter Form -->
    <form method="get" style="display: flex; gap: 10px; margin-bottom: 20px; flex-wrap: wrap; align-items: center;">
      <div class="field" style="margin-bottom: 0; flex: 1; min-width: 200px;">
        <input type="text" name="q" placeholder="Search hospital name, city, or type..." value="<?php echo esc($searchQuery); ?>">
      </div>

      <div class="field" style="margin-bottom: 0; width: 160px;">
        <select name="status">
          <option value="">All Statuses</option>
          <option value="approved" <?php echo $statusFilter === 'approved' ? 'selected' : ''; ?>>Approved</option>
          <option value="pending" <?php echo $statusFilter === 'pending' ? 'selected' : ''; ?>>Pending</option>
          <option value="rejected" <?php echo $statusFilter === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
        </select>
      </div>

      <button type="submit" class="btn btn-primary btn-sm">Filter</button>
      <?php if ($searchQuery !== '' || $statusFilter !== ''): ?>
        <a class="btn btn-ghost btn-sm" href="<?php echo BASE_URL; ?>/super-admin/manage-hospitals.php">Reset</a>
      <?php endif; ?>
    </form>

    <?php if (empty($hospitals)): ?>
      <div class="empty">
        <h4>No hospitals found</h4>
        <p>No hospitals match your search criteria.</p>
      </div>
    <?php else: ?>
      <div class="card">
        <table>
          <thead>
            <tr>
              <th>ID</th>
              <th>Hospital Name</th>
              <th>City</th>
              <th>Type</th>
              <th>Status</th>
              <th>Capacity</th>
              <th style="text-align: right;">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($hospitals as $h): ?>
              <tr>
                <td style="font-family: var(--font-mono); font-size: 12px; color: var(--ink-soft);">#<?php echo $h['id']; ?></td>
                <td><strong><?php echo esc($h['name']); ?></strong></td>
                <td><?php echo esc($h['city']); ?></td>
                <td><?php echo esc($h['type']); ?></td>
                <td><?php echo badge($h['status']); ?></td>
                <td style="font-size: 13px;">
                  <span style="color: var(--teal); font-weight: 600;"><?php echo (int)$h['available_beds']; ?></span> / <?php echo (int)$h['total_beds']; ?> open
                </td>
                <td class="table-actions" style="justify-content: flex-end;">
                  <?php if ($h['status'] !== 'approved'): ?>
                    <form method="post" style="display: inline;">
                      <?php echo csrf_field(); ?>
                      <input type="hidden" name="hospital_id" value="<?php echo $h['id']; ?>">
                      <input type="hidden" name="action" value="approve">
                      <button class="btn btn-sm btn-primary" type="submit">Approve</button>
                    </form>
                  <?php endif; ?>

                  <?php if ($h['status'] !== 'rejected'): ?>
                    <form method="post" style="display: inline;">
                      <?php echo csrf_field(); ?>
                      <input type="hidden" name="hospital_id" value="<?php echo $h['id']; ?>">
                      <input type="hidden" name="action" value="reject">
                      <button class="btn btn-sm btn-outline" type="submit">Reject</button>
                    </form>
                  <?php endif; ?>

                  <form method="post" style="display: inline;">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="hospital_id" value="<?php echo $h['id']; ?>">
                    <input type="hidden" name="action" value="delete">
                    <button class="btn btn-sm btn-danger" type="submit" onclick="return confirm('Permanently delete hospital \'<?php echo esc(addslashes($h['name'])); ?>\' and all associated records?');">
                      <i class="fas fa-trash-alt" style="margin-right: 4px;"></i>Delete
                    </button>
                  </form>
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
