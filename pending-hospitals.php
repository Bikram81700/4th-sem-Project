<?php
// pending hospital approvals


require_once __DIR__ . '/../config/functions.php';

// Ensure user has super admin permissions
require_role('super');

// Handle approval / rejection submissions
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
                $update = $pdo->prepare("UPDATE hospitals SET status = 'approved' WHERE id = ?");
                $update->execute([$hospitalId]);
                flash('success', "Hospital '{$hospital['name']}' has been approved.");
            } elseif ($action === 'reject') {
                $update = $pdo->prepare("UPDATE hospitals SET status = 'rejected' WHERE id = ?");
                $update->execute([$hospitalId]);
                flash('success', "Hospital '{$hospital['name']}' registration was rejected.");
            }
        }
    }
    redirect('/super-admin/pending-hospitals.php');
}

// Fetch all pending hospital registration applications
$pendingHospitals = $pdo->query("
    SELECT * FROM hospitals 
    WHERE status = 'pending' 
    ORDER BY id DESC
")->fetchAll();

$active    = 'approvals';
$role      = 'super';
$pageTitle = 'Hospital Approvals';

include __DIR__ . '/../includes/header.php';
?>

<div class="app-shell">
  <?php include __DIR__ . '/../includes/sidebar.php'; ?>

  <div class="main">
    <h1>Hospital Approvals</h1>
    <p class="lede">Review license verification documents and approve hospital admin accounts.</p>

    <?php if (empty($pendingHospitals)): ?>
      <div class="empty">
        <h4>No pending hospital applications</h4>
        <p>All hospital registrations have been reviewed and processed.</p>
      </div>
    <?php else: ?>
      <div class="card">
        <table>
          <thead>
            <tr>
              <th>Hospital Name</th>
              <th>City</th>
              <th>Type</th>
              <th>Specializations</th>
              <th>License File</th>
              <th>Submitted Date</th>
              <th style="text-align: right;">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($pendingHospitals as $h): ?>
              <tr>
                <td><strong><?php echo esc($h['name']); ?></strong></td>
                <td><?php echo esc($h['city']); ?></td>
                <td><?php echo esc($h['type']); ?></td>
                <td><?php echo esc($h['specializations'] ?: '—'); ?></td>
                <td>
                  <?php if (!empty($h['license_file'])): ?>
                    <a href="<?php echo BASE_URL; ?>/assets/images/uploads/license_docs/<?php echo rawurlencode($h['license_file']); ?>" target="_blank" style="color: var(--teal); font-weight: 500;">
                      <i class="fas fa-file-pdf" style="margin-right: 4px;"></i>View License
                    </a>
                  <?php else: ?>
                    <span style="color: var(--ink-soft);">None</span>
                  <?php endif; ?>
                </td>
                <td style="font-family: var(--font-mono); font-size: 12px; color: var(--ink-soft);">
                  <?php echo date('Y-m-d', strtotime($h['created_at'])); ?>
                </td>
                <td class="table-actions" style="justify-content: flex-end;">
                  <form method="post" style="display: inline;">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="hospital_id" value="<?php echo $h['id']; ?>">
                    <input type="hidden" name="action" value="approve">
                    <button class="btn btn-sm btn-primary" type="submit">Approve</button>
                  </form>

                  <form method="post" style="display: inline;">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="hospital_id" value="<?php echo $h['id']; ?>">
                    <input type="hidden" name="action" value="reject">
                    <button class="btn btn-sm btn-outline" type="submit" onclick="return confirm('Are you sure you want to reject this hospital registration?');">Reject</button>
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
