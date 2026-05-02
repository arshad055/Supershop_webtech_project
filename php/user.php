<?php

declare(strict_types=1);
session_start();


mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$DB_HOST = '127.0.0.1';
$DB_PORT = 3306;               
$DB_USER = 'root';
$DB_PASS = '';
$DB_NAME = 'addproductdb';

try {
  $conn = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME, $DB_PORT);
  $conn->set_charset('utf8mb4');
} catch (Throwable $e) {
  http_response_code(500);
  exit('Database connection failed.');
}


function e(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }

if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));
$csrf = $_SESSION['csrf'];

$errors = [];
$success = '';


if (isset($_GET['delete'])) {
  $id = (int)$_GET['delete'];
  if ($id > 0) {
  
    $st = $conn->prepare("DELETE FROM users WHERE id=?");
    $st->bind_param("i", $id);
    $st->execute();
    $st->close();
  }
  header("Location: user.php");
  exit;
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!isset($_POST['csrf']) || !hash_equals($csrf, (string)$_POST['csrf'])) {
    $errors[] = 'Invalid request. Please reload.';
  } else {
    $formType = $_POST['formType'] ?? '';         
    $name  = trim((string)($_POST['name'] ?? ''));
    $email = trim((string)($_POST['email'] ?? ''));
    $phone = trim((string)($_POST['phone'] ?? ''));
    $role  = trim((string)($_POST['role'] ?? ''));

    if ($name === '' || mb_strlen($name) < 3) $errors[] = 'Name must be at least 3 characters.';
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email required.';
    if (!preg_match('/^01[0-9]{9}$/', $phone)) $errors[] = 'Phone must be 11 digits and start with 01 (e.g., 017XXXXXXXX).';
    if (!in_array($role, ['CUSTOMER','EMPLOYEE'], true)) $errors[] = 'Invalid role.';

    if (!$errors) {
      try {
        $conn->begin_transaction();

        if ($formType === 'edit') {
          $id = (int)($_POST['id'] ?? 0);
          if ($id <= 0) throw new RuntimeException('Missing user id.');

         
          $st = $conn->prepare("UPDATE users SET fullname=?, email=?, phone=? WHERE id=?");
          $st->bind_param("sssi", $name, $email, $phone, $id);
          $st->execute(); $st->close();

          if ($role === 'EMPLOYEE') {
            $st = $conn->prepare("SELECT 1 FROM employees WHERE user_id=? LIMIT 1");
            $st->bind_param("i", $id);
            $st->execute();
            $isEmp = $st->get_result()->num_rows > 0;
            $st->close();

            if (!$isEmp) {
              $st = $conn->prepare("INSERT INTO employees (user_id) VALUES (?)");
              $st->bind_param("i", $id);
              $st->execute(); $st->close();
            }
          } else { 
            $st = $conn->prepare("DELETE FROM employees WHERE user_id=?");
            $st->bind_param("i", $id);
            $st->execute(); $st->close();
          }

          $success = "User $id updated successfully.";

        } elseif ($formType === 'add') {
          
          $baseUser = '';
          if ($email !== '' && strpos($email, '@') !== false) {
            $baseUser = preg_replace('/[^a-z0-9_]/i', '', strstr($email, '@', true));
          }
          if ($baseUser === '') {
            $baseUser = preg_replace('/[^a-z0-9_]/i', '', strtolower(str_replace(' ', '', $name)));
            if ($baseUser === '') $baseUser = 'user';
          }

          $username = $baseUser;
          $i = 1;
          $check = $conn->prepare("SELECT 1 FROM users WHERE username=? LIMIT 1");
          while (true) {
            $check->bind_param("s", $username);
            $check->execute();
            $exists = $check->get_result()->num_rows > 0;
            if (!$exists) break;
            $username = $baseUser . $i++;
          }
          $check->close();

          $passwordHash = password_hash('123456', PASSWORD_DEFAULT);

        
          $st = $conn->prepare("
            INSERT INTO users (fullname, email, username, password_hash, phone, gender)
            VALUES (?, ?, ?, ?, ?, NULL)
          ");
          $st->bind_param("sssss", $name, $email, $username, $passwordHash, $phone);
          $st->execute();
          $newId = $st->insert_id;
          $st->close();

          
          if ($role === 'EMPLOYEE') {
            $st = $conn->prepare("INSERT INTO employees (user_id) VALUES (?)");
            $st->bind_param("i", $newId);
            $st->execute(); $st->close();
          }

          $success = "User $newId added successfully.";
        }

        $conn->commit();
      } catch (Throwable $e) {
        try { $conn->rollback(); } catch (Throwable $e2) {}
        $errors[] = 'Operation failed.';
      }
    }
  }
}

$sql = "
  SELECT
    u.id,
    u.fullname   AS name,
    u.email      AS email,
    u.phone      AS phone,
    CASE WHEN e.user_id IS NULL THEN 'CUSTOMER' ELSE 'EMPLOYEE' END AS role,
    DATE_FORMAT(u.created_at, '%d-%b-%Y') AS joined
  FROM users u
  LEFT JOIN employees e ON e.user_id = u.id
  ORDER BY u.id DESC
";
$res = $conn->query($sql);
$users = $res->fetch_all(MYSQLI_ASSOC);
$res->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/><meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Users • Admin Panel</title>
  <link rel="stylesheet" href="../css/user.css"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
  <style>
    .error{color:#b00020;font-size:.9rem;margin-top:4px}
    .success{color:#0a7f33;font-weight:600;margin-bottom:10px}
    .flash{margin:10px 0;padding:8px 10px;border-radius:8px}
    .flash.ok{background:#e8fff0;border:1px solid #c7f2d0;color:#0a7f33}
    .flash.err{background:#ffecec;border:1px solid #ffcbcb;color:#b00020}
    .edit-form,.add-form{margin-top:8px;padding:8px;border:1px solid #ddd;border-radius:8px;background:#fff}
    .edit-form input,.edit-form select,.add-form input,.add-form select{width:100%;padding:6px;margin-bottom:8px}
    .badge.customer{background:#e3f2fd;color:#0d47a1}
    .badge.employee{background:#fff8e1;color:#e65100}
  </style>
</head>
<body>
<header class="site">
  <nav class="nav">
    <a href="admin.php" class="logo">Admin<span>Panel</span></a>
    <a href="home.php">Home</a>
    <a href="admin_products.php">Products</a>
    <a href="order.php">Orders</a>
    <a href="user.php" class="active">Users</a>
    <a href="message.php">Messages</a>
    <div class="icons">
      <a href="logout.php" class="btn logout-btn"><i class="fas fa-right-from-bracket"></i> Logout</a>
      <button id="user-btn" class="icon-btn" aria-label="User"><i class="fas fa-user"></i></button>
    </div>
  </nav>
</header>

<main class="container">
  <h1 class="title">User Accounts</h1>

  <?php if($success): ?><div class="flash ok"><i class="fa-solid fa-circle-check"></i> <?= e($success) ?></div><?php endif; ?>
  <?php if($errors): ?><div class="flash err"><i class="fa-solid fa-triangle-exclamation"></i> <?= e(implode(' ', $errors)) ?></div><?php endif; ?>

  <section class="add-user card">
    <h2>Add New User</h2>
    <form method="post" class="add-form">
      <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
      <input type="hidden" name="formType" value="add">
      <input type="text"   name="name"  placeholder="Full Name" required>
      <input type="email"  name="email" placeholder="Email" required>
      <input type="text"   name="phone" placeholder="Phone (e.g. 017XXXXXXXX)" required>
      <select name="role" required>
        <option value="">Select Role</option>
        <option value="CUSTOMER">CUSTOMER</option>
        <option value="EMPLOYEE">EMPLOYEE</option>
      </select>
      <button type="submit" class="btn small">Add User</button>
      <p class="muted" style="margin-top:6px">New users get default password <code>123456</code>. Change it later.</p>
    </form>
  </section>

  <!-- List -->
  <section class="users-grid">
    <?php foreach($users as $u): ?>
      <article class="user-card card" data-role="<?= e($u['role']) ?>">
        <div class="avatar-wrap"><img src="../images/about-img-2.png" alt="<?= e($u['name'] ?? '') ?>" class="avatar"></div>
        <div class="user-main">
          <div class="head">
            <h3 class="name"><?= e($u['name'] ?? '') ?></h3>
            <span class="badge <?= strtolower($u['role']) ?>"><?= e($u['role'] ?? '') ?></span>
          </div>
          <div class="meta">
            <p><span class="label">User ID</span><span class="value"><?= e((string)$u['id']) ?></span></p>
            <p><span class="label">Joined</span><span class="value"><?= e($u['joined'] ?? '') ?></span></p>
            <p><span class="label">Email</span><span class="value"><?= e($u['email'] ?? '') ?></span></p>
            <p><span class="label">Phone</span><span class="value"><?= e($u['phone'] ?? '') ?></span></p>
          </div>
        </div>
        <div class="actions">
          <details>
            <summary class="btn ghost"><i class="fa-solid fa-pen"></i> Edit</summary>
            <form method="post" class="edit-form">
              <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
              <input type="hidden" name="formType" value="edit">
              <input type="hidden" name="id" value="<?= e((string)$u['id']) ?>">
              <input type="text"   name="name"  value="<?= e($u['name'] ?? '') ?>" required>
              <input type="email"  name="email" value="<?= e($u['email'] ?? '') ?>" required>
              <input type="text"   name="phone" value="<?= e($u['phone'] ?? '') ?>" required>
              <select name="role" required>
                <option value="CUSTOMER" <?= ($u['role']==='CUSTOMER')?'selected':''; ?>>CUSTOMER</option>
                <option value="EMPLOYEE" <?= ($u['role']==='EMPLOYEE')?'selected':''; ?>>EMPLOYEE</option>
              </select>
              <button type="submit" class="btn small">Save</button>
            </form>
          </details>
          <a href="?delete=<?= e((string)$u['id']) ?>" class="btn danger" onclick="return confirm('Delete this user?')">
            <i class="fa-solid fa-trash"></i> Delete
          </a>
        </div>
      </article>
    <?php endforeach; ?>

    <?php if (empty($users)): ?>
      <p class="muted">No users found.</p>
    <?php endif; ?>
  </section>
</main>

<script>

  const search=document.getElementById('search'), roleFilter=document.getElementById('roleFilter');
  function applyFilters(){
    const q=(search?.value||'').toLowerCase(), r=roleFilter?.value||'';
    document.querySelectorAll('.user-card').forEach(card=>{
      const text=card.innerText.toLowerCase(), okQ=!q||text.includes(q), okR=!r||card.dataset.role===r;
      card.style.display= okQ&&okR ? '' : 'none';
    });
  }
  search?.addEventListener('input',applyFilters); 
  roleFilter?.addEventListener('change',applyFilters);
</script>
</body>
</html>
