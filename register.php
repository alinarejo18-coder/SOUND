<?php

session_start();
require_once "config/db.php";

$message = "";
$message_type = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $name     = trim($_POST["name"]);
    $address  = trim($_POST["address"]);
    $country_code = trim($_POST["country_code"] ?? "");
    $phone    = trim($_POST["phone"]);
    $phone    = ($country_code !== "" && $phone !== "") ? $country_code . " " . $phone : $phone;
    $email    = trim($_POST["email"]);
    $password = $_POST["password"];
    $confirm_password = $_POST["confirm_password"] ?? "";

    if (
        empty($name) ||
        empty($address) ||
        empty($country_code) ||
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

            // Generate a simple numeric user_id in the range 1000 to 100000000
            $user_id = null;
            for ($attempt = 0; $attempt < 50; $attempt++) {
                $candidate = (string) mt_rand(1000, 100000000);
                $user_id_check = mysqli_prepare($conn, "SELECT id FROM users WHERE user_id = ?");
                mysqli_stmt_bind_param($user_id_check, "s", $candidate);
                mysqli_stmt_execute($user_id_check);
                mysqli_stmt_store_result($user_id_check);

                if (mysqli_stmt_num_rows($user_id_check) === 0) {
                    $user_id = $candidate;
                    mysqli_stmt_close($user_id_check);
                    break;
                }

                mysqli_stmt_close($user_id_check);
            }

            if ($user_id === null) {
                $user_id = (string) time();
            }

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

        .country-code-select.error {
            border-color: rgba(239,68,68,.9);
            box-shadow: 0 0 0 3px rgba(239,68,68,.12);
        }

        .phone-input-wrap {
            display: flex;
            gap: 10px;
            align-items: stretch;
        }

        .phone-input-wrap .country-code-select {
            width: 170px;
            min-width: 170px;
            background: var(--auth-card-bg);
            border: 1px solid var(--auth-input-border);
            color: var(--auth-text-dark);
            border-radius: 10px;
            padding: 10px 12px;
            font-size: 15px;
            box-sizing: border-box;
            font-family: inherit;
            appearance: none;
            -webkit-appearance: none;
            -moz-appearance: none;
            background-image: linear-gradient(45deg, transparent 50%, #A1A1AA 50%), linear-gradient(135deg, #A1A1AA 50%, transparent 50%);
            background-position: calc(100% - 18px) calc(50% - 2px), calc(100% - 12px) calc(50% - 2px);
            background-size: 6px 6px, 6px 6px;
            background-repeat: no-repeat;
            padding-right: 28px;
        }

        .phone-input-wrap .country-code-select option {
            background: #15151C;
            color: #F8FAFC;
        }

        .phone-input-wrap .phone-input {
            flex: 1;
            min-width: 0;
        }

        @media (max-width: 480px) {
            .auth-card {
                padding: 28px 20px;
            }

            .phone-input-wrap {
                flex-direction: column;
            }

            .phone-input-wrap .country-code-select {
                width: 100%;
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

        <form method="POST" action="" class="auth-form" id="registerForm" novalidate>
            <div class="form-group">
                <label>Full Name</label>
                <input
                    type="text"
                    name="name"
                    class="auth-input form-control"
                    placeholder="Enter your full name"
                    required
                >
                <span class="field-error" data-error-for="name"></span>
            </div>

            <div class="form-group">
                <label>Address</label>
                <textarea
                    name="address"
                    class="auth-input form-control"
                    placeholder="Enter your address"
                    required
                ></textarea>
                <span class="field-error" data-error-for="address"></span>
            </div>

            <div class="form-group">
                <label>Phone</label>
                <div class="phone-input-wrap">
                    <select name="country_code" class="country-code-select form-control" aria-label="Country code" required>
                        <option value="">Code</option>
                        <option value="+1">🇺🇸 +1</option>
                        <option value="+7">🇷🇺 +7</option>
                        <option value="+20">🇪🇬 +20</option>
                        <option value="+27">🇿🇦 +27</option>
                        <option value="+30">🇬🇷 +30</option>
                        <option value="+31">🇳🇱 +31</option>
                        <option value="+32">🇧🇪 +32</option>
                        <option value="+33">🇫🇷 +33</option>
                        <option value="+34">🇪🇸 +34</option>
                        <option value="+39">🇮🇹 +39</option>
                        <option value="+41">🇨🇭 +41</option>
                        <option value="+44">🇬🇧 +44</option>
                        <option value="+45">🇩🇰 +45</option>
                        <option value="+46">🇸🇪 +46</option>
                        <option value="+47">🇳🇴 +47</option>
                        <option value="+49">🇩🇪 +49</option>
                        <option value="+61">🇦🇺 +61</option>
                        <option value="+65">🇸🇬 +65</option>
                        <option value="+81">🇯🇵 +81</option>
                        <option value="+82">🇰🇷 +82</option>
                        <option value="+86">🇨🇳 +86</option>
                        <option value="+91">🇮🇳 +91</option>
                        <option value="+92">🇵🇰 +92</option>
                        <option value="+234">🇳🇬 +234</option>
                        <option value="+254">🇰🇪 +254</option>
                        <option value="+353">🇮🇪 +353</option>
                        <option value="+358">🇫🇮 +358</option>
                        <option value="+880">🇧🇩 +880</option>
                        <option value="+960">🇲🇻 +960</option>
                        <option value="+962">🇯🇴 +962</option>
                        <option value="+966">🇸🇦 +966</option>
                        <option value="+971">🇦🇪 +971</option>
                    </select>
                    <input
                        type="text"
                        name="phone"
                        class="auth-input form-control phone-input"
                        placeholder="Enter your phone number"
                        required
                    >
                </div>
                <span class="field-error" data-error-for="country_code"></span>
                <span class="field-error" data-error-for="phone"></span>
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
                <span class="field-error" data-error-for="email"></span>
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
                <span class="field-error" data-error-for="password"></span>
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
                <span class="field-error" data-error-for="confirm_password"></span>
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

<script>
    const registerForm = document.getElementById('registerForm');
    const countryCodeMap = [
        { code: '+1', prefixes: ['1'] },
        { code: '+7', prefixes: ['7'] },
        { code: '+20', prefixes: ['20'] },
        { code: '+27', prefixes: ['27'] },
        { code: '+30', prefixes: ['30'] },
        { code: '+31', prefixes: ['31'] },
        { code: '+32', prefixes: ['32'] },
        { code: '+33', prefixes: ['33'] },
        { code: '+34', prefixes: ['34'] },
        { code: '+39', prefixes: ['39'] },
        { code: '+41', prefixes: ['41'] },
        { code: '+44', prefixes: ['44'] },
        { code: '+45', prefixes: ['45'] },
        { code: '+46', prefixes: ['46'] },
        { code: '+47', prefixes: ['47'] },
        { code: '+49', prefixes: ['49'] },
        { code: '+61', prefixes: ['61'] },
        { code: '+65', prefixes: ['65'] },
        { code: '+81', prefixes: ['81'] },
        { code: '+82', prefixes: ['82'] },
        { code: '+86', prefixes: ['86'] },
        { code: '+91', prefixes: ['91'] },
        { code: '+92', prefixes: ['92'] },
        { code: '+234', prefixes: ['234'] },
        { code: '+254', prefixes: ['254'] },
        { code: '+353', prefixes: ['353'] },
        { code: '+358', prefixes: ['358'] },
        { code: '+880', prefixes: ['880'] },
        { code: '+960', prefixes: ['960'] },
        { code: '+962', prefixes: ['962'] },
        { code: '+966', prefixes: ['966'] },
        { code: '+971', prefixes: ['971'] }
    ];

    function detectCountryCode(phoneNumber) {
        const digits = phoneNumber.replace(/\D/g, '');
        if (!digits) return null;

        let matchedCode = null;

        countryCodeMap.forEach(function (item) {
            item.prefixes.forEach(function (prefix) {
                if (digits.startsWith(prefix) && (!matchedCode || prefix.length > matchedCode.length)) {
                    matchedCode = prefix;
                }
            });
        });

        if (!matchedCode) return null;

        const selected = countryCodeMap.find(function (item) {
            return item.prefixes.includes(matchedCode);
        });

        return selected ? selected.code : null;
    }

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

    function validateField(input) {
        const name = input.name;
        const value = input.value.trim();

        clearFieldError(input);

        if (name === 'name') {
            if (!value) {
                setFieldError(input, 'Full name is required.');
                return false;
            }
            if (!/^[A-Za-z ]+$/.test(value)) {
                setFieldError(input, 'Name can only contain letters and spaces.');
                return false;
            }
            if (value.length < 2) {
                setFieldError(input, 'Name must be at least 2 characters.');
                return false;
            }
        }

        if (name === 'address') {
            if (!value) {
                setFieldError(input, 'Address is required.');
                return false;
            }
            if (value.length < 5) {
                setFieldError(input, 'Address must be at least 5 characters.');
                return false;
            }
        }

        if (name === 'country_code') {
            if (!input.value) {
                setFieldError(input, 'Please select a country code.');
                return false;
            }
        }

        if (name === 'phone') {
            if (!value) {
                setFieldError(input, 'Phone number is required.');
                return false;
            }

            const selectedCode = registerForm.querySelector('[name="country_code"]').value;
            const countryRules = {
                '+1': { min: 10, max: 10 },
                '+7': { min: 11, max: 11 },
                '+20': { min: 10, max: 10 },
                '+27': { min: 9, max: 9 },
                '+30': { min: 10, max: 10 },
                '+31': { min: 9, max: 9 },
                '+32': { min: 9, max: 9 },
                '+33': { min: 9, max: 9 },
                '+34': { min: 9, max: 9 },
                '+39': { min: 9, max: 10 },
                '+41': { min: 9, max: 9 },
                '+44': { min: 10, max: 10 },
                '+45': { min: 8, max: 8 },
                '+46': { min: 9, max: 9 },
                '+47': { min: 8, max: 8 },
                '+49': { min: 10, max: 11 },
                '+61': { min: 9, max: 9 },
                '+65': { min: 8, max: 8 },
                '+81': { min: 10, max: 10 },
                '+82': { min: 9, max: 10 },
                '+86': { min: 11, max: 11 },
                '+91': { min: 10, max: 10 },
                '+92': { min: 10, max: 10 },
                '+234': { min: 10, max: 10 },
                '+254': { min: 9, max: 10 },
                '+353': { min: 9, max: 9 },
                '+358': { min: 9, max: 9 },
                '+880': { min: 10, max: 10 },
                '+960': { min: 7, max: 7 },
                '+962': { min: 9, max: 9 },
                '+966': { min: 9, max: 9 },
                '+971': { min: 9, max: 9 }
            };

            const rule = countryRules[selectedCode] || { min: 7, max: 15 };

            if (!/^[0-9]+$/.test(value)) {
                setFieldError(input, 'Phone number must contain digits only.');
                return false;
            }

            if (value.length > rule.max) {
                const fieldError = document.querySelector('[data-error-for="phone"]');
                setFieldError(input, 'Too many digits for the selected country code.');
                if (fieldError) {
                    fieldError.style.color = '#FCA5A5';
                }
                return false;
            }

            if (value.length < rule.min) {
                setFieldError(input, 'Phone number is too short for the selected country code.');
                return false;
            }
        }

        if (name === 'email') {
            if (!value) {
                setFieldError(input, 'Email is required.');
                return false;
            }
            if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)) {
                setFieldError(input, 'Please enter a valid email address.');
                return false;
            }
        }

        if (name === 'password') {
            if (!input.value) {
                setFieldError(input, 'Password is required.');
                return false;
            }
            if (input.value.length < 6) {
                setFieldError(input, 'Password must be at least 6 characters.');
                return false;
            }
        }

        if (name === 'confirm_password') {
            const passwordField = registerForm.querySelector('[name="password"]');
            if (!input.value) {
                setFieldError(input, 'Please confirm your password.');
                return false;
            }
            if (passwordField.value !== input.value) {
                setFieldError(input, 'Passwords do not match.');
                return false;
            }
        }

        return true;
    }

    function validateRegisterForm() {
        let isValid = true;

        const fields = {
            name: registerForm.querySelector('[name="name"]'),
            address: registerForm.querySelector('[name="address"]'),
            country_code: registerForm.querySelector('[name="country_code"]'),
            phone: registerForm.querySelector('[name="phone"]'),
            email: registerForm.querySelector('[name="email"]'),
            password: registerForm.querySelector('[name="password"]'),
            confirm_password: registerForm.querySelector('[name="confirm_password"]')
        };

        Object.values(fields).forEach(input => {
            if (input) {
                const result = validateField(input);
                if (!result) isValid = false;
            }
        });

        return isValid;
    }

    ['name', 'address', 'country_code', 'phone', 'email', 'password', 'confirm_password'].forEach(fieldName => {
        const input = registerForm.querySelector('[name="' + fieldName + '"]');
        if (!input) return;

        input.addEventListener('blur', function () {
            validateField(this);
        });

        input.addEventListener('focus', function () {
            if (fieldName === 'phone') {
                const countrySelect = registerForm.querySelector('[name="country_code"]');
                if (!countrySelect.value) {
                    setFieldError(countrySelect, 'Please select a country code first.');
                }
            }
        });

        input.addEventListener('input', function () {
            if (fieldName === 'phone') {
                const phoneInput = this;
                const detectedCode = detectCountryCode(phoneInput.value);
                const countrySelect = registerForm.querySelector('[name="country_code"]');

                if (countrySelect.value === '') {
                    if (detectedCode) {
                        countrySelect.value = detectedCode;
                        clearFieldError(countrySelect);
                    } else {
                        setFieldError(countrySelect, 'Please select a country code first.');
                    }
                } else if (detectedCode && countrySelect.value !== detectedCode) {
                    countrySelect.value = detectedCode;
                    clearFieldError(countrySelect);
                }
            }

            if (this.classList.contains('error')) {
                validateField(this);
            }
        });
    });

    registerForm.addEventListener('submit', function (event) {
        if (!validateRegisterForm()) {
            event.preventDefault();
            const firstInvalid = registerForm.querySelector('.error');
            if (firstInvalid) {
                firstInvalid.focus();
            }
        }
    });
</script>

</body>
</html>
