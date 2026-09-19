<?php
require_once "../includes/auth.php";
require_once "../config/db.php";
requireUser();

$user_id = isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : 0;
$message = "";
$error = "";

function fetchAssocFromStatement($stmt)
{
    if (!$stmt) {
        return null;
    }

    if (function_exists('mysqli_stmt_get_result')) {
        $result = mysqli_stmt_get_result($stmt);
        if ($result === false) {
            return null;
        }
        return mysqli_fetch_assoc($result);
    }

    if (!mysqli_stmt_store_result($stmt)) {
        return null;
    }

    $meta = mysqli_stmt_result_metadata($stmt);
    if (!$meta) {
        return null;
    }

    $fields = $meta->fetch_fields();
    $binds = [];
    foreach ($fields as $field) {
        $binds[] = &$row[$field->name];
    }

    if (!mysqli_stmt_bind_result($stmt, ...$binds)) {
        return null;
    }

    if (!mysqli_stmt_fetch($stmt)) {
        return null;
    }

    return $row;
}

// Handle Profile Updates
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'delete_image') {
        $stmt = mysqli_prepare($conn, "SELECT profile_image FROM users WHERE id = ?");
        if ($stmt && mysqli_stmt_bind_param($stmt, "i", $user_id) && mysqli_stmt_execute($stmt)) {
            $result = fetchAssocFromStatement($stmt);
            if ($result && !empty($result['profile_image'])) {
                $file_path = "../uploads/users/" . $result['profile_image'];
                if (file_exists($file_path)) {
                    unlink($file_path);
                }

                $update_stmt = mysqli_prepare($conn, "UPDATE users SET profile_image = NULL WHERE id = ?");
                if ($update_stmt && mysqli_stmt_bind_param($update_stmt, "i", $user_id) && mysqli_stmt_execute($update_stmt)) {
                    $message = "Profile image removed successfully.";
                } else {
                    $error = "Failed to remove profile image.";
                }
            }
        } else {
            $error = "Failed to load profile image.";
        }
    } elseif (isset($_POST['action']) && $_POST['action'] === 'update_profile') {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $date_of_birth = trim($_POST['date_of_birth'] ?? '');
        $gender = trim($_POST['gender'] ?? '');
        $bio = trim($_POST['bio'] ?? '');
        $country = trim($_POST['country'] ?? '');
        $city = trim($_POST['city'] ?? '');

        if (empty($name) || empty($email)) {
            $error = "Name and Email are required.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Invalid email format.";
        } else {
            $check_stmt = mysqli_prepare($conn, "SELECT id FROM users WHERE email = ? AND id != ?");
            if ($check_stmt && mysqli_stmt_bind_param($check_stmt, "si", $email, $user_id) && mysqli_stmt_execute($check_stmt)) {
                mysqli_stmt_store_result($check_stmt);
                if (mysqli_stmt_num_rows($check_stmt) > 0) {
                    $error = "Email is already in use.";
                } else {
                    $user_update_stmt = mysqli_prepare($conn, "UPDATE users SET name = ?, email = ?, phone = ?, address = ? WHERE id = ?");
                    if ($user_update_stmt && mysqli_stmt_bind_param($user_update_stmt, "ssssi", $name, $email, $phone, $address, $user_id) && mysqli_stmt_execute($user_update_stmt)) {
                        $_SESSION['name'] = $name;
                        $profile_date = ($date_of_birth === '') ? null : $date_of_birth;

                        $profile_check_stmt = mysqli_prepare($conn, "SELECT id FROM user_profiles WHERE user_id = ?");
                        if ($profile_check_stmt && mysqli_stmt_bind_param($profile_check_stmt, "i", $user_id) && mysqli_stmt_execute($profile_check_stmt)) {
                            mysqli_stmt_store_result($profile_check_stmt);
                            $has_profile = mysqli_stmt_num_rows($profile_check_stmt) > 0;

                            if ($has_profile) {
                                $profile_update_stmt = mysqli_prepare($conn, "UPDATE user_profiles SET date_of_birth = ?, gender = ?, bio = ?, country = ?, city = ? WHERE user_id = ?");
                                if ($profile_update_stmt && mysqli_stmt_bind_param($profile_update_stmt, "sssssi", $profile_date, $gender, $bio, $country, $city, $user_id) && mysqli_stmt_execute($profile_update_stmt)) {
                                    $profile_success = true;
                                } else {
                                    $profile_success = false;
                                }
                            } else {
                                $profile_insert_stmt = mysqli_prepare($conn, "INSERT INTO user_profiles (user_id, date_of_birth, gender, bio, country, city) VALUES (?, ?, ?, ?, ?, ?)");
                                if ($profile_insert_stmt && mysqli_stmt_bind_param($profile_insert_stmt, "isssss", $user_id, $profile_date, $gender, $bio, $country, $city) && mysqli_stmt_execute($profile_insert_stmt)) {
                                    $profile_success = true;
                                } else {
                                    $profile_success = false;
                                }
                            }

                            if ($profile_success) {
                                $message = "Profile updated successfully.";
                            } else {
                                $error = "Failed to update profile.";
                            }
                        } else {
                            $error = "Failed to update profile details.";
                        }
                    } else {
                        $error = "Failed to update profile.";
                    }
                }
            } else {
                $error = "Failed to validate email.";
            }
        }

        if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = '../uploads/users/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            $file_name = time() . '_' . basename($_FILES["profile_image"]["name"]);
            $target_file = $upload_dir . $file_name;
            if (move_uploaded_file($_FILES["profile_image"]["tmp_name"], $target_file)) {
                $stmt = mysqli_prepare($conn, "SELECT profile_image FROM users WHERE id = ?");
                if ($stmt && mysqli_stmt_bind_param($stmt, "i", $user_id) && mysqli_stmt_execute($stmt)) {
                    $result = fetchAssocFromStatement($stmt);
                    if ($result && !empty($result['profile_image'])) {
                        $old_file = $upload_dir . $result['profile_image'];
                        if (file_exists($old_file)) {
                            unlink($old_file);
                        }
                    }
                }

                $update_stmt = mysqli_prepare($conn, "UPDATE users SET profile_image = ? WHERE id = ?");
                if ($update_stmt && mysqli_stmt_bind_param($update_stmt, "si", $file_name, $user_id) && mysqli_stmt_execute($update_stmt)) {
                    if (empty($message)) {
                        $message = "Profile image updated successfully.";
                    } else {
                        $message .= " Image updated as well.";
                    }
                } else {
                    $error = "Failed to update profile image.";
                }
            }
        }
    }
}

// Fetch user data
$stmt = mysqli_prepare($conn, "SELECT * FROM users WHERE id = ?");
if ($stmt && mysqli_stmt_bind_param($stmt, "i", $user_id) && mysqli_stmt_execute($stmt)) {
    $user_data = fetchAssocFromStatement($stmt);
} else {
    $user_data = [];
}

$profile_stmt = mysqli_prepare($conn, "SELECT * FROM user_profiles WHERE user_id = ? LIMIT 1");
if ($profile_stmt && mysqli_stmt_bind_param($profile_stmt, "i", $user_id) && mysqli_stmt_execute($profile_stmt)) {
    $profile_data = fetchAssocFromStatement($profile_stmt);
} else {
    $profile_data = [];
}
if (!$profile_data) {
    $profile_data = [];
}

$nav_genres = [];
$nav_genre_q = mysqli_query($conn, "SELECT * FROM genres ORDER BY genre_name ASC");
if ($nav_genre_q) {
    while ($g = mysqli_fetch_assoc($nav_genre_q)) {
        $nav_genres[] = $g;
    }
}

$site_info = [];
$site_info_query = mysqli_query($conn, "SELECT * FROM website_info LIMIT 1");
if ($site_info_query) {
    $site_info = mysqli_fetch_assoc($site_info_query);
}

$current_page = 'profile';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - <?php echo htmlspecialchars($site_info['site_name'] ?? 'SOUND'); ?></title>
    <link rel="stylesheet" href="../assets/css/style.css?v=<?= time() ?>">
    <script src="../assets/js/theme.js?v=<?php echo time(); ?>"></script>
    <style>
        .profile-container {
            max-width: 800px;
            margin: 40px auto;
            background: var(--bg-card);
            border-radius: 12px;
            padding: 40px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            border: 1px solid var(--border-color);
        }
        .profile-header {
            display: flex;
            align-items: center;
            gap: 30px;
            margin-bottom: 40px;
            padding-bottom: 30px;
            border-bottom: 1px solid var(--border-color);
        }
        .profile-img-large {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid var(--accent-cyan);
            background: #282828;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 64px;
            color: #fff;
        }
        .profile-actions {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }
        .form-group {
            margin-bottom: 24px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--text-secondary);
        }
        .form-group input {
            width: 100%;
            padding: 12px 16px;
            background: #282828;
            border: 1px solid #333;
            border-radius: 8px;
            color: var(--text-primary);
            font-size: 15px;
            transition: all 0.3s;
        }
        .form-group input:focus {
            border-color: var(--accent-cyan);
            outline: none;
            box-shadow: 0 0 0 2px rgba(6, 182, 212, 0.2);
        }
        .form-group input[readonly] {
            background: #1a1a1a;
            color: var(--text-muted);
            cursor: not-allowed;
        }
        .btn-update {
            background: var(--accent-cyan);
            color: #000;
            font-weight: 700;
            padding: 14px 28px;
            border-radius: 500px;
            border: none;
            cursor: pointer;
            font-size: 16px;
            transition: 0.2s;
            width: 100%;
        }
        .btn-update:hover {
            transform: scale(1.02);
            background: #0891b2;
            color: #fff;
        }
        
        .alert {
            padding: 16px;
            border-radius: 8px;
            margin-bottom: 24px;
            font-weight: 500;
        }
        .alert-success {
            background: rgba(16, 185, 129, 0.1);
            color: #10b981;
            border: 1px solid rgba(16, 185, 129, 0.2);
        }
        .alert-error {
            background: rgba(239, 68, 68, 0.1);
            color: #ef4444;
            border: 1px solid rgba(239, 68, 68, 0.2);
        }

        #profileImgInput {
            display: none;
        }
        .btn-upload {
            background: #fff;
            color: #000;
            padding: 10px 20px;
            border-radius: 500px;
            font-weight: 600;
            cursor: pointer;
            transition: 0.2s;
            display: inline-block;
        }
        .btn-upload:hover {
            transform: scale(1.02);
        }
        .btn-remove {
            background: transparent;
            color: #ef4444;
            border: 1px solid #ef4444;
            padding: 10px 20px;
            border-radius: 500px;
            font-weight: 600;
            cursor: pointer;
            transition: 0.2s;
        }
        .btn-remove:hover {
            background: rgba(239, 68, 68, 0.1);
        }
        
        @media (max-width: 768px) {
            .profile-header {
                flex-direction: column;
                text-align: center;
            }
            .profile-actions {
                justify-content: center;
            }
            .profile-container {
                padding: 20px;
                margin: 20px;
            }
        }
    </style>
</head>

<body>
    <?php include '../includes/user_sidebar.php'; ?>
    <div class="user-main-content main-wrapper">

        <div class="animated-bg"></div>

        <?php include '../includes/navbar.php'; ?>

        <div class="container" style="max-width: 1200px; margin: 40px auto; padding: 20px; min-height: 60vh;">
            <h2 class="section-header">My <span style="color: var(--accent-cyan);">Profile</span></h2>

            <div class="profile-container">
                <?php if ($message): ?>
                    <div class="alert alert-success"><?php echo $message; ?></div>
                <?php endif; ?>
                <?php if ($error): ?>
                    <div class="alert alert-error"><?php echo $error; ?></div>
                <?php endif; ?>

                <div class="profile-header">
                    <?php if ($user_data['profile_image']): ?>
                        <img src="../uploads/users/<?php echo htmlspecialchars($user_data['profile_image']); ?>" class="profile-img-large" alt="Profile">
                    <?php else: ?>
                        <div class="profile-img-large"><?php echo strtoupper(substr($user_data['name'], 0, 1)); ?></div>
                    <?php endif; ?>
                    
                    <div>
                        <h3 style="margin: 0 0 15px 0; font-size: 24px; color: var(--text-primary);"><?php echo htmlspecialchars($user_data['name']); ?></h3>
                        <div class="profile-actions">
                            <label class="btn-upload" for="profileImgInput">
                                Change Image
                            </label>
                            
                            <?php if ($user_data['profile_image']): ?>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="action" value="delete_image">
                                <button type="submit" class="btn-remove" onclick="return confirm('Remove profile image?');">Remove Image</button>
                            </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="update_profile">
                    
                    <input type="file" id="profileImgInput" name="profile_image" accept="image/*" onchange="document.getElementById('fileName').textContent = this.files[0].name; document.getElementById('fileNameLabel').style.display = 'block';">
                    <div id="fileNameLabel" style="display: none; margin-top: -20px; margin-bottom: 20px; color: var(--accent-cyan); font-size: 14px;">Selected file: <span id="fileName"></span></div>

                    <div class="form-group">
                        <label>User ID</label>
                        <input type="text" value="<?php echo htmlspecialchars($user_data['user_id'] ?? ''); ?>" readonly>
                    </div>

                    <div class="form-group">
                        <label>Name</label>
                        <input type="text" name="name" value="<?php echo htmlspecialchars($user_data['name']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" value="<?php echo htmlspecialchars($user_data['email']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label>Phone</label>
                        <input type="text" name="phone" value="<?php echo htmlspecialchars($user_data['phone'] ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <label>Address</label>
                        <input type="text" name="address" value="<?php echo htmlspecialchars($user_data['address'] ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <label>Date of Birth</label>
                        <input type="date" name="date_of_birth" value="<?php echo htmlspecialchars($profile_data['date_of_birth'] ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <label>Gender</label>
                        <select name="gender" style="width: 100%; padding: 12px 16px; background: #282828; border: 1px solid #333; border-radius: 8px; color: var(--text-primary); font-size: 15px; transition: all 0.3s;">
                            <option value="" <?php echo empty($profile_data['gender'] ?? '') ? 'selected' : ''; ?>>Select</option>
                            <option value="Male" <?php echo (($profile_data['gender'] ?? '') === 'Male') ? 'selected' : ''; ?>>Male</option>
                            <option value="Female" <?php echo (($profile_data['gender'] ?? '') === 'Female') ? 'selected' : ''; ?>>Female</option>
                            <option value="Other" <?php echo (($profile_data['gender'] ?? '') === 'Other') ? 'selected' : ''; ?>>Other</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Bio</label>
                        <textarea name="bio" rows="4" style="width: 100%; padding: 12px 16px; background: #282828; border: 1px solid #333; border-radius: 8px; color: var(--text-primary); font-size: 15px; resize: vertical; transition: all 0.3s;"><?php echo htmlspecialchars($profile_data['bio'] ?? ''); ?></textarea>
                    </div>

                    <div class="form-group">
                        <label>Country</label>
                        <input type="text" name="country" value="<?php echo htmlspecialchars($profile_data['country'] ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <label>City</label>
                        <input type="text" name="city" value="<?php echo htmlspecialchars($profile_data['city'] ?? ''); ?>">
                    </div>

                    <div style="margin-top: 40px;">
                        <button type="submit" class="btn-update">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>

        <?php include '../includes/footer.php'; ?>

    </div> <!-- End user-main-content -->

    <script src="../assets/js/app.js"></script>
</body>
</html>
