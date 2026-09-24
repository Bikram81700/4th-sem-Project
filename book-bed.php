<?php
require_once __DIR__ . '/../config/functions.php';
require_role('user');
$u = current_user();

$hospitalId = (int)($_GET['hospital_id'] ?? $_POST['hospital_id'] ?? 0);
$categoryId = (int)($_GET['category_id'] ?? $_POST['category_id'] ?? 0);

$hospitals = $pdo->query("SELECT * FROM hospitals WHERE status='approved' ORDER BY name")->fetchAll();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $hospitalId  = (int)$_POST['hospital_id'];
    $categoryId  = (int)$_POST['category_id'];
    $bookingFor  = ($_POST['booking_for'] ?? '') === 'other' ? 'other' : 'self';
    $patientName = trim($_POST['patient_name'] ?? '');
    $contact     = trim($_POST['contact'] ?? '');
    $date        = trim($_POST['booking_date'] ?? '');

    $h = hospital_by_id($hospitalId);
    $c = category_by_id($categoryId);

    if (!$h || $h['status'] !== 'approved' || !$c || (int)$c['hospital_id'] !== $hospitalId) {
        $errors[] = 'Please choose a valid hospital and bed category.';
    }
    if ($patientName === '' || $contact === '' || $date === '') {
        $errors[] = 'Please fill in the patient name, contact number, and date.';
    } else {
        if (!validate_phone($contact)) {
            $errors[] = 'Please enter a valid 10-digit contact phone number.';
        }
        if (!validate_future_or_today_date($date)) {
            $errors[] = 'Booking date cannot be in the past. Please select today or a future date.';
        }
    }
    if (empty($errors) && available_count($categoryId) === 0) {
        $errors[] = 'No beds are currently available in that category. Please choose another category or hospital.';
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare("
          INSERT INTO bookings (user_id, hospital_id, category_id, patient_name, booking_for, contact, booking_date, status, created_at)
          VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', NOW())
        ");
        $stmt->execute([$u['id'], $hospitalId, $categoryId, $patientName, $bookingFor, $contact, $date]);

        flash('success', 'Your booking request has been submitted. The hospital will review it shortly.');
        redirect('/user/my-bookings.php');
    }
}

$categories = $hospitalId ? categories_of_hospital($hospitalId) : [];

$active = 'hospitals';
$pageTitle = 'Book a bed';
include __DIR__ . '/../includes/patient-head.php';
include __DIR__ . '/../includes/patient-nav.php';
include __DIR__ . '/../includes/patient-main-open.php';
?>
    <div class="mb-6">
      <a href="<?php echo BASE_URL; ?>/user/hospitals.php" class="text-[13px] text-[#06b6d4] hover:underline inline-flex items-center gap-1 mb-2">
        <span class="material-symbols-outlined text-[16px]">arrow_back</span>
        <span>Back to hospitals</span>
      </a>
      <h1 class="text-[28px] md:text-[32px] font-semibold text-[#161d19] font-['Plus_Jakarta_Sans'] tracking-[-0.01em] mb-1">Book a bed</h1>
      <p class="text-[14px] md:text-[15px] text-[#3c4a42]">Submit your request — the hospital admin will review and respond promptly.</p>
    </div>

    <?php if ($errors): ?>
      <div class="mb-6 rounded-xl border border-red-200 bg-red-50 text-[#ba1a1a] p-4 text-[14px] max-w-2xl">
        <div class="flex items-center gap-2 font-semibold mb-1">
          <span class="material-symbols-outlined text-[18px]">error</span>
          <span>Please fix the following issues:</span>
        </div>
        <ul class="list-disc list-inside space-y-1">
          <?php foreach ($errors as $e): ?>
            <li><?php echo esc($e); ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <div class="bg-white border border-[#bbcabf] rounded-2xl shadow-sm max-w-2xl overflow-hidden mb-8">
      <div class="px-6 py-4 border-b border-[#bbcabf] bg-[#ecfeff]">
        <h2 class="text-[16px] font-semibold text-[#161d19]">Booking Details</h2>
      </div>

      <form method="post" id="bookingForm" class="p-6 space-y-5">
        <?php echo csrf_field(); ?>

        <div>
          <label class="block text-[13px] font-semibold text-[#161d19] mb-1.5">Select Hospital</label>
          <select name="hospital_id" id="hospitalSelect" required onchange="window.location.href='<?php echo BASE_URL; ?>/user/book-bed.php?hospital_id='+this.value"
                  class="w-full bg-white border border-[#bbcabf] rounded-xl px-4 py-2.5 text-[14px] focus:outline-none focus:ring-2 focus:ring-[#06b6d4] focus:border-transparent text-[#161d19] cursor-pointer">
            <option value="">Choose a hospital</option>
            <?php foreach ($hospitals as $h): ?>
              <option value="<?php echo $h['id']; ?>" <?php echo $hospitalId == $h['id'] ? 'selected' : ''; ?>>
                <?php echo esc($h['name']); ?> — <?php echo esc($h['city']); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <?php if ($hospitalId): ?>
        <div>
          <label class="block text-[13px] font-semibold text-[#161d19] mb-1.5">Bed Category</label>
          <select name="category_id" id="categorySelect" required
                  class="w-full bg-white border border-[#bbcabf] rounded-xl px-4 py-2.5 text-[14px] focus:outline-none focus:ring-2 focus:ring-[#06b6d4] focus:border-transparent text-[#161d19] cursor-pointer">
            <option value="">Select a bed category</option>
            <?php foreach ($categories as $c):
              $avail = available_count($c['id']);
            ?>
              <option value="<?php echo $c['id']; ?>" data-avail="<?php echo $avail; ?>" <?php echo $categoryId == $c['id'] ? 'selected' : ''; ?> <?php echo $avail === 0 ? 'disabled' : ''; ?>>
                <?php echo esc($c['name']); ?> (<?php echo $avail; ?> available)
              </option>
            <?php endforeach; ?>
          </select>
          <p id="categoryAvailHint" class="text-[12px] text-[#06b6d4] font-medium mt-1"></p>
        </div>

        <div>
          <label class="block text-[13px] font-semibold text-[#161d19] mb-2">Booking For</label>
          <div class="flex items-center gap-6 text-[14px]">
            <label class="flex items-center gap-2 cursor-pointer text-[#161d19]">
              <input type="radio" name="booking_for" value="self" checked class="text-[#06b6d4] focus:ring-[#06b6d4]">
              <span>Myself</span>
            </label>
            <label class="flex items-center gap-2 cursor-pointer text-[#161d19]">
              <input type="radio" name="booking_for" value="other" class="text-[#06b6d4] focus:ring-[#06b6d4]">
              <span>Someone else (attendant)</span>
            </label>
          </div>
        </div>

        <div id="patientNameField">
          <label class="block text-[13px] font-semibold text-[#161d19] mb-1.5">Patient Name</label>
          <input type="text" name="patient_name" id="patientNameInput" data-self-name="<?php echo esc($u['name']); ?>" value="<?php echo esc($u['name']); ?>" readonly required
                 class="w-full bg-white border border-[#bbcabf] rounded-xl px-4 py-2.5 text-[14px] focus:outline-none focus:ring-2 focus:ring-[#06b6d4] focus:border-transparent text-[#161d19]" />
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label class="block text-[13px] font-semibold text-[#161d19] mb-1.5">Contact Number (10 digits)</label>
            <input type="text" name="contact" value="<?php echo esc($u['phone'] ?? ''); ?>" maxlength="10" pattern="[0-9]{10}" required placeholder="98XXXXXXXX"
                   class="w-full bg-white border border-[#bbcabf] rounded-xl px-4 py-2.5 text-[14px] focus:outline-none focus:ring-2 focus:ring-[#06b6d4] focus:border-transparent text-[#161d19]" />
          </div>
          <div>
            <label class="block text-[13px] font-semibold text-[#161d19] mb-1.5">Date Needed</label>
            <input type="date" name="booking_date" min="<?php echo date('Y-m-d'); ?>" required
                   class="w-full bg-white border border-[#bbcabf] rounded-xl px-4 py-2.5 text-[14px] focus:outline-none focus:ring-2 focus:ring-[#06b6d4] focus:border-transparent text-[#161d19]" />
          </div>
        </div>

        <div class="pt-3">
          <button type="submit" class="bg-[#06b6d4] text-white px-6 py-2.5 rounded-xl text-[14px] font-semibold hover:bg-[#0891b2] transition-colors shadow-sm inline-flex items-center gap-2">
            <span class="material-symbols-outlined text-[18px]">send</span>
            <span>Submit booking request</span>
          </button>
        </div>
        <?php else: ?>
          <div class="p-4 rounded-xl bg-[#ecfeff] text-[#3c4a42] text-[14px] flex items-center gap-2">
            <span class="material-symbols-outlined text-[20px] text-[#06b6d4]">info</span>
            <span>Please select a hospital above to view and choose available bed categories.</span>
          </div>
        <?php endif; ?>
      </form>
    </div>

    <script src="<?php echo BASE_URL; ?>/assets/js/booking.js"></script>
<?php include __DIR__ . '/../includes/patient-foot.php'; ?>
