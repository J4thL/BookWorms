<?php

define('DB_HOST', getenv('MYSQLHOST') ?: getenv('DB_HOST') ?: 'localhost' );
define('DB_USER', getenv('MYSQLUSER') ?: getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('MYSQLPASSWORD') ?: getenv('DB_PASS') ?: '');
define('DB_NAME', getenv('MYSQLDATABASE') ?: getenv('DB_NAME') ?: 'library_db');
define('DB_PORT', getenv('MYSQLPORT') ?: getenv('DB_PORT') ?: '3306');

function getDB() {
    static $conn = null;
    if ($conn === null) {
        $conn = new mysqli(
            DB_HOST,
            DB_USER,
            DB_PASS,
            DB_NAME,
            (int)DB_PORT
        );
        if ($conn->connect_error) {
            die('<div style="font-family:monospace;padding:20px;background:#1a0000;color:#ff4444;border:1px solid #ff4444;margin:20px;border-radius:8px;">
                <strong>Database Connection Failed:</strong><br>' . $conn->connect_error . '<br><br>
                Please check your database settings in <code>includes/config.php</code>
                and make sure you have run <code>database.sql</code>.
            </div>');
        }
        $conn->set_charset('utf8mb4');
    }
    return $conn;
}
?>
