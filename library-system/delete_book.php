<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
requireAdmin();

$db = getDB();
$id = intval($_GET['id'] ?? 0);

$stmt = $db->prepare("SELECT title FROM books WHERE id = ?");
$stmt->bind_param('i', $id);
$stmt->execute();
$book = $stmt->get_result()->fetch_assoc();

if ($book) {
    $del = $db->prepare("DELETE FROM books WHERE id = ?");
    $del->bind_param('i', $id);
    if ($del->execute()) {
        flashMessage('success', '"' . $book['title'] . '" has been deleted.');
    } else {
        flashMessage('danger', 'Failed to delete the book. Please try again.');
    }
} else {
    flashMessage('danger', 'Book not found.');
}

header('Location: index.php');
exit;
