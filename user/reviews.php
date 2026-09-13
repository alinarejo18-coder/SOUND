<?php
require_once "../includes/auth.php";
require_once "../config/db.php";
requireUser();

$user_id = $_SESSION["user_id"];
$message = "";

// Handle Delete
if (isset($_GET['delete'])) {
    $delete_id = intval($_GET['delete']);
    $stmt = mysqli_prepare($conn, "DELETE FROM reviews WHERE id = ? AND user_id = ?");
    mysqli_stmt_bind_param($stmt, "ii", $delete_id, $user_id);
    if (mysqli_stmt_execute($stmt)) {
        $message = "Review deleted successfully.";
    }
}

// Fetch Reviews
$query = "
    SELECT r.id, r.review, r.created_at,
           m.title AS music_title, 
           v.title AS video_title 
    FROM reviews r
    LEFT JOIN music m ON r.music_id = m.id
    LEFT JOIN videos v ON r.video_id = v.id
    WHERE r.user_id = ?
    ORDER BY r.created_at DESC
";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Reviews - SOUND</title>
    <link rel="stylesheet" href="../assets/css/style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../assets/css/admin.css?v=<?php echo time(); ?>">
    <script src="../assets/js/theme.js?v=<?php echo time(); ?>"></script>
</head>
<body>

<div class="admin-layout">

    <?php 
    $current_page = 'dashboard';
    $user_base = ''; 
    include "../includes/user_header.php"; 
    ?>

    <main class="admin-main">
        
        <div class="admin-topbar">
            <div>
                <h1>My Reviews <i data-lucide="notebook-pen" class="icon-ui"></i></h1>
                <div class="breadcrumb"><a href="dashboard.php">Dashboard</a> / My Reviews</div>
            </div>
            <div>

            </div>
        </div>

        <div class="admin-content">
            <?php if ($message): ?>
                <div style="background: rgba(16, 185, 129, 0.1); color: var(--accent-green); padding: 12px; border-radius: 6px; margin-bottom: 20px; font-weight: bold; border: 1px solid rgba(16, 185, 129, 0.3);">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Media</th>
                            <th>Review</th>
                            <th>Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (mysqli_num_rows($result) > 0): ?>
                        <?php while ($row = mysqli_fetch_assoc($result)): ?>
                            <tr>
                                <td style="white-space: nowrap;">
                                    <?php 
                                    if ($row['music_title']) {
                                        echo '<i data-lucide="music" class="icon-ui"></i> ' . htmlspecialchars($row['music_title']);
                                    } elseif ($row['video_title']) {
                                        echo '<i data-lucide="video" class="icon-ui"></i> ' . htmlspecialchars($row['video_title']);
                                    } else {
                                        echo 'Deleted Media';
                                    }
                                    ?>
                                </td>
                                <td><?php echo nl2br(htmlspecialchars($row['review'])); ?></td>
                                <td style="white-space: nowrap;"><?php echo date('M d, Y', strtotime($row['created_at'])); ?></td>
                                <td>
                                    <a href="?delete=<?php echo $row['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this review?');">Delete</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="4" style="text-align: center; padding: 20px; color: var(--text-muted);">You haven't written any reviews yet.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>

        </div>
        
        <?php include '../includes/footer.php'; ?>
    </main>
</div>

<script>
    document.getElementById('sidebarToggle')?.addEventListener('click', () => {
        document.getElementById('sidebar').classList.toggle('open');
    });
</script>


</body>
</html>
