<?php
session_start();
require_once "../config/db.php";

$site_info_query = mysqli_query($conn, "SELECT * FROM website_info LIMIT 1");
$site_info = mysqli_fetch_assoc($site_info_query);

$current_page = 'categories';

// Fetch genres for navbar dropdown
$nav_genres = [];
$nav_genre_q = mysqli_query($conn, "SELECT * FROM genres ORDER BY genre_name ASC");
if ($nav_genre_q) {
    while($g = mysqli_fetch_assoc($nav_genre_q)) {
        $nav_genres[] = $g;
    }
}

// Fetch all genres for display
$genres_query = mysqli_query($conn, "SELECT * FROM genres ORDER BY genre_name ASC");

$genre_icon_map = [
    'Pop' => '♫',
    'Rock' => '🎸',
    'R&B/Soul' => '🎤',
    'Alternative' => '🎧',
    'Dance' => '💃',
    'Urbano latino' => '🔥',
    'Soundtrack' => '🎬',
    'Country' => '🤠',
    'Pop Punk' => '⚡',
    'Children\'s Music' => '🌈',
    'Hip-Hop/Rap' => '🎵',
    'Singer/Songwriter' => '✍️',
    'Urdu' => '🌙',
    'Hindi' => '🎶',
    'English' => '🎼',
    'World' => '🌍',
    'Worldwide' => '🌐',
    'K-Pop' => '✨',
    'Bollywood' => '🎭',
];

$genre_palette = [
    'Pop' => ['#8b5cf6', '#ec4899'],
    'Rock' => ['#f97316', '#ef4444'],
    'R&B/Soul' => ['#ec4899', '#8b5cf6'],
    'Alternative' => ['#22c55e', '#06b6d4'],
    'Dance' => ['#f59e0b', '#ec4899'],
    'Urbano latino' => ['#10b981', '#14b8a6'],
    'Soundtrack' => ['#3b82f6', '#8b5cf6'],
    'Country' => ['#84cc16', '#16a34a'],
    'Pop Punk' => ['#f43f5e', '#fb7185'],
    'Children\'s Music' => ['#38bdf8', '#a78bfa'],
    'Hip-Hop/Rap' => ['#f59e0b', '#f97316'],
    'Singer/Songwriter' => ['#14b8a6', '#22c55e'],
    'Urdu' => ['#7c3aed', '#2563eb'],
    'Hindi' => ['#f97316', '#ef4444'],
    'English' => ['#60a5fa', '#3b82f6'],
    'World' => ['#facc15', '#f97316'],
    'Worldwide' => ['#06b6d4', '#3b82f6'],
    'K-Pop' => ['#ec4899', '#f472b6'],
    'Bollywood' => ['#f59e0b', '#f43f5e'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Categories - <?php echo htmlspecialchars($site_info['site_name'] ?? 'SOUND'); ?></title>
    <link rel="stylesheet" href="../assets/css/style.css?v=<?= time() ?>">
    <script src="../assets/js/theme.js?v=<?php echo time(); ?>"></script>
    <style>
        .genre-card {
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
        .genre-card:hover {
            transform: translateY(-5px);
            border-color: var(--accent-cyan);
            box-shadow: 0 18px 35px rgba(0,0,0,0.22);
            color: var(--accent-cyan);
        }
        .genre-art {
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
        .genre-art::after {
            content: "";
            position: absolute;
            inset: 0;
            background: linear-gradient(180deg, rgba(255,255,255,0.08), rgba(0,0,0,0.2));
        }
        .genre-art span {
            position: relative;
            z-index: 1;
            filter: drop-shadow(0 8px 12px rgba(0,0,0,0.2));
        }
        .genre-card h3 {
            margin: 0;
            font-size: 1.1rem;
            font-weight: 700;
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
    <h2 class="section-header">All <span style="color: var(--accent-cyan);">Categories</span></h2>
    
    <?php if (mysqli_num_rows($genres_query) > 0): ?>
        <div class="media-grid" style="margin-top: 30px;">
            <?php while($genre = mysqli_fetch_assoc($genres_query)): ?>
                <?php
                    $genre_name = trim((string) ($genre['genre_name'] ?? ''));
                    $genre_icon = $genre_icon_map[$genre_name] ?? '♫';
                    [$color_a, $color_b] = $genre_palette[$genre_name] ?? ['#8b5cf6', '#06b6d4'];
                ?>
                <a href="../music.php?genre=<?php echo $genre['id']; ?>" class="genre-card fade-on-scroll">
                    <div class="genre-art" style="background: linear-gradient(135deg, <?php echo $color_a; ?>, <?php echo $color_b; ?>);">
                        <span><?php echo htmlspecialchars($genre_icon); ?></span>
                    </div>
                    <h3><?php echo htmlspecialchars($genre_name); ?></h3>
                </a>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <p style="color: var(--text-muted);">No categories found.</p>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>

</div> <!-- End user-main-content -->

<script src="../assets/js/app.js"></script>
</body>
</html>

