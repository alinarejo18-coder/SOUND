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

if (!isset($_GET['id'])) {
    header("Location: dashboard.php");
    exit;
}
$pl_id = intval($_GET['id']);
$pl_query = "SELECT * FROM playlists WHERE id = $pl_id AND user_id = $user_id";
$pl_result = mysqli_query($conn, $pl_query);
if (mysqli_num_rows($pl_result) == 0) {
    die("Playlist not found or access denied.");
}
$active_playlist = mysqli_fetch_assoc($pl_result);

// Handle Remove Song
if (isset($_POST['action']) && $_POST['action'] === 'remove_song') {
    $m_id = intval($_POST['music_id']);
    mysqli_query($conn, "DELETE FROM playlist_items WHERE playlist_id = $pl_id AND music_id = $m_id");
}

// Handle Delete Playlist
if (isset($_POST['action']) && $_POST['action'] === 'delete_playlist') {
    mysqli_query($conn, "DELETE FROM playlist_items WHERE playlist_id = $pl_id");
    mysqli_query($conn, "DELETE FROM playlists WHERE id = $pl_id");
    header("Location: dashboard.php");
    exit;
}

// Handle Playlist Cover Delete
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_cover') {
    if ($active_playlist['cover_image']) {
        $file_path = "../uploads/playlists/" . $active_playlist['cover_image'];
        if (file_exists($file_path)) unlink($file_path);
        mysqli_query($conn, "UPDATE playlists SET cover_image = NULL WHERE id = $pl_id AND user_id = $user_id");
        header("Location: playlist.php?id=" . $pl_id);
        exit;
    }
}

// Handle Playlist Cover Update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['playlist_cover']) && $_FILES['playlist_cover']['error'] === UPLOAD_ERR_OK) {
    $upload_dir = '../uploads/playlists/';
    if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
    
    $file_name = time() . '_' . basename($_FILES["playlist_cover"]["name"]);
    $target_file = $upload_dir . $file_name;
    if (move_uploaded_file($_FILES["playlist_cover"]["tmp_name"], $target_file)) {
        if ($active_playlist['cover_image']) {
            $old_file = $upload_dir . $active_playlist['cover_image'];
            if (file_exists($old_file)) unlink($old_file);
        }
        $stmt = mysqli_prepare($conn, "UPDATE playlists SET cover_image = ? WHERE id = ? AND user_id = ?");
        mysqli_stmt_bind_param($stmt, "sii", $file_name, $pl_id, $user_id);
        mysqli_stmt_execute($stmt);
        header("Location: playlist.php?id=" . $pl_id);
        exit;
    }
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
            .sidebar { 
                display: flex !important; 
                position: fixed !important;
                top: 0;
                right: -100%;
                bottom: 0;
                width: min(85vw, 350px) !important;
                background: #101017 !important;
                z-index: 1000 !important;
                transition: right 0.3s ease-in-out !important;
                box-shadow: -5px 0 25px rgba(0,0,0,0.5);
                padding-bottom: 20px !important; 
                overflow-y: auto;
            }
            .sidebar.open {
                right: 0 !important;
            }
            .mobile-sidebar-toggle-btn {
                position: fixed;
                top: 15px;
                left: 15px;
                width: 45px;
                height: 45px;
                border-radius: 50%;
                background: linear-gradient(135deg, #8B5CF6, #EC4899);
                color: white;
                border: none;
                display: flex;
                align-items: center;
                justify-content: center;
                box-shadow: 0 4px 15px rgba(0,0,0,0.5);
                z-index: 9999;
                cursor: pointer;
            }
        }
        @media (min-width: 769px) {
            .mobile-sidebar-toggle-btn { display: none !important; }
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
        .top-nav-left { flex: 1; min-width: 0; }
        .top-nav-center { flex: 2; display: flex; justify-content: flex-start; padding-left: 20px; min-width: 0; }
        .top-nav-right { flex: 1; display: flex; justify-content: flex-end; min-width: 0; flex-shrink: 0; gap: 16px; }
        
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
        
        .play-btn-overlay {
            position: absolute; right: 24px; bottom: 90px;
            width: 48px; height: 48px;
            background: var(--accent);
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            opacity: 0; transform: translateY(8px);
            transition: all 0.3s ease;
            box-shadow: 0 8px 8px rgba(0,0,0,0.3);
            color: #ffffff;
        }
        .music-card:hover .play-btn-overlay { opacity: 1; transform: translateY(0); }
        .play-btn-overlay svg { width: 24px; height: 24px; fill: #ffffff; margin-left: 4px; }

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
        .ctrl-play { background: #fff; color: #000; width: 32px; height: 32px; border-radius: 50%; display:flex; align-items:center; justify-content:center; transition: transform 0.1s; }
        .ctrl-play:hover { transform: scale(1.05); color: #000; }
        .ctrl-play svg { width: 16px; height: 16px; fill: currentColor; margin-left:2px; }
        .ctrl-play.is-playing svg { margin-left:0; }
        
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

        /* Profile Modal */
        .modal { display: none; position: fixed; z-index: 100; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); align-items: center; justify-content: center; }
        .modal-content { background: var(--card-bg); padding: 30px; border-radius: 8px; width: 400px; max-width: 90%; }
        .modal-close { float: right; cursor: pointer; font-size: 24px; font-weight: bold; color: #b3b3b3; }
        .modal-close:hover { color: #fff; }
        
        .custom-file-input::-webkit-file-upload-button {
            background: var(--accent, #1db954);
            color: #000;
            border: none;
            padding: 8px 16px;
            border-radius: 50px;
            cursor: pointer;
            font-weight: bold;
            margin-right: 12px;
            transition: transform 0.2s;
        }
        .custom-file-input::-webkit-file-upload-button:hover {
            transform: scale(1.05);
        }
        .custom-file-input {
            background: #242424;
            padding: 12px;
            border-radius: 6px;
            border: 1px dashed #4d4d4d;
        }
        
        .playlist-row:hover { background: rgba(255,255,255,0.1); }
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
        
        /* Responsive Overrides */
        @media (max-width: 768px) {
            .app-container {
                grid-template-rows: 1fr auto !important;
                width: 100% !important;
                max-width: 100vw !important;
                overflow-x: hidden !important;
                box-sizing: border-box !important;
            }
            .main-content {
                margin: 0 !important;
                border-radius: 0 !important;
                width: 100% !important;
                box-sizing: border-box !important;
            }
            .top-nav {
                padding: 12px 16px !important;
                padding-left: 75px !important;
                flex-wrap: wrap !important;
                gap: 12px !important;
                width: 100% !important;
                box-sizing: border-box !important;
            }
            .top-nav-left { display: none !important; }
            .top-nav-center { order: 3 !important; flex: 1 1 100% !important; padding-left: 0 !important; min-width: 0 !important; box-sizing: border-box !important; }
            .top-nav-right { order: 2 !important; flex: 1 !important; justify-content: flex-start !important; min-width: 0 !important; box-sizing: border-box !important; }
            .search-container { max-width: 100% !important; width: 100% !important; box-sizing: border-box !important; }
            .search-bar { width: 100% !important; box-sizing: border-box !important; }

            .profile-header {
                flex-direction: column !important;
                text-align: center !important;
                padding: 24px 16px 16px !important;
                margin-top: 0 !important;
                gap: 16px !important;
                width: 100% !important;
                box-sizing: border-box !important;
            }
            .profile-img-large {
                width: 140px !important;
                height: 140px !important;
                margin: 0 auto !important;
            }
            .profile-info {
                align-items: center !important;
                width: 100% !important;
                min-width: 0 !important;
                box-sizing: border-box !important;
            }
            .profile-name {
                word-wrap: break-word !important;
                overflow-wrap: break-word !important;
                max-width: 100% !important;
            }
            .profile-info > div[style*="margin-top"] {
                justify-content: center !important;
                align-items: center !important;
                flex-wrap: wrap !important;
                gap: 16px !important;
                margin-top: 16px !important;
                width: 100% !important;
            }
            
            .content-section {
                padding: 0 16px 24px !important;
                overflow-x: hidden !important;
            }

            .content-section table, 
            .content-section tbody, 
            .content-section tr, 
            .content-section td {
                display: block !important;
                width: 100% !important;
                box-sizing: border-box !important;
            }
            .content-section tr[style*="uppercase"] { 
                display: none !important; 
            }
            .content-section tr.playlist-row {
                display: flex !important;
                align-items: center !important;
                padding: 10px 0 !important;
                position: relative !important;
                gap: 12px !important;
                width: 100% !important;
            }
            .content-section tr.playlist-row td {
                padding: 0 !important;
                border: none !important;
            }
            .content-section tr.playlist-row td:nth-child(1) { width: 24px !important; text-align: left !important; flex-shrink: 0 !important; }
            .content-section tr.playlist-row td:nth-child(2) { flex: 1 !important; min-width: 0 !important; overflow: hidden !important; }
            .content-section tr.playlist-row td:nth-child(3) { display: none !important; }
            .content-section tr.playlist-row td:nth-child(4) { width: auto !important; flex-shrink: 0 !important; }
            
            .content-section tr.playlist-row td:nth-child(2) div[style*="gap:12px"] {
                gap: 12px !important;
            }
            .content-section tr.playlist-row td:nth-child(2) div[style*="flex-direction:column"] {
                flex: 1 !important;
                min-width: 0 !important;
            }
            .content-section tr.playlist-row td:nth-child(2) div[style*="flex-direction:column"] span {
                white-space: nowrap !important;
                overflow: hidden !important;
                text-overflow: ellipsis !important;
                max-width: 100% !important;
                display: block !important;
            }
            
            .bottom-player {
                padding: 8px 16px !important;
                display: flex !important;
                align-items: center !important;
                justify-content: space-between !important;
                height: auto !important;
                min-height: 64px !important;
                width: 100% !important;
                box-sizing: border-box !important;
            }
            .player-left { 
                width: auto !important; 
                flex: 1 !important; 
                gap: 12px !important; 
                min-width: 0 !important; 
            }
            .player-left img { 
                width: 44px !important; 
                height: 44px !important; 
                flex-shrink: 0 !important;
            }
            .pl-info { 
                min-width: 0 !important; 
                flex: 1 !important; 
            }
            .pl-title, .pl-artist { 
                white-space: nowrap !important; 
                overflow: hidden !important; 
                text-overflow: ellipsis !important; 
                display: block !important; 
                max-width: 100% !important;
            }
            .player-center { 
                width: auto !important; 
                flex: none !important; 
                flex-direction: row !important; 
                align-items: center !important; 
            }
            .player-controls { 
                gap: 16px !important; 
            }
            .playback-bar { 
                display: none !important; 
            }
            .player-right { display: none !important; }
            
            #addSongsResultsInline > div {
                flex-direction: column !important;
                align-items: stretch !important;
                gap: 12px !important;
                padding: 12px !important;
            }
            #addSongsResultsInline > div > div:last-child {
                text-align: right !important;
                margin-top: 8px !important;
            }
            #addSongsResultsInline > div > div:first-child > div:last-child {
                min-width: 0 !important;
                flex: 1 !important;
            }
            #addSongsResultsInline > div > div:first-child > div:last-child div {
                white-space: nowrap !important;
                overflow: hidden !important;
                text-overflow: ellipsis !important;
                max-width: 100% !important;
            }
            
            /* Responsive modal */
            .modal-content {
                width: 90% !important;
                padding: 24px !important;
                margin: 20px auto !important;
                box-sizing: border-box !important;
            }
        }
        
        @media (max-width: 576px) {
            .profile-name { font-size: 26px !important; margin-bottom: 8px !important; }
            .profile-img-large { width: 120px !important; height: 120px !important; }
            
            #inlineAddSongsSection h2 { font-size: 18px !important; }
            
            .profile-info > div[style*="margin-top"] button {
                padding: 8px 16px !important;
                font-size: 13px !important;
            }
            .profile-info > div[style*="margin-top"] button#playAllBtn {
                width: 48px !important;
                height: 48px !important;
            }
            
            .user-menu span {
                max-width: 90px !important;
                overflow: hidden !important;
                text-overflow: ellipsis !important;
            }
            
            #prevBtn, #nextBtn { display: none !important; }
        }
    </style>
</head>
<body>

<div class="app-container">

    <!-- LEFT SIDEBAR -->
    <aside class="sidebar">
        <div style="display:flex; justify-content:space-between; align-items:center;">
            <a href="../index.php" class="logo"><img src="../assets/images/sound-logo-white.svg" alt="SOUND Logo"></a>
            <a href="../music.php" style="color:var(--text-base); transition:0.2s; padding-right:24px; cursor:pointer;" onmouseover="this.style.color='#fff'" onmouseout="this.style.color='var(--text-base)'" title="Back to Music">
                <i data-lucide="home" style="width: 24px; height: 24px;"></i>
            </a>
        </div>

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
                <a href="dashboard.php#likedSongsSection" class="playlist-item">
                    <div class="playlist-img" style="background: linear-gradient(135deg, #450af5, #c4efd9);">
                        <i data-lucide="heart" style="width: 20px; height: 20px;"></i>
                    </div>
                    <div class="playlist-info">
                        <span class="playlist-title">Liked Songs</span>
                        <span class="playlist-desc">Playlist • <?php echo $liked_count; ?> songs</span>
                    </div>
                </a>

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
                    <span style="font-weight:700; font-size:14px; margin-right:8px; white-space:nowrap; display:inline-block; vertical-align:middle; line-height:1;"><?php echo htmlspecialchars($user_data['name']); ?></span>
                    <i data-lucide="chevron-down" style="width: 16px; height: 16px;"></i>
                </div>
            </div>
        </div>
        
        <div class="profile-header">
            <div style="position:relative; cursor:pointer;" onclick="document.getElementById('playlistCoverModal').style.display='flex'" title="Update Playlist Cover">
                <?php if ($active_playlist['cover_image']): ?>
                    <img src="../uploads/playlists/<?php echo htmlspecialchars($active_playlist['cover_image']); ?>" class="profile-img-large" style="border-radius:4px;" alt="Cover">
                <?php else: ?>
                    <div class="profile-img-large" style="background:#282828; display:flex; align-items:center; justify-content:center; border-radius:4px;">
                        <i data-lucide="music" style="width: 24px; height: 24px;"></i>
                    </div>
                <?php endif; ?>
                <div style="position:absolute; bottom:8px; right:8px; background:rgba(0,0,0,0.6); padding:6px; border-radius:50%; display:flex;">
                    <i data-lucide="pencil" style="width: 16px; height: 16px;"></i>
                </div>
            </div>
            <div class="profile-info">
                <div class="profile-label">Playlist</div>
                <h1 class="profile-name"><?php echo htmlspecialchars($active_playlist['title']); ?></h1>
                <div class="profile-stats">
                    <?php echo htmlspecialchars($user_data['name']); ?> • <?php echo $items_count; ?> songs
                </div>
                <div style="margin-top: 24px; display:flex; gap:16px; align-items:center;">
                    <?php if ($items_count > 0): ?>
                    <button id="playAllBtn" style="background:linear-gradient(135deg,#8B5CF6,#EC4899); color:#fff; border:none; width:56px; height:56px; border-radius:50%; display:flex; align-items:center; justify-content:center; cursor:pointer; box-shadow:0 4px 15px rgba(236, 72, 153, 0.4); transition:all 0.2s ease;" onmouseover="this.style.transform='scale(1.08)'; this.style.boxShadow='0 6px 20px rgba(236, 72, 153, 0.6)';" onmouseout="this.style.transform='scale(1)'; this.style.boxShadow='0 4px 15px rgba(236, 72, 153, 0.4)';">
                        <svg id="playAllIcon" viewBox="0 0 24 24" style="width:24px;height:24px;fill:#ffffff;margin-left:4px;"><path d="M8 5v14l11-7z"/></svg>
                        <svg id="pauseAllIcon" viewBox="0 0 24 24" style="width:24px;height:24px;fill:#ffffff;display:none;"><path d="M6 19h4V5H6v14zm8-14v14h4V5h-4z"/></svg>
                    </button>
                    <button style="background:transparent; border:none; color:var(--text-base); cursor:pointer; padding:8px; transition:0.2s;" onmouseover="this.style.color='#fff'" onmouseout="this.style.color='var(--text-base)'">
                        <i data-lucide="shuffle" style="width: 20px; height: 20px;"></i>
                    </button>
                    <?php endif; ?>
                    <button id="openAddSongsBtn" style="background:rgba(255,255,255,0.1); color:#fff; border:none; padding:8px 20px; border-radius:50px; cursor:pointer; font-weight:600; font-size:14px; display:flex; align-items:center; gap:6px; transition:all 0.2s ease; backdrop-filter:blur(10px);" onmouseover="this.style.background='rgba(255,255,255,0.2)';" onmouseout="this.style.background='rgba(255,255,255,0.1)';">
                        <svg viewBox="0 0 24 24" style="width:18px;height:18px;stroke:#fff;stroke-width:2;stroke-linecap:round;stroke-linejoin:round;fill:none;"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                        Add
                    </button>
                    <form method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this playlist?');">
                        <input type="hidden" name="action" value="delete_playlist">
                        <button type="submit" style="background:transparent; border:none; color:var(--text-base); cursor:pointer; font-weight:bold; font-size: 20px; letter-spacing: 2px; transition:0.2s;" title="Delete Playlist" onmouseover="this.style.color='#fff'" onmouseout="this.style.color='var(--text-base)'"><i data-lucide="more-vertical" style="width: 20px; height: 20px;"></i></button>
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
                    <?php $idx = 1; while ($ls = mysqli_fetch_assoc($items_result)): ?>
                        <tr class="playlist-row track-item" data-id="<?php echo $ls['id']; ?>" data-src="<?php echo htmlspecialchars(!empty($ls['music_file']) ? $ls['music_file'] : ('audius:' . ($ls['source_track_id'] ?? ''))); ?>" data-title="<?php echo htmlspecialchars($ls['title']); ?>" data-artist="<?php echo htmlspecialchars($ls['artist_name'] ?? 'Unknown'); ?>" data-album="<?php echo htmlspecialchars($ls['album_name'] ?? 'Unknown'); ?>" data-img="<?php echo htmlspecialchars($ls['image'] ?? ''); ?>" style="border-bottom:1px solid rgba(255,255,255,0.05); cursor:pointer;">
                            <td style="padding:12px; color:var(--text-base);"><?php echo $idx++; ?></td>
                            <td style="padding:12px;">
                                <div style="display:flex; align-items:center; gap:12px;">
                                    <?php if ($ls['image']): ?>
                                        <?php 
                                            $img_url = $ls['image']; 
                                            if (!preg_match('/^https?:\/\//i', $img_url)) {
                                                $img_url = "../uploads/music/images/" . htmlspecialchars($img_url);
                                            } else {
                                                $img_url = htmlspecialchars($img_url);
                                            }
                                        ?>
                                        <img src="<?php echo $img_url; ?>" style="width:40px; height:40px; object-fit:cover; border-radius:4px;">
                                    <?php else: ?>
                                        <div style="width:40px; height:40px; background:#282828; border-radius:4px; display:flex; align-items:center; justify-content:center;">
                                            <i data-lucide="music" style="width: 24px; height: 24px;"></i>
                                        </div>
                                    <?php endif; ?>
                                    <div style="display:flex; flex-direction:column;">
                                        <span style="font-weight:600; color:#fff;"><?php echo htmlspecialchars($ls['title']); ?></span>
                                        <span style="font-size:13px; color:var(--text-base);"><?php echo htmlspecialchars($ls['artist_name'] ?? 'Unknown'); ?></span>
                                    </div>
                                </div>
                            </td>
                            <td style="padding:12px; color:var(--text-base); font-size:14px;"><?php echo htmlspecialchars($ls['album_name'] ?? 'Single'); ?></td>
                            <td style="padding:12px;">
                                <button type="button" class="remove-song-btn" onclick="event.stopPropagation(); removeSongFromPlaylist(this, <?php echo $pl_id; ?>, <?php echo $ls['id']; ?>)" style="background:none; border:none; color:var(--text-base); cursor:pointer; padding:8px; transition:color 0.2s;" title="Remove from playlist" onmouseover="this.style.color='#ef4444'" onmouseout="this.style.color='var(--text-base)'">
                                    <i data-lucide="trash-2" style="width: 20px; height: 20px;"></i>
                                </button>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </table>
            <?php else: ?>
                <div style="text-align:center; padding:60px 20px; color:#b3b3b3;">
                    <i data-lucide="info" style="width: 48px; height: 48px;"></i>
                    <h3 style="color:#fff; font-size:24px; margin-bottom:8px;">It's a bit empty here...</h3>
                    <p style="font-size:14px; margin-bottom:24px;">Let's find some songs for your playlist</p>
                    <button id="findSongsEmptyStateBtn" style="border:none; cursor:pointer; display:inline-block; background:#fff; color:#000; padding:12px 32px; border-radius:50px; font-weight:700; font-size:16px; transition:0.2s;" onmouseover="this.style.transform='scale(1.05)'" onmouseout="this.style.transform='scale(1)'">Find songs</button>
                </div>
            <?php endif; ?>

            <!-- INLINE ADD SONGS SECTION -->
            <div id="inlineAddSongsSection" style="display:none; margin-top: 40px; padding-top: 24px; border-top: 1px solid rgba(255,255,255,0.1);">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 24px;">
                    <h2 style="margin:0; color:#fff; font-size: 20px;">Add Songs to Playlist</h2>
                    <span id="closeAddSongsInline" style="cursor:pointer; font-size:24px; color:#b3b3b3; transition: color 0.2s;" onmouseover="this.style.color='#fff'" onmouseout="this.style.color='#b3b3b3'">&times;</span>
                </div>
                <div style="margin-bottom: 24px; position:relative;">
                    <i data-lucide="clock" style="width: 16px; height: 16px;"></i>
                    <input type="text" id="addSongsSearchInline" placeholder="Search songs..." style="width:100%; padding:12px 12px 12px 40px; border-radius:4px; border:none; background:rgba(255,255,255,0.1); color:#fff; outline:none; font-size:14px; box-sizing:border-box;">
                </div>
                <div id="addSongsResultsInline" style="display:flex; flex-direction:column; gap:8px;">
                    <!-- Results go here -->
                </div>
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
            <div id="npActive" style="display:none; flex-direction:column; padding: 0 8px;">
                <img id="npImage" src="" class="np-img" alt="Album Art" style="margin-bottom: 24px; border-radius: 8px; box-shadow: 0 8px 24px rgba(0,0,0,0.5);">
                
                <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                    <div style="display: flex; flex-direction: column; overflow: hidden; padding-right: 16px;">
                        <a href="#" id="npTitleLink" style="text-decoration:none; color: #fff;">
                            <div id="npTitle" style="font-size: 26px; font-weight: 800; letter-spacing: -0.5px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; margin-bottom: 6px; transition: color 0.2s;" onmouseover="this.style.textDecoration='underline'" onmouseout="this.style.textDecoration='none'">Song Title</div>
                        </a>
                        <div id="npArtist" style="color: var(--text-base); font-size: 16px; font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; transition: color 0.2s; cursor: pointer;" onmouseover="this.style.color='#fff'" onmouseout="this.style.color='var(--text-base)'">Artist Name</div>
                        <div id="npAlbum" style="color: var(--text-base); font-size: 14px; font-weight: 500; opacity: 0.7; margin-top: 6px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">Album Name</div>
                    </div>
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
            <svg id="bpLike" viewBox="0 0 24 24" style="width:16px;fill:var(--text-base);visibility:hidden;"><path d="M5.21 3.08c3.16-2.72 7.82-1.35 9.79 3.02 1.97-4.37 6.63-5.74 9.79-3.02 3.56 3.07 3.07 8.78-.97 12.33L15 22l-8.82-6.59c-4.04-3.55-4.53-9.26-.97-12.33z"/></svg>
        </div>
        
        <div class="player-center">
            <div class="player-controls">
                <button class="ctrl-btn" title="Enable shuffle"><i data-lucide="shuffle" style="width: 20px; height: 20px;"></i></button>
                <button class="ctrl-btn" id="prevBtn" title="Previous"><i data-lucide="skip-back" style="width: 20px; height: 20px;"></i></button>
                <button class="ctrl-btn ctrl-play" id="playBtn" title="Play">
                    <i data-lucide="play" id="playIcon" style="width: 24px; height: 24px; fill: currentColor;"></i>
                    <i data-lucide="pause" id="pauseIcon" style="width: 24px; height: 24px; fill: currentColor; display:none;"></i>
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
        <span class="modal-close" onclick="document.getElementById('profileModal').style.display='none'">&times;</span>
        <h2 style="margin-bottom: 20px; color: #fff;">Edit Profile Image</h2>
        <?php if ($message): ?>
            <div style="background:rgba(16,185,129,0.1);color:#10b981;padding:12px;margin-bottom:20px;border-radius:6px;"><?php echo $message; ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div style="background:rgba(239,68,68,0.1);color:#ef4444;padding:12px;margin-bottom:20px;border-radius:6px;"><?php echo $error; ?></div>
        <?php endif; ?>
        <form method="POST" enctype="multipart/form-data" style="margin-bottom: 16px;">
            <input type="file" name="profile_image" accept="image/*" required class="custom-file-input" style="margin-bottom: 12px; width:100%; color:var(--text-base); box-sizing: border-box;">
            <button type="submit" style="background:var(--accent); color:#000; border:none; padding:8px 16px; border-radius:50px; cursor:pointer; font-weight:bold;">Upload New Image</button>
        </form>
        <?php if ($user_data['profile_image']): ?>
        <form method="POST">
            <input type="hidden" name="action" value="delete">
            <button type="submit" onclick="return confirm('Delete profile image?');" style="background:transparent; color:#ef4444; border:1px solid #ef4444; padding:8px 16px; border-radius:50px; cursor:pointer; font-weight:bold;">Remove Image</button>
        </form>
        <?php endif; ?>
        <div style="margin-top:20px; border-top:1px solid #333; padding-top:20px;">
            <a href="../logout.php" style="color:var(--text-base); font-weight:bold; display:block; text-align:center;">Logout from SOUND</a>
        </div>
    </div>
</div>

<!-- Playlist Cover Modal -->
<div id="playlistCoverModal" class="modal">
    <div class="modal-content">
        <span class="modal-close" onclick="document.getElementById('playlistCoverModal').style.display='none'">&times;</span>
        <h2 style="margin-bottom: 20px; color: #fff;">Edit Playlist Cover</h2>
        <form method="POST" enctype="multipart/form-data" style="margin-bottom: 16px;">
            <input type="file" name="playlist_cover" accept="image/*" required class="custom-file-input" style="margin-bottom: 12px; width:100%; color:var(--text-base); box-sizing: border-box;">
            <button type="submit" style="background:var(--accent); color:#000; border:none; padding:8px 16px; border-radius:50px; cursor:pointer; font-weight:bold;">Upload New Cover</button>
        </form>
        <?php if ($active_playlist['cover_image']): ?>
        <form method="POST">
            <input type="hidden" name="action" value="delete_cover">
            <button type="submit" onclick="return confirm('Delete playlist cover?');" style="background:transparent; color:#ef4444; border:1px solid #ef4444; padding:8px 16px; border-radius:50px; cursor:pointer; font-weight:bold;">Remove Cover</button>
        </form>
        <?php endif; ?>
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
                if (window.SOUNDAudius) window.SOUNDAudius.play(trackInfo.src.substring(7), document.getElementById('audioElement')).catch(() => {});
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
        if(bpTitle) bpTitle.textContent = trackInfo.title;
        else if(bpTitleLink) bpTitleLink.textContent = trackInfo.title;
        
        if(npArtist) npArtist.textContent = trackInfo.artist;
        if(bpArtist) bpArtist.textContent = trackInfo.artist;
        if(npAlbum) npAlbum.textContent = trackInfo.album || 'Unknown Album';
        
        if (trackInfo.id) {
            npTitleLink.href = '../play_music.php?id=' + trackInfo.id;
            bpTitleLink.href = '../play_music.php?id=' + trackInfo.id;
        }
        
        audio.play().catch(e => console.log("Autoplay blocked"));
    }

    // Attach click events to tracks to act as a playlist (using event delegation for dynamic items)
    document.addEventListener('click', (e) => {
        const tr = e.target.closest('.track-item');
        // Ignore if clicking the remove button
        if (tr && !e.target.closest('button') && !e.target.closest('a')) {
            e.preventDefault();
            // Build playlist dynamically from all currently visible track-items
            const currentTrackItems = document.querySelectorAll('.track-item');
            currentPlaylist = Array.from(currentTrackItems).map(el => ({
                id: el.getAttribute('data-id'),
                src: el.getAttribute('data-src'),
                title: el.getAttribute('data-title'),
                artist: el.getAttribute('data-artist'),
                album: el.getAttribute('data-album'),
                img: el.getAttribute('data-img')
            }));
            currentIndex = Array.from(currentTrackItems).indexOf(tr);
            if (currentIndex !== -1) {
                loadTrack(currentPlaylist[currentIndex]);
            }
        }
    });

    const playAllBtn = document.getElementById('playAllBtn');
    if (playAllBtn) {
        playAllBtn.addEventListener('click', () => {
            const currentTrackItems = document.querySelectorAll('.track-item');
            if (currentTrackItems.length > 0) {
                currentPlaylist = Array.from(currentTrackItems).map(el => ({
                    id: el.getAttribute('data-id'),
                    src: el.getAttribute('data-src'),
                    title: el.getAttribute('data-title'),
                    artist: el.getAttribute('data-artist'),
                    album: el.getAttribute('data-album'),
                    img: el.getAttribute('data-img')
                }));
                currentIndex = 0;
                loadTrack(currentPlaylist[currentIndex]);
            }
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

    const playAllIcon = document.getElementById('playAllIcon');
    const pauseAllIcon = document.getElementById('pauseAllIcon');

    audio.addEventListener('play', () => {
        if(playIcon) playIcon.style.display = 'none';
        if(pauseIcon) pauseIcon.style.display = 'inline-block';
        if(playAllIcon) playAllIcon.style.display = 'none';
        if(pauseAllIcon) pauseAllIcon.style.display = 'inline-block';
    });
    
    audio.addEventListener('pause', () => {
        if(playIcon) playIcon.style.display = 'inline-block';
        if(pauseIcon) pauseIcon.style.display = 'none';
        if(playAllIcon) playAllIcon.style.display = 'inline-block';
        if(pauseAllIcon) pauseAllIcon.style.display = 'none';
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
    
    // ----- INLINE ADD SONGS LOGIC -----
    const currentPlaylistId = <?php echo intval($pl_id); ?>;
    const openAddSongsBtn = document.getElementById('openAddSongsBtn');
    const inlineAddSongsSection = document.getElementById('inlineAddSongsSection');
    const closeAddSongsInline = document.getElementById('closeAddSongsInline');
    const addSongsSearchInline = document.getElementById('addSongsSearchInline');
    const addSongsResultsInline = document.getElementById('addSongsResultsInline');
    let searchTimeout = null;
    let currentPlaylistCount = <?php echo $items_count; ?>;
    let currentSongsList = [];

    function toggleAddSongs() {
        if (inlineAddSongsSection.style.display === 'none') {
            inlineAddSongsSection.style.display = 'block';
            fetchSongs('');
            setTimeout(() => inlineAddSongsSection.scrollIntoView({behavior: 'smooth', block: 'start'}), 100);
        } else {
            inlineAddSongsSection.style.display = 'none';
        }
    }

    if (openAddSongsBtn) {
        openAddSongsBtn.addEventListener('click', toggleAddSongs);
    }
    
    const findSongsEmptyStateBtn = document.getElementById('findSongsEmptyStateBtn');
    if (findSongsEmptyStateBtn) {
        findSongsEmptyStateBtn.addEventListener('click', toggleAddSongs);
    }

    if (closeAddSongsInline) {
        closeAddSongsInline.addEventListener('click', () => {
            inlineAddSongsSection.style.display = 'none';
        });
    }

    if (addSongsSearchInline) {
        addSongsSearchInline.addEventListener('input', (e) => {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                fetchSongs(e.target.value);
            }, 300);
        });
    }

    function fetchSongs(query) {
        addSongsResultsInline.innerHTML = '<div style="text-align:center; padding:20px; color:#b3b3b3;">Loading...</div>';
        fetch(`ajax_playlist_add.php?action=search&playlist_id=${currentPlaylistId}&q=${encodeURIComponent(query)}`)
            .then(res => res.json())
            .then(data => {
                if (data.songs) {
                    currentSongsList = data.songs;
                    renderSongs(currentSongsList);
                } else {
                    addSongsResultsInline.innerHTML = '<div style="text-align:center; padding:20px; color:#ef4444;">Error loading songs</div>';
                }
            })
            .catch(err => {
                addSongsResultsInline.innerHTML = '<div style="text-align:center; padding:20px; color:#ef4444;">Error loading songs</div>';
            });
    }

    function renderSongs(songs) {
        if (songs.length === 0) {
            addSongsResultsInline.innerHTML = '<div style="text-align:center; padding:20px; color:#b3b3b3;">No songs found.</div>';
            return;
        }

        let html = '';
        songs.forEach(s => {
            const isAdded = parseInt(s.is_added) > 0;
            const btnHtml = isAdded 
                ? `<button disabled style="background:transparent; color:#1db954; border:none; font-weight:bold; cursor:default;"><i data-lucide="check" style="width:16px;height:16px;display:inline-block;vertical-align:text-bottom;margin-right:4px;"></i> Added</button>`
                : `<button onclick="addSongToPlaylist(${s.id}, this)" style="background:transparent; color:#fff; border:1px solid #666; border-radius:50px; padding:4px 16px; cursor:pointer; font-weight:bold; transition:0.2s;" onmouseover="this.style.borderColor='#fff'" onmouseout="this.style.borderColor='#666'"><i data-lucide="plus" style="width: 16px; height: 16px; display:inline-block; vertical-align:text-bottom; margin-right:4px;"></i> Add</button>`;

            html += `
                <div style="display:flex; align-items:center; justify-content:space-between; padding:8px; border-radius:8px; transition:0.2s;" onmouseover="this.style.background='rgba(255,255,255,0.05)'" onmouseout="this.style.background='transparent'">
                    <div style="display:flex; align-items:center; gap:12px;">
                        <img src="${s.image_url}" style="width:40px; height:40px; border-radius:4px; object-fit:cover;">
                        <div>
                            <div style="color:#fff; font-weight:500; font-size:14px;">${s.title.replace(/"/g, '&quot;')}</div>
                            <div style="color:#b3b3b3; font-size:12px;">${(s.artist_name || 'Unknown Artist').replace(/"/g, '&quot;')}</div>
                            <div style="color:#b3b3b3; font-size:11px;">${(s.album_name || 'Single').replace(/"/g, '&quot;')}</div>
                        </div>
                    </div>
                    <div>
                        ${btnHtml}
                    </div>
                </div>
            `;
        });
        addSongsResultsInline.innerHTML = html;
    }

    window.addSongToPlaylist = function(id, btn) {
        btn.disabled = true;
        btn.innerHTML = 'Adding...';
        
        const s = currentSongsList.find(x => x.id == id);
        if(!s) return;
        
        const formData = new FormData();
        formData.append('action', 'add');
        formData.append('playlist_id', currentPlaylistId);
        formData.append('music_id', s.id);

        fetch('ajax_playlist_add.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                btn.outerHTML = `<button disabled style="background:transparent; color:#1db954; border:none; font-weight:bold; cursor:default;"><i data-lucide="check" style="width:16px;height:16px;display:inline-block;vertical-align:text-bottom;margin-right:4px;"></i> Added</button>`;
                appendSongToPlaylist(s);
            } else {
                btn.disabled = false;
                btn.innerHTML = '<i data-lucide="plus" style="width: 16px; height: 16px; display:inline-block; vertical-align:text-bottom; margin-right:4px;"></i> Add';
                alert('Failed to add song.');
            }
        })
        .catch(err => {
            btn.disabled = false;
            btn.innerHTML = '<i data-lucide="plus" style="width: 16px; height: 16px; display:inline-block; vertical-align:text-bottom; margin-right:4px;"></i> Add';
            alert('Error adding song.');
        });
    };

    function appendSongToPlaylist(s) {
        currentPlaylistCount++;
        
        const profileStats = document.querySelector('.profile-stats');
        if (profileStats) {
            const userName = "<?php echo addslashes($user_data['name']); ?>";
            profileStats.innerHTML = `${userName} • ${currentPlaylistCount} song${currentPlaylistCount !== 1 ? 's' : ''}`;
        }

        const tableBody = document.querySelector('.content-section table tbody') || document.querySelector('.content-section table');
        
        if (!tableBody) {
            // Empty state edge case, refresh cleanly
            window.location.reload();
            return;
        }

        const tr = document.createElement('tr');
        tr.className = 'playlist-row track-item';
        tr.setAttribute('data-id', s.id);
        tr.setAttribute('data-src', s.music_file || (s.source_track_id ? 'audius:' + s.source_track_id : ''));
        tr.setAttribute('data-title', s.title);
        tr.setAttribute('data-artist', s.artist_name || 'Unknown');
        tr.setAttribute('data-album', s.album_name || 'Single');
        tr.setAttribute('data-img', s.image || '');
        tr.style = "border-bottom:1px solid rgba(255,255,255,0.05); cursor:pointer;";

        tr.innerHTML = `
            <td style="padding:12px; color:var(--text-base);">${currentPlaylistCount}</td>
            <td style="padding:12px;">
                <div style="display:flex; align-items:center; gap:12px;">
                    <img src="${s.image_url}" style="width:40px; height:40px; object-fit:cover; border-radius:4px;">
                    <div style="display:flex; flex-direction:column;">
                        <span style="font-weight:600; color:#fff;">${s.title}</span>
                        <span style="font-size:13px; color:var(--text-base);">${s.artist_name || 'Unknown'}</span>
                    </div>
                </div>
            </td>
            <td style="padding:12px; color:var(--text-base); font-size:14px;">${s.album_name || 'Single'}</td>
            <td style="padding:12px;">
                <button type="button" onclick="event.stopPropagation(); removeSongFromPlaylist(this, <?php echo $pl_id; ?>, ${s.id})" style="background:none; border:none; color:var(--text-base); cursor:pointer; padding:8px; transition:color 0.2s;" title="Remove from playlist" onmouseover="this.style.color='#ef4444'" onmouseout="this.style.color='var(--text-base)'">
                    <i data-lucide="trash-2" style="width: 20px; height: 20px;"></i>
                </button>
            </td>
        `;
        
        tr.addEventListener('click', () => {
            const index = Array.from(document.querySelectorAll('.track-item')).indexOf(tr);
            if(index !== -1) {
                currentPlaylist = Array.from(document.querySelectorAll('.track-item')).map(el => ({
                    id: el.getAttribute('data-id'),
                    src: el.getAttribute('data-src'),
                    title: el.getAttribute('data-title'),
                    artist: el.getAttribute('data-artist'),
                    album: el.getAttribute('data-album'),
                    img: el.getAttribute('data-img')
                }));
                currentIndex = index;
                loadTrack(currentPlaylist[currentIndex]);
            }
        });
        
        tableBody.appendChild(tr);
        
        currentPlaylist = Array.from(document.querySelectorAll('.track-item')).map(el => ({
            id: el.getAttribute('data-id'),
            src: el.getAttribute('data-src'),
            title: el.getAttribute('data-title'),
            artist: el.getAttribute('data-artist'),
            album: el.getAttribute('data-album'),
            img: el.getAttribute('data-img')
        }));
    }

    async function removeSongFromPlaylist(btn, playlistId, musicId) {
        if (!confirm('Are you sure you want to remove this song from the playlist?')) return;
        
        const formData = new FormData();
        formData.append('action', 'remove_song');
        formData.append('playlist_id', playlistId);
        formData.append('music_id', musicId);

        try {
            const response = await fetch('ajax_playlist_add.php', {
                method: 'POST',
                body: formData
            });
            const data = await response.json();
            
            if (data.success) {
                const row = btn.closest('tr');
                row.style.transition = 'opacity 0.3s ease';
                row.style.opacity = '0';
                setTimeout(() => {
                    row.remove();
                    // Re-calculate numbering
                    const rows = document.querySelectorAll('.playlist-row');
                    rows.forEach((r, idx) => {
                        r.querySelector('td:first-child').textContent = idx + 1;
                    });
                    
                    // Update header count
                    const profileStats = document.querySelector('.profile-stats');
                    if (profileStats && typeof currentPlaylistCount !== 'undefined') {
                        currentPlaylistCount = Math.max(0, currentPlaylistCount - 1);
                        const userName = "<?php echo addslashes($user_data['name']); ?>";
                        profileStats.innerHTML = `${userName} • ${currentPlaylistCount} song${currentPlaylistCount !== 1 ? 's' : ''}`;
                    } else if (profileStats) {
                        // Fallback parsing if currentPlaylistCount wasn't initialized in older script
                        let htmlStr = profileStats.innerHTML;
                        let match = htmlStr.match(/(\d+)\s+songs?/i);
                        if(match && match[1]) {
                           let cnt = parseInt(match[1]) - 1;
                           cnt = Math.max(0, cnt);
                           profileStats.innerHTML = `<?php echo addslashes($user_data['name']); ?> • ${cnt} song${cnt !== 1 ? 's' : ''}`;
                        }
                    }
                    
                    // Update currentPlaylist JS array
                    currentPlaylist = Array.from(document.querySelectorAll('.track-item')).map(el => ({
                        id: el.getAttribute('data-id'),
                        src: el.getAttribute('data-src'),
                        title: el.getAttribute('data-title'),
                        artist: el.getAttribute('data-artist'),
                        album: el.getAttribute('data-album'),
                        img: el.getAttribute('data-img')
                    }));
                }, 300);
            } else {
                alert(data.message || 'Failed to remove song.');
            }
        } catch (error) {
            alert('Network error occurred.');
        }
    }
</script>

<button id="mobileSidebarToggleBtn" class="mobile-sidebar-toggle-btn" aria-label="Toggle Sidebar">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:24px; height:24px; pointer-events: none;">
        <line x1="3" y1="12" x2="21" y2="12"></line>
        <line x1="3" y1="6" x2="21" y2="6"></line>
        <line x1="3" y1="18" x2="21" y2="18"></line>
    </svg>
</button>
<script>
    document.getElementById('mobileSidebarToggleBtn')?.addEventListener('click', function(e) {
        e.preventDefault();
        const sidebar = document.querySelector('.sidebar');
        if(sidebar) {
            sidebar.classList.toggle('open');
        }
    });
</script>
</body>
</html>
