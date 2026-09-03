<?php
require_once "../includes/auth.php";
require_once "../config/db.php";
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;

if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit;
}
$pl_id = intval($_GET['id']);
$pl_query = "SELECT p.*, u.name as creator_name, u.profile_image FROM playlists p JOIN users u ON p.user_id = u.id WHERE p.id = $pl_id";
$pl_result = mysqli_query($conn, $pl_query);
if (mysqli_num_rows($pl_result) == 0) {
    die("Playlist not found.");
}
$active_playlist = mysqli_fetch_assoc($pl_result);

// Check access
if ($active_playlist['is_public'] == 0 && $active_playlist['user_id'] != $user_id) {
    die("This playlist is private or access is denied.");
}

// User data for sidebar (if logged in)
$user_data = null;
if ($user_id > 0) {
    $stmt = mysqli_prepare($conn, "SELECT * FROM users WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $user_data = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
}

$liked_count = 0;
$playlist_count = 0;
$playlists_result = null;

if ($user_id > 0) {
    $liked_query = "SELECT 1 FROM ratings WHERE user_id = $user_id AND rating > 0";
    $liked_result = mysqli_query($conn, $liked_query);
    $liked_count = mysqli_num_rows($liked_result);

    $playlists_query = "SELECT * FROM playlists WHERE user_id = $user_id ORDER BY created_at DESC";
    $playlists_result = mysqli_query($conn, $playlists_query);
    $playlist_count = mysqli_num_rows($playlists_result);
}

$items_query = "SELECT m.*, a.artist_name, al.album_name FROM playlist_items pi JOIN music m ON pi.music_id = m.id LEFT JOIN artists a ON m.artist_id = a.id LEFT JOIN albums al ON m.album_id = al.id WHERE pi.playlist_id = $pl_id ORDER BY pi.added_at DESC";
$items_result = mysqli_query($conn, $items_query);
$items_count = mysqli_num_rows($items_result);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SOUND - Web Player</title>
    <link rel="stylesheet" href="assets/css/style.css?v=<?php echo time(); ?>">
    <script src="assets/js/audius_player.js?v=<?php echo time(); ?>"></script>
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

        body,
        html {
            margin: 0;
            padding: 0;
            height: 100vh;
            overflow: hidden;
            background-color: var(--app-bg);
            color: var(--text-highlight);
            font-family: 'Inter', sans-serif;
        }

        a {
            text-decoration: none;
            color: inherit;
        }

        .app-container {
            display: grid;
            grid-template-areas:
                "sidebar main"
                "player player";
            grid-template-columns: 280px 1fr;
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
        }

        @media (max-width: 768px) {
            .app-container {
                grid-template-areas:
                    "main"
                    "player";
                grid-template-columns: 1fr;
            }

            .sidebar {
                display: none !important;
            }
        }

        /* Custom Scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
        }

        ::-webkit-scrollbar-track {
            background: transparent;
        }

        ::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.3);
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: rgba(255, 255, 255, 0.5);
        }

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

        .sidebar-nav a:hover,
        .sidebar-nav a.active {
            color: var(--text-highlight);
        }

        .sidebar-nav svg {
            width: 24px;
            height: 24px;
            fill: currentColor;
        }

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

        .lib-header svg {
            width: 24px;
            height: 24px;
            fill: currentColor;
        }

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

        .playlist-item:hover {
            background: var(--card-hover);
        }

        .playlist-img {
            width: 48px;
            height: 48px;
            border-radius: 4px;
            background: var(--card-bg);
            object-fit: cover;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .playlist-info {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .playlist-title {
            font-weight: 500;
            font-size: 15px;
        }

        .playlist-desc {
            color: var(--text-base);
            font-size: 13px;
        }

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
        .top-nav-left {
            flex: 1;
        }

        .top-nav-center {
            flex: 2;
            display: flex;
            justify-content: center;
        }

        .top-nav-right {
            flex: 1;
            display: flex;
            justify-content: flex-end;
        }

        .search-container {
            position: relative;
            width: 100%;
            max-width: 400px;
        }

        .search-container svg {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            width: 20px;
            height: 20px;
            fill: var(--text-base);
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

        .search-bar:focus {
            outline: none;
            border-color: #fff;
            background: #333;
        }

        .search-bar::placeholder {
            color: #b3b3b3;
        }

        .user-menu {
            display: flex;
            align-items: center;
            gap: 8px;
            background: rgba(0, 0, 0, 0.5);
            padding: 4px 16px 4px 4px;
            border-radius: 500px;
            cursor: pointer;
        }

        .user-menu:hover {
            background: rgba(0, 0, 0, 0.8);
        }

        .user-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            object-fit: cover;
            background: #333;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .profile-header {
            padding: 24px;
            display: flex;
            align-items: center;
            gap: 24px;
            margin-top: 20px;
        }

        .profile-img-large {
            width: 192px;
            height: 192px;
            border-radius: 50%;
            box-shadow: 0 4px 60px rgba(0, 0, 0, 0.5);
            object-fit: cover;
        }

        .profile-info {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .profile-label {
            font-size: 14px;
            font-weight: 700;
            margin-bottom: 8px;
            color: #fff;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.5);
        }

        .profile-name {
            font-size: clamp(32px, 5vw, 56px);
            font-weight: 900;
            margin: 0 0 16px 0;
            letter-spacing: -2px;
            color: #fff;
            text-shadow: 0 4px 8px rgba(0, 0, 0, 0.5);
            line-height: 1.1;
            word-break: break-word;
        }

        .profile-stats {
            font-size: 14px;
            font-weight: 500;
            color: rgba(255, 255, 255, 0.8);
        }

        .content-section {
            padding: 24px;
            background: rgba(0, 0, 0, 0.2);
            flex: 1;
        }

        .section-title {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 24px;
            color: #fff;
        }

        .card-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 24px;
        }

        @media (min-width: 600px) {
            .card-grid {
                grid-template-columns: repeat(3, 1fr);
            }
        }

        @media (min-width: 1024px) {
            .card-grid {
                grid-template-columns: repeat(4, 1fr);
            }
        }

        .music-card {
            background: var(--card-bg);
            padding: 16px;
            border-radius: 8px;
            cursor: pointer;
            transition: background 0.3s ease;
            position: relative;
        }

        .music-card:hover {
            background: var(--card-hover);
        }

        .music-card img {
            width: 100%;
            aspect-ratio: 1/1;
            object-fit: cover;
            border-radius: 6px;
            margin-bottom: 16px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.5);
        }

        .music-card-title {
            font-weight: 700;
            font-size: 16px;
            margin-bottom: 4px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .music-card-desc {
            color: var(--text-base);
            font-size: 14px;
            line-height: 1.4;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .play-btn-overlay {
            position: absolute;
            right: 24px;
            bottom: 90px;
            width: 48px;
            height: 48px;
            background: var(--accent);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transform: translateY(8px);
            transition: all 0.3s ease;
            box-shadow: 0 8px 8px rgba(0, 0, 0, 0.3);
        }

        .music-card:hover .play-btn-overlay {
            opacity: 1;
            transform: translateY(0);
        }

        .play-btn-overlay svg {
            width: 24px;
            height: 24px;
            fill: #000;
            margin-left: 4px;
        }

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

        .np-header {
            font-weight: 700;
            font-size: 16px;
            margin-bottom: 8px;
        }

        .np-img {
            width: 100%;
            aspect-ratio: 1/1;
            border-radius: 8px;
            object-fit: cover;
            background: var(--card-bg);
        }

        .np-title {
            font-size: 24px;
            font-weight: 700;
            margin: 8px 0 4px;
        }

        .np-artist {
            color: var(--text-base);
            font-size: 16px;
        }

        .np-empty {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100%;
            color: var(--text-base);
            text-align: center;
        }

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

        .player-left {
            display: flex;
            align-items: center;
            gap: 16px;
            width: 30%;
        }

        .player-left img {
            width: 56px;
            height: 56px;
            border-radius: 4px;
            object-fit: cover;
            background: #282828;
        }

        .pl-info {
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .pl-title {
            font-size: 14px;
            font-weight: 600;
            color: #fff;
            text-decoration: none;
        }

        .pl-title:hover {
            text-decoration: underline;
        }

        .pl-artist {
            font-size: 12px;
            color: var(--text-base);
        }

        .player-center {
            display: flex;
            flex-direction: column;
            align-items: center;
            width: 40%;
            max-width: 722px;
            gap: 8px;
        }

        .player-controls {
            display: flex;
            align-items: center;
            gap: 24px;
        }

        .ctrl-btn {
            background: none;
            border: none;
            color: var(--text-base);
            cursor: pointer;
            padding: 0;
            display: flex;
            align-items: center;
        }

        .ctrl-btn:hover {
            color: #fff;
        }

        .ctrl-btn svg {
            width: 16px;
            height: 16px;
            fill: currentColor;
        }

        .ctrl-play {
            background: #fff;
            color: #000;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: transform 0.1s;
        }

        .ctrl-play:hover {
            transform: scale(1.05);
            color: #000;
        }

        .ctrl-play svg {
            width: 16px;
            height: 16px;
            fill: currentColor;
            margin-left: 2px;
        }

        .ctrl-play.is-playing svg {
            margin-left: 0;
        }

        .playback-bar {
            display: flex;
            align-items: center;
            gap: 8px;
            width: 100%;
        }

        .time-text {
            font-size: 11px;
            color: var(--text-base);
            min-width: 40px;
            text-align: center;
        }

        .progress-bar-container {
            height: 4px;
            background: #4d4d4d;
            border-radius: 2px;
            width: 100%;
            cursor: pointer;
            position: relative;
        }

        .progress-bar-fill {
            height: 100%;
            background: #fff;
            border-radius: 2px;
            width: 0%;
            pointer-events: none;
        }

        .progress-bar-container:hover .progress-bar-fill {
            background: var(--accent);
        }

        .player-right {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 16px;
            width: 30%;
        }

        .vol-container {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .vol-slider {
            width: 93px;
            height: 4px;
            -webkit-appearance: none;
            background: #4d4d4d;
            border-radius: 2px;
            outline: none;
        }

        .vol-slider::-webkit-slider-thumb {
            -webkit-appearance: none;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: #fff;
            cursor: pointer;
            display: none;
        }

        .vol-container:hover .vol-slider::-webkit-slider-thumb {
            display: block;
        }

        /* Profile Modal */
        .modal {
            display: none;
            position: fixed;
            z-index: 100;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            align-items: center;
            justify-content: center;
        }

        .modal-content {
            background: var(--card-bg);
            padding: 30px;
            border-radius: 8px;
            width: 400px;
            max-width: 90%;
        }

        .modal-close {
            float: right;
            cursor: pointer;
            font-size: 24px;
            font-weight: bold;
            color: #b3b3b3;
        }

        .modal-close:hover {
            color: #fff;
        }

        .playlist-row:hover {
            background: rgba(255, 255, 255, 0.1);
        }

        /* Reference layout skin: dark navigation/player with light content panels. */
        body,
        html {
            background: #0B0B0F !important;
            color: #F8FAFC !important;
        }

        .sidebar-nav,
        .library-section,
        .now-playing-panel,
        .bottom-player {
            background: #101017 !important;
        }

        .sidebar-nav a.active {
            color: #F8FAFC !important;
            background: rgba(139, 92, 246, .18);
            padding: 10px;
            margin: -10px;
            border-radius: 8px;
        }

        .main-content {
            background: #101017 !important;
        }

        .top-nav {
            background: rgba(16, 16, 23, .92) !important;
            border-bottom: 1px solid #27272A;
        }

        .search-bar {
            background: #111118 !important;
            color: #F8FAFC !important;
            border: 1px solid #27272A !important;
        }

        .content-section {
            background: transparent !important;
        }

        .profile-label,
        .profile-name,
        .profile-stats,
        .section-title {
            color: #F8FAFC !important;
            text-shadow: none !important;
        }

        .music-card {
            background: #15151C !important;
            border: 1px solid #27272A !important;
            box-shadow: 0 8px 18px rgba(0, 0, 0, .18);
        }

        .music-card-title {
            color: #F8FAFC !important;
        }

        .music-card-desc {
            color: #A1A1AA !important;
        }

        .ctrl-play,
        .progress-bar-fill {
            background: linear-gradient(135deg, #8B5CF6, #EC4899) !important;
            color: #ffffff !important;
        }

        .play-btn-overlay {
            background: linear-gradient(135deg, #8B5CF6, #EC4899) !important;
        }
    </style>
</head>

<body>


    <div class="main-wrapper">

        <div class="app-container">

            <!-- LEFT SIDEBAR -->
            <aside class="sidebar">
                <a href="index.php" class="logo"><img src="assets/images/sound-logo-white.svg" alt="SOUND Logo"></a>

                <div class="library-section">
                    <div class="lib-header" style="justify-content: space-between;">
                        <div style="display:flex; align-items:center; gap:12px;">
                            <svg viewBox="0 0 24 24">
                                <path d="M3 22a1 1 0 0 1-1-1V3a1 1 0 0 1 2 0v18a1 1 0 0 1-1 1zM15.5 2.134A1 1 0 0 0 14 3v18a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V6.464a1 1 0 0 0-.5-.866l-6-3.464zM9 2a1 1 0 0 0-1 1v18a1 1 0 1 0 2 0V3a1 1 0 0 0-1-1z" />
                            </svg>
                            Your Library
                        </div>
                        <button onclick="document.getElementById('createPlaylistModal').style.display='flex'" style="background:none;border:none;color:var(--text-base);cursor:pointer;padding:4px;"><svg viewBox="0 0 24 24" style="width:20px;height:20px;fill:currentColor;">
                                <path d="M11 11V4h2v7h7v2h-7v7h-2v-7H4v-2h7z" />
                            </svg></button>
                    </div>
                    <div class="playlist-list">
                        <?php if ($liked_count == 0 && $playlist_count == 0): ?>
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
                                            <img src="uploads/playlists/<?php echo htmlspecialchars($pl['cover_image']); ?>" style="width:100%;height:100%;object-fit:cover;border-radius:4px;">
                                        <?php else: ?>
                                            <svg viewBox="0 0 24 24" style="width:24px;fill:#b3b3b3">
                                                <path d="M6 3h15v15.167a3.5 3.5 0 1 1-3.5-3.5H19V5H8v13.167a3.5 3.5 0 1 1-3.5-3.5H6V3z" />
                                            </svg>
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
                            <svg viewBox="0 0 24 24">
                                <path d="M10.533 1.279c-5.18 0-9.407 4.14-9.407 9.279s4.226 9.279 9.407 9.279c2.234 0 4.29-.77 5.907-2.058l4.353 4.353a1 1 0 1 0 1.414-1.414l-4.344-4.344a9.157 9.157 0 0 0 2.077-5.816c0-5.14-4.226-9.28-9.407-9.28zm-7.407 9.279c0-4.006 3.302-7.28 7.407-7.28s7.407 3.274 7.407 7.28-3.302 7.279-7.407 7.279-7.407-3.273-7.407-7.279z" />
                            </svg>
                            <form action="search.php" method="GET" style="margin:0; width: 100%;">
                                <input type="text" name="q" class="search-bar" placeholder="What do you want to play?">
                            </form>
                        </div>
                    </div>

                    <div class="top-nav-right">
                        <div class="user-menu" id="profileBtn">
                            <?php if ($user_data['profile_image']): ?>
                                <img src="uploads/users/<?php echo htmlspecialchars($user_data['profile_image']); ?>" class="user-avatar" alt="Avatar">
                            <?php else: ?>
                                <div class="user-avatar" style="font-size:12px; color:#fff;"><?php echo strtoupper(substr($user_data['name'], 0, 1)); ?></div>
                            <?php endif; ?>
                            <span style="font-weight:700; font-size:14px; margin-right:8px;"><?php echo htmlspecialchars($user_data['name']); ?></span>
                            <svg viewBox="0 0 16 16" style="width:16px;height:16px;fill:#fff;margin-right:4px;">
                                <path d="M14 6l-6 6-6-6h12z" />
                            </svg>
                        </div>
                    </div>
                </div>

                <div class="profile-header">
                    <?php if ($active_playlist['cover_image']): ?>
                        <img src="uploads/playlists/<?php echo htmlspecialchars($active_playlist['cover_image']); ?>" class="profile-img-large" style="border-radius:4px;" alt="Cover">
                    <?php else: ?>
                        <div class="profile-img-large" style="background:#282828; display:flex; align-items:center; justify-content:center; border-radius:4px;">
                            <svg viewBox="0 0 24 24" style="width:64px;fill:#b3b3b3">
                                <path d="M6 3h15v15.167a3.5 3.5 0 1 1-3.5-3.5H19V5H8v13.167a3.5 3.5 0 1 1-3.5-3.5H6V3z" />
                            </svg>
                        </div>
                    <?php endif; ?>
                    <div class="profile-info">
                        <div class="profile-label">Playlist</div>
                        <h1 class="profile-name"><?php echo htmlspecialchars($active_playlist['title']); ?></h1>
                        <div class="profile-stats">
                            <?php echo htmlspecialchars($user_data['name']); ?><br>
                            <?php echo $items_count; ?> songs
                        </div>
                        <div style="margin-top: 24px; display:flex; gap:16px;">
                            <?php if ($items_count > 0): ?>
                                <button id="playAllBtn" style="background:var(--accent); color:#000; border:none; width:56px; height:56px; border-radius:50%; display:flex; align-items:center; justify-content:center; cursor:pointer; box-shadow:0 8px 16px rgba(0,0,0,0.3);">
                                    <svg viewBox="0 0 24 24" style="width:28px;height:28px;fill:#000;margin-left:4px;">
                                        <path d="M8 5v14l11-7z" />
                                    </svg>
                                </button>
                            <?php endif; ?>
                            <button id="openAddSongsBtn" style="background:transparent; color:#b3b3b3; border:1px solid #b3b3b3; padding:8px 16px; border-radius:50px; cursor:pointer; font-weight:bold; height:max-content; align-self:center; transition:0.2s;" onmouseover="this.style.color='#fff'; this.style.borderColor='#fff';" onmouseout="this.style.color='#b3b3b3'; this.style.borderColor='#b3b3b3';">+ Add</button>
                            <form method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this playlist?');">
                                <input type="hidden" name="action" value="delete_playlist">
                                <button type="submit" style="background:transparent; color:#ef4444; border:1px solid #ef4444; padding:8px 16px; border-radius:50px; cursor:pointer; font-weight:bold; height:max-content; align-self:center;">Delete Playlist</button>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="content-section" style="padding-top: 0;">
                    <?php if ($items_count > 0): ?>
                        <table style="width:100%; border-collapse:collapse; text-align:left;">
                            <tr style="border-bottom:1px solid #333; color:var(--text-base); font-size:12px; letter-spacing:1px; text-transform:uppercase;">
                                <th style="padding:12px; width:40px;">#</th>
                                <th style="padding:12px;">Title</th>
                                <th style="padding:12px;">Album</th>
                                <th style="padding:12px; width:100px;">Actions</th>
                            </tr>
                            <?php $idx = 1;
                            while ($ls = mysqli_fetch_assoc($items_result)): ?>
                                <tr class="playlist-row track-item" data-id="<?php echo $ls['id']; ?>" data-src="<?php echo htmlspecialchars(!empty($ls['music_file']) ? $ls['music_file'] : ('audius:' . ($ls['source_track_id'] ?? ''))); ?>" data-title="<?php echo htmlspecialchars($ls['title']); ?>" data-artist="<?php echo htmlspecialchars($ls['artist_name'] ?? 'Unknown'); ?>" data-album="<?php echo htmlspecialchars($ls['album_name'] ?? 'Unknown'); ?>" data-img="<?php echo htmlspecialchars($ls['image'] ?? ''); ?>" style="border-bottom:1px solid rgba(255,255,255,0.05); cursor:pointer;">
                                    <td style="padding:12px; color:var(--text-base);"><?php echo $idx++; ?></td>
                                    <td style="padding:12px;">
                                        <div style="display:flex; align-items:center; gap:12px;">
                                            <?php if ($ls['image']): ?>
                                                <?php
                                                $img_url = $ls['image'];
                                                if (!preg_match('/^https?:\/\//i', $img_url)) {
                                                    $img_url = "uploads/music/images/" . htmlspecialchars($img_url);
                                                } else {
                                                    $img_url = htmlspecialchars($img_url);
                                                }
                                                ?>
                                                <img src="<?php echo $img_url; ?>" style="width:40px; height:40px; object-fit:cover; border-radius:4px;">
                                            <?php else: ?>
                                                <div style="width:40px; height:40px; background:#282828; border-radius:4px; display:flex; align-items:center; justify-content:center;">
                                                    <svg viewBox="0 0 24 24" style="width:20px;fill:#b3b3b3">
                                                        <path d="M6 3h15v15.167a3.5 3.5 0 1 1-3.5-3.5H19V5H8v13.167a3.5 3.5 0 1 1-3.5-3.5H6V3z" />
                                                    </svg>
                                                </div>
                                            <?php endif; ?>
                                            <div style="display:flex; flex-direction:column;">
                                                <span style="font-weight:600; color:#fff;"><?php echo htmlspecialchars($ls['title']); ?></span>
                                                <span style="font-size:13px; color:var(--text-base);"><?php echo htmlspecialchars($ls['artist_name'] ?? 'Unknown'); ?></span>
                                            </div>
                                        </div>
                                    </td>
                                    <td style="padding:12px; color:var(--text-base); font-size:14px;"><?php echo htmlspecialchars($ls['album_name'] ?? 'Single'); ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </table>
                    <?php else: ?>
                        <div style="text-align:center; padding:60px 20px; color:var(--text-base);">
                            <svg viewBox="0 0 24 24" style="width:64px;fill:currentColor;margin-bottom:16px;">
                                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 14H9V8h2v8zm4 0h-2V8h2v8z" />
                            </svg>
                            <h3>It's a bit empty here...</h3>
                            <p>Let's find some songs for your playlist</p>
                            <a href="music.php" style="display:inline-block; margin-top:16px; background:#fff; color:#000; padding:10px 24px; border-radius:50px; font-weight:700;">Find songs</a>
                        </div>
                    <?php endif; ?>
                </div>

            </main>

            <!-- BOTTOM PLAYER -->
            <footer class="bottom-player">
                <div class="player-left">
                    <img id="bpImage" src="assets/img/default_cover.jpg" style="visibility:hidden;">
                    <div class="pl-info">
                        <a href="#" id="bpTitleLink" class="pl-title"></a>
                        <div id="bpArtist" class="pl-artist"></div>
                    </div>
                    <svg id="bpLike" viewBox="0 0 24 24" style="width:16px;fill:var(--text-base);visibility:hidden;">
                        <path d="M5.21 3.08c3.16-2.72 7.82-1.35 9.79 3.02 1.97-4.37 6.63-5.74 9.79-3.02 3.56 3.07 3.07 8.78-.97 12.33L15 22l-8.82-6.59c-4.04-3.55-4.53-9.26-.97-12.33z" />
                    </svg>
                </div>

                <div class="player-center">
                    <div class="player-controls">
                        <button class="ctrl-btn" title="Enable shuffle"><svg viewBox="0 0 16 16">
                                <path d="M13.151.922a.75.75 0 1 0-1.06 1.06L13.109 3H11.16a3.75 3.75 0 0 0-2.873 1.34l-6.173 7.356A2.25 2.25 0 0 1 .39 12.5H0V14h.391a3.75 3.75 0 0 0 2.873-1.34l6.173-7.356a2.25 2.25 0 0 1 1.724-.804h1.947l-1.017 1.018a.75.75 0 0 0 1.06 1.06L15.98 3.75 13.15.922zM.391 3.5H0V2h.391c1.109 0 2.16.49 2.873 1.34L4.89 5.277l-.979 1.167-1.796-2.14A2.25 2.25 0 0 0 .39 3.5z" />
                                <path d="M7.5 10.723l.98-1.167 1.795 2.14A2.25 2.25 0 0 0 11.999 12.5h1.927l-1.018-1.018a.75.75 0 1 1 1.06-1.06l2.829 2.828-2.829 2.828a.75.75 0 1 1-1.06-1.06L13.927 14h-1.927a3.75 3.75 0 0 1-2.873-1.34l-1.627-1.937z" />
                            </svg></button>
                        <button class="ctrl-btn" id="prevBtn" title="Previous"><svg viewBox="0 0 16 16">
                                <path d="M3.3 1a.7.7 0 0 1 .7.7v5.15l9.95-5.744a.7.7 0 0 1 1.05.606v12.575a.7.7 0 0 1-1.05.607L4 9.149V14.3a.7.7 0 0 1-.7.7H1.7a.7.7 0 0 1-.7-.7V1.7a.7.7 0 0 1 .7-.7h1.6z" />
                            </svg></button>
                        <button class="ctrl-btn ctrl-play" id="playBtn" title="Play">
                            <svg id="playIcon" viewBox="0 0 16 16">
                                <path d="M3 1.713a.7.7 0 0 1 1.05-.607l10.89 6.288a.7.7 0 0 1 0 1.212L4.05 14.894A.7.7 0 0 1 3 14.288V1.713z" />
                            </svg>
                            <svg id="pauseIcon" viewBox="0 0 16 16" style="display:none;margin:0;">
                                <path d="M2.7 1a.7.7 0 0 0-.7.7v12.6a.7.7 0 0 0 .7.7h2.6a.7.7 0 0 0 .7-.7V1.7a.7.7 0 0 0-.7-.7H2.7zm8 0a.7.7 0 0 0-.7.7v12.6a.7.7 0 0 0 .7.7h2.6a.7.7 0 0 0 .7-.7V1.7a.7.7 0 0 0-.7-.7h-2.6z" />
                            </svg>
                        </button>
                        <button class="ctrl-btn" id="nextBtn" title="Next"><svg viewBox="0 0 16 16">
                                <path d="M12.7 1a.7.7 0 0 0-.7.7v5.15L2.05 1.107A.7.7 0 0 0 1 1.712v12.575a.7.7 0 0 0 1.05.607L12 9.149V14.3a.7.7 0 0 0 .7.7h1.6a.7.7 0 0 0 .7-.7V1.7a.7.7 0 0 0-.7-.7h-1.6z" />
                            </svg></button>
                        <button class="ctrl-btn" title="Enable repeat"><svg viewBox="0 0 16 16">
                                <path d="M0 4.75A3.75 3.75 0 0 1 3.75 1h8.5A3.75 3.75 0 0 1 16 4.75v5a3.75 3.75 0 0 1-3.75 3.75H9.81l1.018 1.018a.75.75 0 1 1-1.06 1.06L6.939 12.75l2.829-2.828a.75.75 0 1 1 1.06 1.06L9.811 12h2.439a2.25 2.25 0 0 0 2.25-2.25v-5a2.25 2.25 0 0 0-2.25-2.25h-8.5A2.25 2.25 0 0 0 1.5 4.75v5A2.25 2.25 0 0 0 3.75 12H5v1.5H3.75A3.75 3.75 0 0 1 0 9.75v-5z" />
                            </svg></button>
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
                    <button class="ctrl-btn" title="Lyrics"><svg viewBox="0 0 16 16">
                            <path d="M13.426 2.574a2.831 2.831 0 0 0-4.004 0l-7.137 7.136a1 1 0 0 0-.269.52l-.996 4.978a1 1 0 0 0 1.17 1.171l4.977-.996a1 1 0 0 0 .52-.269l7.136-7.137a2.83 2.83 0 0 0 0-4.003zM5.522 13.318l-3.376.675.675-3.376 6.002-6.002 2.7 2.7-6.001 6.003zm8.01-8.01l-1.05 1.05-2.7-2.7 1.05-1.05a1.33 1.33 0 0 1 1.88 0l.82.82a1.33 1.33 0 0 1 0 1.88z" />
                        </svg></button>
                    <button class="ctrl-btn" title="Queue"><svg viewBox="0 0 16 16">
                            <path d="M15 15H1v-1.5h14V15zm0-4.5H1V9h14v1.5zm-14-7A2.5 2.5 0 0 1 3.5 1h9a2.5 2.5 0 0 1 0 5h-9A2.5 2.5 0 0 1 1 3.5zm2.5-1a1 1 0 0 0 0 2h9a1 1 0 1 0 0-2h-9z" />
                        </svg></button>
                    <button class="ctrl-btn" id="muteBtn" title="Mute"><svg viewBox="0 0 16 16">
                            <path d="M9.741.85a.75.75 0 0 1 .375.65v13a.75.75 0 0 1-1.125.65l-6.925-4a3.642 3.642 0 0 1-1.33-4.967 3.639 3.639 0 0 1 1.33-1.332l6.925-4a.75.75 0 0 1 .75 0zm-6.924 5.3a2.139 2.139 0 0 0 0 3.7l5.8 3.35V2.8l-5.8 3.35zm8.683 4.29V5.56a2.75 2.75 0 0 1 0 4.88z" />
                        </svg></button>
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
                <span class="modal-close" onclick="document.getElementById('profileModal').style.display='none'">&times;</span>
                <h2 style="margin-bottom: 20px;">Edit Profile Image</h2>
                <?php if ($message): ?>
                    <div style="background:rgba(16,185,129,0.1);color:#10b981;padding:12px;margin-bottom:20px;border-radius:6px;"><?php echo $message; ?></div>
                <?php endif; ?>
                <?php if ($error): ?>
                    <div style="background:rgba(239,68,68,0.1);color:#ef4444;padding:12px;margin-bottom:20px;border-radius:6px;"><?php echo $error; ?></div>
                <?php endif; ?>
                <form method="POST" enctype="multipart/form-data" style="margin-bottom: 16px;">
                    <input type="file" name="profile_image" accept="image/*" required style="margin-bottom: 12px; width:100%; color:var(--text-base);">
                    <button type="submit" style="background:var(--accent); color:#000; border:none; padding:8px 16px; border-radius:50px; cursor:pointer; font-weight:bold;">Upload New Image</button>
                </form>
                <?php if ($user_data['profile_image']): ?>
                    <form method="POST">
                        <input type="hidden" name="action" value="delete">
                        <button type="submit" onclick="return confirm('Delete profile image?');" style="background:transparent; color:#ef4444; border:1px solid #ef4444; padding:8px 16px; border-radius:50px; cursor:pointer; font-weight:bold;">Remove Image</button>
                    </form>
                <?php endif; ?>
                <div style="margin-top:20px; border-top:1px solid #333; padding-top:20px;">
                    <a href="logout.php" style="color:var(--text-base); font-weight:bold; display:block; text-align:center;">Logout from SOUND</a>
                </div>
            </div>
        </div>

        <!-- Create Playlist Modal -->
        <div id="createPlaylistModal" class="modal">
            <div class="modal-content">
                <span class="modal-close" onclick="document.getElementById('createPlaylistModal').style.display='none'">&times;</span>
                <h2 style="margin-bottom: 20px; color: #fff;">Create Playlist</h2>
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
                    if (window.SOUNDAudius) window.SOUNDAudius.play(trackInfo.src.substring(7), document.getElementById('audioElement')).catch(() => {});
                    return;
                }

                if (trackInfo.src.startsWith('http://') || trackInfo.src.startsWith('https://')) {
                    audio.src = trackInfo.src;
                } else {
                    audio.src = 'uploads/music/files/' + trackInfo.src;
                }
                audio.load();

                // Update UI
                bpImage.style.visibility = 'visible';
                bpLike.style.visibility = 'visible';

                let imgSrc = 'assets/img/default_cover.jpg';
                if (trackInfo.img) {
                    if (trackInfo.img.startsWith('http://') || trackInfo.img.startsWith('https://')) {
                        imgSrc = trackInfo.img;
                    } else {
                        imgSrc = 'uploads/music/images/' + trackInfo.img;
                    }
                }
                bpImage.src = imgSrc;

                bpTitle.textContent = trackInfo.title;
                bpArtist.textContent = trackInfo.artist;

                if (trackInfo.id) {
                    bpTitleLink.href = 'play_music.php?id=' + trackInfo.id;
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
                    currentIndex = index;
                    loadTrack(currentPlaylist[currentIndex]);
                });
            });

            const playAllBtn = document.getElementById('playAllBtn');
            if (playAllBtn && trackItems.length > 0) {
                playAllBtn.addEventListener('click', () => {
                    currentPlaylist = Array.from(trackItems).map(el => ({
                        id: el.getAttribute('data-id'),
                        src: el.getAttribute('data-src'),
                        title: el.getAttribute('data-title'),
                        artist: el.getAttribute('data-artist'),
                        album: el.getAttribute('data-album'),
                        img: el.getAttribute('data-img')
                    }));
                    currentIndex = 0;
                    loadTrack(currentPlaylist[currentIndex]);
                });
            }

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

            audio.addEventListener('play', () => {
                playIcon.style.display = 'none';
                pauseIcon.style.display = 'block';
            });

            audio.addEventListener('pause', () => {
                playIcon.style.display = 'block';
                pauseIcon.style.display = 'none';
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

            // ----- ADD SONGS MODAL LOGIC -----
            const currentPlaylistId = <?php echo intval($pl_id); ?>;
            const openAddSongsBtn = document.getElementById('openAddSongsBtn');
            const addSongsModal = document.getElementById('addSongsModal');
            const closeAddSongsModal = document.getElementById('closeAddSongsModal');
            const addSongsSearch = document.getElementById('addSongsSearch');
            const addSongsResults = document.getElementById('addSongsResults');
            let searchTimeout = null;
            let didAddSongs = false;

            if (openAddSongsBtn) {
                openAddSongsBtn.addEventListener('click', () => {
                    addSongsModal.style.display = 'flex';
                    didAddSongs = false;
                    fetchSongs('');
                });
            }

            if (closeAddSongsModal) {
                closeAddSongsModal.addEventListener('click', () => {
                    addSongsModal.style.display = 'none';
                    if (didAddSongs) {
                        window.location.reload();
                    }
                });
            }

            if (addSongsSearch) {
                addSongsSearch.addEventListener('input', (e) => {
                    clearTimeout(searchTimeout);
                    searchTimeout = setTimeout(() => {
                        fetchSongs(e.target.value);
                    }, 300);
                });
            }

            function fetchSongs(query) {
                addSongsResults.innerHTML = '<div style="text-align:center; padding:20px; color:#b3b3b3;">Loading...</div>';
                fetch(`ajax_playlist_add.php?action=search&playlist_id=${currentPlaylistId}&q=${encodeURIComponent(query)}`)
                    .then(res => res.json())
                    .then(data => {
                        if (data.songs) {
                            renderSongs(data.songs);
                        } else {
                            addSongsResults.innerHTML = '<div style="text-align:center; padding:20px; color:#ef4444;">Error loading songs</div>';
                        }
                    })
                    .catch(err => {
                        addSongsResults.innerHTML = '<div style="text-align:center; padding:20px; color:#ef4444;">Error loading songs</div>';
                    });
            }

            function renderSongs(songs) {
                if (songs.length === 0) {
                    addSongsResults.innerHTML = '<div style="text-align:center; padding:20px; color:#b3b3b3;">No songs found.</div>';
                    return;
                }

            })
            .catch(err => {
                btn.disabled = false;
                btn.innerHTML = '+ Add';
                alert('Error adding song.');
            });
            };
        </script>

        <!-- Add Songs Modal -->
        <div id="addSongsModal" style="display:none; position:fixed; z-index:2000; left:0; top:0; width:100%; height:100%; background:rgba(0,0,0,0.8); align-items:center; justify-content:center;">
            <div style="background:var(--card-bg); padding:24px; border-radius:12px; width:500px; max-width:90%; position:relative; box-shadow:0 10px 30px rgba(0,0,0,0.5); display:flex; flex-direction:column; max-height:80vh;">
                <span id="closeAddSongsModal" style="position:absolute; right:20px; top:20px; cursor:pointer; font-size:24px; color:#b3b3b3;">&times;</span>
                <h2 style="margin-bottom:16px; color:#fff;">Add to playlist</h2>
                <div style="margin-bottom: 16px;">
                    <input type="text" id="addSongsSearch" placeholder="Search for songs or artists" style="width:100%; padding:12px; border-radius:4px; border:none; background:rgba(255,255,255,0.1); color:#fff; outline:none; font-size:14px; box-sizing:border-box;">
                </div>
                <div style="flex:1; overflow-y:auto;">
                    <h3 style="font-size:14px; color:var(--text-base); margin-bottom:12px; text-transform:uppercase; letter-spacing:1px;">Suggested songs</h3>
                    <div id="addSongsResults" style="display:flex; flex-direction:column; gap:8px;">
                        <!-- Results go here -->
                    </div>
                </div>
            </div>
        </div> <!-- End app-container -->
    </div> <!-- End user-main-content -->
</body>

</html>