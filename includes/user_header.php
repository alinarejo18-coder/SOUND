<?php
if (!isset($current_page)) {
    $current_page = '';
}

$user_name = htmlspecialchars($_SESSION['name'] ?? 'User');
$user_initial = strtoupper(substr($user_name, 0, 1));
$user_id = $_SESSION['user_id'] ?? 0;
$user_img = "";
if (isset($conn) && $user_id) {
    $uq = mysqli_query($conn, "SELECT profile_image FROM users WHERE id = '$user_id'");
    if ($uq && mysqli_num_rows($uq) > 0) {
        $ur = mysqli_fetch_assoc($uq);
        $user_img = $ur['profile_image'];
    }
}
?>

<!-- Sidebar -->
<aside class="sidebar" id="sidebar">

    <div class="sidebar-header">
        <a href="<?php echo $user_base ?? ''; ?>../index.php" class="logo"><img src="<?php echo $user_base ?? ''; ?>../assets/images/sound-logo-white.svg" alt="SOUND Logo"></a>
        <div class="admin-label">Hi, <?php echo $user_name; ?>!</div>
    </div>

    <nav class="sidebar-nav">

        <div class="nav-section">
            <div class="nav-section-title">Main</div>

            <a href="<?php echo $user_base ?? ''; ?>dashboard.php"
               class="<?php echo $current_page === 'dashboard' ? 'active' : ''; ?>">
                <span class="nav-icon"><i data-lucide="layout-dashboard"></i></span> Dashboard
            </a>

            <a href="<?php echo $user_base ?? ''; ?>../index.php">
                <span class="nav-icon"><i data-lucide="house"></i></span> Home Page
            </a>
        </div>

        <div class="nav-section">
            <div class="nav-section-title">Explore</div>

            <a href="<?php echo $user_base ?? ''; ?>../music.php">
                <span class="nav-icon"><i data-lucide="music"></i></span> Music
            </a>

            <a href="<?php echo $user_base ?? ''; ?>../videos.php">
                <span class="nav-icon"><i data-lucide="video"></i></span> Videos
            </a>
        </div>

    </nav>

    <div class="sidebar-footer">
        <div class="admin-user">
            <?php if (!empty($user_img)): ?>
                <img src="<?php echo $user_base ?? ''; ?>../uploads/users/<?php echo htmlspecialchars($user_img); ?>" alt="Profile" style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover; border: 2px solid var(--accent-cyan);">
            <?php else: ?>
                <div class="admin-avatar"><?php echo $user_initial; ?></div>
            <?php endif; ?>
            <div>
                <div class="admin-name"><?php echo $user_name; ?></div>
                <div class="admin-role">Member</div>
            </div>
        </div>

        <a href="<?php echo $user_base ?? ''; ?>../logout.php" class="logout-btn">
            <span class="nav-icon"><i data-lucide="log-out"></i></span> Logout
        </a>
    </div>

</aside>

<!-- Mobile sidebar toggle -->
<button class="sidebar-toggle" id="sidebarToggle" aria-label="Toggle menu"><i data-lucide="menu"></i></button>

<script src="https://unpkg.com/lucide@latest"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        if (typeof lucide !== 'undefined') lucide.createIcons();
    });
</script>
