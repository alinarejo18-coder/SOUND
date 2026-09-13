<?php
require_once "../../includes/auth.php";
require_once "../../config/db.php";

requireAdmin();

$id = intval($_GET['id'] ?? $_POST['id'] ?? 0);
$message = "";
$name = "";
$email = "";
$phone = "";
$address = "";
$user_profile_image = null;

if ($id <= 0) {
    header("Location: index.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (isset($_POST['action']) && $_POST['action'] === 'delete_image') {
        $stmt = mysqli_prepare($conn, "SELECT profile_image FROM users WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        if ($result && $result['profile_image']) {
            $file_path = "../../uploads/users/" . $result['profile_image'];
            if (file_exists($file_path)) unlink($file_path);
            
            $stmt = mysqli_prepare($conn, "UPDATE users SET profile_image = NULL WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "i", $id);
            mysqli_stmt_execute($stmt);
            $message = "Profile image removed successfully.";
        }
    } elseif (isset($_POST['action']) && $_POST['action'] === 'update_profile') {
        $name = trim($_POST['name'] ?? "");
        $email = trim($_POST['email'] ?? "");
        $phone = trim($_POST['phone'] ?? "");
        $address = trim($_POST['address'] ?? "");

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
                $update = mysqli_prepare($conn, "UPDATE users SET name = ?, email = ?, phone = ?, address = ? WHERE id = ?");
                mysqli_stmt_bind_param($update, "ssssi", $name, $email, $phone, $address, $id);

                if (mysqli_stmt_execute($update)) {
                    $message = "User updated successfully.";
                } else {
                    $message = "Could not update user.";
                }
                mysqli_stmt_close($update);

                // Handle image upload
                if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
                    $upload_dir = '../../uploads/users/';
                    if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
                    
                    $file_name = time() . '_' . basename($_FILES["profile_image"]["name"]);
                    $target_file = $upload_dir . $file_name;
                    if (move_uploaded_file($_FILES["profile_image"]["tmp_name"], $target_file)) {
                        $stmt = mysqli_prepare($conn, "SELECT profile_image FROM users WHERE id = ?");
                        mysqli_stmt_bind_param($stmt, "i", $id);
                        mysqli_stmt_execute($stmt);
                        $result = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
                        if ($result && $result['profile_image']) {
                            $old_file = $upload_dir . $result['profile_image'];
                            if (file_exists($old_file)) unlink($old_file);
                        }
                        $stmt = mysqli_prepare($conn, "UPDATE users SET profile_image = ? WHERE id = ?");
                        mysqli_stmt_bind_param($stmt, "si", $file_name, $id);
                        if(mysqli_stmt_execute($stmt)){
                            if($message === "User updated successfully.") {
                                $message .= " Image updated as well.";
                            } else {
                                $message = "Profile image updated successfully.";
                            }
                        }
                    }
                }
            }

            mysqli_stmt_close($check_email);
        }
    }
} 

// Fetch current user details
$stmt = mysqli_prepare($conn, "SELECT name, email, phone, address, profile_image FROM users WHERE id = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if ($user = mysqli_fetch_assoc($result)) {
    $name = $user['name'];
    $email = $user['email'];
    $phone = $user['phone'] ?? "";
    $address = $user['address'] ?? "";
    $user_profile_image = $user['profile_image'] ?? null;
} else {
    mysqli_stmt_close($stmt);
    header("Location: index.php");
    exit;
}

mysqli_stmt_close($stmt);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit User - SOUND Admin</title>
    <link rel="stylesheet" href="../../assets/css/style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../../assets/css/admin.css?v=<?php echo time(); ?>">
    <style>
        .profile-img-large {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid var(--accent);
            background: #282828;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 48px;
            color: #fff;
            margin-bottom: 15px;
        }
        .img-container {
            display: flex;
            align-items: center;
            gap: 30px;
            margin-bottom: 25px;
        }
        #profileImgInput { display: none; }
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
                <h1>Edit User</h1>
                <div class="breadcrumb"><a href="index.php">Users</a> / Edit</div>
            </div>
        </div>

        <div class="admin-content">
            <div class="form-panel" style="max-width: 800px;">
                <?php if ($message): ?>
                    <div style="color: #10b981; background: rgba(16,185,129,0.1); padding: 12px; border-radius: 6px; margin-bottom: 20px;">
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php endif; ?>

                <div class="img-container">
                    <?php if ($user_profile_image): ?>
                        <img src="../../uploads/users/<?php echo htmlspecialchars($user_profile_image); ?>" class="profile-img-large" alt="Profile">
                    <?php else: ?>
                        <div class="profile-img-large"><?php echo strtoupper(substr($name, 0, 1)); ?></div>
                    <?php endif; ?>
                    
                    <div>
                        <label class="btn btn-primary" for="profileImgInput" style="cursor: pointer; display: inline-block;">Change Image</label>
                        <?php if ($user_profile_image): ?>
                        <form method="POST" style="display: inline-block; margin-left: 10px;">
                            <input type="hidden" name="id" value="<?php echo $id; ?>">
                            <input type="hidden" name="action" value="delete_image">
                            <button type="submit" class="btn btn-danger" onclick="return confirm('Remove profile image?');">Remove Image</button>
                        </form>
                        <?php endif; ?>
                    </div>
                </div>

                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="id" value="<?php echo $id; ?>">
                    <input type="hidden" name="action" value="update_profile">
                    
                    <input type="file" id="profileImgInput" name="profile_image" accept="image/*" onchange="document.getElementById('fileName').textContent = this.files[0].name; document.getElementById('fileNameLabel').style.display = 'block';">
                    <div id="fileNameLabel" style="display: none; margin-bottom: 15px; color: var(--accent); font-size: 13px;">Selected file: <span id="fileName"></span></div>

                    <div class="form-group">
                        <label for="user-name">Name</label>
                        <input id="user-name" type="text" name="name" value="<?php echo htmlspecialchars($name); ?>" maxlength="100" required class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="user-email">Email</label>
                        <input id="user-email" type="email" name="email" value="<?php echo htmlspecialchars($email); ?>" maxlength="150" required class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="user-phone">Phone</label>
                        <input id="user-phone" type="text" name="phone" value="<?php echo htmlspecialchars($phone); ?>" maxlength="30" class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="user-address">Address</label>
                        <input id="user-address" type="text" name="address" value="<?php echo htmlspecialchars($address); ?>" maxlength="255" class="form-control">
                    </div>
                    
                    <button type="submit" class="btn btn-primary btn-block" style="margin-top: 20px;">Update User</button>
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
