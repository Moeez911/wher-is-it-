<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'db.php';

$error_msg = null;
$success_msg = null;
$mode = isset($_GET['mode']) ? $_GET['mode'] : 'login';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // --- HANDLE LOGIN SUBMISSION ---
    if (isset($_POST['submit_login'])) {
        $email = trim($_POST['email']);
        $password = $_POST['password'];
        
        try {
            $stmt = $pdo->prepare("SELECT * FROM user WHERE email = ? LIMIT 1");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if ($user && ($password === $user['password_hash'] || password_verify($password, $user['password_hash']))) {
                $_SESSION['user_id'] = $user['user_id'];
                header("Location: index.php");
                exit;
            } else {
                $error_msg = "Invalid credentials. Incorrect email or password.";
            }
        } catch (PDOException $e) {
            $error_msg = "Database Error: " . htmlspecialchars($e->getMessage());
        }
    }
    
    // --- HANDLE SIGN UP SUBMISSION WITH FIXED PASS-THROUGH DIRECT LOGIN ---
    if (isset($_POST['submit_signup'])) {
        $username = trim($_POST['username']);
        $phone = trim($_POST['phone']);
        $email = trim($_POST['email']);
        $password = $_POST['password'];
        
        try {
            $check_stmt = $pdo->prepare("SELECT * FROM user WHERE email = ? LIMIT 1");
            $check_stmt->execute([$email]);
            
            if ($check_stmt->fetch()) {
                $error_msg = "This email is already registered. Try logging in.";
            } else {
                // FIXED: Processes the full name natively without throwing hardcoded strings into your system
                $name_parts = explode(' ', $username, 2);
                $first_name = ucwords($name_parts[0]);
                $last_name = isset($name_parts[1]) ? ucwords($name_parts[1]) : '';
                
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                
                $id_stmt = $pdo->query("SELECT MAX(user_id) AS max_id FROM user");
                $id_result = $id_stmt->fetch();
                $next_id = ($id_result && $id_result['max_id'] !== null) ? (int)$id_result['max_id'] + 1 : 1016;
                
                $insert_stmt = $pdo->prepare("INSERT INTO user (user_id, first_name, last_name, email, password_hash) VALUES (?, ?, ?, ?, ?)");
                if ($insert_stmt->execute([$next_id, $first_name, $last_name, $email, $password_hash])) {
                    
                    if (!empty($phone)) {
                        $phone_stmt = $pdo->prepare("INSERT INTO user_phone_number (phone_number, user_id) VALUES (?, ?)");
                        $phone_stmt->execute([$phone, $next_id]);
                    }
                    
                    // FIXED: Instantly logs user session credentials in rather than dropping back to login view prompts
                    $_SESSION['user_id'] = $next_id;
                    header("Location: index.php");
                    exit;
                } else {
                    $error_msg = "Failed to compile your registration profile data.";
                }
            }
        } catch (PDOException $e) {
            $error_msg = "Registration Error: " . htmlspecialchars($e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In - Where Is It?</title>
    <link rel="stylesheet" href="Assets/css/style.css?v=95">
    <style>
        .login-page-custom { background-color: #181c4b !important; display: flex !important; justify-content: center !important; align-items: center !important; min-height: 100vh !important; font-family: system-ui, sans-serif !important; margin: 0 !important; }
        .auth-card-glass { width: 100% !important; max-width: 400px !important; background: #101235 !important; border: 1px solid rgba(255, 255, 255, 0.12) !important; border-radius: 12px !important; padding: 35px !important; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5) !important; box-sizing: border-box !important; }
        .auth-tabs { display: flex !important; justify-content: center !important; gap: 25px !important; margin-bottom: 30px !important; border-bottom: 1px solid rgba(255, 255, 255, 0.12) !important; padding-bottom: 12px !important; }
        .auth-tab-link { color: #a2b4cc !important; text-decoration: none !important; font-weight: 700 !important; font-size: 1.1rem !important; transition: all 0.2s; }
        .auth-tab-link.active { color: #ffffff !important; border-bottom: 2px solid #7c4dff !important; padding-bottom: 10px !important; }
        .custom-form-group { margin-bottom: 18px !important; display: flex !important; flex-direction: column !important; }
        .custom-form-label { color: #a2b4cc !important; font-size: 0.85rem !important; margin-bottom: 8px !important; font-weight: 600 !important; text-align: left !important; }
        .custom-form-input { width: 100% !important; padding: 12px 16px !important; background: rgba(255, 255, 255, 0.05) !important; border: 1px solid rgba(255, 255, 255, 0.12) !important; border-radius: 8px !important; color: #ffffff !important; font-size: 0.95rem !important; box-sizing: border-box !important; outline: none; }
        .custom-form-input:focus { border-color: #7c4dff !important; background: rgba(255, 255, 255, 0.08) !important; }
        .custom-action-btn { width: 100% !important; padding: 14px !important; background: #7c4dff !important; color: #ffffff !important; border: none !important; border-radius: 8px !important; font-size: 1rem !important; font-weight: 700 !important; cursor: pointer !important; transition: all 0.2s !important; margin-top: 10px !important; }
        .custom-action-btn:hover { background: #651fff !important; }
    </style>
</head>
<body class="login-page-custom">
    <div class="auth-card-glass">
        <div class="auth-tabs">
            <a href="login.php?mode=login" class="auth-tab-link <?= $mode === 'login' ? 'active' : '' ?>">Sign In</a>
            <a href="login.php?mode=signup" class="auth-tab-link <?= $mode === 'signup' ? 'active' : '' ?>">Sign Up</a>
        </div>
        <?php if ($error_msg): ?>
            <div style="color: #ff1744; background: rgba(255, 23, 68, 0.1); padding: 12px; border-radius: 8px; margin-bottom: 20px; font-size: 0.85rem; border: 1px solid rgba(255, 23, 68, 0.2); text-align: center; font-weight: 600;">⚠️ <?= $error_msg ?></div>
        <?php endif; ?>
        <?php if ($mode === 'login'): ?>
            <form method="POST" action="login.php?mode=login">
                <div class="custom-form-group">
                    <label class="custom-form-label">University Email</label>
                    <input type="email" name="email" class="custom-form-input" placeholder="Enter your registered email" required autocomplete="off">
                </div>
                <div class="custom-form-group">
                    <label class="custom-form-label">Password</label>
                    <input type="password" name="password" class="custom-form-input" placeholder="••••••••" required>
                </div>
                <button type="submit" name="submit_login" class="custom-action-btn">Authorize</button>
            </form>
        <?php else: ?>
            <form method="POST" action="login.php?mode=signup">
                <div class="custom-form-group">
                    <label class="custom-form-label">Full Username</label>
                    <input type="text" name="username" class="custom-form-input" placeholder="e.g., Fatima Ali" required autocomplete="off">
                </div>
                <div class="custom-form-group">
                    <label class="custom-form-label">Telephone Number</label>
                    <input type="tel" name="phone" class="custom-form-input" placeholder="e.g., 03001234567" required autocomplete="off">
                </div>
                <div class="custom-form-group">
                    <label class="custom-form-label">University Email</label>
                    <input type="email" name="email" class="custom-form-input" placeholder="name@domain.com" required autocomplete="off">
                </div>
                <div class="custom-form-group">
                    <label class="custom-form-label">New Password</label>
                    <input type="password" name="password" class="custom-form-input" placeholder="Create secure password" required>
                </div>
                <button type="submit" name="submit_signup" class="custom-action-btn">Create Account</button>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>