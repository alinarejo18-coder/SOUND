<?php
require_once "../../includes/auth.php";
require_once "../../config/db.php";

requireAdmin();

$id = intval($_GET['id'] ?? $_POST['id'] ?? 0);
$message = "";
$name = "";
$email = "";

if ($id <= 0) {
    header("Location: index.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = trim($_POST['name'] ?? "");
    $email = trim($_POST['email'] ?? "");

    if ($name === "" || $email === "") {
        $message = "Name and email are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Please enter a valid email address.";
    } else {
        $check_email = mysqli_prepare($conn, "SELECT id FROM users WHERE email = ? AND id != ? LIMIT 1");
        mysqli_stmt_bind_param($check_email, "si", $email, $id);
        mysqli_stmt_execute($check_email);
        mysqli_stmt_store_result($check_email);

        if (mysqli_stmt_num_rows($check_email) > 0) {
            $message = "This email address is already in use.";
        } else {
            $update = mysqli_prepare($conn, "UPDATE users SET name = ?, email = ? WHERE id = ?");
            mysqli_stmt_bind_param($update, "ssi", $name, $email, $id);

            if (mysqli_stmt_execute($update)) {
                mysqli_stmt_close($update);
                mysqli_stmt_close($check_email);
                header("Location: index.php");
                exit;
            }

            $message = "Could not update user.";
            mysqli_stmt_close($update);
        }

        mysqli_stmt_close($check_email);
    }
} else {
    $stmt = mysqli_prepare($conn, "SELECT name, email FROM users WHERE id = ? LIMIT 1");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if ($user = mysqli_fetch_assoc($result)) {
        $name = $user['name'];
        $email = $user['email'];
    } else {
        mysqli_stmt_close($stmt);
        header("Location: index.php");
        exit;
    }

    mysqli_stmt_close($stmt);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit User - SOUND Admin</title>
    <link rel="stylesheet" href="../../assets/css/style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../../assets/css/admin.css?v=<?php echo time(); ?>">
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
                <h1>Edit User</h1>
                <div class="breadcrumb"><a href="index.php">Users</a> / Edit</div>
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
                    <input type="hidden" name="id" value="<?php echo $id; ?>">
                    <div class="form-group">
                        <label for="user-name">Name</label>
                        <input id="user-name" type="text" name="name" value="<?php echo htmlspecialchars($name); ?>" maxlength="100" required class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="user-email">Email</label>
                        <input id="user-email" type="email" name="email" value="<?php echo htmlspecialchars($email); ?>" maxlength="150" required class="form-control">
                    </div>
                    <button type="submit" class="btn btn-primary btn-block">Update User</button>
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
