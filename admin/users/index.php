<?php
require_once "../../includes/auth.php";
require_once "../../config/db.php";

requireAdmin();

$query = mysqli_query(
    $conn,
    "SELECT id, user_id, name, email, role, created_at
     FROM users
     ORDER BY id DESC"
);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Users - SOUND Admin</title>
    <link rel="stylesheet" href="../../assets/css/style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../../assets/css/admin.css?v=<?php echo time(); ?>">
    <style>
        .badge { padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: bold; }
        .badge.admin { background: #fee2e2; color: #991b1b; }
        .badge.user { background: #e0e7ff; color: #3730a3; }
    </style>
</head>
<body>

<div class="admin-layout">

    <?php 
    $current_page = 'users';
    $admin_base = '../';
    include "../../includes/admin_header.php"; 
    ?>

    <main class="admin-main">
        <div class="admin-topbar">
            <div>
                <h1>Users</h1>
                <div class="breadcrumb">Manage users in the platform.</div>
            </div>
        </div>

        <div class="admin-content">
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Joined At</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (mysqli_num_rows($query) > 0): ?>
                        <?php while ($user = mysqli_fetch_assoc($query)): ?>
                            <tr>
                                <td><?php echo $user["id"]; ?></td>
                                <td><?php echo htmlspecialchars($user["name"]); ?></td>
                                <td><?php echo htmlspecialchars($user["email"]); ?></td>
                                <td>
                                    <span class="badge <?php echo $user['role']; ?>">
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($user["created_at"]); ?></td>
                                <td style="white-space: nowrap;">
                                    <a href="edit.php?id=<?php echo $user["id"]; ?>" class="btn btn-sm btn-primary" style="margin: 0; padding: 6px 12px; font-size: 0.85rem;">Edit</a>
                                    <?php if ($user["id"] != $_SESSION["user_id"]): ?>
                                        <a href="delete.php?id=<?php echo $user["id"]; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this user?');" style="margin: 0; padding: 6px 12px; font-size: 0.85rem;">Delete</a>
                                    <?php else: ?>
                                        <span style="color:#999; font-size:13px;">(You)</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="6" style="text-align: center; padding: 30px; color: #777;">No users found.</td></tr>
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
