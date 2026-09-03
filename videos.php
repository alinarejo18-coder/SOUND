<?php
session_start();
require_once "config/db.php";

$site_info_query = mysqli_query($conn, "SELECT * FROM website_info LIMIT 1");
$site_info = mysqli_fetch_assoc($site_info_query);

// Fetch genres for navbar dropdown
$nav_genres = [];
$nav_genre_q = mysqli_query($conn, "SELECT * FROM genres ORDER BY genre_name ASC");
if ($nav_genre_q) {
    while($g = mysqli_fetch_assoc($nav_genre_q)) {
        $nav_genres[] = $g;
    }
}

// Fetch filter options (artist, year, language only)
$filter_artists = mysqli_query($conn, "SELECT id, artist_name FROM artists ORDER BY artist_name ASC");
$filter_years = mysqli_query($conn, "SELECT id, year_value FROM years ORDER BY year_value DESC");
$filter_languages = mysqli_query($conn, "SELECT id, language_name FROM languages ORDER BY language_name ASC");

$video_query_str = "SELECT v.*, a.artist_name 
     FROM videos v 
     LEFT JOIN artists a ON v.artist_id = a.id 
     WHERE 1=1";

$params = [];
$types = "";
$is_filtered = false;

if (!empty($_GET['artist'])) {
    $video_query_str .= " AND v.artist_id = ?";
    $params[] = intval($_GET['artist']);
    $types .= "i";
    $is_filtered = true;
}
if (!empty($_GET['genre'])) {
    $video_query_str .= " AND v.genre_id = ?";
    $params[] = intval($_GET['genre']);
    $types .= "i";
    $is_filtered = true;
}
if (!empty($_GET['year'])) {
    $video_query_str .= " AND v.year_id = ?";
    $params[] = intval($_GET['year']);
    $types .= "i";
    $is_filtered = true;
}
if (!empty($_GET['language'])) {
    $video_query_str .= " AND v.language_id = ?";
    $params[] = intval($_GET['language']);
    $types .= "i";
    $is_filtered = true;
}

$video_query_str .= " ORDER BY v.created_at DESC";

$stmt = mysqli_prepare($conn, $video_query_str);
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
    $video_query = mysqli_stmt_get_result($stmt);
} else {
    $video_query = false;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Videos - <?php echo htmlspecialchars($site_info['site_name'] ?? 'SOUND'); ?></title>
    <link rel="stylesheet" href="assets/css/style.css?v=<?= time() ?>">
    <script src="assets/js/theme.js?v=<?php echo time(); ?>"></script>
    <style>
        .animated-bg {
            background: radial-gradient(circle at 12% 8%, rgba(255, 49, 49, .15), transparent 29rem), radial-gradient(circle at 86% 0%, rgba(30, 215, 96, .12), transparent 27rem), #0f0f10 !important;
        }
    </style>
</head>
<body>

<?php if (isset($_SESSION['user_id'])) { include 'includes/user_sidebar.php'; } ?>
<div class="<?php echo isset($_SESSION['user_id']) ? 'user-main-content main-wrapper' : 'main-wrapper'; ?>">

<div class="animated-bg"></div>

<?php include 'includes/navbar.php'; ?>

<?php if (!$is_filtered): ?>
<div class="hero-premium-sm" style="min-height: auto; height: auto; padding-top: 100px; padding-bottom: 25px; margin-bottom: 10px; align-items: flex-start;">
    <div class="hero-content-sm">
        <h1 style="color: #ffffff; margin-top: 0; margin-bottom: 10px;">Immerse Yourself in <span class="highlight">Visuals</span></h1>
        <p style="margin: 0;">Watch the latest and most trending entertainment videos, music clips, and exclusive content.</p>
    </div>
</div>
<?php else: ?>
<div class="filtered-header" style="padding: 100px 20px 10px; text-align: center;">
    <h2 style="color: #ffffff; margin: 0;">Filtered Videos</h2>
</div>
<?php endif; ?>

<div class="container" style="max-width: 1200px; margin: 0 auto 40px auto; padding: 10px 20px 20px 20px;">

    <form method="GET" action="videos.php" class="premium-filter-container">
        <div class="filter-group-premium">
            <i data-lucide="user" class="filter-icon-left"></i>
            <label for="video-filter-artist" class="filter-label-small">Artist</label>
            <select name="artist" class="filter-input-premium" id="video-filter-artist">
                <option value="">All Artists</option>
                <?php if($filter_artists) { while($row = mysqli_fetch_assoc($filter_artists)) { ?>
                    <option value="<?php echo $row['id']; ?>" <?php echo (isset($_GET['artist']) && $_GET['artist'] == $row['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($row['artist_name']); ?></option>
                <?php } } ?>
            </select>
            <i data-lucide="chevron-down" class="filter-chevron-right"></i>
        </div>
        <div class="filter-group-premium">
            <i data-lucide="calendar" class="filter-icon-left"></i>
            <label for="video-filter-year" class="filter-label-small">Year</label>
            <select name="year" class="filter-input-premium" id="video-filter-year">
                <option value="">All Years</option>
                <?php if($filter_years) { while($row = mysqli_fetch_assoc($filter_years)) { ?>
                    <option value="<?php echo $row['id']; ?>" <?php echo (isset($_GET['year']) && $_GET['year'] == $row['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($row['year_value']); ?></option>
                <?php } } ?>
            </select>
            <i data-lucide="chevron-down" class="filter-chevron-right"></i>
        </div>
        <div class="filter-group-premium">
            <i data-lucide="globe" class="filter-icon-left"></i>
            <label for="video-filter-language" class="filter-label-small">Language</label>
            <select name="language" class="filter-input-premium" id="video-filter-language">
                <option value="">All Languages</option>
                <?php if($filter_languages) { while($row = mysqli_fetch_assoc($filter_languages)) { ?>
                    <option value="<?php echo $row['id']; ?>" <?php echo (isset($_GET['language']) && $_GET['language'] == $row['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($row['language_name']); ?></option>
                <?php } } ?>
            </select>
            <i data-lucide="chevron-down" class="filter-chevron-right"></i>
        </div>
        <div class="filter-actions-premium">
            <button type="submit" class="btn-premium-apply"><i data-lucide="filter"></i> Apply Filters</button>
            <a href="videos.php" class="btn-premium-clear" id="video-filter-clear"><i data-lucide="refresh-cw"></i> Clear / Reset Filters</a>
        </div>
    </form>

    <div id="video-results-container">
        <?php
        if (isset($_GET['ajax']) && $_GET['ajax'] == '1') {
            while (ob_get_level()) { ob_end_clean(); }
            ob_start();
        }
        ?>
    <section id="videos">
        <h2 class="section-header">All <span style="color: var(--accent-cyan);">Videos</span></h2>
        
        <?php if (mysqli_num_rows($video_query) > 0): ?>
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
                                <a href="play_video.php?id=<?php echo $video['id']; ?>" class="play-icon" style="background: var(--accent-cyan); box-shadow: 0 0 30px var(--accent-cyan-glow);">
                                    <i data-lucide="play" style="width: 24px; height: 24px; fill: currentColor;"></i>
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
            <p style="color: var(--text-muted);"><?php echo $is_filtered ? 'No videos found for the selected filters.' : 'No videos available yet.'; ?></p>
        <?php endif; ?>
    </section>
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

</div> <!-- End user-main-content -->

</body>
</html>

