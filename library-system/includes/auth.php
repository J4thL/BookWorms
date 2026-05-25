<?php
require_once __DIR__ . '/config.php';

function requireLogin() {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (!isset($_SESSION['user_id'])) {
        header('Location: login.php');
        exit;
    }
}

function requireAdmin() {
    requireLogin();
    if ($_SESSION['user_role'] !== 'admin') {
        header('Location: index.php?error=Access+denied');
        exit;
    }
}

function isLoggedIn() {
    if (session_status() === PHP_SESSION_NONE) session_start();
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isLoggedIn() && $_SESSION['user_role'] === 'admin';
}

function flashMessage($type, $message) {
    if (session_status() === PHP_SESSION_NONE) session_start();
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash() {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

function sanitize($str) {
    return htmlspecialchars(trim($str), ENT_QUOTES, 'UTF-8');
}

function randomCoverColor() {
    $colors = ['#1a1a2e','#16213e','#0f3460','#533483','#2d6a4f','#1b4332','#3d405b','#2b2d42','#6b2737','#1d3557'];
    return $colors[array_rand($colors)];
}
?>
