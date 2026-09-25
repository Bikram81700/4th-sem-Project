<?php
/**
 * BedTrack - Admin: Edit & Manage Bed Category
 * Allows hospital administrators to rename a bed category, add new beds,
 * remove individual available beds, or delete the category entirely.
 */

require_once __DIR__ . '/../config/functions.php';

// Ensure user has hospital admin permissions
require_role('hospital');

$currentUser = current_user();
$hospitalId  = (int) $currentUser['hospital_id'];

$categoryId = (int) ($_GET['id'] ?? $_POST['category_id'] ?? 0);
$category   = category_by_id($categoryId);

// Verify category exists and belongs to the logged-in hospital
if (!$category || (int) $category['hospital_id'] !== $hospitalId) {
    flash('error', 'Bed category not found.');
    redirect('/admin/manage-beds.php');
}

$errors = [];

// Handle action submissions for category management
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'update_details') {
        $categoryName = trim($_POST['name'] ?? '');

        if ($categoryName === '') {
            $errors[] = 'Category name is required.';
        } elseif (!preg_match('/^[a-zA-Z\s\-]+$/', $categoryName)) {
            $errors[] = 'Category name must contain only letters and spaces (no numbers).';
        } elseif (strlen($categoryName) < 2 || strlen($categoryName) > 60) {
            $errors[] = 'Category name must be between 2 and 60 characters.';
        } else {
            $stmt = $pdo->prepare("UPDATE bed_categories SET name = ? WHERE id = ?");
            $stmt->execute([$categoryName, $categoryId]);

            flash('success', 'Category updated.');
            redirect('/admin/edit-bed.php?id=' . $categoryId);
        }

    } elseif ($action === 'add_beds') {
        $bedsToAdd = max(1, min(200, (int) ($_POST['count'] ?? 0)));

        $pdo->beginTransaction();

        $insertStmt = $pdo->prepare("INSERT INTO beds (category_id, hospital_id, status) VALUES (?, ?, 'available')");
        for ($i = 0; $i < $bedsToAdd; $i++) {
            $insertStmt->execute([$categoryId, $hospitalId]);
        }

        $pdo->prepare("UPDATE bed_categories SET total = total + ? WHERE id = ?")
            ->execute([$bedsToAdd, $categoryId]);

        $pdo->commit();

        flash('success', "{$bedsToAdd} bed(s) added.");
        redirect('/admin/edit-bed.php?id=' . $categoryId);

    } elseif ($action === 'remove_bed') {
        $bedId = (int) ($_POST['bed_id'] ?? 0);

        // Ensure bed is available before allowing removal
        $stmt = $pdo->prepare("SELECT * FROM beds WHERE id = ? AND category_id = ? AND status = 'available'");
        $stmt->execute([$bedId, $categoryId]);

        if ($stmt->fetch()) {
            $pdo->beginTransaction();

            $pdo->prepare("DELETE FROM beds WHERE id = ?")
                ->execute([$bedId]);

            $pdo->prepare("UPDATE bed_categories SET total = total - 1 WHERE id = ?")
                ->execute([$categoryId]);

            $pdo->commit();

            flash('success', 'Bed removed.');
        } else {
            flash('error', 'Only available (empty) beds can be removed.');
        }

        redirect('/admin/edit-bed.php?id=' . $categoryId);

    } elseif ($action === 'delete_category') {
        $result = delete_bed_category($categoryId, $hospitalId);

        if ($result['success']) {
            flash('success', $result['message']);
            redirect('/admin/manage-beds.php');
        } else {
            flash('error', $result['message']);
            $errors[] = $result['message'];
        }
    }
}

// Refresh category data after potential updates
$category = category_by_id($categoryId);

// Fetch all individual beds in this category
$stmt = $pdo->prepare("SELECT * FROM beds WHERE category_id = ? ORDER BY id");
$stmt->execute([$categoryId]);
$bedsList = $stmt->fetchAll();

// Page layout configuration
$active    = 'beds';
$role      = 'hospital';
$pageTitle = 'Manage ' . $category['name'];

include __DIR__ . '/../includes/header.php';
?>

<div class="app-shell">
  <?php include __DIR__ . '/../includes/sidebar.php'; ?>

  <div class="main">
    <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 12px; flex-wrap: wrap;">
      <div>
        <h1><?php echo esc($category['name']); ?></h1>
        <p class="lede" id="bed-count-lede">
          <span class="total-beds-count"><?php echo (int) $category['total']; ?></span> total beds · 
          <span class="avail-beds-count"><?php echo available_count($categoryId); ?></span> available
        </p>
      </div>

      <form 
        method="post" 
        action="<?php echo BASE_URL; ?>/admin/edit-bed.php?id=<?php echo $categoryId; ?>" 
        onsubmit="return confirm('Are you sure you want to permanently delete category \'<?php echo esc(addslashes($category['name'])); ?>\' and all its beds?');" 
        style="display: inline;"
      >
        <?php echo csrf_field(); ?>
        <input type="hidden" name="action" value="delete_category">
        <input type="hidden" name="category_id" value="<?php echo $categoryId; ?>">
        
        <button class="btn btn-sm btn-danger" type="submit">
          <i class="fas fa-trash-alt" style="margin-right: 4px;"></i>Delete Category
        </button>
      </form>
    </div>

    <?php if (!empty($errors)): ?>
      <div class="field error" style="margin-bottom: 14px;">
        <?php foreach ($errors as $error): ?>
          <?php echo esc($error); ?><br>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <!-- Management Controls: Rename Category & Add Beds -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 16px; margin-bottom: 20px;">
      <div class="card" style="margin-bottom: 0;">
        <h3>Rename category</h3>
        
        <form method="post">
          <?php echo csrf_field(); ?>
          <input type="hidden" name="action" value="update_details">
          <input type="hidden" name="category_id" value="<?php echo $categoryId; ?>">
          
          <div class="field">
            <input 
              type="text" 
              name="name" 
              pattern="[A-Za-z\s\-]+" 
              title="Category name must contain only letters and spaces." 
              value="<?php echo esc($category['name']); ?>" 
              required
            >
          </div>
          
          <button class="btn btn-primary btn-sm" type="submit">Save name</button>
        </form>
      </div>

      <div class="card" style="margin-bottom: 0;">
        <h3>Add beds</h3>
        
        <form method="post" style="display: flex; gap: 8px; align-items: flex-end;">
          <?php echo csrf_field(); ?>
          <input type="hidden" name="action" value="add_beds">
          <input type="hidden" name="category_id" value="<?php echo $categoryId; ?>">
          
          <div class="field" style="margin-bottom: 0; flex: 1;">
            <label>How many?</label>
            <input type="number" name="count" min="1" max="200" value="1" required>
          </div>
          
          <button class="btn btn-primary btn-sm" type="submit">Add</button>
        </form>
      </div>
    </div>

    <!-- Individual Bed Inventory Table -->
    <div class="card">
      <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px;">
        <h3 style="margin-bottom: 0;">Individual beds</h3>
        <span style="font-size: 13px; color: var(--ink-soft);"><?php echo count($bedsList); ?> bed(s) listed</span>
      </div>

      <?php if (empty($bedsList)): ?>
        <div style="text-align: center; padding: 32px 16px; color: var(--ink-soft); font-size: 14px;">
          <i class="fas fa-bed" style="font-size: 28px; opacity: 0.4; display: block; margin-bottom: 8px;"></i>
          No beds in this category yet. Use "Add beds" above to add beds.
        </div>
      <?php else: ?>
        <table>
          <thead>
            <tr>
              <th>Bed #</th>
              <th>System ID</th>
              <th>Status</th>
              <th style="text-align: right;">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php $bedIndex = 1; foreach ($bedsList as $bed): ?>
              <tr data-bed-id="<?php echo $bed['id']; ?>">
                <td><strong>Bed <?php echo $bedIndex++; ?></strong></td>
                <td style="font-family: var(--font-mono); font-size: 12px; color: var(--ink-soft);">
                  #<?php echo $bed['id']; ?>
                </td>
                <td><?php echo badge($bed['status']); ?></td>
                <td class="table-actions" style="justify-content: flex-end;">
                  <?php if ($bed['status'] === 'available'): ?>
                    <form 
                      method="post" 
                      action="<?php echo BASE_URL; ?>/admin/edit-bed.php?id=<?php echo $categoryId; ?>" 
                      onsubmit="return confirm('Remove this empty bed from the category?');" 
                      style="display: inline;"
                    >
                      <?php echo csrf_field(); ?>
                      <input type="hidden" name="action" value="remove_bed">
                      <input type="hidden" name="category_id" value="<?php echo $categoryId; ?>">
                      <input type="hidden" name="bed_id" value="<?php echo $bed['id']; ?>">
                      <button class="btn btn-sm btn-danger" type="submit">Remove</button>
                    </form>
                  <?php else: ?>
                    <span style="font-size: 12px; color: var(--ink-soft); font-weight: 500;">Occupied</span>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </div>

    <a class="btn btn-ghost" href="<?php echo BASE_URL; ?>/admin/manage-beds.php">&larr; Back to bed categories</a>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>

