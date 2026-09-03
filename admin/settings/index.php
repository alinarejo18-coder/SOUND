<?php
require_once "../../includes/auth.php";
require_once "../../config/db.php";

requireAdmin();

$message = "";
$message_type = "";
$site_name = "";
$description = "";
$email = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $site_name = trim($_POST['site_name'] ?? "");
    $description = trim($_POST['description'] ?? "");
    $email = trim($_POST['email'] ?? "");

    if ($site_name === "") {
        $message = "Website name is required.";
        $message_type = "error";
    } elseif ($email !== "" && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Please enter a valid contact email.";
        $message_type = "error";
    } else {
        $check = mysqli_query($conn, "SELECT id FROM website_info ORDER BY id ASC LIMIT 1");
        $existing = $check ? mysqli_fetch_assoc($check) : null;

        if ($existing) {
            $stmt = mysqli_prepare($conn, "UPDATE website_info SET site_name = ?, description = ?, email = ? WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "sssi", $site_name, $description, $email, $existing['id']);
        } else {
            $stmt = mysqli_prepare($conn, "INSERT INTO website_info (site_name, description, email) VALUES (?, ?, ?)");
            mysqli_stmt_bind_param($stmt, "sss", $site_name, $description, $email);
        }

        if (mysqli_stmt_execute($stmt)) {
            $message = "Settings saved successfully.";
            $message_type = "success";
        } else {
            $message = "Could not save settings.";
            $message_type = "error";
        }

        mysqli_stmt_close($stmt);
    }
} else {
    $result = mysqli_query($conn, "SELECT site_name, description, email FROM website_info ORDER BY id ASC LIMIT 1");
    if ($settings = ($result ? mysqli_fetch_assoc($result) : null)) {
        $site_name = $settings['site_name'];
        $description = $settings['description'] ?? "";
        $email = $settings['email'] ?? "";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - SOUND Admin</title>
    <link rel="stylesheet" href="../../assets/css/style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../../assets/css/admin.css?v=<?php echo time(); ?>">
</head>
<body>

<div class="admin-layout">
    <?php
    $current_page = 'settings';
    $admin_base = '../';
    include "../../includes/admin_header.php";
    ?>

    <main class="admin-main">
        <div class="admin-topbar">
            <div>
                <h1>Settings</h1>
                <div class="breadcrumb">Manage basic website settings.</div>
            </div>
        </div>

        <div class="admin-content">
            <div class="form-panel">
                <?php if ($message): ?>
                    <div style="color: <?php echo $message_type === 'success' ? '#15803d' : '#b91c1c'; ?>; margin-bottom: 15px;">
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php endif; ?>

                <form method="POST">
                    <div class="form-group">
                        <label for="site-name">Website Name</label>
                        <input id="site-name" type="text" name="site_name" value="<?php echo htmlspecialchars($site_name); ?>" maxlength="150" required class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="site-description">Site Description / Tagline</label>
                        <textarea id="site-description" name="description" rows="4" class="form-control"><?php echo htmlspecialchars($description); ?></textarea>
                    </div>
                    <div class="form-group">
                        <label for="contact-email">Contact Email</label>
                        <input id="contact-email" type="email" name="email" value="<?php echo htmlspecialchars($email); ?>" maxlength="150" class="form-control">
                    </div>
                    <button type="submit" class="btn btn-primary btn-block">Save Settings</button>
                </form>
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
