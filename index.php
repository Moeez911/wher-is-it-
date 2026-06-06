<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'db.php';

// Form actions submission handling blocks
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_submit'])) {
    if (!isset($_SESSION['user_id'])) {
        header("Location: index.php?error=auth");
        exit;
    }
    
    $title = trim($_POST['title']);
    $desc  = trim($_POST['description']);
    $cat   = (int)$_POST['category_id'];
    $board = $_POST['board_type']; 
    $user  = $_SESSION['user_id'];
    
    $photo_path = '';
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $target_dir = 'uploads/items/';
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        $file_name = time() . '_' . basename($_FILES['photo']['name']);
        $photo_path = $target_dir . $file_name;
        if (!move_uploaded_file($_FILES['photo']['tmp_name'], $photo_path)) {
            $photo_path = '';
        }
    }
    
    $new_id = rand(15000, 99999);
    
    try {
        if ($board === 'lost') {
            $stmt = $pdo->prepare("INSERT INTO lost_item (lost_id, title, description, date_lost, status, days_missing, user_id, category_id) VALUES (?, ?, ?, CURDATE(), 'searching', 0, ?, ?)");
            $stmt->execute([$new_id, $title, $desc, $user, $cat]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO found_items (item_id, title, description, photo, date_found, status, days_listed, user_id, category_id, location_id) VALUES (?, ?, ?, ?, CURDATE(), 'pending', 0, ?, ?, 4001)");
            $stmt->execute([$new_id, $title, $desc, $photo_path, $user, $cat]);
        }
        header("Location: index.php?success=posted");
        exit;
    } catch (PDOException $e) {
        echo "<script>alert('Database Write Error: " . addslashes($e->getMessage()) . "');</script>";
    }
}

$categories = $pdo->query("SELECT * FROM category")->fetchAll();
$search_cat = isset($_GET['cat_filter']) ? (int)$_GET['cat_filter'] : 0;
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';

// FIXED search evaluation criteria: Uses LOWER() comparison parameters to prevent case mismatch dead drops
$found_sql = "SELECT fi.*, c.category_name FROM found_items fi JOIN category c ON fi.category_id = c.category_id WHERE fi.status = 'pending'";
if ($search_cat) $found_sql .= " AND fi.category_id = $search_cat";
if ($search_query) $found_sql .= " AND (LOWER(fi.title) LIKE " . $pdo->quote('%'.strtolower($search_query).'%') . " OR LOWER(fi.description) LIKE " . $pdo->quote('%'.strtolower($search_query).'%') . ")";
$found_sql .= " ORDER BY fi.item_id DESC";
$found_items = $pdo->query($found_sql)->fetchAll();

$lost_sql = "SELECT li.*, c.category_name FROM lost_item li JOIN category c ON li.category_id = c.category_id WHERE li.status = 'searching'";
if ($search_cat) $lost_sql .= " AND li.category_id = $search_cat";
if ($search_query) $lost_sql .= " AND (LOWER(li.title) LIKE " . $pdo->quote('%'.strtolower($search_query).'%') . " OR LOWER(li.description) LIKE " . $pdo->quote('%'.strtolower($search_query).'%') . ")";
$lost_sql .= " ORDER BY li.lost_id DESC";
$lost_items = $pdo->query($lost_sql)->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Where Is It? — Campus Lost & Found Portal</title>
    <link rel="stylesheet" href="Assets/css/style.css">
    <style>
        .search-wrapper { position: relative; margin-bottom: 30px; width: 100%; }
        .search-bar { width: 100%; padding: 16px 24px; font-size: 1rem; border: 2px solid var(--border); border-radius: var(--radius); outline: none; background: rgba(255,255,255,0.05); color: white; transition: all 0.2s; }
        .search-bar:focus { border-color: var(--accent); background: rgba(255,255,255,0.08); }
        .search-dropdown { display: none; position: absolute; top: 100%; left: 0; right: 0; background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius); z-index: 500; max-height: 250px; overflow-y: auto; }
        .search-dropdown a { display: block; padding: 12px 24px; text-decoration: none; color: white; border-bottom: 1px solid var(--bg-main); }
        .search-dropdown a:hover { background: rgba(255,255,255,0.05); color: var(--accent); }
        .clickable-card { text-decoration: none; color: inherit; display: flex; flex-direction: column; }
        select.form-input option { background-color: #101235 !important; color: #ffffff !important; padding: 10px; }
        
        /* Local Override Fixes for Drawers/Forms */
        .drawer { 
            position: fixed; 
            top: 0; 
            right: -450px; 
            width: 450px; 
            height: 100%; 
            background: #0f1130; 
            box-shadow: -5px 0 15px rgba(0,0,0,0.5); 
            transition: right 0.3s ease; 
            z-index: 9999; 
            padding: 30px; 
            box-sizing: border-box;
            overflow-y: auto;
        }
    </style>
</head>
<body>

<nav class="navbar">
    <a href="index.php" class="brand-link">🎓 Where Is <span>It?</span></a>
    <div class="nav-links">
        <span style="font-size: 0.95rem; color: var(--text-secondary); margin-right: 15px;">Campus Portal: <strong style="color: white;">UCP Core</strong></span>
        <?php if (isset($_SESSION['user_id'])): ?>
            <a href="logout.php" style="background: rgba(255, 23, 68, 0.1); color: var(--danger); padding: 6px 12px; border-radius: 6px; border: 1px solid rgba(255, 23, 68, 0.2); text-decoration: none;">Sign Out</a>
        <?php else: ?>
            <a href="login.php" style="background: var(--accent); color: white; padding: 8px 18px; border-radius: 6px; font-size: 0.9rem; font-weight: bold; text-decoration: none;">Sign In</a>
        <?php endif; ?>
    </div>
</nav>

<section class="hero-section">
    <h1>Where Is It?</h1>
    <p>Campus Lost & Found Portal — University Property Loss & Recovery Ledger</p>
</section>

<main class="main-container">
    
    <div class="search-wrapper">
        <form method="GET" action="index.php" id="searchForm">
            <input type="text" name="search" id="omniboxInput" class="search-bar" placeholder="🔍 Search matching items..." autocomplete="off" value="<?= htmlspecialchars($search_query) ?>">
        </form>
        <div class="search-dropdown" id="categoryDropdown">
            <a href="index.php" style="font-weight: bold; background: rgba(255,255,255,0.05);">Clear Category Filter</a>
            <?php foreach ($categories as $cat): ?>
                <a href="index.php?cat_filter=<?= $cat['category_id'] ?><?= $search_query ? '&search='.$search_query : '' ?>"><?= htmlspecialchars($cat['category_name']) ?></a>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="filter-bar">
        <div style="display: flex; gap: 10px;">
            <button class="badge-btn unclaimed active" data-type="found">📦 Unclaimed Found Inventory <span class="badge-count"><?= count($found_items) ?></span></button>
            <button class="badge-btn active-lost" data-type="lost">🔴 Active Lost Declarations <span class="badge-count"><?= count($lost_items) ?></span></button>
        </div>
        
        <div class="dual-btn-group" style="margin-left: auto; display: flex; gap: 10px;">
            <?php if (isset($_SESSION['user_id'])): ?>
                <button class="badge-btn" id="openFoundDrawerBtn">📢 Record a Found Item</button>
                <button class="badge-btn" id="openLostDrawerBtn" style="background: rgba(255,255,255,0.05); color: white;">➕ Record a Missing Object</button>
            <?php else: ?>
                <a href="login.php" class="badge-btn" style="text-decoration: none;">🔒 Log In to Create Entry</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="catalog-grid" id="foundGridSection">
        <?php if (empty($found_items)): ?>
            <p style="grid-column: 1/-1; text-align: center; color: var(--text-secondary); padding: 40px;">No unclaimed found properties reported.</p>
        <?php endif; ?>
        <?php foreach ($found_items as $item): ?>
            <a href="item_detail.php?id=<?= $item['item_id'] ?>&type=found" class="item-card clickable-card" data-board="found">
                <div class="image-wrapper">
                    <?php if (!empty($item['photo'])): ?>
                        <img src="<?= htmlspecialchars($item['photo']) ?>" class="card-img" alt="Item Image">
                    <?php else: ?>
                        <span class="placeholder-ui-box">📷 IMAGE CAPTURE ARCHIVE</span>
                    <?php endif; ?>
                </div>
                <div class="card-content">
                    <div class="card-meta">
                        <span class="status-indicator pending">FOUND ITEM</span>
                        <small><?= date('Y-m-d', strtotime($item['date_found'] ?? 'now')) ?></small>
                    </div>
                    <h3 class="card-title"><?= htmlspecialchars($item['title']) ?></h3>
                    <p class="card-desc"><?= htmlspecialchars(substr($item['description'], 0, 90)) ?>...</p>
                    <div class="card-footer">
                        <span class="status-indicator pending">Status: <?= htmlspecialchars($item['status']) ?></span>
                        <span class="view-link">View Archive →</span>
                    </div>
                </div>
            </a>
        <?php endforeach; ?>
    </div>

    <div class="catalog-grid" id="lostGridSection" style="display:none;">
        <?php if (empty($lost_items)): ?>
            <p style="grid-column: 1/-1; text-align: center; color: var(--text-secondary); padding: 40px;">No outstanding active missing asset logs match this sequence.</p>
        <?php endif; ?>
        <?php foreach ($lost_items as $item): ?>
            <a href="item_detail.php?id=<?= $item['lost_id'] ?>&type=lost" class="item-card clickable-card" data-board="lost">
                <div class="image-wrapper">
                    <span class="placeholder-ui-box">📦 REFERENCE IMAGE ARCHIVE</span>
                </div>
                <div class="card-content">
                    <div class="card-meta">
                        <span class="status-indicator lost">MISSING OBJECT</span>
                        <small><?= date('Y-m-d', strtotime($item['date_lost'] ?? 'now')) ?></small>
                    </div>
                    <h3 class="card-title"><?= htmlspecialchars($item['title']) ?></h3>
                    <p class="card-desc"><?= htmlspecialchars(substr($item['description'], 0, 90)) ?>...</p>
                    <div class="card-footer">
                        <span class="status-indicator lost">Status: <?= htmlspecialchars($item['status']) ?></span>
                        <span class="view-link">View Archive →</span>
                    </div>
                </div>
            </a>
        <?php endforeach; ?>
    </div>
</main>

<div class="drawer" id="lostDrawer">
    <div class="drawer-header">
        <h3>File Missing Property Log</h3>
        <button class="close-btn" data-target="lostDrawer">&times;</button>
    </div>
    <form method="POST" action="index.php">
        <input type="hidden" name="action_submit" value="1">
        <input type="hidden" name="board_type" value="lost">
        <div class="form-group">
            <label class="form-label">Descriptive Subject Title</label>
            <input type="text" name="title" class="form-input" placeholder="e.g., Missing Registration Card" required>
        </div>
        <div class="form-group">
            <label class="form-label">Category Specification</label>
            <select name="category_id" class="form-input" required>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= $cat['category_id'] ?>"><?= htmlspecialchars($cat['category_name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label class="form-label">Unique Characteristics & Last Seen</label>
            <textarea name="description" class="form-input" rows="5" placeholder="Specify unique marks, scratches, or contents inside..." required></textarea>
        </div>
        <button type="submit" class="action-btn">Upload to Missing Registry</button>
    </form>
</div>

<div class="drawer" id="foundDrawer">
    <div class="drawer-header">
        <h3>File Found Inventory Asset</h3>
        <button class="close-btn" data-target="foundDrawer">&times;</button>
    </div>
    <form method="POST" action="index.php" enctype="multipart/form-data">
        <input type="hidden" name="action_submit" value="1">
        <input type="hidden" name="board_type" value="found">
        <div class="form-group">
            <label class="form-label">Found Asset Subject Title</label>
            <input type="text" name="title" class="form-input" placeholder="e.g., Found Silver Smart Watch" required>
        </div>
        <div class="form-group">
            <label class="form-label">Category Specification</label>
            <select name="category_id" class="form-input" required>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= $cat['category_id'] ?>"><?= htmlspecialchars($cat['category_name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label class="form-label">Physical Asset Image Upload</label>
            <input type="file" name="photo" accept="image/*" class="form-input" style="padding: 8px 12px;" required>
        </div>
        <div class="form-group">
            <label class="form-label">Discovery Details & Recovery Instructions</label>
            <textarea name="description" class="form-input" rows="5" placeholder="Where was it found? Specify general identifiers..." required></textarea>
        </div>
        <button type="submit" class="action-btn">Commit Found Asset to Feed</button>
    </form>
</div>

<script>
document.addEventListener("DOMContentLoaded", () => {
    const omnibox = document.getElementById("omniboxInput");
    const dropdown = document.getElementById("categoryDropdown");

    if (omnibox && dropdown) {
        omnibox.addEventListener("focus", () => dropdown.style.display = "block");
        document.addEventListener("click", (e) => {
            if (!omnibox.contains(e.target) && !dropdown.contains(e.target)) {
                dropdown.style.display = "none";
            }
        });
    }

    const filterButtons = document.querySelectorAll(".badge-btn[data-type]");
    const foundGrid = document.getElementById("foundGridSection");
    const lostGrid = document.getElementById("lostGridSection");

    filterButtons.forEach(btn => {
        btn.addEventListener("click", () => {
            filterButtons.forEach(b => b.classList.remove("active"));
            btn.classList.add("active");
            
            const type = btn.getAttribute("data-type");
            if (type === "found") {
                foundGrid.style.display = "grid";
                lostGrid.style.display = "none";
            } else {
                foundGrid.style.display = "none";
                lostGrid.style.display = "grid";
            }
        });
    });

    const lostDrawer = document.getElementById("lostDrawer");
    const foundDrawer = document.getElementById("foundDrawer");

    document.getElementById("openLostDrawerBtn")?.addEventListener("click", () => {
        if (lostDrawer.style.right === "0px" || lostDrawer.style.right === "0") {
            lostDrawer.style.right = "-450px";
        } else {
            foundDrawer.style.right = "-450px";
            lostDrawer.style.right = "0px";
        }
    });

    document.getElementById("openFoundDrawerBtn")?.addEventListener("click", () => {
        if (foundDrawer.style.right === "0px" || foundDrawer.style.right === "0") {
            foundDrawer.style.right = "-450px";
        } else {
            lostDrawer.style.right = "-450px";
            foundDrawer.style.right = "0px";
        }
    });
    
    document.querySelectorAll(".close-btn").forEach(btn => {
        btn.addEventListener("click", () => {
            const targetId = btn.getAttribute("data-target");
            document.getElementById(targetId).style.right = "-450px";
        });
    });
});
</script>
</body>
</html>