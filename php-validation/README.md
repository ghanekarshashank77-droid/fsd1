# PHP CRUD Student Registration System

A complete server-side PHP CRUD application using MySQL (PDO) and Bootstrap 5. Supports student **registration, viewing, editing, and deletion** with full server-side form validation.

---

## 📁 Folder Structure

```
php-validation/
├── index.php       → Main file (all CRUD, forms, validation, UI)
├── db.php          → Database connection file (PDO)
├── schema.sql      → SQL to create the database and students table
└── README.md       → This setup guide
```

---

## ⚙️ Prerequisites

- **XAMPP** or **WAMP** installed and running
- Apache and MySQL services must be **started**
- PHP 7.4+ (included with XAMPP/WAMP)

---

## 🚀 Setup Steps

### Step 1: Place the Files
Copy the entire `php-validation/` folder to your web server's root directory:

| Server | Web Root Directory |
|--------|-------------------|
| XAMPP  | `C:\xampp\htdocs\` |
| WAMP   | `C:\wamp64\www\`   |

So the final path should be:
```
C:\xampp\htdocs\php-validation\
```

### Step 2: Start Apache & MySQL
- Open **XAMPP Control Panel** (or WAMP system tray icon)
- Click **Start** next to both **Apache** and **MySQL**
- Ensure both show green status

### Step 3: Create the Database
1. Open your browser and go to: [http://localhost/phpmyadmin](http://localhost/phpmyadmin)
2. Click the **SQL** tab in the top menu
3. Open `schema.sql` from the project folder, copy all its contents, and paste into the SQL box
4. Click **Go** to execute — this creates the `student_db` database and `students` table

   > **Alternatively:** Click **Import** → Choose file → Select `schema.sql` → Click **Go**

### Step 4: Configure Database Credentials (if needed)
Open `db.php` and verify/update the constants at the top:

```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');   // XAMPP default; WAMP may be 'root' too
define('DB_PASS', '');       // XAMPP default is empty; WAMP may use 'root'
define('DB_NAME', 'student_db');
```

### Step 5: Open the Application
Go to: [http://localhost/php-validation/index.php](http://localhost/php-validation/index.php)

You should see the Student Registration System with four tabs:
- **Register** — Add a new student
- **View Students** — See all registered students in a table
- **Update** — Search by Roll No and edit details
- **Delete** — Remove a student by Roll No

---

## 🔐 Security Features
- All passwords stored using `password_hash()` with BCRYPT — **never plain text**
- All database queries use **PDO Prepared Statements** to prevent SQL injection
- All output is escaped with `htmlspecialchars()` to prevent XSS

---

## 🛠️ Troubleshooting

| Issue | Solution |
|-------|----------|
| Blank page / 500 error | Enable error display in `php.ini` or check Apache logs |
| "Database connection failed" | Make sure MySQL is running and `schema.sql` was executed |
| "Table not found" error | Re-run `schema.sql` in phpMyAdmin |
| Port conflict on Apache | Change Apache port in XAMPP → Config → httpd.conf |
