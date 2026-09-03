<?php
require_once "../includes/auth.php";
require_once "../config/db.php";
requireUser();

header('Content-Type: application/json');

$user_id = $_SESSION['user_id'];
$action = $_GET['action'] ?? ($_POST['action'] ?? '');

if ($action === 'search') {
    $pl_id = intval($_GET['playlist_id'] ?? 0);
    $q = trim($_GET['q'] ?? '');

    // Verify playlist ownership
    $check = mysqli_query($conn, "SELECT id FROM playlists WHERE id = $pl_id AND user_id = $user_id");
    if (mysqli_num_rows($check) == 0) {
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }

    $search_cond = "";
    if ($q !== '') {
        $q_esc = mysqli_real_escape_string($conn, "%{$q}%");
        $search_cond = " WHERE m.title LIKE '$q_esc' OR a.artist_name LIKE '$q_esc' OR al.album_name LIKE '$q_esc' ";
    }

    $query = "
        SELECT m.id, m.title, m.image, m.music_file, m.source_track_id, a.artist_name, al.album_name,
               (SELECT COUNT(*) FROM playlist_items pi WHERE pi.playlist_id = $pl_id AND pi.music_id = m.id) as is_added
        FROM music m
        LEFT JOIN artists a ON m.artist_id = a.id
        LEFT JOIN albums al ON m.album_id = al.id
        $search_cond
        ORDER BY m.id DESC
        LIMIT 20
    ";

    $res = mysqli_query($conn, $query);
    $songs = [];
    while ($row = mysqli_fetch_assoc($res)) {
        $img_url = $row['image'] ?? '';
        if ($img_url) {
            if (!preg_match('/^https?:\/\//i', $img_url)) {
                $img_url = "../uploads/music/images/" . htmlspecialchars($img_url);
            } else {
                $img_url = htmlspecialchars($img_url);
            }
        } else {
            $img_url = "../assets/img/default_cover.jpg";
        }
        $row['image_url'] = $img_url;
        
        $songs[] = $row;
    }

    echo json_encode(['songs' => $songs]);
    exit;

} elseif ($action === 'add') {
    $pl_id = intval($_POST['playlist_id'] ?? 0);
    $music_id = intval($_POST['music_id'] ?? 0);

    // Verify playlist ownership
    $check = mysqli_query($conn, "SELECT id FROM playlists WHERE id = $pl_id AND user_id = $user_id");
    if (mysqli_num_rows($check) == 0) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized or Playlist not found']);
        exit;
    }
    
    // Verify song exists
    $check_music = mysqli_query($conn, "SELECT id FROM music WHERE id = $music_id");
    if (mysqli_num_rows($check_music) == 0) {
        echo json_encode(['success' => false, 'message' => 'Song not found']);
        exit;
    }

    // Check if already added
    $check_item = mysqli_query($conn, "SELECT id FROM playlist_items WHERE playlist_id = $pl_id AND music_id = $music_id");
    if (mysqli_num_rows($check_item) == 0) {
        $stmt = mysqli_prepare($conn, "INSERT INTO playlist_items (playlist_id, music_id) VALUES (?, ?)");
        mysqli_stmt_bind_param($stmt, "ii", $pl_id, $music_id);
        if (mysqli_stmt_execute($stmt)) {
            echo json_encode(['success' => true, 'message' => 'Song added to playlist.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Database error while adding song.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Song is already in this playlist.']);
    }
    exit;

} elseif ($action === 'get_playlists') {
    $playlists_query = "SELECT id, title, (SELECT COUNT(*) FROM playlist_items WHERE playlist_id = playlists.id) as song_count FROM playlists WHERE user_id = $user_id ORDER BY created_at DESC";
    $res = mysqli_query($conn, $playlists_query);
    $playlists = [];
    while ($row = mysqli_fetch_assoc($res)) {
        $playlists[] = $row;
    }
    echo json_encode(['success' => true, 'playlists' => $playlists]);
    exit;

} elseif ($action === 'create_and_add') {
    $title = trim($_POST['title'] ?? '');
    $music_id = intval($_POST['music_id'] ?? 0);

    if (empty($title)) {
        echo json_encode(['success' => false, 'message' => 'Playlist name cannot be empty.']);
        exit;
    }
    
    // Verify song exists
    $check_music = mysqli_query($conn, "SELECT id FROM music WHERE id = $music_id");
    if (mysqli_num_rows($check_music) == 0) {
        echo json_encode(['success' => false, 'message' => 'Song not found']);
        exit;
    }

    $stmt = mysqli_prepare($conn, "INSERT INTO playlists (user_id, title, is_public) VALUES (?, ?, 0)");
    mysqli_stmt_bind_param($stmt, "is", $user_id, $title);
    
    if (mysqli_stmt_execute($stmt)) {
        $pl_id = mysqli_insert_id($conn);
        $stmt_item = mysqli_prepare($conn, "INSERT INTO playlist_items (playlist_id, music_id) VALUES (?, ?)");
        mysqli_stmt_bind_param($stmt_item, "ii", $pl_id, $music_id);
        
        if (mysqli_stmt_execute($stmt_item)) {
            echo json_encode(['success' => true, 'message' => 'Playlist created & song added.', 'playlist_id' => $pl_id, 'playlist_title' => $title]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Playlist created, but failed to add song.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to create playlist.']);
    }
    exit;
    
} elseif ($action === 'remove_song') {
    $pl_id = intval($_POST['playlist_id'] ?? 0);
    $music_id = intval($_POST['music_id'] ?? 0);

    // Verify playlist ownership
    $check = mysqli_query($conn, "SELECT id FROM playlists WHERE id = $pl_id AND user_id = $user_id");
    if (mysqli_num_rows($check) == 0) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }

    $stmt = mysqli_prepare($conn, "DELETE FROM playlist_items WHERE playlist_id = ? AND music_id = ?");
    mysqli_stmt_bind_param($stmt, "ii", $pl_id, $music_id);
    if (mysqli_stmt_execute($stmt)) {
        echo json_encode(['success' => true, 'message' => 'Song removed']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to remove song']);
    }
    exit;
}

echo json_encode(['error' => 'Invalid action']);
