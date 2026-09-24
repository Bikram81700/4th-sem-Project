<?php
require_once __DIR__ . '/../config/functions.php';
require_role('user');
$u = current_user();

$q    = trim($_GET['q'] ?? '');
$city = trim($_GET['city'] ?? '');

$sql = "SELECT * FROM hospitals WHERE status = 'approved'";
$params = [];
if ($q !== '') {
    $sql .= " AND (name LIKE ? OR specializations LIKE ?)";
    $params[] = "%$q%";
    $params[] = "%$q%";
}
if ($city !== '') {
    $sql .= " AND city = ?";
    $params[] = $city;
}
$sql .= " ORDER BY name";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$hospitals = $stmt->fetchAll();

$cities = $pdo->query("SELECT DISTINCT city FROM hospitals WHERE status='approved' ORDER BY city")->fetchAll(PDO::FETCH_COLUMN);

$active = 'hospitals';
$pageTitle = 'Browse hospitals';
include __DIR__ . '/../includes/patient-head.php';
include __DIR__ . '/../includes/patient-nav.php';
include __DIR__ . '/../includes/patient-main-open.php';
?>
    <div class="mb-6">
      <h1 class="text-[28px] md:text-[32px] font-semibold text-[#161d19] font-['Plus_Jakarta_Sans'] tracking-[-0.01em] mb-1">Browse hospitals</h1><br>
      <p class="text-[14px] text-[#3c4a42]">Search by name/specialty and filter by city.</p>
    </div>

    <form id="hospital-search-form" method="get" class="mb-8 flex flex-col md:flex-row gap-3 max-w-3xl">
      <div class="flex-1">
        <input id="hospital-search-query" class="w-full bg-white border border-[#bbcabf] rounded-lg px-4 py-2.5 text-[14px] focus:outline-none focus:ring-2 focus:ring-[#06b6d4] focus:border-transparent text-[#161d19] placeholder:text-[#3c4a42]" placeholder="Search by hospital name or specialty" type="text" name="q" value="<?php echo esc($q); ?>" autocomplete="off" />
      </div>
      <div class="md:w-56">
        <select id="hospital-search-city" name="city" class="w-full bg-white border border-[#bbcabf] rounded-lg px-4 py-2.5 text-[14px] appearance-none focus:outline-none focus:ring-2 focus:ring-[#06b6d4] focus:border-transparent text-[#161d19] cursor-pointer">
          <option value="">All cities</option>
          <?php foreach ($cities as $c): ?>
            <option value="<?php echo esc($c); ?>" <?php echo $city === $c ? 'selected' : ''; ?>><?php echo esc($c); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <button class="bg-[#161d19] text-white rounded-lg px-6 py-2.5 text-[12px] font-semibold hover:bg-[#3c4a42] transition-colors whitespace-nowrap self-start md:self-auto shadow-sm" type="submit">Search</button>
    </form>

    <div id="hospitals-results-container">
    <?php if (empty($hospitals)): ?>
      <div class="bg-white border border-[#bbcabf] rounded-xl p-8 md:p-10 text-center max-w-xl">
        <h4 class="text-[16px] md:text-[18px] font-semibold text-[#161d19] mb-2">No hospitals match</h4>
        <p class="text-[14px] text-[#3c4a42]">Try a different search or clear the filters.</p>
      </div>
    <?php else: ?>
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
        <?php foreach ($hospitals as $h):
          $cats = categories_of_hospital($h['id']);
          $totalAvail = available_beds_of_hospital($h['id']);
          $totalBedsH = total_beds_of_hospital($h['id']);
          $specs = array_filter(array_map('trim', explode(',', $h['specializations'])));
          $cover = hospital_cover_photo($h['id']);
        ?>
        <div class="bg-white rounded-2xl border border-[#bbcabf] overflow-hidden hover:shadow-md hover:border-[#06b6d4]/40 transition-all flex flex-col group">
          <div class="h-44 bg-[#ecfeff] overflow-hidden flex-shrink-0 relative">
            <img class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" src="<?php echo esc($cover ?: 'https://images.unsplash.com/photo-1587351021759-3e566b2af12a?w=600&h=300&fit=crop'); ?>" alt="<?php echo esc($h['name']); ?>" />
            <div class="absolute top-3 right-3 bg-white/90 backdrop-blur-sm px-2.5 py-1 rounded-full text-[11px] font-bold text-[#161d19] border border-[#bbcabf]/60 shadow-sm">
              <?php echo esc($h['city']); ?>
            </div>
          </div>
          <div class="p-5 flex-1 flex flex-col">
            <h3 class="text-[17px] font-semibold text-[#161d19] mb-1 group-hover:text-[#06b6d4] transition-colors">
              <a href="<?php echo BASE_URL; ?>/user/hospital-detail.php?id=<?php echo $h['id']; ?>">
                <?php echo esc($h['name']); ?>
              </a>
            </h3>
            <p class="text-[13px] text-[#3c4a42] mb-3"><?php echo esc($h['type']); ?> Hospital</p>
            
            <div class="flex flex-wrap gap-1.5 mb-4">
              <?php foreach ($specs as $s): ?>
                <span class="bg-[#ecfeff] text-[#161d19] border border-[#bbcabf] px-2.5 py-0.5 rounded-full text-[11px] font-medium"><?php echo esc($s); ?></span>
              <?php endforeach; ?>
            </div>
            
            <div class="flex justify-between items-center border-t border-[#bbcabf] pt-3.5 mt-auto">
              <div>
                <?php if ($totalAvail === 0): ?>
                  <div class="text-[12px] font-semibold text-[#ba1a1a] flex items-center gap-1">
                    <span class="material-symbols-outlined text-[16px]">cancel</span>
                    <span>0 / <?php echo $totalBedsH; ?> beds</span>
                  </div>
                <?php else: ?>
                  <div class="text-[12px] font-semibold text-[#06b6d4] flex items-center gap-1">
                    <span class="material-symbols-outlined text-[16px]">check_circle</span>
                    <span><?php echo $totalAvail; ?> / <?php echo $totalBedsH; ?> beds open</span>
                  </div>
                <?php endif; ?>
                <p class="text-[11px] text-[#3c4a42]"><?php echo count($cats); ?> categories</p>
              </div>

              <div class="flex items-center gap-2">
                <a href="<?php echo BASE_URL; ?>/user/hospital-detail.php?id=<?php echo $h['id']; ?>" class="text-[12px] font-semibold text-[#3c4a42] hover:text-[#161d19] px-2.5 py-1.5 rounded-lg border border-[#bbcabf] hover:bg-[#ecfeff] transition-colors">
                  Details
                </a>
                <a href="<?php echo BASE_URL; ?>/user/book-bed.php?hospital_id=<?php echo $h['id']; ?>" class="bg-[#06b6d4] text-white text-[12px] font-semibold px-3.5 py-1.5 rounded-lg hover:bg-[#0891b2] transition-colors shadow-sm inline-flex items-center gap-1">
                  <span>Book</span>
                  <span class="material-symbols-outlined text-[14px]">arrow_forward</span>
                </a>
              </div>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
    </div>
<?php include __DIR__ . '/../includes/patient-foot.php'; ?>