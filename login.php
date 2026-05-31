<?php
require_once 'db.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $stmt = $pdo->prepare("SELECT * FROM user WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    if ($user) {
        $_SESSION['user_id'] = $user['user_id'];
        header("Location: index.php");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><link rel="stylesheet" href="Assets/css/style.css"><title>Sign In</title></head>
<body>
<div style="max-width: 400px; margin: 100px auto; background: white; border:1px solid var(--border); padding: 30px; border-radius:var(--radius);">
    <h3 style="text-align:center; margin-bottom:20px;">Campus Sign In</h3>
    <form method="POST">
        <div class="form-group">
            <label>University Email</label>
            <input type="email" name="email" value="fatima.ali@gmail.com" required>
        </div>
        <button type="submit" class="action-btn" style="width:100%;">Authorize</button>
    </form>
</div>
</body>
</html>