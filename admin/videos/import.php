<?php
require_once "../../includes/auth.php";
require_once "../../config/db.php";
require_once "../../services/ITunesProvider.php";

/** @var mysqli $conn */

requireAdmin();

$current_page = 'videos';
$admin_base = '../';
$message = "";
$error = "";

// Import selected iTunes tracks into the existing videos table.
if ($_SERVER["REQUEST_METHOD"] === "POST" && ($_POST['action'] ?? '') === 'import_selected') {
    $selected_tracks = $_POST['selected_tracks'] ?? [];
    $imported_count = 0;
    $existing_count = 0;
    $failed_count = 0;
    $database_error = '';

    foreach ($selected_tracks as $encoded_track) {
        $track = json_decode(base64_decode((string)$encoded_track, true) ?: '', true);
        if (!is_array($track)) {
            $failed_count++;
            continue;
        }

        $track_id = trim((string)($track['providerId'] ?? $track['id'] ?? ''));
        $title = trim((string)($track['title'] ?? ''));
        $provider = trim((string)($track['provider'] ?? ''));
        $artist_name = trim((string)($track['artistName'] ?? ''));
        $album_name = trim((string)($track['albumName'] ?? ''));
        $genre_name = trim((string)($track['genre'] ?? ''));
        $year_val = substr(trim((string)($track['releaseDate'] ?? '')), 0, 4);
        $image_url = trim((string)($track['artworkUrl'] ?? ''));
        $preview_url = trim((string)($track['previewUrl'] ?? ''));
        $is_music_video = ($track['type'] ?? '') === 'music-video';
        $video_file = $preview_url !== ''
            ? ($is_music_video ? 'itunes-video:' . $preview_url : $preview_url)
            : 'itunes:' . $track_id;

        if ($provider !== 'itunes' || $track_id === '' || $title === '') {
            $failed_count++;
            continue;
        }

        $duplicate_stmt = mysqli_prepare($conn, "SELECT id FROM videos WHERE video_file = ? LIMIT 1");
        mysqli_stmt_bind_param($duplicate_stmt, "s", $video_file);
        mysqli_stmt_execute($duplicate_stmt);
        $duplicate_result = mysqli_stmt_get_result($duplicate_stmt);
        $is_duplicate = $duplicate_result && mysqli_num_rows($duplicate_result) > 0;
        mysqli_stmt_close($duplicate_stmt);
        if ($is_duplicate) {
            $existing_count++;
            continue;
        }

        $artist_id = null;
        if ($artist_name !== '') {
            $stmt = mysqli_prepare($conn, "SELECT id FROM artists WHERE artist_name = ? LIMIT 1");
            mysqli_stmt_bind_param($stmt, "s", $artist_name);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $artist_row = $result ? mysqli_fetch_assoc($result) : null;
            mysqli_stmt_close($stmt);
            if ($artist_row) {
                $artist_id = (int)$artist_row['id'];
            } else {
                $stmt = mysqli_prepare($conn, "INSERT INTO artists (artist_name) VALUES (?)");
                mysqli_stmt_bind_param($stmt, "s", $artist_name);
                if (!mysqli_stmt_execute($stmt)) { $failed_count++; mysqli_stmt_close($stmt); continue; }
                $artist_id = mysqli_insert_id($conn);
                mysqli_stmt_close($stmt);
            }
        }

        $album_id = null;
        if ($album_name !== '') {
            $stmt = mysqli_prepare($conn, "SELECT id FROM albums WHERE album_name = ? AND artist_id <=> ? LIMIT 1");
            mysqli_stmt_bind_param($stmt, "si", $album_name, $artist_id);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $album_row = $result ? mysqli_fetch_assoc($result) : null;
            mysqli_stmt_close($stmt);
            if ($album_row) {
                $album_id = (int)$album_row['id'];
            } else {
                $stmt = mysqli_prepare($conn, "INSERT INTO albums (album_name, artist_id) VALUES (?, ?)");
                mysqli_stmt_bind_param($stmt, "si", $album_name, $artist_id);
                if (!mysqli_stmt_execute($stmt)) { $failed_count++; mysqli_stmt_close($stmt); continue; }
                $album_id = mysqli_insert_id($conn);
                mysqli_stmt_close($stmt);
            }
        }

        $genre_id = null;
        if ($genre_name !== '') {
            $stmt = mysqli_prepare($conn, "SELECT id FROM genres WHERE genre_name = ? LIMIT 1");
            mysqli_stmt_bind_param($stmt, "s", $genre_name);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $genre_row = $result ? mysqli_fetch_assoc($result) : null;
            mysqli_stmt_close($stmt);
            if ($genre_row) {
                $genre_id = (int)$genre_row['id'];
            } else {
                $stmt = mysqli_prepare($conn, "INSERT INTO genres (genre_name) VALUES (?)");
                mysqli_stmt_bind_param($stmt, "s", $genre_name);
                if (!mysqli_stmt_execute($stmt)) { $failed_count++; mysqli_stmt_close($stmt); continue; }
                $genre_id = mysqli_insert_id($conn);
                mysqli_stmt_close($stmt);
            }
        }

        $year_id = null;
        if (preg_match('/^\d{4}$/', $year_val)) {
            $year_number = (int)$year_val;
            $stmt = mysqli_prepare($conn, "SELECT id FROM years WHERE year_value = ? LIMIT 1");
            mysqli_stmt_bind_param($stmt, "i", $year_number);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $year_row = $result ? mysqli_fetch_assoc($result) : null;
            mysqli_stmt_close($stmt);
            if ($year_row) {
                $year_id = (int)$year_row['id'];
            } else {
                $stmt = mysqli_prepare($conn, "INSERT INTO years (year_value) VALUES (?)");
                mysqli_stmt_bind_param($stmt, "i", $year_number);
                if (!mysqli_stmt_execute($stmt)) { $failed_count++; mysqli_stmt_close($stmt); continue; }
                $year_id = mysqli_insert_id($conn);
                mysqli_stmt_close($stmt);
            }
        }

        $stmt = mysqli_prepare($conn, "INSERT INTO videos (title, artist_id, album_id, year_id, genre_id, image, video_file, is_new) VALUES (?, ?, ?, ?, ?, ?, ?, 1)");
        if (!$stmt) { $failed_count++; continue; }
        mysqli_stmt_bind_param($stmt, "siiiiss", $title, $artist_id, $album_id, $year_id, $genre_id, $image_url, $video_file);
        if (mysqli_stmt_execute($stmt)) $imported_count++;
        else { $failed_count++; $database_error = mysqli_error($conn); }
        mysqli_stmt_close($stmt);
    }

    if (count($selected_tracks) === 0) {
        $error = 'Select at least one video to import.';
    } elseif ($failed_count > 0) {
        $error = "$imported_count imported, $existing_count already exists, $failed_count failed." . ($database_error !== '' ? " Database error: $database_error" : '');
    } else {
        $message = count($selected_tracks) . " selected - $imported_count imported, $existing_count already exists.";
    }
}

// Fetch already imported video URLs to avoid showing import button for them
$imported_urls = [];
$res = mysqli_query($conn, "SELECT video_file FROM videos WHERE video_file IS NOT NULL");
while ($row = mysqli_fetch_assoc($res)) {
    $imported_urls[] = $row['video_file'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Import API Videos - SOUND Admin</title>
    <link rel="stylesheet" href="../../assets/css/style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../../assets/css/admin.css?v=<?php echo time(); ?>">
    <style>
        .search-bar {
            display: flex;
            gap: 10px;
            margin-bottom: 30px;
        }
        .search-bar input {
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
            aspect-ratio: 16/9;
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
        .api-card video {
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
                <h1>Import Videos from API</h1>
                <div class="breadcrumb">Search the iTunes catalog to add tracks to Videos.</div>
            </div>
            <a href="index.php" class="btn btn-secondary">Back to Videos</a>
        </div>

        <div class="admin-content">
            <?php if ($message) echo "<div class='alert alert-success'>$message</div>"; ?>
            <?php if ($error) echo "<div class='alert alert-error'>$error</div>"; ?>

            <form id="itunesSearchForm" class="search-bar">
                <input id="itunesSearchInput" type="text" placeholder="Search by video, artist, or album..." required>
                <button type="submit" class="btn btn-primary">Fetch Videos</button>
            </form>

            <div id="itunesStatus" style="margin-bottom:20px;"></div>
            <form method="POST" id="itunesImportForm" style="display:none;">
                <input type="hidden" name="action" value="import_selected">
                <div style="display:flex; justify-content:flex-end; margin-bottom:16px;">
                    <button type="submit" class="btn btn-primary">Import Selected</button>
                </div>
                <div class="api-results" id="itunesResults"></div>
            </form>
        </div>
    </main>
</div>

<?php include '../../includes/footer.php'; ?>

<script>
    document.getElementById('sidebarToggle')?.addEventListener('click', () => {
        document.getElementById('sidebar').classList.toggle('open');
    });

    const itunesSearchForm = document.getElementById('itunesSearchForm');
    const itunesSearchInput = document.getElementById('itunesSearchInput');
    const itunesResults = document.getElementById('itunesResults');
    const itunesImportForm = document.getElementById('itunesImportForm');
    const itunesStatus = document.getElementById('itunesStatus');
    const importedVideoFiles = <?php echo json_encode(array_values($imported_urls), JSON_UNESCAPED_SLASHES); ?>;

    function encodeTrack(track) {
        return btoa(unescape(encodeURIComponent(JSON.stringify(track))));
    }

    function artworkUrl(url) {
        return String(url || '').replace(/\/100x100bb\./i, '/600x600bb.');
    }

    function renderTracks(results) {
        itunesResults.innerHTML = '';
        if (!results.length) {
            itunesImportForm.style.display = 'none';
            itunesStatus.textContent = 'No videos found.';
            return;
        }

        results.forEach(function (item) {
            const track = {
                id: String(item.trackId || ''),
                provider: 'itunes',
                providerId: String(item.trackId || ''),
                title: item.trackName || '',
                artistName: item.artistName || '',
                albumName: item.collectionName || '',
                genre: item.primaryGenreName || '',
                releaseDate: item.releaseDate || '',
                artworkUrl: artworkUrl(item.artworkUrl100 || ''),
                previewUrl: item.previewUrl || '',
                type: item.kind === 'music-video' ? 'music-video' : 'song',
                duration: item.trackTimeMillis ? Math.round(item.trackTimeMillis / 1000) : null,
                isPlayable: Boolean(item.previewUrl)
            };
            const source = track.previewUrl
                ? (track.type === 'music-video' ? 'itunes-video:' + track.previewUrl : track.previewUrl)
                : 'itunes:' + track.providerId;
            const card = document.createElement('div');
            card.className = 'api-card';
            const duplicate = importedVideoFiles.indexOf(source) !== -1;
            const label = document.createElement('label');
            label.style.cssText = 'display:flex; align-items:center; gap:8px; color:var(--text-primary);';
            const checkbox = document.createElement('input');
            checkbox.type = 'checkbox';
            checkbox.name = 'selected_tracks[]';
            checkbox.value = encodeTrack(track);
            checkbox.disabled = duplicate;
            label.appendChild(checkbox);
            label.appendChild(document.createTextNode(' Select'));
            card.appendChild(label);
            if (track.artworkUrl) {
                const artwork = document.createElement('img');
                artwork.src = track.artworkUrl;
                artwork.alt = 'Artwork';
                card.appendChild(artwork);
            }
            const details = document.createElement('div');
            const title = document.createElement('h3');
            title.textContent = track.title || 'Unknown';
            details.appendChild(title);
            ['Artist: ' + (track.artistName || 'Unknown'), 'Album: ' + (track.albumName || 'Unknown'), 'Track ID: ' + track.providerId].forEach(function (text) {
                const paragraph = document.createElement('p');
                paragraph.textContent = text;
                details.appendChild(paragraph);
            });
            card.appendChild(details);
            const note = document.createElement('p');
            note.className = 'provider-note';
            note.textContent = track.previewUrl ? 'Preview available' : 'Preview unavailable for this track.';
            card.appendChild(note);
            if (track.previewUrl) {
                const preview = document.createElement(track.type === 'music-video' ? 'video' : 'audio');
                preview.controls = true;
                preview.preload = 'none';
                preview.src = track.previewUrl;
                preview.style.width = '100%';
                if (track.type === 'music-video') preview.setAttribute('playsinline', '');
                card.appendChild(preview);
            }
            if (duplicate) {
                const imported = document.createElement('button');
                imported.type = 'button';
                imported.className = 'btn btn-secondary';
                imported.disabled = true;
                imported.textContent = 'Already Imported';
                card.appendChild(imported);
            }
            itunesResults.appendChild(card);
        });
        itunesImportForm.style.display = 'block';
        itunesStatus.textContent = '';
    }

    itunesSearchForm.addEventListener('submit', function (event) {
        event.preventDefault();
        const query = itunesSearchInput.value.trim();
        if (!query) return;
        itunesStatus.textContent = 'Loading...';
        itunesImportForm.style.display = 'none';
        fetch('https://itunes.apple.com/search?term=' + encodeURIComponent(query) + '&media=musicVideo&entity=musicVideo&limit=25')
            .then(function (response) {
                if (!response.ok) throw new Error('iTunes search failed.');
                return response.json();
            })
            .then(function (data) { renderTracks(Array.isArray(data.results) ? data.results : []); })
            .catch(function () {
                itunesImportForm.style.display = 'none';
                itunesStatus.textContent = 'iTunes search is temporarily unavailable.';
            });
    });
</script>

</body>
</html>
