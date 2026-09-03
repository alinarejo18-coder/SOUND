<?php

require_once "../../includes/auth.php";
require_once "../../config/db.php";

requireAdmin();

$id = intval($_GET["id"] ?? 0);

if ($id <= 0) {
    header("Location: index.php");
    exit;
}


// Get artist
$stmt = mysqli_prepare(
    $conn,
    "SELECT id, artist_name
     FROM artists
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

$artist = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);


$message = "";


// Update artist
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $artist_name = trim($_POST["artist_name"] ?? "");

    if ($artist_name === "") {

        $message = "Artist name is required.";

    } else {

        $update = mysqli_prepare(
            $conn,
            "UPDATE artists
             SET artist_name = ?
             WHERE id = ?"
        );

        mysqli_stmt_bind_param(
            $update,
            "si",
            $artist_name,
            $id
        );

        if (mysqli_stmt_execute($update)) {

            header("Location: index.php");
            exit;

        } else {

            $message = "Unable to update artist.";
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
    <title>Edit Artist - SOUND Admin</title>
    <link rel="stylesheet" href="../../assets/css/style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../../assets/css/admin.css?v=<?php echo time(); ?>">
</head>
<body>

<div class="admin-layout">

    <?php 
    $current_page = 'artists';
    $admin_base = '../';
    include "../../includes/admin_header.php"; 
    ?>

    <main class="admin-main">
        <div class="admin-topbar">
            <div>
                <h1>Edit Artist</h1>
                <div class="breadcrumb"><a href="index.php">Artists</a> / Edit</div>
            </div>
        </div>

        <div class="admin-content">
            <div class="form-panel">
                <?php if ($message !== ""): ?>
                    <div style="background: #fee2e2; color: #991b1b; padding: 12px; border-radius: 7px; margin-bottom: 18px;"><?php echo htmlspecialchars($message); ?></div>
                <?php endif; ?>
                
                <form method="POST">
                    <div class="form-group">
                        <label>Artist Name *</label>
                        <input type="text" name="artist_name" value="<?php echo htmlspecialchars($artist["artist_name"]); ?>" required class="form-control">
                    </div>
                    
                    <button type="submit" class="btn btn-primary btn-block">Update Artist</button>
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
