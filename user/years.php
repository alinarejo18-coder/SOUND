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

$year_palette = [
    '2026' => ['#8b5cf6', '#ec4899'],
    '2025' => ['#14b8a6', '#22c55e'],
    '2024' => ['#3b82f6', '#06b6d4'],
    '2023' => ['#f59e0b', '#f97316'],
    '2022' => ['#ef4444', '#f43f5e'],
    '2021' => ['#8b5cf6', '#6366f1'],
    '2020' => ['#10b981', '#0ea5e9'],
    '2019' => ['#f97316', '#fb7185'],
    '2018' => ['#84cc16', '#22c55e'],
    '2017' => ['#38bdf8', '#3b82f6'],
    '2016' => ['#facc15', '#f59e0b'],
    '2015' => ['#f472b6', '#ec4899'],
    '2014' => ['#a78bfa', '#8b5cf6'],
    '2013' => ['#14b8a6', '#2dd4bf'],
    '2012' => ['#fb7185', '#f43f5e'],
    '2011' => ['#60a5fa', '#2563eb'],
    '2010' => ['#f59e0b', '#eab308'],
    '2009' => ['#22c55e', '#16a34a'],
    '2008' => ['#f97316', '#ef4444'],
    '2007' => ['#8b5cf6', '#a78bfa'],
];
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
            border-radius: 16px;
            padding: 18px 18px 22px;
            text-align: center;
            text-decoration: none;
            color: var(--text-primary);
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 12px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.12);
            overflow: hidden;
        }
        .year-card:hover {
            transform: translateY(-5px);
            border-color: var(--accent-cyan);
            box-shadow: 0 18px 35px rgba(0,0,0,0.22);
            color: var(--accent-cyan);
        }
        .year-art {
            width: 100%;
            height: 120px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.8rem;
            box-shadow: inset 0 1px 0 rgba(255,255,255,0.2);
            background-size: cover;
            background-position: center;
            position: relative;
            overflow: hidden;
        }
        .year-art::after {
            content: "";
            position: absolute;
            inset: 0;
            background: linear-gradient(180deg, rgba(255,255,255,0.08), rgba(0,0,0,0.2));
        }
        .year-art span {
            position: relative;
            z-index: 1;
            filter: drop-shadow(0 8px 12px rgba(0,0,0,0.2));
        }
        .year-card h3 {
            margin: 0;
            font-size: 1.4rem;
            font-weight: 800;
            letter-spacing: 0.2px;
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
                <?php
                    $year_value = trim((string) ($year['year_value'] ?? ''));
                    [$color_a, $color_b] = $year_palette[$year_value] ?? ['#8b5cf6', '#06b6d4'];
                ?>
                <a href="../music.php?year=<?php echo $year['id']; ?>" class="year-card fade-on-scroll">
                    <div class="year-art" style="background: linear-gradient(135deg, <?php echo $color_a; ?>, <?php echo $color_b; ?>);">
                        <span>📅</span>
                    </div>
                    <h3><?php echo htmlspecialchars($year_value); ?></h3>
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

