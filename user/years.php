<?php
session_start();
require_once "../config/db.php";

$site_info_query = mysqli_query($conn, "SELECT * FROM website_info LIMIT 1");
$site_info = mysqli_fetch_assoc($site_info_query);

$current_page = 'years';

// Fetch genres for navbar dropdown
$nav_genres = [];
$nav_genre_q = mysqli_query($conn, "SELECT * FROM genres ORDER BY genre_name ASC");
if ($nav_genre_q) {
    while($g = mysqli_fetch_assoc($nav_genre_q)) {
        $nav_genres[] = $g;
    }
}

// Fetch all years
$years_query = mysqli_query($conn, "SELECT * FROM years ORDER BY year_value DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Years - <?php echo htmlspecialchars($site_info['site_name'] ?? 'SOUND'); ?></title>
    <link rel="stylesheet" href="../assets/css/style.css?v=<?= time() ?>">
    <script src="../assets/js/theme.js?v=<?php echo time(); ?>"></script>
    <style>
        .year-card {
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
        .year-card:hover {
            transform: translateY(-5px);
            border-color: var(--accent-cyan);
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
            color: var(--accent-cyan);
        }
        .year-card i {
            font-size: 2.5rem;
        }
        .year-card h3 {
            margin: 0;
            font-size: 1.5rem;
            font-weight: 700;
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
    <h2 class="section-header">All <span style="color: var(--accent-cyan);">Release Years</span></h2>
    
    <?php if (mysqli_num_rows($years_query) > 0): ?>
        <div class="media-grid" style="margin-top: 30px;">
            <?php while($year = mysqli_fetch_assoc($years_query)): ?>
                <a href="../music.php?year=<?php echo $year['id']; ?>" class="year-card fade-on-scroll">
                    <i data-lucide="calendar" class="icon-ui"></i>
                    <h3><?php echo htmlspecialchars($year['year_value']); ?></h3>
                </a>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <p style="color: var(--text-muted);">No years found.</p>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>

</div> <!-- End user-main-content -->

<script src="../assets/js/app.js"></script>
</body>
</html>

