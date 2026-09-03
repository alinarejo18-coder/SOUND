<?php

require_once "../../includes/auth.php";
require_once "../../config/db.php";

requireAdmin();

$id = intval($_GET["id"] ?? 0);

if ($id <= 0) {
    header("Location: index.php");
    exit;
}


// Get album
$stmt = mysqli_prepare(
    $conn,
    "SELECT id, album_name, artist_id
     FROM albums
     WHERE id = ?"
);

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $id
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);


if (mysqli_num_rows($result) !== 1) {

    header("Location: index.php");
    exit;
}


$album = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);


// Get artists
$artists_query = mysqli_query(
    $conn,
    "SELECT id, artist_name
     FROM artists
     ORDER BY artist_name ASC"
);


$message = "";


// Update
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $album_name = trim($_POST["album_name"] ?? "");
    $artist_id = intval($_POST["artist_id"] ?? 0);


    if ($album_name === "") {

        $message = "Album name is required.";

    } elseif ($artist_id <= 0) {

        $message = "Please select an artist.";

    } else {

        $update = mysqli_prepare(
            $conn,
            "UPDATE albums
             SET album_name = ?, artist_id = ?
             WHERE id = ?"
        );

        mysqli_stmt_bind_param(
            $update,
            "sii",
            $album_name,
            $artist_id,
            $id
        );


        if (mysqli_stmt_execute($update)) {

            header("Location: index.php");
            exit;

        } else {

            $message = "Unable to update album.";
        }

        mysqli_stmt_close($update);
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Album - SOUND Admin</title>
    <link rel="stylesheet" href="../../assets/css/style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../../assets/css/admin.css?v=<?php echo time(); ?>">
</head>
<body>

<div class="admin-layout">
    <?php 
    $current_page = 'albums';
    $admin_base = '../';
    include "../../includes/admin_header.php"; 
    ?>

    <main class="admin-main">
        <div class="admin-topbar">
            <div>
                <h1>Edit Album</h1>
                <div class="breadcrumb">
                    <a href="index.php" style="color: #666; text-decoration: none;">Albums</a> / Edit
                </div>
            </div>
        </div>

        <div class="admin-content">
            <div class="form-panel">
                <?php if ($message !== ""): ?>
                    <div style="background: #fee2e2; color: #991b1b; padding: 12px; border-radius: 7px; margin-bottom: 18px;">
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php endif; ?>

                <form method="POST">
                    <div class="form-group">
                        <label>Album Name</label>
                        <input type="text" name="album_name" value="<?php echo htmlspecialchars($album["album_name"]); ?>" required class="form-control">
                    </div>

                    <div class="form-group">
                        <label>Artist</label>
                        <select name="artist_id" required class="form-control">
                            <option value="">Select Artist</option>
                            <?php while ($artist = mysqli_fetch_assoc($artists_query)): ?>
                                <option value="<?php echo $artist["id"]; ?>" <?php if ($artist["id"] == $album["artist_id"]) echo "selected"; ?>>
                                    <?php echo htmlspecialchars($artist["artist_name"]); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <button type="submit" class="btn btn-primary btn-block">Update Album</button>
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
