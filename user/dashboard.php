<?php
require_once "../includes/auth.php";
require_once "../config/db.php";
requireUser();

$user_id = $_SESSION['user_id'];
$message = "";
$error = "";

// Handle Profile Image Update / Delete
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'delete') {
        $stmt = mysqli_prepare($conn, "SELECT profile_image FROM users WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        if ($result && $result['profile_image']) {
            $file_path = "../uploads/users/" . $result['profile_image'];
            if (file_exists($file_path)) unlink($file_path);
            
            $stmt = mysqli_prepare($conn, "UPDATE users SET profile_image = NULL WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "i", $user_id);
            mysqli_stmt_execute($stmt);
            $message = "Profile image deleted successfully.";
        }
    } elseif (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../uploads/users/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
        
        $file_name = time() . '_' . basename($_FILES["profile_image"]["name"]);
        $target_file = $upload_dir . $file_name;
        if (move_uploaded_file($_FILES["profile_image"]["tmp_name"], $target_file)) {
            $stmt = mysqli_prepare($conn, "SELECT profile_image FROM users WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "i", $user_id);
            mysqli_stmt_execute($stmt);
            $result = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
            if ($result && $result['profile_image']) {
                $old_file = $upload_dir . $result['profile_image'];
                if (file_exists($old_file)) unlink($old_file);
            }
            $stmt = mysqli_prepare($conn, "UPDATE users SET profile_image = ? WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "si", $file_name, $user_id);
            if(mysqli_stmt_execute($stmt)){
                $message = "Profile image updated successfully.";
            } else {
                $error = "Failed to update database.";
            }
        }
    }
}

// Handle Playlist Creation
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_playlist') {
    $pl_title = mysqli_real_escape_string($conn, trim($_POST['title']));
    $pl_desc = mysqli_real_escape_string($conn, trim($_POST['description'] ?? ''));
    
    if (!empty($pl_title)) {
        $stmt = mysqli_prepare($conn, "INSERT INTO playlists (user_id, title, description, is_public) VALUES (?, ?, ?, 0)");
        mysqli_stmt_bind_param($stmt, "iss", $user_id, $pl_title, $pl_desc);
        if (mysqli_stmt_execute($stmt)) {
            $message = "Playlist created successfully!";
        } else {
            $error = "Failed to create playlist.";
        }
    }
}

// Fetch user data
$stmt = mysqli_prepare($conn, "SELECT * FROM users WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$user_data = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

// Data queries (User specific only)
$liked_query = "SELECT m.*, a.artist_name, al.album_name FROM music m JOIN ratings r ON m.id = r.music_id LEFT JOIN artists a ON m.artist_id = a.id LEFT JOIN albums al ON m.album_id = al.id WHERE r.user_id = $user_id AND r.rating > 0 LIMIT 10";
$liked_result = mysqli_query($conn, $liked_query);
$liked_count = mysqli_num_rows($liked_result);

$playlists_query = "SELECT * FROM playlists WHERE user_id = $user_id ORDER BY created_at DESC";
$playlists_result = mysqli_query($conn, $playlists_query);
$playlist_count = mysqli_num_rows($playlists_result);

$public_playlists_query = "SELECT p.*, u.name as creator_name FROM playlists p JOIN users u ON p.user_id = u.id WHERE p.is_public = 1 ORDER BY p.created_at DESC LIMIT 10";
$public_playlists_result = mysqli_query($conn, $public_playlists_query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SOUND - Web Player</title>
    <link rel="stylesheet" href="../assets/css/style.css?v=<?php echo time(); ?>">
    <script src="../assets/js/audius_player.js?v=<?php echo time(); ?>"></script>
    <style>
        /* SOUND Music-Streaming App Layout */
        :root {
            --app-bg: #0B0B0F;
            --panel-bg: #101017;
            --card-bg: #15151C;
            --card-hover: #1B1B24;
            --text-base: #A1A1AA;
            --text-highlight: #F8FAFC;
            --accent: #8B5CF6;
        }
        body, html {
            margin: 0; padding: 0;
            height: 100vh;
            overflow: hidden;
            background-color: var(--app-bg);
            color: var(--text-highlight);
            font-family: 'Inter', sans-serif;
        }
        a { text-decoration: none; color: inherit; }
        
        .app-container {
            display: grid;
            grid-template-areas: 
                "sidebar main rightpanel"
                "player player player";
            grid-template-columns: 280px 1fr 320px;
            grid-template-rows: 1fr 90px;
            height: 100vh;
        }
        
        @media (max-width: 1200px) {
            .app-container {
                grid-template-areas: 
                    "sidebar main"
                    "player player";
                grid-template-columns: 240px 1fr;
            }
            .right-panel { display: none !important; }
        }
        
        @media (max-width: 768px) {
            .app-container {
                grid-template-areas: 
                    "main"
                    "player";
                grid-template-columns: 1fr;
            }
            .sidebar { display: none !important; }
        }
        
        /* Custom Scrollbar */
        ::-webkit-scrollbar { width: 8px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.3); border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: rgba(255,255,255,0.5); }

        /* Left Sidebar */
        .sidebar {
            grid-area: sidebar;
            padding: 16px 8px;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        .sidebar-nav {
            background: var(--panel-bg);
            border-radius: 8px;
            padding: 12px 16px;
            display: flex;
            flex-direction: column;
            gap: 16px;
        }
        .sidebar-nav a {
            font-weight: 700;
            color: var(--text-base);
            display: flex;
            align-items: center;
            gap: 16px;
            transition: color 0.3s;
        }
        .sidebar-nav a:hover, .sidebar-nav a.active { color: var(--text-highlight); }
        .sidebar-nav svg { width: 24px; height: 24px; fill: currentColor; }
        
        .library-section {
            background: var(--panel-bg);
            border-radius: 8px;
            flex: 1;
            padding: 8px;
            display: flex;
            flex-direction: column;
            overflow-y: auto;
        }
        .lib-header {
            padding: 8px 16px;
            color: var(--text-base);
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .lib-header svg { width: 24px; height: 24px; fill: currentColor; }
        
        .playlist-list {
            display: flex;
            flex-direction: column;
            gap: 4px;
            margin-top: 12px;
        }
        .playlist-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 8px;
            border-radius: 6px;
            cursor: pointer;
            transition: background 0.2s;
        }
        .playlist-item:hover { background: var(--card-hover); }
        .playlist-img {
            width: 48px;
            height: 48px;
            border-radius: 4px;
            background: var(--card-bg);
            object-fit: cover;
            display: flex; align-items: center; justify-content: center;
        }
        .playlist-info { display: flex; flex-direction: column; gap: 4px; }
        .playlist-title { font-weight: 500; font-size: 15px; }
        .playlist-desc { color: var(--text-base); font-size: 13px; }

        /* Main Content */
        .main-content {
            grid-area: main;
            background: linear-gradient(to bottom, #2b2b2b 0%, var(--panel-bg) 100%);
            border-radius: 8px;
            margin: 8px 0;
            overflow-y: auto;
            position: relative;
            display: flex;
            flex-direction: column;
        }
        .top-nav {
            position: sticky;
            top: 0;
            background: rgba(18, 18, 18, 0.7);
            backdrop-filter: blur(20px);
            padding: 16px 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            z-index: 10;
        }
        /* Top Navigation - specific widths for search centering */
        .top-nav-left { flex: 1; }
        .top-nav-center { flex: 2; display: flex; justify-content: center; }
        .top-nav-right { flex: 1; display: flex; justify-content: flex-end; }
        
        .search-container {
            position: relative;
            width: 100%;
            max-width: 400px;
        }
        .search-container svg {
            position: absolute;
            left: 16px; top: 50%; transform: translateY(-50%);
            width: 20px; height: 20px; fill: var(--text-base);
        }
        .search-bar {
            width: 100%;
            background: #242424;
            border: 1px solid transparent;
            border-radius: 500px;
            padding: 14px 16px 14px 48px;
            color: #fff;
            font-size: 14px;
            box-sizing: border-box;
            transition: all 0.3s;
        }
        .search-bar:focus { outline: none; border-color: #fff; background: #333; }
        .search-bar::placeholder { color: #b3b3b3; }
        
        .user-menu {
            display: flex; align-items: center; gap: 8px;
            background: rgba(0,0,0,0.5);
            padding: 4px 16px 4px 4px;
            border-radius: 500px;
            cursor: pointer;
        }
        .user-menu:hover { background: rgba(0,0,0,0.8); }
        .user-avatar {
            width: 32px; height: 32px; border-radius: 50%; object-fit: cover;
            background: #333; display:flex; align-items:center; justify-content:center;
        }

        .profile-header {
            padding: 24px;
            display: flex;
            align-items: center;
            gap: 24px;
            margin-top: 20px;
        }
        .profile-img-large {
            width: 192px; height: 192px;
            border-radius: 50%;
            box-shadow: 0 4px 60px rgba(0,0,0,0.5);
            object-fit: cover;
        }
        .profile-info { 
            flex: 1; 
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        .profile-label { font-size: 14px; font-weight: 700; margin-bottom: 8px; color: #fff; text-shadow: 0 2px 4px rgba(0,0,0,0.5); }
        .profile-name { font-size: clamp(32px, 5vw, 56px); font-weight: 900; margin: 0 0 16px 0; letter-spacing: -2px; color: #fff; text-shadow: 0 4px 8px rgba(0,0,0,0.5); line-height: 1.1; word-break: break-word; }
        .profile-stats { font-size: 14px; font-weight: 500; color: rgba(255,255,255,0.8); }
        
        .content-section { padding: 24px; background: rgba(0,0,0,0.2); flex: 1; }
        .section-title { font-size: 24px; font-weight: 700; margin-bottom: 24px; color: #fff; }
        
        .card-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
            gap: 24px;
        }
        .music-card {
            background: var(--card-bg);
            padding: 16px;
            border-radius: 8px;
            cursor: pointer;
            transition: background 0.3s ease;
            position: relative;
        }
        .music-card:hover { background: var(--card-hover); }
        .music-card img {
            width: 100%; aspect-ratio: 1/1; object-fit: cover;
            border-radius: 6px; margin-bottom: 16px;
            box-shadow: 0 8px 24px rgba(0,0,0,0.5);
        }
        .music-card-title { font-weight: 700; font-size: 16px; margin-bottom: 4px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .music-card-desc { color: var(--text-base); font-size: 14px; line-height: 1.4; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
        
        .image-wrapper { position: relative; margin-bottom: 16px; width: 100%; aspect-ratio: 1/1; border-radius: 8px; }
        .image-wrapper img { width: 100%; height: 100%; margin-bottom: 0 !important; object-fit: cover; border-radius: 8px; }
        
        .play-btn-overlay {
            position: absolute; right: 8px; bottom: 8px;
            width: 48px; height: 48px;
            background: linear-gradient(135deg,#8B5CF6,#EC4899) !important; box-shadow: 0 4px 12px rgba(139, 92, 246, 0.4);
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            opacity: 0; transform: translateY(8px);
            transition: all 0.3s ease;
            z-index: 10;
        }
        .music-card:hover .play-btn-overlay { opacity: 1; transform: translateY(0); }
        .play-btn-overlay:hover { transform: scale(1.08) !important; box-shadow: 0 6px 16px rgba(139, 92, 246, 0.6); }
        .play-btn-overlay svg { width: 24px; height: 24px; fill: #ffffff; }
        .play-btn-overlay .card-play-icon { margin-left: 4px; }
        .play-btn-overlay .card-pause-icon { margin-left: 0; display: none; }
        .play-btn-overlay.is-playing { opacity: 1 !important; transform: translateY(0) !important; }

        /* Right Panel */
        .right-panel {
            grid-area: rightpanel;
            padding: 16px 8px 16px 0;
            display: flex;
            flex-direction: column;
        }
        .now-playing-panel {
            background: var(--panel-bg);
            border-radius: 8px;
            flex: 1;
            padding: 16px;
            display: flex;
            flex-direction: column;
            gap: 16px;
        }
        .np-header { font-weight: 700; font-size: 16px; margin-bottom: 8px; }
        .np-img { width: 100%; aspect-ratio: 1/1; border-radius: 8px; object-fit: cover; background: var(--card-bg); }
        .np-title { font-size: 24px; font-weight: 700; margin: 8px 0 4px; }
        .np-artist { color: var(--text-base); font-size: 16px; }
        .np-empty { display:flex; flex-direction:column; align-items:center; justify-content:center; height:100%; color:var(--text-base); text-align:center;}

        /* Bottom Player */
        .bottom-player {
            grid-area: player;
            background: #000;
            border-top: 1px solid #282828;
            padding: 0 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .player-left { display: flex; align-items: center; gap: 16px; width: 30%; }
        .player-left img { width: 56px; height: 56px; border-radius: 4px; object-fit: cover; background: #282828; }
        .pl-info { display: flex; flex-direction: column; justify-content: center; }
        .pl-title { font-size: 14px; font-weight: 600; color: #fff; text-decoration: none; }
        .pl-title:hover { text-decoration: underline; }
        .pl-artist { font-size: 12px; color: var(--text-base); }
        
        .player-center { display: flex; flex-direction: column; align-items: center; width: 40%; max-width: 722px; gap: 8px; }
        .player-controls { display: flex; align-items: center; gap: 24px; }
        .ctrl-btn { background: none; border: none; color: var(--text-base); cursor: pointer; padding: 0; display:flex; align-items:center; }
        .ctrl-btn:hover { color: #fff; }
        .ctrl-btn svg { width: 16px; height: 16px; fill: currentColor; }
        .ctrl-play { background: #fff; color: #000; width: 40px; height: 40px; border-radius: 50%; display:flex; align-items:center; justify-content:center; transition: transform 0.1s; }
        .ctrl-play:hover { transform: scale(1.05); color: #000; }
        .ctrl-play svg { width: 20px; height: 20px; fill: currentColor; margin-left:2px; }
        .ctrl-play.is-playing svg { margin-left:0; }
        .ctrl-play, .progress-bar-fill { background: linear-gradient(135deg,#8B5CF6,#EC4899) !important; color: #ffffff !important; }
        .play-btn-overlay { background: linear-gradient(135deg,#8B5CF6,#EC4899) !important; box-shadow: 0 4px 12px rgba(139, 92, 246, 0.4); }
        
        .playback-bar { display: flex; align-items: center; gap: 8px; width: 100%; }
        .time-text { font-size: 11px; color: var(--text-base); min-width: 40px; text-align: center; }
        .progress-bar-container { height: 4px; background: #4d4d4d; border-radius: 2px; width: 100%; cursor: pointer; position: relative; }
        .progress-bar-fill { height: 100%; background: #fff; border-radius: 2px; width: 0%; pointer-events: none; }
        .progress-bar-container:hover .progress-bar-fill { background: var(--accent); }
        
        .player-right { display: flex; align-items: center; justify-content: flex-end; gap: 16px; width: 30%; }
        .vol-container { display: flex; align-items: center; gap: 8px; }
        .vol-slider { width: 93px; height: 4px; -webkit-appearance: none; background: #4d4d4d; border-radius: 2px; outline: none; }
        .vol-slider::-webkit-slider-thumb { -webkit-appearance: none; width: 12px; height: 12px; border-radius: 50%; background: #fff; cursor: pointer; display: none; }
        .vol-container:hover .vol-slider::-webkit-slider-thumb { display: block; }

        /* Modals */
        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); backdrop-filter: blur(4px); align-items: center; justify-content: center; }
        .modal-content { background: #1f1f1f; padding: 32px; border-radius: 16px; border: 1px solid #333; box-shadow: 0 10px 40px rgba(0,0,0,0.5); width: calc(100% - 32px); max-width: 420px; box-sizing: border-box; }
        .modal-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 24px; }
        .modal-title { font-size: 24px; font-weight: 700; color: #fff; margin: 0; }
        .modal-close { cursor: pointer; font-size: 28px; font-weight: 300; color: #b3b3b3; line-height: 1; transition: 0.2s; }
        .modal-close:hover { color: #fff; }
        /* Reference layout skin: dark navigation/player with light dashboard panels. */
        body, html { background: #0B0B0F !important; color: #F8FAFC !important; }
        .sidebar-nav, .library-section, .now-playing-panel, .bottom-player { background: #101017 !important; }
        .sidebar-nav a.active { color: #F8FAFC !important; background: rgba(139,92,246,.18); padding: 10px; margin: -10px; border-radius: 8px; }
        .main-content { background: #101017 !important; }
        .top-nav { background: rgba(16,16,23,.92) !important; border-bottom: 1px solid #27272A; }
        .search-bar { background: #111118 !important; color: #F8FAFC !important; border: 1px solid #27272A !important; }
        .content-section { background: transparent !important; }
        .profile-label, .profile-name, .profile-stats, .section-title { color: #F8FAFC !important; text-shadow: none !important; }
        .music-card { background: #15151C !important; border: 1px solid #27272A !important; box-shadow: 0 8px 18px rgba(0,0,0,.18); }
        .music-card-title { color: #F8FAFC !important; }
        .music-card-desc { color: #A1A1AA !important; }
        .ctrl-play, .progress-bar-fill { background: linear-gradient(135deg,#8B5CF6,#EC4899) !important; color: #ffffff !important; }
        .play-btn-overlay { background: linear-gradient(135deg,#8B5CF6,#EC4899) !important; }
    </style>
</head>
<body>

<div class="app-container">

    <!-- LEFT SIDEBAR -->
    <aside class="sidebar">
        <a href="../index.php" class="logo"><img src="../assets/images/sound-logo-white.svg" alt="SOUND Logo"></a>

        <div class="library-section">
            <div class="lib-header" style="justify-content: space-between;">
                <div style="display:flex; align-items:center; gap:12px;">
                    <i data-lucide="library" style="width: 24px; height: 24px;"></i>
                    Your Library
                </div>
                <button onclick="document.getElementById('createPlaylistModal').style.display='flex'" style="background:none;border:none;color:var(--text-base);cursor:pointer;padding:4px;"><i data-lucide="plus" style="width: 20px; height: 20px;"></i></button>
            </div>
            
            <div style="padding: 8px 16px;">
                <button style="background:rgba(255,255,255,0.1); border:none; color:#fff; padding:6px 16px; border-radius:50px; font-size:13px; font-weight:500; cursor:pointer; transition:0.2s;" onmouseover="this.style.background='rgba(255,255,255,0.2)'" onmouseout="this.style.background='rgba(255,255,255,0.1)'">Playlists</button>
            </div>
            
            <div style="padding: 8px 16px; display:flex; justify-content:space-between; align-items:center;">
                <i data-lucide="clock" style="width: 16px; height: 16px;"></i>
                <span style="color:var(--text-base); font-size:13px; font-weight:500; display:flex; align-items:center; gap:4px; cursor:pointer;">Recents <i data-lucide="chevron-down" style="width: 16px; height: 16px;"></i></span>
            </div>

            <div class="playlist-list">
                <a href="#likedSongsSection" class="playlist-item" onclick="document.getElementById('likedSongsSection').scrollIntoView({behavior: 'smooth'})">
                    <div class="playlist-img" style="background: linear-gradient(135deg, #450af5, #c4efd9);">
                        <i data-lucide="heart" style="width: 20px; height: 20px;"></i>
                    </div>
                    <div class="playlist-info">
                        <span class="playlist-title">Liked Songs</span>
                        <span class="playlist-desc">Playlist • <?php echo $liked_count; ?> songs</span>
                    </div>
                </a>

                <?php if ($playlist_count == 0): ?>
                    <div style="padding: 16px 8px;">
                        <div style="background: #242424; padding: 16px; border-radius: 8px;">
                            <div style="font-weight: 700; color: #fff; margin-bottom: 8px;">Create your first playlist</div>
                            <div style="font-size: 13px; color: var(--text-base); margin-bottom: 16px;">It's easy, we'll help you start your playlist.</div>
                            <button onclick="document.getElementById('createPlaylistModal').style.display='flex'" style="background: #fff; color: #000; border: none; padding: 8px 16px; border-radius: 500px; font-weight: 700; font-size: 13px; cursor: pointer;">Create playlist</button>
                        </div>
                    </div>
                <?php else: ?>

                    <?php while ($pl = mysqli_fetch_assoc($playlists_result)): ?>
                    <?php 
                        $pl_id = $pl['id'];
                        $item_count_query = mysqli_query($conn, "SELECT COUNT(*) as count FROM playlist_items WHERE playlist_id = $pl_id");
                        $item_count = mysqli_fetch_assoc($item_count_query)['count'];
                    ?>
                    <a href="playlist.php?id=<?php echo $pl_id; ?>" class="playlist-item">
                        <div class="playlist-img">
                            <?php if ($pl['cover_image']): ?>
                                <img src="../uploads/playlists/<?php echo htmlspecialchars($pl['cover_image']); ?>" style="width:100%;height:100%;object-fit:cover;border-radius:4px;">
                            <?php else: ?>
                                <i data-lucide="music" style="width: 24px; height: 24px;"></i>
                            <?php endif; ?>
                        </div>
                        <div class="playlist-info">
                            <span class="playlist-title"><?php echo htmlspecialchars($pl['title']); ?></span>
                            <span class="playlist-desc">Playlist • <?php echo $item_count; ?> songs</span>
                        </div>
                    </a>
                    <?php endwhile; ?>
                <?php endif; ?>
            </div>
        </div>
    </aside>

    <!-- MAIN CONTENT -->
    <main class="main-content">
        <div class="top-nav">
            <div class="top-nav-left"></div>
            <div class="top-nav-center">
                <div class="search-container">
                    <i data-lucide="clock" style="width: 16px; height: 16px;"></i>
                    <form action="../search.php" method="GET" style="margin:0; width: 100%;">
                        <input type="text" name="q" class="search-bar" placeholder="What do you want to play?">
                    </form>
                </div>
            </div>
            
            <div class="top-nav-right">
                <div class="user-menu" id="profileBtn">
                    <?php if ($user_data['profile_image']): ?>
                        <img src="../uploads/users/<?php echo htmlspecialchars($user_data['profile_image']); ?>" class="user-avatar" alt="Avatar">
                    <?php else: ?>
                        <div class="user-avatar" style="font-size:12px; color:#fff;"><?php echo strtoupper(substr($user_data['name'], 0, 1)); ?></div>
                    <?php endif; ?>
                    <span style="font-weight:700; font-size:14px; margin-right:8px;"><?php echo htmlspecialchars($user_data['name']); ?></span>
                    <i data-lucide="chevron-down" style="width: 16px; height: 16px;"></i>
                </div>
            </div>
        </div>
        
        <div class="profile-header">
            <?php if ($user_data['profile_image']): ?>
                <img src="../uploads/users/<?php echo htmlspecialchars($user_data['profile_image']); ?>" class="profile-img-large" alt="Profile">
            <?php else: ?>
                <div class="profile-img-large" style="background:#333; display:flex; align-items:center; justify-content:center; font-size:64px; color:#fff;"><?php echo strtoupper(substr($user_data['name'], 0, 1)); ?></div>
            <?php endif; ?>
            <div class="profile-info">
                <div class="profile-label">Profile</div>
                <h1 class="profile-name"><?php echo htmlspecialchars($user_data['name']); ?></h1>
                <div class="profile-stats">
                    <?php echo $playlist_count; ?> Public Playlists • <?php echo $liked_count; ?> Liked Songs
                </div>
            </div>
        </div>
        
        <div class="content-section">
            <h2 class="section-title">Public Playlists</h2>
            <div class="card-grid">
                <?php if (mysqli_num_rows($public_playlists_result) > 0): ?>
                    <?php while ($ppl = mysqli_fetch_assoc($public_playlists_result)): ?>
                        <div class="music-card">
                            <?php if ($ppl['cover_image']): ?>
                                <img src="../uploads/playlists/<?php echo htmlspecialchars($ppl['cover_image']); ?>" alt="Cover">
                            <?php else: ?>
                                <div style="width:100%; aspect-ratio:1/1; background:#282828; border-radius:6px; margin-bottom:16px; display:flex; align-items:center; justify-content:center;">
                                    <i data-lucide="music" style="width: 24px; height: 24px;"></i>
                                </div>
                            <?php endif; ?>
                            <div class="music-card-title"><?php echo htmlspecialchars($ppl['title']); ?></div>
                            <div class="music-card-desc">By <?php echo htmlspecialchars($ppl['creator_name']); ?></div>
                            <div class="play-btn-overlay">
                                <i data-lucide="play" style="width: 24px; height: 24px; fill: currentColor; margin-left: 4px;"></i>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p style="color:var(--text-base);">No public playlists available.</p>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Liked Songs Section for playback -->
        <div id="likedSongsSection" class="content-section" style="padding-top: 0;">
            <h2 class="section-title">Your Liked Songs</h2>
            <div class="card-grid">
                <?php if ($liked_count > 0): ?>
                    <?php mysqli_data_seek($liked_result, 0); while ($ls = mysqli_fetch_assoc($liked_result)): ?>
                        <div class="music-card track-item" data-id="<?php echo $ls['id']; ?>" data-src="<?php echo htmlspecialchars(!empty($ls['music_file']) ? $ls['music_file'] : ('audius:' . ($ls['source_track_id'] ?? ''))); ?>" data-title="<?php echo htmlspecialchars($ls['title']); ?>" data-artist="<?php echo htmlspecialchars($ls['artist_name'] ?? 'Unknown Artist'); ?>" data-album="<?php echo htmlspecialchars($ls['album_name'] ?? 'Unknown Album'); ?>" data-img="<?php echo htmlspecialchars($ls['image'] ?? ''); ?>">
                            <div class="image-wrapper">
                                <?php if ($ls['image']): ?>
                                    <?php 
                                        $img_url = $ls['image']; 
                                        if (!preg_match('/^https?:\/\//i', $img_url)) {
                                            $img_url = "../uploads/music/images/" . htmlspecialchars($img_url);
                                        } else {
                                            $img_url = htmlspecialchars($img_url);
                                        }
                                    ?>
                                    <img src="<?php echo $img_url; ?>" alt="Cover">
                                <?php else: ?>
                                    <div style="width:100%; height:100%; background:#282828; display:flex; align-items:center; justify-content:center;">
                                        <i data-lucide="music" style="width: 24px; height: 24px;"></i>
                                    </div>
                                <?php endif; ?>
                                <div class="play-btn-overlay">
                                    <i data-lucide="play" class="card-play-icon"></i>
                                    <i data-lucide="pause" class="card-pause-icon"></i>
                                </div>
                            </div>
                            <div class="music-card-title"><?php echo htmlspecialchars($ls['title']); ?></div>
                            <div class="music-card-desc"><?php echo htmlspecialchars($ls['artist_name'] ?? 'Unknown'); ?></div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p style="color:var(--text-base);">You haven't liked any songs yet.</p>
                <?php endif; ?>
            </div>
        </div>
        
    </main>

    <!-- RIGHT PANEL -->
    <aside class="right-panel">
        <div class="now-playing-panel">
            <div class="np-header">Now Playing</div>
            <div id="npEmpty" class="np-empty">
                <div style="font-size: 32px; margin-bottom: 16px;"><i data-lucide="music" class="icon-ui"></i></div>
                Select a song to start listening
            </div>
            <div id="npActive" style="display:none; flex-direction:column; gap:16px;">
                <img id="npImage" src="" class="np-img" alt="Album Art">
                <div>
                    <a href="#" id="npTitleLink" style="text-decoration:none;"><div id="npTitle" class="np-title">Song Title</div></a>
                    <div id="npArtist" class="np-artist">Artist Name</div>
                    <div id="npAlbum" class="np-artist" style="margin-top:2px;">Album Name</div>
                </div>
                <div style="display:flex; justify-content:space-between; align-items:center;">
                    <i data-lucide="heart" style="width: 20px; height: 20px;"></i>
                    <div style="color:var(--text-base); font-size: 20px; font-weight: bold; cursor: pointer; letter-spacing: 2px;"><i data-lucide="more-vertical" style="width: 20px; height: 20px;"></i></div>
                </div>
            </div>
        </div>
    </aside>

    <!-- BOTTOM PLAYER -->
    <footer class="bottom-player">
        <div class="player-left">
            <img id="bpImage" src="../assets/img/default_cover.jpg" style="visibility:hidden;">
            <div class="pl-info">
                <a href="#" id="bpTitleLink" class="pl-title"></a>
                <div id="bpArtist" class="pl-artist"></div>
            </div>
            <i id="bpLike" data-lucide="heart" style="width: 20px; height: 20px; visibility:hidden;"></i>
        </div>
        
        <div class="player-center">
            <div class="player-controls">
                <button class="ctrl-btn" title="Enable shuffle"><i data-lucide="shuffle" style="width: 20px; height: 20px;"></i></button>
                <button class="ctrl-btn" id="prevBtn" title="Previous"><i data-lucide="skip-back" style="width: 20px; height: 20px;"></i></button>
                <button class="ctrl-btn ctrl-play" id="playBtn" title="Play">
                    <i data-lucide="play" id="playIcon"></i>
                    <i data-lucide="pause" id="pauseIcon" style="display:none;"></i>
                </button>
                <button class="ctrl-btn" id="nextBtn" title="Next"><i data-lucide="skip-forward" style="width: 20px; height: 20px;"></i></button>
                <button class="ctrl-btn" title="Enable repeat"><i data-lucide="repeat" style="width: 20px; height: 20px;"></i></button>
            </div>
            <div class="playback-bar">
                <span id="currentTime" class="time-text">0:00</span>
                <div class="progress-bar-container" id="progressBar">
                    <div class="progress-bar-fill" id="progressFill"></div>
                </div>
                <span id="totalTime" class="time-text">0:00</span>
            </div>
        </div>
        
        <div class="player-right">
            <button class="ctrl-btn" title="Lyrics"><i data-lucide="mic" style="width: 20px; height: 20px;"></i></button>
            <button class="ctrl-btn" title="Queue"><i data-lucide="list-music" style="width: 20px; height: 20px;"></i></button>
            <button class="ctrl-btn" id="muteBtn" title="Mute"><i data-lucide="volume-x" style="width: 20px; height: 20px;"></i></button>
            <div class="vol-container">
                <input type="range" class="vol-slider" id="volSlider" min="0" max="1" step="0.01" value="1">
            </div>
        </div>
    </footer>
    
    <audio id="audioPlayer" style="display:none;"></audio>

</div>

<!-- Profile Modal -->
<div id="profileModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 class="modal-title">Edit Profile Image</h2>
            <span class="modal-close" onclick="document.getElementById('profileModal').style.display='none'">&times;</span>
        </div>
        
        <?php if ($message): ?>
            <div style="background:rgba(16,185,129,0.1);color:#10b981;padding:12px;margin-bottom:20px;border-radius:6px;"><?php echo $message; ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div style="background:rgba(239,68,68,0.1);color:#ef4444;padding:12px;margin-bottom:20px;border-radius:6px;"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST" enctype="multipart/form-data" style="margin-bottom: 20px; display:flex; flex-direction:column; gap:16px;">
            <style>
                #profileImgInput {
                    width: 100%; color: var(--text-base);
                    background: #282828; padding: 16px; border-radius: 8px; border: 1px solid #333; box-sizing: border-box; font-size: 14px;
                }
                #profileImgInput::file-selector-button {
                    background: #fff; color: #000; border: none; padding: 8px 16px; border-radius: 500px; cursor: pointer; font-weight: 700; font-size: 13px; margin-right: 16px; transition: 0.2s;
                }
                #profileImgInput::file-selector-button:hover {
                    transform: scale(1.02);
                }
            </style>
            <input type="file" id="profileImgInput" name="profile_image" accept="image/*" required>
            <button type="submit" style="background:var(--accent); color:#fff; border:none; padding:12px 24px; border-radius:500px; cursor:pointer; font-weight:bold; font-size: 14px; text-align:center; width: 100%; transition: 0.2s;" onmouseover="this.style.opacity='0.9'" onmouseout="this.style.opacity='1'">Upload New Image</button>
        </form>
        
        <?php if ($user_data['profile_image']): ?>
        <form method="POST">
            <input type="hidden" name="action" value="delete">
            <button type="submit" onclick="return confirm('Delete profile image?');" style="background:transparent; color:#ef4444; border:1px solid #ef4444; padding:12px 24px; border-radius:500px; cursor:pointer; font-weight:bold; font-size: 14px; text-align:center; width: 100%; transition: 0.2s;" onmouseover="this.style.background='rgba(239,68,68,0.1)'" onmouseout="this.style.background='transparent'">Remove Image</button>
        </form>
        <?php endif; ?>
        
        <div style="margin-top:24px; border-top:1px solid #333; padding-top:24px; text-align:center;">
            <a href="../logout.php" style="color:var(--text-base); font-weight:500; font-size:14px; text-decoration:none; transition:0.2s;" onmouseover="this.style.color='#fff'" onmouseout="this.style.color='var(--text-base)'">Logout from SOUND</a>
        </div>
    </div>
</div>

<!-- Create Playlist Modal -->
<div id="createPlaylistModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 class="modal-title">Create Playlist</h2>
            <span class="modal-close" onclick="document.getElementById('createPlaylistModal').style.display='none'">&times;</span>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="create_playlist">
            
            <label style="display:block; margin-bottom:8px; font-weight:bold; font-size:14px;">Playlist Name</label>
            <input type="text" name="title" required placeholder="My Awesome Playlist" style="width:100%; padding:12px; margin-bottom:20px; border-radius:6px; border:1px solid #333; background:#242424; color:#fff; box-sizing:border-box;">
            
            <label style="display:block; margin-bottom:8px; font-weight:bold; font-size:14px;">Description</label>
            <textarea name="description" placeholder="Optional description" style="width:100%; padding:12px; margin-bottom:24px; border-radius:6px; border:1px solid #333; background:#242424; color:#fff; box-sizing:border-box; height:80px; resize:vertical; font-family:inherit;"></textarea>
            
            <div style="display:flex; justify-content:flex-end; gap:16px;">
                <button type="button" onclick="document.getElementById('createPlaylistModal').style.display='none'" style="background:transparent; color:#fff; border:none; cursor:pointer; font-weight:bold;">Cancel</button>
                <button type="submit" style="background:var(--accent); color:#000; border:none; padding:10px 24px; border-radius:50px; cursor:pointer; font-weight:bold;">Create Playlist</button>
            </div>
        </form>
    </div>
</div>

<script>
    // Profile Modal
    document.getElementById('profileBtn').addEventListener('click', () => {
        document.getElementById('profileModal').style.display = 'flex';
    });
    
    // Audio Player Logic ported from play_music.php
    const audio = document.getElementById('audioPlayer');
    const playBtn = document.getElementById('playBtn');
    const playIcon = document.getElementById('playIcon');
    const pauseIcon = document.getElementById('pauseIcon');
    const progressBar = document.getElementById('progressBar');
    const progressFill = document.getElementById('progressFill');
    const currentTimeText = document.getElementById('currentTime');
    const totalTimeText = document.getElementById('totalTime');
    const volSlider = document.getElementById('volSlider');
    const muteBtn = document.getElementById('muteBtn');
    
    const npEmpty = document.getElementById('npEmpty');
    const npActive = document.getElementById('npActive');
    const npImage = document.getElementById('npImage');
    const npTitle = document.getElementById('npTitle');
    const npArtist = document.getElementById('npArtist');
    const npAlbum = document.getElementById('npAlbum');
    const npTitleLink = document.getElementById('npTitleLink');
    
    const bpImage = document.getElementById('bpImage');
    const bpTitle = document.getElementById('bpTitle');
    const bpArtist = document.getElementById('bpArtist');
    const bpLike = document.getElementById('bpLike');
    const bpTitleLink = document.getElementById('bpTitleLink');

    let currentPlaylist = [];
    let currentIndex = -1;

    function formatTime(seconds) {
        if (isNaN(seconds)) return "0:00";
        const m = Math.floor(seconds / 60);
        const s = Math.floor(seconds % 60);
        return m + ":" + (s < 10 ? "0" + s : s);
    }

    function loadTrack(trackInfo) {
        if (!trackInfo.src) return;
        if (trackInfo.src.startsWith('audius:')) {
            if (window.SOUNDAudius) window.SOUNDAudius.play(trackInfo.src.substring(7), audio).catch(() => {});
            npEmpty.style.display = 'none'; npActive.style.display = 'flex';
            npTitle.textContent = trackInfo.title; npArtist.textContent = trackInfo.artist;
            bpTitle.textContent = trackInfo.title; bpArtist.textContent = trackInfo.artist;
            return;
        }
        
        if (trackInfo.src.startsWith('http://') || trackInfo.src.startsWith('https://')) {
            audio.src = trackInfo.src;
        } else {
            audio.src = '../uploads/music/files/' + trackInfo.src;
        }
        audio.load();
        
        // Update UI
        npEmpty.style.display = 'none';
        npActive.style.display = 'flex';
        bpImage.style.visibility = 'visible';
        if (bpLike) bpLike.style.visibility = 'visible';
        
        let imgSrc = '../assets/img/default_cover.jpg';
        if (trackInfo.img) {
            if (trackInfo.img.startsWith('http://') || trackInfo.img.startsWith('https://')) {
                imgSrc = trackInfo.img;
            } else {
                imgSrc = '../uploads/music/images/' + trackInfo.img;
            }
        }
        npImage.src = imgSrc;
        bpImage.src = imgSrc;
        
        npTitle.textContent = trackInfo.title;
        bpTitle.textContent = trackInfo.title;
        npArtist.textContent = trackInfo.artist;
        bpArtist.textContent = trackInfo.artist;
        if(npAlbum) npAlbum.textContent = trackInfo.album || 'Unknown Album';
        
        if (trackInfo.id) {
            npTitleLink.href = '../play_music.php?id=' + trackInfo.id;
            bpTitleLink.href = '../play_music.php?id=' + trackInfo.id;
        }
        
        audio.play().catch(e => console.log("Autoplay blocked"));
    }

    // Attach click events to tracks to act as a playlist
    const trackItems = document.querySelectorAll('.track-item');
    trackItems.forEach((item, index) => {
        item.addEventListener('click', (e) => {
            e.preventDefault();
            // Build playlist from all visible track-items
            currentPlaylist = Array.from(trackItems).map(el => ({
                id: el.getAttribute('data-id'),
                src: el.getAttribute('data-src'),
                title: el.getAttribute('data-title'),
                artist: el.getAttribute('data-artist'),
                album: el.getAttribute('data-album'),
                img: el.getAttribute('data-img')
            }));
            
            if (currentIndex === index) {
                if (audio.src) {
                    if (audio.paused) {
                        audio.play().catch(err => console.log(err));
                    } else {
                        audio.pause();
                    }
                }
            } else {
                currentIndex = index;
                loadTrack(currentPlaylist[currentIndex]);
            }
        });
    });

    document.getElementById('nextBtn').addEventListener('click', () => {
        if (currentPlaylist.length > 0 && currentIndex < currentPlaylist.length - 1) {
            currentIndex++;
            loadTrack(currentPlaylist[currentIndex]);
        }
    });

    document.getElementById('prevBtn').addEventListener('click', () => {
        if (currentPlaylist.length > 0 && currentIndex > 0) {
            currentIndex--;
            loadTrack(currentPlaylist[currentIndex]);
        } else if (audio.currentTime > 3) {
            audio.currentTime = 0;
        }
    });

    audio.addEventListener('ended', () => {
        if (currentPlaylist.length > 0 && currentIndex < currentPlaylist.length - 1) {
            currentIndex++;
            loadTrack(currentPlaylist[currentIndex]);
        } else {
            // End of playlist
            playIcon.style.display = 'block';
            pauseIcon.style.display = 'none';
            progressFill.style.width = '0%';
            currentTimeText.textContent = formatTime(0);
        }
    });

    playBtn.addEventListener('click', () => {
        if (!audio.src) return;
        if (audio.paused) {
            audio.play();
        } else {
            audio.pause();
        }
    });

    function updateCardIcons() {
        document.querySelectorAll('.track-item').forEach((item, index) => {
            const playIcon = item.querySelector('.card-play-icon');
            const pauseIcon = item.querySelector('.card-pause-icon');
            const overlay = item.querySelector('.play-btn-overlay');
            if (!overlay) return;
            
            if (index === currentIndex) {
                overlay.classList.add('is-playing');
                if (audio.paused) {
                    if(playIcon) playIcon.style.display = 'block';
                    if(pauseIcon) pauseIcon.style.display = 'none';
                } else {
                    if(playIcon) playIcon.style.display = 'none';
                    if(pauseIcon) pauseIcon.style.display = 'block';
                }
            } else {
                overlay.classList.remove('is-playing');
                if(playIcon) playIcon.style.display = 'block';
                if(pauseIcon) pauseIcon.style.display = 'none';
            }
        });
    }

    audio.addEventListener('play', () => {
        playIcon.style.display = 'none';
        pauseIcon.style.display = 'block';
        updateCardIcons();
    });
    
    audio.addEventListener('pause', () => {
        playIcon.style.display = 'block';
        pauseIcon.style.display = 'none';
        updateCardIcons();
    });

    audio.addEventListener('loadedmetadata', () => {
        totalTimeText.textContent = formatTime(audio.duration);
    });

    audio.addEventListener('timeupdate', () => {
        if (audio.duration) {
            const percent = (audio.currentTime / audio.duration) * 100;
            progressFill.style.width = percent + '%';
            currentTimeText.textContent = formatTime(audio.currentTime);
        }
    });

    progressBar.addEventListener('click', (e) => {
        if (!audio.duration) return;
        const rect = progressBar.getBoundingClientRect();
        const pos = Math.max(0, Math.min(1, (e.clientX - rect.left) / rect.width));
        audio.currentTime = pos * audio.duration;
    });

    let isDragging = false;
    progressBar.addEventListener('mousedown', () => isDragging = true);
    document.addEventListener('mousemove', (e) => {
        if (isDragging && audio.duration) {
            const rect = progressBar.getBoundingClientRect();
            let pos = (e.clientX - rect.left) / rect.width;
            pos = Math.max(0, Math.min(1, pos));
            progressFill.style.width = (pos * 100) + '%';
            audio.currentTime = pos * audio.duration;
        }
    });
    document.addEventListener('mouseup', () => isDragging = false);

    volSlider.addEventListener('input', (e) => {
        audio.volume = e.target.value;
        audio.muted = (audio.volume === 0);
    });

    muteBtn.addEventListener('click', () => {
        audio.muted = !audio.muted;
        volSlider.value = audio.muted ? 0 : audio.volume;
    });

    <?php if ($message || $error): ?>
        // Show modal automatically if there was a response from form submit
        document.getElementById('profileModal').style.display = 'flex';
    <?php endif; ?>
</script>

</body>
</html>
