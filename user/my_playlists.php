<?php
session_start();
require_once "../config/db.php";
require_once "../includes/auth.php";

requireLogin();

$site_info_query = mysqli_query($conn, "SELECT * FROM website_info LIMIT 1");
$site_info = mysqli_fetch_assoc($site_info_query);

$current_page = 'my_playlists';

$user_id = $_SESSION['user_id'];

// Fetch user's playlists
$playlists_query = mysqli_query($conn, "SELECT * FROM playlists WHERE user_id = $user_id ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Playlists - <?php echo htmlspecialchars($site_info['site_name'] ?? 'SOUND'); ?></title>
    <link rel="stylesheet" href="../assets/css/style.css?v=<?= time() ?>">
    <script src="../assets/js/theme.js?v=<?php echo time(); ?>"></script>
    <style>
        .playlist-card {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 20px;
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
        .playlist-card:hover {
            transform: translateY(-5px);
            border-color: var(--accent-cyan);
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
        }
        .playlist-card img {
            width: 120px;
            height: 120px;
            object-fit: cover;
            border-radius: 8px;
            background: #242424;
        }
        .playlist-card .placeholder {
            width: 120px;
            height: 120px;
            border-radius: 8px;
            background: #242424;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            color: #555;
        }
        .playlist-card h3 {
            margin: 0;
            font-size: 1.1rem;
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
    <h2 class="section-header">My <span style="color: var(--accent-cyan);">Playlists</span></h2>
    
    <?php if (mysqli_num_rows($playlists_query) > 0): ?>
        <div class="media-grid" style="margin-top: 30px;">
            <?php while($pl = mysqli_fetch_assoc($playlists_query)): ?>
                <a href="playlist.php?id=<?php echo $pl['id']; ?>" class="playlist-card fade-on-scroll">
                    <?php if ($pl['cover_image']): ?>
                        <img src="../uploads/playlists/<?php echo htmlspecialchars($pl['cover_image']); ?>" alt="Cover">
                    <?php else: ?>
                        <div class="placeholder"><i data-lucide="list-music" class="icon-ui"></i></div>
                    <?php endif; ?>
                    <h3><?php echo htmlspecialchars($pl['title']); ?></h3>
                </a>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <div style="text-align: center; padding: 50px; background: var(--bg-card); border-radius: 12px; border: 1px solid var(--border-color);">
            <i data-lucide="list-music" style="font-size: 48px; color: var(--text-muted); margin-bottom: 20px; display: block; margin-left: auto; margin-right: auto; width: 48px; height: 48px;"></i>
            <h3 style="color: var(--text-primary); margin-bottom: 10px;">No Playlists Yet</h3>
            <p style="color: var(--text-muted); margin-bottom: 20px;">Create your first playlist from your dashboard to get started.</p>
            <a href="dashboard.php" style="display: inline-block; background: var(--accent-cyan); color: #000; padding: 10px 20px; border-radius: 20px; text-decoration: none; font-weight: bold;">Go to Dashboard</a>
        </div>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>

</div> <!-- End user-main-content -->

<script src="../assets/js/app.js"></script>
</body>
</html>

