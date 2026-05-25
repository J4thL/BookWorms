<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
requireLogin();

$db = getDB();

// Stats
$total_books   = $db->query("SELECT COUNT(*) as c FROM books")->fetch_assoc()['c'];
$total_copies  = $db->query("SELECT SUM(total_copies) as c FROM books")->fetch_assoc()['c'] ?? 0;
$avail_copies  = $db->query("SELECT SUM(available_copies) as c FROM books")->fetch_assoc()['c'] ?? 0;
$total_users   = $db->query("SELECT COUNT(*) as c FROM users")->fetch_assoc()['c'];
$total_borrows = $db->query("SELECT COUNT(*) as c FROM borrow_records WHERE returned_at IS NULL")->fetch_assoc()['c'];

// Search & filter
$search = trim($_GET['search'] ?? '');
$genre  = trim($_GET['genre'] ?? '');
$page   = max(1, intval($_GET['page'] ?? 1));
$limit  = 12;
$offset = ($page - 1) * $limit;

$where  = [];
$params = [];
$types  = '';

if ($search !== '') {
    $where[]  = "(title LIKE ? OR author LIKE ? OR isbn LIKE ?)";
    $s = "%$search%";
    $params[] = $s; $params[] = $s; $params[] = $s;
    $types   .= 'sss';
}
if ($genre !== '') {
    $where[]  = "genre = ?";
    $params[] = $genre;
    $types   .= 's';
}

$where_sql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// Total for pagination
$count_sql  = "SELECT COUNT(*) as c FROM books $where_sql";
$count_stmt = $db->prepare($count_sql);
if ($params) { $count_stmt->bind_param($types, ...$params); }
$count_stmt->execute();
$total_results = $count_stmt->get_result()->fetch_assoc()['c'];
$total_pages   = ceil($total_results / $limit);

// Books
$books_sql  = "SELECT * FROM books $where_sql ORDER BY created_at DESC LIMIT ? OFFSET ?";
$params[]   = $limit; $params[] = $offset;
$types     .= 'ii';
$stmt       = $db->prepare($books_sql);
if ($params) { $stmt->bind_param($types, ...$params); }
$stmt->execute();
$books = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Genres for filter
$genres = $db->query("SELECT DISTINCT genre FROM books WHERE genre IS NOT NULL AND genre != '' ORDER BY genre")->fetch_all(MYSQLI_ASSOC);

$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>BookWorms — Library</title>
  <link rel="stylesheet" href="css/style.css">
</head>
<body>

<!-- Navbar -->
<nav class="navbar">
  <a class="nav-brand" href="index.php"><span>BW</span> BookWorms</a>
  <ul class="nav-links">
    <li><a href="index.php" class="active">Books</a></li>
    <?php if (isAdmin()): ?>
    <li><a href="add_book.php">Add Book</a></li>
    <li><a href="users.php">Users</a></li>
    <?php endif; ?>
  </ul>
  <div class="nav-user">
    <div class="nav-avatar"><?= strtoupper(substr($_SESSION['user_name'], 0, 1)) ?></div>
    <span><?= sanitize($_SESSION['user_name']) ?></span>
    <?php if (isAdmin()): ?><span style="font-size:.7rem;background:rgba(201,168,76,.15);color:var(--accent);padding:.1rem .4rem;border-radius:4px;">Admin</span><?php endif; ?>
    <a href="logout.php" style="color:var(--muted);font-size:.85rem;">Sign out</a>
  </div>
</nav>

<div class="container">
  <div class="page-header">
    <div>
      <h1>The Collection</h1>
      <p><?= number_format($total_results) ?> books <?= $search ? 'matching "' . sanitize($search) . '"' : 'in the library' ?></p>
    </div>
    <?php if (isAdmin()): ?>
    <a href="add_book.php" class="btn btn-primary" style="width:auto">+ Add Book</a>
    <?php endif; ?>
  </div>

  <?php if ($flash): ?>
    <div class="alert alert-<?= $flash['type'] ?>"><?= sanitize($flash['message']) ?></div>
  <?php endif; ?>

  <!-- Stats -->
  <div class="stats-grid">
    <div class="stat-card">
      <div class="stat-num"><?= $total_books ?></div>
      <div class="stat-label">Total Titles</div>
    </div>
    <div class="stat-card">
      <div class="stat-num"><?= $avail_copies ?></div>
      <div class="stat-label">Available Copies</div>
    </div>
    <div class="stat-card">
      <div class="stat-num"><?= $total_borrows ?></div>
      <div class="stat-label">Currently Borrowed</div>
    </div>
    <div class="stat-card">
      <div class="stat-num"><?= $total_users ?></div>
      <div class="stat-label">Members</div>
    </div>
  </div>

  <!-- Search -->
  <form method="GET" action="index.php">
    <div class="search-bar">
      <div class="search-input-wrap">
        <span class="search-icon">🔍</span>
        <input type="text" name="search" class="form-control"
               placeholder="Search by title, author, or ISBN…"
               value="<?= sanitize($search) ?>">
      </div>
      <select name="genre" class="form-control" style="max-width:180px">
        <option value="">All Genres</option>
        <?php foreach ($genres as $g): ?>
          <option value="<?= sanitize($g['genre']) ?>" <?= $genre === $g['genre'] ? 'selected' : '' ?>>
            <?= sanitize($g['genre']) ?>
          </option>
        <?php endforeach; ?>
      </select>
      <button type="submit" class="btn btn-secondary">Filter</button>
      <?php if ($search || $genre): ?>
        <a href="index.php" class="btn btn-secondary">Clear</a>
      <?php endif; ?>
    </div>
  </form>

  <!-- Books Grid -->
  <?php if (empty($books)): ?>
    <div class="empty-state">
      <div class="empty-icon">📭</div>
      <h3>No books found</h3>
      <p>Try a different search or <?php if(isAdmin()): ?><a href="add_book.php">add a book</a><?php else: ?>ask an admin to add books<?php endif; ?>.</p>
    </div>
  <?php else: ?>
  <div class="books-grid">
    <?php foreach ($books as $book): ?>
    <div class="book-card">
      <div class="book-cover" style="background: linear-gradient(135deg, <?= sanitize($book['cover_color']) ?>, <?= sanitize($book['cover_color']) ?>99);">
        <div class="book-spine"></div>
        <span class="book-badge <?= $book['available_copies'] > 0 ? 'badge-available' : 'badge-unavailable' ?>">
          <?= $book['available_copies'] > 0 ? 'Available' : 'Out' ?>
        </span>
        <span style="position:relative;z-index:1;font-size:2.5rem;">📖</span>
      </div>
      <div class="book-info">
        <div class="book-title"><?= sanitize($book['title']) ?></div>
        <div class="book-author">by <?= sanitize($book['author']) ?></div>
        <?php if ($book['genre']): ?>
          <span class="book-genre"><?= sanitize($book['genre']) ?></span>
        <?php endif; ?>
        <div style="font-size:.78rem;color:var(--muted);margin-bottom:.75rem;">
          <?= $book['available_copies'] ?> / <?= $book['total_copies'] ?> copies
          <?php if ($book['published_year']): ?> · <?= $book['published_year'] ?><?php endif; ?>
        </div>
        <div class="book-actions">
          <a href="book.php?id=<?= $book['id'] ?>" class="btn btn-secondary btn-sm">View</a>
          <?php if (isAdmin()): ?>
          <a href="edit_book.php?id=<?= $book['id'] ?>" class="btn btn-secondary btn-sm">Edit</a>
          <button class="btn btn-danger btn-sm" onclick="confirmDelete(<?= $book['id'] ?>, '<?= addslashes(sanitize($book['title'])) ?>')">Delete</button>
          <?php endif; ?>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- Pagination -->
  <?php if ($total_pages > 1): ?>
  <div class="pagination">
    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
      <?php $q = http_build_query(['search' => $search, 'genre' => $genre, 'page' => $i]); ?>
      <?php if ($i === $page): ?>
        <span class="current"><?= $i ?></span>
      <?php else: ?>
        <a href="index.php?<?= $q ?>"><?= $i ?></a>
      <?php endif; ?>
    <?php endfor; ?>
  </div>
  <?php endif; ?>
  <?php endif; ?>
</div>

<!-- Delete Modal -->
<div class="modal-overlay" id="deleteModal">
  <div class="modal">
    <h3>Delete Book?</h3>
    <p id="deleteModalText">This action cannot be undone.</p>
    <div class="modal-actions">
      <button class="btn btn-secondary" onclick="closeModal()">Cancel</button>
      <a id="deleteModalBtn" href="#" class="btn btn-danger">Yes, Delete</a>
    </div>
  </div>
</div>

<script>
function confirmDelete(id, title) {
  document.getElementById('deleteModalText').textContent = 'Delete "' + title + '"? This cannot be undone.';
  document.getElementById('deleteModalBtn').href = 'delete_book.php?id=' + id;
  document.getElementById('deleteModal').classList.add('open');
}
function closeModal() {
  document.getElementById('deleteModal').classList.remove('open');
}
document.getElementById('deleteModal').addEventListener('click', function(e) {
  if (e.target === this) closeModal();
});
</script>
</body>
</html>
