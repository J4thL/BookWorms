<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
requireAdmin();

$db = getDB();
$id = intval($_GET['id'] ?? 0);

// Fetch existing
$stmt = $db->prepare("SELECT * FROM books WHERE id = ?");
$stmt->bind_param('i', $id);
$stmt->execute();
$book = $stmt->get_result()->fetch_assoc();

if (!$book) {
    header('Location: index.php');
    exit;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title       = trim($_POST['title'] ?? '');
    $author      = trim($_POST['author'] ?? '');
    $isbn        = trim($_POST['isbn'] ?? '');
    $genre       = trim($_POST['genre'] ?? '');
    $year        = intval($_POST['published_year'] ?? 0);
    $total       = max(1, intval($_POST['total_copies'] ?? 1));
    $available   = max(0, intval($_POST['available_copies'] ?? 0));
    $description = trim($_POST['description'] ?? '');
    $color       = $_POST['cover_color'] ?? $book['cover_color'];

    if (!$title)  $errors[] = 'Title is required.';
    if (!$author) $errors[] = 'Author is required.';
    if ($available > $total) $errors[] = 'Available copies cannot exceed total copies.';

    if (!$errors && $isbn) {
        $chk = $db->prepare("SELECT id FROM books WHERE isbn = ? AND id != ?");
        $chk->bind_param('si', $isbn, $id);
        $chk->execute();
        $chk->store_result();
        if ($chk->num_rows > 0) $errors[] = 'Another book with this ISBN already exists.';
    }

    if (!$errors) {
        $stmt2 = $db->prepare("UPDATE books SET title=?, author=?, isbn=?, genre=?, published_year=?, total_copies=?, available_copies=?, description=?, cover_color=? WHERE id=?");
        $stmt2->bind_param('ssssiiiisi', $title, $author, $isbn, $genre, $year, $total, $available, $description, $color, $id);
        if ($stmt2->execute()) {
            flashMessage('success', '"' . $title . '" has been updated.');
            header('Location: index.php');
            exit;
        } else {
            $errors[] = 'Database error. Please try again.';
        }
    }

    // Re-populate $book with POST values for re-render
    $book = array_merge($book, [
        'title' => $title, 'author' => $author, 'isbn' => $isbn,
        'genre' => $genre, 'published_year' => $year,
        'total_copies' => $total, 'available_copies' => $available,
        'description' => $description, 'cover_color' => $color,
    ]);
}

$predefined_genres = ['Classic Fiction','Romance','Science Fiction','Dystopian','Mystery','Thriller','Fantasy','Horror','Biography','History','Self-Help','Science','Philosophy','Poetry','Children'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Edit Book — BookWorms</title>
  <link rel="stylesheet" href="css/style.css">
</head>
<body>
<nav class="navbar">
  <a class="nav-brand" href="index.php"><span>BW</span> BookWorms</a>
  <ul class="nav-links">
    <li><a href="index.php">Books</a></li>
    <li><a href="add_book.php">Add Book</a></li>
    <li><a href="users.php">Users</a></li>
  </ul>
  <div class="nav-user">
    <div class="nav-avatar"><?= strtoupper(substr($_SESSION['user_name'], 0, 1)) ?></div>
    <span><?= sanitize($_SESSION['user_name']) ?></span>
    <a href="logout.php" style="color:var(--muted);font-size:.85rem;">Sign out</a>
  </div>
</nav>

<div class="container">
  <div class="form-page">
    <a href="book.php?id=<?= $id ?>" style="color:var(--muted);font-size:.88rem;display:inline-flex;align-items:center;gap:.4rem;margin-bottom:1.5rem">← Back to book</a>

    <div class="form-card">
      <h2>Edit Book</h2>
      <p>Update the details for <em><?= sanitize($book['title']) ?></em>.</p>

      <?php if ($errors): ?>
        <div class="alert alert-danger">⚠ <?= implode('<br>', array_map('sanitize', $errors)) ?></div>
      <?php endif; ?>

      <form method="POST" action="edit_book.php?id=<?= $id ?>">
        <div class="form-row">
          <div class="form-group">
            <label>Title *</label>
            <input type="text" name="title" class="form-control" required
                   value="<?= sanitize($book['title']) ?>">
          </div>
          <div class="form-group">
            <label>Author *</label>
            <input type="text" name="author" class="form-control" required
                   value="<?= sanitize($book['author']) ?>">
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label>ISBN</label>
            <input type="text" name="isbn" class="form-control"
                   value="<?= sanitize($book['isbn'] ?? '') ?>">
          </div>
          <div class="form-group">
            <label>Genre</label>
            <select name="genre" class="form-control">
              <option value="">— Select Genre —</option>
              <?php foreach ($predefined_genres as $g): ?>
                <option value="<?= $g ?>" <?= $book['genre'] === $g ? 'selected' : '' ?>><?= $g ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label>Published Year</label>
            <input type="number" name="published_year" class="form-control"
                   min="1000" max="<?= date('Y') ?>"
                   value="<?= intval($book['published_year'] ?? 0) ?: '' ?>">
          </div>
          <div class="form-group">
            <label>Total Copies</label>
            <input type="number" name="total_copies" class="form-control"
                   min="1" value="<?= intval($book['total_copies']) ?>">
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label>Available Copies</label>
            <input type="number" name="available_copies" class="form-control"
                   min="0" value="<?= intval($book['available_copies']) ?>">
          </div>
          <div class="form-group">
            <label>Cover Color</label>
            <div class="color-preview-row">
              <input type="color" name="cover_color" id="coverColor"
                     value="<?= sanitize($book['cover_color'] ?? '#1a1a2e') ?>"
                     class="form-control" style="width:60px;height:44px;padding:.25rem;cursor:pointer">
              <div class="color-preview-swatch" id="colorSwatch"
                   style="background:<?= sanitize($book['cover_color'] ?? '#1a1a2e') ?>"></div>
            </div>
          </div>
        </div>

        <div class="form-group">
          <label>Description</label>
          <textarea name="description" class="form-control"><?= sanitize($book['description'] ?? '') ?></textarea>
        </div>

        <hr class="form-divider">
        <div class="form-actions">
          <a href="book.php?id=<?= $id ?>" class="btn btn-secondary">Cancel</a>
          <button type="submit" class="btn btn-primary" style="width:auto">Save Changes →</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
const colorInput = document.getElementById('coverColor');
const swatch = document.getElementById('colorSwatch');
colorInput.addEventListener('input', () => swatch.style.background = colorInput.value);
</script>
</body>
</html>
