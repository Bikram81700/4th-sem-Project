<main class="pt-20 pb-20 md:pb-10 md:pt-20 md:ml-64 px-4 sm:px-6 md:px-8 lg:px-10 min-h-screen transition-all">
  <div class="max-w-7xl mx-auto w-full">
    <?php if ($msg = flash('success')): ?>
      <div class="mb-6 rounded-xl border border-cyan-200 bg-cyan-50 text-[#06b6d4] font-medium px-4 py-3 text-[14px] flex items-center gap-2 shadow-sm">
        <span class="material-symbols-outlined text-[20px] text-[#06b6d4]">check_circle</span>
        <span><?php echo esc($msg); ?></span>
      </div>
    <?php endif; ?>
    <?php if ($msg = flash('error')): ?>
      <div class="mb-6 rounded-xl border border-red-200 bg-red-50 text-[#ba1a1a] font-medium px-4 py-3 text-[14px] flex items-center gap-2 shadow-sm">
        <span class="material-symbols-outlined text-[20px] text-[#ba1a1a]">error</span>
        <span><?php echo esc($msg); ?></span>
      </div>
    <?php endif; ?>