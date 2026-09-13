<?php
require_once "../includes/auth.php";
require_once "../config/db.php";
requireAdmin();

$admin_user_id = $_SESSION['user_id'];
$success_msg = "";
$error_msg = "";

// Handle Remove Image
if (isset($_POST['remove_image'])) {
    $q = mysqli_query($conn, "SELECT profile_image FROM admins WHERE id = '$admin_user_id'");
    $row = mysqli_fetch_assoc($q);
    if ($row && !empty($row['profile_image'])) {
        $old_file = "../assets/uploads/profiles/" . $row['profile_image'];
        if (file_exists($old_file)) {
            unlink($old_file);
        }
        mysqli_query($conn, "UPDATE admins SET profile_image = NULL WHERE id = '$admin_user_id'");
        $success_msg = "Profile image removed successfully!";
    }
}

// Handle Profile Image Update
if (isset($_POST['upload_image'])) {
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0) {
        $filename = $_FILES['profile_image']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        $new_filename = uniqid('profile_') . "." . $ext;
        $dest = "../assets/uploads/profiles/" . $new_filename;
        
        if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $dest)) {
            // Delete old image
            $q = mysqli_query($conn, "SELECT profile_image FROM admins WHERE id = '$admin_user_id'");
            $row = mysqli_fetch_assoc($q);
            if ($row && !empty($row['profile_image'])) {
                $old_file = "../assets/uploads/profiles/" . $row['profile_image'];
                if (file_exists($old_file)) {
                    unlink($old_file);
                }
            }
            
            $check_admin = mysqli_query($conn, "SELECT id FROM admins WHERE id = '$admin_user_id'");
            if (mysqli_num_rows($check_admin) == 0) {
                // Fetch info from users table to insert a complete row
                $q_old = mysqli_query($conn, "SELECT name, email, password FROM users WHERE id = '$admin_user_id'");
                $row_old = mysqli_fetch_assoc($q_old);
                $name_old = mysqli_real_escape_string($conn, $row_old['name'] ?? 'Admin');
                $email_old = mysqli_real_escape_string($conn, $row_old['email'] ?? '');
                $password_old = $row_old['password'] ?? '';
                
                $query = "INSERT INTO admins (id, name, email, password, profile_image) VALUES ('$admin_user_id', '$name_old', '$email_old', '$password_old', '$new_filename')";
            } else {
                $query = "UPDATE admins SET profile_image = '$new_filename' WHERE id = '$admin_user_id'";
            }
            
            if (mysqli_query($conn, $query)) {
                $success_msg = "Profile image updated successfully!";
            } else {
                $error_msg = "Database error: " . mysqli_error($conn);
            }
        } else {
            $error_msg = "Failed to move uploaded file to destination.";
        }
    } elseif (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] != UPLOAD_ERR_NO_FILE) {
        $error_msg = "Image upload failed with error code: " . $_FILES['profile_image']['error'];
    } else {
        $error_msg = "Please select an image to upload.";
    }
}

// Handle Profile Update
if (isset($_POST['update_profile'])) {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = $_POST['password'];

    // Update password if provided
    $password_query = "";
    if (!empty($password)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $password_query = ", password = '$hashed_password'";
    }

    $check_admin = mysqli_query($conn, "SELECT id FROM admins WHERE id = '$admin_user_id'");
        
        if (mysqli_num_rows($check_admin) == 0) {
            // Insert into admins if not exists
            if (empty($password)) {
                $q_old = mysqli_query($conn, "SELECT password FROM users WHERE id = '$admin_user_id'");
                $row_old = mysqli_fetch_assoc($q_old);
                $hashed_password = $row_old['password'] ?? '';
            }
            $img_val = isset($new_filename) && !empty($new_filename) ? "'$new_filename'" : "NULL";
            $query = "INSERT INTO admins (id, name, email, password, profile_image) VALUES ('$admin_user_id', '$name', '$email', '$hashed_password', $img_val)";
        } else {
            // Update existing admin
            $query = "UPDATE admins SET name = '$name', email = '$email' $password_query WHERE id = '$admin_user_id'";
        }
        
        if (mysqli_query($conn, $query)) {
            $success_msg = "Profile updated successfully!";
            $_SESSION['name'] = $name; // Update session name
        } else {
            $error_msg = "Database error: " . mysqli_error($conn);
        }
}

// Handle Profile Deletion
if (isset($_POST['delete_profile'])) {
    $del_query = mysqli_query($conn, "DELETE FROM admins WHERE id = '$admin_user_id'");
    if ($del_query) {
        header("Location: ../logout.php");
        exit;
    } else {
        $error_msg = "Failed to delete account from admins table: " . mysqli_error($conn);
    }
}

// Fetch current details
$q = mysqli_query($conn, "SELECT * FROM admins WHERE id = '$admin_user_id'");
if (mysqli_num_rows($q) == 0) {
    // Fallback to users table if they haven't been inserted into admins yet
    $q = mysqli_query($conn, "SELECT * FROM users WHERE id = '$admin_user_id'");
}
$admin_data = mysqli_fetch_assoc($q);

$current_page = 'profile';
$admin_base = '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Profile - SOUND</title>
    <link rel="stylesheet" href="../assets/css/style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../assets/css/admin.css?v=<?php echo time(); ?>">
    <style>
        .profile-container {
            max-width: 800px;
            margin: 0 auto;
        }
        .profile-actions {
            display: flex;
            gap: 10px;
        }
        .msg {
            padding: 16px;
            border-radius: var(--radius-md);
            margin-bottom: 24px;
            font-weight: 600;
        }
        .msg.success { background: rgba(16, 185, 129, 0.1); color: #10b981; border: 1px solid rgba(16, 185, 129, 0.2); }
        .msg.error { background: rgba(239, 68, 68, 0.1); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.2); }
        
        .profile-header-card {
            display: flex;
            gap: 30px;
            align-items: center;
            margin-bottom: 40px;
        }
        .profile-pic-large {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: var(--gradient-primary);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 40px;
            font-weight: 800;
            color: var(--text-light);
            border: 4px solid var(--admin-bg);
            box-shadow: var(--shadow-sm);
            overflow: hidden;
            flex-shrink: 0;
        }
        .profile-pic-large img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
    </style>
</head>
<body class="admin-layout">

    <?php include "../includes/admin_header.php"; ?>

    <!-- Main Content -->
    <main class="admin-main">
        <header class="admin-topbar">
            <h1>My Profile</h1>
            <div class="topbar-actions">
                <span class="date-display"><?php echo date('l, F j, Y'); ?></span>
            </div>
        </header>

        <div class="admin-content">
            <div class="profile-container">
                <?php if (!empty($success_msg)): ?>
                    <div class="msg success"><?php echo $success_msg; ?></div>
                <?php endif; ?>
                <?php if (!empty($error_msg)): ?>
                    <div class="msg error"><?php echo $error_msg; ?></div>
                <?php endif; ?>

                <div class="form-panel profile-header-card">
                    <div class="profile-pic-large">
                        <?php if (!empty($admin_data['profile_image'])): ?>
                            <img src="../assets/uploads/profiles/<?php echo htmlspecialchars($admin_data['profile_image']); ?>" alt="Profile Image">
                        <?php else: ?>
                            <?php echo !empty($admin_data['name']) ? strtoupper(substr($admin_data['name'], 0, 1)) : 'A'; ?>
                        <?php endif; ?>
                    </div>
                    
                    <div class="profile-actions" style="flex: 1; display: flex; flex-direction: column;">
                        <h2 style="margin: 0 0 4px 0; color: var(--text-main); font-weight: 800; font-size: 24px;"><?php echo htmlspecialchars($admin_data['name'] ?? 'Admin'); ?></h2>
                        <p style="color: var(--text-muted); margin: 0 0 20px 0; font-weight: 500;">Manage your profile image.</p>
                        
                        <div style="display: flex; gap: 12px; align-items: center; flex-wrap: wrap;">
                            <form method="POST" enctype="multipart/form-data" style="display: flex; align-items: center; gap: 10px;">
                                <input type="file" name="profile_image" accept="image/*" required class="form-control" style="padding: 8px; max-width: 250px;">
                                <button type="submit" name="upload_image" class="btn btn-primary">Upload</button>
                            </form>
                            
                            <?php if (!empty($admin_data['profile_image'])): ?>
                            <form method="POST" style="margin: 0;">
                                <button type="submit" name="remove_image" class="btn btn-danger" onclick="return confirm('Are you sure you want to remove your profile picture?');">Remove</button>
                            </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <form method="POST" class="form-panel">
                    <h3 style="margin-top: 0; margin-bottom: 24px; color: var(--text-main); font-weight: 800; font-size: 20px;">Update Details</h3>

                    <div class="form-group">
                        <label>Full Name</label>
                        <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($admin_data['name'] ?? ''); ?>" required>
                    </div>

                    <div class="form-group">
                        <label>Email Address</label>
                        <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($admin_data['email'] ?? ''); ?>" required>
                    </div>

                    <div class="form-group">
                        <label>New Password (leave blank to keep current)</label>
                        <input type="password" name="password" class="form-control" placeholder="Enter new password">
                    </div>

                    <div style="display: flex; gap: 16px; margin-top: 32px;">
                        <button type="submit" name="update_profile" class="btn btn-primary" style="flex: 1;">Save Changes</button>
                        <button type="submit" name="delete_profile" class="btn btn-danger" style="flex: 1;" onclick="return confirm('Are you sure you want to completely delete your admin profile? You will be logged out.');">Delete Account</button>
                    </div>
                </form>
            </div>
        </div>
        
        <?php include '../includes/footer.php'; ?>
    </main>

    <script>
        // Use existing sidebar toggle script if any, or a simple one
        document.addEventListener('DOMContentLoaded', () => {
            const toggle = document.getElementById('sidebarToggle');
            const sidebar = document.getElementById('sidebar');
            if(toggle && sidebar) {
                toggle.addEventListener('click', () => {
                    sidebar.classList.toggle('active');
                });
            }
        });
    </script>

</body>
</html>
