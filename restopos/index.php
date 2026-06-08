<?php
require_once 'includes/config.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($username && $password) {
        try {
            $db = getDB();
            $stmt = $db->prepare("SELECT * FROM users WHERE username = ? AND active = 1");
            $stmt->execute([$username]);
            $user = $stmt->fetch();

            // Plain text password comparison (as requested)
            if ($user && $user['password'] === $password) {
                $_SESSION['user_id']   = $user['id'];
                $_SESSION['user']      = ['id'=>$user['id'],'name'=>$user['name'],'role'=>$user['role'],'username'=>$user['username']];
                header('Location: modules/dashboard.php');
                exit;
            } else {
                $error = 'Invalid username or password.';
            }
        } catch (Exception $e) {
            $error = 'Database error. Please check your connection.';
        }
    } else {
        $error = 'Please enter username and password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Login — RestoPOS Sri Lanka</title>
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="login-page">
  <div class="login-box">
    <div class="login-logo">
      <div class="logo-icon">🍛</div>
      <div class="logo-name">RestoPOS</div>
      <div class="logo-sub">Restaurant Management System — Sri Lanka</div>
    </div>

    <h2>Sign In</h2>

    <?php if ($error): ?>
      <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="">
      <div class="form-group mb-16">
        <label class="form-label">Username</label>
        <input type="text" name="username" class="form-control" placeholder="Enter username"
               value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required autocomplete="username">
      </div>
      <div class="form-group mb-20">
        <label class="form-label">Password</label>
        <input type="password" name="password" class="form-control" placeholder="Enter password" required autocomplete="current-password">
      </div>
      <button type="submit" class="btn btn-block btn-lg">Sign In →</button>
    </form>


  </div>
</div>
</body>
</html>
