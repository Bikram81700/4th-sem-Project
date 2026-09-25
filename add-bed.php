<?php
/**
 * BedTrack - Admin: Add Bed Category
 * Allows hospital administrators to create a new bed category
 * and automatically populate initial available beds.
 */

require_once __DIR__ . '/../config/functions.php';

// Ensure user has hospital admin permissions
require_role('hospital');

$currentUser = current_user();
$hospitalId  = (int) $currentUser['hospital_id'];
$errors      = [];

// Handle form submission for adding a new bed category
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $categoryName = trim($_POST['name'] ?? '');
    $totalBeds   = (int) ($_POST['total'] ?? 0);

    // Validate category name
    if ($categoryName === '') {
        $errors[] = 'Category name is required.';
    } elseif (!preg_match('/^[a-zA-Z\s\-]+$/', $categoryName)) {
        $errors[] = 'Category name must contain only letters and spaces (no numbers).';
    } elseif (strlen($categoryName) < 2 || strlen($categoryName) > 60) {
        $errors[] = 'Category name must be between 2 and 60 characters.';
    }

    // Validate total bed count
    if ($totalBeds < 1 || $totalBeds > 500) {
        $errors[] = 'Total beds must be between 1 and 500.';
    }

    // If validation passes, create category and insert individual beds
    if (empty($errors)) {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("INSERT INTO bed_categories (hospital_id, name, total) VALUES (?, ?, ?)");
        $stmt->execute([$hospitalId, $categoryName, $totalBeds]);
        $categoryId = $pdo->lastInsertId();

        $insertBedStmt = $pdo->prepare("INSERT INTO beds (category_id, hospital_id, status) VALUES (?, ?, 'available')");
        for ($i = 0; $i < $totalBeds; $i++) {
            $insertBedStmt->execute([$categoryId, $hospitalId]);
        }

        $pdo->commit();

        flash('success', 'Bed category added.');
        redirect('/admin/manage-beds.php');
    }
}

// Page layout configuration
$active    = 'beds';
$role      = 'hospital';
$pageTitle = 'Add bed category';

include __DIR__ . '/../includes/header.php';
?>

<div class="app-shell">
  <?php include __DIR__ . '/../includes/sidebar.php'; ?>
  
  <div class="main">
    <h1>Add bed category</h1>
    <p class="lede">e.g. ICU, General Ward, Private Room, HDU.</p>

    <?php if (!empty($errors)): ?>
      <div class="field error" style="margin-bottom: 14px;">
        <?php foreach ($errors as $error): ?>
          <?php echo esc($error); ?><br>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <div class="card" style="max-width: 420px;">
      <form method="post">
        <?php echo csrf_field(); ?>
        
        <div class="field">
          <label>Category name</label>
          <input 
            type="text" 
            name="name" 
            pattern="[A-Za-z\s\-]+" 
            title="Category name must contain only letters and spaces." 
            required 
            value="<?php echo esc($_POST['name'] ?? ''); ?>"
          >
        </div>

        <div class="field">
          <label>Total beds</label>
          <input 
            type="number" 
            name="total" 
            min="1" 
            max="500" 
            required 
            value="<?php echo esc($_POST['total'] ?? ''); ?>"
          >
        </div>

        <button class="btn btn-primary" type="submit">Add category</button>
        <a class="btn btn-ghost" href="<?php echo BASE_URL; ?>/admin/manage-beds.php">Cancel</a>
      </form>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>

