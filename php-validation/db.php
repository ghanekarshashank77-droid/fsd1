<?php

define('DB_HOST', 'localhost');
define('DB_USER', 'root');       // Default XAMPP/WAMP username
define('DB_PASS', '');           // Default XAMPP password is empty; WAMP may use 'root'
define('DB_NAME', 'student_db');
define('DB_CHARSET', 'utf8mb4');

// --- PDO Connection ---
try {
    // Build the DSN (Data Source Name) string for MySQL
    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;

    // PDO options for better error handling and security
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,  // Throw exceptions on error
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,        // Return arrays by column name
        PDO::ATTR_EMULATE_PREPARES => false,                   // Use real prepared statements
    ];

    // Create the PDO connection object
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);

} catch (PDOException $e) {
    // If connection fails, stop execution and display an error (never expose in production)
    die('<div style="font-family:sans-serif;color:red;padding:20px;">
            <h3>&#10060; Database Connection Failed</h3>
            <p><strong>Error:</strong> ' . htmlspecialchars($e->getMessage()) . '</p>
            <p>Make sure XAMPP/WAMP is running and the database <em>' . DB_NAME . '</em> exists.</p>
            <p>Run <code>schema.sql</code> in phpMyAdmin to set up the database.</p>
         </div>');
}
// ============================================================
// End of db.php
// ============================================================
