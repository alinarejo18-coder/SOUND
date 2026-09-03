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
    $new_review = trim($_POST['review']);
    if (!empty($new_review)) {
        $stmt = mysqli_prepare($conn, "UPDATE reviews SET review = ? WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "si", $new_review, $id);
        if (mysqli_stmt_execute($stmt)) {
            $message = "Review updated successfully!";
        } else {
            $error = "Failed to update review.";
        }
    }
}

// Fetch Current Review
$stmt = mysqli_prepare($conn, "SELECT r.*, u.name as user_name FROM reviews r JOIN users u ON r.user_id = u.id WHERE r.id = ?");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$review = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$review) {
    die("Review not found.");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Review - SOUND Admin</title>
    <link rel="stylesheet" href="../../assets/css/style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../../assets/css/admin.css?v=<?php echo time(); ?>">
</head>
<body>

<div class="admin-layout">

    <?php 
    $current_page = 'reviews';
    $admin_base = '../'; 
    include "../../includes/admin_header.php"; 
    ?>

    <main class="admin-main">
        
        <div class="admin-topbar">
            <div>
                <h1>Edit Review</h1>
                <div class="breadcrumb"><a href="../dashboard.php">Dashboard</a> / <a href="index.php">Reviews</a> / Edit</div>
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
                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($review['user_name']); ?>" disabled>
                    </div>
                    
                    <div class="form-group">
                        <label>Review</label>
                        <textarea name="review" class="form-control" rows="5" required style="background: #222; color: #fff; padding: 10px; border-radius: 8px; border: 1px solid #444; width: 100%;"><?php echo htmlspecialchars($review['review']); ?></textarea>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Update Review</button>
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
