<?php
session_start();
require_once "config/db.php";

$site_info_query = mysqli_query($conn, "SELECT * FROM website_info LIMIT 1");
$site_info = mysqli_fetch_assoc($site_info_query);

// Fetch genres for navbar dropdown
$nav_genres = [];
$nav_genre_q = mysqli_query($conn, "SELECT * FROM genres ORDER BY genre_name ASC");
if ($nav_genre_q) {
    while ($g = mysqli_fetch_assoc($nav_genre_q)) {
        $nav_genres[] = $g;
    }
}

// Fetch filter options (artist, year, language only)
$filter_artists = mysqli_query($conn, "SELECT id, artist_name FROM artists ORDER BY artist_name ASC");
$filter_years = mysqli_query($conn, "SELECT id, year_value FROM years ORDER BY year_value DESC");
$filter_languages = mysqli_query($conn, "SELECT id, language_name FROM languages ORDER BY language_name ASC");

$music_query_str = "SELECT m.*, a.artist_name, l.language_name 
     FROM music m 
     LEFT JOIN artists a ON m.artist_id = a.id 
     LEFT JOIN languages l ON m.language_id = l.id 
     WHERE 1=1";

$params = [];
$types = "";

$is_filtered = false;

if (!empty($_GET['artist'])) {
    $music_query_str .= " AND m.artist_id = ?";
    $params[] = intval($_GET['artist']);
    $types .= "i";
    $is_filtered = true;
}
if (!empty($_GET['album'])) {
    $music_query_str .= " AND m.album_id = ?";
    $params[] = intval($_GET['album']);
    $types .= "i";
    $is_filtered = true;
}
if (!empty($_GET['genre'])) {
    $music_query_str .= " AND m.genre_id = ?";
    $params[] = intval($_GET['genre']);
    $types .= "i";
    $is_filtered = true;
}
if (!empty($_GET['year'])) {
    $music_query_str .= " AND m.year_id = ?";
    $params[] = intval($_GET['year']);
    $types .= "i";
    $is_filtered = true;
}
if (!empty($_GET['language'])) {
    $music_query_str .= " AND m.language_id = ?";
    $params[] = intval($_GET['language']);
    $types .= "i";
    $is_filtered = true;
}

$music_query_str .= " ORDER BY m.created_at DESC";

$stmt = mysqli_prepare($conn, $music_query_str);
if ($stmt) {
    if (!empty($params)) {
        // Bind parameters using call_user_func_array for reference passing
        $bind_params = array_merge([$types], $params);
        $tmp = [];
        foreach ($bind_params as $key => $value) {
            $tmp[$key] = &$bind_params[$key];
        }
        call_user_func_array([$stmt, 'bind_param'], $tmp);
    }
    mysqli_stmt_execute($stmt);
    $music_query = mysqli_stmt_get_result($stmt);
} else {
    $music_query = false;
}

$english_songs = [];
$urdu_hindi_songs = [];

$english_fallback_artists = ['coldplay', 'ed sheeran', 'the weeknd', 'taylor swift', 'adele', 'bruno mars', 'dua lipa', 'maroon 5', 'harry styles', 'miley cyrus', 'imagine dragons', 'billie eilish'];

if ($music_query) {
    while ($music = mysqli_fetch_assoc($music_query)) {
        $lang = $music['language_name'] ? strtolower(trim($music['language_name'])) : '';

        if ($lang === 'english') {
            $english_songs[] = $music;
        } elseif (in_array($lang, ['urdu', 'hindi', 'eng-urdu'])) {
            $urdu_hindi_songs[] = $music;
        } else {
            // Fallback for NULL language_id to ensure no songs are missing
            $artist = strtolower(trim($music['artist_name'] ?? ''));
            if (in_array($artist, $english_fallback_artists)) {
                $english_songs[] = $music;
            } else {
                $urdu_hindi_songs[] = $music;
            }
        }
    }
}

// Helper function to render a music card
function render_music_card($music)
{
    $id = $music['id'];
    $title = htmlspecialchars($music['title']);
    $artist = htmlspecialchars($music['artist_name'] ?? 'Unknown Artist');
    $is_new = $music['is_new'];

    $img_src = $music['image'];
    if (!preg_match('/^https?:\/\//i', $img_src) && !empty($img_src)) {
        $img_src = "uploads/music/images/" . htmlspecialchars($img_src);
    } else {
        $img_src = htmlspecialchars($img_src);
    }

    echo '<div class="music-card fade-on-scroll" onclick="window.location.href=\'play_music.php?id=' . $id . '\'">';
    echo '<div class="card-image">';
    if ($img_src) {
        echo '<img src="' . $img_src . '" alt="Cover">';
    } else {
        echo '<div class="placeholder-icon"><i data-lucide="music" class="icon-ui"></i></div>';
    }
    echo '<a href="play_music.php?id=' . $id . '" class="play-btn-circle" onclick="event.stopPropagation();">';
    echo '<i data-lucide="play" style="width: 24px; height: 24px; fill: currentColor;"></i>';
    echo '</a>';

    // Add to Playlist Button
    if (isset($_SESSION['user_id'])) {
        echo '<button class="add-to-pl-btn" onclick="event.stopPropagation(); openPlaylistModal(' . $id . ', \'' . addslashes($title) . '\')" title="Add to Playlist">';
        echo '<i data-lucide="plus" style="width: 24px; height: 24px;"></i>';
        echo '</button>';
    }

    echo '</div>';
    echo '<div class="card-info">';
    echo '<h3>' . $title . '</h3>';
    echo '<p>' . $artist . '</p>';
    if ($is_new) {
        echo '<span class="badge badge-new">NEW</span>';
    }
    echo '</div>';
    echo '</div>';
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Music - <?php echo htmlspecialchars($site_info['site_name'] ?? 'SOUND'); ?></title>
    <link rel="stylesheet" href="assets/css/style.css?v=<?= time() ?>">
    <script src="assets/js/theme.js?v=<?php echo time(); ?>"></script>
    <style>
        .animated-bg {
            background: radial-gradient(circle at 14% 10%, rgba(30, 215, 96, .14), transparent 28rem), radial-gradient(circle at 88% 0%, rgba(255, 49, 49, .13), transparent 26rem), #0f0f10 !important;
        }


        /* Add to Playlist button styles */
        .add-to-pl-btn {
            position: absolute;
            top: 8px;
            right: 8px;
            width: 32px;
            height: 32px;
            background: rgba(0, 0, 0, 0.6);
            border: none;
            border-radius: 50%;
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            opacity: 0;
            transform: translateY(-8px);
            transition: all 0.3s ease;
            backdrop-filter: blur(4px);
        }

        .music-card:hover .add-to-pl-btn {
            opacity: 1;
            transform: translateY(0);
        }

        .add-to-pl-btn:hover {
            background: var(--accent-purple, #06b6d4);
            color: #000;
        }

        .add-to-pl-btn svg {
            width: 20px;
            height: 20px;
            fill: currentColor;
        }

        /* Modern Modal specific to Playlist */
        .pl-modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            z-index: 1000;
            align-items: center;
            justify-content: center;
            backdrop-filter: blur(4px);
        }

        .pl-modal {
            background: #181818;
            width: 400px;
            max-width: 90%;
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.5);
            display: flex;
            flex-direction: column;
        }

        .pl-modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .pl-modal-header h3 {
            margin: 0;
            color: #fff;
            font-size: 20px;
        }

        .pl-modal-close {
            background: none;
            border: none;
            color: #b3b3b3;
            font-size: 24px;
            cursor: pointer;
        }

        .pl-modal-close:hover {
            color: #fff;
        }

        .pl-list-container {
            max-height: 250px;
            overflow-y: auto;
            margin-bottom: 16px;
            border-top: 1px solid #333;
            border-bottom: 1px solid #333;
            padding: 8px 0;
        }

        .pl-item-btn {
            width: 100%;
            background: transparent;
            border: none;
            padding: 12px;
            display: flex;
            align-items: center;
            gap: 12px;
            color: #fff;
            cursor: pointer;
            border-radius: 6px;
            text-align: left;
        }

        .pl-item-btn:hover {
            background: #282828;
        }

        .pl-item-btn svg {
            width: 24px;
            height: 24px;
            fill: #b3b3b3;
        }

        .pl-item-btn:hover svg {
            fill: #fff;
        }

        .pl-new-btn {
            background: transparent;
            color: var(--accent-purple, #06b6d4);
            border: 1px solid var(--accent-purple, #06b6d4);
            padding: 10px;
            border-radius: 50px;
            font-weight: bold;
            cursor: pointer;
            text-align: center;
            transition: 0.2s;
        }

        .pl-new-btn:hover {
            background: var(--accent-purple, #06b6d4);
            color: #000;
        }

        .pl-create-form {
            display: none;
            flex-direction: column;
            gap: 12px;
            margin-top: 16px;
        }

        .pl-input {
            width: 100%;
            padding: 12px;
            border-radius: 6px;
            border: 1px solid #333;
            background: #242424;
            color: #fff;
            box-sizing: border-box;
        }

        .pl-btn-group {
            display: flex;
            gap: 12px;
            justify-content: flex-end;
        }

        .pl-submit {
            background: var(--accent-purple, #06b6d4);
            color: #000;
            border: none;
            padding: 10px 20px;
            border-radius: 50px;
            font-weight: bold;
            cursor: pointer;
        }

        .pl-cancel {
            background: transparent;
            color: #fff;
            border: none;
            cursor: pointer;
            font-weight: bold;
        }

        /* Toast styles */
        #pl-toast {
            visibility: hidden;
            min-width: 250px;
            background-color: #333;
            color: #fff;
            text-align: center;
            border-radius: 8px;
            padding: 16px;
            position: fixed;
            z-index: 1001;
            left: 50%;
            bottom: 30px;
            transform: translateX(-50%);
            font-size: 14px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
            display: flex;
            align-items: center;
            gap: 12px;
            justify-content: center;
            font-weight: 500;
        }

        #pl-toast.show {
            visibility: visible;
            -webkit-animation: fadein 0.5s, fadeout 0.5s 2.5s;
            animation: fadein 0.5s, fadeout 0.5s 2.5s;
        }

        #pl-toast.success {
            background-color: #10b981;
            color: #000;
        }

        #pl-toast.error {
            background-color: #ef4444;
            color: #fff;
        }

        @-webkit-keyframes fadein {
            from {
                bottom: 0;
                opacity: 0;
            }

            to {
                bottom: 30px;
                opacity: 1;
            }
        }

        @keyframes fadein {
            from {
                bottom: 0;
                opacity: 0;
            }

            to {
                bottom: 30px;
                opacity: 1;
            }
        }

        @-webkit-keyframes fadeout {
            from {
                bottom: 30px;
                opacity: 1;
            }

            to {
                bottom: 0;
                opacity: 0;
            }
        }

        @keyframes fadeout {
            from {
                bottom: 30px;
                opacity: 1;
            }

            to {
                bottom: 0;
                opacity: 0;
            }
        }
    </style>
</head>

<body>

    <?php if (isset($_SESSION['user_id'])) {
        include 'includes/user_sidebar.php';
    } ?>
    <div class="<?php echo isset($_SESSION['user_id']) ? 'user-main-content main-wrapper' : 'main-wrapper'; ?>">

        <div class="animated-bg"></div>

        <?php include 'includes/navbar.php'; ?>

        <?php if (!$is_filtered): ?>
            <div class="hero-premium-sm" style="min-height: auto; height: auto; padding-top: 100px; padding-bottom: 25px; margin-bottom: 10px; align-items: flex-start;">
                <div class="hero-content-sm">
                    <h1 style="color: #ffffff; margin-top: 0; margin-bottom: 10px;">Experience the Magic of <span class="highlight">Music</span></h1>
                    <p style="margin: 0;">Dive into our vast collection of high-quality tracks, albums, and exclusive releases.</p>
                </div>
            </div>
        <?php else: ?>
            <div class="filtered-header" style="padding: 40px 20px 10px; text-align: center;">
                <h2 style="color: #ffffff; margin: 0;">Filtered Music</h2>
            </div>
        <?php endif; ?>

        <div class="container" style="max-width: 1200px; margin: 0 auto 40px auto; padding: 10px 20px 20px 20px;">

            <form method="GET" action="music.php" class="premium-filter-container">
                <div class="filter-group-premium">
                    <i data-lucide="user" class="filter-icon-left"></i>
                    <label for="music-filter-artist" class="filter-label-small">Artist</label>
                    <select name="artist" class="filter-input-premium" id="music-filter-artist">
                        <option value="">All Artists</option>
                        <?php if ($filter_artists) {
                            while ($row = mysqli_fetch_assoc($filter_artists)) { ?>
                                <option value="<?php echo $row['id']; ?>" <?php echo (isset($_GET['artist']) && $_GET['artist'] == $row['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($row['artist_name']); ?></option>
                        <?php }
                        } ?>
                    </select>
                    <i data-lucide="chevron-down" class="filter-chevron-right"></i>
                </div>
                <div class="filter-group-premium">
                    <i data-lucide="calendar" class="filter-icon-left"></i>
                    <label for="music-filter-year" class="filter-label-small">Year</label>
                    <select name="year" class="filter-input-premium" id="music-filter-year">
                        <option value="">All Years</option>
                        <?php if ($filter_years) {
                            while ($row = mysqli_fetch_assoc($filter_years)) { ?>
                                <option value="<?php echo $row['id']; ?>" <?php echo (isset($_GET['year']) && $_GET['year'] == $row['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($row['year_value']); ?></option>
                        <?php }
                        } ?>
                    </select>
                    <i data-lucide="chevron-down" class="filter-chevron-right"></i>
                </div>
                <div class="filter-group-premium">
                    <i data-lucide="globe" class="filter-icon-left"></i>
                    <label for="music-filter-language" class="filter-label-small">Language</label>
                    <select name="language" class="filter-input-premium" id="music-filter-language">
                        <option value="">All Languages</option>
                        <?php if ($filter_languages) {
                            while ($row = mysqli_fetch_assoc($filter_languages)) { ?>
                                <option value="<?php echo $row['id']; ?>" <?php echo (isset($_GET['language']) && $_GET['language'] == $row['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($row['language_name']); ?></option>
                        <?php }
                        } ?>
                    </select>
                    <i data-lucide="chevron-down" class="filter-chevron-right"></i>
                </div>
                <div class="filter-actions-premium">
                    <button type="submit" class="btn-premium-apply"><i data-lucide="filter"></i> Apply Filters</button>
                    <a href="music.php" class="btn-premium-clear" id="music-filter-clear"><i data-lucide="rotate-ccw"></i> Clear / Reset Filters</a>
                </div>
            </form>

            <div id="music-results-container">
                <?php
                if (isset($_GET['ajax']) && $_GET['ajax'] == '1') {
                    while (ob_get_level()) {
                        ob_end_clean();
                    }
                    ob_start();
                }
                ?>
                <?php if (count($english_songs) > 0): ?>
                    <section id="music-english" style="margin-bottom: 60px;">
                        <div class="premium-heading-container">
                            <h2 class="premium-heading">English <span class="text-gradient-neon">Songs</span></h2>
                            <div class="neon-accent-line"></div>
                        </div>
                        <div class="media-grid">
                            <?php foreach ($english_songs as $music): ?>
                                <?php render_music_card($music); ?>
                            <?php endforeach; ?>
                        </div>
                    </section>
                <?php endif; ?>

                <?php if (count($urdu_hindi_songs) > 0): ?>
                    <section id="music-urdu" style="margin-bottom: 60px;">
                        <div class="premium-heading-container">
                            <h2 class="premium-heading">Urdu / Hindi <span class="text-gradient-neon">Songs</span></h2>
                            <div class="neon-accent-line"></div>
                        </div>
                        <div class="media-grid">
                            <?php foreach ($urdu_hindi_songs as $music): ?>
                                <?php render_music_card($music); ?>
                            <?php endforeach; ?>
                        </div>
                    </section>
                <?php endif; ?>

                <?php if (count($english_songs) === 0 && count($urdu_hindi_songs) === 0): ?>
                    <section id="music">
                        <div class="premium-heading-container">
                            <h2 class="premium-heading">All <span class="text-gradient-neon">Music</span></h2>
                            <div class="neon-accent-line"></div>
                        </div>
                        <p style="color: var(--text-muted);"><?php echo $is_filtered ? 'No songs found for the selected filters.' : 'No music available yet.'; ?></p>
                    </section>
                <?php endif; ?>
                <?php
                if (isset($_GET['ajax']) && $_GET['ajax'] == '1') {
                    echo ob_get_clean();
                    exit;
                }
                ?>
            </div>

        </div>

        <?php include 'includes/footer.php'; ?>

        <script src="assets/js/app.js"></script>

        <?php if (isset($_SESSION['user_id'])): ?>
            <!-- Add to Playlist Modal -->
            <div id="addToPlaylistModal" class="pl-modal-overlay">
                <div class="pl-modal">
                    <div class="pl-modal-header">
                        <h3 id="plModalSongTitle">Add to Playlist</h3>
                        <button class="pl-modal-close" onclick="closePlaylistModal()">&times;</button>
                    </div>

                    <div class="pl-list-container" id="plListContainer">
                        <div style="text-align:center; padding: 20px; color: #b3b3b3;">Loading your playlists...</div>
                    </div>

                    <button class="pl-new-btn" id="showCreatePlBtn" onclick="toggleCreateForm()">+ Create New Playlist</button>

                    <form class="pl-create-form" id="plCreateForm" onsubmit="event.preventDefault(); createAndAddPlaylist();">
                        <input type="text" id="newPlTitle" class="pl-input" placeholder="My New Playlist" required>
                        <div class="pl-btn-group">
                            <button type="button" class="pl-cancel" onclick="toggleCreateForm()">Cancel</button>
                            <button type="submit" class="pl-submit">Create & Add</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Toast Notification -->
            <div id="pl-toast"></div>

            <script>
                let currentSongId = null;

                function showToast(message, type = 'success') {
                    const toast = document.getElementById("pl-toast");
                    toast.textContent = message;
                    toast.className = "show " + type;
                    setTimeout(function() {
                        toast.className = toast.className.replace("show", "");
                    }, 3000);
                }

                function openPlaylistModal(songId, songTitle) {
                    currentSongId = songId;
                    document.getElementById('plModalSongTitle').innerText = 'Add "' + songTitle + '" to:';
                    document.getElementById('addToPlaylistModal').style.display = 'flex';
                    document.getElementById('plCreateForm').style.display = 'none';
                    document.getElementById('showCreatePlBtn').style.display = 'block';
                    loadPlaylists();
                }

                function closePlaylistModal() {
                    document.getElementById('addToPlaylistModal').style.display = 'none';
                    currentSongId = null;
                }

                function toggleCreateForm() {
                    const form = document.getElementById('plCreateForm');
                    const btn = document.getElementById('showCreatePlBtn');
                    if (form.style.display === 'none' || form.style.display === '') {
                        form.style.display = 'flex';
                        btn.style.display = 'none';
                        document.getElementById('newPlTitle').focus();
                    } else {
                        form.style.display = 'none';
                        btn.style.display = 'block';
                    }
                }

                async function loadPlaylists() {
                    const container = document.getElementById('plListContainer');
                    try {
                        const response = await fetch('user/ajax_playlist_add.php?action=get_playlists');
                        const data = await response.json();

                        if (data.success && data.playlists.length > 0) {
                            container.innerHTML = '';
                            data.playlists.forEach(pl => {
                                const btn = document.createElement('button');
                                btn.className = 'pl-item-btn';
                                btn.innerHTML = `<i data-lucide="music" style="width: 24px; height: 24px;"></i>
                                         <div style="display:flex; flex-direction:column;">
                                            <span style="font-weight:600; font-size:15px;">${pl.title}</span>
                                            <span style="color:#b3b3b3; font-size:12px;">${pl.song_count} songs</span>
                                         </div>`;
                                btn.onclick = () => addSongToPlaylist(pl.id);
                                container.appendChild(btn);
                            });
                        } else if (data.success && data.playlists.length === 0) {
                            container.innerHTML = '<div style="text-align:center; padding:20px; color:#b3b3b3;">You don\'t have any playlists yet.</div>';
                        } else {
                            container.innerHTML = '<div style="text-align:center; padding:20px; color:#ef4444;">Failed to load playlists.</div>';
                        }
                    } catch (error) {
                        container.innerHTML = '<div style="text-align:center; padding:20px; color:#ef4444;">Network error.</div>';
                    }
                }

                async function addSongToPlaylist(playlistId) {
                    if (!currentSongId) return;

                    const formData = new FormData();
                    formData.append('action', 'add');
                    formData.append('playlist_id', playlistId);
                    formData.append('music_id', currentSongId);

                    try {
                        const response = await fetch('user/ajax_playlist_add.php', {
                            method: 'POST',
                            body: formData
                        });
                        const data = await response.json();

                        closePlaylistModal();
                        if (data.success) {
                            showToast(data.message, 'success');
                        } else {
                            showToast(data.message || 'Failed to add song.', 'error');
                        }
                    } catch (error) {
                        showToast('Network error occurred.', 'error');
                    }
                }

                async function createAndAddPlaylist() {
                    if (!currentSongId) return;
                    const titleInput = document.getElementById('newPlTitle');
                    const title = titleInput.value.trim();
                    if (!title) return;

                    const formData = new FormData();
                    formData.append('action', 'create_and_add');
                    formData.append('title', title);
                    formData.append('music_id', currentSongId);

                    try {
                        const response = await fetch('user/ajax_playlist_add.php', {
                            method: 'POST',
                            body: formData
                        });
                        const data = await response.json();

                        if (data.success) {
                            titleInput.value = '';
                            closePlaylistModal();
                            showToast(data.message, 'success');
                        } else {
                            showToast(data.message || 'Failed to create playlist.', 'error');
                        }
                    } catch (error) {
                        showToast('Network error occurred.', 'error');
                    }
                }

                // Close modal when clicking outside
                window.onclick = function(event) {
                    const modal = document.getElementById('addToPlaylistModal');
                    if (event.target === modal) {
                        closePlaylistModal();
                    }
                }
            </script>
        <?php endif; ?>

    </div> <!-- End user-main-content -->

</body>

</html>
