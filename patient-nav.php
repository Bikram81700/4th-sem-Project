<?php
$__links = [
    'dashboard' => ['Dashboard', '/user/dashboard.php', 'dashboard'],
    'hospitals' => ['Browse hospitals', '/user/hospitals.php', 'search'],
    'bookings'  => ['My bookings', '/user/my-bookings.php', 'book_online'],
    'profile'   => ['Profile', '/user/profile.php', 'person'],
];
$__firstName = $u ? explode(' ', $u['name'])[0] : '';
?>
<!-- TopNav (Shared for mobile & desktop) -->
<header class="bg-white border-b border-[#bbcabf] fixed top-0 left-0 right-0 h-16 z-50 flex justify-between items-center px-4 md:px-6 shadow-sm">
  <div class="flex items-center gap-2">
    <a href="<?php echo BASE_URL; ?>/user/dashboard.php" class="flex items-center gap-2">
      <span class="material-symbols-outlined text-[#06b6d4] filled" style="font-size:28px;">bed</span>
      <span class="text-[18px] md:text-[20px] font-bold text-[#161d19] font-['Plus_Jakarta_Sans']">Bed<span class="text-[#06b6d4]">Track</span></span>
    </a>
  </div>
  
  <div class="flex items-center gap-2 md:gap-3">
    <?php if (!empty($__firstName)): ?>
      <span class="text-[13px] text-[#3c4a42] font-medium hidden sm:inline-block">Hi, <?php echo esc($__firstName); ?></span>
    <?php endif; ?>
    <a class="bg-[#ba1a1a] text-white text-[12px] font-semibold rounded-lg px-3.5 py-1.5 md:px-4 md:py-2 hover:bg-[#93000a] transition-colors shadow-sm" href="<?php echo BASE_URL; ?>/auth/logout.php">Log out</a>
  </div>
</header>

<!-- Desktop SideNav (Positioned right below 64px header) -->
<nav class="hidden md:flex flex-col fixed top-16 left-0 bottom-0 w-64 bg-white border-r border-[#bbcabf] z-40 shadow-sm overflow-y-auto">
  <div class="px-6 py-5 border-b border-[#bbcabf]">
    <h2 class="text-[11px] font-semibold text-[#3c4a42] uppercase tracking-wider mb-0.5">PATIENT</h2>
    <p class="text-[14px] text-[#161d19] font-semibold">Manage your care</p>
  </div>
  <ul class="flex flex-col gap-1 w-full px-3 pt-4">
    <?php foreach ($__links as $key => $link): $isActive = ($active ?? '') === $key; ?>
    <li>
      <a class="flex items-center gap-3 px-4 py-3 transition-all duration-150 rounded-xl <?php echo $isActive
          ? 'bg-[#ecfeff] text-[#06b6d4] font-semibold shadow-sm'
          : 'text-[#3c4a42] hover:bg-[#ecfeff] hover:text-[#161d19] font-medium'; ?>"
         href="<?php echo BASE_URL . $link[1]; ?>">
        <span class="material-symbols-outlined <?php echo $isActive ? 'filled text-[#06b6d4]' : 'text-[#3c4a42]'; ?>" style="font-size:22px;"><?php echo $link[2]; ?></span>
        <span class="text-[14px]"><?php echo esc($link[0]); ?></span>
      </a>
    </li>
    <?php endforeach; ?>
  </ul>
</nav>

<!-- Mobile Bottom Navigation -->
<nav class="md:hidden fixed bottom-0 left-0 right-0 h-16 bg-white border-t border-[#bbcabf] z-50 flex items-center justify-around px-2 shadow-lg">
  <?php foreach ($__links as $key => $link): $isActive = ($active ?? '') === $key; ?>
  <a class="flex flex-col items-center justify-center flex-1 py-1 text-center transition-colors <?php echo $isActive ? 'text-[#06b6d4] font-bold' : 'text-[#3c4a42] hover:text-[#161d19]'; ?>"
     href="<?php echo BASE_URL . $link[1]; ?>">
    <span class="material-symbols-outlined <?php echo $isActive ? 'filled' : ''; ?>" style="font-size:22px;"><?php echo $link[2]; ?></span>
    <span class="text-[10px] mt-0.5"><?php echo esc($link[0]); ?></span>
  </a>
  <?php endforeach; ?>
</nav>