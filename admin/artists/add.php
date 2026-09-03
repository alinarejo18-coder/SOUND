<?php

require_once "../../includes/auth.php";
require_once "../../config/db.php";

requireAdmin();

$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $artist_name = trim($_POST["artist_name"] ?? "");

    if ($artist_name === "") {

        $message = "Artist name is required.";

    } else {

        $check = mysqli_prepare(
            $conn,
            "SELECT id FROM artists WHERE artist_name = ?"
        );

        mysqli_stmt_bind_param(
            $check,
            "s",
            $artist_name
        );

        mysqli_stmt_execute($check);

        $result = mysqli_stmt_get_result($check);

        if (mysqli_num_rows($result) > 0) {

            $message = "This artist already exists.";

        } else {

            $stmt = mysqli_prepare(
                $conn,
                "INSERT INTO artists (artist_name)
                 VALUES (?)"
            );

            mysqli_stmt_bind_param(
                $stmt,
                "s",
                $artist_name
            );

            if (mysqli_stmt_execute($stmt)) {

                header("Location: index.php");
                exit;

            } else {

                $message = "Failed to add artist.";
            }

            mysqli_stmt_close($stmt);
        }

        mysqli_stmt_close($check);
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Artist - SOUND Admin</title>
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
                <h1>Add Artist</h1>
                <div class="breadcrumb"><a href="index.php">Artists</a> / Add</div>
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
                        <input type="text" name="artist_name" required class="form-control">
                    </div>
                    
                    <button type="submit" class="btn btn-primary btn-block">Add Artist</button>
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
