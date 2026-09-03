<?php
session_start();
require_once "config/db.php";

// Fetch website info
$site_info_query = mysqli_query($conn, "SELECT * FROM website_info LIMIT 1");
$site_info = mysqli_fetch_assoc($site_info_query);

// Fetch filter options (artist, year, language only)
$filter_artists = mysqli_query($conn, "SELECT id, artist_name FROM artists ORDER BY artist_name ASC");
$filter_years = mysqli_query($conn, "SELECT id, year_value FROM years ORDER BY year_value DESC");
$filter_languages = mysqli_query($conn, "SELECT id, language_name FROM languages ORDER BY language_name ASC");

// Fetch music with filters (artist, year, language)
$music_query_str = "SELECT m.*, a.artist_name 
     FROM music m 
     LEFT JOIN artists a ON m.artist_id = a.id 
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

if ($is_filtered) {
    $music_query_str .= " ORDER BY m.created_at DESC";
} else {
    $music_query_str .= " ORDER BY m.created_at DESC LIMIT 5";
}

$stmt = mysqli_prepare($conn, $music_query_str);
if ($stmt) {
    if (!empty($params)) {
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

// Fetch recent videos
$video_query = mysqli_query(
    $conn,
    "SELECT v.*, a.artist_name 
     FROM videos v 
     LEFT JOIN artists a ON v.artist_id = a.id 
     ORDER BY v.created_at DESC LIMIT 5"
);

// Fetch genres for navbar dropdown
$nav_genres = [];
$nav_genre_q = mysqli_query($conn, "SELECT * FROM genres ORDER BY genre_name ASC");
if ($nav_genre_q) {
    while ($g = mysqli_fetch_assoc($nav_genre_q)) {
        $nav_genres[] = $g;
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($site_info['site_name'] ?? 'SOUND'); ?> - Home</title>
    <link rel="stylesheet" href="assets/css/style.css?v=<?= time() ?>">
    <script src="assets/js/theme.js?v=<?php echo time(); ?>"></script>
</head>

<body>

    <?php if (isset($_SESSION['user_id'])) {
        include 'includes/user_sidebar.php';
    } ?>
    <div class="<?php echo isset($_SESSION['user_id']) ? 'user-main-content main-wrapper' : 'main-wrapper'; ?>">

        <div class="animated-bg"></div>

        <?php include 'includes/navbar.php'; ?>

        <?php if (!$is_filtered): ?>
            <div class="hero-premium" style="position: relative; overflow: hidden; border-bottom: none; min-height: auto; padding-top: 55px; padding-bottom: 25px; margin-bottom: 5px; display: flex; align-items: flex-start;">
                <!-- Background Image -->
                <div style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: linear-gradient(120deg, #141416 0%, #182a1f 52%, #281719 100%); z-index: 0;"></div>

                <!-- Very subtle overlay just for text readability if needed (not gray/white) -->
                <div style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: rgba(15, 23, 42, 0.4); z-index: 1;"></div>

                <!-- Mobile overlay enhancement (for smaller screens to ensure readability if image shifts) -->
                <div class="mobile-overlay" style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: rgba(15, 23, 42, 0.6); z-index: 1; display: none;"></div>

                <div class="hero-content hero-content-shift" style="position: relative; z-index: 3; width: 100%; max-width: 1200px; margin: 0 auto; padding: 0 20px;">
                    <h1 style="color: #ffffff;">Experience the Best <br> of <span class="highlight">Sound & Vision</span></h1>
                    <p style="color: #cbd5e1;"><?php echo htmlspecialchars($site_info['description'] ?? 'Your ultimate music and video entertainment platform.'); ?></p>
                    <style>
                        .hero-content-shift {
                            transform: translateX(-60px);
                        }

                        /* Keep hero content naturally closer to the navbar */
                        .hero-premium .hero-content {
                            padding-top: 0 !important;
                        }

                        /* Balanced heading spacing */
                        .hero-premium h1 {
                            margin-top: 0 !important;
                            margin-bottom: 12px !important;
                        }

                        /* Balanced description spacing */
                        .hero-premium p {
                            margin-top: 0 !important;
                            margin-bottom: 0 !important;
                        }

                        /* Keep buttons close to the description */
                        .hero-premium .hero-buttons {
                            margin-top: 16px !important;
                            margin-bottom: 0 !important;
                        }

                        .hero-btn-outline {
                            color: #ffffff !important;
                            border-color: rgba(255, 255, 255, 0.4) !important;
                        }

                        .hero-btn-outline:hover {
                            color: #000000 !important;
                            background: #ffffff !important;
                            border-color: #ffffff !important;
                        }

                        @keyframes subtleZoom {
                            0% {
                                transform: scale(1);
                            }

                            100% {
                                transform: scale(1.05);
                            }
                        }

                        /* Bring the Music section closer to the Hero */
                        .filter-section {
                            margin-top: 15px;
                        }

                        #music {
                            margin-top: 0 !important;
                        }

                        @media (max-width: 768px) {
                            .hero-premium {
                                padding-top: 35px !important;
                                padding-bottom: 25px !important;
                            }

                            .hero-content-shift {
                                transform: translateX(0);
                            }

                            .mobile-overlay {
                                display: block !important;
                            }

                            .hero-premium>div:first-child {
                                background-position: 70% center !important;
                            }

                            .hero-premium h1 {
                                margin-bottom: 10px !important;
                            }

                            .hero-premium .hero-buttons {
                                margin-top: 15px !important;
                            }
                        }
                    </style>
                    <div class="hero-buttons" style="display: flex; gap: 15px; margin-top: 20px;">
                        <a href="music.php" class="btn btn-primary btn-lg" style="display: inline-flex; align-items: center; gap: 8px;">
                            <i data-lucide="play" style="width: 20px; height: 20px; fill: currentColor;"></i> Explore Music
                        </a>
                        <a href="videos.php" class="btn btn-outline btn-lg hero-btn-outline" style="display: inline-flex; align-items: center; gap: 8px;">
                            <i data-lucide="video" style="width: 20px; height: 20px;"></i> Watch Videos
                        </a>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="filtered-header" style="padding: 40px; text-align: center;">
                <h2 style="color: #ffffff; margin: 0;">Filtered Results</h2>
            </div>
        <?php endif; ?>

        <div class="media-sections-wrapper" style="position: relative; overflow: visible; padding: 20px 0 80px; margin-bottom: 0;">
            <!-- Single continuous background image spanning both sections -->
            <div style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: linear-gradient(120deg, #171719 0%, #25191b 48%, #14291d 100%); z-index: 0;"></div>

            <div class="container" style="max-width: 1200px; margin: 0 auto; padding: 0 20px; position: relative; z-index: 2;">

                <form method="GET" action="index.php" class="premium-filter-container ajax-filter-form" data-results-container="home-music-results-container">
                    <div class="filter-group-premium">
                        <i data-lucide="user" class="filter-icon-left"></i>
                        <label for="home-filter-artist" class="filter-label-small">Artist</label>
                        <select name="artist" class="filter-input-premium" id="home-filter-artist">
                            <option value="">All Artists</option>
                            <?php if ($filter_artists) {
                                mysqli_data_seek($filter_artists, 0);
                                while ($row = mysqli_fetch_assoc($filter_artists)) { ?>
                                    <option value="<?php echo $row['id']; ?>" <?php echo (isset($_GET['artist']) && $_GET['artist'] == $row['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($row['artist_name']); ?></option>
                            <?php }
                            } ?>
                        </select>
                        <i data-lucide="chevron-down" class="filter-chevron-right"></i>
                    </div>
                    <div class="filter-group-premium">
                        <i data-lucide="calendar" class="filter-icon-left"></i>
                        <label for="home-filter-year" class="filter-label-small">Year</label>
                        <select name="year" class="filter-input-premium" id="home-filter-year">
                            <option value="">All Years</option>
                            <?php if ($filter_years) {
                                mysqli_data_seek($filter_years, 0);
                                while ($row = mysqli_fetch_assoc($filter_years)) { ?>
                                    <option value="<?php echo $row['id']; ?>" <?php echo (isset($_GET['year']) && $_GET['year'] == $row['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($row['year_value']); ?></option>
                            <?php }
                            } ?>
                        </select>
                        <i data-lucide="chevron-down" class="filter-chevron-right"></i>
                    </div>
                    <div class="filter-group-premium">
                        <i data-lucide="globe" class="filter-icon-left"></i>
                        <label for="home-filter-language" class="filter-label-small">Language</label>
                        <select name="language" class="filter-input-premium" id="home-filter-language">
                            <option value="">All Languages</option>
                            <?php if ($filter_languages) {
                                mysqli_data_seek($filter_languages, 0);
                                while ($row = mysqli_fetch_assoc($filter_languages)) { ?>
                                    <option value="<?php echo $row['id']; ?>" <?php echo (isset($_GET['language']) && $_GET['language'] == $row['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($row['language_name']); ?></option>
                            <?php }
                            } ?>
                        </select>
                        <i data-lucide="chevron-down" class="filter-chevron-right"></i>
                    </div>
                    <div class="filter-actions-premium">
                        <button type="submit" class="btn-premium-apply"><i data-lucide="filter"></i> Apply Filters</button>
                        <a href="index.php" class="btn-premium-clear" id="home-filter-clear"><i data-lucide="rotate-ccw"></i> Clear / Reset Filters</a>
                    </div>
                </form>

                <div id="home-music-results-container">
                    <?php
                    if (isset($_GET['ajax']) && $_GET['ajax'] == '1') {
                        while (ob_get_level()) {
                            ob_end_clean();
                        }
                        ob_start();
                    }
                    ?>
                    <section id="music" style="margin-bottom: 80px;">
                        <div class="premium-heading-container">
                            <h2 class="premium-heading"><?php echo $is_filtered ? 'Filtered' : 'Latest'; ?> <span class="text-gradient-neon">Music</span></h2>
                            <div class="neon-accent-line"></div>
                        </div>

                        <?php if ($music_query && mysqli_num_rows($music_query) > 0): ?>
                            <div class="media-grid large">
                                <?php while ($music = mysqli_fetch_assoc($music_query)): ?>
                                    <div class="music-card fade-on-scroll" onclick="window.location.href='<?php echo isset($_SESSION['user_id']) ? 'play_music.php?id=' . $music['id'] : 'login.php?redirect=' . urlencode('play_music.php?id=' . $music['id']); ?>'">

                                        <div class="card-image" style="padding-top: 56.25%;">
                                            <?php if ($music['image']): ?>
                                                <?php
                                                $img_src = $music['image'];
                                                if (!preg_match('/^https?:\/\//i', $img_src) && !empty($img_src)) {
                                                    $img_src = "uploads/music/images/" . htmlspecialchars($img_src);
                                                } else {
                                                    $img_src = htmlspecialchars($img_src);
                                                }
                                                ?>
                                                <img src="<?php echo $img_src; ?>" alt="Cover" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; object-fit: cover;">
                                            <?php else: ?>
                                                <div style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: var(--bg-secondary); display:flex; align-items:center; justify-content:center; font-size: 3rem;"><i data-lucide="music" class="icon-ui"></i></div>
                                            <?php endif; ?>
                                            <a href="<?php echo isset($_SESSION['user_id']) ? 'play_music.php?id=' . $music['id'] : 'login.php?redirect=' . urlencode('play_music.php?id=' . $music['id']); ?>" class="play-btn-circle" onclick="event.stopPropagation();">
                                                <i data-lucide="play" style="width: 24px; height: 24px; fill: currentColor; margin-left: 4px;"></i>
                                            </a>
                                        </div>
                                        <div class="card-info">
                                            <h3><?php echo htmlspecialchars($music['title']); ?></h3>
                                            <p><?php echo htmlspecialchars($music['artist_name'] ?? 'Unknown Artist'); ?></p>
                                            <?php if ($music['is_new']): ?>
                                                <span class="badge badge-new">NEW</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            </div>
                        <?php else: ?>
                            <p style="color: var(--text-muted);"><?php echo $is_filtered ? 'No music found for the selected filters.' : 'No music available yet.'; ?></p>
                        <?php endif; ?>

                        <div style="text-align: center; margin-top: 60px; margin-bottom: 30px;">
                            <a href="music.php" class="btn btn-outline" style="display:inline-flex; align-items:center; border-color:var(--accent-pink); color:var(--accent-pink); border-radius:50px; padding:10px 24px; font-weight:600; text-transform:uppercase; letter-spacing:1px; transition:0.3s;" onmouseover="this.style.background='var(--accent-pink)'; this.style.color='#fff';" onmouseout="this.style.background='transparent'; this.style.color='var(--accent-pink)';">
                            Explore All Music <i data-lucide="arrow-right" style="width:18px;height:18px;margin-left:8px;"></i>
                            </a>
                        </div>
                    </section>
                    <?php
                    if (isset($_GET['ajax']) && $_GET['ajax'] == '1') {
                        echo ob_get_clean();
                        exit;
                    }
                    ?>
                </div>

                <?php if (!$is_filtered): ?>
                    <section id="videos">
                        <h2 class="section-header">Latest <span style="color: var(--accent-cyan);">Videos</span></h2>

                        <?php if ($video_query && mysqli_num_rows($video_query) > 0): ?>
                            <div class="media-grid large">
                                <?php while ($video = mysqli_fetch_assoc($video_query)): ?>
                                    <div class="premium-media-card fade-on-scroll">
                                        <div class="card-image" style="padding-top: 56.25%;"> <!-- 16:9 aspect ratio for videos -->
                                            <?php if ($video['image']): ?>
                                                <img src="<?php echo preg_match('/^https?:\\/\\//i', $video['image']) ? htmlspecialchars($video['image']) : 'uploads/videos/images/' . htmlspecialchars($video['image']); ?>" alt="Cover" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; object-fit: cover;">
                                            <?php else: ?>
                                                <div style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: var(--bg-secondary); display:flex; align-items:center; justify-content:center; font-size: 3rem;"><i data-lucide="video" class="icon-ui"></i></div>
                                            <?php endif; ?>
                                            <div class="play-overlay">
                                                <a href="<?php echo isset($_SESSION['user_id']) ? 'play_video.php?id=' . $video['id'] : 'login.php'; ?>" class="play-icon" style="background: var(--accent-cyan); box-shadow: 0 0 30px var(--accent-cyan-glow); display: flex; align-items: center; justify-content: center;">
                                                    <i data-lucide="play" style="width: 24px; height: 24px; fill: currentColor; margin-left: 4px;"></i>
                                                </a>
                                            </div>
                                        </div>
                                        <div class="card-info">
                                            <h3><?php echo htmlspecialchars($video['title']); ?></h3>
                                            <p><?php echo htmlspecialchars($video['artist_name'] ?? 'Unknown Artist'); ?></p>
                                            <?php if ($video['is_new']): ?>
                                                <span class="badge" style="color: var(--accent-cyan); background: var(--accent-cyan-glow);">NEW</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            </div>
                        <?php else: ?>
                            <p style="color: var(--text-muted);">No videos available yet.</p>
                        <?php endif; ?>

                        <div style="text-align: center; margin-top: 60px; margin-bottom: 60px;">
                            <a href="videos.php" class="btn btn-outline" style="display:inline-flex; align-items:center; border-color:var(--accent-pink); color:var(--accent-pink); border-radius:50px; padding:10px 24px; font-weight:600; text-transform:uppercase; letter-spacing:1px; transition:0.3s;" onmouseover="this.style.background='var(--accent-pink)'; this.style.color='#fff';" onmouseout="this.style.background='transparent'; this.style.color='var(--accent-pink)';">
                            Explore All Videos <i data-lucide="arrow-right" style="width:18px;height:18px;margin-left:8px;"></i>
                            </a>
                        </div>
                    </section>
                <?php endif; ?>
            </div>
        </div>

        <?php include 'includes/footer.php'; ?>

        <script src="assets/js/app.js"></script>

    </div> <!-- End user-main-content -->

</body>

</html>