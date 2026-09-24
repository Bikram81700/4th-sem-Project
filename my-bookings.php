<?php
require_once __DIR__ . '/../config/functions.php';
require_role('user');
$u = current_user();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $bookingId = (int)($_POST['booking_id'] ?? 0);
    $action    = $_POST['action'] ?? '';

    if ($action === 'delete') {
        $stmt = $pdo->prepare("SELECT * FROM bookings WHERE id = ? AND user_id = ?");
        $stmt->execute([$bookingId, $u['id']]);
        $b = $stmt->fetch();
        if ($b) {
            delete_booking($bookingId);
            $msg = 'Booking removed from your history.';
            flash('success', $msg);
            if (is_ajax()) {
                json_response([
                    'success' => true,
                    'booking_id' => $bookingId,
                    'action' => 'delete',
                    'message' => $msg
                ]);
            }
        } else {
            $msg = 'Booking not found.';
            flash('error', $msg);
            if (is_ajax()) {
                json_response([
                    'success' => false,
                    'message' => $msg
                ], 404);
            }
        }
    }
    redirect('/user/my-bookings.php');
}

$stmt = $pdo->prepare("
  SELECT b.*, h.name AS hospital_name, c.name AS category_name
  FROM bookings b
  JOIN hospitals h ON h.id = b.hospital_id
  JOIN bed_categories c ON c.id = b.category_id
  WHERE b.user_id = ?
  ORDER BY b.id DESC
");
$stmt->execute([$u['id']]);
$bookings = $stmt->fetchAll();

$active = 'bookings';
$pageTitle = 'My bookings';
include __DIR__ . '/../includes/patient-head.php';
include __DIR__ . '/../includes/patient-nav.php';
include __DIR__ . '/../includes/patient-main-open.php';
?>
    <div class="mb-6">
      <h1 class="text-[32px] font-semibold text-[#161d19] font-['Plus_Jakarta_Sans'] tracking-[-0.01em] mb-1">My bookings</h1><br>
      <p class="text-[16px] text-[#3c4a42]">Every request you've submitted, and where it stands.</p>
    </div>

    <?php if (empty($bookings)): ?>
      <div class="bg-white border border-[#bbcabf] rounded-xl p-10 text-center max-w-xl">
        <h4 class="text-[18px] font-semibold text-[#161d19] mb-2">No bookings yet</h4>
        <p class="text-[14px] text-[#3c4a42] mb-4">Browse hospitals and book a bed when you need one.</p>
        <a class="inline-block bg-[#06b6d4] text-white rounded-lg px-6 py-2.5 text-[12px] font-semibold hover:bg-[#0891b2]" href="<?php echo BASE_URL; ?>/user/hospitals.php">Browse hospitals</a>
      </div>
    <?php else: ?>
      <div class="bg-white border border-[#bbcabf] rounded-xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-left booking-table">
            <thead>
              <tr>
                <th class="px-5 py-3 text-[11px] font-semibold text-[#3c4a42] uppercase tracking-wider">PATIENT</th>
                <th class="px-5 py-3 text-[11px] font-semibold text-[#3c4a42] uppercase tracking-wider">HOSPITAL</th>
                <th class="px-5 py-3 text-[11px] font-semibold text-[#3c4a42] uppercase tracking-wider">CATEGORY</th>
                <th class="px-5 py-3 text-[11px] font-semibold text-[#3c4a42] uppercase tracking-wider">CONTACT</th>
                <th class="px-5 py-3 text-[11px] font-semibold text-[#3c4a42] uppercase tracking-wider">DATE</th>
                <th class="px-5 py-3 text-[11px] font-semibold text-[#3c4a42] uppercase tracking-wider">STATUS</th>
                <th class="px-5 py-3 text-[11px] font-semibold text-[#3c4a42] uppercase tracking-wider text-right">ACTION</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($bookings as $b): ?>
              <tr data-booking-id="<?php echo $b['id']; ?>" class="hover:bg-[#ecfeff] transition-colors">
                <td class="px-5 py-3.5 text-[#161d19] font-medium">
                  <?php echo esc($b['patient_name']); ?>
                  <?php if ($b['booking_for'] === 'other'): ?>
                    <span class="text-[#3c4a42] font-normal text-xs ml-1">(by attendant)</span>
                  <?php endif; ?>
                </td>
                <td class="px-5 py-3.5 text-[#161d19]"><?php echo esc($b['hospital_name']); ?></td>
                <td class="px-5 py-3.5 text-[#161d19]"><?php echo esc($b['category_name']); ?></td>
                <td class="px-5 py-3.5 text-[#3c4a42] font-mono text-sm"><?php echo esc($b['contact']); ?></td>
                <td class="px-5 py-3.5 text-[#3c4a42]"><?php echo esc($b['booking_date']); ?></td>
                <td class="px-5 py-3.5">
                  <?php 
                    $statusClass = 'status-pill-pending';
                    if ($b['status'] === 'accepted') $statusClass = 'status-pill-accepted';
                    else if ($b['status'] === 'discharged') $statusClass = 'status-pill-discharged';
                    else if ($b['status'] === 'rejected') $statusClass = 'status-pill-rejected';
                  ?>
                  <span class="status-pill <?php echo $statusClass; ?>"><?php echo esc(ucfirst($b['status'])); ?></span>
                </td>
                <td class="px-5 py-3.5 text-right whitespace-nowrap">
                  <form method="post" class="ajax-form" data-type="booking" style="display:inline;">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="booking_id" value="<?php echo $b['id']; ?>">
                    <input type="hidden" name="action" value="delete">
                    <button type="submit" data-confirm="Are you sure you want to remove this booking from your history?" class="text-[#ba1a1a] hover:text-[#93000a] text-[13px] font-medium inline-flex items-center gap-1 hover:underline cursor-pointer">
                      <span class="material-symbols-outlined text-[18px]">delete</span>
                      <span>Remove</span>
                    </button>
                  </form>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    <?php endif; ?>
<?php include __DIR__ . '/../includes/patient-foot.php'; ?>