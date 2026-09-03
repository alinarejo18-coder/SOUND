<?php
session_start();
require_once "../config/db.php";

$site_info_query = mysqli_query($conn, "SELECT * FROM website_info LIMIT 1");
$site_info = mysqli_fetch_assoc($site_info_query);

$current_page = 'languages';

// Fetch genres for navbar dropdown
$nav_genres = [];
$nav_genre_q = mysqli_query($conn, "SELECT * FROM genres ORDER BY genre_name ASC");
if ($nav_genre_q) {
    while($g = mysqli_fetch_assoc($nav_genre_q)) {
        $nav_genres[] = $g;
    }
}

// Fetch all languages
$languages_query = mysqli_query($conn, "SELECT * FROM languages ORDER BY language_name ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Languages - <?php echo htmlspecialchars($site_info['site_name'] ?? 'SOUND'); ?></title>
    <link rel="stylesheet" href="../assets/css/style.css?v=<?= time() ?>">
    <script src="../assets/js/theme.js?v=<?php echo time(); ?>"></script>
    <style>
        .language-card {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 30px 20px;
            text-align: center;
            text-decoration: none;
            color: var(--text-primary);
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .language-card:hover {
            transform: translateY(-5px);
            border-color: var(--accent-cyan);
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
            color: var(--accent-cyan);
        }
        .language-card i {
            font-size: 2.5rem;
        }
        .language-card h3 {
            margin: 0;
            font-size: 1.2rem;
            font-weight: 600;
        }
    </style>
</head>
<body>


    <?php if (isset($_SESSION['user_id'])) {
        include '../includes/user_sidebar.php';
    } ?>
    <div class="<?php echo isset($_SESSION['user_id']) ? 'user-main-content main-wrapper' : 'main-wrapper'; ?>">

<div class="animated-bg"></div>

<?php include '../includes/navbar.php'; ?>

<div class="container" style="max-width: 1200px; margin: 40px auto; padding: 20px; min-height: 60vh;">
    <h2 class="section-header">All <span style="color: var(--accent-cyan);">Languages</span></h2>
    
    <?php if (mysqli_num_rows($languages_query) > 0): ?>
        <div class="media-grid" style="margin-top: 30px;">
            <?php while($lang = mysqli_fetch_assoc($languages_query)): ?>
                <a href="../music.php?language=<?php echo $lang['id']; ?>" class="language-card fade-on-scroll">
                    <i data-lucide="globe" class="icon-ui"></i>
                    <h3><?php echo htmlspecialchars($lang['language_name']); ?></h3>
                </a>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <p style="color: var(--text-muted);">No languages found.</p>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>

</div> <!-- End user-main-content -->

<script src="../assets/js/app.js"></script>
</body>
</html>

