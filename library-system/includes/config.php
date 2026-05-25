<?php

function env($key, $default = null) {
    $value = getenv($key);
    return ($value !== false && $value !== null) ? $value : $default;
}

define('DB_HOST', env('MYSQLHOST'));
define('DB_USER', env('MYSQLUSER'));
define('DB_PASS', env('MYSQLPASSWORD'));
define('DB_NAME', env('MYSQLDATABASE'));
define('DB_PORT', env('MYSQLPORT', 3306));

function getDB() {
    static $conn = null;

    if ($conn === null) {

        // 🔴 HARD CHECK (this prevents silent 500 crashes)
        if (!DB_HOST || !DB_USER || !DB_NAME) {
            die("Missing Railway DB environment variables.");
        }

        $conn = new mysqli(
            DB_HOST,
            DB_USER,
            DB_PASS,
            DB_NAME,
            DB_PORT
        );

        if ($conn->connect_error) {
            die("Database Connection Failed: " . $conn->connect_error);
        }

        $conn->set_charset('utf8mb4');
    }

    return $conn;
}
