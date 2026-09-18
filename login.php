<?php

session_start();

require_once "config/db.php";

$message = "";
$message_type = "";
$registration_success = $_SESSION['registration_success'] ?? "";
unset($_SESSION['registration_success']);

if ($registration_success !== "") {
    $message = $registration_success;
    $message_type = "success";
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $login = trim($_POST["login"] ?? "");
    $password = $_POST["password"] ?? "";

    if ($login === "" || $password === "") {

        $message = "Please enter User ID/Email and Password.";
        $message_type = "error";

    } else {
        // First, check if it's an admin
        $stmt_admin = mysqli_prepare($conn, "SELECT id, name, email, password FROM admins WHERE email = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt_admin, "s", $login);
        mysqli_stmt_execute($stmt_admin);
        $result_admin = mysqli_stmt_get_result($stmt_admin);

        if (mysqli_num_rows($result_admin) === 1) {
            $admin = mysqli_fetch_assoc($result_admin);
            if (password_verify($password, $admin["password"])) {
                session_regenerate_id(true);
                $_SESSION["user_id"]    = $admin["id"];
                $_SESSION["user_login"] = "admin_" . $admin["id"]; // Admins don't have user_id strings
                $_SESSION["name"]       = $admin["name"];
                $_SESSION["email"]      = $admin["email"];
                $_SESSION["role"]       = "admin";

                header("Location: admin/dashboard.php");
                exit;
            } else {
                $message = "Incorrect password.";
                $message_type = "error";
            }
        } else {
            // Not an admin, check users table
            $stmt = mysqli_prepare($conn, "SELECT id, user_id, name, email, password, role FROM users WHERE user_id = ? OR email = ? LIMIT 1");
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, "ss", $login, $login);
                mysqli_stmt_execute($stmt);
                $result = mysqli_stmt_get_result($stmt);

                if (mysqli_num_rows($result) === 1) {
                    $user = mysqli_fetch_assoc($result);
                    if (password_verify($password, $user["password"])) {
                        session_regenerate_id(true);
                        $_SESSION["user_id"]    = $user["id"];
                        $_SESSION["user_login"] = $user["user_id"];
                        $_SESSION["name"]       = $user["name"];
                        $_SESSION["email"]      = $user["email"];
                        $_SESSION["role"]       = $user["role"]; // Should be 'user'

                        // Redirect to intended page if provided, otherwise go to homepage
                        $redirect_to = 'index.php';
                        if (!empty($_GET['redirect'])) {
                            $requested = $_GET['redirect'];
                            // Only allow relative URLs (no protocol, no external hosts)
                            if (!preg_match('/^https?:\/\//i', $requested)) {
                                $redirect_to = $requested;
                            }
                        }
                        header("Location: " . $redirect_to);
                        exit;
                    } else {
                        $message = "Incorrect password.";
                        $message_type = "error";
                    }
                } else {
                    $message = "User ID or Email not found.";
                    $message_type = "error";
                }
                mysqli_stmt_close($stmt);
            } else {
                $message = "Database query error.";
                $message_type = "error";
            }
        }
        mysqli_stmt_close($stmt_admin);
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - SOUND</title>
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
            padding-bottom: 160px;
            box-sizing: border-box;
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

        .field-error {
            display: block;
            min-height: 18px;
            margin-top: 6px;
            color: #FCA5A5;
            font-size: 12px;
            font-weight: 500;
        }

        .auth-input.error {
            border-color: rgba(239,68,68,.9);
            box-shadow: 0 0 0 3px rgba(239,68,68,.12);
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
    <div class="auth-card login-card fade-in">
        <div class="auth-header">
            <h1 class="auth-logo"><img src="assets/images/sound-logo-white.svg" alt="SOUND Logo"></h1>
            <p class="auth-subtitle">Welcome Back</p>
        </div>

        <?php if (!empty($message)): ?>
            <div class="auth-alert <?php echo $message_type; ?>">
                <?php echo nl2br(htmlspecialchars($message)); ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="auth-form" id="loginForm" novalidate>
            <div class="form-group">
                <label>User ID or Email</label>
                <input
                    type="text"
                    name="login"
                    class="auth-input form-control"
                    placeholder="Enter User ID or Email"
                    required
                >
                <span class="field-error" data-error-for="login"></span>
            </div>

            <div class="form-group">
                <label>Password</label>
                <input
                    type="password"
                    name="password"
                    class="auth-input form-control"
                    placeholder="Enter Password"
                    required
                >
                <span class="field-error" data-error-for="password"></span>
            </div>

            <button type="submit" class="auth-button btn btn-primary">
                Login
            </button>
        </form>

        <div class="auth-footer">
            Don't have an account?
            <a href="register.php" class="auth-link">Create Account</a>
        </div>
    </div>
</div>

    <script>
        const loginForm = document.getElementById('loginForm');

        function setFieldError(input, message) {
            const fieldError = document.querySelector('[data-error-for="' + input.name + '"]');
            input.classList.add('error');
            if (fieldError) {
                fieldError.textContent = message;
            }
        }

        function clearFieldError(input) {
            const fieldError = document.querySelector('[data-error-for="' + input.name + '"]');
            input.classList.remove('error');
            if (fieldError) {
                fieldError.textContent = '';
            }
        }

        function validateLoginForm() {
            let isValid = true;
            const loginInput = loginForm.querySelector('[name="login"]');
            const passwordInput = loginForm.querySelector('[name="password"]');

            clearFieldError(loginInput);
            clearFieldError(passwordInput);

            if (!loginInput.value.trim()) {
                setFieldError(loginInput, 'User ID or Email is required.');
                isValid = false;
            } else if (loginInput.value.trim().length < 4) {
                setFieldError(loginInput, 'Please enter a valid User ID or Email.');
                isValid = false;
            } else if (!/^[A-Za-z0-9_@.-]+$/.test(loginInput.value.trim()) && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(loginInput.value.trim())) {
                setFieldError(loginInput, 'Use letters, numbers, underscore, dot, or a valid email format.');
                isValid = false;
            }

            if (!passwordInput.value) {
                setFieldError(passwordInput, 'Password is required.');
                isValid = false;
            } else if (passwordInput.value.length < 6) {
                setFieldError(passwordInput, 'Password must be at least 6 characters.');
                isValid = false;
            }

            return isValid;
        }

        loginForm.addEventListener('submit', function (event) {
            if (!validateLoginForm()) {
                event.preventDefault();
                const firstInvalid = loginForm.querySelector('.error');
                if (firstInvalid) {
                    firstInvalid.focus();
                }
            }
        });

        // Prevent browser back-button cache restore after logout/login flow
        history.pushState(null, null, location.href);

        window.addEventListener('popstate', function () {
            history.pushState(null, null, location.href);
        });

        window.addEventListener('pageshow', function (event) {
            if (event.persisted) {
                window.location.reload();
            }
        });
    </script>

</body>
</html>
