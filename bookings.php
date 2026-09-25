<?php
/**
 * BedTrack - Admin: Bookings & Patient History
 * Allows hospital administrators to review incoming booking requests,
 * assign beds upon acceptance, discharge patients, or delete records.
 */

require_once __DIR__ . '/../config/functions.php';

// Ensure user has hospital admin permissions
require_role('hospital');

$currentUser = current_user();
$hospitalId  = (int) $currentUser['hospital_id'];

// Handle action submissions (Accept, Reject, Discharge, Delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $bookingId = (int) ($_POST['booking_id'] ?? 0);
    $action    = $_POST['action'] ?? '';
    $message   = '';
    $newStatus = null;
    $flashType = 'success';

    // Fetch target booking record for this hospital
    $stmt = $pdo->prepare("SELECT * FROM bookings WHERE id = ? AND hospital_id = ?");
    $stmt->execute([$bookingId, $hospitalId]);
    $booking = $stmt->fetch();

    if ($booking) {
        if ($action === 'accept' && $booking['status'] === 'pending') {
            // Find an available bed in the requested category
            $bedStmt = $pdo->prepare("SELECT id FROM beds WHERE category_id = ? AND status = 'available' LIMIT 1");
            $bedStmt->execute([$booking['category_id']]);
            $availableBed = $bedStmt->fetch();

            if ($availableBed) {
                $pdo->beginTransaction();

                $pdo->prepare("UPDATE beds SET status = 'occupied' WHERE id = ?")
                    ->execute([$availableBed['id']]);

                $pdo->prepare("UPDATE bookings SET status = 'accepted' WHERE id = ?")
                    ->execute([$bookingId]);

                $pdo->commit();

                $newStatus = 'accepted';
                $message   = 'Booking accepted and a bed assigned.';
                flash('success', $message);
            } else {
                $message   = 'No available beds left in this category.';
                $flashType = 'error';
                flash('error', $message);
            }

        } elseif ($action === 'reject' && $booking['status'] === 'pending') {
            $pdo->prepare("UPDATE bookings SET status = 'rejected' WHERE id = ?")
                ->execute([$bookingId]);

            $newStatus = 'rejected';
            $message   = 'Booking rejected.';
            flash('success', $message);

        } elseif ($action === 'discharge' && $booking['status'] === 'accepted') {
            // Find an occupied bed in this category to release back to available
            $bedStmt = $pdo->prepare("SELECT id FROM beds WHERE category_id = ? AND status = 'occupied' LIMIT 1");
            $bedStmt->execute([$booking['category_id']]);
            $occupiedBed = $bedStmt->fetch();

            $pdo->beginTransaction();

            if ($occupiedBed) {
                $pdo->prepare("UPDATE beds SET status = 'available' WHERE id = ?")
                    ->execute([$occupiedBed['id']]);
            }

            $pdo->prepare("UPDATE bookings SET status = 'discharged' WHERE id = ?")
                ->execute([$bookingId]);

            $pdo->commit();

            $newStatus = 'discharged';
            $message   = 'Patient marked discharged and the bed freed up.';
            flash('success', $message);

        } elseif ($action === 'delete') {
            delete_booking($bookingId, $hospitalId);

            $message = 'Patient booking history record removed.';
            flash('success', $message);

            if (is_ajax()) {
                json_response([
                    'success'    => true,
                    'booking_id' => $bookingId,
                    'action'     => 'delete',
                    'message'    => $message,
                    'type'       => 'success'
                ]);
            }
        }
    }

    // Return JSON response if requested via AJAX
    if (is_ajax()) {
        json_response([
            'success'    => $newStatus !== null,
            'booking_id' => $bookingId,
            'action'     => $action,
            'new_status' => $newStatus,
            'badge'      => $newStatus ? badge($newStatus) : null,
            'message'    => $message,
            'type'       => $flashType
        ]);
    }

    redirect('/admin/bookings.php');
}

// Fetch all bookings for the hospital ordered by status priority
$stmt = $pdo->prepare("
    SELECT b.*, c.name AS category_name 
    FROM bookings b
    JOIN bed_categories c ON c.id = b.category_id
    WHERE b.hospital_id = ? 
    ORDER BY FIELD(b.status, 'pending', 'accepted', 'discharged', 'rejected'), b.id DESC
");
$stmt->execute([$hospitalId]);
$bookings = $stmt->fetchAll();

// Page layout configuration
$active    = 'bookings';
$role      = 'hospital';
$pageTitle = 'Bookings & Patient History';

include __DIR__ . '/../includes/header.php';
?>

<div class="app-shell">
  <?php include __DIR__ . '/../includes/sidebar.php'; ?>

  <div class="main">
    <h1>Bookings &amp; Patient History</h1>
    <p class="lede">Review, accept, reject, discharge patients, and manage patient records.</p>

    <?php if (empty($bookings)): ?>
      <div class="empty">
        <h4>No bookings yet</h4>
        <p>Requests from patients will show up here.</p>
      </div>
    <?php else: ?>
      <div class="card">
        <table>
          <thead>
            <tr>
              <th>Patient</th>
              <th>Category</th>
              <th>Contact</th>
              <th>Date</th>
              <th>Status</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($bookings as $b): ?>
              <tr data-booking-id="<?php echo $b['id']; ?>">
                <td>
                  <strong><?php echo esc($b['patient_name']); ?></strong>
                  <?php if ($b['booking_for'] === 'other'): ?>
                    <span style="color: var(--ink-soft); font-size: 12px;">(by attendant)</span>
                  <?php endif; ?>
                </td>
                <td><?php echo esc($b['category_name']); ?></td>
                <td style="font-family: var(--font-mono); font-size: 12px;">
                  <?php echo esc($b['contact'] ?: '—'); ?>
                </td>
                <td style="font-family: var(--font-mono); font-size: 12px;">
                  <?php echo esc($b['booking_date']); ?>
                </td>
                <td class="status-cell">
                  <?php echo badge($b['status']); ?>
                </td>
                <td class="table-actions action-cell" style="white-space: nowrap;">
                  <div style="display: inline-flex; align-items: center; gap: 6px;">
                    <div class="status-action-wrapper" style="display: inline-block;">
                      <?php if ($b['status'] === 'pending'): ?>
                        <form method="post" class="ajax-form" data-type="booking" style="display: inline;">
                          <?php echo csrf_field(); ?>
                          <input type="hidden" name="booking_id" value="<?php echo $b['id']; ?>">
                          <input type="hidden" name="action" value="accept">
                          <button class="btn btn-sm btn-primary" type="submit">Accept</button>
                        </form>

                        <form method="post" class="ajax-form" data-type="booking" style="display: inline;">
                          <?php echo csrf_field(); ?>
                          <input type="hidden" name="booking_id" value="<?php echo $b['id']; ?>">
                          <input type="hidden" name="action" value="reject">
                          <button class="btn btn-sm btn-outline" type="submit" data-confirm="Reject this booking request?">Reject</button>
                        </form>
                      <?php elseif ($b['status'] === 'accepted'): ?>
                        <form method="post" class="ajax-form" data-type="booking" style="display: inline;">
                          <?php echo csrf_field(); ?>
                          <input type="hidden" name="booking_id" value="<?php echo $b['id']; ?>">
                          <input type="hidden" name="action" value="discharge">
                          <button class="btn btn-sm" type="submit" data-confirm="Mark this patient as discharged and free the bed?">Mark discharged</button>
                        </form>
                      <?php endif; ?>
                    </div>

                    <form method="post" class="ajax-form" data-type="booking" style="display: inline;">
                      <?php echo csrf_field(); ?>
                      <input type="hidden" name="booking_id" value="<?php echo $b['id']; ?>">
                      <input type="hidden" name="action" value="delete">
                      <button class="btn btn-sm btn-danger" type="submit" data-confirm="Permanently remove this patient history record?">
                        <i class="fas fa-trash-alt" style="margin-right: 4px;"></i>Delete
                      </button>
                    </form>
                  </div>
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

