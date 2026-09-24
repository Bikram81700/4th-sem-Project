<?php
require_once __DIR__ . '/../config/functions.php';
require_role('user');
$u = current_user();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $name      = trim($_POST['name'] ?? '');
    $phone     = trim($_POST['phone'] ?? '');
    $blood     = trim($_POST['blood_group'] ?? '');
    $address   = trim($_POST['address'] ?? '');
    $emergency = trim($_POST['emergency'] ?? '');
    $newPass   = $_POST['new_password'] ?? '';

    if ($name === '') $errors[] = 'Name cannot be empty.';
    if ($phone !== '' && !validate_phone($phone)) {
        $errors[] = 'Please enter a valid 10-digit primary phone number.';
    }
    if ($emergency !== '' && !validate_phone($emergency)) {
        $errors[] = 'Please enter a valid 10-digit emergency contact number.';
    }
    if ($phone !== '' && $emergency !== '' && $phone === $emergency) {
        $errors[] = 'Emergency contact number must be different from your primary phone number.';
    }
    if ($blood !== '' && !validate_blood_group($blood)) {
        $errors[] = 'Invalid blood group selected.';
    }

    if (empty($errors)) {
        if ($newPass !== '') {
            if (strlen($newPass) < 6) {
                $errors[] = 'New password must be at least 6 characters.';
            } else {
                $stmt = $pdo->prepare("UPDATE users SET name=?, phone=?, blood_group=?, address=?, emergency_contact=?, password=? WHERE id=?");
                $stmt->execute([$name, $phone, $blood, $address, $emergency, password_hash($newPass, PASSWORD_DEFAULT), $u['id']]);
            }
        } else {
            $stmt = $pdo->prepare("UPDATE users SET name=?, phone=?, blood_group=?, address=?, emergency_contact=? WHERE id=?");
            $stmt->execute([$name, $phone, $blood, $address, $emergency, $u['id']]);
        }
        if (empty($errors)) {
            flash('success', 'Profile updated successfully.');
            redirect('/user/profile.php');
        }
    }
    $u = array_merge($u, compact('name', 'phone', 'blood', 'address', 'emergency'));
}

$active = 'profile';
$pageTitle = 'Profile';
include __DIR__ . '/../includes/patient-head.php';
include __DIR__ . '/../includes/patient-nav.php';
include __DIR__ . '/../includes/patient-main-open.php';
?>
    <div class="mb-6">
      <h1 class="text-[28px] md:text-[32px] font-semibold text-[#161d19] font-['Plus_Jakarta_Sans'] tracking-[-0.01em] mb-1">Profile</h1>
      <p class="text-[14px] md:text-[15px] text-[#3c4a42]">Keep your contact details current so hospitals can reach you.</p>
    </div>

    <?php if ($errors): ?>
      <div class="mb-6 rounded-xl border border-red-200 bg-red-50 text-[#ba1a1a] p-4 text-[14px] max-w-2xl">
        <div class="flex items-center gap-2 font-semibold mb-1">
          <span class="material-symbols-outlined text-[18px]">error</span>
          <span>Please correct the following:</span>
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
        <h2 class="text-[16px] font-semibold text-[#161d19]">Personal & Contact Details</h2>
      </div>

      <form method="post" class="p-6 space-y-5">
        <?php echo csrf_field(); ?>

        <div>
          <label class="block text-[13px] font-semibold text-[#161d19] mb-1.5">Full Name</label>
          <input type="text" name="name" value="<?php echo esc($u['name']); ?>" required
                 class="w-full bg-white border border-[#bbcabf] rounded-xl px-4 py-2.5 text-[14px] focus:outline-none focus:ring-2 focus:ring-[#06b6d4] focus:border-transparent text-[#161d19]" />
        </div>

        <div>
          <label class="block text-[13px] font-semibold text-[#161d19] mb-1.5">Email Address</label>
          <input type="email" value="<?php echo esc($u['email']); ?>" disabled
                 class="w-full bg-[#ecfeff] border border-[#dde4dd] rounded-xl px-4 py-2.5 text-[14px] text-[#55635b] cursor-not-allowed" />
          <p class="text-[12px] text-[#55635b] mt-1">Email is tied to your account and cannot be changed.</p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label class="block text-[13px] font-semibold text-[#161d19] mb-1.5">Phone Number (10 digits)</label>
            <input type="text" name="phone" value="<?php echo esc($u['phone'] ?? ''); ?>" maxlength="10" pattern="[0-9]{10}" placeholder="98XXXXXXXX"
                   class="w-full bg-white border border-[#bbcabf] rounded-xl px-4 py-2.5 text-[14px] focus:outline-none focus:ring-2 focus:ring-[#06b6d4] focus:border-transparent text-[#161d19]" />
          </div>
          <div>
            <label class="block text-[13px] font-semibold text-[#161d19] mb-1.5">Blood Group</label>
            <input type="text" name="blood_group" value="<?php echo esc($u['blood_group'] ?? ''); ?>" placeholder="e.g. O+, A+, B-"
                   class="w-full bg-white border border-[#bbcabf] rounded-xl px-4 py-2.5 text-[14px] focus:outline-none focus:ring-2 focus:ring-[#06b6d4] focus:border-transparent text-[#161d19]" />
          </div>
        </div>

        <div>
          <label class="block text-[13px] font-semibold text-[#161d19] mb-1.5">Address</label>
          <input type="text" name="address" value="<?php echo esc($u['address'] ?? ''); ?>" placeholder="Street, City, Ward No."
                 class="w-full bg-white border border-[#bbcabf] rounded-xl px-4 py-2.5 text-[14px] focus:outline-none focus:ring-2 focus:ring-[#06b6d4] focus:border-transparent text-[#161d19]" />
        </div>

        <div>
          <label class="block text-[13px] font-semibold text-[#161d19] mb-1.5">Emergency Contact (10 digits)</label>
          <input type="text" name="emergency" value="<?php echo esc($u['emergency_contact'] ?? ''); ?>" maxlength="10" pattern="[0-9]{10}" placeholder="98XXXXXXXX"
                 class="w-full bg-white border border-[#bbcabf] rounded-xl px-4 py-2.5 text-[14px] focus:outline-none focus:ring-2 focus:ring-[#06b6d4] focus:border-transparent text-[#161d19]" />
        </div>

        <div class="pt-4 border-t border-[#bbcabf]">
          <h3 class="text-[14px] font-semibold text-[#161d19] mb-1">Change Password (Optional)</h3>
          <p class="text-[12px] text-[#3c4a42] mb-3">Leave blank if you wish to keep your current password.</p>
          <div class="password-field-wrapper">
            <input type="password" name="new_password" placeholder="Enter new password (min. 6 characters)"
                   class="w-full bg-white border border-[#bbcabf] rounded-xl px-4 py-2.5 text-[14px] focus:outline-none focus:ring-2 focus:ring-[#06b6d4] focus:border-transparent text-[#161d19]" />
          </div>
        </div>

        <div class="pt-2">
          <button type="submit" class="bg-[#06b6d4] text-white px-6 py-2.5 rounded-xl text-[14px] font-semibold hover:bg-[#0891b2] transition-colors shadow-sm inline-flex items-center gap-2">
            <span class="material-symbols-outlined text-[18px]">save</span>
            <span>Save changes</span>
          </button>
        </div>
      </form>
    </div>
<?php include __DIR__ . '/../includes/patient-foot.php'; ?>
