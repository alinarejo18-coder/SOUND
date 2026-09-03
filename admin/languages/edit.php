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
$language_name_current = "";

$stmt = mysqli_prepare($conn, "SELECT language_name FROM languages WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
if ($row = mysqli_fetch_assoc($result)) {
    $language_name_current = $row['language_name'];
} else {
    header("Location: index.php");
    exit;
}
mysqli_stmt_close($stmt);

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $language_name = trim($_POST["language_name"]);

    if ($language_name === "") {
        $message = "Language name is required.";
    } else {
        $stmt = mysqli_prepare(
            $conn,
            "UPDATE languages SET language_name = ? WHERE id = ?"
        );

        mysqli_stmt_bind_param($stmt, "si", $language_name, $id);

        if (mysqli_stmt_execute($stmt)) {
            header("Location: index.php");
            exit;
        } else {
            $message = "Could not update language.";
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
    <title>Edit Language - SOUND Admin</title>
    <link rel="stylesheet" href="../../assets/css/style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../../assets/css/admin.css?v=<?php echo time(); ?>">
</head>
<body>

<div class="admin-layout">
    <?php 
    $current_page = 'languages';
    $admin_base = '../';
    include "../../includes/admin_header.php"; 
    ?>

    <main class="admin-main">
        <div class="admin-topbar">
            <div>
                <h1>Edit Language</h1>
                <div class="breadcrumb">
                    <a href="index.php" style="color: #666; text-decoration: none;">Languages</a> / Edit
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
                        <label>Language Name</label>
                        <input type="text" name="language_name" value="<?php echo htmlspecialchars($language_name_current); ?>" placeholder="e.g. English" maxlength="100" required class="form-control">
                    </div>

                    <button type="submit" class="btn btn-primary btn-block">Update Language</button>
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
