<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
requireAdmin();

$db     = getDB();
$errors = [];
$data   = $_POST;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title       = trim($data['title'] ?? '');
    $author      = trim($data['author'] ?? '');
    $isbn        = trim($data['isbn'] ?? '');
    $genre       = trim($data['genre'] ?? '');
    $year        = intval($data['published_year'] ?? 0);
    $copies      = max(1, intval($data['total_copies'] ?? 1));
    $description = trim($data['description'] ?? '');
    $color       = $data['cover_color'] ?? randomCoverColor();

    if (!$title)  $errors[] = 'Title is required.';
    if (!$author) $errors[] = 'Author is required.';

    if (!$errors) {
        if ($isbn) {
            $chk = $db->prepare("SELECT id FROM books WHERE isbn = ?");
            $chk->bind_param('s', $isbn);
            $chk->execute();
            $chk->store_result();
            if ($chk->num_rows > 0) $errors[] = 'A book with this ISBN already exists.';
        }
    }

    if (!$errors) {
        $uid  = $_SESSION['user_id'];
        $stmt = $db->prepare("INSERT INTO books (title, author, isbn, genre, published_year, total_copies, available_copies, description, cover_color, added_by) VALUES (?,?,?,?,?,?,?,?,?,?)");
        $stmt->bind_param('ssssiiiisi', $title, $author, $isbn, $genre, $year, $copies, $copies, $description, $color, $uid);
        if ($stmt->execute()) {
            flashMessage('success', '"' . $title . '" has been added to the collection.');
            header('Location: index.php');
            exit;
        } else {
            $errors[] = 'Database error. Please try again.';
        }
    }
}

$predefined_genres = ['Classic Fiction','Romance','Science Fiction','Dystopian','Mystery','Thriller','Fantasy','Horror','Biography','History','Self-Help','Science','Philosophy','Poetry','Children'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Add Book — BookWorms</title>
  <link rel="stylesheet" href="css/style.css">
</head>
<body>
<nav class="navbar">
  <a class="nav-brand" href="index.php"><span>BW</span> BookWorms</a>
  <ul class="nav-links">
    <li><a href="index.php">Books</a></li>
    <li><a href="add_book.php" class="active">Add Book</a></li>
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
    <a href="index.php" style="color:var(--muted);font-size:.88rem;display:inline-flex;align-items:center;gap:.4rem;margin-bottom:1.5rem">← Back to collection</a>

    <div class="form-card">
      <h2>Add New Book</h2>
      <p>Fill in the details below to add a book to the library collection.</p>

      <?php if ($errors): ?>
        <div class="alert alert-danger">⚠ <?= implode('<br>', array_map('sanitize', $errors)) ?></div>
      <?php endif; ?>

      <form method="POST" action="add_book.php">
        <div class="form-row">
          <div class="form-group">
            <label>Title *</label>
            <input type="text" name="title" class="form-control" required
                   placeholder="Book title" value="<?= sanitize($data['title'] ?? '') ?>">
          </div>
          <div class="form-group">
            <label>Author *</label>
            <input type="text" name="author" class="form-control" required
                   placeholder="Author name" value="<?= sanitize($data['author'] ?? '') ?>">
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label>ISBN</label>
            <input type="text" name="isbn" class="form-control"
                   placeholder="e.g. 9780743273565" value="<?= sanitize($data['isbn'] ?? '') ?>">
          </div>
          <div class="form-group">
            <label>Genre</label>
            <select name="genre" class="form-control">
              <option value="">— Select Genre —</option>
              <?php foreach ($predefined_genres as $g): ?>
                <option value="<?= $g ?>" <?= ($data['genre'] ?? '') === $g ? 'selected' : '' ?>><?= $g ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label>Published Year</label>
            <input type="number" name="published_year" class="form-control"
                   min="1000" max="<?= date('Y') ?>" placeholder="e.g. 1984"
                   value="<?= sanitize($data['published_year'] ?? '') ?>">
          </div>
          <div class="form-group">
            <label>Number of Copies</label>
            <input type="number" name="total_copies" class="form-control"
                   min="1" max="999" value="<?= intval($data['total_copies'] ?? 1) ?>">
          </div>
        </div>

        <div class="form-group">
          <label>Description</label>
          <textarea name="description" class="form-control"
                    placeholder="A short description of the book…"><?= sanitize($data['description'] ?? '') ?></textarea>
        </div>

        <div class="form-group">
          <label>Cover Color</label>
          <div class="color-preview-row">
            <input type="color" name="cover_color" id="coverColor"
                   value="<?= sanitize($data['cover_color'] ?? '#1a1a2e') ?>"
                   class="form-control" style="width:60px;height:44px;padding:.25rem;cursor:pointer">
            <div class="color-preview-swatch" id="colorSwatch"
                 style="background:<?= sanitize($data['cover_color'] ?? '#1a1a2e') ?>"></div>
            <span style="font-size:.85rem;color:var(--muted)">Choose the book card color</span>
          </div>
        </div>

        <hr class="form-divider">
        <div class="form-actions">
          <a href="index.php" class="btn btn-secondary">Cancel</a>
          <button type="submit" class="btn btn-primary" style="width:auto">Add to Collection →</button>
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
