<?php
$SERVER_NAME = getenv('SERVER_NAME');
$USERNAME = getenv('USERNAME');
$PASSWORD = getenv('PASSWORD');
$DB_NAME = getenv('DB_NAME');
        
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
