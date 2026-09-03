<?php

session_start();
require_once "config/db.php";

$message = "";
$message_type = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $name     = trim($_POST["name"]);
    $address  = trim($_POST["address"]);
    $phone    = trim($_POST["phone"]);
    $email    = trim($_POST["email"]);
    $password = $_POST["password"];
    $confirm_password = $_POST["confirm_password"] ?? "";

    if (
        empty($name) ||
        empty($address) ||
        empty($phone) ||
        empty($email) ||
        empty($password) ||
        empty($confirm_password)
    ) {
        $message = "All fields are required.";
        $message_type = "error";
    }

    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Please enter a valid email address.";
        $message_type = "error";
    }

    elseif (strlen($password) < 6) {
        $message = "Password must be at least 6 characters.";
        $message_type = "error";
    }
    
    elseif ($password !== $confirm_password) {
        $message = "Passwords do not match.";
        $message_type = "error";
    }

    else {
        $check_email = mysqli_prepare($conn, "SELECT id FROM users WHERE email = ?");
        mysqli_stmt_bind_param($check_email, "s", $email);
        mysqli_stmt_execute($check_email);
        mysqli_stmt_store_result($check_email);

        if (mysqli_stmt_num_rows($check_email) > 0) {
            $message = "This Email is already registered!";
            $message_type = "error";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $profile_image = NULL;

            // Generate unique user_id using uniqid
            $user_id = "USER_" . uniqid();

            // Insert user with generated user_id
            $stmt = mysqli_prepare(
                $conn,
                "INSERT INTO users
                (user_id, name, address, phone, email, password, role, profile_image)
                VALUES (?, ?, ?, ?, ?, ?, 'user', ?)"
            );

            mysqli_stmt_bind_param(
                $stmt, "sssssss",
                $user_id, $name, $address, $phone, $email, $hashed_password, $profile_image
            );

            if (mysqli_stmt_execute($stmt)) {
                $_SESSION['registration_success'] = "Account created successfully!\n\nYour User ID is: " . $user_id . "\n\nPlease save this User ID and use your credentials to log in.";
                header("Location: login.php");
                exit;
            } else {
                $message = "Registration failed. Please try again.";
                $message_type = "error";
            }
            
            mysqli_stmt_close($stmt);
        }

        mysqli_stmt_close($check_email);
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - SOUND</title>
    <link rel="stylesheet" href="assets/css/style.css?v=<?= time() ?>">
    <link rel="stylesheet" href="assets/css/admin.css">
    <script src="assets/js/theme.js?v=<?php echo time(); ?>"></script>
    <style>
        :root {
            --auth-bg: #0B0B0F;
            --auth-card-bg: #15151C;
            --auth-border: #27272A;
            --auth-primary: #8B5CF6;
            --auth-primary-hover: #6D28D9;
            --auth-text-dark: #F8FAFC;
            --auth-text-muted: #A1A1AA;
            --auth-input-border: #27272A;
            --auth-input-focus-shadow: rgba(139, 92, 246, .18);
        }
        
        body {
            margin: 0;
            font-family: 'Inter', sans-serif;
            background: var(--auth-bg) !important;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            color: var(--auth-text-dark);
            padding: 40px 0;
        }

        .auth-container {
            width: 100%;
            padding: 20px;
            box-sizing: border-box;
            display: flex;
            justify-content: center;
        }

        .auth-card {
            background: var(--auth-card-bg);
            border: 1px solid var(--auth-border);
            border-radius: 20px;
            box-shadow: 0 20px 50px rgba(15, 23, 42, 0.08);
            width: 100%;
            padding: 32px 36px;
            box-sizing: border-box;
        }

        .login-card { max-width: 440px; }
        .register-card { max-width: 500px; }

        .auth-header {
            text-align: center;
            margin-bottom: 24px;
        }

        .auth-logo {
            font-size: 36px;
            font-weight: 700;
            color: var(--auth-primary);
            margin: 0 0 8px 0;
            letter-spacing: -0.5px;
        }

        .auth-subtitle {
            color: var(--auth-text-muted);
            font-weight: 500;
            margin: 0;
            font-size: 15px;
        }

        .auth-form .form-group {
            margin-bottom: 16px;
            text-align: left;
        }

        .auth-form label {
            display: block;
            color: var(--auth-text-dark);
            font-weight: 500;
            margin-bottom: 6px;
            font-size: 14px;
        }

        .auth-input {
            width: 100%;
            background: var(--auth-card-bg);
            border: 1px solid var(--auth-input-border);
            color: var(--auth-text-dark);
            border-radius: 10px;
            padding: 12px 14px;
            font-size: 15px;
            box-sizing: border-box;
            transition: all 0.2s ease;
            font-family: inherit;
        }

        .auth-input::placeholder {
            color: var(--auth-text-muted);
        }

        .auth-input:focus {
            border-color: var(--auth-primary);
            box-shadow: 0 0 0 3px var(--auth-input-focus-shadow);
            outline: none;
        }

        textarea.auth-input {
            resize: vertical;
            min-height: 80px;
        }

        .auth-button {
            width: 100%;
            background: var(--auth-primary);
            color: #07140b;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            padding: 12px;
            font-size: 16px;
            cursor: pointer;
            transition: background 0.2s ease;
            margin-top: 8px;
        }

        .auth-button:hover {
            background: var(--auth-primary-hover);
        }

        .auth-footer {
            text-align: center;
            margin-top: 20px;
            font-size: 14px;
            color: var(--auth-text-muted);
            font-weight: 500;
        }

        .auth-link {
            color: var(--auth-primary);
            text-decoration: none;
            font-weight: 600;
            transition: color 0.2s ease;
        }

        .auth-link:hover {
            color: var(--auth-primary-hover);
        }

        .auth-alert {
            padding: 12px 16px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 14px;
            font-weight: 500;
            text-align: center;
        }

        .auth-alert.error {
            background: rgba(239,68,68,.14);
            color: #FCA5A5;
            border: 1px solid rgba(239,68,68,.45);
        }

        .auth-alert.success {
            background: rgba(34,197,94,.14);
            color: #86EFAC;
            border: 1px solid rgba(34,197,94,.45);
        }

        @media (max-width: 480px) {
            .auth-card {
                padding: 28px 20px;
            }
        }
    </style>
</head>

<body>

<div class="auth-container">
    <div class="auth-card register-card fade-in">
        <div class="auth-header">
            <h1 class="auth-logo"><img src="assets/images/sound-logo-white.svg" alt="SOUND Logo"></h1>
            <p class="auth-subtitle">Create Account</p>
        </div>

        <?php if (!empty($message)): ?>
            <div class="auth-alert <?php echo $message_type; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="" class="auth-form">
            <div class="form-group">
                <label>Full Name</label>
                <input
                    type="text"
                    name="name"
                    class="auth-input form-control"
                    placeholder="Enter your full name"
                    required
                >
            </div>

            <div class="form-group">
                <label>Address</label>
                <textarea
                    name="address"
                    class="auth-input form-control"
                    placeholder="Enter your address"
                    required
                ></textarea>
            </div>

            <div class="form-group">
                <label>Phone</label>
                <input
                    type="text"
                    name="phone"
                    class="auth-input form-control"
                    placeholder="Enter your phone number"
                    required
                >
            </div>

            <div class="form-group">
                <label>Email</label>
                <input
                    type="email"
                    name="email"
                    class="auth-input form-control"
                    placeholder="Enter your email"
                    required
                >
            </div>

            <div class="form-group">
                <label>Password</label>
                <input
                    type="password"
                    name="password"
                    class="auth-input form-control"
                    placeholder="Minimum 6 characters"
                    required
                >
            </div>
            
            <div class="form-group">
                <label>Confirm Password</label>
                <input
                    type="password"
                    name="confirm_password"
                    class="auth-input form-control"
                    placeholder="Confirm your password"
                    required
                >
            </div>

            <button type="submit" class="auth-button btn btn-primary">
                Create Account
            </button>
        </form>

        <div class="auth-footer">
            Already have an account?
            <a href="login.php" class="auth-link">Login</a>
        </div>
    </div>
</div>

</body>
</html>
