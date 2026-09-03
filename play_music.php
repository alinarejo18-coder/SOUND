<?php
require_once "includes/auth.php";
require_once "config/db.php";

// Require login to access music playback
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

$user_id = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : 0;
$music_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$message = "";
$error = "";

if ($music_id === 0) {
    die("Invalid music ID.");
}

// Handle Add to Playlist
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] === 'add_to_playlist') {
    requireLogin();
    $playlist_id = intval($_POST['playlist_id']);
    $real_user_id = $user_id;
    if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
        $admin_email = mysqli_real_escape_string($conn, $_SESSION['email']);
        $find_user = mysqli_query($conn, "SELECT id FROM users WHERE email = '$admin_email' LIMIT 1");
        if ($find_user && mysqli_num_rows($find_user) > 0) {
            $real_user_id = intval(mysqli_fetch_assoc($find_user)['id']);
        }
    }
    
    if ($real_user_id > 0) {
        $check_own = mysqli_query($conn, "SELECT id FROM playlists WHERE id = $playlist_id AND user_id = $real_user_id");
        if (mysqli_num_rows($check_own) > 0) {
            $check_exist = mysqli_query($conn, "SELECT playlist_id FROM playlist_items WHERE playlist_id = $playlist_id AND music_id = $music_id");
            if (mysqli_num_rows($check_exist) == 0) {
                mysqli_query($conn, "INSERT INTO playlist_items (playlist_id, music_id) VALUES ($playlist_id, $music_id)");
                $message = "Added to playlist!";
            } else {
                $error = "Song is already in this playlist.";
            }
        } else {
            $error = "Invalid playlist.";
        }
    } else {
        $error = "Admin account must have a linked User account to add to playlists.";
    }
}

// Handle Rating & Review Submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && !isset($_POST['action'])) {
    requireLogin(); // Ensure the user is logged in (admin or user)

    $user_id = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : 0;
    
    // If logged in as admin, we must find their corresponding users.id to satisfy the foreign key
    if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
        $admin_email = mysqli_real_escape_string($conn, $_SESSION['email']);
        $find_user = mysqli_query($conn, "SELECT id FROM users WHERE email = '$admin_email' LIMIT 1");
        if ($find_user && mysqli_num_rows($find_user) > 0) {
            $user_row = mysqli_fetch_assoc($find_user);
            $user_id = intval($user_row['id']); // Use the valid users.id
        } else {
            $user_id = 0; // No linked user account found
        }
    }

    if ($user_id > 0) {
        if (isset($_POST['rating'])) {
            $rating = intval($_POST['rating']);
            if ($rating >= 1 && $rating <= 5) {
                // Check if already rated using prepared statement
                $check_stmt = mysqli_prepare($conn, "SELECT id FROM ratings WHERE user_id = ? AND music_id = ?");
                mysqli_stmt_bind_param($check_stmt, "ii", $user_id, $music_id);
                mysqli_stmt_execute($check_stmt);
                $check_result = mysqli_stmt_get_result($check_stmt);
                
                if (mysqli_num_rows($check_result) > 0) {
                    // Update existing rating
                    $update_stmt = mysqli_prepare($conn, "UPDATE ratings SET rating = ? WHERE user_id = ? AND music_id = ?");
                    mysqli_stmt_bind_param($update_stmt, "iii", $rating, $user_id, $music_id);
                    mysqli_stmt_execute($update_stmt);
                    $message = "Rating updated successfully!";
                    mysqli_stmt_close($update_stmt);
                } else {
                    // Insert new rating
                    $insert_stmt = mysqli_prepare($conn, "INSERT INTO ratings (user_id, music_id, rating) VALUES (?, ?, ?)");
                    mysqli_stmt_bind_param($insert_stmt, "iii", $user_id, $music_id, $rating);
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
                $check_rev_stmt = mysqli_prepare($conn, "SELECT id FROM reviews WHERE user_id = ? AND music_id = ?");
                mysqli_stmt_bind_param($check_rev_stmt, "ii", $user_id, $music_id);
                mysqli_stmt_execute($check_rev_stmt);
                $check_rev_result = mysqli_stmt_get_result($check_rev_stmt);

                if (mysqli_num_rows($check_rev_result) > 0) {
                    // Update existing review
                    $update_rev_stmt = mysqli_prepare($conn, "UPDATE reviews SET review = ? WHERE user_id = ? AND music_id = ?");
                    mysqli_stmt_bind_param($update_rev_stmt, "sii", $review, $user_id, $music_id);
                    if (mysqli_stmt_execute($update_rev_stmt)) {
                        $message = "Review updated successfully!";
                    } else {
                        $error = "Failed to update review.";
                    }
                    mysqli_stmt_close($update_rev_stmt);
                } else {
                    // Insert new review
                    $insert_rev_stmt = mysqli_prepare($conn, "INSERT INTO reviews (user_id, music_id, review) VALUES (?, ?, ?)");
                    mysqli_stmt_bind_param($insert_rev_stmt, "iis", $user_id, $music_id, $review);
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
    } else {
        $error = "Admin account must have a linked User account (same email) to submit ratings and reviews.";
    }
}

if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    !isset($_POST['action']) &&
    isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
    strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest'
) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => $error === '' && $message !== '',
        'message' => $message,
        'error' => $error
    ]);
    exit;
}

// Fetch Music Details
$query = "
    SELECT m.*, 
           a.artist_name AS artist_name, 
           al.album_name AS album_name, 
           g.genre_name AS genre_name, 
           l.language_name AS language_name, 
           y.year_value AS year_name 
    FROM music m
    LEFT JOIN artists a ON m.artist_id = a.id
    LEFT JOIN albums al ON m.album_id = al.id
    LEFT JOIN genres g ON m.genre_id = g.id
    LEFT JOIN languages l ON m.language_id = l.id
    LEFT JOIN years y ON m.year_id = y.id
    WHERE m.id = ?
";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $music_id);
mysqli_stmt_execute($stmt);
$music_result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($music_result) == 0) {
    die("Music not found.");
}
$music = mysqli_fetch_assoc($music_result);

// Fetch average rating using prepared statement
$avg_stmt = mysqli_prepare($conn, "SELECT AVG(rating) as avg_rating, COUNT(id) as total_ratings FROM ratings WHERE music_id = ?");
mysqli_stmt_bind_param($avg_stmt, "i", $music_id);
mysqli_stmt_execute($avg_stmt);
$avg_data = mysqli_fetch_assoc(mysqli_stmt_get_result($avg_stmt));
$avg_rating = $avg_data['avg_rating'] ? round($avg_data['avg_rating'], 1) : 0;
$total_ratings = $avg_data['total_ratings'];
mysqli_stmt_close($avg_stmt);

// Fetch user's current rating using prepared statement
$my_rating_stmt = mysqli_prepare($conn, "SELECT rating FROM ratings WHERE user_id = ? AND music_id = ?");
mysqli_stmt_bind_param($my_rating_stmt, "ii", $user_id, $music_id);
mysqli_stmt_execute($my_rating_stmt);
$my_rating_result = mysqli_stmt_get_result($my_rating_stmt);
$my_rating = (mysqli_num_rows($my_rating_result) > 0) ? mysqli_fetch_assoc($my_rating_result)['rating'] : 0;
mysqli_stmt_close($my_rating_stmt);

// Fetch user's current review using prepared statement
$my_rev_stmt = mysqli_prepare($conn, "SELECT review FROM reviews WHERE user_id = ? AND music_id = ?");
mysqli_stmt_bind_param($my_rev_stmt, "ii", $user_id, $music_id);
mysqli_stmt_execute($my_rev_stmt);
$my_rev_result = mysqli_stmt_get_result($my_rev_stmt);
$my_review = (mysqli_num_rows($my_rev_result) > 0) ? mysqli_fetch_assoc($my_rev_result)['review'] : '';
mysqli_stmt_close($my_rev_stmt);

// Fetch all reviews for this music
$reviews_query = "
    SELECT r.review, r.created_at, u.name, u.profile_image 
    FROM reviews r 
    JOIN users u ON r.user_id = u.id 
    WHERE r.music_id = $music_id 
    ORDER BY r.created_at DESC
";
$reviews_result = mysqli_query($conn, $reviews_query);

// Fetch Suggested Songs
$suggested_songs = [];
$m_genre = intval($music['genre_id']);
$m_lang = intval($music['language_id']);
$m_artist = intval($music['artist_id']);

$sug_query = "
    SELECT m.id, m.title, m.image, a.artist_name 
    FROM music m
    LEFT JOIN artists a ON m.artist_id = a.id
    WHERE m.id != $music_id
    ORDER BY 
        (m.genre_id = $m_genre AND m.genre_id IS NOT NULL) DESC,
        (m.language_id = $m_lang AND m.language_id IS NOT NULL) DESC,
        (m.artist_id = $m_artist AND m.artist_id IS NOT NULL) DESC,
        RAND()
    LIMIT 10
";
$sug_res = mysqli_query($conn, $sug_query);
if ($sug_res) {
    while ($row = mysqli_fetch_assoc($sug_res)) {
        $suggested_songs[] = $row;
    }
}

// Fetch user's playlists for the modal
$my_playlists = [];
if ($user_id > 0) {
    $pl_query = mysqli_query($conn, "SELECT * FROM playlists WHERE user_id = $user_id ORDER BY created_at DESC");
    if ($pl_query) {
        while ($prow = mysqli_fetch_assoc($pl_query)) {
            $my_playlists[] = $prow;
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($music['title']); ?> - SOUND</title>
    <link rel="stylesheet" href="assets/css/style.css?v=<?= time() ?>">
    <script src="assets/js/theme.js?v=<?= time() ?>"></script>
    <style>
        body {
            background: #F8FAFC !important;
            font-family: 'Inter', sans-serif;
        }

        body>.play-container {
            max-width: 1300px;
            margin: 0 auto;
            padding: calc(var(--navbar-top) + var(--navbar-height) + 38px) 20px 40px !important;
            font-family: 'Inter', sans-serif;
        }

        .top-split-layout {
            display: grid;
            grid-template-columns: 1fr;
            gap: 30px;
            margin-bottom: 40px;
        }

        @media (min-width: 1100px) {
            .top-split-layout {
                grid-template-columns: 1fr;
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

        .nav-back {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 15px;
            color: #64748B;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.95rem;
            transition: color 0.3s ease;
            padding: 8px 0;
        }

        .nav-back:hover {
            color: #2563EB;
        }

        .media-box {
            background: #FFFFFF;
            border: 1px solid #E2E8F0;
            border-radius: 22px;
            box-shadow: 0 15px 35px rgba(15, 23, 42, 0.10);
            padding: 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 25px;
            margin-bottom: 40px;
        }

        @media(min-width: 768px) {
            .media-box {
                flex-direction: row;
                align-items: flex-start;
                gap: 40px;
                padding: 30px;
            }
        }

        .cover-wrapper {
            flex: 1;
            width: 100%;
            max-width: 300px;
            position: relative;
        }

        @media(min-width: 768px) {
            .cover-wrapper {
                max-width: 350px;
            }
        }

        .cover-wrapper img {
            width: 100%;
            aspect-ratio: 1 / 1;
            object-fit: cover;
            border-radius: 18px;
            box-shadow: 0 10px 20px rgba(15, 23, 42, 0.08);
            transition: transform 0.4s ease;
        }

        .cover-wrapper img:hover {
            transform: scale(1.02);
        }

        .info-wrapper {
            flex: 2;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            width: 100%;
            min-width: 0;
        }

        @media(min-width: 768px) {
            .info-wrapper {
                align-items: flex-start;
                text-align: left;
            }
        }

        .now-playing-label {
            color: #2563EB;
            font-size: 13px;
            font-weight: 700;
            letter-spacing: 2px;
            text-transform: uppercase;
            margin-bottom: 10px;
        }

        .info-wrapper h1 {
            font-size: 32px;
            margin: 0 0 15px 0;
            font-weight: 700;
            line-height: 1.15;
            word-wrap: break-word;
            color: #0F172A;
        }

        @media(min-width: 768px) {
            .info-wrapper h1 {
                font-size: 40px;
            }
        }

        .meta-chips {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            justify-content: center;
            margin-bottom: 30px;
            font-size: 14px;
            color: #64748B;
        }

        @media(min-width: 768px) {
            .meta-chips {
                justify-content: flex-start;
            }
        }

        .meta-chip {
            background: #F8FAFC;
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: 500;
            border: 1px solid #E2E8F0;
        }

        .meta-chip span, .meta-chip strong {
            color: #0F172A;
            font-weight: 600;
        }

        /* Player */
        .custom-player {
            background: #EFF6FF;
            padding: 20px;
            border-radius: 18px;
            border: 1px solid #DBEAFE;
            margin-top: auto;
            width: 100%;
            box-sizing: border-box;
        }

        .progress-section {
            width: 100%;
            margin-bottom: 20px;
        }

        .time-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
            padding: 0 2px;
        }

        .time-display {
            font-size: 13px;
            color: #64748B;
            font-weight: 500;
            font-variant-numeric: tabular-nums;
            min-width: 40px;
        }

        .progress-bar-wrapper {
            flex: 1;
            height: 6px;
            background: #DBEAFE;
            border-radius: 6px;
            position: relative;
            cursor: pointer;
        }

        .progress-bar-fill {
            height: 100%;
            background: #2563EB;
            width: 0%;
            border-radius: 6px;
            position: relative;
            transition: width 0.1s linear;
        }

        .progress-bar-fill::after {
            content: '';
            position: absolute;
            right: -6px;
            top: 50%;
            transform: translateY(-50%) scale(0);
            width: 12px;
            height: 12px;
            background: #FFFFFF;
            border-radius: 50%;
            transition: transform 0.2s;
            box-shadow: 0 0 10px rgba(15, 23, 42, 0.2);
        }

        .progress-bar-wrapper:hover .progress-bar-fill::after {
            transform: translateY(-50%) scale(1);
        }

        .controls-row {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 25px;
            position: relative;
        }

        .btn-control {
            background: none;
            border: none;
            color: #64748B;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .btn-control:hover {
            color: #2563EB;
        }

        .btn-play-pause {
            width: 56px;
            height: 56px;
            background: #2563EB;
            color: #FFFFFF;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            cursor: pointer;
            border: none;
            box-shadow: 0 4px 15px rgba(37, 99, 235, 0.2);
        }

        .btn-play-pause:hover {
            transform: scale(1.05);
            background: #1E3A8A;
        }

        .btn-play-pause svg {
            width: 24px;
            height: 24px;
            fill: currentColor;
        }

        .icon-play {
            margin-left: 4px;
        }

        .icon-pause {
            display: none;
        }

        .is-playing .icon-play {
            display: none;
        }

        .is-playing .icon-pause {
            display: block;
            margin-left: 0;
        }

        .volume-container {
            display: none;
            align-items: center;
            gap: 10px;
            position: absolute;
            right: 0;
            color: #64748B;
        }

        @media(min-width: 768px) {
            .volume-container {
                display: flex;
            }
        }

        .volume-slider {
            width: 80px;
            height: 4px;
            -webkit-appearance: none;
            background: #DBEAFE;
            border-radius: 4px;
            outline: none;
            cursor: pointer;
        }

        .volume-slider::-webkit-slider-thumb {
            -webkit-appearance: none;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: #2563EB;
            cursor: pointer;
        }

        .audio-error {
            display: none;
            background: #FEE2E2;
            color: #991B1B;
            padding: 12px;
            border-radius: 8px;
            border: 1px solid #FCA5A5;
            font-size: 0.9rem;
            margin-top: 15px;
            text-align: center;
        }

        /* Sections below player */
        .section-title {
            font-size: 1.4rem;
            margin: 0 0 20px 0;
            color: #0F172A;
            font-weight: 700;
        }

        .description-box,
        .form-panel {
            background: #FFFFFF;
            padding: 20px;
            border-radius: 20px;
            margin-bottom: 40px;
            line-height: 1.7;
            color: #475569;
            border: 1px solid #E2E8F0;
            font-size: 0.95rem;
            box-shadow: 0 10px 30px rgba(15, 23, 42, 0.05);
        }

        @media(min-width: 768px) {
            .description-box,
            .form-panel {
                padding: 30px;
            }
        }

        .interaction-section {
            display: grid;
            grid-template-columns: 1fr;
            gap: 25px;
            margin-bottom: 25px;
        }

        @media(min-width: 768px) {
            .interaction-section {
                grid-template-columns: 1fr 1fr;
            }
        }

        .form-group {
            margin-bottom: 0;
        }

        .form-group label {
            display: block;
            margin-bottom: 10px;
            font-weight: 600;
            color: #475569;
            font-size: 0.95rem;
        }

        .form-control,
        .star-rating-ui select {
            width: 100%;
            padding: 14px;
            border-radius: 10px;
            border: 1px solid #CBD5E1;
            background: #FFFFFF;
            color: #0F172A;
            font-family: inherit;
            font-size: 1rem;
            transition: all 0.3s ease;
            box-sizing: border-box;
        }

        .form-control::placeholder {
            color: #64748B;
        }

        .form-control:focus,
        .star-rating-ui select:focus {
            outline: none;
            border-color: #2563EB;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.10);
        }

        .star-rating-ui select {
            appearance: none;
            cursor: pointer;
            background-image: url("data:image/svg+xml;charset=US-ASCII,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20width%3D%22292.4%22%20height%3D%22292.4%22%3E%3Cpath%20fill%3D%22%2394a3b8%22%20d%3D%22M287%2069.4a17.6%2017.6%200%200%200-13-5.4H18.4c-5%200-9.3%201.8-12.9%205.4A17.6%2017.6%200%200%200%200%2082.2c0%205%201.8%209.3%205.4%2012.9l128%20127.9c3.6%203.6%207.8%205.4%2012.8%205.4s9.2-1.8%2012.8-5.4L287%2095c3.5-3.5%205.4-7.8%205.4-12.8%200-5-1.9-9.2-5.5-12.8z%22%2F%3E%3C%2Fsvg%3E");
            background-repeat: no-repeat;
            background-position: right 15px top 50%;
            background-size: 12px auto;
        }

        .star-rating-ui select option {
            background: #FFFFFF;
            color: #0F172A;
        }

        .btn-submit {
            background: #2563EB;
            color: #FFFFFF;
            border: none;
            padding: 14px 24px;
            border-radius: 12px;
            cursor: pointer;
            font-weight: 700;
            font-size: 1rem;
            width: 100%;
            transition: all 0.3s ease;
        }

        .btn-submit:hover {
            background: #1E3A8A;
        }

        .reviews-list {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .review-card {
            background: #FFFFFF;
            padding: 20px;
            border-radius: 16px;
            border: 1px solid #E2E8F0;
            color: #475569;
        }

        .review-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
            font-size: 0.9rem;
            color: #64748B;
            border-bottom: 1px solid #E2E8F0;
            padding-bottom: 10px;
        }

        .review-author {
            font-weight: 600;
            color: #0F172A;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .review-author::before {
            content: '';
            display: inline-block;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            background: #2563EB;
        }

        .review-author.has-avatar::before {
            display: none;
        }

        .alert {
            padding: 16px;
            border-radius: 12px;
            margin-bottom: 25px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-success {
            background: #EFF6FF;
            color: #2563EB;
            border: 1px solid #DBEAFE;
        }
        /* Dark theme overrides */
        body { background: #0B0B0F !important; color: #F8FAFC !important; }
        body > .play-container { background: transparent !important; }
        .media-box, .sidebar-column, .custom-player, .description-box, .form-panel, .review-card {
            background: #15151C !important;
            border-color: #27272A !important;
            color: #F8FAFC !important;
        }
        .sidebar-column h3, .section-title, .info-wrapper h1, .review-author { color: #F8FAFC !important; }
        /* Suggestion items: dark bg, visible text */
        .suggestion-item { background: #1B1B24 !important; border-color: #27272A !important; }
        .suggestion-item:hover { background: rgba(139,92,246,.15) !important; border-color: rgba(139,92,246,.3) !important; }
        .suggestion-title { color: #F8FAFC !important; }
        .suggestion-artist { color: #A1A1AA !important; }
        /* Meta chips: dark bg so text is readable */
        .now-playing-label { color: #A1A1AA !important; }
        .meta-chip { background: #1B1B24 !important; border-color: #27272A !important; color: #A1A1AA !important; }
        .meta-chip span, .meta-chip strong { color: #F8FAFC !important; }
        /* Player */
        .custom-player { background: #101017 !important; color: #F8FAFC !important; border-color: #27272A !important; }
        .custom-player .time-display, .custom-player .btn-control { color: #A1A1AA !important; }
        .progress-bar-wrapper, .volume-slider { background: #27272A !important; }
        .progress-bar-fill, .btn-play-pause { background: #168a4b !important; color: #ffffff !important; }
        .btn-submit { background: #dc2626 !important; color: #ffffff !important; }
        .btn-control:hover, .nav-back:hover { color: #4ade80 !important; }
        .form-control, .star-rating-ui select { background: #111118 !important; color: #F8FAFC !important; border-color: #27272A !important; }
        .form-control:focus, .star-rating-ui select:focus { border-color: #8B5CF6 !important; box-shadow: 0 0 0 3px rgba(139,92,246,.17) !important; }
        .alert-danger { background: rgba(239,68,68,.14) !important; color: #FCA5A5 !important; }
        .review-header { border-color: #27272A !important; color: #A1A1AA !important; }
        .review-author { color: #F8FAFC !important; }

        /* Removed strict override for user-main-content because sidebar is hidden */
    </style>
</head>

<body>

    
    <div class="main-wrapper">

    <?php include 'includes/navbar.php'; ?>

    <div class="play-container">
        <?php if ($message): ?>
            <div class="alert alert-success"><i data-lucide="circle-check" class="icon-ui"></i> <?php echo $message; ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger" style="background:#fee2e2;color:#991b1b;padding:15px;margin-bottom:20px;border-radius:8px;"><i data-lucide="triangle-alert" class="icon-ui"></i> <?php echo $error; ?></div>
        <?php endif; ?>

        <!-- Media Player & Suggested Songs Split -->
        <div class="top-split-layout">
            <div class="main-column">
                <div class="media-box">
                    <div class="cover-wrapper">
                        <?php if ($music['image']): ?>
                            <?php
                            $img_src = $music['image'];
                            if (!preg_match('/^https?:\/\//i', $img_src) && !empty($img_src)) {
                                $img_src = "uploads/music/images/" . htmlspecialchars($img_src);
                            } else {
                                $img_src = htmlspecialchars($img_src);
                            }
                            ?>
                            <img src="<?php echo $img_src; ?>" alt="Cover">
                        <?php else: ?>
                            <div style="width:100%; aspect-ratio:1; background:#EFF6FF; display:flex; align-items:center; justify-content:center; border-radius:18px; font-size:3rem; color:#2563EB;"><i data-lucide="music" class="icon-ui"></i></div>
                        <?php endif; ?>
                    </div>
                    <div class="info-wrapper">
                        <div class="now-playing-label">Music</div>
                        <h1><?php echo htmlspecialchars($music['title']); ?></h1>
                        <div class="meta-chips">
                            <div class="meta-chip">Artist: <span><?php echo htmlspecialchars($music['artist_name'] ?? 'Unknown'); ?></span></div>
                            <?php if ($music['album_name']): ?><div class="meta-chip">Album: <span><?php echo htmlspecialchars($music['album_name']); ?></span></div><?php endif; ?>
                            <?php if ($music['genre_name']): ?><div class="meta-chip">Genre: <span><?php echo htmlspecialchars($music['genre_name']); ?></span></div><?php endif; ?>
                            <?php if ($music['language_name']): ?><div class="meta-chip">Language: <span><?php echo htmlspecialchars($music['language_name']); ?></span></div><?php endif; ?>
                            <?php if ($music['year_name']): ?><div class="meta-chip">Released: <span><?php echo htmlspecialchars($music['year_name']); ?></span></div><?php endif; ?>
                            <?php if ($music['is_new']): ?><div class="meta-chip" style="background: #2563EB; color: #FFFFFF; border: none; border-radius: 999px; padding: 9px 16px; font-weight: 600;"><span style="color: #FFFFFF;">NEW RELEASE</span></div><?php endif; ?>
                        </div>
                        <div class="meta-chips" style="margin-bottom:20px; align-items:center;">
                            <div class="meta-chip"><span><i data-lucide="star" class="icon-ui"></i> <?php echo $avg_rating; ?>/5</span> (<?php echo $total_ratings; ?> ratings)</div>
                            <?php if ($user_id > 0): ?>
                                <button onclick="document.getElementById('addToPlaylistModal').style.display='flex'" style="background: #FFFFFF; color: #2563EB; border: 1px solid #2563EB; padding: 8px 16px; border-radius: 20px; font-weight: 600; cursor: pointer; font-size: 0.9rem; display:flex; align-items:center; gap:6px; transition: 0.2s;" onmouseover="this.style.background='#EFF6FF'" onmouseout="this.style.background='#FFFFFF'">
                                    <i data-lucide="plus" style="width: 16px; height: 16px;"></i>
                                    Add to Playlist
                                </button>
                            <?php endif; ?>
                        </div>
                        <div class="custom-player" id="customPlayer">
                            <?php if (!empty($music['music_file'])): ?>
                                <audio id="audioElement" preload="metadata">
                                    <?php
                                    $audio_src = $music['music_file'];
                                    if (!preg_match('/^https?:\/\//i', $audio_src)) {
                                        $audio_src = "uploads/music/files/" . htmlspecialchars($audio_src);
                                    } else {
                                        $audio_src = htmlspecialchars($audio_src);
                                    }
                                    ?>
                                    <source src="<?php echo $audio_src; ?>">
                                </audio>
                                <div class="progress-section">
                                    <div class="time-row"><div class="time-display" id="currentTimeDisplay">0:00</div><div class="time-display" id="durationDisplay">0:00</div></div>
                                    <div class="progress-bar-wrapper" id="progressBarWrapper"><div class="progress-bar-fill" id="progressBarFill"></div></div>
                                </div>
                                <div class="controls-row">
                                    <button class="btn-control" title="Previous"><i data-lucide="skip-back" style="width: 24px; height: 24px;"></i></button>
                                    <button class="btn-play-pause" id="playPauseBtn" title="Play">
                                    <i data-lucide="play" class="icon-play" style="width: 24px; height: 24px;"></i>
                                    <i data-lucide="pause" class="icon-pause" style="width: 24px; height: 24px;"></i>
                                </button>

                                <!-- Next placeholder -->
                                <button class="btn-control" title="Next">
                                    <i data-lucide="skip-forward" style="width: 24px; height: 24px;"></i>
                                </button>

                                <div class="volume-container">
                                    <button class="btn-control" id="muteBtn" title="Mute">
                                        <i data-lucide="volume-2" style="width: 20px; height: 20px;"></i>
                                    </button>
                                    <input type="range" class="volume-slider" id="volumeSlider" min="0" max="1" step="0.05" value="1">
                                </div>
                            </div>

                            <div class="audio-error" id="audioErrorMsg">
                                Unable to load audio file. Please check if the file exists.
                            </div>
                        <?php else: ?>
                            <div style="text-align: center; color: #71717A; padding: 20px 0;">
                                No audio file available for this track.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

        <!-- Description -->
        <?php if ($music['description']): ?>
            <h3 class="section-title">ABOUT THIS SONG</h3>
            <div class="description-box">
                <?php echo nl2br(htmlspecialchars($music['description'])); ?>
            </div>
        <?php endif; ?>

        <!-- Interaction (Rate & Review) -->
        <h3 class="section-title">Rate & Review</h3>
            <div class="form-panel">
                <form method="POST" id="ratingReviewForm">
                    <div class="interaction-section">
                        <!-- Rate -->
                        <div class="form-group star-rating-ui">
                            <label>Your Rating</label>
                            <select name="rating" required>
                                <option value="5" <?php echo ($my_rating == 5) ? 'selected' : ''; ?>>5 / 5</option>
                                <option value="4" <?php echo ($my_rating == 4) ? 'selected' : ''; ?>>4 / 5</option>
                                <option value="3" <?php echo ($my_rating == 3) ? 'selected' : ''; ?>>3 / 5</option>
                                <option value="2" <?php echo ($my_rating == 2) ? 'selected' : ''; ?>>2 / 5</option>
                                <option value="1" <?php echo ($my_rating == 1) ? 'selected' : ''; ?>>1 / 5</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Your Review</label>
                            <textarea name="review" class="form-control" rows="3" placeholder="What did you think of this track?" required><?php echo htmlspecialchars($my_review); ?></textarea>
                        </div>
                    </div>
                    <button type="submit" class="btn-submit">Submit Rating & Review</button>
                </form>
            </div>

        <!-- User Reviews -->
        <div class="reviews-section">
            <h3 class="section-title">User Reviews</h3>
            <div class="reviews-list">
                <?php if (mysqli_num_rows($reviews_result) > 0): ?>
                    <?php while ($rev = mysqli_fetch_assoc($reviews_result)): ?>
                        <div class="review-card">
                            <div class="review-header">
                                <?php
                                $review_profile_image = basename((string) ($rev['profile_image'] ?? ''));
                                $review_profile_path = __DIR__ . '/uploads/users/' . $review_profile_image;
                                $has_review_profile_image = $review_profile_image !== '' && is_file($review_profile_path);
                                ?>
                                <span class="review-author<?php echo $has_review_profile_image ? ' has-avatar' : ''; ?>">
                                    <?php if ($has_review_profile_image): ?>
                                        <img src="uploads/users/<?php echo htmlspecialchars($review_profile_image); ?>" alt="<?php echo htmlspecialchars($rev['name']); ?> profile" style="width:24px;height:24px;border-radius:50%;object-fit:cover;">
                                    <?php endif; ?>
                                    <?php echo htmlspecialchars($rev['name']); ?>
                                </span>
                                <span><?php echo date('M d, Y', strtotime($rev['created_at'])); ?></span>
                            </div>
                            <div class="review-text">
                                <?php echo nl2br(htmlspecialchars($rev['review'])); ?>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p style="color: #64748B;">No reviews yet. Be the first to review!</p>
                <?php endif; ?>
            </div>
        </div> <!-- End of reviews-section -->
        </div> <!-- End of main-column -->

            <!-- Suggested Songs Sidebar Removed as per user request -->
        </div> <!-- End of top-split-layout -->
    </div> <!-- End of play-container -->

            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    const reviewForm = document.getElementById('ratingReviewForm');
                    if (reviewForm) {
                        reviewForm.addEventListener('submit', async function(event) {
                            event.preventDefault();
                            let status = document.getElementById('reviewStatus');
                            if (!status) {
                                status = document.createElement('div');
                                status.id = 'reviewStatus';
                                reviewForm.parentNode.insertBefore(status, reviewForm);
                            }
                            try {
                                const response = await fetch(window.location.href, {
                                    method: 'POST',
                                    body: new FormData(reviewForm),
                                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                                });
                                const result = await response.json();
                                status.className = result.success ? 'alert alert-success' : 'alert alert-danger';
                                status.textContent = result.success ? result.message : (result.error || 'Failed to submit rating and review.');
                            } catch (error) {
                                status.className = 'alert alert-danger';
                                status.textContent = 'Failed to submit rating and review.';
                            }
                        });
                    }

                    const audio = document.getElementById('audioElement');
                    if (!audio) return;
                    const playPauseBtn = document.getElementById('playPauseBtn');
                    const customPlayer = document.getElementById('customPlayer');
                    const progressBarWrapper = document.getElementById('progressBarWrapper');
                    const progressBarFill = document.getElementById('progressBarFill');
                    const currentTimeDisplay = document.getElementById('currentTimeDisplay');
                    const durationDisplay = document.getElementById('durationDisplay');
                    const volumeSlider = document.getElementById('volumeSlider');
                    const muteBtn = document.getElementById('muteBtn');
                    const errorMsg = document.getElementById('audioErrorMsg');

                    let isDragging = false;

                    // Helper: Format Time
                    function formatTime(seconds) {
                        if (isNaN(seconds) || !isFinite(seconds)) return "0:00";
                        const mins = Math.floor(seconds / 60);
                        const secs = Math.floor(seconds % 60);
                        return mins + ":" + (secs < 10 ? "0" : "") + secs;
                    }

                    // Initialize Duration if already loaded, else wait for event
                    if (audio.readyState >= 1) {
                        durationDisplay.textContent = formatTime(audio.duration);
                    }
                    audio.addEventListener('loadedmetadata', () => {
                        durationDisplay.textContent = formatTime(audio.duration);
                    });

                    // Play/Pause Toggle
                    playPauseBtn.addEventListener('click', () => {
                        if (audio.paused) {
                            audio.play().catch(e => {
                                console.error("Playback failed:", e);
                                errorMsg.style.display = "block";
                                errorMsg.textContent = "Unable to play audio. Please check your connection or browser settings.";
                            });
                        } else {
                            audio.pause();
                        }
                    });

                    // Check if autoplay is requested (e.g. from previous song ending)
                    const urlParams = new URLSearchParams(window.location.search);
                    const isAutoplay = urlParams.get('autoplay') === '1';

                    if (isAutoplay) {
                        // Try to play immediately, might be blocked by browser policy
                        const playPromise = audio.play();
                        if (playPromise !== undefined) {
                            playPromise.catch(e => {
                                console.log("Autoplay was prevented by browser policy.");
                            });
                        }
                    } else {
                        // Fresh navigation, clear any active playlist state
                        sessionStorage.removeItem('playlist');
                        sessionStorage.removeItem('playlistIndex');
                    }

                    // Capture clicks on suggestions to establish/update the playlist context
                    document.querySelectorAll('.suggestion-item').forEach((item, index, list) => {
                        item.addEventListener('click', (e) => {
                            e.preventDefault();
                            // Store the current visible suggestions as our active playlist
                            const currentPlaylist = Array.from(list).map(el => el.href.split('&autoplay=')[0]);
                            sessionStorage.setItem('playlist', JSON.stringify(currentPlaylist));
                            sessionStorage.setItem('playlistIndex', index);
                            // Navigate to clicked song and trigger autoplay
                            window.location.href = currentPlaylist[index] + '&autoplay=1';
                        });
                    });

                    // Audio Play/Pause Events to update UI
                    audio.addEventListener('play', () => {
                        customPlayer.classList.add('is-playing');
                    });
                    audio.addEventListener('pause', () => {
                        customPlayer.classList.remove('is-playing');
                    });
                    audio.addEventListener('ended', () => {
                        let playlist = JSON.parse(sessionStorage.getItem('playlist') || 'null');
                        let playlistIndex = parseInt(sessionStorage.getItem('playlistIndex'), 10);

                        if (!playlist || isNaN(playlistIndex)) {
                            // No active playlist in memory, so start one from the current sidebar
                            const items = document.querySelectorAll('.suggestion-item');
                            if (items.length > 0) {
                                playlist = Array.from(items).map(el => el.href.split('&autoplay=')[0]);
                                playlistIndex = 0;
                                sessionStorage.setItem('playlist', JSON.stringify(playlist));
                                sessionStorage.setItem('playlistIndex', playlistIndex);
                                window.location.href = playlist[playlistIndex] + '&autoplay=1';
                                return;
                            }
                        } else {
                            // We have an active playlist in memory, move to next
                            playlistIndex++;
                            if (playlistIndex < playlist.length) {
                                sessionStorage.setItem('playlistIndex', playlistIndex);
                                window.location.href = playlist[playlistIndex] + '&autoplay=1';
                                return;
                            } else {
                                // Reached the end of the playlist
                                sessionStorage.removeItem('playlist');
                                sessionStorage.removeItem('playlistIndex');
                            }
                        }

                        // Fallback: stop playback if no suggestions or playlist is completely finished
                        customPlayer.classList.remove('is-playing');
                        progressBarFill.style.width = '0%';
                        currentTimeDisplay.textContent = formatTime(0);
                    });

                    // Time Update
                    audio.addEventListener('timeupdate', () => {
                        if (!isDragging && audio.duration) {
                            const progressPercent = (audio.currentTime / audio.duration) * 100;
                            progressBarFill.style.width = progressPercent + '%';
                            currentTimeDisplay.textContent = formatTime(audio.currentTime);
                        }
                    });

                    // Seeking on Click / Drag
                    function updateProgress(e) {
                        const rect = progressBarWrapper.getBoundingClientRect();
                        let pos = (e.clientX - rect.left) / rect.width;
                        pos = Math.max(0, Math.min(1, pos));

                        progressBarFill.style.width = (pos * 100) + '%';
                        if (audio.duration) {
                            currentTimeDisplay.textContent = formatTime(pos * audio.duration);
                        }
                        return pos;
                    }

                    progressBarWrapper.addEventListener('mousedown', (e) => {
                        isDragging = true;
                        updateProgress(e);
                    });

                    document.addEventListener('mousemove', (e) => {
                        if (isDragging) {
                            updateProgress(e);
                        }
                    });

                    document.addEventListener('mouseup', (e) => {
                        if (isDragging) {
                            const pos = updateProgress(e);
                            if (audio.duration) {
                                audio.currentTime = pos * audio.duration;
                            }
                            isDragging = false;
                        }
                    });

                    // Volume Control
                    volumeSlider.addEventListener('input', (e) => {
                        audio.volume = e.target.value;
                        audio.muted = (audio.volume === 0);
                    });

                    muteBtn.addEventListener('click', () => {
                        audio.muted = !audio.muted;
                        volumeSlider.value = audio.muted ? 0 : audio.volume;
                    });

                    // Error Handling
                    audio.addEventListener('error', (e) => {
                        console.error("Audio error:", e);
                        errorMsg.style.display = "block";
                    });
                });
            </script>
            <?php include 'includes/footer.php'; ?>
            
            <?php if ($user_id > 0): ?>
            <div id="addToPlaylistModal" style="display:none; position:fixed; z-index:9999; left:0; top:0; width:100%; height:100%; background:rgba(15, 23, 42, 0.60); align-items:center; justify-content:center; padding: 15px; box-sizing: border-box;">
                <div style="background:#FFFFFF; border-radius:18px; border:1px solid #E2E8F0; box-shadow:0 25px 60px rgba(15,23,42,0.20); width:100%; max-width:520px; position:relative; font-family:'Inter',sans-serif; max-height:85vh; display:flex; flex-direction:column; overflow:hidden;">
                    
                    <!-- Close Button -->
                    <span onclick="document.getElementById('addToPlaylistModal').style.display='none'" title="Close"
                          style="position:absolute; right:16px; top:16px; cursor:pointer; width:34px; height:34px; border-radius:50%; background:#F8FAFC; color:#64748B; display:flex; align-items:center; justify-content:center; font-size:18px; line-height:1; transition:all 0.2s; user-select:none; z-index:1;"
                          onmouseover="this.style.background='#EFF6FF';this.style.color='#2563EB';"
                          onmouseout="this.style.background='#F8FAFC';this.style.color='#64748B';">&times;</span>

                    <!-- Modal Header -->
                    <div style="padding:28px 28px 0 28px;">
                        <h2 style="margin:0 0 5px 0; color:#0F172A; font-size:22px; font-weight:700; letter-spacing:-0.3px; padding-right:30px;">Add to Playlist</h2>
                        <p style="margin:0 0 20px 0; color:#64748B; font-size:14px; line-height:1.5;">Choose a playlist to add this song to.</p>
                    </div>

                    <!-- Modal Body (scrollable) -->
                    <div style="padding:0 28px; overflow-y:auto; flex:1;">
                        <form method="POST" id="addToPlaylistForm">
                            <input type="hidden" name="action" value="add_to_playlist">
                            <div style="display:flex; flex-direction:column; gap:8px; padding-bottom:4px;">
                                <?php if(count($my_playlists) > 0): ?>
                                    <?php foreach($my_playlists as $pl): ?>
                                        <label style="display:flex; align-items:center; gap:12px; padding:13px 14px; background:#FFFFFF; border:1px solid #E2E8F0; border-radius:12px; cursor:pointer; transition:all 0.2s; user-select:none;"
                                               onmouseover="this.style.background='#EFF6FF'; this.style.borderColor='#BFDBFE';"
                                               onmouseout="var r=this.querySelector('input'); if(!r.checked){this.style.background='#FFFFFF'; this.style.borderColor='#E2E8F0';}">
                                            <input type="radio" name="playlist_id" value="<?php echo $pl['id']; ?>" required
                                                   style="accent-color:#2563EB; width:17px; height:17px; flex-shrink:0; cursor:pointer;"
                                                   onchange="document.querySelectorAll('#addToPlaylistForm label').forEach(function(l){var i=l.querySelector('input'); if(i.checked){l.style.background='#EFF6FF';l.style.borderColor='#2563EB';}else{l.style.background='#FFFFFF';l.style.borderColor='#E2E8F0';}});">
                                            <span style="color:#0F172A; font-weight:500; font-size:15px; flex:1;"><?php echo htmlspecialchars($pl['title']); ?></span>
                                        </label>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div style="text-align:center; padding:30px 20px;">
                                        <div style="font-size:2.5rem; margin-bottom:10px;"><i data-lucide="music" class="icon-ui"></i></div>
                                        <p style="color:#0F172A; font-weight:600; font-size:15px; margin:0 0 5px 0;">No playlists yet</p>
                                        <p style="color:#64748B; font-size:13px; margin:0;">Create your first playlist to get started.</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                    
                    <!-- Modal Footer -->
                    <div style="display:flex; align-items:center; justify-content:space-between; gap:16px; padding:20px 0 0 0; border-top:1px solid #E2E8F0; margin-top:16px; flex-wrap:wrap;">
                        <a href="/SOUND/user/dashboard.php" style="color:#2563EB; text-decoration:none; font-size:14px; font-weight:600; display:inline-flex; align-items:center; gap:5px; transition:color 0.2s; flex-shrink:0;"
                           onmouseover="this.style.color='#1E3A8A';" onmouseout="this.style.color='#2563EB';">
                            <i data-lucide="plus" style="width: 16px; height: 16px;"></i>
                            Create New Playlist
                        </a>
                        <button type="submit" style="background:#2563EB; color:#FFFFFF; border:none; padding:11px 22px; border-radius:10px; cursor:pointer; font-weight:600; font-size:14px; transition:all 0.2s; font-family:inherit; flex-shrink:0;"
                                onmouseover="this.style.background='#1E3A8A';" onmouseout="this.style.background='#2563EB';">Add to Playlist</button>
                    </div>
                        </form>
                    </div>
                    <!-- bottom padding -->
                    <div style="height:24px;"></div>
                </div>
            </div>
            <?php endif; ?>

    </div> <!-- End user-main-content -->

</body>

</html>

