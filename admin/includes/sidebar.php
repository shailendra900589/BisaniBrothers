<aside class="w-72 bg-[#173978] text-white flex flex-col shadow-2xl z-20 hidden md:flex h-screen sticky top-0">
    <div class="h-24 flex items-center px-8 border-b border-white/10">
        <h1 class="text-2xl font-bold tracking-tight">Bisani<span class="text-[#2fcaf0]">Portal</span>.</h1>
    </div>
    
    <div class="px-8 py-6 border-b border-white/10 bg-[#122c5e]">
        <p class="text-xs font-bold text-blue-300 uppercase tracking-widest mb-1">Logged in as</p>
        <p class="text-lg font-bold text-white capitalize">
            <?php echo isset($_SESSION['username']) ? $_SESSION['username'] : 'User'; ?>
        </p>
        <span class="inline-block mt-2 text-xs bg-[#2fcaf0] text-[#173978] px-2 py-0.5 rounded font-bold uppercase border border-[#2fcaf0] shadow-sm">
            <?php echo isset($_SESSION['role']) ? $_SESSION['role'] : 'Staff'; ?>
        </span>
    </div>

    <nav class="flex-1 py-6 px-4 space-y-2 overflow-y-auto">
        <?php
        $currentPage = basename($_SERVER['PHP_SELF'] ?? '');
        $navClass = function (string $page) use ($currentPage): string {
            $base = 'flex items-center px-4 py-3 rounded-xl font-medium transition-all group';
            if ($currentPage === $page) {
                return $base . ' nav-active shadow-lg shadow-[#2fcaf0]/20';
            }
            return $base . ' text-blue-100 hover:bg-white/10 hover:text-white';
        };
        $iconClass = function (string $page) use ($currentPage): string {
            return 'w-8 ' . ($currentPage === $page ? 'text-[#173978]' : 'group-hover:text-[#2fcaf0]');
        };
        ?>

        <a href="dashboard.php" class="<?php echo $navClass('dashboard.php'); ?>">
            <div class="<?php echo $iconClass('dashboard.php'); ?>"><i class="fa-solid fa-gauge-high"></i></div>
            <span>Dashboard</span>
        </a>

        <?php $role = isset($_SESSION['role']) ? strtolower($_SESSION['role']) : ''; ?>

        <?php if($role == 'admin' || $role == 'hr'): ?>
            <?php if($role == 'admin') echo '<div class="mt-4 mb-2 px-4 text-xs font-bold text-blue-400 uppercase tracking-widest">Recruitment</div>'; ?>
            
            <a href="jobs.php" class="flex items-center px-4 py-3 text-blue-100 hover:bg-white/10 hover:text-white rounded-xl font-medium transition-all group">
                <div class="w-8 group-hover:text-[#2fcaf0]"><i class="fa-solid fa-briefcase"></i></div>
                <span>Manage Jobs</span>
            </a>
            <a href="applications.php" class="flex items-center px-4 py-3 text-blue-100 hover:bg-white/10 hover:text-white rounded-xl font-medium transition-all group">
                <div class="w-8 group-hover:text-[#2fcaf0]"><i class="fa-solid fa-users-viewfinder"></i></div>
                <span>Applications</span>
            </a>
        <?php endif; ?>

        <?php if($role == 'admin' || $role == 'writer'): ?>
            <?php if($role == 'admin') echo '<div class="mt-4 mb-2 px-4 text-xs font-bold text-blue-400 uppercase tracking-widest">Content</div>'; ?>

            <a href="blogs.php" class="flex items-center px-4 py-3 text-blue-100 hover:bg-white/10 hover:text-white rounded-xl font-medium transition-all group">
                <div class="w-8 group-hover:text-[#2fcaf0]"><i class="fa-solid fa-newspaper"></i></div>
                <span>My Blogs</span>
            </a>
            <a href="case-studies.php" class="flex items-center px-4 py-3 text-blue-100 hover:bg-white/10 hover:text-white rounded-xl font-medium transition-all group">
                <div class="w-8 group-hover:text-[#2fcaf0]"><i class="fa-solid fa-trophy"></i></div>
                <span>Case Studies</span>
            </a>
        <?php endif; ?>

        <?php if($role == 'admin' || $role == 'marketer'): ?>
            <?php if($role == 'admin') echo '<div class="mt-4 mb-2 px-4 text-xs font-bold text-blue-400 uppercase tracking-widest">Marketing</div>'; ?>

            <a href="popups.php" class="flex items-center px-4 py-3 text-blue-100 hover:bg-white/10 hover:text-white rounded-xl font-medium transition-all group">
                <div class="w-8 group-hover:text-[#2fcaf0]"><i class="fa-solid fa-bullhorn"></i></div>
                <span>Popups</span>
            </a>
            <a href="enquiries.php" class="flex items-center px-4 py-3 text-blue-100 hover:bg-white/10 hover:text-white rounded-xl font-medium transition-all group">
                <div class="w-8 group-hover:text-[#2fcaf0]"><i class="fa-solid fa-inbox"></i></div>
                <span>Enquiries</span>
            </a>
            <a href="growth_partners.php" class="flex items-center px-4 py-3 text-blue-100 hover:bg-white/10 hover:text-white rounded-xl font-medium transition-all group">
                <div class="w-8 group-hover:text-[#2fcaf0]"><i class="fa-solid fa-handshake"></i></div>
                <span>Growth Partners</span>
            </a>
            <a href="testimonials.php" class="flex items-center px-4 py-3 text-blue-100 hover:bg-white/10 hover:text-white rounded-xl font-medium transition-all group">
                <div class="w-8 group-hover:text-[#2fcaf0]"><i class="fa-solid fa-quote-left"></i></div>
                <span>Testimonials</span>
            </a>
            <a href="marketing-campaigns.php" class="flex items-center px-4 py-3 text-blue-100 hover:bg-white/10 hover:text-white rounded-xl font-medium transition-all group">
                <div class="w-8 group-hover:text-[#2fcaf0]"><i class="fa-solid fa-envelope-circle-check"></i></div>
                <span>Email Campaigns</span>
            </a>
            <a href="mail-outbox.php" class="flex items-center px-4 py-3 text-blue-100 hover:bg-white/10 hover:text-white rounded-xl font-medium transition-all group">
                <div class="w-8 group-hover:text-[#2fcaf0]"><i class="fa-solid fa-inbox"></i></div>
                <span>Mail Outbox <span class="text-[10px] opacity-70">(local)</span></span>
            </a>
            <a href="newsletter.php" class="flex items-center px-4 py-3 text-blue-100 hover:bg-white/10 hover:text-white rounded-xl font-medium transition-all group">
                <div class="w-8 group-hover:text-[#2fcaf0]"><i class="fa-solid fa-envelope-open-text"></i></div>
                <span>Newsletter</span>
            </a>
        <?php endif; ?>

        <?php if($role == 'admin' || $role == 'writer'): ?>
            <a href="faqs-admin.php" class="flex items-center px-4 py-3 text-blue-100 hover:bg-white/10 hover:text-white rounded-xl font-medium transition-all group">
                <div class="w-8 group-hover:text-[#2fcaf0]"><i class="fa-solid fa-circle-question"></i></div>
                <span>Site FAQs</span>
            </a>
        <?php endif; ?>

        <?php if($role == 'admin'): ?>
            <div class="mt-6 mb-2 px-4 text-xs font-bold text-blue-400 uppercase tracking-widest">System</div>
            <a href="users.php" class="flex items-center px-4 py-3 text-blue-100 hover:bg-white/10 hover:text-white rounded-xl font-medium transition-all group">
                <div class="w-8 group-hover:text-[#2fcaf0]"><i class="fa-solid fa-user-shield"></i></div>
                <span>Users</span>
            </a>
            <a href="categories.php" class="flex items-center px-4 py-3 text-blue-100 hover:bg-white/10 hover:text-white rounded-xl font-medium transition-all group">
                <div class="w-8 group-hover:text-[#2fcaf0]"><i class="fa-solid fa-tags"></i></div>
                <span>Categories</span>
            </a>
            <a href="seo-reindex.php" class="flex items-center px-4 py-3 text-blue-100 hover:bg-white/10 hover:text-white rounded-xl font-medium transition-all group">
                <div class="w-8 group-hover:text-[#2fcaf0]"><i class="fa-solid fa-satellite-dish"></i></div>
                <span>SEO Reindex</span>
            </a>
        <?php endif; ?>
    </nav>

    <div class="p-6 border-t border-white/10">
        <a href="logout.php" class="flex items-center justify-center w-full py-3 border border-red-400/30 bg-red-500/10 text-red-200 rounded-xl hover:bg-red-500 hover:text-white transition-all font-semibold shadow-inner">
            <i class="fa-solid fa-power-off mr-2"></i> Logout
        </a>
    </div>
</aside>