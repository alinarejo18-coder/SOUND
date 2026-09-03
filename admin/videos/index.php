<?php
require_once "../../includes/auth.php";
require_once "../../config/db.php";

requireAdmin();

$query = mysqli_query(
    $conn,
    "SELECT v.id, v.title, a.artist_name, g.genre_name, v.created_at
     FROM videos v
     LEFT JOIN artists a ON v.artist_id = a.id
     LEFT JOIN genres g ON v.genre_id = g.id
     ORDER BY v.id DESC"
);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Videos - SOUND Admin</title>
    <link rel="stylesheet" href="../../assets/css/style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../../assets/css/admin.css?v=<?php echo time(); ?>">
</head>
<body>

<div class="admin-layout">

    <?php 
    $current_page = 'videos';
    $admin_base = '../';
    include "../../includes/admin_header.php"; 
    ?>

    <main class="admin-main">
        <div class="admin-topbar">
            <div>
                <h1>Videos</h1>
                <div class="breadcrumb">Manage video tracks in the platform.</div>
            </div>
            <div style="display: flex; gap: 10px;">
                <a href="import.php" class="btn btn-secondary">Import from API</a>
                <a href="add.php" class="btn btn-primary">+ Add Video</a>
            </div>
        </div>

        <div class="admin-content">
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Title</th>
                            <th>Artist</th>
                            <th>Genre</th>
                            <th>Created At</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (mysqli_num_rows($query) > 0): ?>
                        <?php while ($video = mysqli_fetch_assoc($query)): ?>
                            <tr>
                                <td><?php echo $video["id"]; ?></td>
                                <td><?php echo htmlspecialchars($video["title"]); ?></td>
                                <td><?php echo htmlspecialchars($video["artist_name"] ?? 'Unknown'); ?></td>
                                <td><?php echo htmlspecialchars($video["genre_name"] ?? 'Unknown'); ?></td>
                                <td><?php echo htmlspecialchars($video["created_at"]); ?></td>
                                <td style="white-space: nowrap;">
                                    <div style="display: flex; gap: 8px; align-items: center;">
                                        <a href="edit.php?id=<?php echo $video["id"]; ?>" class="btn btn-sm btn-primary" style="margin: 0; padding: 6px 12px; font-size: 0.85rem;">Edit</a>
                                        <a href="delete.php?id=<?php echo $video["id"]; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this video?');" style="margin: 0; padding: 6px 12px; font-size: 0.85rem;">Delete</a>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="6" style="text-align: center; padding: 30px; color: #777;">No videos found.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>
<?php include '../../includes/admin_footer.php'; ?>

<script>
    document.getElementById('sidebarToggle')?.addEventListener('click', () => {
        document.getElementById('sidebar').classList.toggle('open');
    });
</script>


</body>
</html>
