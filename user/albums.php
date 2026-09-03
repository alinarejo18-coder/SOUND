<?php
session_start();
require_once "../config/db.php";

$site_info_query = mysqli_query($conn, "SELECT * FROM website_info LIMIT 1");
$site_info = mysqli_fetch_assoc($site_info_query);

$current_page = 'albums';

// Fetch genres for navbar dropdown
$nav_genres = [];
$nav_genre_q = mysqli_query($conn, "SELECT * FROM genres ORDER BY genre_name ASC");
if ($nav_genre_q) {
    while ($g = mysqli_fetch_assoc($nav_genre_q)) {
        $nav_genres[] = $g;
    }
}

// Fetch all albums with artist names
$albums_query = mysqli_query($conn, "
    SELECT al.*, a.artist_name 
    FROM albums al 
    LEFT JOIN artists a ON al.artist_id = a.id 
    ORDER BY al.album_name ASC
");

$albums_to_display = [];
if ($albums_query) {
    while ($album = mysqli_fetch_assoc($albums_query)) {
        // If the newly added image column is NULL, try to fetch from Deezer API
        if (!isset($album['image']) || $album['image'] === null) {
            $search_query = $album['album_name'];
            if (!empty($album['artist_name'])) {
                $search_query .= ' ' . $album['artist_name'];
            }
            $api_url = 'https://api.deezer.com/search/album?q=' . urlencode($search_query) . '&limit=1';

            $ch = curl_init($api_url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 3);
            $response = curl_exec($ch);
            curl_close($ch);

            $new_image = '__none__'; // Placeholder string to avoid repeated API calls
            if ($response) {
                $data = json_decode($response, true);
                if (!empty($data['data'][0]['cover_medium'])) {
                    $new_image = $data['data'][0]['cover_medium'];
                }
            }

            // Save it back to the database
            $stmt = mysqli_prepare($conn, "UPDATE albums SET image = ? WHERE id = ?");
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, "si", $new_image, $album['id']);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
            }
            $album['image'] = $new_image;
        }

        // Custom overrides for missing album covers
        $id = $album['id'];
        if ($id == 1) $album['image'] = 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcTS1rDtYAxa90rEH9kaE4Nb-n9VPaVnyXmCIRWqyauL9SbS68MdPeyWVlY&s=10'; // Khuda Aur Mohabbat
        elseif ($id == 5) $album['image'] = 'https://cdn-images.dzcdn.net/images/cover/4c1bfb589c432b2c82aa0eeb04c52f1b/0x1900-000000-80-0-0.jpg'; // Despacito
        elseif ($id == 14) $album['image'] = 'https://i.scdn.co/image/ab67616d0000b2738a3f0a3ca7929dea23cd274c'; // Lovely
        elseif ($id == 16) $album['image'] = 'https://cdn-images.dzcdn.net/images/cover/016f3117546e2bcc4229142c7268fb04/1900x1900-000000-80-0-0.jpg'; // Counting Stars
        elseif ($id == 18) $album['image'] = 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcTUWGZ-iq9jpoVfUObQ6-ttiM-EjCTQOFUvAXJr6cG1HXUi_YEriFbpHzs&s=10'; // Bad Guy
        elseif ($id == 20) $album['image'] = 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcTf-gN860vNeo-Do7jf0hUNy8JhjHiFiEb2Qc6dDIBIw7JRrrI3YVIDIOHw&s=10'; // PRISM
        elseif ($id == 21) $album['image'] = 'https://i.scdn.co/image/ab67616d00001e022bf0876d42b90a8852ad6244'; // Until I Found You
        elseif ($id == 22) $album['image'] = 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcRNM2su0uW5QaesaWacf11WmJMv4IfhDeUnF5lPDBWQji-qEhP41-EtRwlb&s=10'; // Tera Mera Hai Pyar Amar
        elseif ($id == 23) $album['image'] = 'https://www.dvdplanetstore.pk/wp-content/uploads/2024/01/iJgahmVRiy7zxLXzqjzpt2R0HVI-600x900.jpg'; // Khaani
        elseif ($id == 24) $album['image'] = 'https://c.files.bbci.co.uk/2DE6/production/_124905711_11.jpg'; // Pasoori
        elseif ($id == 25) $album['image'] = 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcQbkCdGlHhbiIfWiioM8rUd4Sk8P0LsZ4EsRZOVvoOc3A&s=10'; // Meri Zindagi Hai Tu
        elseif ($id == 27) $album['image'] = 'https://m.media-amazon.com/images/M/MV5BNWE0OTE0ODUtY2Q0ZS00NDQxLTlhMmItYjM1MmI3MDVkMjNhXkEyXkFqcGc@._V1_QL75_UY207_CR2,0,140,207_.jpg'; // Satyameva Jayate 2
        elseif ($id == 28) $album['image'] = 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcRk5KrQiJS8zWMFCOseY0MN4ePf6iLvuQ1IBA8xqisPObAyS1Sw9RyKjKk&s=10'; // Tere Bin

        $albums_to_display[] = $album;
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Albums - <?php echo htmlspecialchars($site_info['site_name'] ?? 'SOUND'); ?></title>
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
            <h2 class="section-header">All <span style="color: var(--accent-cyan);">Albums</span></h2>

            <?php if (count($albums_to_display) > 0): ?>
                <div class="media-grid">
                    <?php foreach ($albums_to_display as $album): ?>
                        <div class="music-card fade-on-scroll" onclick="window.location.href='../music.php?album=<?php echo $album['id']; ?>'">
                            <div class="card-image">
                                <?php if (!empty($album['image']) && $album['image'] !== '__none__'): ?>
                                    <img src="<?php echo preg_match('/^https?:\/\//i', $album['image']) ? htmlspecialchars($album['image']) : '../uploads/albums/' . htmlspecialchars($album['image']); ?>" alt="Album">
                                <?php else: ?>
                                    <div class="placeholder-icon"><i data-lucide="disc" class="icon-ui"></i></div>
                                <?php endif; ?>
                                <a href="../music.php?album=<?php echo $album['id']; ?>" class="play-btn-circle" onclick="event.stopPropagation();">
                                    <i data-lucide="play" style="width: 24px; height: 24px; fill: currentColor; margin-left: 4px;"></i>
                                </a>
                            </div>
                            <div class="card-info">
                                <h3><?php echo htmlspecialchars($album['album_name']); ?></h3>
                                <p><?php echo htmlspecialchars($album['artist_name'] ?? 'Unknown Artist'); ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p style="color: var(--text-muted);">No albums found.</p>
            <?php endif; ?>
        </div>

        <?php include '../includes/footer.php'; ?>

    </div> <!-- End user-main-content -->

    <script src="../assets/js/app.js"></script>
</body>

</html>