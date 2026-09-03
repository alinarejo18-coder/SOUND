<?php
require_once "../../includes/auth.php";
require_once "../../config/db.php";
requireAdmin();

$id = intval($_GET["id"] ?? 0);
if ($id <= 0) {
    header("Location: index.php");
    exit;
}

$stmt = mysqli_prepare($conn, "SELECT * FROM music WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
if (mysqli_num_rows($result) !== 1) {
    header("Location: index.php");
    exit;
}
$track = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $title = trim($_POST["title"] ?? "");
    $artist_id = intval($_POST["artist_id"] ?? 0);
    $album_id = intval($_POST["album_id"] ?? 0);
    $year_id = intval($_POST["year_id"] ?? 0);
    $genre_id = intval($_POST["genre_id"] ?? 0);
    $language_id = intval($_POST["language_id"] ?? 0);
    $description = trim($_POST["description"] ?? "");
    $is_new = isset($_POST["is_new"]) ? 1 : 0;

    $image = $track['image'];
    $music_file = $track['music_file'];

    if ($title === "") {
        $message = "Title is required.";
    } else {
        if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
            $image = time() . '_' . $_FILES['image']['name'];
            move_uploaded_file($_FILES['image']['tmp_name'], "../../uploads/music/images/" . $image);
        }
        if (isset($_FILES['music_file']) && $_FILES['music_file']['error'] == 0) {
            $music_file = time() . '_' . $_FILES['music_file']['name'];
            move_uploaded_file($_FILES['music_file']['tmp_name'], "../../uploads/music/files/" . $music_file);
        }

        $update = mysqli_prepare($conn, "UPDATE music SET title=?, artist_id=?, album_id=?, year_id=?, genre_id=?, language_id=?, description=?, image=?, music_file=?, is_new=? WHERE id=?");
        
        $artist_id = $artist_id ?: null;
        $album_id = $album_id ?: null;
        $year_id = $year_id ?: null;
        $genre_id = $genre_id ?: null;
        $language_id = $language_id ?: null;

        mysqli_stmt_bind_param($update, "siiiiisssii", $title, $artist_id, $album_id, $year_id, $genre_id, $language_id, $description, $image, $music_file, $is_new, $id);
        
        if (mysqli_stmt_execute($update)) {
            header("Location: index.php");
            exit;
        } else {
            $message = "Failed to update music track.";
        }
    }
}

$artists = mysqli_query($conn, "SELECT id, artist_name FROM artists ORDER BY artist_name ASC");
$albums = mysqli_query($conn, "SELECT id, album_name FROM albums ORDER BY album_name ASC");
$genres = mysqli_query($conn, "SELECT id, genre_name FROM genres ORDER BY genre_name ASC");
$years = mysqli_query($conn, "SELECT id, year_value FROM years ORDER BY year_value DESC");
$languages = mysqli_query($conn, "SELECT id, language_name FROM languages ORDER BY language_name ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Music - SOUND Admin</title>
    <link rel="stylesheet" href="../../assets/css/style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../../assets/css/admin.css?v=<?php echo time(); ?>">
</head>
<body>

<div class="admin-layout">

    <?php 
    $current_page = 'music';
    $admin_base = '../';
    include "../../includes/admin_header.php"; 
    ?>

    <main class="admin-main">
        <div class="admin-topbar">
            <div>
                <h1>Edit Music</h1>
                <div class="breadcrumb"><a href="index.php">Music</a> / Edit</div>
            </div>
        </div>

        <div class="admin-content">
            <div class="form-panel">
                <?php if ($message !== ""): ?>
                    <div style="background: #fee2e2; color: #991b1b; padding: 12px; border-radius: 7px; margin-bottom: 18px;"><?php echo htmlspecialchars($message); ?></div>
                <?php endif; ?>
                
                <form method="POST" enctype="multipart/form-data">
                    <label >Title *</label>
                    <input type="text" name="title" value="<?php echo htmlspecialchars($track['title']); ?>" required class="form-control">
                    
                    <label >Artist</label>
                    <select name="artist_id" class="form-control">
                        <option value="0">Select Artist</option>
                        <?php while($row = mysqli_fetch_assoc($artists)): ?>
                            <option value="<?php echo $row['id']; ?>" <?php if($track['artist_id'] == $row['id']) echo 'selected'; ?>><?php echo $row['artist_name']; ?></option>
                        <?php endwhile; ?>
                    </select>

                    <div class="form-group">
                        <label>Genre</label>
                        <select name="genre_id" class="form-control">
                            <option value="0">Select Genre</option>
                            <?php while($row = mysqli_fetch_assoc($genres)): ?>
                                <option value="<?php echo $row['id']; ?>" <?php if($track['genre_id'] == $row['id']) echo 'selected'; ?>><?php echo $row['genre_name']; ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Album</label>
                        <select name="album_id" class="form-control">
                            <option value="0">Select Album</option>
                            <?php while($row = mysqli_fetch_assoc($albums)): ?>
                                <option value="<?php echo $row['id']; ?>" <?php if($track['album_id'] == $row['id']) echo 'selected'; ?>><?php echo $row['album_name']; ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Year</label>
                        <select name="year_id" class="form-control">
                            <option value="0">Select Year</option>
                            <?php while($row = mysqli_fetch_assoc($years)): ?>
                                <option value="<?php echo $row['id']; ?>" <?php if($track['year_id'] == $row['id']) echo 'selected'; ?>><?php echo $row['year_value']; ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Language</label>
                        <select name="language_id" class="form-control">
                            <option value="0">Select Language</option>
                            <?php while($row = mysqli_fetch_assoc($languages)): ?>
                                <option value="<?php echo $row['id']; ?>" <?php if($track['language_id'] == $row['id']) echo 'selected'; ?>><?php echo $row['language_name']; ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" class="form-control" rows="4"><?php echo htmlspecialchars($track['description'] ?? ''); ?></textarea>
                    </div>
                    
                    <label >Cover Image (JPG/PNG)</label>
                    <?php if($track['image']): ?>
                        <p style="margin-bottom: 10px; color: #666;">Current: <?php echo $track['image']; ?></p>
                    <?php endif; ?>
                    <input type="file" name="image" accept="image/*" style="margin-bottom: 18px;">
                    
                    <label >Audio File (MP3)</label>
                    <?php if($track['music_file']): ?>
                        <p style="margin-bottom: 10px; color: #666;">Current: <?php echo $track['music_file']; ?></p>
                    <?php endif; ?>
                    <input type="file" name="music_file" accept="audio/*" style="margin-bottom: 18px;">
                    
                    <label style="display: block; margin-bottom: 18px;"><input type="checkbox" name="is_new" value="1" <?php if($track['is_new']) echo 'checked'; ?>> Mark as New Release</label>
                    
                    <button type="submit" class="btn btn-primary btn-block">Update Music</button>
                </form>
            </div>
        </div>
    </main>
</div>

<script>
    document.getElementById('sidebarToggle')?.addEventListener('click', () => {
        document.getElementById('sidebar').classList.toggle('open');
    });
</script>


</body>
</html>
