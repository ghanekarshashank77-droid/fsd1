<?php
// ============================================================
// register.php — Public Student Registration Page
// No login required. After success, redirects to login.php
// ============================================================

session_start();

// If already logged in, go straight to dashboard
if (isset($_SESSION['student_id'])) {
    header('Location: index.php');
    exit;
}

require_once 'db.php';

function clean($value) {
    return htmlspecialchars(trim(strip_tags($value)));
}

// ===== Variables =====
$errors  = [];
$success = '';
$data    = ['first_name' => '', 'last_name' => '', 'roll_no' => '', 'contact_number' => ''];

// ===== POST Handler =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'register') {

    $first_name       = clean($_POST['first_name']       ?? '');
    $last_name        = clean($_POST['last_name']        ?? '');
    $roll_no          = clean($_POST['roll_no']          ?? '');
    $password         = $_POST['password']               ?? '';
    $confirm_password = $_POST['confirm_password']       ?? '';
    $contact_number   = clean($_POST['contact_number']   ?? '');

    // Preserve sticky values
    $data = compact('first_name', 'last_name', 'roll_no', 'contact_number');

    // --- Validation ---
    if ($first_name === '') {
        $errors['first_name'] = 'First Name is required.';
    } elseif (!preg_match('/^[A-Za-z\s]+$/', $first_name)) {
        $errors['first_name'] = 'First Name must contain alphabetic characters only.';
    }

    if ($last_name === '') {
        $errors['last_name'] = 'Last Name is required.';
    } elseif (!preg_match('/^[A-Za-z\s]+$/', $last_name)) {
        $errors['last_name'] = 'Last Name must contain alphabetic characters only.';
    }

    if ($roll_no === '') {
        $errors['roll_no'] = 'Roll No / Student ID is required.';
    } else {
        $stmt = $pdo->prepare('SELECT id FROM students WHERE roll_no = ?');
        $stmt->execute([$roll_no]);
        if ($stmt->fetch()) {
            $errors['roll_no'] = 'Roll No "' . $roll_no . '" is already taken. Choose a unique one.';
        }
    }

    if ($password === '') {
        $errors['password'] = 'Password is required.';
    } elseif (strlen($password) < 6) {
        $errors['password'] = 'Password must be at least 6 characters.';
    }

    if ($confirm_password === '') {
        $errors['confirm_password'] = 'Please confirm your password.';
    } elseif ($password !== $confirm_password) {
        $errors['confirm_password'] = 'Passwords do not match.';
    }

    if ($contact_number === '') {
        $errors['contact_number'] = 'Contact Number is required.';
    } elseif (!preg_match('/^\d{10}$/', $contact_number)) {
        $errors['contact_number'] = 'Contact Number must be exactly 10 digits.';
    }

    // --- Insert into DB if valid ---
    if (empty($errors)) {
        $hashed = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $pdo->prepare('INSERT INTO students (first_name, last_name, roll_no, password, contact_number) VALUES (?, ?, ?, ?, ?)');
        $stmt->execute([$first_name, $last_name, $roll_no, $hashed, $contact_number]);

        // Redirect to login with success flag
        header('Location: login.php?registered=1');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="description" content="Register as a new student — StudentDB" />
    <title>Student Registration | StudentDB</title>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet" />

    <style>
        :root {
            --primary:    #4f46e5;
            --secondary:  #7c3aed;
            --bg:         #0f0e17;
            --surface:    #1a1928;
            --border:     rgba(255,255,255,0.08);
            --text:       #e2e0ff;
            --text-muted: #8b8aa8;
            --radius:     14px;
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
            padding: 2rem 1rem;
            position: relative;
            overflow-x: hidden;
        }

        /* Background blobs */
        .blob {
            position: fixed; border-radius: 50%;
            filter: blur(80px); opacity: 0.35;
            animation: blobFloat 8s ease-in-out infinite alternate;
            pointer-events: none; z-index: 0;
        }
        .blob-1 { width:500px; height:500px; background:radial-gradient(circle,#4f46e5,transparent); top:-150px; left:-150px; }
        .blob-2 { width:400px; height:400px; background:radial-gradient(circle,#7c3aed,transparent); bottom:-120px; right:-120px; animation-delay:3s; }
        @keyframes blobFloat {
            0%   { transform: scale(1) translate(0,0); }
            100% { transform: scale(1.1) translate(15px,-15px); }
        }
        .grid-overlay {
            position: fixed; inset: 0; z-index: 0; pointer-events: none;
            background-image: linear-gradient(rgba(255,255,255,0.015) 1px, transparent 1px),
                              linear-gradient(90deg, rgba(255,255,255,0.015) 1px, transparent 1px);
            background-size: 40px 40px;
        }

        /* Brand */
        .brand-logo {
            display: flex; align-items: center; gap: 0.6rem;
            margin-bottom: 1.5rem; text-decoration: none;
            position: relative; z-index: 1;
        }
        .logo-icon {
            width: 44px; height: 44px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            border-radius: 12px; display: flex; align-items: center;
            justify-content: center; font-size: 1.3rem;
            box-shadow: 0 4px 18px rgba(79,70,229,0.5);
        }
        .logo-text {
            font-size: 1.45rem; font-weight: 700;
            background: linear-gradient(135deg, #818cf8, #a78bfa);
            -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;
        }

        /* Card */
        .reg-card {
            background: rgba(255,255,255,0.04);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: var(--radius);
            padding: 2rem 2.25rem;
            width: 100%; max-width: 520px;
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            box-shadow: 0 8px 40px rgba(0,0,0,0.5);
            position: relative; z-index: 1;
            animation: cardIn 0.4s ease forwards;
        }
        @keyframes cardIn {
            from { opacity: 0; transform: translateY(20px) scale(0.97); }
            to   { opacity: 1; transform: translateY(0) scale(1); }
        }
        .reg-card h1 { font-size: 1.4rem; font-weight: 700; margin-bottom: 0.25rem; }
        .reg-card .subtitle { font-size: 0.85rem; color: var(--text-muted); margin-bottom: 1.5rem; }

        /* Form */
        .form-group { margin-bottom: 1rem; }
        .form-label-custom {
            display: block; font-size: 0.82rem; font-weight: 600;
            color: #a5b4fc; margin-bottom: 0.38rem;
        }
        .input-wrap { position: relative; }
        .input-icon {
            position: absolute; left: 0.85rem; top: 50%;
            transform: translateY(-50%); color: var(--text-muted);
            font-size: 0.95rem; pointer-events: none; transition: color 0.2s;
        }
        .input-wrap:focus-within .input-icon { color: #818cf8; }

        .fc {
            width: 100%;
            background: rgba(255,255,255,0.055);
            border: 1px solid rgba(255,255,255,0.1);
            color: var(--text);
            border-radius: 9px;
            padding: 0.62rem 0.9rem 0.62rem 2.45rem;
            font-size: 0.88rem;
            font-family: 'Inter', sans-serif;
            transition: border-color 0.2s, box-shadow 0.2s;
            outline: none;
        }
        .fc::placeholder { color: rgba(255,255,255,0.2); }
        .fc:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(79,70,229,0.25);
            background: rgba(255,255,255,0.07);
        }
        .fc.is-invalid { border-color: #ef4444; }
        .field-error {
            color: #f87171; font-size: 0.78rem;
            margin-top: 0.32rem;
            display: flex; align-items: center; gap: 0.3rem;
        }

        /* Password toggle */
        .pw-toggle {
            position: absolute; right: 0.85rem; top: 50%;
            transform: translateY(-50%);
            background: none; border: none;
            color: var(--text-muted); cursor: pointer;
            padding: 0; font-size: 1rem; transition: color 0.2s;
        }
        .pw-toggle:hover { color: #a5b4fc; }

        /* Divider */
        .divider {
            border-top: 1px solid var(--border);
            margin: 1.25rem 0;
        }

        /* Submit */
        .btn-submit {
            width: 100%;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            border: none; color: #fff; font-weight: 600;
            font-size: 0.95rem; padding: 0.72rem;
            border-radius: 9px; cursor: pointer;
            transition: transform 0.15s, box-shadow 0.15s;
            box-shadow: 0 3px 14px rgba(79,70,229,0.45);
            display: flex; align-items: center; justify-content: center; gap: 0.5rem;
            font-family: 'Inter', sans-serif;
        }
        .btn-submit:hover { transform: translateY(-1px); box-shadow: 0 5px 22px rgba(79,70,229,0.6); }
        .btn-submit:active { transform: translateY(0); }

        /* Error summary */
        .alert-err {
            background: rgba(239,68,68,0.1);
            border: 1px solid rgba(239,68,68,0.3);
            border-radius: 9px; padding: 0.85rem 1rem;
            margin-bottom: 1.25rem;
            display: flex; align-items: flex-start; gap: 0.6rem;
            font-size: 0.85rem; color: #f87171;
        }

        /* Login link */
        .login-link {
            text-align: center; margin-top: 1.25rem;
            font-size: 0.85rem; color: var(--text-muted);
            position: relative; z-index: 1;
        }
        .login-link a { color: #818cf8; font-weight: 600; text-decoration: none; }
        .login-link a:hover { color: #a5b4fc; text-decoration: underline; }

        /* Footer */
        .page-footer {
            position: relative; z-index: 1; margin-top: 1.25rem;
            font-size: 0.75rem; color: var(--text-muted); text-align: center;
        }

        /* Two-column grid for the form */
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0 1rem;
        }
        .form-grid .full { grid-column: 1 / -1; }

        @media (max-width: 480px) {
            .form-grid { grid-template-columns: 1fr; }
            .reg-card { padding: 1.5rem; }
        }
    </style>
</head>
<body>

<div class="blob blob-1"></div>
<div class="blob blob-2"></div>
<div class="grid-overlay"></div>

<!-- Brand -->
<a class="brand-logo" href="login.php">
    <div class="logo-icon">&#127891;</div>
    <span class="logo-text">StudentDB</span>
</a>

<!-- Registration Card -->
<div class="reg-card">
    <h1>Create Account</h1>
    <p class="subtitle">Fill in the details below to register as a student.</p>

    <!-- Error summary -->
    <?php if (!empty($errors)): ?>
    <div class="alert-err">
        <i class="bi bi-exclamation-triangle-fill fs-5 flex-shrink-0"></i>
        <div>
            <strong>Please fix these errors:</strong>
            <ul class="mb-0 mt-1" style="padding-left:1.1rem;">
                <?php foreach ($errors as $e): ?>
                <li><?= $e ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
    <?php endif; ?>

    <!-- ===== REGISTRATION FORM ===== -->
    <form method="POST" action="register.php" novalidate id="reg-form">
        <input type="hidden" name="action" value="register" />

        <div class="form-grid">

            <!-- First Name -->
            <div class="form-group">
                <label class="form-label-custom" for="first_name">First Name</label>
                <div class="input-wrap">
                    <input type="text" id="first_name" name="first_name"
                           class="fc <?= isset($errors['first_name']) ? 'is-invalid' : '' ?>"
                           placeholder="e.g. John"
                           value="<?= htmlspecialchars($data['first_name']) ?>"
                           autocomplete="given-name" autofocus />
                    <i class="bi bi-person input-icon"></i>
                </div>
                <?php if (isset($errors['first_name'])): ?>
                <div class="field-error"><i class="bi bi-exclamation-circle"></i> <?= $errors['first_name'] ?></div>
                <?php endif; ?>
            </div>

            <!-- Last Name -->
            <div class="form-group">
                <label class="form-label-custom" for="last_name">Last Name</label>
                <div class="input-wrap">
                    <input type="text" id="last_name" name="last_name"
                           class="fc <?= isset($errors['last_name']) ? 'is-invalid' : '' ?>"
                           placeholder="e.g. Doe"
                           value="<?= htmlspecialchars($data['last_name']) ?>"
                           autocomplete="family-name" />
                    <i class="bi bi-person input-icon"></i>
                </div>
                <?php if (isset($errors['last_name'])): ?>
                <div class="field-error"><i class="bi bi-exclamation-circle"></i> <?= $errors['last_name'] ?></div>
                <?php endif; ?>
            </div>

            <!-- Roll No -->
            <div class="form-group">
                <label class="form-label-custom" for="roll_no">Roll No / Student ID</label>
                <div class="input-wrap">
                    <input type="text" id="roll_no" name="roll_no"
                           class="fc <?= isset($errors['roll_no']) ? 'is-invalid' : '' ?>"
                           placeholder="e.g. CS2024001"
                           value="<?= htmlspecialchars($data['roll_no']) ?>" />
                    <i class="bi bi-hash input-icon"></i>
                </div>
                <?php if (isset($errors['roll_no'])): ?>
                <div class="field-error"><i class="bi bi-exclamation-circle"></i> <?= $errors['roll_no'] ?></div>
                <?php endif; ?>
            </div>

            <!-- Contact Number -->
            <div class="form-group">
                <label class="form-label-custom" for="contact_number">Contact Number</label>
                <div class="input-wrap">
                    <input type="text" id="contact_number" name="contact_number"
                           class="fc <?= isset($errors['contact_number']) ? 'is-invalid' : '' ?>"
                           placeholder="10-digit number"
                           value="<?= htmlspecialchars($data['contact_number']) ?>"
                           maxlength="10" />
                    <i class="bi bi-telephone input-icon"></i>
                </div>
                <?php if (isset($errors['contact_number'])): ?>
                <div class="field-error"><i class="bi bi-exclamation-circle"></i> <?= $errors['contact_number'] ?></div>
                <?php endif; ?>
            </div>

            <!-- Password -->
            <div class="form-group">
                <label class="form-label-custom" for="password">Password</label>
                <div class="input-wrap">
                    <input type="password" id="password" name="password"
                           class="fc <?= isset($errors['password']) ? 'is-invalid' : '' ?>"
                           placeholder="Min. 6 characters"
                           autocomplete="new-password"
                           style="padding-right:2.4rem;" />
                    <i class="bi bi-lock input-icon"></i>
                    <button type="button" class="pw-toggle" id="pw-btn1" aria-label="Toggle password">
                        <i class="bi bi-eye" id="pw-icon1"></i>
                    </button>
                </div>
                <?php if (isset($errors['password'])): ?>
                <div class="field-error"><i class="bi bi-exclamation-circle"></i> <?= $errors['password'] ?></div>
                <?php endif; ?>
            </div>

            <!-- Confirm Password -->
            <div class="form-group">
                <label class="form-label-custom" for="confirm_password">Confirm Password</label>
                <div class="input-wrap">
                    <input type="password" id="confirm_password" name="confirm_password"
                           class="fc <?= isset($errors['confirm_password']) ? 'is-invalid' : '' ?>"
                           placeholder="Re-enter password"
                           autocomplete="new-password"
                           style="padding-right:2.4rem;" />
                    <i class="bi bi-lock-fill input-icon"></i>
                    <button type="button" class="pw-toggle" id="pw-btn2" aria-label="Toggle confirm password">
                        <i class="bi bi-eye" id="pw-icon2"></i>
                    </button>
                </div>
                <?php if (isset($errors['confirm_password'])): ?>
                <div class="field-error"><i class="bi bi-exclamation-circle"></i> <?= $errors['confirm_password'] ?></div>
                <?php endif; ?>
            </div>

        </div><!-- /.form-grid -->

        <div class="divider"></div>

        <button type="submit" id="btn-register" class="btn-submit">
            <i class="bi bi-person-check-fill"></i> Create Account
        </button>
    </form>
</div>

<!-- Login link -->
<p class="login-link">
    Already have an account? <a href="login.php" id="link-login">Sign In</a>
</p>

<p class="page-footer">&copy; <?= date('Y') ?> StudentDB &mdash; Passwords secured with BCrypt</p>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Password toggle — field 1
    document.getElementById('pw-btn1').addEventListener('click', function () {
        const f = document.getElementById('password');
        const i = document.getElementById('pw-icon1');
        const show = f.type === 'password';
        f.type = show ? 'text' : 'password';
        i.className = show ? 'bi bi-eye-slash' : 'bi bi-eye';
    });
    // Password toggle — field 2
    document.getElementById('pw-btn2').addEventListener('click', function () {
        const f = document.getElementById('confirm_password');
        const i = document.getElementById('pw-icon2');
        const show = f.type === 'password';
        f.type = show ? 'text' : 'password';
        i.className = show ? 'bi bi-eye-slash' : 'bi bi-eye';
    });
    // Only allow digits in contact field
    document.getElementById('contact_number').addEventListener('input', function () {
        this.value = this.value.replace(/\D/g, '');
    });
    // Loading state on submit
    document.getElementById('reg-form').addEventListener('submit', function () {
        const btn = document.getElementById('btn-register');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Registering…';
    });
</script>
</body>
</html>
