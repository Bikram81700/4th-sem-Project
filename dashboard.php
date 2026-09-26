<?php
// super admin main dashboard


require_once __DIR__ . '/../config/functions.php';

// Ensure user has super admin permissions
require_role('super');

$currentUser = current_user();

// Handle quick hospital approval actions from dashboard
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $hospitalId = (int)($_POST['hospital_id'] ?? 0);
    $action     = $_POST['action'] ?? '';

    if ($hospitalId > 0) {
        if ($action === 'approve') {
            $stmt = $pdo->prepare("UPDATE hospitals SET status = 'approved' WHERE id = ?");
            $stmt->execute([$hospitalId]);
            flash('success', 'Hospital registration approved successfully.');
        } elseif ($action === 'reject') {
            $stmt = $pdo->prepare("UPDATE hospitals SET status = 'rejected' WHERE id = ?");
            $stmt->execute([$hospitalId]);
            flash('success', 'Hospital registration rejected.');
        }
    }
    redirect('/super-admin/dashboard.php');
}

// System stats
$totalHospitals   = (int)$pdo->query("SELECT COUNT(*) FROM hospitals")->fetchColumn();
$pendingHospitals = (int)$pdo->query("SELECT COUNT(*) FROM hospitals WHERE status = 'pending'")->fetchColumn();
$approvedHospitals= (int)$pdo->query("SELECT COUNT(*) FROM hospitals WHERE status = 'approved'")->fetchColumn();

$totalUsers       = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'user'")->fetchColumn();
$totalBeds        = (int)$pdo->query("SELECT COUNT(*) FROM beds")->fetchColumn();
$availableBeds    = (int)$pdo->query("SELECT COUNT(*) FROM beds WHERE status = 'available'")->fetchColumn();
$occupiedBeds     = $totalBeds - $availableBeds;

$totalBookings    = (int)$pdo->query("SELECT COUNT(*) FROM bookings")->fetchColumn();
$pendingBookings  = (int)$pdo->query("SELECT COUNT(*) FROM bookings WHERE status = 'pending'")->fetchColumn();

// Fetch pending hospital approval requests (up to 5)
$pendingList = $pdo->query("
    SELECT * FROM hospitals 
    WHERE status = 'pending' 
    ORDER BY id DESC LIMIT 5
")->fetchAll();

// Fetch recent platform-wide bookings (up to 6)
$recentBookings = $pdo->query("
    SELECT b.*, h.name AS hospital_name, c.name AS category_name
    FROM bookings b
    JOIN hospitals h ON h.id = b.hospital_id
    JOIN bed_categories c ON c.id = b.category_id
    ORDER BY b.id DESC LIMIT 6
")->fetchAll();

$active    = 'dashboard';
$role      = 'super';
$pageTitle = 'Super Admin Dashboard';

include __DIR__ . '/../includes/header.php';
?>

<div class="app-shell">
  <?php include __DIR__ . '/../includes/sidebar.php'; ?>

  <div class="main">
    <h1>Platform Overview</h1>
    <p class="lede">System-wide live metrics and hospital verification requests.</p>

    <!-- Stat Cards Grid -->
    <div class="stat-grid">
      <div class="stat-card">
        <div class="label">Total Hospitals</div>
        <div class="num"><?php echo $totalHospitals; ?></div>
      </div>
      <div class="stat-card">
        <div class="label">Pending Approvals</div>
        <div class="num" style="color: var(--amber);"><?php echo $pendingHospitals; ?></div>
      </div>
      <div class="stat-card">
        <div class="label">Total Patients</div>
        <div class="num" style="color: var(--teal);"><?php echo $totalUsers; ?></div>
      </div>
      <div class="stat-card">
        <div class="label">Available Beds</div>
        <div class="num" style="color: var(--teal);"><?php echo $availableBeds; ?> / <?php echo $totalBeds; ?></div>
      </div>
    </div>

    <!-- Pending Hospital Registrations -->
    <div class="card">
      <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
        <h3 style="margin: 0;">Pending Hospital Approvals</h3>
        <a class="btn btn-sm" href="<?php echo BASE_URL; ?>/super-admin/pending-hospitals.php">View all requests &rarr;</a>
      </div>

      <?php if (empty($pendingList)): ?>
        <div class="empty">
          <h4>No pending hospital approvals</h4>
          <p>All hospital registrations have been reviewed.</p>
        </div>
      <?php else: ?>
        <table>
          <thead>
            <tr>
              <th>Hospital Name</th>
              <th>City</th>
              <th>Type</th>
              <th>License Document</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($pendingList as $h): ?>
              <tr>
                <td><strong><?php echo esc($h['name']); ?></strong></td>
                <td><?php echo esc($h['city']); ?></td>
                <td><?php echo esc($h['type']); ?></td>
                <td>
                  <?php if (!empty($h['license_file'])): ?>
                    <a href="<?php echo BASE_URL; ?>/assets/images/uploads/license_docs/<?php echo rawurlencode($h['license_file']); ?>" target="_blank" style="color: var(--teal); font-weight: 500;">
                      <i class="fas fa-file-pdf"></i> View License
                    </a>
                  <?php else: ?>
                    <span style="color: var(--ink-soft);">None</span>
                  <?php endif; ?>
                </td>
                <td class="table-actions">
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
                    <button class="btn btn-sm btn-outline" type="submit" onclick="return confirm('Reject this hospital registration?');">Reject</button>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </div>

    <!-- Recent Platform Bookings -->
    <div class="card">
      <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
        <h3 style="margin: 0;">Recent Platform Bookings</h3>
        <a class="btn btn-sm" href="<?php echo BASE_URL; ?>/super-admin/patient-history.php">View patient history &rarr;</a>
      </div>

      <?php if (empty($recentBookings)): ?>
        <div class="empty">
          <h4>No recent bookings</h4>
        </div>
      <?php else: ?>
        <table>
          <thead>
            <tr>
              <th>Patient</th>
              <th>Hospital</th>
              <th>Category</th>
              <th>Date</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($recentBookings as $b): ?>
              <tr>
                <td><strong><?php echo esc($b['patient_name']); ?></strong></td>
                <td><?php echo esc($b['hospital_name']); ?></td>
                <td><?php echo esc($b['category_name']); ?></td>
                <td><?php echo esc($b['booking_date']); ?></td>
                <td><?php echo badge($b['status']); ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </div>

  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
