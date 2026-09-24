<?php
require_once __DIR__ . '/../config/functions.php';
require_role('user');
$u = current_user();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE user_id = ?");
$stmt->execute([$u['id']]);
$totalBookings = (int)$stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE user_id = ? AND status = 'pending'");
$stmt->execute([$u['id']]);
$pendingBookings = (int)$stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE user_id = ? AND status = 'accepted'");
$stmt->execute([$u['id']]);
$activeBookings = (int)$stmt->fetchColumn();

$approvedHospitals = (int)$pdo->query("SELECT COUNT(*) FROM hospitals WHERE status='approved'")->fetchColumn();

$stmt = $pdo->prepare("
  SELECT b.*, h.name AS hospital_name, c.name AS category_name
  FROM bookings b
  JOIN hospitals h ON h.id = b.hospital_id
  JOIN bed_categories c ON c.id = b.category_id
  WHERE b.user_id = ?
  ORDER BY b.id DESC LIMIT 5
");
$stmt->execute([$u['id']]);
$recent = $stmt->fetchAll();

$active = 'dashboard';
$pageTitle = 'Dashboard';
include __DIR__ . '/../includes/patient-head.php';
include __DIR__ . '/../includes/patient-nav.php';
include __DIR__ . '/../includes/patient-main-open.php';

$firstName = $u ? explode(' ', $u['name'])[0] : '';
?>
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-4 mb-6">
      <div>
        <h1 class="text-[28px] md:text-[32px] font-semibold text-[#161d19] font-['Plus_Jakarta_Sans'] tracking-[-0.01em] mb-1">Welcome back, <?php echo esc($firstName); ?></h1> <br>
        <p class="text-[15px] md:text-[16px] text-[#3c4a42]">Here's where things stand across your bookings.</p>
      </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 md:gap-4 mb-6">
      <div class="bg-white border border-[#bbcabf] rounded-xl p-4 md:p-5 shadow-sm">
        <p class="text-[10px] md:text-[11px] font-semibold text-[#3c4a42] uppercase tracking-wider mb-1 md:mb-2">TOTAL BOOKINGS</p>
        <p class="text-[32px] md:text-[40px] font-bold text-[#161d19] font-['Plus_Jakarta_Sans']"><?php echo $totalBookings; ?></p>
      </div>
      <div class="bg-white border border-[#bbcabf] rounded-xl p-4 md:p-5 shadow-sm">
        <p class="text-[10px] md:text-[11px] font-semibold text-[#3c4a42] uppercase tracking-wider mb-1 md:mb-2">PENDING</p>
        <p class="text-[32px] md:text-[40px] font-bold text-[#06b6d4] font-['Plus_Jakarta_Sans']"><?php echo $pendingBookings; ?></p>
      </div>
      <div class="bg-white border border-[#bbcabf] rounded-xl p-4 md:p-5 shadow-sm">
        <p class="text-[10px] md:text-[11px] font-semibold text-[#3c4a42] uppercase tracking-wider mb-1 md:mb-2">ACTIVE / ACCEPTED</p>
        <p class="text-[32px] md:text-[40px] font-bold text-[#161d19] font-['Plus_Jakarta_Sans']"><?php echo $activeBookings; ?></p>
      </div>
      <div class="bg-white border border-[#bbcabf] rounded-xl p-4 md:p-5 shadow-sm">
        <p class="text-[10px] md:text-[11px] font-semibold text-[#3c4a42] uppercase tracking-wider mb-1 md:mb-2">APPROVED HOSPITALS</p>
        <p class="text-[32px] md:text-[40px] font-bold text-[#161d19] font-['Plus_Jakarta_Sans']"><?php echo $approvedHospitals; ?></p>
      </div>
    </div>

    <!-- Recent Bookings -->
    <div class="bg-white border border-[#bbcabf] rounded-xl shadow-sm overflow-hidden">
      <div class="px-4 md:px-5 py-3 md:py-4 border-b border-[#bbcabf] bg-[#ecfeff] flex justify-between items-center">
        <h2 class="text-[16px] md:text-[18px] font-semibold text-[#161d19]">Recent bookings</h2>
        <a href="<?php echo BASE_URL; ?>/user/my-bookings.php" class="text-[13px] font-semibold text-[#06b6d4] hover:underline">View all &rarr;</a>
      </div>
      <?php if (empty($recent)): ?>
        <div class="p-8 md:p-10 text-center">
          <h4 class="text-[16px] md:text-[18px] font-semibold text-[#161d19] mb-2">No bookings yet</h4>
          <p class="text-[13px] md:text-[14px] text-[#3c4a42] mb-4">Browse hospitals and book a bed when you need one.</p>
          <a class="inline-block bg-[#06b6d4] text-white rounded-lg px-5 md:px-6 py-2 md:py-2.5 text-[11px] md:text-[12px] font-semibold hover:bg-[#0891b2]" href="<?php echo BASE_URL; ?>/user/hospitals.php">Browse hospitals</a>
        </div>
      <?php else: ?>
        <div class="overflow-x-auto">
          <table class="w-full text-left">
            <thead>
              <tr class="border-b border-[#bbcabf]">
                <th class="px-3 md:px-5 py-2 md:py-3 text-[10px] md:text-[11px] font-semibold text-[#3c4a42] uppercase tracking-wider">PATIENT</th>
                <th class="px-3 md:px-5 py-2 md:py-3 text-[10px] md:text-[11px] font-semibold text-[#3c4a42] uppercase tracking-wider">HOSPITAL</th>
                <th class="px-3 md:px-5 py-2 md:py-3 text-[10px] md:text-[11px] font-semibold text-[#3c4a42] uppercase tracking-wider">CATEGORY</th>
                <th class="px-3 md:px-5 py-2 md:py-3 text-[10px] md:text-[11px] font-semibold text-[#3c4a42] uppercase tracking-wider">DATE</th>
                <th class="px-3 md:px-5 py-2 md:py-3 text-[10px] md:text-[11px] font-semibold text-[#3c4a42] uppercase tracking-wider">STATUS</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($recent as $b): ?>
              <tr class="hover:bg-[#ecfeff] transition-colors border-b border-[#dde4dd]">
                <td class="px-3 md:px-5 py-3 md:py-3.5 text-[13px] md:text-[14px] text-[#161d19]">
                  <?php echo esc($b['patient_name']); ?>
                  <?php if ($b['booking_for'] === 'other'): ?>
                    <span class="text-[#3c4a42] text-[11px] md:text-xs">(by attendant)</span>
                  <?php endif; ?>
                </td>
                <td class="px-3 md:px-5 py-3 md:py-3.5 text-[13px] md:text-[14px] text-[#3c4a42]"><?php echo esc($b['hospital_name']); ?></td>
                <td class="px-3 md:px-5 py-3 md:py-3.5 text-[13px] md:text-[14px] text-[#3c4a42]"><?php echo esc($b['category_name']); ?></td>
                <td class="px-3 md:px-5 py-3 md:py-3.5 text-[13px] md:text-[14px] text-[#3c4a42]"><?php echo esc($b['booking_date']); ?></td>
                <td class="px-3 md:px-5 py-3 md:py-3.5">
                  <?php 
                    $statusClass = 'status-pill-pending';
                    if ($b['status'] === 'accepted') $statusClass = 'status-pill-accepted';
                    else if ($b['status'] === 'discharged') $statusClass = 'status-pill-discharged';
                    else if ($b['status'] === 'rejected') $statusClass = 'status-pill-rejected';
                  ?>
                  <span class="status-pill <?php echo $statusClass; ?> text-[11px] md:text-[12px]"><?php echo esc(ucfirst($b['status'])); ?></span>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>

    <!-- Action Button -->
    <div class="mt-6">
      <a class="inline-flex items-center gap-2 bg-[#06b6d4] text-white px-5 md:px-6 py-2.5 md:py-3 rounded-xl text-[14px] md:text-[15px] font-semibold hover:bg-[#0891b2] transition-colors shadow-sm" href="<?php echo BASE_URL; ?>/user/hospitals.php">
        <span class="material-symbols-outlined text-[20px]">add_circle</span>
        <span>Book a bed</span>
      </a>
    </div>
<?php include __DIR__ . '/../includes/patient-foot.php'; ?>