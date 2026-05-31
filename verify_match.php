<?php
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$tracking_id = (int)($_GET['id'] ?? 0);
$board_type  = $_GET['type'] ?? 'lost';

if (isset($_POST['simulate_qr_scan'])) {
    if ($board_type === 'lost') {
        $update = $pdo->prepare("UPDATE lost_item SET status = 'found' WHERE lost_id = ?");
    } else {
        $update = $pdo->prepare("UPDATE found_items SET status = 'returned' WHERE item_id = ?");
    }
    $update->execute([$tracking_id]);
    header("Location: verify_match.php?id=$tracking_id&type=$board_type&status=success");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Ownership Match Verification Panel</title>
    <link rel="stylesheet" href="Assets/css/style.css?v=2">
    <style>
        /* Blends the white container box into your custom Prussian-Navy palette */
        .qr-box { 
            max-width: 500px; 
            margin: 80px auto; 
            background: var(--deep-navy); 
            border: 1px solid var(--border); 
            padding: 40px; 
            text-align: center; 
            border-radius: var(--radius); 
            box-shadow: var(--shadow); 
        }
        
        .qr-box h2 {
            color: var(--white);
            font-family: Georgia, serif;
            margin-bottom: 12px;
        }

        /* High-contrast QR matrix block */
        .qr-matrix { 
            width: 220px; 
            height: 220px; 
            margin: 25px auto; 
            background: var(--prussian-blue); 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            color: var(--periwinkle); 
            font-weight: bold; 
            font-family: monospace; 
            border: 4px solid var(--border); 
            border-radius: var(--radius);
            box-shadow: inset 0 0 15px rgba(0,0,0,0.5);
        }

        /* Success alerts styled cleanly with your custom emerald status colors */
        .success-alert {
            background: var(--emerald-bg); 
            color: var(--emerald); 
            padding: 20px; 
            border-radius: var(--radius); 
            font-weight: bold; 
            margin-bottom: 25px;
            border: 1px solid rgba(163, 255, 163, 0.2);
        }
        
        .qr-box p {
            color: var(--text-muted);
            font-size: 0.95rem;
            margin-bottom: 20px;
            line-height: 1.6;
        }
    </style>
</head>
<body>

<nav class="navbar">
    <a href="index.php" class="brand">🎓 Where Is It?</a>
    <div class="menu"><a href="index.php" style="color: var(--egyptian-blue); text-decoration: none; font-weight: 600;">← Return to Dashboard</a></div>
</nav>

<div class="main-container">
    <div class="qr-box">
        <?php if (($_GET['status'] ?? '') === 'success'): ?>
            <div class="success-alert">
                🎉 Secure Verification Handshake Complete!
            </div>
            <p>The central system database has updated successfully. The asset item pipeline status has officially transitioned into completed resolution fields.</p>
            <a href="index.php" class="action-btn" style="display: inline-block; text-decoration: none; width: 100%; text-align: center;">Go Home</a>
        <?php else: ?>
            <h2>Secure Verification</h2>
            <p>Present this cryptographic campus token data matrix at the security desk to authorize asset handover protocols.</p>
            
            <div class="qr-matrix">[ QR_TOKEN_#<?= $tracking_id ?> ]</div>
            
            <form method="POST" style="margin-top: 25px;">
                <button type="submit" name="simulate_qr_scan" class="action-btn btn-found-action" style="width: 100%; padding: 12px; font-size: 0.95rem;">
                    Simulate Security Guard QR Scan
                </button>
            </form>
        <?php endif; ?>
    </div>
</div>

</body>
</html>