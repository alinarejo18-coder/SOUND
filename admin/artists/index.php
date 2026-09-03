<?php

require_once "../../includes/auth.php";
require_once "../../config/db.php";

requireAdmin();

$query = mysqli_query(
    $conn,
    "SELECT id, artist_name, created_at
     FROM artists
     ORDER BY id DESC"
);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Artists - SOUND Admin</title>
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
                <h1>Artists</h1>
                <div class="breadcrumb">Manage artists in the platform.</div>
            </div>
            <a href="add.php" class="btn btn-primary">+ Add Artist</a>
        </div>

        <div class="admin-content">
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Artist Name</th>
                            <th>Created At</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (mysqli_num_rows($query) > 0): ?>
                        <?php while ($artist = mysqli_fetch_assoc($query)): ?>
                            <tr>
                                <td><?php echo $artist["id"]; ?></td>
                                <td><?php echo htmlspecialchars($artist["artist_name"]); ?></td>
                                <td><?php echo htmlspecialchars($artist["created_at"]); ?></td>
                                <td style="white-space: nowrap;">
                                    <div style="display: flex; gap: 8px; align-items: center;">
                                        <a href="edit.php?id=<?php echo $artist["id"]; ?>" class="btn btn-sm btn-primary" style="margin: 0; padding: 6px 12px; font-size: 0.85rem;">Edit</a>
                                        <a href="delete.php?id=<?php echo $artist["id"]; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this artist?');" style="margin: 0; padding: 6px 12px; font-size: 0.85rem;">Delete</a>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="4" style="text-align: center; padding: 30px; color: #777;">No artists found.</td></tr>
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
