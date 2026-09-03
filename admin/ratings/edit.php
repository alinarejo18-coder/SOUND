<?php
require_once "../../includes/auth.php";
require_once "../../config/db.php";
requireAdmin();

$message = "";
$error = "";

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id === 0) {
    header("Location: index.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $new_rating = intval($_POST['rating']);
    if ($new_rating >= 1 && $new_rating <= 5) {
        $stmt = mysqli_prepare($conn, "UPDATE ratings SET rating = ? WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "ii", $new_rating, $id);
        if (mysqli_stmt_execute($stmt)) {
            $message = "Rating updated successfully!";
        } else {
            $error = "Failed to update rating.";
        }
    }
}

// Fetch Current Rating
$stmt = mysqli_prepare($conn, "SELECT r.*, u.name as user_name FROM ratings r JOIN users u ON r.user_id = u.id WHERE r.id = ?");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$rating = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$rating) {
    die("Rating not found.");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Rating - SOUND Admin</title>
    <link rel="stylesheet" href="../../assets/css/style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../../assets/css/admin.css?v=<?php echo time(); ?>">
</head>
<body>

<div class="admin-layout">

    <?php 
    $current_page = 'ratings';
    $admin_base = '../'; 
    include "../../includes/admin_header.php"; 
    ?>

    <main class="admin-main">
        
        <div class="admin-topbar">
            <div>
                <h1>Edit Rating</h1>
                <div class="breadcrumb"><a href="../dashboard.php">Dashboard</a> / <a href="index.php">Ratings</a> / Edit</div>
            </div>
        </div>

        <div class="admin-content">
            <?php if ($message): ?>
                <div style="background: #064e3b; color: #34d399; padding: 12px; border-radius: 6px; margin-bottom: 20px;">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div style="background: #7f1d1d; color: #fca5a5; padding: 12px; border-radius: 6px; margin-bottom: 20px;">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <div class="form-container">
                <form method="POST">
                    <div class="form-group">
                        <label>User</label>
                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($rating['user_name']); ?>" disabled>
                    </div>
                    
                    <div class="form-group">
                        <label>Rating</label>
                        <select name="rating" class="form-control" required style="background: #222; color: #fff;">
                            <option value="5" <?php echo ($rating['rating']==5)?'selected':''; ?>>5 / 5</option>
                            <option value="4" <?php echo ($rating['rating']==4)?'selected':''; ?>>4 / 5</option>
                            <option value="3" <?php echo ($rating['rating']==3)?'selected':''; ?>>3 / 5</option>
                            <option value="2" <?php echo ($rating['rating']==2)?'selected':''; ?>>2 / 5</option>
                            <option value="1" <?php echo ($rating['rating']==1)?'selected':''; ?>>1 / 5</option>
                        </select>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Update Rating</button>
                    <a href="index.php" class="btn btn-outline" style="margin-left: 10px;">Cancel</a>
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
