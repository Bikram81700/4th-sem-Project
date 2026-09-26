<?php
// global patient booking history


require_once __DIR__ . '/../config/functions.php';

// Ensure user has super admin permissions
require_role('super');

// Handle deletion of booking records
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $bookingId = (int)($_POST['booking_id'] ?? 0);
    $action    = $_POST['action'] ?? '';

    if ($action === 'delete' && $bookingId > 0) {
        delete_booking($bookingId);
        flash('success', 'Booking history record removed.');
    }
    redirect('/super-admin/patient-history.php');
}

$searchQuery  = trim($_GET['q'] ?? '');
$statusFilter = trim($_GET['status'] ?? '');

$sql = "SELECT b.*, h.name AS hospital_name, c.name AS category_name, u.email AS user_email
        FROM bookings b
        JOIN hospitals h ON h.id = b.hospital_id
        JOIN bed_categories c ON c.id = b.category_id
        JOIN users u ON u.id = b.user_id
        WHERE 1=1";
$params = [];

if ($searchQuery !== '') {
    $sql .= " AND (b.patient_name LIKE ? OR h.name LIKE ? OR u.email LIKE ? OR b.contact LIKE ?)";
    $params[] = "%$searchQuery%";
    $params[] = "%$searchQuery%";
    $params[] = "%$searchQuery%";
    $params[] = "%$searchQuery%";
}

if (in_array($statusFilter, ['pending', 'accepted', 'rejected', 'discharged'])) {
    $sql .= " AND b.status = ?";
    $params[] = $statusFilter;
}

$sql .= " ORDER BY b.id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$bookings = $stmt->fetchAll();

$active    = 'patient-history';
$role      = 'super';
$pageTitle = 'Patient History';

include __DIR__ . '/../includes/header.php';
?>

<div class="app-shell">
  <?php include __DIR__ . '/../includes/sidebar.php'; ?>

  <div class="main">
    <h1>Patient Booking History</h1>
    <p class="lede">Complete platform log of all patient bed reservations and statuses.</p>

    <!-- Filter Bar -->
    <form method="get" style="display: flex; gap: 10px; margin-bottom: 20px; flex-wrap: wrap; align-items: center;">
      <div class="field" style="margin-bottom: 0; flex: 1; min-width: 200px;">
        <input type="text" name="q" placeholder="Search patient name, hospital, contact, or user email..." value="<?php echo esc($searchQuery); ?>">
      </div>

      <div class="field" style="margin-bottom: 0; width: 160px;">
        <select name="status">
          <option value="">All Statuses</option>
          <option value="pending" <?php echo $statusFilter === 'pending' ? 'selected' : ''; ?>>Pending</option>
          <option value="accepted" <?php echo $statusFilter === 'accepted' ? 'selected' : ''; ?>>Accepted</option>
          <option value="discharged" <?php echo $statusFilter === 'discharged' ? 'selected' : ''; ?>>Discharged</option>
          <option value="rejected" <?php echo $statusFilter === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
        </select>
      </div>

      <button type="submit" class="btn btn-primary btn-sm">Filter</button>
      <?php if ($searchQuery !== '' || $statusFilter !== ''): ?>
        <a class="btn btn-ghost btn-sm" href="<?php echo BASE_URL; ?>/super-admin/patient-history.php">Reset</a>
      <?php endif; ?>
    </form>

    <?php if (empty($bookings)): ?>
      <div class="empty">
        <h4>No booking records found</h4>
        <p>No patient booking records match your filter criteria.</p>
      </div>
    <?php else: ?>
      <div class="card">
        <table>
          <thead>
            <tr>
              <th>ID</th>
              <th>Patient</th>
              <th>Hospital</th>
              <th>Category</th>
              <th>Contact</th>
              <th>Date</th>
              <th>Status</th>
              <th style="text-align: right;">Action</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($bookings as $b): ?>
              <tr>
                <td style="font-family: var(--font-mono); font-size: 12px; color: var(--ink-soft);">#<?php echo $b['id']; ?></td>
                <td>
                  <strong><?php echo esc($b['patient_name']); ?></strong>
                  <?php if ($b['booking_for'] === 'other'): ?>
                    <span style="color: var(--ink-soft); font-size: 12px;">(attendant)</span>
                  <?php endif; ?>
                </td>
                <td><?php echo esc($b['hospital_name']); ?></td>
                <td><?php echo esc($b['category_name']); ?></td>
                <td style="font-family: var(--font-mono); font-size: 12px;"><?php echo esc($b['contact'] ?: '—'); ?></td>
                <td style="font-family: var(--font-mono); font-size: 12px;"><?php echo esc($b['booking_date']); ?></td>
                <td><?php echo badge($b['status']); ?></td>
                <td class="table-actions" style="justify-content: flex-end;">
                  <form method="post" style="display: inline;">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="booking_id" value="<?php echo $b['id']; ?>">
                    <input type="hidden" name="action" value="delete">
                    <button class="btn btn-sm btn-danger" type="submit" onclick="return confirm('Permanently remove this booking record?');">
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
