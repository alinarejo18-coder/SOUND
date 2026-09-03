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

$search_query = isset($_GET['q']) ? trim($_GET['q']) : '';
$music_results = [];
$video_results = [];

if ($search_query !== '') {
    $search_term = "%" . $search_query . "%";
    
    // Search Music - with all fields: Title, Artist, Album, Year, Genre, Language
    $m_stmt = mysqli_prepare($conn, "
        SELECT DISTINCT m.*, a.artist_name 
        FROM music m 
        LEFT JOIN artists a ON m.artist_id = a.id 
        LEFT JOIN albums al ON m.album_id = al.id
        LEFT JOIN years y ON m.year_id = y.id
        LEFT JOIN genres g ON m.genre_id = g.id
        LEFT JOIN languages l ON m.language_id = l.id
        WHERE m.title LIKE ? 
           OR a.artist_name LIKE ?
           OR al.album_name LIKE ?
           OR CAST(y.year_value AS CHAR) LIKE ?
           OR g.genre_name LIKE ?
           OR l.language_name LIKE ?
        ORDER BY m.created_at DESC
    ");
    mysqli_stmt_bind_param($m_stmt, "ssssss", $search_term, $search_term, $search_term, $search_term, $search_term, $search_term);
    mysqli_stmt_execute($m_stmt);
    $m_res = mysqli_stmt_get_result($m_stmt);
    while($row = mysqli_fetch_assoc($m_res)) {
        $music_results[] = $row;
    }
    
    // Search Videos - with all fields: Title, Artist, Album, Year, Genre, Language
    $v_stmt = mysqli_prepare($conn, "
        SELECT DISTINCT v.*, a.artist_name 
        FROM videos v 
        LEFT JOIN artists a ON v.artist_id = a.id 
        LEFT JOIN albums al ON v.album_id = al.id
        LEFT JOIN years y ON v.year_id = y.id
        LEFT JOIN genres g ON v.genre_id = g.id
        LEFT JOIN languages l ON v.language_id = l.id
        WHERE v.title LIKE ? 
           OR a.artist_name LIKE ?
           OR al.album_name LIKE ?
           OR CAST(y.year_value AS CHAR) LIKE ?
           OR g.genre_name LIKE ?
           OR l.language_name LIKE ?
        ORDER BY v.created_at DESC
    ");
    mysqli_stmt_bind_param($v_stmt, "ssssss", $search_term, $search_term, $search_term, $search_term, $search_term, $search_term);
    mysqli_stmt_execute($v_stmt);
    $v_res = mysqli_stmt_get_result($v_stmt);
    while($row = mysqli_fetch_assoc($v_res)) {
        $video_results[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Results - <?php echo htmlspecialchars($site_info['site_name'] ?? 'SOUND'); ?></title>
    <link rel="stylesheet" href="assets/css/style.css?v=<?= time() ?>">
    <script src="assets/js/theme.js?v=<?php echo time(); ?>"></script>
</head>
<body>


<div class="<?php echo isset($_SESSION['user_id']) ? 'user-main-content main-wrapper' : 'main-wrapper'; ?>">

    <?php if (isset($_SESSION['user_id'])) {
        include 'includes/user_sidebar.php';
    } ?>

<div class="animated-bg"></div>

<?php include 'includes/navbar.php'; ?>



<div class="filtered-header" style="padding: 100px 20px 10px; text-align: center;">
    <h2 style="color: #ffffff; margin: 0;">Search Results for "<?php echo htmlspecialchars($search_query); ?>"</h2>
</div>

<div class="container" style="max-width: 1200px; margin: 0 auto 40px auto; padding: 10px 20px 20px 20px;">
    
    <?php if ($search_query !== ''): ?>
        <h2 class="section-title">Music Results (<?php echo count($music_results); ?>)</h2>
        <?php if (count($music_results) > 0): ?>
            <div class="media-grid" style="margin-bottom: 50px;">
                <?php foreach ($music_results as $m): ?>
                    <div class="music-card fade-on-scroll" onclick="window.location.href='play_music.php?id=<?php echo $m['id']; ?>'">
                        <div class="card-image">
                            <?php if ($m['image']): ?>
                                <?php 
                                $img_src = $m['image'];
                                if (!preg_match('/^https?:\/\//i', $img_src) && !empty($img_src)) {
                                    $img_src = "uploads/music/images/" . htmlspecialchars($img_src);
                                } else {
                                    $img_src = htmlspecialchars($img_src);
                                }
                                ?>
                                <img src="<?php echo $img_src; ?>" alt="Cover">
                            <?php else: ?>
                                <div class="placeholder-icon"><i data-lucide="music" class="icon-ui"></i></div>
                            <?php endif; ?>
                            <a href="play_music.php?id=<?php echo $m['id']; ?>" class="play-btn-circle" onclick="event.stopPropagation();">
                                <i data-lucide="play" style="width: 24px; height: 24px; fill: currentColor; margin-left: 4px;"></i>
                            </a>
                        </div>
                        <div class="card-info">
                            <h3><?php echo htmlspecialchars($m['title']); ?></h3>
                            <p><?php echo htmlspecialchars($m['artist_name'] ?? 'Unknown Artist'); ?></p>
                            <?php if (isset($m['is_new']) && $m['is_new']): ?>
                                <span class="badge badge-new">NEW</span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state" style="text-align: center; padding: 40px; background: var(--bg-card); border-radius: var(--radius-lg); border: 1px solid var(--border-color); margin-bottom: 50px;">
                <p style="color: var(--text-muted);">No music found for "<?php echo htmlspecialchars($search_query); ?>"</p>
            </div>
        <?php endif; ?>
        
        <h2 class="section-title">Video Results (<?php echo count($video_results); ?>)</h2>
        <?php if (count($video_results) > 0): ?>
            <div class="media-grid large" style="margin-bottom: 50px;">
                <?php foreach ($video_results as $v): ?>
                    <div class="premium-media-card fade-on-scroll">
                        <div class="card-image" style="padding-top: 56.25%;">
                            <?php if (!empty($v['image'])): ?>
                                <?php
                                $v_img = $v['image'];
                                if (!preg_match('/^https?:\/\//i', $v_img)) {
                                    $v_img = "uploads/videos/images/" . htmlspecialchars($v_img);
                                } else {
                                    $v_img = htmlspecialchars($v_img);
                                }
                                ?>
                                <img src="<?php echo $v_img; ?>" alt="Thumbnail" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; object-fit: cover;">
                            <?php else: ?>
                                <div style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: var(--bg-secondary); display:flex; align-items:center; justify-content:center; font-size: 3rem;"><i data-lucide="video" class="icon-ui"></i></div>
                            <?php endif; ?>
                            <div class="play-overlay">
                                <a href="play_video.php?id=<?php echo $v['id']; ?>" class="play-icon" style="background: var(--accent-cyan); box-shadow: 0 0 30px var(--accent-cyan-glow);">
                                    <i data-lucide="play" style="width: 24px; height: 24px; fill: currentColor; margin-left: 4px;"></i>
                                </a>
                            </div>
                        </div>
                        <div class="card-info">
                            <h3><?php echo htmlspecialchars($v['title']); ?></h3>
                            <p><?php echo htmlspecialchars($v['artist_name'] ?? 'Unknown Artist'); ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state" style="text-align: center; padding: 40px; background: var(--bg-card); border-radius: var(--radius-lg); border: 1px solid var(--border-color); margin-bottom: 50px;">
                <p style="color: var(--text-muted);">No videos found for "<?php echo htmlspecialchars($search_query); ?>"</p>
            </div>
        <?php endif; ?>
        
    <?php endif; ?>

</div>

<?php include 'includes/footer.php'; ?>

</div> <!-- End user-main-content -->

</body>
</html>

