<?php
require_once "../../includes/auth.php";
require_once "../../config/db.php";

requireAdmin();

$query = mysqli_query(
    $conn,
    "SELECT m.id, m.title, a.artist_name, g.genre_name, m.created_at
     FROM music m
     LEFT JOIN artists a ON m.artist_id = a.id
     LEFT JOIN genres g ON m.genre_id = g.id
     ORDER BY m.id DESC"
);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Music - SOUND Admin</title>
    <link rel="stylesheet" href="../../assets/css/style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../../assets/css/admin.css?v=<?php echo time(); ?>">
</head>
<body>

<div class="admin-layout">

    <?php 
    $current_page = 'music';
    $admin_base = '../';
    include "../../includes/admin_header.php"; 
    ?>

    <main class="admin-main">
        <div class="admin-topbar">
            <div>
                <h1>Music</h1>
                <div class="breadcrumb">Manage music tracks in the platform.</div>
            </div>
            <div style="display: flex; gap: 10px;">
                <a href="add.php" class="btn btn-primary">+ Add Music</a>
                <a href="import.php" class="btn btn-primary" style="background-color: #00bcd4; border-color: #00bcd4; color: #fff;">Import from API</a>
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
                        <?php while ($music = mysqli_fetch_assoc($query)): ?>
                            <tr>
                                <td><?php echo $music["id"]; ?></td>
                                <td><?php echo htmlspecialchars($music["title"]); ?></td>
                                <td><?php echo htmlspecialchars($music["artist_name"] ?? 'Unknown'); ?></td>
                                <td><?php echo htmlspecialchars($music["genre_name"] ?? 'Unknown'); ?></td>
                                <td><?php echo htmlspecialchars($music["created_at"]); ?></td>
                                <td style="white-space: nowrap;">
                                    <div style="display: flex; gap: 8px; align-items: center;">
                                        <a href="edit.php?id=<?php echo $music["id"]; ?>" class="btn btn-sm btn-primary" style="margin: 0; padding: 6px 12px; font-size: 0.85rem;">Edit</a>
                                        <a href="delete.php?id=<?php echo $music["id"]; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this track?');" style="margin: 0; padding: 6px 12px; font-size: 0.85rem;">Delete</a>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="6" style="text-align: center; padding: 30px; color: #777;">No music tracks found.</td></tr>
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
