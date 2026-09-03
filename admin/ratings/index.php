<?php
require_once "../../includes/auth.php";
require_once "../../config/db.php";
requireAdmin();

$message = "";

// Handle Delete
if (isset($_GET['delete'])) {
    $delete_id = intval($_GET['delete']);
    $stmt = mysqli_prepare($conn, "DELETE FROM ratings WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $delete_id);
    if (mysqli_stmt_execute($stmt)) {
        $message = "Rating deleted successfully.";
    }
}

// Fetch All Ratings
$query = "
    SELECT r.id, r.rating, 
           u.name AS user_name, u.email AS user_email,
           m.title AS music_title, 
           v.title AS video_title 
    FROM ratings r
    JOIN users u ON r.user_id = u.id
    LEFT JOIN music m ON r.music_id = m.id
    LEFT JOIN videos v ON r.video_id = v.id
    ORDER BY r.id DESC
";
$result = mysqli_query($conn, $query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Ratings - SOUND Admin</title>
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
                <h1>Manage Ratings <i data-lucide="star" class="icon-ui"></i></h1>
                <div class="breadcrumb"><a href="../dashboard.php">Dashboard</a> / Ratings</div>
            </div>
        </div>

        <div class="admin-content">
            <?php if ($message): ?>
                <div style="background: #064e3b; color: #34d399; padding: 12px; border-radius: 6px; margin-bottom: 20px;">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Media</th>
                            <th>Rating</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (mysqli_num_rows($result) > 0): ?>
                        <?php while ($row = mysqli_fetch_assoc($result)): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($row['user_name']); ?></strong><br>
                                    <small style="color: var(--text-muted);"><?php echo htmlspecialchars($row['user_email']); ?></small>
                                </td>
                                <td>
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
                                <td>
                                    <?php 
                                    $rating = $row['rating'];
                                    echo str_repeat('<i data-lucide="star" class="icon-ui"></i>', $rating) . str_repeat('<i data-lucide="star" class="icon-ui" style="opacity:.28"></i>', 5 - $rating);
                                    ?>
                                </td>
                                <td style="white-space: nowrap;">
                                    <div style="display: flex; gap: 8px; align-items: center;">
                                        <a href="edit.php?id=<?php echo $row['id']; ?>" class="btn btn-sm" style="background: var(--accent-cyan); color: #fff; margin: 0; padding: 6px 12px; font-size: 0.85rem;">Edit</a>
                                        <a href="?delete=<?php echo $row['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this rating?');" style="margin: 0; padding: 6px 12px; font-size: 0.85rem;">Delete</a>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="4" style="text-align: center; padding: 20px; color: #888;">No ratings found.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
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
