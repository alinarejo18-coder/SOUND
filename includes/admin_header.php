<?php
/**
 * Admin Sidebar Navigation
 * Include in all admin pages:
 *   $current_page = 'dashboard'; // active page key
 *   include "../includes/admin_header.php";
 */

if (!isset($current_page)) {
    $current_page = '';
}

$admin_name = htmlspecialchars($_SESSION['name'] ?? 'Administrator');
$admin_initial = strtoupper(substr($admin_name, 0, 1));
$admin_user_id = $_SESSION['user_id'] ?? null;
$profile_image = null;

if ($admin_user_id && isset($conn)) {
    $q_img = mysqli_query($conn, "SELECT profile_image FROM admins WHERE id = '$admin_user_id'");
    if ($q_img && mysqli_num_rows($q_img) > 0) {
        $row_img = mysqli_fetch_assoc($q_img);
        $profile_image = $row_img['profile_image'];
    }
}
?>

<!-- Sidebar -->
<aside class="sidebar" id="sidebar">

    <div class="sidebar-header" style="position: relative;">
        <a href="<?php echo $admin_base ?? ''; ?>dashboard.php" class="logo" style="text-decoration: none;"><img src="<?php echo $admin_base ?? ''; ?>../assets/images/sound-logo-white.svg" alt="SOUND Logo"></a>

    </div>

    <nav class="sidebar-nav">

        <div class="nav-section">
            <div class="nav-section-title">Main</div>

            <a href="<?php echo $admin_base ?? ''; ?>dashboard.php"
               class="<?php echo $current_page === 'dashboard' ? 'active' : ''; ?>">
                <span class="nav-icon"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z" /></svg></span> Dashboard
            </a>
        </div>

        <div class="nav-section">
            <div class="nav-section-title">Content</div>

            <a href="<?php echo $admin_base ?? ''; ?>music/index.php"
               class="<?php echo $current_page === 'music' ? 'active' : ''; ?>">
                <span class="nav-icon"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19.5V15m0 0v-4.5m0 4.5h.008v.008H9v-.008zM12 19.5v-7.5m0 7.5h.008v.008H12v-.008zM15 19.5v-10.5m0 10.5h.008v.008H15v-.008zM21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg></span> Music
            </a>

            <a href="<?php echo $admin_base ?? ''; ?>videos/index.php"
               class="<?php echo $current_page === 'videos' ? 'active' : ''; ?>">
                <span class="nav-icon"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" d="M15.75 10.5l4.72-4.72a.75.75 0 011.28.53v11.38a.75.75 0 01-1.28.53l-4.72-4.72M4.5 18.75h9a2.25 2.25 0 002.25-2.25v-9a2.25 2.25 0 00-2.25-2.25h-9A2.25 2.25 0 002.25 7.5v9a2.25 2.25 0 002.25 2.25z" /></svg></span> Videos
            </a>

            <a href="<?php echo $admin_base ?? ''; ?>ratings/index.php"
               class="<?php echo $current_page === 'ratings' ? 'active' : ''; ?>">
                <span class="nav-icon"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 00-1.28.347l1.32 5.378c.11.46-.38.823-.77.587l-4.71-2.825a.563.563 0 00-.586 0L5.98 20.273c-.39.236-.88-.127-.77-.587l1.32-5.378a.563.563 0 00-1.28-.347L1.046 10.387c-.38-.325-.178-.948.321-.988l5.518-.442a.563.563 0 00.475-.345L9.48 3.5z" /></svg></span> Ratings
            </a>

            <a href="<?php echo $admin_base ?? ''; ?>reviews/index.php"
               class="<?php echo $current_page === 'reviews' ? 'active' : ''; ?>">
                <span class="nav-icon"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M7.5 8.25h9m-9 3H12m-9.75 1.51c0 1.6 1.123 2.994 2.707 3.227 1.129.166 2.27.293 3.423.379.35.026.67.21.865.501L12 21l2.755-4.133a1.14 1.14 0 01.865-.501 48.172 48.172 0 003.423-.379c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0012 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018z" /></svg></span> Reviews
            </a>
        </div>

        <div class="nav-section">
            <div class="nav-section-title">Catalog</div>

            <a href="<?php echo $admin_base ?? ''; ?>artists/index.php"
               class="<?php echo $current_page === 'artists' ? 'active' : ''; ?>">
                <span class="nav-icon"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" /></svg></span> Artists
            </a>

            <a href="<?php echo $admin_base ?? ''; ?>albums/index.php"
               class="<?php echo $current_page === 'albums' ? 'active' : ''; ?>">
                <span class="nav-icon"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M11.99 21.04a9 9 0 100-18.08 9 9 0 000 18.08zM11.99 15.02a3 3 0 100-6.04 3 3 0 000 6.04z" /></svg></span> Albums
            </a>

            <a href="<?php echo $admin_base ?? ''; ?>genres/index.php"
               class="<?php echo $current_page === 'genres' ? 'active' : ''; ?>">
                <span class="nav-icon"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6h9.75M10.5 6a1.5 1.5 0 11-3 0m3 0a1.5 1.5 0 10-3 0M3.75 6H7.5m3 12h9.75m-9.75 0a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m-3.75 0H7.5m9-6h3.75m-3.75 0a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m-9.75 0h9.75" /></svg></span> Genres
            </a>

            <a href="<?php echo $admin_base ?? ''; ?>languages/index.php"
               class="<?php echo $current_page === 'languages' ? 'active' : ''; ?>">
                <span class="nav-icon"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9.004 9.004 0 008.716-6.747M12 21a9.004 9.004 0 01-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 017.843 4.582M12 3a8.997 8.997 0 00-7.843 4.582m15.686 0A11.953 11.953 0 0112 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0121 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0112 16.5c-3.162 0-6.133-.815-8.716-2.247m0 0A9.015 9.015 0 013 12c0-1.605.42-3.113 1.157-4.418" /></svg></span> Languages
            </a>

            <a href="<?php echo $admin_base ?? ''; ?>years/index.php"
               class="<?php echo $current_page === 'years' ? 'active' : ''; ?>">
                <span class="nav-icon"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5m-9-6h.008v.008H12v-.008zM12 15h.008v.008H12V15zM9.75 15h.008v.008H9.75V15zM7.5 15h.008v.008H7.5V15zm6.75-4.5h.008v.008h-.008v-.008zm0 2.25h.008v.008h-.008V15zm2.25-4.5h.008v.008H16.5v-.008zm0 2.25h.008v.008H16.5V15z" /></svg></span> Years
            </a>
        </div>

        <div class="nav-section">
            <div class="nav-section-title">System</div>

            <a href="<?php echo $admin_base ?? ''; ?>users/index.php"
               class="<?php echo $current_page === 'users' ? 'active' : ''; ?>">
                <span class="nav-icon"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" /></svg></span> Users
            </a>

            <a href="<?php echo $admin_base ?? ''; ?>settings/index.php"
               class="<?php echo $current_page === 'settings' ? 'active' : ''; ?>">
                <span class="nav-icon"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10.343 3.94c.09-.542.56-.94 1.11-.94h1.093c.55 0 1.02.398 1.11.94l.149.894c.07.424.384.764.78.93.398.164.855.142 1.205-.108l.737-.527a1.125 1.125 0 011.45.12l.773.774c.39.389.44 1.002.12 1.45l-.527.737c-.25.35-.272.806-.107 1.204.165.397.505.71.93.78l.893.15c.543.09.94.56.94 1.109v1.094c0 .55-.397 1.02-.94 1.11l-.893.149c-.425.07-.765.383-.93.78-.165.398-.143.854.107 1.204l.527.738c.32.447.269 1.06-.12 1.45l-.774.773a1.125 1.125 0 01-1.449.12l-.738-.527c-.35-.25-.806-.272-1.203-.107-.397.165-.71.505-.781.929l-.149.894c-.09.542-.56.94-1.11.94h-1.094c-.55 0-1.019-.398-1.11-.94l-.148-.894c-.071-.424-.384-.764-.781-.93-.398-.164-.854-.142-1.204.108l-.738.527c-.447.32-1.06.269-1.45-.12l-.773-.774a1.125 1.125 0 01-.12-1.45l.527-.737c.25-.35.273-.806.108-1.204-.165-.397-.505-.71-.93-.78l-.894-.15c-.542-.09-.94-.56-.94-1.109v-1.094c0-.55.398-1.02.94-1.11l.894-.149c.424-.07.765-.383.93-.78.165-.398.143-.854-.107-1.204l-.527-.738a1.125 1.125 0 01.12-1.45l.773-.773a1.125 1.125 0 011.45-.12l.737.527c.35.25.807.272 1.204.107.397-.165.71-.505.78-.929l.15-.894zM15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg></span> Settings
            </a>

            <a href="<?php echo $admin_base ?? ''; ?>../logout.php">
                <span class="nav-icon"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6A2.25 2.25 0 005.25 5.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3.75-6l2.25 2.25-2.25 2.25m-6.75-2.25h9" /></svg></span> Logout
            </a>
        </div>

    </nav>

</aside>

<!-- Mobile sidebar toggle -->
<button class="sidebar-toggle" id="sidebarToggle">
    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" /></svg>
</button>

<script src="<?php echo $admin_base ?? ''; ?>../assets/js/theme.js?v=<?php echo time(); ?>"></script>
<script src="https://unpkg.com/lucide@latest"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        if (typeof lucide !== 'undefined') lucide.createIcons();
    });
</script>
