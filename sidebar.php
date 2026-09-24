<?php
/**
 * Expects $active (string key) and $role ('user' | 'hospital' | 'super') to be set
 * before including this file.
 */
$menus = [
    'hospital' => [
        'label' => 'Hospital admin',
        'links' => [
            'dashboard'     => ['Dashboard', '/admin/dashboard.php'],
            'beds'          => ['Manage beds', '/admin/manage-beds.php'],
            'bookings'      => ['Bookings', '/admin/bookings.php'],
        ],
    ],
    'super' => [
        'label' => 'Super admin',
        'links' => [
            'dashboard'        => ['Dashboard', '/super-admin/dashboard.php'],
            'approvals'        => ['Hospital approvals', '/super-admin/pending-hospitals.php'],
            'hospitals'        => ['Manage hospitals', '/super-admin/manage-hospitals.php'],
            'patient-history'  => ['Patient history', '/super-admin/patient-history.php'],
            'users'            => ['Manage users', '/super-admin/manage-users.php'],
            'analytics'        => ['Analytics', '/super-admin/analytics.php'],
        ],
    ],
];
$menu = $menus[$role];
?>
<aside class="sidebar">
  <h4><?php echo esc($menu['label']); ?></h4>
  <?php foreach ($menu['links'] as $key => $link): ?>
    <a class="side-link <?php echo $active === $key ? 'active' : ''; ?>" href="<?php echo BASE_URL . $link[1]; ?>">
      <span class="dot"></span><?php echo esc($link[0]); ?>
    </a>
  <?php endforeach; ?>
</aside>
