<?php
/**
 * BedTrack - Admin Dashboard
 * Provides hospital administrators with real-time ward stats, cover photo upload,
 * ward map grid visualization, and recent patient booking requests.
 */

require_once __DIR__ . '/../config/functions.php';

// Ensure user has hospital admin permissions
require_role('hospital');

$currentUser = current_user();
$hospitalId  = (int) $currentUser['hospital_id'];
$hospital    = hospital_by_id($hospitalId);

// Handle hospital cover photo upload submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_cover'])) {
    verify_csrf();

    if (!empty($_FILES['cover_photo']['name'])) {
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];
        $uploadedExt       = strtolower(pathinfo($_FILES['cover_photo']['name'], PATHINFO_EXTENSION));

        if (!in_array($uploadedExt, $allowedExtensions)) {
            flash('error', 'Cover photo must be JPG, PNG, or WEBP.');
        } elseif ($_FILES['cover_photo']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/../assets/images/hospital_photos/' . $hospitalId;
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            // Remove existing cover photo file if present
            $existingPhoto = $hospital['cover_photo'] ?? null;
            if ($existingPhoto && file_exists($uploadDir . '/' . $existingPhoto)) {
                unlink($uploadDir . '/' . $existingPhoto);
            }

            // Generate clean unique filename and store upload
            $sanitizedOriginalName = preg_replace('/[^a-zA-Z0-9_.-]/', '_', $_FILES['cover_photo']['name']);
            $newFileName           = 'cover_' . time() . '_' . $sanitizedOriginalName;
            $targetFilePath        = $uploadDir . '/' . $newFileName;

            if (move_uploaded_file($_FILES['cover_photo']['tmp_name'], $targetFilePath)) {
                $stmt = $pdo->prepare("UPDATE hospitals SET cover_photo = ? WHERE id = ?");
                $stmt->execute([$newFileName, $hospitalId]);
                flash('success', 'Your hospital cover photo has been updated.');
            } else {
                flash('error', 'Could not upload the cover photo. Please try again.');
            }
        } else {
            flash('error', 'The cover photo upload failed. Please try again.');
        }
    } else {
        flash('error', 'Please choose a cover photo to upload.');
    }

    redirect('/admin/dashboard.php');
}

// Fetch total and available bed counts
$totalBeds     = total_beds_of_hospital($hospitalId);
$availableBeds = available_beds_of_hospital($hospitalId);
$occupiedBeds  = $totalBeds - $availableBeds;

// Fetch pending booking request count
$stmt = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE hospital_id = ? AND status = 'pending'");
$stmt->execute([$hospitalId]);
$pendingCount = (int) $stmt->fetchColumn();

// Fetch ward map bed cell statuses (limit 120 for layout display)
$stmt = $pdo->prepare("SELECT status FROM beds WHERE hospital_id = ? LIMIT 120");
$stmt->execute([$hospitalId]);
$wardCells = $stmt->fetchAll();

// Fetch 6 most recent bookings for dashboard overview
$stmt = $pdo->prepare("
    SELECT b.*, c.name AS category_name 
    FROM bookings b
    JOIN bed_categories c ON c.id = b.category_id
    WHERE b.hospital_id = ? 
    ORDER BY b.id DESC 
    LIMIT 6
");
$stmt->execute([$hospitalId]);
$recentBookings = $stmt->fetchAll();

$coverPhotoUrl = hospital_cover_photo($hospitalId);

// Page layout configuration
$active    = 'dashboard';
$role      = 'hospital';
$pageTitle = 'Dashboard';

include __DIR__ . '/../includes/header.php';
?>

<div class="app-shell">
  <?php include __DIR__ . '/../includes/sidebar.php'; ?>

  <div class="main">
    <h1><?php echo esc($hospital['name']); ?></h1>
    <p class="lede">A live snapshot of your ward.</p>

    <!-- Hospital Cover Photo Management -->
    <div class="card">
      <h3>Hospital cover photo</h3>
      <?php if ($coverPhotoUrl): ?>
        <img class="hospital-detail-cover" src="<?php echo esc($coverPhotoUrl); ?>" alt="<?php echo esc($hospital['name']); ?> cover photo" style="margin-bottom: 16px;">
      <?php else: ?>
        <img class="hospital-detail-cover" src="https://images.unsplash.com/photo-1587351021759-3e566b2af12a?w=600&h=300&fit=crop" alt="<?php echo esc($hospital['name']); ?> cover photo" style="margin-bottom: 16px;">
      <?php endif; ?>

      <form method="post" enctype="multipart/form-data">
        <?php echo csrf_field(); ?>
        <input type="hidden" name="upload_cover" value="1">
        
        <div class="field">
          <label>Choose a cover photo</label>
          <input type="file" name="cover_photo" accept=".jpg,.jpeg,.png,.webp" required>
          <small>Use one image so patients can see the hospital design.</small>
        </div>
        
        <button type="submit" class="btn btn-primary">Upload cover photo</button>
      </form>
    </div>

    <!-- Ward Statistics Cards Grid -->
    <div class="stat-grid">
      <div class="stat-card">
        <div class="label">Total beds</div>
        <div class="num"><?php echo $totalBeds; ?></div>
      </div>
      
      <div class="stat-card">
        <div class="label">Available</div>
        <div class="num" style="color: var(--teal);"><?php echo $availableBeds; ?></div>
      </div>

      <div class="stat-card">
        <div class="label">Occupied</div>
        <div class="num" style="color: var(--rust);"><?php echo $occupiedBeds; ?></div>
      </div>

      <div class="stat-card">
        <div class="label">Pending requests</div>
        <div class="num" style="color: var(--amber);"><?php echo $pendingCount; ?></div>
      </div>
    </div>

    <!-- Live Ward Map Grid -->
    <div class="card">
      <h3>Ward map</h3>
      
      <div class="wardmap">
        <?php foreach ($wardCells as $cell): ?>
          <div class="cell <?php echo esc($cell['status']); ?>"></div>
        <?php endforeach; ?>
      </div>

      <div class="legend">
        <span><i style="background: var(--teal);"></i>Available</span>
        <span><i style="background: var(--rust);"></i>Occupied</span>
      </div>
    </div>

    <!-- Recent Booking Activity -->
    <div class="card">
      <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
        <h3 style="margin: 0;">Recent bookings</h3>
        <a class="btn btn-sm" href="<?php echo BASE_URL; ?>/admin/bookings.php">Manage bookings &rarr;</a>
      </div>

      <?php if (empty($recentBookings)): ?>
        <div class="empty">
          <h4>No bookings yet</h4>
        </div>
      <?php else: ?>
        <table>
          <thead>
            <tr>
              <th>Patient</th>
              <th>Category</th>
              <th>Date</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($recentBookings as $b): ?>
              <tr>
                <td><?php echo esc($b['patient_name']); ?></td>
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

