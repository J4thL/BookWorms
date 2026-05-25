<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
requireAdmin();

$db = getDB();

// Handle role change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_id'], $_POST['role'])) {
    $uid  = intval($_POST['user_id']);
    $role = in_array($_POST['role'], ['admin','member']) ? $_POST['role'] : 'member';
    if ($uid !== $_SESSION['user_id']) { // can't change own role
        $stmt = $db->prepare("UPDATE users SET role = ? WHERE id = ?");
        $stmt->bind_param('si', $role, $uid);
        $stmt->execute();
        flashMessage('success', 'User role updated.');
    }
    header('Location: users.php');
    exit;
}

// Handle delete user
if (isset($_GET['delete_user'])) {
    $uid = intval($_GET['delete_user']);
    if ($uid !== $_SESSION['user_id']) {
        $db->prepare("DELETE FROM users WHERE id = ?")->bind_param('i', $uid) ;
        $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param('i', $uid);
        $stmt->execute();
        flashMessage('success', 'User removed.');
    }
    header('Location: users.php');
    exit;
}

$users = $db->query("SELECT u.*, COUNT(b.id) as borrow_count FROM users u LEFT JOIN borrow_records b ON u.id = b.user_id AND b.returned_at IS NULL GROUP BY u.id ORDER BY u.created_at DESC")->fetch_all(MYSQLI_ASSOC);
$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Users — BookWorms</title>
  <link rel="stylesheet" href="css/style.css">
</head>
<body>
<nav class="navbar">
  <a class="nav-brand" href="index.php"><span>BW</span> BookWorms</a>
  <ul class="nav-links">
    <li><a href="index.php">Books</a></li>
    <li><a href="add_book.php">Add Book</a></li>
    <li><a href="users.php" class="active">Users</a></li>
  </ul>
  <div class="nav-user">
    <div class="nav-avatar"><?= strtoupper(substr($_SESSION['user_name'], 0, 1)) ?></div>
    <span><?= sanitize($_SESSION['user_name']) ?></span>
    <a href="logout.php" style="color:var(--muted);font-size:.85rem;">Sign out</a>
  </div>
</nav>

<div class="container">
  <div class="page-header">
    <div>
      <h1>Members</h1>
      <p><?= count($users) ?> registered users</p>
    </div>
  </div>

  <?php if ($flash): ?>
    <div class="alert alert-<?= $flash['type'] ?>"><?= sanitize($flash['message']) ?></div>
  <?php endif; ?>

  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>#</th>
          <th>Name</th>
          <th>Email</th>
          <th>Role</th>
          <th>Active Borrows</th>
          <th>Joined</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($users as $u): ?>
        <tr>
          <td style="color:var(--muted)"><?= $u['id'] ?></td>
          <td>
            <div style="display:flex;align-items:center;gap:.6rem">
              <div class="nav-avatar" style="width:28px;height:28px;font-size:.7rem"><?= strtoupper(substr($u['full_name'],0,1)) ?></div>
              <?= sanitize($u['full_name']) ?>
              <?php if ($u['id'] == $_SESSION['user_id']): ?><span style="font-size:.7rem;color:var(--muted)">(you)</span><?php endif; ?>
            </div>
          </td>
          <td style="color:var(--muted)"><?= sanitize($u['email']) ?></td>
          <td>
            <span style="font-size:.75rem;font-weight:600;padding:.2rem .6rem;border-radius:20px;
              <?= $u['role']==='admin' ? 'background:rgba(201,168,76,.15);color:var(--accent)' : 'background:var(--bg3);color:var(--muted)' ?>">
              <?= $u['role'] ?>
            </span>
          </td>
          <td><?= $u['borrow_count'] ?></td>
          <td style="color:var(--muted)"><?= date('M d, Y', strtotime($u['created_at'])) ?></td>
          <td>
            <?php if ($u['id'] != $_SESSION['user_id']): ?>
            <div style="display:flex;gap:.5rem;flex-wrap:wrap">
              <form method="POST" action="users.php" style="display:inline">
                <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                <input type="hidden" name="role" value="<?= $u['role']==='admin' ? 'member' : 'admin' ?>">
                <button type="submit" class="btn btn-secondary btn-sm">
                  <?= $u['role']==='admin' ? 'Make Member' : 'Make Admin' ?>
                </button>
              </form>
              <a href="users.php?delete_user=<?= $u['id'] ?>"
                 class="btn btn-danger btn-sm"
                 onclick="return confirm('Remove <?= addslashes(sanitize($u['full_name'])) ?>?')">Remove</a>
            </div>
            <?php else: ?>
              <span style="font-size:.8rem;color:var(--muted)">—</span>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
</body>
</html>
