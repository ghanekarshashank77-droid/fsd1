<?php
// ============================================================
// index.php — PHP CRUD Student Registration System
// All CRUD operations, HTML forms, Bootstrap 5 UI, Validation
// ============================================================

// Start the session first — must be before any output
session_start();

// ===== SESSION GUARD =====
// If the student is not logged in, redirect to the login page
if (!isset($_SESSION['student_id'])) {
    header('Location: login.php');
    exit;
}

// Grab the logged-in student's info from session
$logged_name = htmlspecialchars($_SESSION['student_name'] ?? 'Student');
$logged_roll = htmlspecialchars($_SESSION['student_roll'] ?? '');

// Include the database connection (PDO object: $pdo)
require_once 'db.php';

// ============================================================
// HELPER — sanitize input: trim whitespace and strip tags
// ============================================================
function clean($value) {
    return htmlspecialchars(trim(strip_tags($value)));
}

// ============================================================
// Determine which page/tab to show (default: register)
// ============================================================
$page = isset($_GET['page']) ? $_GET['page'] : 'register';
$allowed_pages = ['register', 'view', 'update', 'delete'];
if (!in_array($page, $allowed_pages)) {
    $page = 'register';
}

// ============================================================
// ===== INSERT SECTION — Student Registration =====
// ============================================================

// Variables to persist form values and carry messages
$reg_errors   = [];
$reg_success  = '';
$reg_data     = [
    'first_name'      => '',
    'last_name'       => '',
    'roll_no'         => '',
    'contact_number'  => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'register') {

    // --- Collect and sanitize inputs ---
    $first_name      = clean($_POST['first_name']      ?? '');
    $last_name       = clean($_POST['last_name']       ?? '');
    $roll_no         = clean($_POST['roll_no']         ?? '');
    $password        = $_POST['password']              ?? '';
    $confirm_password= $_POST['confirm_password']      ?? '';
    $contact_number  = clean($_POST['contact_number']  ?? '');

    // Preserve sticky form data
    $reg_data = [
        'first_name'     => $first_name,
        'last_name'      => $last_name,
        'roll_no'        => $roll_no,
        'contact_number' => $contact_number,
    ];

    // --- Validation ---

    // First Name
    if ($first_name === '') {
        $reg_errors['first_name'] = 'First Name is required.';
    } elseif (!preg_match('/^[A-Za-z\s]+$/', $first_name)) {
        $reg_errors['first_name'] = 'First Name must contain alphabetic characters only.';
    }

    // Last Name
    if ($last_name === '') {
        $reg_errors['last_name'] = 'Last Name is required.';
    } elseif (!preg_match('/^[A-Za-z\s]+$/', $last_name)) {
        $reg_errors['last_name'] = 'Last Name must contain alphabetic characters only.';
    }

    // Roll No
    if ($roll_no === '') {
        $reg_errors['roll_no'] = 'Roll No / ID is required.';
    } else {
        // Check for duplicate Roll No in database
        $stmt = $pdo->prepare('SELECT id FROM students WHERE roll_no = ?');
        $stmt->execute([$roll_no]);
        if ($stmt->fetch()) {
            $reg_errors['roll_no'] = 'Roll No "' . $roll_no . '" is already registered. Use a unique Roll No.';
        }
    }

    // Password
    if ($password === '') {
        $reg_errors['password'] = 'Password is required.';
    } elseif (strlen($password) < 6) {
        $reg_errors['password'] = 'Password must be at least 6 characters long.';
    }

    // Confirm Password
    if ($confirm_password === '') {
        $reg_errors['confirm_password'] = 'Please confirm your password.';
    } elseif ($password !== $confirm_password) {
        $reg_errors['confirm_password'] = 'Passwords do not match.';
    }

    // Contact Number
    if ($contact_number === '') {
        $reg_errors['contact_number'] = 'Contact Number is required.';
    } elseif (!preg_match('/^\d{10}$/', $contact_number)) {
        $reg_errors['contact_number'] = 'Contact Number must be exactly 10 numeric digits.';
    }

    // --- Insert into DB if no errors ---
    if (empty($reg_errors)) {
        // Hash the password securely with BCRYPT (never store plain text!)
        $hashed_password = password_hash($password, PASSWORD_BCRYPT);

        // Prepared statement to prevent SQL injection
        $stmt = $pdo->prepare('
            INSERT INTO students (first_name, last_name, roll_no, password, contact_number)
            VALUES (?, ?, ?, ?, ?)
        ');
        $stmt->execute([$first_name, $last_name, $roll_no, $hashed_password, $contact_number]);

        $reg_success = 'Student <strong>' . $first_name . ' ' . $last_name . '</strong> registered successfully!';
        // Reset form data after success
        $reg_data = ['first_name' => '', 'last_name' => '', 'roll_no' => '', 'contact_number' => ''];
    }
}

// ============================================================
// ===== DELETE SECTION — Delete Student Record =====
// ============================================================

$del_error   = '';
$del_success = '';
$del_roll_no = '';
$del_confirm_data = null; // Stores student data for confirmation step

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    // Step 1: User submits Roll No to search for deletion
    if ($_POST['action'] === 'delete_search') {
        $del_roll_no = clean($_POST['roll_no'] ?? '');

        if ($del_roll_no === '') {
            $del_error = 'Please enter a Roll No to search.';
        } else {
            // Look up the student
            $stmt = $pdo->prepare('SELECT id, first_name, last_name, roll_no, contact_number FROM students WHERE roll_no = ?');
            $stmt->execute([$del_roll_no]);
            $student = $stmt->fetch();

            if ($student) {
                // Store in session-like hidden fields (we'll pass via form)
                $del_confirm_data = $student;
            } else {
                $del_error = 'No student found with Roll No: <strong>' . $del_roll_no . '</strong>';
            }
        }
    }

    // Step 2: User confirms deletion
    if ($_POST['action'] === 'delete_confirm') {
        $del_roll_no = clean($_POST['roll_no'] ?? '');

        if ($del_roll_no !== '') {
            $stmt = $pdo->prepare('DELETE FROM students WHERE roll_no = ?');
            $stmt->execute([$del_roll_no]);

            if ($stmt->rowCount() > 0) {
                $del_success = 'Student with Roll No <strong>' . $del_roll_no . '</strong> has been permanently deleted.';
            } else {
                $del_error = 'Could not delete. Student not found.';
            }
            $del_roll_no = '';
        }
    }
}

// ============================================================
// ===== UPDATE SECTION — Update Student Details =====
// ============================================================

$upd_errors       = [];
$upd_error        = '';
$upd_success      = '';
$upd_search_roll  = '';
$upd_student      = null; // Will hold the found student for pre-filling the form
$upd_data         = ['first_name' => '', 'last_name' => '', 'contact_number' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    // Step 1: Search for the student by Roll No
    if ($_POST['action'] === 'update_search') {
        $upd_search_roll = clean($_POST['roll_no'] ?? '');

        if ($upd_search_roll === '') {
            $upd_error = 'Please enter a Roll No to search.';
        } else {
            $stmt = $pdo->prepare('SELECT id, first_name, last_name, roll_no, contact_number FROM students WHERE roll_no = ?');
            $stmt->execute([$upd_search_roll]);
            $upd_student = $stmt->fetch();

            if (!$upd_student) {
                $upd_error = 'No student found with Roll No: <strong>' . $upd_search_roll . '</strong>';
            } else {
                // Pre-fill edit form with existing data
                $upd_data = [
                    'first_name'     => $upd_student['first_name'],
                    'last_name'      => $upd_student['last_name'],
                    'contact_number' => $upd_student['contact_number'],
                ];
            }
        }
    }

    // Step 2: Save updated details
    if ($_POST['action'] === 'update_save') {
        $upd_search_roll = clean($_POST['roll_no']         ?? '');
        $first_name      = clean($_POST['first_name']      ?? '');
        $last_name       = clean($_POST['last_name']       ?? '');
        $contact_number  = clean($_POST['contact_number']  ?? '');

        // Preserve sticky form values
        $upd_data = [
            'first_name'     => $first_name,
            'last_name'      => $last_name,
            'contact_number' => $contact_number,
        ];

        // Keep the edit form visible while showing errors
        if ($upd_search_roll !== '') {
            $stmt = $pdo->prepare('SELECT id, first_name, last_name, roll_no, contact_number FROM students WHERE roll_no = ?');
            $stmt->execute([$upd_search_roll]);
            $upd_student = $stmt->fetch();
        }

        // --- Validation ---
        if ($first_name === '') {
            $upd_errors['first_name'] = 'First Name is required.';
        } elseif (!preg_match('/^[A-Za-z\s]+$/', $first_name)) {
            $upd_errors['first_name'] = 'First Name must contain alphabetic characters only.';
        }

        if ($last_name === '') {
            $upd_errors['last_name'] = 'Last Name is required.';
        } elseif (!preg_match('/^[A-Za-z\s]+$/', $last_name)) {
            $upd_errors['last_name'] = 'Last Name must contain alphabetic characters only.';
        }

        if ($contact_number === '') {
            $upd_errors['contact_number'] = 'Contact Number is required.';
        } elseif (!preg_match('/^\d{10}$/', $contact_number)) {
            $upd_errors['contact_number'] = 'Contact Number must be exactly 10 numeric digits.';
        }

        // --- Save to DB if no errors ---
        if (empty($upd_errors)) {
            $stmt = $pdo->prepare('
                UPDATE students
                SET first_name = ?, last_name = ?, contact_number = ?
                WHERE roll_no = ?
            ');
            $stmt->execute([$first_name, $last_name, $contact_number, $upd_search_roll]);

            if ($stmt->rowCount() > 0) {
                $upd_success = 'Student record updated successfully!';
            } else {
                $upd_success = 'No changes were made (values may be the same).';
            }
            $upd_student = null; // Hide edit form after save
        }
    }
}

// ============================================================
// ===== VIEW SECTION — Fetch All Students =====
// ============================================================
$all_students = [];
if ($page === 'view') {
    $stmt = $pdo->query('SELECT id, first_name, last_name, roll_no, contact_number, created_at FROM students ORDER BY created_at DESC');
    $all_students = $stmt->fetchAll();
}

// Also handle inline delete from the View table (Edit/Delete action buttons)
$view_del_success = '';
$view_del_error   = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'table_delete') {
    $del_id = (int)($_POST['student_id'] ?? 0);
    if ($del_id > 0) {
        $stmt = $pdo->prepare('DELETE FROM students WHERE id = ?');
        $stmt->execute([$del_id]);
        if ($stmt->rowCount() > 0) {
            $view_del_success = 'Student record deleted successfully.';
        } else {
            $view_del_error = 'Record not found or already deleted.';
        }
    }
    // Re-fetch after deletion
    $stmt = $pdo->query('SELECT id, first_name, last_name, roll_no, contact_number, created_at FROM students ORDER BY created_at DESC');
    $all_students = $stmt->fetchAll();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="description" content="PHP CRUD Student Registration System with MySQL, Bootstrap 5, and full server-side validation." />
    <title>Student Registration System | PHP CRUD</title>

    <!-- Bootstrap 5 CDN -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" />
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" />
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet" />

    <style>
        /* ===== Root Variables & Base ===== */
        :root {
            --primary:      #4f46e5;
            --primary-dark: #3730a3;
            --secondary:    #7c3aed;
            --success:      #059669;
            --danger:       #dc2626;
            --bg:           #0f0e17;
            --surface:      #1a1928;
            --surface-2:    #22213a;
            --border:       rgba(255,255,255,0.08);
            --text:         #e2e0ff;
            --text-muted:   #8b8aa8;
            --radius:       12px;
        }

        * { box-sizing: border-box; }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg);
            color: var(--text);
            min-height: 100vh;
            margin: 0;
        }

        /* ===== Gradient Background Blobs ===== */
        body::before {
            content: '';
            position: fixed;
            top: -200px; left: -200px;
            width: 600px; height: 600px;
            background: radial-gradient(circle, rgba(79,70,229,0.15) 0%, transparent 70%);
            pointer-events: none; z-index: 0;
        }
        body::after {
            content: '';
            position: fixed;
            bottom: -200px; right: -200px;
            width: 600px; height: 600px;
            background: radial-gradient(circle, rgba(124,58,237,0.12) 0%, transparent 70%);
            pointer-events: none; z-index: 0;
        }

        /* ===== Navbar ===== */
        .navbar-custom {
            background: rgba(26,25,40,0.85);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-bottom: 1px solid var(--border);
            position: sticky; top: 0; z-index: 1000;
            padding: 0.85rem 0;
        }

        .navbar-brand-custom {
            font-size: 1.25rem;
            font-weight: 700;
            background: linear-gradient(135deg, #818cf8, #a78bfa);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            text-decoration: none;
            display: flex; align-items: center; gap: 0.5rem;
        }
        .navbar-brand-custom i { font-size: 1.4rem; }

        .nav-pills-custom {
            display: flex; gap: 0.25rem; flex-wrap: wrap;
        }

        .nav-pills-custom a {
            display: inline-flex; align-items: center; gap: 0.4rem;
            padding: 0.45rem 1rem;
            border-radius: 8px;
            font-size: 0.875rem;
            font-weight: 500;
            color: var(--text-muted);
            text-decoration: none;
            transition: all 0.2s ease;
        }

        .nav-pills-custom a:hover {
            background: rgba(255,255,255,0.06);
            color: var(--text);
        }

        .nav-pills-custom a.active {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: #fff;
            box-shadow: 0 2px 12px rgba(79,70,229,0.4);
        }

        /* ===== Main Container ===== */
        .main-content {
            position: relative; z-index: 1;
            max-width: 900px;
            margin: 2.5rem auto;
            padding: 0 1rem;
        }

        /* ===== Section Heading ===== */
        .section-heading {
            font-size: 1.6rem;
            font-weight: 700;
            margin-bottom: 0.25rem;
            background: linear-gradient(135deg, #c7d2fe, #ddd6fe);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .section-sub {
            color: var(--text-muted);
            font-size: 0.9rem;
            margin-bottom: 1.75rem;
        }

        /* ===== Card (Glassmorphism) ===== */
        .glass-card {
            background: rgba(255,255,255,0.04);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 2rem;
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            box-shadow: 0 4px 32px rgba(0,0,0,0.3);
        }

        /* ===== Form Controls ===== */
        .form-label {
            font-size: 0.85rem;
            font-weight: 600;
            color: #a5b4fc;
            margin-bottom: 0.35rem;
        }

        .form-control, .form-select {
            background: rgba(255,255,255,0.05) !important;
            border: 1px solid rgba(255,255,255,0.12) !important;
            color: var(--text) !important;
            border-radius: 8px !important;
            padding: 0.6rem 0.9rem;
            font-size: 0.9rem;
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--primary) !important;
            box-shadow: 0 0 0 3px rgba(79,70,229,0.25) !important;
            outline: none;
        }

        .form-control::placeholder { color: rgba(255,255,255,0.25) !important; }

        .form-control.is-invalid {
            border-color: var(--danger) !important;
        }

        .invalid-feedback {
            color: #f87171;
            font-size: 0.8rem;
            margin-top: 0.3rem;
        }

        /* ===== Buttons ===== */
        .btn-primary-custom {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            border: none;
            color: #fff;
            font-weight: 600;
            padding: 0.65rem 1.75rem;
            border-radius: 8px;
            font-size: 0.9rem;
            cursor: pointer;
            transition: transform 0.15s, box-shadow 0.15s, opacity 0.15s;
            box-shadow: 0 2px 12px rgba(79,70,229,0.4);
        }
        .btn-primary-custom:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 20px rgba(79,70,229,0.55);
            opacity: 0.95;
        }
        .btn-primary-custom:active { transform: translateY(0); }

        .btn-danger-custom {
            background: linear-gradient(135deg, #dc2626, #b91c1c);
            border: none;
            color: #fff;
            font-weight: 600;
            padding: 0.65rem 1.75rem;
            border-radius: 8px;
            font-size: 0.9rem;
            cursor: pointer;
            transition: transform 0.15s, box-shadow 0.15s;
            box-shadow: 0 2px 10px rgba(220,38,38,0.35);
        }
        .btn-danger-custom:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 18px rgba(220,38,38,0.5);
        }

        .btn-outline-custom {
            background: transparent;
            border: 1px solid rgba(255,255,255,0.2);
            color: var(--text-muted);
            font-weight: 500;
            padding: 0.65rem 1.2rem;
            border-radius: 8px;
            font-size: 0.9rem;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
            display: inline-block;
        }
        .btn-outline-custom:hover {
            border-color: var(--primary);
            color: #a5b4fc;
        }

        /* Small action buttons inside table */
        .btn-sm-edit {
            background: rgba(79,70,229,0.15);
            border: 1px solid rgba(79,70,229,0.4);
            color: #a5b4fc;
            padding: 0.3rem 0.7rem;
            border-radius: 6px;
            font-size: 0.78rem;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.2s;
            display: inline-flex; align-items: center; gap: 0.3rem;
        }
        .btn-sm-edit:hover {
            background: rgba(79,70,229,0.35);
            color: #fff;
        }
        .btn-sm-del {
            background: rgba(220,38,38,0.12);
            border: 1px solid rgba(220,38,38,0.35);
            color: #f87171;
            padding: 0.3rem 0.7rem;
            border-radius: 6px;
            font-size: 0.78rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            display: inline-flex; align-items: center; gap: 0.3rem;
        }
        .btn-sm-del:hover { background: rgba(220,38,38,0.3); color: #fff; }

        /* ===== Alerts ===== */
        .alert-success-custom {
            background: rgba(5,150,105,0.15);
            border: 1px solid rgba(5,150,105,0.4);
            color: #6ee7b7;
            border-radius: 10px;
            padding: 1rem 1.25rem;
            margin-bottom: 1.5rem;
            display: flex; align-items: flex-start; gap: 0.75rem;
            font-size: 0.9rem;
        }
        .alert-danger-custom {
            background: rgba(220,38,38,0.12);
            border: 1px solid rgba(220,38,38,0.35);
            color: #f87171;
            border-radius: 10px;
            padding: 1rem 1.25rem;
            margin-bottom: 1.5rem;
            display: flex; align-items: flex-start; gap: 0.75rem;
            font-size: 0.9rem;
        }
        .alert-warning-custom {
            background: rgba(217,119,6,0.12);
            border: 1px solid rgba(217,119,6,0.3);
            color: #fcd34d;
            border-radius: 10px;
            padding: 1rem 1.25rem;
            margin-bottom: 1.5rem;
            display: flex; align-items: flex-start; gap: 0.75rem;
            font-size: 0.9rem;
        }

        /* ===== Students Table ===== */
        .table-wrapper {
            overflow-x: auto;
            border-radius: var(--radius);
            border: 1px solid var(--border);
        }
        table.students-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            font-size: 0.875rem;
        }
        table.students-table thead tr {
            background: linear-gradient(135deg, rgba(79,70,229,0.3), rgba(124,58,237,0.2));
        }
        table.students-table thead th {
            padding: 0.85rem 1rem;
            font-size: 0.78rem;
            font-weight: 700;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            color: #a5b4fc;
            border-bottom: 1px solid var(--border);
            white-space: nowrap;
        }
        table.students-table tbody tr {
            transition: background 0.15s;
        }
        table.students-table tbody tr:nth-child(even) {
            background: rgba(255,255,255,0.025);
        }
        table.students-table tbody tr:hover {
            background: rgba(79,70,229,0.1);
        }
        table.students-table tbody td {
            padding: 0.8rem 1rem;
            border-bottom: 1px solid rgba(255,255,255,0.05);
            color: var(--text);
            vertical-align: middle;
        }
        table.students-table tbody tr:last-child td {
            border-bottom: none;
        }

        .badge-roll {
            background: rgba(79,70,229,0.2);
            border: 1px solid rgba(79,70,229,0.4);
            color: #a5b4fc;
            padding: 0.25rem 0.6rem;
            border-radius: 6px;
            font-size: 0.78rem;
            font-weight: 700;
            font-family: monospace;
        }

        /* ===== Search bar for update/delete ===== */
        .search-bar {
            display: flex; gap: 0.75rem; align-items: flex-end; flex-wrap: wrap;
        }
        .search-bar .form-group { flex: 1; min-width: 180px; }

        /* ===== Confirm box ===== */
        .confirm-box {
            background: rgba(220,38,38,0.08);
            border: 1px solid rgba(220,38,38,0.3);
            border-radius: 10px;
            padding: 1.25rem 1.5rem;
            margin-top: 1rem;
        }
        .confirm-box h6 {
            color: #f87171;
            font-weight: 700;
            margin-bottom: 0.75rem;
        }
        .confirm-box p { color: var(--text-muted); font-size: 0.875rem; margin-bottom: 0.5rem; }
        .confirm-box strong { color: var(--text); }

        /* ===== Empty state ===== */
        .empty-state {
            text-align: center;
            padding: 3rem 1rem;
        }
        .empty-state i { font-size: 3rem; color: var(--text-muted); margin-bottom: 1rem; }
        .empty-state p { color: var(--text-muted); }

        /* ===== Divider ===== */
        .divider {
            border-top: 1px solid var(--border);
            margin: 1.5rem 0;
        }

        /* ===== Fade-in animation ===== */
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(16px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        .fade-in { animation: fadeInUp 0.35s ease forwards; }

        /* ===== Responsive ===== */
        @media (max-width: 576px) {
            .glass-card { padding: 1.25rem; }
            .main-content { margin-top: 1.5rem; }
        }

        /* ===== User Pill & Logout in Navbar ===== */
        .user-pill {
            display: inline-flex; align-items: center; gap: 0.5rem;
            background: rgba(255,255,255,0.06);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 999px;
            padding: 0.3rem 0.9rem 0.3rem 0.45rem;
            font-size: 0.8rem;
            color: var(--text);
        }
        .user-pill .user-avatar {
            width: 26px; height: 26px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-weight: 700; font-size: 0.72rem; color: #fff;
            flex-shrink: 0;
        }
        .user-pill .user-info { line-height: 1.2; }
        .user-pill .user-name { font-weight: 600; font-size: 0.8rem; color: var(--text); }
        .user-pill .user-roll { font-size: 0.7rem; color: var(--text-muted); }

        .btn-logout {
            display: inline-flex; align-items: center; gap: 0.35rem;
            background: rgba(220,38,38,0.1);
            border: 1px solid rgba(220,38,38,0.3);
            color: #f87171;
            font-size: 0.8rem;
            font-weight: 600;
            padding: 0.38rem 0.85rem;
            border-radius: 8px;
            text-decoration: none;
            transition: all 0.2s;
        }
        .btn-logout:hover {
            background: rgba(220,38,38,0.25);
            color: #fff;
            border-color: rgba(220,38,38,0.6);
        }
    </style>
</head>
<body>

<!-- ============================================================ -->
<!-- TOP NAVIGATION BAR -->
<!-- ============================================================ -->
<nav class="navbar-custom">
    <div class="container d-flex align-items-center justify-content-between flex-wrap gap-2">
        <!-- Brand -->
        <a class="navbar-brand-custom" href="index.php">
            <i class="bi bi-mortarboard-fill"></i>
            StudentDB
        </a>

        <!-- Navigation Tabs -->
        <div class="nav-pills-custom">
            <a href="index.php?page=register"
               class="<?= $page === 'register' ? 'active' : '' ?>"
               id="nav-register">
                <i class="bi bi-person-plus"></i> Register
            </a>
            <a href="index.php?page=view"
               class="<?= $page === 'view' ? 'active' : '' ?>"
               id="nav-view">
                <i class="bi bi-table"></i> View Students
            </a>
            <a href="index.php?page=update"
               class="<?= $page === 'update' ? 'active' : '' ?>"
               id="nav-update">
                <i class="bi bi-pencil-square"></i> Update
            </a>
            <a href="index.php?page=delete"
               class="<?= $page === 'delete' ? 'active' : '' ?>"
               id="nav-delete">
                <i class="bi bi-trash3"></i> Delete
            </a>
        </div>

        <!-- Logged-in user info + Logout -->
        <div class="d-flex align-items-center gap-2">
            <!-- User pill -->
            <div class="user-pill">
                <div class="user-avatar">
                    <?= strtoupper(substr($_SESSION['student_name'] ?? 'S', 0, 1)) ?>
                </div>
                <div class="user-info">
                    <div class="user-name"><?= $logged_name ?></div>
                    <div class="user-roll"><?= $logged_roll ?></div>
                </div>
            </div>
            <!-- Logout button -->
            <a href="logout.php" class="btn-logout" id="btn-logout">
                <i class="bi bi-box-arrow-right"></i> Logout
            </a>
        </div>
    </div>
</nav>

<!-- ============================================================ -->
<!-- MAIN CONTENT AREA -->
<!-- ============================================================ -->
<div class="main-content">

<?php // ======================================================= ?>
<?php // ===== PAGE: REGISTER (INSERT) ========================= ?>
<?php // ======================================================= ?>
<?php if ($page === 'register'): ?>
<div class="fade-in">
    <h1 class="section-heading"><i class="bi bi-person-plus-fill me-2"></i>Student Registration</h1>
    <p class="section-sub">Fill in the form below to register a new student. All fields are required.</p>

    <!-- Success Message -->
    <?php if ($reg_success): ?>
    <div class="alert-success-custom" role="alert">
        <i class="bi bi-check-circle-fill fs-5 flex-shrink-0"></i>
        <div><?= $reg_success ?></div>
    </div>
    <?php endif; ?>

    <!-- Validation Error Summary (if any) -->
    <?php if (!empty($reg_errors)): ?>
    <div class="alert-danger-custom" role="alert">
        <i class="bi bi-exclamation-triangle-fill fs-5 flex-shrink-0"></i>
        <div>
            <strong>Please fix the following errors:</strong>
            <ul class="mb-0 mt-1" style="padding-left:1.1rem;">
                <?php foreach ($reg_errors as $err): ?>
                <li><?= $err ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
    <?php endif; ?>

    <!-- ===== REGISTRATION FORM ===== -->
    <div class="glass-card">
        <form method="POST" action="index.php?page=register" novalidate id="register-form">
            <!-- Hidden action field to identify which POST handler to use -->
            <input type="hidden" name="action" value="register" />

            <div class="row g-3">

                <!-- First Name -->
                <div class="col-md-6">
                    <label class="form-label" for="reg_first_name">
                        <i class="bi bi-person me-1"></i>First Name
                    </label>
                    <input
                        type="text"
                        id="reg_first_name"
                        name="first_name"
                        class="form-control <?= isset($reg_errors['first_name']) ? 'is-invalid' : '' ?>"
                        placeholder="e.g. John"
                        value="<?= htmlspecialchars($reg_data['first_name']) ?>"
                        autocomplete="given-name"
                    />
                    <?php if (isset($reg_errors['first_name'])): ?>
                    <div class="invalid-feedback">&#9888; <?= $reg_errors['first_name'] ?></div>
                    <?php endif; ?>
                </div>

                <!-- Last Name -->
                <div class="col-md-6">
                    <label class="form-label" for="reg_last_name">
                        <i class="bi bi-person me-1"></i>Last Name
                    </label>
                    <input
                        type="text"
                        id="reg_last_name"
                        name="last_name"
                        class="form-control <?= isset($reg_errors['last_name']) ? 'is-invalid' : '' ?>"
                        placeholder="e.g. Doe"
                        value="<?= htmlspecialchars($reg_data['last_name']) ?>"
                        autocomplete="family-name"
                    />
                    <?php if (isset($reg_errors['last_name'])): ?>
                    <div class="invalid-feedback">&#9888; <?= $reg_errors['last_name'] ?></div>
                    <?php endif; ?>
                </div>

                <!-- Roll No -->
                <div class="col-md-6">
                    <label class="form-label" for="reg_roll_no">
                        <i class="bi bi-hash me-1"></i>Roll No / Student ID
                    </label>
                    <input
                        type="text"
                        id="reg_roll_no"
                        name="roll_no"
                        class="form-control <?= isset($reg_errors['roll_no']) ? 'is-invalid' : '' ?>"
                        placeholder="e.g. CS2024001"
                        value="<?= htmlspecialchars($reg_data['roll_no']) ?>"
                    />
                    <?php if (isset($reg_errors['roll_no'])): ?>
                    <div class="invalid-feedback">&#9888; <?= $reg_errors['roll_no'] ?></div>
                    <?php endif; ?>
                </div>

                <!-- Contact Number -->
                <div class="col-md-6">
                    <label class="form-label" for="reg_contact">
                        <i class="bi bi-telephone me-1"></i>Contact Number
                    </label>
                    <input
                        type="text"
                        id="reg_contact"
                        name="contact_number"
                        class="form-control <?= isset($reg_errors['contact_number']) ? 'is-invalid' : '' ?>"
                        placeholder="10-digit number"
                        value="<?= htmlspecialchars($reg_data['contact_number']) ?>"
                        maxlength="10"
                    />
                    <?php if (isset($reg_errors['contact_number'])): ?>
                    <div class="invalid-feedback">&#9888; <?= $reg_errors['contact_number'] ?></div>
                    <?php endif; ?>
                </div>

                <!-- Password -->
                <div class="col-md-6">
                    <label class="form-label" for="reg_password">
                        <i class="bi bi-lock me-1"></i>Password
                    </label>
                    <input
                        type="password"
                        id="reg_password"
                        name="password"
                        class="form-control <?= isset($reg_errors['password']) ? 'is-invalid' : '' ?>"
                        placeholder="Minimum 6 characters"
                        autocomplete="new-password"
                    />
                    <?php if (isset($reg_errors['password'])): ?>
                    <div class="invalid-feedback">&#9888; <?= $reg_errors['password'] ?></div>
                    <?php endif; ?>
                </div>

                <!-- Confirm Password -->
                <div class="col-md-6">
                    <label class="form-label" for="reg_confirm_password">
                        <i class="bi bi-lock-fill me-1"></i>Confirm Password
                    </label>
                    <input
                        type="password"
                        id="reg_confirm_password"
                        name="confirm_password"
                        class="form-control <?= isset($reg_errors['confirm_password']) ? 'is-invalid' : '' ?>"
                        placeholder="Re-enter password"
                        autocomplete="new-password"
                    />
                    <?php if (isset($reg_errors['confirm_password'])): ?>
                    <div class="invalid-feedback">&#9888; <?= $reg_errors['confirm_password'] ?></div>
                    <?php endif; ?>
                </div>

            </div><!-- /.row -->

            <div class="divider"></div>

            <div class="d-flex gap-2 align-items-center">
                <button type="submit" id="btn-register" class="btn-primary-custom">
                    <i class="bi bi-person-check me-1"></i> Register Student
                </button>
                <a href="index.php?page=view" class="btn-outline-custom">
                    <i class="bi bi-table me-1"></i> View All Students
                </a>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>


<?php // ======================================================= ?>
<?php // ===== PAGE: VIEW ALL STUDENTS ========================= ?>
<?php // ======================================================= ?>
<?php if ($page === 'view'): ?>
<div class="fade-in">
    <h1 class="section-heading"><i class="bi bi-table me-2"></i>All Students</h1>
    <p class="section-sub">Complete list of registered students. Use the action buttons to edit or delete records.</p>

    <!-- Inline delete success/error from table actions -->
    <?php if ($view_del_success): ?>
    <div class="alert-success-custom">
        <i class="bi bi-check-circle-fill fs-5 flex-shrink-0"></i>
        <div><?= htmlspecialchars($view_del_success) ?></div>
    </div>
    <?php endif; ?>
    <?php if ($view_del_error): ?>
    <div class="alert-danger-custom">
        <i class="bi bi-exclamation-triangle-fill fs-5 flex-shrink-0"></i>
        <div><?= htmlspecialchars($view_del_error) ?></div>
    </div>
    <?php endif; ?>

    <?php if (empty($all_students)): ?>
    <!-- Empty State -->
    <div class="glass-card">
        <div class="empty-state">
            <i class="bi bi-inbox d-block"></i>
            <p class="fw-semibold">No students registered yet.</p>
            <p style="font-size:0.85rem;">Go to the <a href="index.php?page=register" style="color:#a5b4fc;">Register</a> tab to add the first student.</p>
        </div>
    </div>

    <?php else: ?>
    <!-- Students Table -->
    <div class="table-wrapper">
        <table class="students-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>First Name</th>
                    <th>Last Name</th>
                    <th>Roll No</th>
                    <th>Contact</th>
                    <th>Registered At</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php $i = 1; foreach ($all_students as $student): ?>
                <tr>
                    <td><?= $i++ ?></td>
                    <td><?= htmlspecialchars($student['first_name']) ?></td>
                    <td><?= htmlspecialchars($student['last_name']) ?></td>
                    <td><span class="badge-roll"><?= htmlspecialchars($student['roll_no']) ?></span></td>
                    <td><?= htmlspecialchars($student['contact_number']) ?></td>
                    <td style="color:var(--text-muted); font-size:0.82rem;">
                        <?= date('d M Y, h:i A', strtotime($student['created_at'])) ?>
                    </td>
                    <td>
                        <div class="d-flex gap-1 flex-wrap">
                            <!-- Edit button: links to update page pre-loaded with roll_no -->
                            <a href="index.php?page=update&roll_no=<?= urlencode($student['roll_no']) ?>"
                               class="btn-sm-edit" title="Edit student">
                                <i class="bi bi-pencil"></i> Edit
                            </a>

                            <!-- Delete button: inline form with confirmation -->
                            <form method="POST"
                                  action="index.php?page=view"
                                  onsubmit="return confirm('Are you sure you want to permanently delete <?= htmlspecialchars(addslashes($student['first_name'] . ' ' . $student['last_name'])) ?>?');"
                                  style="margin:0;">
                                <input type="hidden" name="action" value="table_delete" />
                                <input type="hidden" name="student_id" value="<?= (int)$student['id'] ?>" />
                                <button type="submit" class="btn-sm-del" title="Delete student">
                                    <i class="bi bi-trash"></i> Delete
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <p style="color:var(--text-muted);font-size:0.8rem;margin-top:0.75rem;">
        Showing <?= count($all_students) ?> student<?= count($all_students) !== 1 ? 's' : '' ?> total.
    </p>
    <?php endif; ?>
</div>
<?php endif; ?>


<?php // ======================================================= ?>
<?php // ===== PAGE: UPDATE STUDENT ============================ ?>
<?php // ======================================================= ?>
<?php
// If coming from "Edit" button in the view table, auto-load the search
if ($page === 'update' && isset($_GET['roll_no']) && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    $upd_search_roll = clean($_GET['roll_no']);
    $stmt = $pdo->prepare('SELECT id, first_name, last_name, roll_no, contact_number FROM students WHERE roll_no = ?');
    $stmt->execute([$upd_search_roll]);
    $upd_student = $stmt->fetch();
    if ($upd_student) {
        $upd_data = [
            'first_name'     => $upd_student['first_name'],
            'last_name'      => $upd_student['last_name'],
            'contact_number' => $upd_student['contact_number'],
        ];
    }
}
?>
<?php if ($page === 'update'): ?>
<div class="fade-in">
    <h1 class="section-heading"><i class="bi bi-pencil-square me-2"></i>Update Student Details</h1>
    <p class="section-sub">Search by Roll No to find the student, then update their information.</p>

    <!-- Success Message -->
    <?php if ($upd_success): ?>
    <div class="alert-success-custom">
        <i class="bi bi-check-circle-fill fs-5 flex-shrink-0"></i>
        <div><?= $upd_success ?></div>
    </div>
    <?php endif; ?>

    <!-- Error Message (search not found) -->
    <?php if ($upd_error): ?>
    <div class="alert-danger-custom">
        <i class="bi bi-exclamation-triangle-fill fs-5 flex-shrink-0"></i>
        <div><?= $upd_error ?></div>
    </div>
    <?php endif; ?>

    <!-- ===== STEP 1: SEARCH FORM ===== -->
    <div class="glass-card">
        <p class="form-label mb-2" style="font-size:0.9rem; color:var(--text-muted);">
            <i class="bi bi-search me-1"></i> Step 1 — Search for Student
        </p>
        <form method="POST" action="index.php?page=update" id="update-search-form">
            <input type="hidden" name="action" value="update_search" />
            <div class="search-bar">
                <div class="form-group">
                    <label class="form-label" for="upd_roll_no">Roll No / Student ID</label>
                    <input
                        type="text"
                        id="upd_roll_no"
                        name="roll_no"
                        class="form-control"
                        placeholder="e.g. CS2024001"
                        value="<?= htmlspecialchars($upd_search_roll) ?>"
                    />
                </div>
                <div>
                    <button type="submit" class="btn-primary-custom" id="btn-update-search">
                        <i class="bi bi-search me-1"></i> Find Student
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- ===== STEP 2: EDIT FORM (shown only when student found) ===== -->
    <?php if ($upd_student): ?>
    <div class="glass-card mt-3" id="edit-form-card">
        <p class="form-label mb-3" style="font-size:0.9rem; color:var(--text-muted);">
            <i class="bi bi-pencil me-1"></i> Step 2 — Edit Details for
            <strong style="color:#a5b4fc;"><?= htmlspecialchars($upd_student['first_name'] . ' ' . $upd_student['last_name']) ?></strong>
            <span class="badge-roll ms-1"><?= htmlspecialchars($upd_student['roll_no']) ?></span>
        </p>

        <!-- Validation errors -->
        <?php if (!empty($upd_errors)): ?>
        <div class="alert-danger-custom mb-3">
            <i class="bi bi-exclamation-triangle-fill fs-5 flex-shrink-0"></i>
            <div>
                <strong>Fix errors before saving:</strong>
                <ul class="mb-0 mt-1" style="padding-left:1.1rem;">
                    <?php foreach ($upd_errors as $err): ?>
                    <li><?= $err ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
        <?php endif; ?>

        <form method="POST" action="index.php?page=update" id="update-save-form">
            <input type="hidden" name="action"   value="update_save" />
            <!-- Pass roll_no so we know which record to update -->
            <input type="hidden" name="roll_no"  value="<?= htmlspecialchars($upd_search_roll ?: $upd_student['roll_no']) ?>" />

            <div class="row g-3">
                <!-- First Name -->
                <div class="col-md-6">
                    <label class="form-label" for="upd_first_name">
                        <i class="bi bi-person me-1"></i>First Name
                    </label>
                    <input
                        type="text"
                        id="upd_first_name"
                        name="first_name"
                        class="form-control <?= isset($upd_errors['first_name']) ? 'is-invalid' : '' ?>"
                        value="<?= htmlspecialchars($upd_data['first_name']) ?>"
                    />
                    <?php if (isset($upd_errors['first_name'])): ?>
                    <div class="invalid-feedback">&#9888; <?= $upd_errors['first_name'] ?></div>
                    <?php endif; ?>
                </div>

                <!-- Last Name -->
                <div class="col-md-6">
                    <label class="form-label" for="upd_last_name">
                        <i class="bi bi-person me-1"></i>Last Name
                    </label>
                    <input
                        type="text"
                        id="upd_last_name"
                        name="last_name"
                        class="form-control <?= isset($upd_errors['last_name']) ? 'is-invalid' : '' ?>"
                        value="<?= htmlspecialchars($upd_data['last_name']) ?>"
                    />
                    <?php if (isset($upd_errors['last_name'])): ?>
                    <div class="invalid-feedback">&#9888; <?= $upd_errors['last_name'] ?></div>
                    <?php endif; ?>
                </div>

                <!-- Contact Number -->
                <div class="col-md-6">
                    <label class="form-label" for="upd_contact">
                        <i class="bi bi-telephone me-1"></i>Contact Number
                    </label>
                    <input
                        type="text"
                        id="upd_contact"
                        name="contact_number"
                        class="form-control <?= isset($upd_errors['contact_number']) ? 'is-invalid' : '' ?>"
                        value="<?= htmlspecialchars($upd_data['contact_number']) ?>"
                        maxlength="10"
                    />
                    <?php if (isset($upd_errors['contact_number'])): ?>
                    <div class="invalid-feedback">&#9888; <?= $upd_errors['contact_number'] ?></div>
                    <?php endif; ?>
                </div>

                <!-- Roll No is read-only (cannot be changed) -->
                <div class="col-md-6">
                    <label class="form-label">
                        <i class="bi bi-hash me-1"></i>Roll No (read-only)
                    </label>
                    <input
                        type="text"
                        class="form-control"
                        value="<?= htmlspecialchars($upd_student['roll_no']) ?>"
                        disabled
                        style="opacity:0.55; cursor:not-allowed;"
                    />
                    <small style="color:var(--text-muted); font-size:0.78rem;">Roll No cannot be changed.</small>
                </div>
            </div>

            <div class="divider"></div>

            <div class="d-flex gap-2">
                <button type="submit" id="btn-save-update" class="btn-primary-custom">
                    <i class="bi bi-save me-1"></i> Save Changes
                </button>
                <a href="index.php?page=update" class="btn-outline-custom">
                    <i class="bi bi-arrow-counterclockwise me-1"></i> Reset
                </a>
            </div>
        </form>
    </div>
    <?php endif; ?>
</div>
<?php endif; ?>


<?php // ======================================================= ?>
<?php // ===== PAGE: DELETE STUDENT ============================ ?>
<?php // ======================================================= ?>
<?php if ($page === 'delete'): ?>
<div class="fade-in">
    <h1 class="section-heading"><i class="bi bi-trash3-fill me-2"></i>Delete Student Record</h1>
    <p class="section-sub">Enter the student's Roll No to search and confirm deletion.</p>

    <!-- Success Message -->
    <?php if ($del_success): ?>
    <div class="alert-success-custom">
        <i class="bi bi-check-circle-fill fs-5 flex-shrink-0"></i>
        <div><?= $del_success ?></div>
    </div>
    <?php endif; ?>

    <!-- Error Message -->
    <?php if ($del_error): ?>
    <div class="alert-danger-custom">
        <i class="bi bi-exclamation-triangle-fill fs-5 flex-shrink-0"></i>
        <div><?= $del_error ?></div>
    </div>
    <?php endif; ?>

    <!-- ===== STEP 1: SEARCH FORM ===== -->
    <div class="glass-card">
        <p class="form-label mb-2" style="font-size:0.9rem; color:var(--text-muted);">
            <i class="bi bi-search me-1"></i> Step 1 — Search Student by Roll No
        </p>
        <form method="POST" action="index.php?page=delete" id="delete-search-form">
            <input type="hidden" name="action" value="delete_search" />
            <div class="search-bar">
                <div class="form-group">
                    <label class="form-label" for="del_roll_no">Roll No / Student ID</label>
                    <input
                        type="text"
                        id="del_roll_no"
                        name="roll_no"
                        class="form-control"
                        placeholder="e.g. CS2024001"
                        value="<?= htmlspecialchars($del_roll_no) ?>"
                    />
                </div>
                <div>
                    <button type="submit" id="btn-delete-search" class="btn-primary-custom">
                        <i class="bi bi-search me-1"></i> Search
                    </button>
                </div>
            </div>
        </form>

        <!-- ===== STEP 2: CONFIRMATION BOX (shown only when student found) ===== -->
        <?php if ($del_confirm_data): ?>
        <div class="confirm-box">
            <h6><i class="bi bi-exclamation-triangle-fill me-1"></i> Confirm Deletion</h6>
            <p>You are about to <strong style="color:#f87171;">permanently delete</strong> the following student record. This action <strong>cannot be undone.</strong></p>
            <table style="font-size:0.875rem; color:var(--text); width:auto; margin-bottom:1rem;">
                <tr>
                    <td style="padding:0.2rem 0.75rem 0.2rem 0; color:var(--text-muted);">Full Name</td>
                    <td><strong><?= htmlspecialchars($del_confirm_data['first_name'] . ' ' . $del_confirm_data['last_name']) ?></strong></td>
                </tr>
                <tr>
                    <td style="padding:0.2rem 0.75rem 0.2rem 0; color:var(--text-muted);">Roll No</td>
                    <td><span class="badge-roll"><?= htmlspecialchars($del_confirm_data['roll_no']) ?></span></td>
                </tr>
                <tr>
                    <td style="padding:0.2rem 0.75rem 0.2rem 0; color:var(--text-muted);">Contact</td>
                    <td><strong><?= htmlspecialchars($del_confirm_data['contact_number']) ?></strong></td>
                </tr>
            </table>

            <!-- Confirmation DELETE form -->
            <form method="POST" action="index.php?page=delete" id="delete-confirm-form">
                <input type="hidden" name="action"   value="delete_confirm" />
                <input type="hidden" name="roll_no"  value="<?= htmlspecialchars($del_confirm_data['roll_no']) ?>" />
                <div class="d-flex gap-2">
                    <button type="submit" id="btn-confirm-delete" class="btn-danger-custom">
                        <i class="bi bi-trash3 me-1"></i> Yes, Delete Permanently
                    </button>
                    <a href="index.php?page=delete" class="btn-outline-custom">
                        <i class="bi bi-x me-1"></i> Cancel
                    </a>
                </div>
            </form>
        </div>
        <?php endif; ?>
    </div>

    <!-- Helpful info box -->
    <div class="alert-warning-custom mt-3">
        <i class="bi bi-info-circle-fill fs-5 flex-shrink-0"></i>
        <div>
            <strong>Note:</strong> Deletion is permanent and cannot be undone. Make sure you have the correct Roll No before confirming.
            You can also delete from the <a href="index.php?page=view" style="color:#fcd34d;">View Students</a> table.
        </div>
    </div>
</div>
<?php endif; ?>

</div><!-- /.main-content -->

<!-- Bootstrap 5 JS Bundle (for any Bootstrap JS features) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
// ============================================================
// Client-side UX enhancements (not a replacement for server validation)
// ============================================================

// Auto-dismiss alerts after 8 seconds
document.addEventListener('DOMContentLoaded', function () {

    // Smooth scroll to any error highlight
    const firstInvalid = document.querySelector('.is-invalid');
    if (firstInvalid) {
        firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
        firstInvalid.focus();
    }

    // Allow only numeric input in contact number fields
    const contactFields = document.querySelectorAll('#reg_contact, #upd_contact, #del_contact');
    contactFields.forEach(function(field) {
        field.addEventListener('input', function () {
            this.value = this.value.replace(/\D/g, '');
        });
    });
});
</script>

</body>
</html>
