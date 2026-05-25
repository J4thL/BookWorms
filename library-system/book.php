<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
requireLogin();

$db = getDB();
$id = intval($_GET['id'] ?? 0);

$stmt = $db->prepare("SELECT b.*, u.full_name as added_by_name FROM books b LEFT JOIN users u ON b.added_by = u.id WHERE b.id = ?");
$stmt->bind_param('i', $id);
$stmt->execute();
$book = $stmt->get_result()->fetch_assoc();

if (!$book) {
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= sanitize($book['title']) ?> — BookWorms</title>
  <link rel="stylesheet" href="css/style.css">
</head>
<body>
<nav class="navbar">
  <a class="nav-brand" href="index.php"><span>BW</span> BookWorms</a>
  <ul class="nav-links">
    <li><a href="index.php">Books</a></li>
    <?php if (isAdmin()): ?>
    <li><a href="add_book.php">Add Book</a></li>
    <li><a href="users.php">Users</a></li>
    <?php endif; ?>
  </ul>
  <div class="nav-user">
    <div class="nav-avatar"><?= strtoupper(substr($_SESSION['user_name'], 0, 1)) ?></div>
    <span><?= sanitize($_SESSION['user_name']) ?></span>
    <a href="logout.php" style="color:var(--muted);font-size:.85rem;">Sign out</a>
  </div>
</nav>

<div class="container" style="padding-top:2.5rem;padding-bottom:4rem;max-width:800px">
  <a href="index.php" style="color:var(--muted);font-size:.88rem;display:inline-flex;align-items:center;gap:.4rem;margin-bottom:1.5rem">← Back to collection</a>

  <div style="background:var(--bg2);border:1px solid var(--border);border-radius:16px;overflow:hidden">
    <!-- Cover banner -->
    <div style="height:200px;background:linear-gradient(135deg, <?= sanitize($book['cover_color']) ?>, <?= sanitize($book['cover_color']) ?>66);display:flex;align-items:center;padding:2rem;gap:2rem;position:relative">
      <div style="font-size:5rem;filter:drop-shadow(0 4px 16px rgba(0,0,0,.5))">📖</div>
      <div>
        <h1 style="font-family:'Playfair Display',serif;font-size:1.8rem;font-weight:900;margin-bottom:.3rem"><?= sanitize($book['title']) ?></h1>
        <p style="color:rgba(255,255,255,.7);font-size:1rem">by <?= sanitize($book['author']) ?></p>
        <?php if ($book['genre']): ?>
          <span style="display:inline-block;margin-top:.5rem;font-size:.75rem;padding:.2rem .7rem;background:rgba(0,0,0,.3);border-radius:20px;color:rgba(255,255,255,.7)"><?= sanitize($book['genre']) ?></span>
        <?php endif; ?>
      </div>
      <span style="position:absolute;top:1rem;right:1rem;" class="book-badge <?= $book['available_copies'] > 0 ? 'badge-available' : 'badge-unavailable' ?>">
        <?= $book['available_copies'] > 0 ? 'Available' : 'Unavailable' ?>
      </span>
    </div>

    <!-- Details -->
    <div style="padding:2rem;display:grid;grid-template-columns:1fr 1fr;gap:2rem">
      <div>
        <h3 style="font-family:'Playfair Display',serif;margin-bottom:1rem;font-size:1.1rem">Details</h3>
        <table style="width:100%;border-collapse:collapse">
          <?php
          $details = [
            'ISBN'       => $book['isbn'],
            'Year'       => $book['published_year'],
            'Total Copies'     => $book['total_copies'],
            'Available'  => $book['available_copies'],
            'Added by'   => $book['added_by_name'] ?? 'Unknown',
            'Added on'   => date('M d, Y', strtotime($book['created_at'])),
          ];
          foreach ($details as $label => $val): if (!$val) continue; ?>
          <tr style="border-bottom:1px solid var(--border)">
            <td style="padding:.6rem 0;font-size:.8rem;color:var(--muted);text-transform:uppercase;letter-spacing:.06em;width:45%"><?= $label ?></td>
            <td style="padding:.6rem 0;font-size:.9rem"><?= sanitize((string)$val) ?></td>
          </tr>
          <?php endforeach; ?>
        </table>
      </div>
      <div>
        <h3 style="font-family:'Playfair Display',serif;margin-bottom:1rem;font-size:1.1rem">Description</h3>
        <p style="color:var(--muted);font-size:.92rem;line-height:1.7"><?= $book['description'] ? sanitize($book['description']) : 'No description available.' ?></p>
      </div>
    </div>

    <?php if (isAdmin()): ?>
    <div style="padding:1rem 2rem 2rem;display:flex;gap:1rem">
      <a href="edit_book.php?id=<?= $book['id'] ?>" class="btn btn-secondary">✏ Edit Book</a>
      <button class="btn btn-danger" onclick="document.getElementById('deleteModal').classList.add('open')">🗑 Delete Book</button>
    </div>
    <?php endif; ?>
  </div>
</div>

<?php if (isAdmin()): ?>
<div class="modal-overlay" id="deleteModal">
  <div class="modal">
    <h3>Delete Book?</h3>
    <p>Delete "<?= sanitize($book['title']) ?>"? This action cannot be undone.</p>
    <div class="modal-actions">
      <button class="btn btn-secondary" onclick="document.getElementById('deleteModal').classList.remove('open')">Cancel</button>
      <a href="delete_book.php?id=<?= $book['id'] ?>" class="btn btn-danger">Yes, Delete</a>
    </div>
  </div>
</div>
<?php endif; ?>
</body>
</html>
