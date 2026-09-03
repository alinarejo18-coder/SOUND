<?php
session_start();
require_once "../config/db.php";

$site_info_query = mysqli_query($conn, "SELECT * FROM website_info LIMIT 1");
$site_info = mysqli_fetch_assoc($site_info_query);

$current_page = 'artists';

// Fetch genres for navbar dropdown
$nav_genres = [];
$nav_genre_q = mysqli_query($conn, "SELECT * FROM genres ORDER BY genre_name ASC");
if ($nav_genre_q) {
    while ($g = mysqli_fetch_assoc($nav_genre_q)) {
        $nav_genres[] = $g;
    }
}

// Fetch all artists
$artists_query = mysqli_query($conn, "SELECT * FROM artists ORDER BY artist_name ASC");

$artists_to_display = [];
if ($artists_query) {
    while ($artist = mysqli_fetch_assoc($artists_query)) {
        // If the newly added image column is NULL, try to fetch from Deezer API
        if (!isset($artist['image']) || $artist['image'] === null) {
            $api_url = 'https://api.deezer.com/search/artist?q=' . urlencode($artist['artist_name']) . '&limit=1';

            $ch = curl_init($api_url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 3);
            $response = curl_exec($ch);
            curl_close($ch);

            $new_image = '__none__'; // Placeholder string to avoid repeated API calls
            if ($response) {
                $data = json_decode($response, true);
                if (!empty($data['data'][0]['picture_medium'])) {
                    $new_image = $data['data'][0]['picture_medium'];
                }
            }

            // Save it back to the database
            $stmt = mysqli_prepare($conn, "UPDATE artists SET image = ? WHERE id = ?");
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, "si", $new_image, $artist['id']);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
            }

            $artist['image'] = $new_image;
        }

        $artists_to_display[] = $artist;
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Artists - <?php echo htmlspecialchars($site_info['site_name'] ?? 'SOUND'); ?></title>
    <link rel="stylesheet" href="../assets/css/style.css?v=<?= time() ?>">
    <script src="../assets/js/theme.js?v=<?php echo time(); ?>"></script>
    <style>
        .artist-card {
            text-align: center;
            text-decoration: none;
            color: inherit;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 15px;
            transition: transform 0.3s ease;
        }

        .artist-card:hover {
            transform: translateY(-8px);
        }

        .artist-card .img-wrapper {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            overflow: hidden;
            background: var(--bg-secondary);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
            border: 2px solid transparent;
            transition: border-color 0.3s;
        }

        .artist-card:hover .img-wrapper {
            border-color: var(--accent-cyan);
        }

        .artist-card img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .artist-card .placeholder-icon {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            color: #555;
        }

        .artist-card h3 {
            font-size: 1.1rem;
            margin: 0;
            color: var(--text-primary);
        }

        .artist-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 30px;
            margin-top: 30px;
        }

        @media (max-width: 1024px) {
            .artist-grid {
                grid-template-columns: repeat(3, 1fr);
            }
        }

        @media (max-width: 768px) {
            .artist-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 20px;
            }
        }

        @media (max-width: 480px) {
            .artist-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 15px;
            }

            .artist-card .img-wrapper {
                width: 120px;
                height: 120px;
            }
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
            <h2 class="section-header">All <span style="color: var(--accent-cyan);">Artists</span></h2>

            <?php if (count($artists_to_display) > 0): ?>
                <div class="artist-grid">
                    <?php foreach ($artists_to_display as $artist): ?>
                        <a href="../music.php?artist=<?php echo $artist['id']; ?>" class="artist-card">
                            <div class="img-wrapper">
                                <?php
                                $img = $artist['image'];
                                $name = strtolower($artist['artist_name']);
                                $needs_fix = (
                                    empty($img) ||
                                    $img === '__none__' ||
                                    strpos($img, '/artist//250x250') !== false ||
                                    strpos($name, 'ahmed jahanzeb') !== false ||
                                    strpos($name, 'casey donahew') !== false ||
                                    strpos($name, 'coldplay') !== false ||
                                    strpos($name, 'ed sheeran') !== false ||
                                    strpos($name, 'glass animals') !== false ||
                                    strpos($name, 'rahat fateh ali khan') !== false ||
                                    strpos($name, 'nish asher') !== false ||
                                    strpos($name, 'rajtailorsoundz') !== false
                                );

                                if ($needs_fix) {
                                    if (strpos($name, 'casey donahew') !== false) {
                                        $img = 'https://api.deezer.com/artist/392161/image';
                                    } elseif (strpos($name, 'coldplay') !== false) {
                                        $img = 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcRW4-7KRyaqtvlOCQqJqUcBCjlJwS4vq2dL62x6c4RwLw&s=10';
                                    } elseif (strpos($name, 'ed sheeran') !== false) {
                                        $img = 'https://upload.wikimedia.org/wikipedia/commons/thumb/c/c1/Ed_Sheeran-6886_%28cropped%29.jpg/500px-Ed_Sheeran-6886_%28cropped%29.jpg';
                                    } elseif (strpos($name, 'glass animals') !== false) {
                                        $img = 'https://fastly-s3.allmusic.com/artist/mn0002984484/400/oJzt9gZe0sc9r9VX3Nxa3B_TZlp6n_cq-Emr2zx15tU=.jpg';
                                    } elseif (strpos($name, 'rahat fateh ali khan') !== false) {
                                        $img = 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcRQO4nSN6juFQ-7XRLK03XDSGvP3FtQ6MTI_LMsoXBzjQ&s=10';
                                    } elseif (strpos($name, 'nish asher') !== false) {
                                        $img = 'https://i.scdn.co/image/ab6761610000e5eba46fa5cd34608f6eb6cdbf63';
                                    } elseif (strpos($name, 'rajtailorsoundz') !== false) {
                                        $img = 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcTA3ADC60jsKDVW9Bo6-def4r2GxUUr-a1O-5gii0x3IGUJPGtFxln6uoLL&s=10';
                                    } elseif (strpos($name, 'ahmed jahanzeb') !== false) {
                                        $img = 'https://i.scdn.co/image/ab6761610000e5ebf4498392646021b27dc48968';
                                    } elseif (strpos($name, 'rahat') !== false) {
                                        $img = 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcS1Lgv8dbbxWlgEuI5qYcTEmz8HgmOQ36tAhVWi2egBbQ&s=10';
                                    } elseif (strpos($name, 'luis fonsi') !== false) {
                                        $img = 'https://e-cdns-images.dzcdn.net/images/artist/db13837cc4442f835ceaeeeb67e9dc98/250x250-000000-80-0-0.jpg';
                                    } elseif (strpos($name, 'lauren babic') !== false) {
                                        $img = 'https://e-cdns-images.dzcdn.net/images/artist/10fc34032630cbc92d5e64dc9cfcc155/250x250-000000-80-0-0.jpg';
                                    } elseif (strpos($name, 'hozier') !== false) {
                                        $img = 'https://e-cdns-images.dzcdn.net/images/artist/4970da41372f40bb2d85b71d41c359e8/250x250-000000-80-0-0.jpg';
                                    } elseif (strpos($name, 'shae gill') !== false) {
                                        $img = 'https://e-cdns-images.dzcdn.net/images/artist/b9d5c3c6b3e5e0e75bda061ada8995a9/250x250-000000-80-0-0.jpg';
                                    } elseif (strpos($name, 'asim azhar') !== false) {
                                        $img = 'https://e-cdns-images.dzcdn.net/images/artist/52a08f78d491088013cf8b68a13ae8b9/250x250-000000-80-0-0.jpg';
                                    } elseif (strpos($name, 'acoustic remedies') !== false) {
                                        $img = 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcSckZMxlXiFCiwRPCurLTakaYPrzWQaX1Hf9fwvh-79BU5BoA1sbqzSyTe3&s=10';
                                    } elseif (strpos($name, 'pritam') !== false) {
                                        $img = 'https://e-cdns-images.dzcdn.net/images/artist/d4914ccd414067cd5e2c108867079a85/250x250-000000-80-0-0.jpg';
                                    } elseif (strpos($name, 'bohemia') !== false) {
                                        $img = 'https://e-cdns-images.dzcdn.net/images/artist/f52365b4cc64affef301afdf89af8d5f/250x250-000000-80-0-0.jpg';
                                    } elseif (strpos($name, 'jubin nautiyal') !== false) {
                                        $img = 'https://e-cdns-images.dzcdn.net/images/artist/9031b483049b5551abcb11529873b4f6/250x250-000000-80-0-0.jpg';
                                    }
                                }
                                ?>
                                <?php if (!empty($img) && $img !== '__none__' && strpos($img, '/artist//250x250') === false): ?>
                                    <img src="<?php echo preg_match('/^https?:\/\//i', $img) ? htmlspecialchars($img) : '../uploads/artists/' . htmlspecialchars($img); ?>" alt="Artist">
                                <?php else: ?>
                                    <div class="placeholder-icon"><i data-lucide="user" class="icon-ui"></i></div>
                                <?php endif; ?>
                            </div>
                            <h3><?php echo htmlspecialchars($artist['artist_name']); ?></h3>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p style="color: var(--text-muted);">No artists found.</p>
            <?php endif; ?>
        </div>

        <?php include '../includes/footer.php'; ?>

    </div> <!-- End user-main-content -->

    <script src="../assets/js/app.js"></script>
</body>

</html>