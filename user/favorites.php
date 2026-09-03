<?php
session_start();
require_once "../config/db.php";
require_once "../includes/auth.php";

requireLogin();

$site_info_query = mysqli_query($conn, "SELECT * FROM website_info LIMIT 1");
$site_info = mysqli_fetch_assoc($site_info_query);

$current_page = 'favorites';
$user_id = $_SESSION['user_id'];

// Fetch genres for navbar dropdown
$nav_genres = [];
$nav_genre_q = mysqli_query($conn, "SELECT * FROM genres ORDER BY genre_name ASC");
if ($nav_genre_q) {
    while ($g = mysqli_fetch_assoc($nav_genre_q)) {
        $nav_genres[] = $g;
    }
}

// Fetch liked music
$liked_query = "
    SELECT m.*, a.artist_name, al.album_name 
    FROM music m 
    JOIN ratings r ON m.id = r.music_id 
    LEFT JOIN artists a ON m.artist_id = a.id 
    LEFT JOIN albums al ON m.album_id = al.id 
    WHERE r.user_id = $user_id AND r.rating > 0 
    ORDER BY r.id DESC
";
$liked_result = mysqli_query($conn, $liked_query);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Liked Songs - <?php echo htmlspecialchars($site_info['site_name'] ?? 'SOUND'); ?></title>
    <link rel="stylesheet" href="../assets/css/style.css?v=<?= time() ?>">
    <script src="../assets/js/theme.js?v=<?php echo time(); ?>"></script>
</head>

<body>

    <?php if (isset($_SESSION['user_id'])) {
        include '../includes/user_sidebar.php';
    } ?>
    <div class="<?php echo isset($_SESSION['user_id']) ? 'user-main-content main-wrapper' : 'main-wrapper'; ?>">

        <div class="animated-bg"></div>

        <?php include '../includes/navbar.php'; ?>

        <div class="container" style="max-width: 1200px; margin: 40px auto; padding: 20px; min-height: 60vh;">
            <h2 class="section-header">Liked <span style="color: var(--accent-cyan);">Songs</span></h2>

            <?php if ($liked_result && mysqli_num_rows($liked_result) > 0): ?>
                <div class="media-grid">
                    <?php while ($m = mysqli_fetch_assoc($liked_result)): ?>
                        <div class="music-card fade-on-scroll" onclick="window.location.href='../play_music.php?id=<?php echo $m['id']; ?>'">
                            <div class="card-image">
                                <?php if ($m['image']): ?>
                                    <img src="<?php echo preg_match('/^https?:\/\//i', $m['image']) ? htmlspecialchars($m['image']) : '../uploads/music/images/' . htmlspecialchars($m['image']); ?>" alt="Cover">
                                <?php else: ?>
                                    <div class="placeholder-icon"><i data-lucide="music" class="icon-ui"></i></div>
                                <?php endif; ?>
                                <a href="../play_music.php?id=<?php echo $m['id']; ?>" class="play-btn-circle" onclick="event.stopPropagation();">
                                    <i data-lucide="play" style="width: 24px; height: 24px; fill: currentColor; margin-left: 4px;"></i>
                                </a>
                            </div>
                            <div class="card-info">
                                <h3><?php echo htmlspecialchars($m['title']); ?></h3>
                                <p><?php echo htmlspecialchars($m['artist_name'] ?? 'Unknown Artist'); ?></p>
                                <?php if ($m['is_new']): ?>
                                    <span class="badge" style="color: var(--accent-cyan); background: var(--accent-cyan-glow);">NEW</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div style="text-align: center; padding: 50px; background: var(--bg-card); border-radius: 12px; border: 1px solid var(--border-color);">
                    <i data-lucide="heart" style="font-size: 48px; color: var(--text-muted); margin-bottom: 20px; display: block; margin-left: auto; margin-right: auto; width: 48px; height: 48px;"></i>
                    <h3 style="color: var(--text-primary); margin-bottom: 10px;">No Liked Songs</h3>
                    <p style="color: var(--text-muted); margin-bottom: 20px;">You haven't liked any songs yet. Go discover some music!</p>
                    <a href="../music.php" style="display: inline-block; background: var(--accent-cyan); color: #000; padding: 10px 20px; border-radius: 20px; text-decoration: none; font-weight: bold;">Browse Music</a>
                </div>
            <?php endif; ?>
        </div>

        <?php include '../includes/footer.php'; ?>

    </div> <!-- End user-main-content -->

    <script src="../assets/js/app.js"></script>
</body>

</html>

