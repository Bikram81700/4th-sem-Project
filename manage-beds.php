<?php
/**
 * BedTrack - Admin: Manage Beds & Categories Overview
 * Displays an overview table of all bed categories for the hospital,
 * including total capacity, available beds, and occupied count.
 */

require_once __DIR__ . '/../config/functions.php';

// Ensure user has hospital admin permissions
require_role('hospital');

$currentUser = current_user();
$hospitalId  = (int) $currentUser['hospital_id'];

// Fetch all bed categories created by this hospital
$categories = categories_of_hospital($hospitalId);

// Page layout configuration
$active    = 'beds';
$role      = 'hospital';
$pageTitle = 'Manage beds';

include __DIR__ . '/../includes/header.php';
?>

<div class="app-shell">
  <?php include __DIR__ . '/../includes/sidebar.php'; ?>

  <div class="main">
    <div style="display: flex; justify-content: space-between; align-items: flex-start;">
      <div>
        <h1>Manage beds</h1>
        <p class="lede">Bed categories and live availability for your hospital.</p>
      </div>

      <a class="btn btn-primary btn-sm" href="<?php echo BASE_URL; ?>/admin/add-bed.php">
        Add category
      </a>
    </div>

    <?php if (empty($categories)): ?>
      <div class="empty">
        <h4>No bed categories yet</h4>
        <p>Add your first category (e.g. ICU, General Ward) to start accepting bookings.</p>
        
        <div style="margin-top: 14px;">
          <a class="btn btn-primary btn-sm" href="<?php echo BASE_URL; ?>/admin/add-bed.php">
            Add category
          </a>
        </div>
      </div>
    <?php else: ?>
      <div class="card">
        <table>
          <thead>
            <tr>
              <th>Category</th>
              <th>Total</th>
              <th>Available</th>
              <th>Occupied</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($categories as $cat): ?>
              <?php 
                $availableCount = available_count($cat['id']);
                $occupiedCount  = occupied_count($cat['id']);
              ?>
              <tr data-category-id="<?php echo $cat['id']; ?>">
                <td><strong><?php echo esc($cat['name']); ?></strong></td>
                <td><?php echo (int) $cat['total']; ?></td>
                <td style="color: var(--teal); font-weight: 600;">
                  <?php echo $availableCount; ?>
                </td>
                <td style="color: var(--rust);">
                  <?php echo $occupiedCount; ?>
                </td>
                <td class="table-actions">
                  <a class="btn btn-sm" href="<?php echo BASE_URL; ?>/admin/edit-bed.php?id=<?php echo $cat['id']; ?>">
                    Manage
                  </a>
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

