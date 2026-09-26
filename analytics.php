<?php
// platform analytics and metrics


require_once __DIR__ . '/../config/functions.php';

// Ensure user has super admin permissions
require_role('super');

// Analytics data aggregation
$totalBeds     = (int)$pdo->query("SELECT COUNT(*) FROM beds")->fetchColumn();
$availableBeds = (int)$pdo->query("SELECT COUNT(*) FROM beds WHERE status = 'available'")->fetchColumn();
$occupiedBeds  = $totalBeds - $availableBeds;
$occupancyRate = $totalBeds > 0 ? round(($occupiedBeds / $totalBeds) * 100, 1) : 0;

// Hospital counts by status
$statusCounts = $pdo->query("
    SELECT status, COUNT(*) as count 
    FROM hospitals 
    GROUP BY status
")->fetchAll(PDO::FETCH_KEY_PAIR);

// Hospitals breakdown by city
$cityDistribution = $pdo->query("
    SELECT city, COUNT(*) as count 
    FROM hospitals 
    WHERE status = 'approved' AND city IS NOT NULL AND city != ''
    GROUP BY city 
    ORDER BY count DESC
")->fetchAll();

// Bookings breakdown by status
$bookingStatusCounts = $pdo->query("
    SELECT status, COUNT(*) as count 
    FROM bookings 
    GROUP BY status
")->fetchAll(PDO::FETCH_KEY_PAIR);

$active    = 'analytics';
$role      = 'super';
$pageTitle = 'Analytics & Insights';

include __DIR__ . '/../includes/header.php';
?>

<div class="app-shell">
  <?php include __DIR__ . '/../includes/sidebar.php'; ?>

  <div class="main">
    <h1>Platform Analytics</h1>
    <p class="lede">Capacity utilization, network growth, and patient booking metrics.</p>

    <!-- Key Metrics Grid -->
    <div class="stat-grid">
      <div class="stat-card">
        <div class="label">Total Ward Beds</div>
        <div class="num"><?php echo $totalBeds; ?></div>
      </div>
      <div class="stat-card">
        <div class="label">Bed Utilization</div>
        <div class="num" style="color: var(--rust);"><?php echo $occupancyRate; ?>%</div>
      </div>
      <div class="stat-card">
        <div class="label">Open Beds</div>
        <div class="num" style="color: var(--teal);"><?php echo $availableBeds; ?></div>
      </div>
      <div class="stat-card">
        <div class="label">Occupied Beds</div>
        <div class="num" style="color: var(--rust);"><?php echo $occupiedBeds; ?></div>
      </div>
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 16px; margin-bottom: 20px;">
      <!-- Hospital Registration Statuses -->
      <div class="card" style="margin-bottom: 0;">
        <h3>Hospital Network Status</h3>
        <ul style="list-style: none; padding: 0; margin: 12px 0 0 0; font-size: 14px; line-height: 2;">
          <li style="display: flex; justify-content: space-between; border-b: 1px solid var(--border); padding: 4px 0;">
            <span>Approved Hospitals</span>
            <strong style="color: var(--teal);"><?php echo (int)($statusCounts['approved'] ?? 0); ?></strong>
          </li>
          <li style="display: flex; justify-content: space-between; border-b: 1px solid var(--border); padding: 4px 0;">
            <span>Pending Registration Review</span>
            <strong style="color: var(--amber);"><?php echo (int)($statusCounts['pending'] ?? 0); ?></strong>
          </li>
          <li style="display: flex; justify-content: space-between; padding: 4px 0;">
            <span>Rejected Applications</span>
            <strong style="color: var(--rust);"><?php echo (int)($statusCounts['rejected'] ?? 0); ?></strong>
          </li>
        </ul>
      </div>

      <!-- Booking Request Statuses -->
      <div class="card" style="margin-bottom: 0;">
        <h3>Patient Booking Status Breakdown</h3>
        <ul style="list-style: none; padding: 0; margin: 12px 0 0 0; font-size: 14px; line-height: 2;">
          <li style="display: flex; justify-content: space-between; border-b: 1px solid var(--border); padding: 4px 0;">
            <span>Pending Requests</span>
            <strong style="color: var(--amber);"><?php echo (int)($bookingStatusCounts['pending'] ?? 0); ?></strong>
          </li>
          <li style="display: flex; justify-content: space-between; border-b: 1px solid var(--border); padding: 4px 0;">
            <span>Accepted / Active Admissions</span>
            <strong style="color: var(--teal);"><?php echo (int)($bookingStatusCounts['accepted'] ?? 0); ?></strong>
          </li>
          <li style="display: flex; justify-content: space-between; border-b: 1px solid var(--border); padding: 4px 0;">
            <span>Discharged Patients</span>
            <strong><?php echo (int)($bookingStatusCounts['discharged'] ?? 0); ?></strong>
          </li>
          <li style="display: flex; justify-content: space-between; padding: 4px 0;">
            <span>Rejected Requests</span>
            <strong style="color: var(--rust);"><?php echo (int)($bookingStatusCounts['rejected'] ?? 0); ?></strong>
          </li>
        </ul>
      </div>
    </div>

    <!-- Hospital City Distribution Table -->
    <div class="card">
      <h3>Approved Hospitals by City</h3>
      <?php if (empty($cityDistribution)): ?>
        <p style="color: var(--ink-soft); font-size: 14px;">No city distribution data available.</p>
      <?php else: ?>
        <table>
          <thead>
            <tr>
              <th>City</th>
              <th>Approved Hospitals</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($cityDistribution as $cd): ?>
              <tr>
                <td><strong><?php echo esc($cd['city']); ?></strong></td>
                <td><?php echo (int)$cd['count']; ?> hospital(s)</td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </div>

  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
