<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'db.php';

$item_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$type = isset($_GET['type']) ? $_GET['type'] : 'found';

// Handles dynamic logging variations into your status log table architecture natively
$report_success = null;
$sighting_success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_sighting'])) {
    $coordinates = trim($_POST['sighting_coordinates']);
    if (!empty($coordinates) && isset($_SESSION['user_id'])) {
        try {
            $id_stmt = $pdo->query("SELECT MAX(log_id) AS max_id FROM status_log");
            $id_result = $id_stmt->fetch();
            $next_log_id = ($id_result && $id_result['max_id'] !== null) ? (int)$id_result['max_id'] + 1 : 5513;

            $insert_log = $pdo->prepare("INSERT INTO status_log (log_id, old_status, new_status, changed_by, item_id, user_id) VALUES (?, 'sighting', ?, ?, ?, ?)");
            if ($insert_log->execute([$next_log_id, $coordinates, $_SESSION['user_id'], $item_id, $_SESSION['user_id']])) {
                $sighting_success = "Sighting trace coordinate successfully broadcasted to campus network logs.";
            }
        } catch (PDOException $e) {
            echo "<script>alert('Log Engine Error: " . addslashes($e->getMessage()) . "');</script>";
        }
    }
}

$item = null;
if ($type === 'lost') {
    $stmt = $pdo->prepare("SELECT li.*, c.category_name, u.first_name, u.last_name, p.phone_number 
                           FROM lost_item li 
                           JOIN category c ON li.category_id = c.category_id 
                           JOIN user u ON li.user_id = u.user_id 
                           LEFT JOIN user_phone_number p ON u.user_id = p.user_id
                           WHERE li.lost_id = ? LIMIT 1");
    $stmt->execute([$item_id]);
    $item = $stmt->fetch();
} else {
    $stmt = $pdo->prepare("SELECT fi.*, c.category_name, u.first_name, u.last_name, p.phone_number 
                           FROM found_items fi 
                           JOIN category c ON fi.category_id = c.category_id 
                           JOIN user u ON fi.user_id = u.user_id 
                           LEFT JOIN user_phone_number p ON u.user_id = p.user_id
                           WHERE fi.item_id = ? LIMIT 1");
    $stmt->execute([$item_id]);
    $item = $stmt->fetch();
}

if (!$item) {
    header("Location: index.php");
    exit;
}

$logs_stmt = $pdo->prepare("SELECT l.*, u.first_name, u.last_name 
                            FROM status_log l 
                            JOIN user u ON l.user_id = u.user_id 
                            WHERE l.item_id = ? AND l.old_status = 'sighting' 
                            ORDER BY l.log_id DESC");
$logs_stmt->execute([$item_id]);
$saved_sightings = $logs_stmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_listing'])) {
    if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $item['user_id']) {
        if ($type === 'lost') {
            $del_stmt = $pdo->prepare("DELETE FROM lost_item WHERE lost_id = ? AND user_id = ?");
        } else {
            $del_stmt = $pdo->prepare("DELETE FROM found_items WHERE item_id = ? AND user_id = ?");
        }
        $del_stmt->execute([$item_id, $_SESSION['user_id']]);
        header("Location: index.php?success=deleted");
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_report'])) {
    $report_reason = trim($_POST['report_reason']);
    $reporter_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
    
    if (!empty($report_reason)) {
        try {
            $id_stmt = $pdo->query("SELECT MAX(report_id) AS max_id FROM report");
            $id_result = $id_stmt->fetch();
            $next_report_id = ($id_result && $id_result['max_id'] !== null) ? (int)$id_result['max_id'] + 1 : 6613;
            
            $report_stmt = $pdo->prepare("INSERT INTO report (report_id, reason, status, user_id, item_id) VALUES (?, ?, 'open', ?, ?)");
            if ($report_stmt->execute([$next_report_id, $report_reason, $reporter_id, $item_id])) {
                $report_success = "Listing flagged successfully. Campus safety boards will audit the post.";
            }
        } catch (PDOException $e) {
            echo "<script>alert('SQL Error: " . addslashes($e->getMessage()) . "');</script>";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($item['title']) ?> — Specification Ledger</title>
    <link rel="stylesheet" href="Assets/css/style.css?v=110">
    <style>
        .details-wrapper { display: grid; grid-template-columns: 2fr 1fr; gap: 30px; margin-top: 30px; }
        .meta-card { background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius); padding: 25px; box-shadow: 0 4px 20px rgba(0,0,0,0.2); }
        .meta-row { display: flex; justify-content: space-between; padding: 12px 0; border-bottom: 1px solid var(--border); font-size: 0.9rem; }
        .meta-row:last-child { border: none; }
        .meta-label { color: var(--text-secondary); }
        .meta-value { font-weight: 600; color: var(--text-primary); text-transform: capitalize; }
        .log-box { background: rgba(255, 255, 255, 0.02); border: 1px solid var(--border); border-radius: var(--radius); padding: 25px; margin-top: 25px; }
        .live-update-row { background: rgba(255,255,255,0.03); border: 1px solid var(--border); border-radius: 8px; padding: 14px; margin-bottom: 12px; }
        .live-update-meta { display: flex; justify-content: space-between; font-size: 0.8rem; color: var(--text-secondary); margin-bottom: 6px; font-weight: bold; }
        .btn-report { background: rgba(255, 23, 68, 0.1); border: 1px solid var(--danger); color: var(--danger); padding: 8px 16px; border-radius: 6px; font-weight: bold; cursor: pointer; font-size: 0.85rem; transition: all 0.2s; }
        .btn-report:hover { background: var(--danger); color: white; }
        .btn-delete { background: rgba(255, 23, 68, 0.15); border: 1px solid var(--danger); color: var(--danger); padding: 8px 16px; border-radius: 6px; font-weight: bold; cursor: pointer; font-size: 0.85rem; transition: all 0.2s; }
        .btn-delete:hover { background: var(--danger); color: white; }
        .modal-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(11, 14, 20, 0.85); z-index: 1500; backdrop-filter: blur(6px); align-items: center; justify-content: center; }
        /* FIXED: Set up background metrics for modal forms cards overlay blocks explicitly */
        .glass-card-modal { padding: 30px; width: 100%; max-width: 450px; position: relative; background: #101235 !important; border: 1px solid rgba(255, 255, 255, 0.12) !important; border-radius: 12px !important; box-shadow: 0 10px 40px rgba(0,0,0,0.5); }
    </style>
</head>
<body>

<nav class="navbar">
    <a href="index.php" class="brand-link">🎓 Where Is <span>It?</span></a>
    <div class="nav-links">
        <a href="index.php" style="background: rgba(255,255,255,0.05); padding: 8px 16px; border-radius: 6px; font-size: 0.9rem; text-decoration: none;">← Return to Portal Feed</a>
    </div>
</nav>

<?php if ($report_success): ?>
    <div style="position: fixed; top: 20px; left: 50%; transform: translateX(-50%); background: rgba(0, 230, 118, 0.2); border: 1px solid #00e676; color: #00e676; padding: 12px 24px; border-radius: 8px; font-weight: bold; z-index: 2000; backdrop-filter: blur(4px);">✅ <?= $report_success ?></div>
<?php endif; ?>

<?php if ($sighting_success): ?>
    <div style="position: fixed; top: 20px; left: 50%; transform: translateX(-50%); background: rgba(0, 230, 118, 0.2); border: 1px solid #00e676; color: #00e676; padding: 12px 24px; border-radius: 8px; font-weight: bold; z-index: 2000; backdrop-filter: blur(4px);">✅ <?= $sighting_success ?></div>
<?php endif; ?>

<main class="main-container">
    
    <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 20px; border-bottom: 1px solid var(--border); padding-bottom: 20px;">
        <h1 style="font-size: 2.5rem; font-weight: 800; color: var(--text-primary); margin: 0; text-transform: capitalize; letter-spacing: -0.02em;"><?= htmlspecialchars($item['title']) ?></h1>
        
        <?php if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $item['user_id']): ?>
            <form method="POST" action="item_detail.php?id=<?= $item_id ?>&type=<?= $type ?>" onsubmit="return confirm('Are you sure you want to permanently delete your listing?');" style="margin: 0;">
                <button type="submit" name="delete_listing" class="btn-delete">🗑️ Remove Listing</button>
            </form>
        <?php else: ?>
            <button onclick="openReportModal()" class="btn-report">⚠️ Report Listing</button>
        <?php endif; ?>
    </div>

    <div class="details-wrapper">
        
        <div>
            <p style="font-size: 1.1rem; line-height: 1.6; color: var(--text-primary); margin-bottom: 30px; opacity: 0.95;"><?= htmlspecialchars($item['description']) ?></p>
            
            <div class="log-box">
                <h3 style="color: var(--text-primary); margin-top: 0; margin-bottom: 15px; font-size: 1.1rem; font-weight: 700;">📍 Target Sighting Broadcast Updates (<?= count($saved_sightings) ?>)</h3>
                
                <div style="max-height: 300px; overflow-y: auto; margin-bottom: 20px;">
                    <?php if (empty($saved_sightings)): ?>
                        <p style="color: var(--text-secondary); font-size: 0.9rem; margin-bottom: 20px;">No sighting comments recorded on this specific registry trace yet.</p>
                    <?php else: ?>
                        <?php foreach ($saved_sightings as $log): ?>
                            <div class="live-update-row">
                                <div class="live-update-meta">
                                    <span>👤 Logged by: <?= htmlspecialchars($log['first_name'] . ' ' . $log['last_name']) ?></span>
                                    <span>⏱️ <?= date('M d, h:i A', strtotime($log['changed_at'])) ?></span>
                                </div>
                                <div style="color: white; font-size: 0.95rem; word-wrap: break-word; text-align: left;"><?= htmlspecialchars($log['new_status']) ?></div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                
                <?php if (isset($_SESSION['user_id'])): ?>
                    <form method="POST" action="item_detail.php?id=<?= $item_id ?>&type=<?= $type ?>">
                        <textarea name="sighting_coordinates" class="form-input" rows="3" placeholder="Type verified campus location coordinates or sighting timestamps here..." style="width: 100%; box-sizing: border-box; background: rgba(255,255,255,0.05); color: white; border-radius: 8px; padding: 12px; resize: none; border: 1px solid var(--border);" required></textarea>
                        <button type="submit" name="submit_sighting" class="action-btn" style="margin-top: 12px; padding: 10px 20px; font-size: 0.9rem; font-weight: 600; width: auto;">Submit Sighting Log Update</button>
                    </form>
                <?php else: ?>
                    <div style="background: rgba(16, 18, 53, 0.4); border: 1px solid var(--border); padding: 14px; border-radius: 8px; text-align: center; font-size: 0.9rem; color: var(--text-secondary);">
                        <a href="login.php" style="color: var(--text-primary); font-weight: bold; text-decoration: underline;">Log In with student account</a> to report campus coordinates.
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div>
            <div class="meta-card">
                <h3 style="color: var(--text-primary); margin-top: 0; border-bottom: 1px solid var(--border); padding-bottom: 12px; font-size: 1.1rem; font-weight: 700; text-align: center;">Case Registry Metadata</h3>
                
                <div style="width: 100%; height: 180px; background: rgba(0,0,0,0.2); border-radius: var(--radius); display: flex; align-items: center; justify-content: center; color: var(--text-secondary); font-weight: bold; font-size: 0.85rem; margin-top: 15px; margin-bottom: 20px; border: 1px solid var(--border); overflow: hidden;">
                    <?php if ($type === 'found' && !empty($item['photo'])): ?>
                        <img src="<?= htmlspecialchars($item['photo']) ?>" style="width: 100%; height: 100%; object-fit: cover;">
                    <?php else: ?>
                        📦 REFERENCE IMAGE ARCHIVE
                    <?php endif; ?>
                </div>

                <div class="meta-row"><span class="meta-label">Reference Track</span><span class="meta-value">#<?= $type === 'found' ? $item['item_id'] : $item['lost_id'] ?></span></div>
                <div class="meta-row"><span class="meta-label">Pipeline Sector</span><span class="meta-value" style="color: var(--periwinkle); font-weight: bold;"><?= $type === 'found' ? 'FOUND' : 'LOST' ?></span></div>
                <div class="meta-row"><span class="meta-label">Classification</span><span class="meta-value"><?= htmlspecialchars($item['category_name']) ?></span></div>
                <div class="meta-row"><span class="meta-label">Registration Date</span><span class="meta-value"><?= date('M d, Y', strtotime($item['date_found'] ?? $item['date_lost'] ?? 'now')) ?></span></div>
                <div class="meta-row"><span class="meta-label">Current Status</span><span class="meta-value" style="color: var(--success); font-weight: bold;"><?= htmlspecialchars($item['status']) ?></span></div>
                <div class="meta-row"><span class="meta-label">Logged By</span><span class="meta-value" style="color: var(--text-primary); font-weight: bold;"><?= htmlspecialchars($item['first_name'] . (!empty($item['last_name']) ? ' ' . $item['last_name'] : '')) ?></span></div>
                <div class="meta-row"><span class="meta-label">Contact Digits</span><span class="meta-value" style="color: var(--periwinkle); font-weight: bold;"><?= !empty($item['phone_number']) ? htmlspecialchars($item['phone_number']) : 'No phone linked' ?></span></div>
            </div>

            <div class="meta-card" style="margin-top: 20px; text-align: center;">
                <h4 style="color: var(--text-primary); margin-top: 0; font-size: 1rem; font-weight: 700;">Ownership Verification</h4>
                <p style="font-size: 0.8rem; color: var(--text-secondary); line-height: 1.5; margin-bottom: 15px;">Scan the active desk ledger token code to immediately claim property and clear from live system feeds.</p>
                <a href="verify_match.php?id=<?= $item_id ?>&type=<?= $type ?>" class="action-btn" style="display: block; text-align: center; text-decoration: none; width: 100%; padding: 12px; font-size: 0.9rem; background: var(--accent); color: white; font-weight: bold; border-radius: 8px; box-sizing: border-box; transition: background 0.2s;">Verify via QR Generator</a>
            </div>
        </div>

    </div>
</main>

<div id="reportModal" class="modal-overlay">
    <div class="glass-card-modal">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 1px solid var(--border); padding-bottom: 12px;">
            <h3 style="color: var(--text-primary); margin: 0; font-size: 1.25rem; font-weight: 700;">Flag Listing Audit</h3>
            <button onclick="closeReportModal()" style="background: none; border: none; color: var(--danger); font-size: 1.5rem; cursor: pointer;">&times;</button>
        </div>
        <form method="POST" action="">
            <div class="form-group">
                <label class="form-label" style="margin-bottom: 8px; display: block; color: var(--text-secondary);">Reason for reporting this post:</label>
                <textarea name="report_reason" rows="4" class="form-input" style="width: 100%; box-sizing: border-box; resize: none; padding: 12px; background: rgba(255,255,255,0.05); color: white; border: 1px solid var(--border); border-radius: 8px;" placeholder="Please clarify reason..." required></textarea>
            </div>
            <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 25px;">
                <button type="button" onclick="closeReportModal()" class="action-btn" style="background: rgba(255,255,255,0.05); border: 1px solid var(--border); color: var(--text-secondary); width: auto; padding: 10px 18px;">Cancel</button>
                <button type="submit" name="submit_report" class="action-btn" style="background: var(--danger); color: white; border: none; width: auto; padding: 10px 22px; font-weight: bold;">Submit Report</button>
            </div>
        </form>
    </div>
</div>

<script>
function openReportModal() { document.getElementById('reportModal').style.display = 'flex'; }
function closeReportModal() { document.getElementById('reportModal').style.display = 'none'; }
</script>
</body>
</html>