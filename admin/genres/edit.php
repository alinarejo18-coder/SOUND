<?php

require_once "../../includes/auth.php";
require_once "../../config/db.php";

requireAdmin();

$message = "";

if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit;
}

$id = (int)$_GET['id'];
$genre_name_current = "";

$stmt = mysqli_prepare($conn, "SELECT genre_name FROM genres WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
if ($row = mysqli_fetch_assoc($result)) {
    $genre_name_current = $row['genre_name'];
} else {
    header("Location: index.php");
    exit;
}
mysqli_stmt_close($stmt);

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $genre_name = trim($_POST["genre_name"]);

    if ($genre_name === "") {
        $message = "Genre name is required.";
    } else {
        $stmt = mysqli_prepare(
            $conn,
            "UPDATE genres SET genre_name = ? WHERE id = ?"
        );

        mysqli_stmt_bind_param($stmt, "si", $genre_name, $id);

        if (mysqli_stmt_execute($stmt)) {
            header("Location: index.php");
            exit;
        } else {
            $message = "Could not update genre.";
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
    <title>Edit Genre - SOUND Admin</title>
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
                <h1>Edit Genre</h1>
                <div class="breadcrumb">
                    <a href="index.php" style="color: #666; text-decoration: none;">Genres</a> / Edit
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
                        <input type="text" name="genre_name" value="<?php echo htmlspecialchars($genre_name_current); ?>" placeholder="e.g. Pop" maxlength="100" required class="form-control">
                    </div>

                    <button type="submit" class="btn btn-primary btn-block">Update Genre</button>
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
