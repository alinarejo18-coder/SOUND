<?php
require_once "includes/auth.php";
require_once "config/db.php";
requireLogin();

$site_info_query = mysqli_query($conn, "SELECT * FROM website_info LIMIT 1");
$site_info = mysqli_fetch_assoc($site_info_query);
$nav_genres = [];
$nav_genre_q = mysqli_query($conn, "SELECT * FROM genres ORDER BY genre_name ASC");
if ($nav_genre_q) {
    while ($genre = mysqli_fetch_assoc($nav_genre_q)) {
        $nav_genres[] = $genre;
    }
}

$user_id = $_SESSION['user_id'];
$video_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$message = "";
$error = "";

if ($video_id === 0) {
    die("Invalid video ID.");
}

// Handle Rating & Review Submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['rating'])) {
        $rating = intval($_POST['rating']);
        if ($rating >= 1 && $rating <= 5) {
            // Check if already rated using prepared statement
            $check_stmt = mysqli_prepare($conn, "SELECT id FROM ratings WHERE user_id = ? AND video_id = ?");
            mysqli_stmt_bind_param($check_stmt, "ii", $user_id, $video_id);
            mysqli_stmt_execute($check_stmt);
            $check_result = mysqli_stmt_get_result($check_stmt);
            
            if (mysqli_num_rows($check_result) > 0) {
                // Update existing rating
                $update_stmt = mysqli_prepare($conn, "UPDATE ratings SET rating = ? WHERE user_id = ? AND video_id = ?");
                mysqli_stmt_bind_param($update_stmt, "iii", $rating, $user_id, $video_id);
                mysqli_stmt_execute($update_stmt);
                $message = "Rating updated successfully!";
                mysqli_stmt_close($update_stmt);
            } else {
                // Insert new rating
                $insert_stmt = mysqli_prepare($conn, "INSERT INTO ratings (user_id, video_id, rating) VALUES (?, ?, ?)");
                mysqli_stmt_bind_param($insert_stmt, "iii", $user_id, $video_id, $rating);
                mysqli_stmt_execute($insert_stmt);
                $message = "Rating submitted successfully!";
                mysqli_stmt_close($insert_stmt);
            }
            mysqli_stmt_close($check_stmt);
        } else {
            $error = "Rating must be between 1 and 5.";
        }
    }

    if (isset($_POST['review'])) {
        $review = trim($_POST['review']);
        if (!empty($review)) {
            // Check if review already exists using prepared statement
            $check_rev_stmt = mysqli_prepare($conn, "SELECT id FROM reviews WHERE user_id = ? AND video_id = ?");
            mysqli_stmt_bind_param($check_rev_stmt, "ii", $user_id, $video_id);
            mysqli_stmt_execute($check_rev_stmt);
            $check_rev_result = mysqli_stmt_get_result($check_rev_stmt);
            
            if (mysqli_num_rows($check_rev_result) > 0) {
                // Update existing review
                $update_rev_stmt = mysqli_prepare($conn, "UPDATE reviews SET review = ? WHERE user_id = ? AND video_id = ?");
                mysqli_stmt_bind_param($update_rev_stmt, "sii", $review, $user_id, $video_id);
                if (mysqli_stmt_execute($update_rev_stmt)) {
                    $message = "Review updated successfully!";
                } else {
                    $error = "Failed to update review.";
                }
                mysqli_stmt_close($update_rev_stmt);
            } else {
                // Insert new review
                $insert_rev_stmt = mysqli_prepare($conn, "INSERT INTO reviews (user_id, video_id, review) VALUES (?, ?, ?)");
                mysqli_stmt_bind_param($insert_rev_stmt, "iis", $user_id, $video_id, $review);
                if (mysqli_stmt_execute($insert_rev_stmt)) {
                    $message = "Review submitted successfully!";
                } else {
                    $error = "Failed to submit review.";
                }
                mysqli_stmt_close($insert_rev_stmt);
            }
            mysqli_stmt_close($check_rev_stmt);
        } else {
            $error = "Review cannot be empty.";
        }
    }
}

// Fetch Video Details
$query = "
    SELECT v.*, 
           a.artist_name AS artist_name, 
           al.album_name AS album_name, 
           g.genre_name AS genre_name, 
           l.language_name AS language_name, 
           y.year_value AS year_name 
    FROM videos v
    LEFT JOIN artists a ON v.artist_id = a.id
    LEFT JOIN albums al ON v.album_id = al.id
    LEFT JOIN genres g ON v.genre_id = g.id
    LEFT JOIN languages l ON v.language_id = l.id
    LEFT JOIN years y ON v.year_id = y.id
    WHERE v.id = ?
";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $video_id);
mysqli_stmt_execute($stmt);
$video_result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($video_result) == 0) {
    die("Video not found.");
}
$video = mysqli_fetch_assoc($video_result);
$raw_video_file = trim((string)$video['video_file']);
$youtube_id = '';

if (preg_match('/^youtube:([A-Za-z0-9_-]{11})$/', $raw_video_file, $youtube_match)) {
    $youtube_id = $youtube_match[1];
} elseif (preg_match('/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/i', $raw_video_file, $youtube_match)) {
    $youtube_id = $youtube_match[1];
}

$itunes_video_preview_url = '';
$itunes_preview_url = '';
$itunes_track_only = false;
$unsupported_external_media = false;
$external_video_url = '';

if (strncmp($raw_video_file, 'itunes-video:', 13) === 0) {
    $itunes_video_preview_url = substr($raw_video_file, 13);
} elseif (strncmp($raw_video_file, 'itunes:', 7) === 0) {
    $itunes_track_only = true;
} elseif ($youtube_id === '') {
    if (preg_match('/^(https?:\/\/)?(www\.)?[-a-zA-Z0-9@:%._\+~#=]{1,256}\.[a-zA-Z0-9()]{1,6}\b([-a-zA-Z0-9()@:%_\+.~#?&\/\/=]*)$/i', $raw_video_file)) {
        $url_to_check = preg_match('/^https?:\/\//i', $raw_video_file) ? $raw_video_file : 'https://' . $raw_video_file;
        $url_path = parse_url($url_to_check, PHP_URL_PATH) ?? '';
        $ext = strtolower(pathinfo($url_path, PATHINFO_EXTENSION));
        
        if (in_array($ext, ['mp4', 'm4v', 'webm', 'ogg'])) {
            $external_video_url = $url_to_check;
        } else {
            $itunes_preview_url = $url_to_check;
        }
    } else {
        $unsupported_external_media = preg_match('/^[a-z][a-z0-9+.-]*:/i', $raw_video_file) === 1;
    }
}

$video_extension = strtolower(pathinfo($raw_video_file, PATHINFO_EXTENSION));
$video_mime_types = ['mp4' => 'video/mp4', 'm4v' => 'video/mp4', 'webm' => 'video/webm', 'ogg' => 'video/ogg'];
$video_mime_type = $video_mime_types[$video_extension] ?? 'video/mp4';

$video_filename = '';
$video_directory = '';
$is_local_file = false;

if ($youtube_id === '' && $itunes_video_preview_url === '' && $itunes_preview_url === '' && !$itunes_track_only && !$unsupported_external_media && $external_video_url === '') {
    $video_filename = basename($raw_video_file);
    if ($video_filename !== '') {
        if (is_file(__DIR__ . '/uploads/videos/files/' . $video_filename)) {
            $video_directory = 'uploads/videos/files/';
            $is_local_file = true;
        } elseif (is_file(__DIR__ . '/uploads/videos/' . $video_filename)) {
            $video_directory = 'uploads/videos/';
            $is_local_file = true;
        }
    }
}
$artwork_src = (string)($video['image'] ?? '');
if ($artwork_src !== '' && !preg_match('/^https?:\/\//i', $artwork_src)) {
    $artwork_src = 'uploads/videos/images/' . $artwork_src;
}

// Fetch average rating using prepared statement
$avg_stmt = mysqli_prepare($conn, "SELECT AVG(rating) as avg_rating, COUNT(id) as total_ratings FROM ratings WHERE video_id = ?");
mysqli_stmt_bind_param($avg_stmt, "i", $video_id);
mysqli_stmt_execute($avg_stmt);
$avg_data = mysqli_fetch_assoc(mysqli_stmt_get_result($avg_stmt));
$avg_rating = $avg_data['avg_rating'] ? round($avg_data['avg_rating'], 1) : 0;
$total_ratings = $avg_data['total_ratings'];
mysqli_stmt_close($avg_stmt);

// Fetch user's current rating using prepared statement
$my_rating_stmt = mysqli_prepare($conn, "SELECT rating FROM ratings WHERE user_id = ? AND video_id = ?");
mysqli_stmt_bind_param($my_rating_stmt, "ii", $user_id, $video_id);
mysqli_stmt_execute($my_rating_stmt);
$my_rating_result = mysqli_stmt_get_result($my_rating_stmt);
$my_rating = (mysqli_num_rows($my_rating_result) > 0) ? mysqli_fetch_assoc($my_rating_result)['rating'] : 0;
mysqli_stmt_close($my_rating_stmt);

// Fetch user's current review using prepared statement
$my_rev_stmt = mysqli_prepare($conn, "SELECT review FROM reviews WHERE user_id = ? AND video_id = ?");
mysqli_stmt_bind_param($my_rev_stmt, "ii", $user_id, $video_id);
mysqli_stmt_execute($my_rev_stmt);
$my_rev_result = mysqli_stmt_get_result($my_rev_stmt);
$my_review = (mysqli_num_rows($my_rev_result) > 0) ? mysqli_fetch_assoc($my_rev_result)['review'] : '';
mysqli_stmt_close($my_rev_stmt);

// Fetch all reviews for this video
$reviews_query = "
    SELECT r.review, r.created_at, u.name 
    FROM reviews r 
    JOIN users u ON r.user_id = u.id 
    WHERE r.video_id = $video_id 
    ORDER BY r.created_at DESC
";
$reviews_result = mysqli_query($conn, $reviews_query);

// Fetch Suggested Songs and Videos
$suggested_items = [];
$v_genre = intval($video['genre_id']);
$v_lang = intval($video['language_id']);
$v_artist = intval($video['artist_id']);

$sug_query = "
    (SELECT 'song' as type, m.id, m.title, m.image, a.artist_name,
            (IF(m.genre_id = $v_genre AND m.genre_id IS NOT NULL, 1, 0) + 
             IF(m.language_id = $v_lang AND m.language_id IS NOT NULL, 1, 0) + 
             IF(m.artist_id = $v_artist AND m.artist_id IS NOT NULL, 1, 0)) as relevance
     FROM music m
     LEFT JOIN artists a ON m.artist_id = a.id)
    UNION
    (SELECT 'video' as type, v.id, v.title, v.image, a.artist_name,
            (IF(v.genre_id = $v_genre AND v.genre_id IS NOT NULL, 1, 0) + 
             IF(v.language_id = $v_lang AND v.language_id IS NOT NULL, 1, 0) + 
             IF(v.artist_id = $v_artist AND v.artist_id IS NOT NULL, 1, 0)) as relevance
     FROM videos v
     LEFT JOIN artists a ON v.artist_id = a.id
     WHERE v.id != $video_id)
    ORDER BY relevance DESC, RAND()
    LIMIT 10
";
$sug_res = mysqli_query($conn, $sug_query);
if ($sug_res) {
    while ($row = mysqli_fetch_assoc($sug_res)) {
        $suggested_items[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($video['title']); ?> - SOUND</title>
    <link rel="stylesheet" href="assets/css/style.css?v=<?= time() ?>">
    <style>
        body { background: #0a0a0a; color: #fff; font-family: 'Inter', sans-serif; }
        .play-container { max-width: 100%; margin: 0 auto; padding: 20px; }

        /* Video Player */
        .video-player-wrapper { width: 100%; background: #000; border-radius: 16px; overflow: hidden; margin-bottom: 20px; box-shadow: 0 10px 40px rgba(0,0,0,0.8); position: relative; z-index: 40; pointer-events: auto !important; }
        .video-player-wrapper video { width: 100%; display: block; max-height: 500px; position: relative; z-index: 50; pointer-events: auto !important; }
        .itunes-preview-stage { min-height: 520px; padding: 36px 24px 28px; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 24px; background: radial-gradient(circle at center, #27202d 0%, #101017 65%, #08080c 100%); box-sizing: border-box; }
        .itunes-preview-artwork { width: min(100%, 360px); aspect-ratio: 1; object-fit: cover; border-radius: 12px; box-shadow: 0 18px 45px rgba(0,0,0,.55); }
        .itunes-preview-copy { width: min(100%, 560px); text-align: center; }
        .itunes-preview-copy h2 { margin: 0 0 6px; color: #F8FAFC; font-size: 24px; }
        .itunes-preview-copy p { margin: 0 0 18px; color: #A1A1AA; }
        .itunes-preview-audio { width: min(100%, 560px); }

        /* Media Info */
        .media-info { background: rgba(255,255,255,0.05); padding: 15px; border-radius: 16px; backdrop-filter: blur(10px); border: 1px solid rgba(255,255,255,0.1); margin-bottom: 30px; }
        .info-wrapper h1 { font-size: 24px; margin-bottom: 10px; color: var(--accent-pink, #ec4899); }
        .meta-tags { display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 15px; }
        .meta-tag { background: rgba(255,255,255,0.1); padding: 5px 12px; border-radius: 20px; font-size: 14px; color: #fff; }
        .description-box { background: rgba(255,255,255,0.03); padding: 15px; border-radius: 12px; margin-top: 15px; line-height: 1.6; color: #ccc; }

        /* Rate & Review Form */
        .interaction-section { display: grid; grid-template-columns: 1fr; gap: 20px; margin-bottom: 30px; }
        .form-panel { background: rgba(255,255,255,0.05); padding: 20px; border-radius: 16px; border: 1px solid rgba(255,255,255,0.08); margin-bottom: 40px; }
        .form-panel h3 { color: #fff; margin-bottom: 20px; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 500; color: #fff; }
        .form-control { width: 100%; padding: 12px; border-radius: 8px; border: 1px solid rgba(255,255,255,0.2); background: rgba(0,0,0,0.4); color: #fff; font-family: inherit; font-size: 0.95rem; resize: vertical; outline: none; transition: border-color 0.2s, box-shadow 0.2s; }
        .form-control::placeholder { color: rgba(255,255,255,0.35); }
        .form-control:focus { border-color: #06b6d4; box-shadow: 0 0 0 2px rgba(6,182,212,0.2); }

        /* Star Rating Select */
        .star-rating-ui label { color: #fff; display: block; margin-bottom: 8px; font-weight: 500; }
        .star-rating-ui select { padding: 10px; width: 100%; border-radius: 8px; background: rgba(0,0,0,0.4); color: #fff; border: 1px solid rgba(255,255,255,0.2); font-size: 0.95rem; cursor: pointer; outline: none; transition: border-color 0.2s, box-shadow 0.2s; }
        .star-rating-ui select:focus { border-color: #06b6d4; box-shadow: 0 0 0 2px rgba(6,182,212,0.2); }

        /* Submit Button */
        .btn-submit { background: linear-gradient(135deg, #06b6d4, #3b82f6); color: white; border: none; padding: 12px 20px; border-radius: 8px; cursor: pointer; font-weight: bold; width: 100%; transition: 0.3s; }
        .btn-submit:hover { opacity: 0.9; transform: translateY(-2px); }

        /* User Reviews */
        .reviews-section { background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.08); border-radius: 16px; padding: 25px; margin-bottom: 40px; }
        .reviews-section h3 { color: #fff; margin-bottom: 20px; font-size: 1.2rem; }
        .reviews-list { margin-top: 10px; }
        .review-card { background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.08); padding: 15px 18px; border-radius: 12px; margin-bottom: 12px; }
        .review-header { display: flex; justify-content: space-between; margin-bottom: 8px; font-size: 13px; color: #aaa; }
        .review-author { font-weight: 600; color: #fff; }
        .review-text { color: #ccc; font-size: 0.95rem; line-height: 1.6; }

        /* Misc */
        .alert { padding: 12px; border-radius: 8px; margin-bottom: 20px; }
        .alert-success { background: rgba(16,185,129,0.2); color: #34d399; border: 1px solid rgba(16,185,129,0.3); }
        .nav-back { display: inline-block; margin-bottom: 20px; color: #aaa; text-decoration: none; }
        .nav-back:hover { color: #fff; }

        @media(min-width: 768px) {
            .media-info { padding: 25px; }
            .info-wrapper h1 { font-size: 32px; }
            .description-box { padding: 20px; }
            .interaction-section { grid-template-columns: 1fr 1fr; gap: 30px; margin-bottom: 40px; }
            .form-panel { padding: 25px; }
        }

        /* Layout */
        .top-split-layout {
            display: grid;
            grid-template-columns: 1fr;
            gap: 30px;
            margin-bottom: 40px;
        }
        @media (min-width: 1100px) {
            .top-split-layout {
                grid-template-columns: 1fr 350px;
                align-items: start;
            }
        }
        .main-column {
            display: flex;
            flex-direction: column;
            width: 100%;
        }
        .sidebar-column {
            display: flex;
            flex-direction: column;
            width: 100%;
            background: #FFFFFF;
            border-radius: 20px;
            border: 1px solid #E2E8F0;
            padding: 25px 20px;
            box-shadow: 0 10px 30px rgba(15, 23, 42, 0.06);
        }
        .sidebar-column h3 {
            color: #0F172A;
            font-size: 22px;
            font-weight: 700;
            margin-top: 0;
            margin-bottom: 20px;
        }
        .suggestion-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        .suggestion-item {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 10px;
            border-radius: 12px;
            background: #FFFFFF;
            text-decoration: none;
            transition: all 0.3s ease;
            border: 1px solid transparent;
        }
        .suggestion-item:hover {
            background: #EFF6FF;
        }
        .suggestion-img {
            width: 52px;
            height: 52px;
            border-radius: 10px;
            object-fit: cover;
        }
        .suggestion-info {
            display: flex;
            flex-direction: column;
            overflow: hidden;
            flex: 1;
        }
        .suggestion-title {
            color: #0F172A;
            font-size: 0.95rem;
            font-weight: 600;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            margin-bottom: 3px;
        }
        .suggestion-artist {
            color: #64748B;
            font-size: 13px;
            font-weight: 500;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        /* Red + green media-player theme override */
        body { background: #0B0B0F !important; color: #F8FAFC !important; }
        .video-player-wrapper { border: 1px solid rgba(255,255,255,.1); }
        .media-info, .form-panel, .reviews-section, .review-card { background: #15151C !important; border-color: #27272A !important; }
        .info-wrapper h1 { color: #EC4899 !important; }
        .meta-tag { background: #1B1B24 !important; color: #F8FAFC !important; }
        .description-box { background: #101017 !important; color: #A1A1AA !important; }
        .form-panel h3, .form-group label, .reviews-section h3, .review-author { color: #F8FAFC !important; }
        .form-control, .star-rating-ui select { background: #111118 !important; color: #F8FAFC !important; border-color: #27272A !important; }
        .form-control:focus, .star-rating-ui select:focus { border-color: #8B5CF6 !important; box-shadow: 0 0 0 3px rgba(139,92,246,.17) !important; }
        .btn-submit { background: linear-gradient(135deg,#8B5CF6,#EC4899) !important; color: #ffffff !important; }
        .nav-back:hover { color: #8B5CF6 !important; }
        
        /* Suggestion items: dark bg, visible text */
        .sidebar-column { background: #15151C !important; border-color: #27272A !important; }
        .sidebar-column h3 { color: #F8FAFC !important; }
        .suggestion-item { background: #1B1B24 !important; border-color: #27272A !important; }
        .suggestion-item:hover { background: rgba(139,92,246,.15) !important; border-color: rgba(139,92,246,.3) !important; }
        .suggestion-title { color: #F8FAFC !important; }
        .suggestion-artist { color: #A1A1AA !important; }
    </style>
</head>
<body>

    
    <div class="main-wrapper">

    <?php include 'includes/navbar.php'; ?>

<div class="play-container">
    <?php if ($message): ?>
        <div class="alert alert-success"><?php echo $message; ?></div>
    <?php endif; ?>

    <div class="top-split-layout">
        <div class="main-column">

    <!-- Media Player -->
    <div class="video-player-wrapper">
        <?php if ($youtube_id): ?>
            <div style="position:relative; aspect-ratio:16/9; background:#000;">
                <iframe src="https://www.youtube.com/embed/<?php echo htmlspecialchars($youtube_id); ?>" title="Video player" style="position:absolute; inset:0; width:100%; height:100%; border:0;" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen></iframe>
            </div>
        <?php elseif ($itunes_video_preview_url): ?>
            <video controls preload="metadata" playsinline poster="<?php echo htmlspecialchars($artwork_src); ?>" style="width:100%; display:block; max-height:500px;">
                <source src="<?php echo htmlspecialchars($itunes_video_preview_url); ?>" type="video/mp4">
                Your browser does not support video playback.
            </video>
        <?php elseif ($itunes_preview_url): ?>
            <div class="itunes-preview-stage">
                <?php if ($artwork_src): ?>
                    <img class="itunes-preview-artwork" src="<?php echo htmlspecialchars($artwork_src); ?>" alt="<?php echo htmlspecialchars($video['title']); ?> album artwork">
                <?php endif; ?>
                <div class="itunes-preview-copy">
                    <h2><?php echo htmlspecialchars($video['title']); ?></h2>
                    <p><?php echo htmlspecialchars($video['artist_name'] ?? 'Unknown Artist'); ?><?php if ($video['album_name']): ?> &middot; <?php echo htmlspecialchars($video['album_name']); ?><?php endif; ?></p>
                    <audio class="itunes-preview-audio" controls preload="metadata" src="<?php echo htmlspecialchars($itunes_preview_url); ?>">
                        <source src="<?php echo htmlspecialchars($itunes_preview_url); ?>" type="audio/mp4">
                        Your browser does not support audio playback.
                    </audio>
                </div>
            </div>
        <?php elseif ($itunes_track_only): ?>
            <div style="padding:40px 20px; text-align:center; color:#A1A1AA;">No iTunes preview is available for this track.</div>
        <?php elseif ($unsupported_external_media): ?>
            <div style="padding:40px 20px; text-align:center; color:#A1A1AA;">This external media is no longer available. iTunes previews are audio-only.</div>
        <?php elseif ($external_video_url): ?>
            <video controls preload="metadata" playsinline poster="<?php echo htmlspecialchars($artwork_src); ?>">
                <source src="<?php echo htmlspecialchars($external_video_url); ?>" type="<?php echo $video_mime_type; ?>">
                Your browser does not support video playback.
            </video>
        <?php elseif ($is_local_file): ?>
            <video controls poster="uploads/videos/images/<?php echo htmlspecialchars($video['image']); ?>">
                <source src="<?php echo htmlspecialchars($video_directory . $video_filename); ?>" type="<?php echo $video_mime_type; ?>">
                Your browser does not support the video element.
            </video>
        <?php else: ?>
            <div style="padding:40px 20px; text-align:center; color:#A1A1AA;">
                <div style="background:#15151C; border:1px solid #27272A; border-radius:12px; padding:30px; display:inline-block;">
                    <i data-lucide="video-off" style="width:48px; height:48px; margin-bottom:15px; opacity:0.5;"></i>
                    <h3 style="margin:0 0 10px 0; color:#F8FAFC;">Video Unavailable</h3>
                    <p style="margin:0; font-size:14px;">Unable to play this video. The source format is unsupported or the file is missing.</p>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Video Info -->
    <div class="media-info">
        <div class="info-wrapper">
            <h1><?php echo htmlspecialchars($video['title']); ?></h1>
            <div class="meta-tags">
                <span class="meta-tag"><i data-lucide="mic-2" class="icon-ui"></i> <?php echo htmlspecialchars($video['artist_name'] ?? 'Unknown'); ?></span>
                <?php if ($video['album_name']): ?><span class="meta-tag"><i data-lucide="disc-3" class="icon-ui"></i> <?php echo htmlspecialchars($video['album_name']); ?></span><?php endif; ?>
                <?php if ($video['genre_name']): ?><span class="meta-tag"><i data-lucide="audio-lines" class="icon-ui"></i> <?php echo htmlspecialchars($video['genre_name']); ?></span><?php endif; ?>
                <?php if ($video['year_name']): ?><span class="meta-tag"><i data-lucide="calendar-days" class="icon-ui"></i> <?php echo htmlspecialchars($video['year_name']); ?></span><?php endif; ?>
            </div>
            
            <div style="font-size: 18px; margin-bottom: 10px;">
                <i data-lucide="star" class="icon-ui"></i> <?php echo $avg_rating; ?>/5 <span style="font-size:14px; color:#aaa;">(<?php echo $total_ratings; ?> ratings)</span>
            </div>
        </div>

        <!-- Description -->
        <?php if ($video['description']): ?>
        <div class="description-box">
            <h4 style="margin-bottom:8px; color: #fff;">Description</h4>
            <?php echo nl2br(htmlspecialchars($video['description'])); ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- Interaction (Rate & Review) -->
    <div class="form-panel">
        <h3>Rate & Review this Video</h3>
        <form method="POST">
            <div class="interaction-section">
                <!-- Rate -->
                <div class="form-group star-rating-ui">
                    <label>Your Rating (1 to 5 Stars)</label>
                    <select name="rating" required>
                        <option value="5" <?php echo ($my_rating==5)?'selected':''; ?>>5 / 5</option>
                        <option value="4" <?php echo ($my_rating==4)?'selected':''; ?>>4 / 5</option>
                        <option value="3" <?php echo ($my_rating==3)?'selected':''; ?>>3 / 5</option>
                        <option value="2" <?php echo ($my_rating==2)?'selected':''; ?>>2 / 5</option>
                        <option value="1" <?php echo ($my_rating==1)?'selected':''; ?>>1 / 5</option>
                    </select>
                </div>
                <!-- Write Review -->
                <div class="form-group">
                    <label>Your Review</label>
                    <textarea name="review" class="form-control" rows="3" placeholder="What did you think of this video?" required><?php echo htmlspecialchars($my_review); ?></textarea>
                </div>
            </div>
            <button type="submit" class="btn-submit">Submit Rating & Review</button>
        </form>
    </div>

    <!-- User Reviews -->
    <div class="reviews-section">
        <h3>User Reviews</h3>
        <div class="reviews-list">
            <?php if (mysqli_num_rows($reviews_result) > 0): ?>
                <?php while ($rev = mysqli_fetch_assoc($reviews_result)): ?>
                    <div class="review-card">
                        <div class="review-header">
                            <span class="review-author"><?php echo htmlspecialchars($rev['name']); ?></span>
                            <span><?php echo date('M d, Y', strtotime($rev['created_at'])); ?></span>
                        </div>
                        <div class="review-text">
                            <?php echo nl2br(htmlspecialchars($rev['review'])); ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p style="color:#aaa;">No reviews yet. Be the first to review!</p>
            <?php endif; ?>
        </div>
    </div>

        </div> <!-- End of main-column -->

        <!-- Suggested Songs Sidebar -->
        <div class="sidebar-column">
            <h3>Suggested Songs & Videos</h3>
            <div class="suggestion-list">
                <?php foreach ($suggested_items as $sug): ?>
                    <?php 
                        $is_video = ($sug['type'] === 'video');
                        $item_link = $is_video ? "play_video.php?id=" . $sug['id'] : "play_music.php?id=" . $sug['id'];
                        $item_img = $sug['image'];
                        if ($item_img) {
                            if (!preg_match('/^https?:\/\//i', $item_img)) {
                                $item_img = $is_video ? "uploads/videos/images/" . htmlspecialchars($item_img) : "uploads/music/images/" . htmlspecialchars($item_img);
                            }
                        }
                    ?>
                    <a href="<?php echo $item_link; ?>" class="suggestion-item">
                        <?php if ($item_img): ?>
                            <img src="<?php echo htmlspecialchars($item_img); ?>" alt="Cover" class="suggestion-img">
                        <?php else: ?>
                            <div class="suggestion-img" style="background:#282828; display:flex; align-items:center; justify-content:center; color:#A1A1AA;">
                                <i data-lucide="<?php echo $is_video ? 'video' : 'music'; ?>" style="width:24px; height:24px;"></i>
                            </div>
                        <?php endif; ?>
                        <div class="suggestion-info">
                            <div class="suggestion-title"><?php echo htmlspecialchars($sug['title']); ?></div>
                            <div class="suggestion-artist"><?php echo htmlspecialchars($sug['artist_name'] ?? 'Unknown'); ?></div>
                        </div>
                        <div style="color: #64748B; display: flex; align-items: center;">
                            <i data-lucide="<?php echo $is_video ? 'video' : 'play-circle'; ?>" style="width: 20px; height: 20px;"></i>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
        
    </div> <!-- End of top-split-layout -->

</div>

<?php include 'includes/footer.php'; ?>

</div> <!-- End user-main-content -->

</body>
</html>

