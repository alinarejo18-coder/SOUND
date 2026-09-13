<?php
require_once "../includes/auth.php";
require_once "../config/db.php";

requireAdmin();

// Count total users
$user_query = mysqli_query($conn, "SELECT COUNT(*) AS total FROM users WHERE role = 'user'");
$user_count = mysqli_fetch_assoc($user_query)["total"];

// Count music
$music_query = mysqli_query($conn, "SELECT COUNT(*) AS total FROM music");
$music_count = mysqli_fetch_assoc($music_query)["total"];

// Count videos
$video_query = mysqli_query($conn, "SELECT COUNT(*) AS total FROM videos");
$video_count = mysqli_fetch_assoc($video_query)["total"];

// Count artists
$artist_query = mysqli_query($conn, "SELECT COUNT(*) AS total FROM artists");
$artist_count = mysqli_fetch_assoc($artist_query)["total"];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - SOUND</title>
    <!-- General resets -->
    <link rel="stylesheet" href="../assets/css/style.css?v=<?php echo time(); ?>">
    <!-- Royal Blue Admin Theme -->
    <link rel="stylesheet" href="../assets/css/admin.css?v=<?php echo time(); ?>">
</head>
<body>

<div class="admin-layout">

    <?php 
    $current_page = 'dashboard';
    $admin_base = ''; // base is current dir for dashboard
    include "../includes/admin_header.php"; 
    // $profile_image, $admin_name, $admin_initial are provided by admin_header.php
    ?>

    <main class="admin-main">
        
        <!-- Topbar -->
        <div class="admin-topbar">
            <div class="admin-topbar-left">
                <h1>Dashboard Overview</h1>
                <div class="breadcrumb">Manage your SOUND entertainment platform.</div>
            </div>
            <div class="admin-topbar-right">
                
                <a href="profile.php" class="admin-profile-dropdown" style="text-decoration: none;">
                    <?php if (!empty($profile_image)): ?>
                        <img src="../assets/uploads/profiles/<?php echo htmlspecialchars($profile_image); ?>" alt="Profile" class="admin-avatar">
                    <?php else: ?>
                        <div class="admin-avatar"><?php echo $admin_initial; ?></div>
                    <?php endif; ?>
                    <div class="admin-details">
                        <span class="admin-name"><?php echo $admin_name; ?></span>
                        <span class="admin-role">Administrator</span>
                    </div>
                </a>

                <a href="../logout.php" class="topbar-logout">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12.75" /></svg>
                    Logout
                </a>

            </div>
        </div>

        <div class="admin-content">
            
            <!-- Statistics -->
            <div class="stats-grid">
                <!-- Total Users -->
                <div class="stat-card">
                    <div class="stat-info">
                        <div class="stat-number"><?php echo number_format($user_count); ?></div>
                        <div class="stat-label">Total Users</div>
                    </div>
                    <div class="stat-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5V18a5 5 0 00-10 0v2h5zm-2-9a4 4 0 11-8 0 4 4 0 018 0zm-8 7H2v-2a4 4 0 014-4h1" /></svg>
                    </div>
                </div>

                <!-- Total Music -->
                <div class="stat-card">
                    <div class="stat-info">
                        <div class="stat-number"><?php echo number_format($music_count); ?></div>
                        <div class="stat-label">Total Music</div>
                    </div>
                    <div class="stat-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19V6l12-3v13M9 19a3 3 0 11-6 0 3 3 0 016 0zm12-3a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                    </div>
                </div>

                <!-- Total Videos -->
                <div class="stat-card">
                    <div class="stat-info">
                        <div class="stat-number"><?php echo number_format($video_count); ?></div>
                        <div class="stat-label">Total Videos</div>
                    </div>
                    <div class="stat-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" /></svg>
                    </div>
                </div>

                <!-- Total Artists -->
                <div class="stat-card">
                    <div class="stat-info">
                        <div class="stat-number"><?php echo number_format($artist_count); ?></div>
                        <div class="stat-label">Total Artists</div>
                    </div>
                    <div class="stat-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" /></svg>
                    </div>
                </div>
            </div>

            <!-- Unified Content Management -->
            <h2 class="section-heading">Platform Management</h2>
            <div class="management-grid">
                
                <a href="artists/index.php" class="management-card">
                    <div class="mc-icon-wrapper">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" /></svg>
                    </div>
                    <div class="mc-title">Artists</div>
                    <div class="mc-desc">Manage all artist profiles and metadata.</div>
                    <div class="mc-action">Manage Artists <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3" /></svg></div>
                </a>

                <a href="albums/index.php" class="management-card">
                    <div class="mc-icon-wrapper">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11.99 21.04a9 9 0 100-18.08 9 9 0 000 18.08zM11.99 15.02a3 3 0 100-6.04 3 3 0 000 6.04z" /></svg>
                    </div>
                    <div class="mc-title">Albums</div>
                    <div class="mc-desc">Organize and group music collections.</div>
                    <div class="mc-action">Manage Albums <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3" /></svg></div>
                </a>

                <a href="genres/index.php" class="management-card">
                    <div class="mc-icon-wrapper">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6h9.75M10.5 6a1.5 1.5 0 11-3 0m3 0a1.5 1.5 0 10-3 0M3.75 6H7.5m3 12h9.75m-9.75 0a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m-3.75 0H7.5m9-6h3.75m-3.75 0a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m-9.75 0h9.75" /></svg>
                    </div>
                    <div class="mc-title">Genres</div>
                    <div class="mc-desc">Categorize content for discovery.</div>
                    <div class="mc-action">Manage Genres <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3" /></svg></div>
                </a>

                <a href="languages/index.php" class="management-card">
                    <div class="mc-icon-wrapper">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9.004 9.004 0 008.716-6.747M12 21a9.004 9.004 0 01-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 017.843 4.582M12 3a8.997 8.997 0 00-7.843 4.582m15.686 0A11.953 11.953 0 0112 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0121 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0112 16.5c-3.162 0-6.133-.815-8.716-2.247m0 0A9.015 9.015 0 013 12c0-1.605.42-3.113 1.157-4.418" /></svg>
                    </div>
                    <div class="mc-title">Languages</div>
                    <div class="mc-desc">Configure localization and tracking.</div>
                    <div class="mc-action">Manage Languages <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3" /></svg></div>
                </a>

                <a href="years/index.php" class="management-card">
                    <div class="mc-icon-wrapper">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                    </div>
                    <div class="mc-title">Years</div>
                    <div class="mc-desc">Release years for advanced filtering.</div>
                    <div class="mc-action">Manage Years <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3" /></svg></div>
                </a>

                <a href="music/index.php" class="management-card">
                    <div class="mc-icon-wrapper">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19V6l12-3v13M9 19a3 3 0 11-6 0 3 3 0 016 0zm12-3a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                    </div>
                    <div class="mc-title">Music</div>
                    <div class="mc-desc">Upload and manage audio tracks.</div>
                    <div class="mc-action">Manage Music <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3" /></svg></div>
                </a>
                
                <a href="videos/index.php" class="management-card">
                    <div class="mc-icon-wrapper">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" /></svg>
                    </div>
                    <div class="mc-title">Videos</div>
                    <div class="mc-desc">Upload and manage video content.</div>
                    <div class="mc-action">Manage Videos <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3" /></svg></div>
                </a>

                <a href="users/index.php" class="management-card">
                    <div class="mc-icon-wrapper">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5V18a5 5 0 00-10 0v2h5zm-2-9a4 4 0 11-8 0 4 4 0 018 0zm-8 7H2v-2a4 4 0 014-4h1" /></svg>
                    </div>
                    <div class="mc-title">Users</div>
                    <div class="mc-desc">Moderate accounts and platform users.</div>
                    <div class="mc-action">Manage Users <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3" /></svg></div>
                </a>

            </div>

        </div>

        <?php include '../includes/footer.php'; ?>
    </main>
</div>

<script>
    document.getElementById('sidebarToggle')?.addEventListener('click', () => {
        document.getElementById('sidebar').classList.toggle('open');
    });
</script>


</body>
</html>
