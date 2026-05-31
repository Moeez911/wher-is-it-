<?php
require_once 'db.php';

$tracking_id = (int)($_GET['id'] ?? 0);
$board_type  = $_GET['type'] ?? 'lost';

if (!$tracking_id) {
    header("Location: index.php");
    exit;
}

// Global action route to delete the entire case report post
if (isset($_POST['delete_post'])) {
    if ($board_type === 'lost') {
        $del = $pdo->prepare("DELETE FROM lost_item WHERE lost_id = ?");
    } else {
        $del = $pdo->prepare("DELETE FROM found_items WHERE item_id = ?");
    }
    $del->execute([$tracking_id]);
    header("Location: index.php?info=deleted");
    exit;
}

// Global action route to delete single comments inside threads
if (isset($_POST['delete_comment_id'])) {
    $del_com = $pdo->prepare("DELETE FROM feedback WHERE feedback_id = ?");
    $del_com->execute([(int)$_POST['delete_comment_id']]);
    header("Location: item_detail.php?id=$tracking_id&type=$board_type");
    exit;
}

// Fetch active target details context wrapper arrays
if ($board_type === 'lost') {
    $stmt = $pdo->prepare("
        SELECT li.lost_id as id, li.title, li.description, li.status, li.created_at, li.user_id,
               c.category_name, u.first_name, u.last_name, u.email
        FROM lost_item li
        JOIN category c ON li.category_id = c.category_id
        JOIN user u ON li.user_id = u.user_id
        WHERE li.lost_id = ?");
} else {
    $stmt = $pdo->prepare("
        SELECT fi.item_id as id, fi.title, fi.description, fi.status, fi.created_at, fi.user_id,
               fi.photo, c.category_name, u.first_name, u.last_name, u.email
        FROM found_items fi
        JOIN category c ON fi.category_id = c.category_id
        JOIN user u ON fi.user_id = u.user_id
        WHERE fi.item_id = ?");
}
$stmt->execute([$tracking_id]);
$item = $stmt->fetch();

if (!$item) {
    die("Error: Target record context index could not be located inside database registers.");
}

// Add new comments attached cleanly using exact matching context keys
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['post_comment'])) {
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit;
    }
    $comment_text = trim($_POST['comment_body']);
    if ($comment_text !== '') {
        $stmt = $pdo->prepare("INSERT INTO feedback (feedback_id, rating, comment, user_id, claim_id) VALUES (?, 5, ?, ?, ?)");
        $stmt->execute([rand(90000, 99999), $comment_text, $_SESSION['user_id'], $tracking_id]);
        header("Location: item_detail.php?id=$tracking_id&type=$board_type");
        exit;
    }
}

// Fetch comments matching EXACTLY this unique property record tracking key context
$comments = $pdo->prepare("
    SELECT f.*, u.first_name, u.last_name 
    FROM feedback f 
    JOIN user u ON f.user_id = u.user_id 
    WHERE f.claim_id = ?
    ORDER BY f.created_at DESC");
$comments->execute([$tracking_id]);
$item_comments = $comments->fetchAll();

$is_owner = isset($_SESSION['user_id']) && $_SESSION['user_id'] == $item['user_id'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($item['title']) ?> — Archive File</title>
    <link rel="stylesheet" href="Assets/css/style.css?v=3">
    <style>
        .btn-danger { background: var(--crimson) !important; color: white !important; padding: 8px 14px; border-radius: var(--radius); border:none; font-weight:bold; cursor:pointer; }
        .btn-danger:hover { background: #FF3333 !important; }
        .comment-header-flex { display: flex; justify-content: space-between; align-items: center; width: 100%; }
        .comment-del-link { background: none; border: none; color: var(--crimson); font-size: 0.8rem; cursor: pointer; text-decoration: underline; }
    </style>
</head>
<body>

<nav class="navbar">
    <a href="index.php" class="brand">🎓 Where Is It?</a>
    <div class="menu"><a href="index.php" style="color:var(--primary); text-decoration:none;">← Return to Portal Feed</a></div>
</nav>

<main class="main-container wiki-layout">
    <div class="wiki-main">
        <div style="display:flex; justify-content:space-between; align-items:center; border-bottom: 1px solid var(--border); padding-bottom: 10px; margin-bottom: 15px;">
            <h1><?= htmlspecialchars($item['title']) ?></h1>
            
            <?php if ($is_owner): ?>
                <form method="POST" onsubmit="return confirm('Confirm permanent asset erasure? This operations cascade cannot be reversed.');">
                    <button type="submit" name="delete_post" class="btn-danger">🗑️ Remove Listing</button>
                </form>
            <?php endif; ?>
        </div>
        
        <p style="font-size:1.1rem; margin-bottom:30px; color:#FFF; line-height:1.8;">
            <?= nl2br(htmlspecialchars($item['description'])) ?>
        </p>

        <div class="comment-box">
            <h3>📍 Target Sighting Broadcast Updates (<?= count($item_comments) ?>)</h3>
            <div style="margin-top:15px;">
                <?php if (empty($item_comments)): ?>
                    <p style="color:var(--text-muted); font-style:italic; padding:10px 0;">No sighting comments recorded on this specific registry trace yet.</p>
                <?php endif; ?>
                <?php foreach ($item_comments as $com): ?>
                    <div class="comment">
                        <div class="comment-header-flex">
                            <div class="meta"><?= htmlspecialchars($com['first_name'] . ' ' . $com['last_name']) ?> — <span style="font-weight:normal; color:gray;"><?= $com['created_at'] ?></span></div>
                            
                            <?php if ($is_owner || (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $com['user_id'])): ?>
                                <form method="POST" style="display:inline;" onsubmit="return confirm('Purge selected comment string?');">
                                    <input type="hidden" name="delete_comment_id" value="<?= $com['feedback_id'] ?>">
                                    <button type="submit" class="comment-del-link">❌ Delete</button>
                                </form>
                            <?php endif; ?>
                        </div>
                        <div style="margin-top:4px;"><?= htmlspecialchars($com['comment']) ?></div>
                    </div>
                <?php endforeach; ?>
            </div>

            <?php if (isset($_SESSION['user_id'])): ?>
                <form method="POST" style="margin-top:20px;">
                    <div class="form-group">
                        <textarea name="comment_body" rows="3" placeholder="Type verified campus location coordinates or sighting timestamps here..." required></textarea>
                    </div>
                    <button type="submit" name="post_comment" class="action-btn">Submit Sighting Log Update</button>
                </form>
            <?php else: ?>
                <p style="text-align:center; padding:10px; background:var(--midnight-blue); border-radius:4px; font-size:0.9rem; border:1px solid var(--border);">
                    <a href="login.php" style="font-weight:bold; color:var(--periwinkle);">Log In with student account</a> to report campus coordinates.
                </p>
            <?php endif; ?>
        </div>
    </div>

    <div class="wiki-sidebar">
        <div class="wiki-infobox">
            <h4>Case Registry Metadata</h4>
            <div style="width:100%; height:180px; background:rgba(35, 35, 102, 0.3); border:1px solid var(--border); display:flex; align-items:center; justify-content:center; color:var(--text-muted); margin-bottom:12px; font-weight:bold; overflow:hidden; border-radius:4px;">
                <?php if (!empty($item['photo']) && file_exists($item['photo'])): ?>
                    <img src="<?= htmlspecialchars($item['photo']) ?>" style="width:100%; height:100%; object-fit:cover;">
                <?php else: ?>
                    📦 REFERENCE IMAGE
                <?php endif; ?>
            </div>
            
            <table class="wiki-table">
                <tr><td>Reference Track</td><td>#<?= $item['id'] ?></td></tr>
                <tr><td>Pipeline Sector</td><td style="text-transform:uppercase; font-weight:bold; color:var(--periwinkle);"><?= $board_type ?></td></tr>
                <tr><td>Classification</td><td><?= htmlspecialchars($item['category_name']) ?></td></tr>
                <tr><td>Registration Date</td><td><?= date('M d, Y', strtotime($item['created_at'])) ?></td></tr>
                <tr><td>Current Status</td><td style="font-weight:bold; color:var(--periwinkle);"><?= htmlspecialchars($item['status']) ?></td></tr>
                <tr><td>Logged By</td><td><?= htmlspecialchars($item['first_name'] . ' ' . $item['last_name']) ?></td></tr>
            </table>
        </div>

        <div style="margin-top:20px; background:var(--deep-navy); padding:20px; border-radius:var(--radius); color:white; text-align:center; border:1px solid var(--border);">
            <h4 style="color:var(--periwinkle); margin-bottom:10px; border:none; background:none;">Ownership Verification</h4>
            <p style="font-size:0.8rem; color:var(--text-muted); margin-bottom:12px;">Scan the active desk ledger token code to immediately claim property and clear from live system feeds.</p>
            <a href="verify_match.php?id=<?= $item['id'] ?>&type=<?= $board_type ?>" class="action-btn" style="display:block; text-decoration:none; text-align:center;">Verify via QR Generator</a>
        </div>
    </div>
</main>

</body>
</html>