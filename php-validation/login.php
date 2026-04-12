<?php
// ============================================================
// login.php — Student Login Page
// Uses Roll No as username + password_verify() for auth
// ============================================================

session_start();

// If already logged in, redirect to dashboard
if (isset($_SESSION['student_id'])) {
    header('Location: index.php');
    exit;
}

require_once 'db.php';

// ===== Variables =====
$errors       = [];
$success      = '';
$sticky_roll  = '';

// ===== POST Handler =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'login') {

    // Collect and sanitize inputs
    $roll_no  = htmlspecialchars(trim(strip_tags($_POST['roll_no']  ?? '')));
    $password = $_POST['password'] ?? '';

    $sticky_roll = $roll_no;

    // --- Validation ---
    if ($roll_no === '') {
        $errors['roll_no'] = 'Roll No / Student ID is required.';
    }

    if ($password === '') {
        $errors['password'] = 'Password is required.';
    }

    // --- Authenticate against DB ---
    if (empty($errors)) {
        // Look up student by Roll No using a prepared statement
        $stmt = $pdo->prepare('SELECT id, first_name, last_name, roll_no, password FROM students WHERE roll_no = ?');
        $stmt->execute([$roll_no]);
        $student = $stmt->fetch();

        if (!$student) {
            // Don't reveal whether roll_no or password is wrong (security best practice)
            $errors['general'] = 'Invalid Roll No or Password. Please try again.';
        } elseif (!password_verify($password, $student['password'])) {
            // Password mismatch
            $errors['general'] = 'Invalid Roll No or Password. Please try again.';
        } else {
            // ✅ Login successful — store student info in session
            $_SESSION['student_id']    = $student['id'];
            $_SESSION['student_name']  = $student['first_name'] . ' ' . $student['last_name'];
            $_SESSION['student_roll']  = $student['roll_no'];

            // Redirect to main dashboard
            header('Location: index.php');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="description" content="Student Login — PHP Student Registration System" />
    <title>Student Login | StudentDB</title>

    <!-- Bootstrap 5 CDN -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" />
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" />
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet" />

    <style>
        /* ===== Root & Base ===== */
        :root {
            --primary:      #4f46e5;
            --primary-dark: #3730a3;
            --secondary:    #7c3aed;
            --success:      #059669;
            --danger:       #dc2626;
            --bg:           #0f0e17;
            --surface:      #1a1928;
            --border:       rgba(255,255,255,0.08);
            --text:         #e2e0ff;
            --text-muted:   #8b8aa8;
            --radius:       14px;
        }

        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg);
            color: var(--text);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
            position: relative;
            overflow: hidden;
        }

        /* ===== Animated Background Blobs ===== */
        .blob {
            position: fixed;
            border-radius: 50%;
            filter: blur(80px);
            opacity: 0.4;
            animation: blobFloat 8s ease-in-out infinite alternate;
            pointer-events: none;
            z-index: 0;
        }
        .blob-1 {
            width: 500px; height: 500px;
            background: radial-gradient(circle, #4f46e5, transparent);
            top: -150px; left: -150px;
            animation-delay: 0s;
        }
        .blob-2 {
            width: 400px; height: 400px;
            background: radial-gradient(circle, #7c3aed, transparent);
            bottom: -120px; right: -120px;
            animation-delay: 3s;
        }
        .blob-3 {
            width: 300px; height: 300px;
            background: radial-gradient(circle, #0ea5e9, transparent);
            top: 50%; left: 60%;
            animation-delay: 1.5s;
            opacity: 0.15;
        }
        @keyframes blobFloat {
            0%   { transform: scale(1) translate(0, 0); }
            100% { transform: scale(1.1) translate(20px, -20px); }
        }

        /* ===== Grid pattern overlay ===== */
        .grid-overlay {
            position: fixed; inset: 0; z-index: 0;
            background-image:
                linear-gradient(rgba(255,255,255,0.015) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255,255,255,0.015) 1px, transparent 1px);
            background-size: 40px 40px;
            pointer-events: none;
        }

        /* ===== Brand Logo ===== */
        .brand-logo {
            display: flex; align-items: center; gap: 0.6rem;
            margin-bottom: 1.75rem;
            text-decoration: none;
            position: relative; z-index: 1;
        }
        .brand-logo .logo-icon {
            width: 46px; height: 46px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.4rem;
            box-shadow: 0 4px 20px rgba(79,70,229,0.5);
        }
        .brand-logo .logo-text {
            font-size: 1.5rem; font-weight: 700;
            background: linear-gradient(135deg, #818cf8, #a78bfa);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        /* ===== Login Card ===== */
        .login-card {
            background: rgba(255,255,255,0.04);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: var(--radius);
            padding: 2.25rem 2.5rem;
            width: 100%;
            max-width: 420px;
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            box-shadow: 0 8px 40px rgba(0,0,0,0.5), 0 0 0 1px rgba(255,255,255,0.05);
            position: relative; z-index: 1;
            animation: cardIn 0.4s ease forwards;
        }
        @keyframes cardIn {
            from { opacity: 0; transform: translateY(24px) scale(0.97); }
            to   { opacity: 1; transform: translateY(0) scale(1); }
        }

        .login-card h1 {
            font-size: 1.45rem;
            font-weight: 700;
            color: var(--text);
            margin-bottom: 0.3rem;
        }
        .login-card .subtitle {
            font-size: 0.85rem;
            color: var(--text-muted);
            margin-bottom: 1.75rem;
        }

        /* ===== Form Controls ===== */
        .form-group { margin-bottom: 1.1rem; }

        .form-label-custom {
            display: block;
            font-size: 0.82rem;
            font-weight: 600;
            color: #a5b4fc;
            margin-bottom: 0.4rem;
            letter-spacing: 0.01em;
        }

        .input-wrapper {
            position: relative;
        }
        .input-icon {
            position: absolute;
            left: 0.85rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
            font-size: 1rem;
            pointer-events: none;
            transition: color 0.2s;
        }
        .form-control-custom {
            width: 100%;
            background: rgba(255,255,255,0.055);
            border: 1px solid rgba(255,255,255,0.1);
            color: var(--text);
            border-radius: 9px;
            padding: 0.65rem 0.9rem 0.65rem 2.5rem;
            font-size: 0.9rem;
            font-family: 'Inter', sans-serif;
            transition: border-color 0.2s, box-shadow 0.2s, background 0.2s;
            outline: none;
        }
        .form-control-custom::placeholder {
            color: rgba(255,255,255,0.2);
        }
        .form-control-custom:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(79,70,229,0.25);
            background: rgba(255,255,255,0.07);
        }
        .form-control-custom:focus + .input-icon,
        .input-wrapper:focus-within .input-icon {
            color: #818cf8;
        }
        .form-control-custom.is-invalid {
            border-color: #ef4444;
        }
        .field-error {
            color: #f87171;
            font-size: 0.78rem;
            margin-top: 0.35rem;
            display: flex; align-items: center; gap: 0.3rem;
        }

        /* Password toggle button */
        .pw-toggle {
            position: absolute;
            right: 0.85rem;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--text-muted);
            cursor: pointer;
            padding: 0;
            font-size: 1rem;
            transition: color 0.2s;
        }
        .pw-toggle:hover { color: #a5b4fc; }

        /* ===== General Error Alert ===== */
        .alert-general {
            background: rgba(239,68,68,0.1);
            border: 1px solid rgba(239,68,68,0.3);
            border-radius: 9px;
            padding: 0.85rem 1rem;
            margin-bottom: 1.25rem;
            display: flex; align-items: center; gap: 0.65rem;
            font-size: 0.875rem;
            color: #f87171;
            animation: shake 0.35s ease;
        }
        @keyframes shake {
            0%,100% { transform: translateX(0); }
            20%      { transform: translateX(-5px); }
            40%      { transform: translateX(5px); }
            60%      { transform: translateX(-3px); }
            80%      { transform: translateX(3px); }
        }

        /* ===== Submit Button ===== */
        .btn-login {
            width: 100%;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            border: none;
            color: #fff;
            font-weight: 600;
            font-size: 0.95rem;
            padding: 0.75rem;
            border-radius: 9px;
            cursor: pointer;
            transition: transform 0.15s, box-shadow 0.15s, opacity 0.15s;
            box-shadow: 0 3px 14px rgba(79,70,229,0.45);
            display: flex; align-items: center; justify-content: center; gap: 0.5rem;
            font-family: 'Inter', sans-serif;
            margin-top: 0.5rem;
        }
        .btn-login:hover {
            transform: translateY(-1px);
            box-shadow: 0 5px 22px rgba(79,70,229,0.6);
        }
        .btn-login:active { transform: translateY(0); }

        /* ===== Divider ===== */
        .or-divider {
            display: flex; align-items: center; gap: 0.75rem;
            margin: 1.25rem 0;
            color: var(--text-muted);
            font-size: 0.78rem;
        }
        .or-divider::before,
        .or-divider::after {
            content: ''; flex: 1;
            border-top: 1px solid var(--border);
        }

        /* ===== Register Link ===== */
        .register-link {
            text-align: center;
            font-size: 0.85rem;
            color: var(--text-muted);
        }
        .register-link a {
            color: #818cf8;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.2s;
        }
        .register-link a:hover { color: #a5b4fc; text-decoration: underline; }

        /* ===== Footer ===== */
        .page-footer {
            position: relative; z-index: 1;
            margin-top: 1.5rem;
            font-size: 0.75rem;
            color: var(--text-muted);
            text-align: center;
        }

        /* ===== Registered-Successfully banner (after register redirect) ===== */
        .alert-success-custom {
            background: rgba(5,150,105,0.13);
            border: 1px solid rgba(5,150,105,0.35);
            color: #6ee7b7;
            border-radius: 9px;
            padding: 0.85rem 1rem;
            margin-bottom: 1.25rem;
            display: flex; align-items: center; gap: 0.65rem;
            font-size: 0.875rem;
        }
    </style>
</head>
<body>

<!-- Background decorations -->
<div class="blob blob-1"></div>
<div class="blob blob-2"></div>
<div class="blob blob-3"></div>
<div class="grid-overlay"></div>

<!-- Brand Logo -->
<a class="brand-logo" href="login.php" aria-label="StudentDB Home">
    <div class="logo-icon">&#127891;</div>
    <span class="logo-text">StudentDB</span>
</a>

<!-- ===== LOGIN CARD ===== -->
<div class="login-card">
    <h1>Welcome back</h1>
    <p class="subtitle">Sign in with your Roll No and password to access the dashboard.</p>

    <!-- Show registered-successfully flash message if coming from registration -->
    <?php if (isset($_GET['registered']) && $_GET['registered'] === '1'): ?>
    <div class="alert-success-custom">
        <i class="bi bi-check-circle-fill fs-5 flex-shrink-0"></i>
        <div>Registration successful! You can now log in.</div>
    </div>
    <?php endif; ?>

    <!-- General Auth Error -->
    <?php if (isset($errors['general'])): ?>
    <div class="alert-general" role="alert">
        <i class="bi bi-shield-exclamation fs-5 flex-shrink-0"></i>
        <div><?= htmlspecialchars($errors['general']) ?></div>
    </div>
    <?php endif; ?>

    <!-- ===== LOGIN FORM ===== -->
    <form method="POST" action="login.php" id="login-form" novalidate>
        <input type="hidden" name="action" value="login" />

        <!-- Roll No -->
        <div class="form-group">
            <label class="form-label-custom" for="roll_no">
                Roll No / Student ID
            </label>
            <div class="input-wrapper">
                <input
                    type="text"
                    id="roll_no"
                    name="roll_no"
                    class="form-control-custom <?= isset($errors['roll_no']) ? 'is-invalid' : '' ?>"
                    placeholder="e.g. CS2024001"
                    value="<?= htmlspecialchars($sticky_roll) ?>"
                    autocomplete="username"
                    autofocus
                />
                <i class="bi bi-hash input-icon"></i>
            </div>
            <?php if (isset($errors['roll_no'])): ?>
            <div class="field-error">
                <i class="bi bi-exclamation-circle"></i> <?= htmlspecialchars($errors['roll_no']) ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- Password -->
        <div class="form-group">
            <div class="d-flex justify-content-between align-items-center mb-1" style="margin-bottom:0.4rem!important;">
                <label class="form-label-custom mb-0" for="password">Password</label>
            </div>
            <div class="input-wrapper">
                <input
                    type="password"
                    id="password"
                    name="password"
                    class="form-control-custom <?= isset($errors['password']) ? 'is-invalid' : '' ?>"
                    placeholder="Enter your password"
                    autocomplete="current-password"
                    style="padding-right: 2.5rem;"
                />
                <i class="bi bi-lock input-icon"></i>
                <!-- Toggle password visibility -->
                <button type="button" class="pw-toggle" id="pw-toggle-btn" aria-label="Toggle password visibility">
                    <i class="bi bi-eye" id="pw-icon"></i>
                </button>
            </div>
            <?php if (isset($errors['password'])): ?>
            <div class="field-error">
                <i class="bi bi-exclamation-circle"></i> <?= htmlspecialchars($errors['password']) ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- Submit -->
        <button type="submit" id="btn-login" class="btn-login">
            <i class="bi bi-box-arrow-in-right"></i>
            Sign In
        </button>
    </form>

    <div class="or-divider">or</div>

    <!-- Register link -->
    <div class="register-link">
        Don't have an account?
        <a href="register.php" id="link-register">Register as a Student</a>
    </div>
</div>

<!-- Footer -->
<p class="page-footer">
    &copy; <?= date('Y') ?> StudentDB &mdash; PHP CRUD System &bull; Secured with BCrypt
</p>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // ===== Password show/hide toggle =====
    const pwInput  = document.getElementById('password');
    const pwIcon   = document.getElementById('pw-icon');
    const pwToggle = document.getElementById('pw-toggle-btn');

    pwToggle.addEventListener('click', function () {
        const isHidden = pwInput.type === 'password';
        pwInput.type   = isHidden ? 'text' : 'password';
        pwIcon.className = isHidden ? 'bi bi-eye-slash' : 'bi bi-eye';
    });

    // ===== Button loading state on submit =====
    document.getElementById('login-form').addEventListener('submit', function () {
        const btn = document.getElementById('btn-login');
        btn.disabled  = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status"></span>Signing in…';
    });
</script>
</body>
</html>
