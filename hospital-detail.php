<?php
require_once __DIR__ . '/../config/functions.php';
require_role('user');
$u = current_user();

$id = (int)($_GET['id'] ?? 0);
$h = hospital_by_id($id);
if (!$h || $h['status'] !== 'approved') {
    flash('error', 'That hospital could not be found.');
    redirect('/user/hospitals.php');
}

$cats = categories_of_hospital($id);
$specs = array_filter(array_map('trim', explode(',', $h['specializations'])));
$cover = hospital_cover_photo($id);

$active = 'hospitals';
$pageTitle = $h['name'];
include __DIR__ . '/../includes/patient-head.php';
include __DIR__ . '/../includes/patient-nav.php';
include __DIR__ . '/../includes/patient-main-open.php';
?>
    <div class="mb-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
      <div>
        <a href="<?php echo BASE_URL; ?>/user/hospitals.php" class="text-[13px] text-[#06b6d4] hover:underline inline-flex items-center gap-1 mb-2">
          <span class="material-symbols-outlined text-[16px]">arrow_back</span>
          <span>Back to hospitals</span>
        </a>
        <h1 class="text-[28px] md:text-[32px] font-semibold text-[#161d19] font-['Plus_Jakarta_Sans'] tracking-[-0.01em]"><?php echo esc($h['name']); ?></h1>
        <p class="text-[14px] md:text-[15px] text-[#3c4a42]"><?php echo esc($h['city']); ?> · <?php echo esc($h['type']); ?> Hospital</p>
      </div>

      <div>
        <a href="<?php echo BASE_URL; ?>/user/book-bed.php?hospital_id=<?php echo $h['id']; ?>" class="bg-[#06b6d4] text-white px-5 py-2.5 rounded-xl text-[14px] font-semibold hover:bg-[#0891b2] transition-colors shadow-sm inline-flex items-center gap-2">
          <span class="material-symbols-outlined text-[18px]">add_circle</span>
          <span>Book a bed here</span>
        </a>
      </div>
    </div>

    <!-- Hospital Cover & Info Card -->
    <div class="bg-white border border-[#bbcabf] rounded-2xl overflow-hidden shadow-sm mb-8">
      <div class="h-56 md:h-72 bg-[#ecfeff] overflow-hidden">
        <img class="w-full h-full object-cover" src="<?php echo esc($cover ?: 'https://images.unsplash.com/photo-1587351021759-3e566b2af12a?w=1200&h=400&fit=crop'); ?>" alt="<?php echo esc($h['name']); ?>" />
      </div>
      <div class="p-6">
        <h3 class="text-[16px] font-semibold text-[#161d19] mb-2">Specializations</h3>
        <div class="flex flex-wrap gap-2">
          <?php foreach ($specs as $s): ?>
            <span class="bg-[#ecfeff] text-[#06b6d4] border border-[#bbcabf] px-3 py-1 rounded-full text-[12px] font-medium"><?php echo esc($s); ?></span>
          <?php endforeach; ?>
        </div>
      </div>
    </div>

    <!-- Bed Categories & Availability -->
    <div class="bg-white border border-[#bbcabf] rounded-2xl shadow-sm overflow-hidden mb-8">
      <div class="px-6 py-4 border-b border-[#bbcabf] bg-[#ecfeff]">
        <h2 class="text-[16px] font-semibold text-[#161d19]">Bed Categories & Live Availability</h2>
      </div>

      <?php if (empty($cats)): ?>
        <div class="p-8 text-center text-[#3c4a42]">
          <p>No bed categories set up yet for this hospital.</p>
        </div>
      <?php else: ?>
        <div class="overflow-x-auto">
          <table class="w-full text-left booking-table">
            <thead>
              <tr>
                <th class="px-5 py-3 text-[11px] font-semibold text-[#3c4a42] uppercase tracking-wider">CATEGORY</th>
                <th class="px-5 py-3 text-[11px] font-semibold text-[#3c4a42] uppercase tracking-wider">TOTAL BEDS</th>
                <th class="px-5 py-3 text-[11px] font-semibold text-[#3c4a42] uppercase tracking-wider">AVAILABLE</th>
                <th class="px-5 py-3 text-[11px] font-semibold text-[#3c4a42] uppercase tracking-wider">OCCUPIED</th>
                <th class="px-5 py-3 text-[11px] font-semibold text-[#3c4a42] uppercase tracking-wider text-right">ACTION</th>
              </tr>
            </thead>
            <tbody>
            <?php foreach ($cats as $c):
              $avail = available_count($c['id']);
              $occ = occupied_count($c['id']);
            ?>
              <tr class="hover:bg-[#ecfeff] transition-colors">
                <td class="px-5 py-3.5 font-semibold text-[#161d19]"><?php echo esc($c['name']); ?></td>
                <td class="px-5 py-3.5 text-[#3c4a42]"><?php echo (int)$c['total']; ?></td>
                <td class="px-5 py-3.5 font-bold text-[#06b6d4]"><?php echo $avail; ?> open</td>
                <td class="px-5 py-3.5 text-[#ba1a1a]"><?php echo $occ; ?></td>
                <td class="px-5 py-3.5 text-right">
                  <?php if ($avail > 0): ?>
                    <a class="bg-[#06b6d4] text-white px-3.5 py-1.5 rounded-lg text-[12px] font-semibold hover:bg-[#0891b2] transition-colors inline-block" href="<?php echo BASE_URL; ?>/user/book-bed.php?hospital_id=<?php echo $h['id']; ?>&category_id=<?php echo $c['id']; ?>">Book now</a>
                  <?php else: ?>
                    <span class="bg-[#f0f3f0] text-[#718076] px-3 py-1.5 rounded-lg text-[12px] font-medium inline-block cursor-not-allowed">Full</span>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
<?php include __DIR__ . '/../includes/patient-foot.php'; ?>
