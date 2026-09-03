<?php
if (basename($_SERVER['PHP_SELF']) === 'play_music.php' || basename($_SERVER['PHP_SELF']) === 'play_video.php') {
    return;
}

if (!isset($current_page) || empty($current_page)) {
    $current_filename = basename($_SERVER['PHP_SELF']);
    switch ($current_filename) {
        case 'index.php':
            $current_page = 'index';
            break;
        case 'music.php':
            if (isset($_GET['artist'])) {
                $current_page = 'artists';
            } elseif (isset($_GET['album'])) {
                $current_page = 'albums';
            } else {
                $current_page = 'music';
            }
            break;
        case 'play_music.php':
            $current_page = 'music';
            break;
        case 'videos.php':
            if (isset($_GET['artist'])) {
                $current_page = 'artists';
            } elseif (isset($_GET['album'])) {
                $current_page = 'albums';
            } else {
                $current_page = 'videos';
            }
            break;
        case 'play_video.php':
            $current_page = 'videos';
            break;
        case 'artists.php':
            $current_page = 'artists';
            break;
        case 'albums.php':
            $current_page = 'albums';
            break;
        case 'categories.php':
            $current_page = 'categories';
            break;
        case 'languages.php':
            $current_page = 'languages';
            break;
        case 'years.php':
            $current_page = 'years';
            break;
        case 'my_playlists.php':
        case 'playlist.php':
            $current_page = 'my_playlists';
            break;
        case 'favorites.php':
            $current_page = 'favorites';
            break;
        case 'dashboard.php':
            $current_page = 'dashboard';
            break;
        default:
            $current_page = '';
            break;
    }
}

$user_name = htmlspecialchars($_SESSION['name'] ?? 'User');
$user_initial = strtoupper(substr($user_name, 0, 1));
$user_id = $_SESSION['user_id'] ?? null;
$profile_image = null;

if ($user_id && isset($conn)) {
    $q_img = mysqli_query($conn, "SELECT profile_image FROM users WHERE id = '$user_id'");
    if ($q_img && mysqli_num_rows($q_img) > 0) {
        $row_img = mysqli_fetch_assoc($q_img);
        $profile_image = $row_img['profile_image'];
    }
}

// Ensure base path is correct for links
$base_path = '/SOUND/'; 
?>

<!-- Sidebar -->
<aside class="sidebar user-sidebar" id="userSidebar">

    <div class="sidebar-header" style="position: relative;">
        <a href="<?php echo $base_path; ?>index.php" class="logo"><img src="<?php echo $base_path; ?>assets/images/sound-logo-white.svg" alt="SOUND Logo"></a>
    </div>

    <nav class="sidebar-nav user-sidebar-nav">

        <div class="nav-section">
            <div class="nav-section-title">Main</div>
            <a href="<?php echo $base_path; ?>index.php" class="<?php echo $current_page === 'index' ? 'active' : ''; ?>">
                <span class="nav-icon"><i data-lucide="house"></i></span> Home
            </a>
            <a href="<?php echo $base_path; ?>music.php" class="<?php echo $current_page === 'music' ? 'active' : ''; ?>">
                <span class="nav-icon"><i data-lucide="music"></i></span> Music
            </a>
            <a href="<?php echo $base_path; ?>videos.php" class="<?php echo $current_page === 'videos' ? 'active' : ''; ?>">
                <span class="nav-icon"><i data-lucide="video"></i></span> Videos
            </a>
        </div>

        <div class="nav-section">
            <div class="nav-section-title">Browse</div>
            <a href="<?php echo $base_path; ?>user/artists.php" class="<?php echo $current_page === 'artists' ? 'active' : ''; ?>">
                <span class="nav-icon"><i data-lucide="users"></i></span> Artists
            </a>
            <a href="<?php echo $base_path; ?>user/albums.php" class="<?php echo $current_page === 'albums' ? 'active' : ''; ?>">
                <span class="nav-icon"><i data-lucide="disc"></i></span> Albums
            </a>
            <a href="<?php echo $base_path; ?>user/categories.php" class="<?php echo $current_page === 'categories' ? 'active' : ''; ?>">
                <span class="nav-icon"><i data-lucide="grid"></i></span> Categories
            </a>
            <a href="<?php echo $base_path; ?>user/languages.php" class="<?php echo $current_page === 'languages' ? 'active' : ''; ?>">
                <span class="nav-icon"><i data-lucide="globe"></i></span> Languages
            </a>
            <a href="<?php echo $base_path; ?>user/years.php" class="<?php echo $current_page === 'years' ? 'active' : ''; ?>">
                <span class="nav-icon"><i data-lucide="calendar"></i></span> Years
            </a>
        </div>

        <div class="nav-section">
            <div class="nav-section-title">My Library</div>
            <a href="<?php echo $base_path; ?>user/my_playlists.php" class="<?php echo $current_page === 'my_playlists' ? 'active' : ''; ?>">
                <span class="nav-icon"><i data-lucide="list-music"></i></span> My Playlists
            </a>
            <a href="<?php echo $base_path; ?>user/favorites.php" class="<?php echo $current_page === 'favorites' ? 'active' : ''; ?>">
                <span class="nav-icon"><i data-lucide="heart"></i></span> Favorites
            </a>
        </div>

        <div class="nav-section">
            <div class="nav-section-title">Account</div>
            <a href="<?php echo $base_path; ?>user/dashboard.php" class="<?php echo $current_page === 'dashboard' ? 'active' : ''; ?>">
                <span class="nav-icon"><i data-lucide="user"></i></span> My Profile
            </a>
            <a href="<?php echo $base_path; ?>logout.php">
                <span class="nav-icon"><i data-lucide="log-out"></i></span> Logout
            </a>
        </div>

    </nav>

</aside>

<!-- Mobile sidebar toggle -->
<button class="sidebar-toggle user-sidebar-toggle" id="userSidebarToggle">
    <i data-lucide="menu"></i>
</button>

<script src="https://unpkg.com/lucide@latest"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        if (typeof lucide !== 'undefined') lucide.createIcons();
        
        document.getElementById('userSidebarToggle')?.addEventListener('click', () => {
            document.getElementById('userSidebar').classList.toggle('open');
        });
    });
</script>
