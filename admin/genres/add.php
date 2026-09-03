<?php

require_once "../../includes/auth.php";
require_once "../../config/db.php";

requireAdmin();

$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $genre_name = trim($_POST["genre_name"]);

    if ($genre_name === "") {

        $message = "Genre name is required.";

    } else {

        $stmt = mysqli_prepare(
            $conn,
            "INSERT INTO genres (genre_name) VALUES (?)"
        );

        mysqli_stmt_bind_param($stmt, "s", $genre_name);

        if (mysqli_stmt_execute($stmt)) {

            header("Location: index.php");
            exit;

        } else {

            $message = "Genre already exists or could not be added.";

        }

        mysqli_stmt_close($stmt);
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Genre - SOUND Admin</title>
    <link rel="stylesheet" href="../../assets/css/style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../../assets/css/admin.css?v=<?php echo time(); ?>">
</head>
<body>

<div class="admin-layout">
    <?php 
    $current_page = 'genres';
    $admin_base = '../';
    include "../../includes/admin_header.php"; 
    ?>

    <main class="admin-main">
        <div class="admin-topbar">
            <div>
                <h1>Add Genre</h1>
                <div class="breadcrumb">
                    <a href="index.php" style="color: #666; text-decoration: none;">Genres</a> / Add New
                </div>
            </div>
        </div>

        <div class="admin-content">
            <div class="form-panel">
                <?php if ($message): ?>
                    <div style="color: #d00; margin-bottom: 15px;">
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php endif; ?>

                <form method="POST">
                    <div class="form-group">
                        <label>Genre Name</label>
                        <input type="text" name="genre_name" placeholder="e.g. Pop" maxlength="100" required class="form-control">
                    </div>

                    <button type="submit" class="btn btn-primary btn-block">Add Genre</button>
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
