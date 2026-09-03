<?php
require_once "../../includes/auth.php";
require_once "../../config/db.php";
require_once "../../services/ITunesProvider.php";

/** @var mysqli $conn */

requireAdmin();

$current_page = 'music';
$admin_base = '../';
$message = "";
$error = "";

// Handle Import Action
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] === 'import') {
    $track_id = trim($_POST['track_id'] ?? '');
    $title = trim($_POST['title'] ?? '');
    $artist_name = trim($_POST['artist_name'] ?? '');
    $album_name = trim($_POST['album_name'] ?? '');
    $genre_name = trim($_POST['genre_name'] ?? '');
    $year_val = substr(trim($_POST['release_date'] ?? ''), 0, 4);
    $image_url = trim($_POST['image_url'] ?? '');
    $preview_url = trim($_POST['preview_url'] ?? '');
    
    // Check if already imported
    $stmt = mysqli_prepare($conn, "SELECT id FROM music WHERE source_track_id = ?");
    mysqli_stmt_bind_param($stmt, "s", $track_id);
    mysqli_stmt_execute($stmt);
    if (mysqli_stmt_fetch($stmt)) {
        $error = "Track is already imported.";
    } else {
        mysqli_stmt_close($stmt);
        
        // Ensure Artist exists
        $artist_id = null;
        if (!empty($artist_name)) {
            $stmt = mysqli_prepare($conn, "SELECT id FROM artists WHERE artist_name = ?");
            mysqli_stmt_bind_param($stmt, "s", $artist_name);
            mysqli_stmt_execute($stmt);
            $res = mysqli_stmt_get_result($stmt);
            if ($row = mysqli_fetch_assoc($res)) {
                $artist_id = $row['id'];
            } else {
                $stmt = mysqli_prepare($conn, "INSERT INTO artists (artist_name) VALUES (?)");
                mysqli_stmt_bind_param($stmt, "s", $artist_name);
                mysqli_stmt_execute($stmt);
                $artist_id = mysqli_insert_id($conn);
            }
        }
        
        // Ensure Album exists
        $album_id = null;
        if (!empty($album_name)) {
            $stmt = mysqli_prepare($conn, "SELECT id FROM albums WHERE album_name = ? AND artist_id = ?");
            mysqli_stmt_bind_param($stmt, "si", $album_name, $artist_id);
            mysqli_stmt_execute($stmt);
            $res = mysqli_stmt_get_result($stmt);
            if ($row = mysqli_fetch_assoc($res)) {
                $album_id = $row['id'];
            } else {
                $stmt = mysqli_prepare($conn, "INSERT INTO albums (album_name, artist_id) VALUES (?, ?)");
                mysqli_stmt_bind_param($stmt, "si", $album_name, $artist_id);
                mysqli_stmt_execute($stmt);
                $album_id = mysqli_insert_id($conn);
            }
        }
        
        // Ensure Genre exists
        $genre_id = null;
        if (!empty($genre_name)) {
            $stmt = mysqli_prepare($conn, "SELECT id FROM genres WHERE genre_name = ?");
            mysqli_stmt_bind_param($stmt, "s", $genre_name);
            mysqli_stmt_execute($stmt);
            $res = mysqli_stmt_get_result($stmt);
            if ($row = mysqli_fetch_assoc($res)) {
                $genre_id = $row['id'];
            } else {
                $stmt = mysqli_prepare($conn, "INSERT INTO genres (genre_name) VALUES (?)");
                mysqli_stmt_bind_param($stmt, "s", $genre_name);
                mysqli_stmt_execute($stmt);
                $genre_id = mysqli_insert_id($conn);
            }
        }
        
        // Ensure Year exists
        $year_id = null;
        if (!empty($year_val)) {
            $stmt = mysqli_prepare($conn, "SELECT id FROM years WHERE year_value = ?");
            mysqli_stmt_bind_param($stmt, "s", $year_val);
            mysqli_stmt_execute($stmt);
            $res = mysqli_stmt_get_result($stmt);
            if ($row = mysqli_fetch_assoc($res)) {
                $year_id = $row['id'];
            } else {
                $stmt = mysqli_prepare($conn, "INSERT INTO years (year_value) VALUES (?)");
                mysqli_stmt_bind_param($stmt, "s", $year_val);
                mysqli_stmt_execute($stmt);
                $year_id = mysqli_insert_id($conn);
            }
        }
        
        // Insert into music
        $stmt = mysqli_prepare($conn, "INSERT INTO music (title, artist_id, album_id, year_id, genre_id, image, music_file, is_new, source_track_id) VALUES (?, ?, ?, ?, ?, ?, ?, 1, ?)");
        
        if ($stmt) {
            $music_file = $preview_url;
            mysqli_stmt_bind_param($stmt, "siiiisss", $title, $artist_id, $album_id, $year_id, $genre_id, $image_url, $music_file, $track_id);
            
            if (mysqli_stmt_execute($stmt)) {
                $message = "Track '{$title}' imported successfully!";
            } else {
                $error = "Error importing track: " . mysqli_error($conn);
            }
            mysqli_stmt_close($stmt);
        } else {
            $error = "Error preparing statement: " . mysqli_error($conn);
        }
    }
}

// Handle iTunes catalog search (metadata and preview URLs).
$api_results = [];
$search_query = "";
if (isset($_GET['q']) && !empty(trim($_GET['q']))) {
    $search_query = trim($_GET['q']);
    try { $api_results = (new ITunesProvider())->searchSongs($search_query); }
    catch (Throwable $e) { $error = 'Music search is temporarily unavailable.'; }
}

// Fetch already imported track IDs to avoid showing import button for them
$imported_ids = [];
$res = mysqli_query($conn, "SELECT source_track_id FROM music WHERE source_track_id IS NOT NULL");
while ($row = mysqli_fetch_assoc($res)) {
    $imported_ids[] = $row['source_track_id'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Import API Music - SOUND Admin</title>
    <link rel="stylesheet" href="../../assets/css/style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../../assets/css/admin.css?v=<?php echo time(); ?>">
    <style>
        .search-bar {
            display: flex;
            gap: 10px;
            margin-bottom: 30px;
        }
        .search-bar input {
            flex: 1;
            padding: 10px 15px;
            border-radius: 5px;
            border: 1px solid var(--card-border);
            background: var(--bg-card);
            color: var(--text-primary);
        }
        .search-bar button {
            padding: 10px 20px;
        }
        .api-results {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }
        .api-card {
            background: var(--bg-card);
            border: 1px solid var(--card-border);
            border-radius: 10px;
            padding: 15px;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        .api-card img {
            width: 100%;
            height: auto;
            border-radius: 8px;
            aspect-ratio: 1;
            object-fit: cover;
        }
        .api-card h3 {
            margin: 0;
            font-size: 1.1rem;
            color: var(--text-primary);
        }
        .api-card p {
            margin: 0;
            color: var(--text-secondary);
            font-size: 0.9rem;
        }
        .api-card audio {
            width: 100%;
            margin-top: 10px;
            margin-bottom: 10px;
        }
        .alert {
            padding: 10px 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .alert-success { background: rgba(0, 255, 0, 0.1); color: #0f0; border: 1px solid rgba(0, 255, 0, 0.2); }
        .alert-error { background: rgba(255, 0, 0, 0.1); color: #f00; border: 1px solid rgba(255, 0, 0, 0.2); }
    </style>
</head>
<body>

<div class="admin-layout">

    <?php include "../../includes/admin_header.php"; ?>

    <main class="admin-main">
        <div class="admin-topbar">
            <div>
                <h1>Import Music from API</h1>
                <div class="breadcrumb">Search iTunes music catalog.</div>
            </div>
            <a href="index.php" class="btn btn-secondary">Back to Music</a>
        </div>

        <div class="admin-content">
            <?php if ($message) echo "<div class='alert alert-success'>$message</div>"; ?>
            <?php if ($error) echo "<div class='alert alert-error'>$error</div>"; ?>

            <form method="GET" class="search-bar">
                <input type="text" name="q" placeholder="Search by song, artist, or album..." value="<?php echo htmlspecialchars($search_query); ?>" required>
                <button type="submit" class="btn btn-primary">Search</button>
            </form>

            <?php if ($search_query): ?>
                <?php if (!empty($api_results)): ?>
                    <div class="api-results">
                        <?php foreach ($api_results as $track): ?>
                            <?php 
                            $track_id = $track['providerId'] ?? '';
                            $is_imported = in_array((string)$track_id, $imported_ids);
                            $img_url = $track['artworkUrl'] ?? '';
                            ?>
                            <div class="api-card">
                                <?php if ($img_url): ?>
                                    <img src="<?php echo htmlspecialchars($img_url); ?>" alt="Cover">
                                <?php endif; ?>
                                <div>
                                    <h3><?php echo htmlspecialchars($track['title'] ?? 'Unknown'); ?></h3>
                                    <p>Artist: <?php echo htmlspecialchars($track['artistName'] ?? 'Unknown'); ?></p>
                                    <p>Album: <?php echo htmlspecialchars($track['albumName'] ?? 'Unknown'); ?></p>
                                    <p>Genre: <?php echo htmlspecialchars($track['genre'] ?? 'Unknown'); ?></p>
                                    <p>Released: <?php echo substr($track['releaseDate'] ?? '', 0, 4); ?></p>
                                </div>
                                <p class="provider-note">iTunes preview available when provided</p>

                                <?php if ($is_imported): ?>
                                    <button class="btn btn-secondary" disabled>Already Imported</button>
                                <?php else: ?>
                                    <form method="POST">
                                        <input type="hidden" name="action" value="import">
                                        <input type="hidden" name="track_id" value="<?php echo htmlspecialchars($track_id); ?>">
                                        <input type="hidden" name="title" value="<?php echo htmlspecialchars($track['title'] ?? ''); ?>">
                                        <input type="hidden" name="artist_name" value="<?php echo htmlspecialchars($track['artistName'] ?? ''); ?>">
                                        <input type="hidden" name="album_name" value="<?php echo htmlspecialchars($track['albumName'] ?? ''); ?>">
                                        <input type="hidden" name="genre_name" value="<?php echo htmlspecialchars($track['genre'] ?? ''); ?>">
                                        <input type="hidden" name="release_date" value="<?php echo htmlspecialchars($track['releaseDate'] ?? ''); ?>">
                                        <input type="hidden" name="image_url" value="<?php echo htmlspecialchars($img_url); ?>">
                                        <input type="hidden" name="preview_url" value="<?php echo htmlspecialchars($track['previewUrl'] ?? ''); ?>">
                                        
                                        <button type="submit" class="btn btn-primary" style="width: 100%; background-color: #00bcd4; border-color: #00bcd4;">Import Track</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p>No results found for "<?php echo htmlspecialchars($search_query); ?>".</p>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </main>
</div>

<?php include '../../includes/admin_footer.php'; ?>

<script>
    document.getElementById('sidebarToggle')?.addEventListener('click', () => {
        document.getElementById('sidebar').classList.toggle('open');
    });
</script>

</body>
</html>
