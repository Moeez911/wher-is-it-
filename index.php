<?php
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
    
    // Generate unique structural IDs to ensure feedback separation
    $new_id = rand(15000, 99999);
    
    if ($board === 'lost') {
        $stmt = $pdo->prepare("INSERT INTO lost_item (lost_id, title, description, date_lost, status, days_missing, user_id, category_id) VALUES (?, ?, ?, CURDATE(), 'searching', 0, ?, ?)");
        $stmt->execute([$new_id, $title, $desc, $user, $cat]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO found_items (item_id, title, description, photo, date_found, status, days_listed, user_id, category_id, location_id) VALUES (?, ?, ?, ?, CURDATE(), 'pending', 0, ?, ?, 4001)");
        $stmt->execute([$new_id, $title, $desc, $photo_path, $user, $cat]);
    }
    header("Location: index.php?success=posted");
    exit;
}

$categories = $pdo->query("SELECT * FROM category")->fetchAll();
$search_cat = isset($_GET['cat_filter']) ? (int)$_GET['cat_filter'] : 0;
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';

// Fetch active properties
$found_sql = "SELECT fi.*, c.category_name FROM found_items fi JOIN category c ON fi.category_id = c.category_id WHERE fi.status = 'pending'";
if ($search_cat) $found_sql .= " AND fi.category_id = $search_cat";
if ($search_query) $found_sql .= " AND fi.title LIKE " . $pdo->quote('%'.$search_query.'%');
$found_items = $pdo->query($found_sql)->fetchAll();

$lost_sql = "SELECT li.*, c.category_name FROM lost_item li JOIN category c ON li.category_id = c.category_id WHERE li.status = 'searching'";
if ($search_cat) $lost_sql .= " AND li.category_id = $search_cat";
if ($search_query) $lost_sql .= " AND li.title LIKE " . $pdo->quote('%'.$search_query.'%');
$lost_items = $pdo->query($lost_sql)->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Where Is It? — Campus Lost & Found Portal</title>
    <link rel="stylesheet" href="Assets/css/style.css?v=3">
    <style>
        .search-wrapper { position: relative; margin-bottom: 25px; width: 100%; }
        .search-bar { width: 100%; padding: 14px 20px; font-size: 1rem; border: 2px solid var(--border); border-radius: var(--radius); outline: none; transition: border-color 0.2s; }
        .search-bar:focus { border-color: var(--periwinkle); }
        .search-dropdown { display: none; position: absolute; top: 100%; left: 0; right: 0; background: #232366; border: 1px solid var(--border); border-radius: var(--radius); z-index: 500; max-height: 250px; overflow-y: auto; }
        .search-dropdown a { display: block; padding: 10px 20px; text-decoration: none; color: white; border-bottom: 1px solid var(--deep-navy); }
        .search-dropdown a:hover { background: var(--egyptian-blue); color: var(--periwinkle); }
        .dual-btn-group { display: flex; gap: 10px; }
        .btn-found-action { background: var(--egyptian-blue) !important; color: white !important; }
        .btn-found-action:hover { background: var(--periwinkle) !important; color: var(--prussian-blue) !important; }
        
        /* Make entire card look completely interactive and clickable */
        .clickable-card { text-decoration: none; color: inherit; display: flex; flex-direction: column; }
    </style>
</head>
<body>

<nav class="navbar">
    <a href="index.php" class="brand">🎓 Where Is It?</a>
    <div class="menu">
        <span>Campus Portal: <strong>UCP Core</strong></span>
        <?php if (isset($_SESSION['user_id'])): ?>
            <a href="logout.php">Sign Out</a>
        <?php else: ?>
            <a href="login.php" class="action-btn" style="color:var(--primary); padding:6px 12px; text-decoration:none;">Sign In</a>
        <?php endif; ?>
    </div>
</nav>

<section class="hero">
    <h1>Where Is It?</h1>
    <p class="mission">Campus Lost & Found Portal — University Property Loss & Recovery Ledger</p>
</section>

<main class="main-container">
    
    <div class="search-wrapper">
        <form method="GET" action="index.php" id="searchForm">
            <input type="text" name="search" id="omniboxInput" class="search-bar" placeholder="🔍 Search matching items or click to view database categories..." autocomplete="off" value="<?= htmlspecialchars($search_query) ?>">
        </form>
        <div class="search-dropdown" id="categoryDropdown">
            <a href="index.php" style="font-weight: bold; background: rgba(35, 35, 102, 0.5);">Clear Category Filter</a>
            <?php foreach ($categories as $cat): ?>
                <a href="index.php?cat_filter=<?= $cat['category_id'] ?><?= $search_query ? '&search='.$search_query : '' ?>"><?= htmlspecialchars($cat['category_name']) ?></a>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="control-bar">
        <div class="filters">
            <button class="filter-btn active" data-type="found">📦 Unclaimed Found Inventory (<?= count($found_items) ?>)</button>
            <button class="filter-btn" data-type="lost">🔴 Active Lost Declarations (<?= count($lost_items) ?>)</button>
        </div>
        
        <div class="dual-btn-group">
            <?php if (isset($_SESSION['user_id'])): ?>
                <button class="action-btn btn-found-action" id="openFoundDrawerBtn">📢 Record a Found Item</button>
                <button class="action-btn" id="openLostDrawerBtn">➕ Record a Missing Object</button>
            <?php else: ?>
                <a href="login.php" class="action-btn" style="text-decoration:none;">🔒 Log In to Create Entry</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="item-grid" id="foundGridSection">
        <?php if (empty($found_items)): ?>
            <p style="grid-column: 1/-1; text-align: center; color: var(--text-muted); padding: 40px;">No unclaimed found properties reported.</p>
        <?php endif; ?>
        <?php foreach ($found_items as $item): ?>
            <a href="item_detail.php?id=<?= $item['item_id'] ?>&type=found" class="item-card clickable-card">
                <div>
                    <div style="display:flex; justify-content:space-between; margin-bottom:12px;">
                        <span class="badge badge-found">FOUND ITEM</span>
                        <small style="color:var(--text-muted);"><?= date('Y-m-d', strtotime($item['created_at'])) ?></small>
                    </div>
                    
                    <div style="width:100%; height:160px; background:rgba(35, 35, 102, 0.3); border-radius:4px; margin-bottom:12px; overflow:hidden; display:flex; align-items:center; justify-content:center; color:var(--text-muted); font-weight:bold; border: 1px solid var(--border);">
                        <?php if (!empty($item['photo']) && file_exists($item['photo'])): ?>
                            <img src="<?= htmlspecialchars($item['photo']) ?>" style="width:100%; height:100%; object-fit:cover;">
                        <?php else: ?>
                            📷 IMAGE CAPTURE ARCHIVE
                        <?php endif; ?>
                    </div>
                    
                    <h3><?= htmlspecialchars($item['title']) ?></h3>
                    <p class="snippet"><?= htmlspecialchars(substr($item['description'], 0, 90)) ?>...</p>
                </div>
                <div style="margin-top:15px; padding-top:12px; border-top:1px solid var(--border); display:flex; justify-content:space-between; align-items:center;">
                    <span style="font-size:0.85rem; color:var(--text-muted);">Status: <strong style="color:var(--emerald);"><?= htmlspecialchars($item['status']) ?></strong></span>
                    <span style="color:var(--periwinkle); font-size:0.85rem; font-weight:bold;">View Archive →</span>
                </div>
            </a>
        <?php endforeach; ?>
    </div>

    <div class="item-grid" id="lostGridSection" style="display:none;">
        <?php if (empty($lost_items)): ?>
            <p style="grid-column: 1/-1; text-align: center; color: var(--text-muted); padding: 40px;">No outstanding active missing asset logs match this sequence.</p>
        <?php endif; ?>
        <?php foreach ($lost_items as $item): ?>
            <a href="item_detail.php?id=<?= $item['lost_id'] ?>&type=lost" class="item-card clickable-card">
                <div>
                    <div style="display:flex; justify-content:space-between; margin-bottom:12px;">
                        <span class="badge badge-lost">MISSING STUDENT STUFF</span>
                        <small style="color:var(--text-muted);"><?= date('Y-m-d', strtotime($item['created_at'])) ?></small>
                    </div>
                    <h3><?= htmlspecialchars($item['title']) ?></h3>
                    <p class="snippet"><?= htmlspecialchars(substr($item['description'], 0, 90)) ?>...</p>
                </div>
                <div style="margin-top:15px; padding-top:12px; border-top:1px solid var(--border); display:flex; justify-content:space-between; align-items:center;">
                    <span style="font-size:0.85rem; color:var(--text-muted);">Status: <strong style="color:var(--crimson);"><?= htmlspecialchars($item['status']) ?></strong></span>
                    <span style="color:var(--periwinkle); font-size:0.85rem; font-weight:bold;">View Archive →</span>
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
            <label>Descriptive Subject Title</label>
            <input type="text" name="title" placeholder="e.g., Missing Registration Card" required>
        </div>
        <div class="form-group">
            <label>Category Specification</label>
            <select name="category_id" required>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= $cat['category_id'] ?>"><?= htmlspecialchars($cat['category_name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label>Unique Characteristics & Last Seen</label>
            <textarea name="description" rows="5" placeholder="Specify unique marks, scratches, or contents inside..." required></textarea>
        </div>
        <button type="submit" class="action-btn" style="width:100%;">Upload to Missing Registry</button>
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
            <label>Found Asset Subject Title</label>
            <input type="text" name="title" placeholder="e.g., Found Silver Smart Watch" required>
        </div>
        <div class="form-group">
            <label>Category Specification</label>
            <select name="category_id" required>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= $cat['category_id'] ?>"><?= htmlspecialchars($cat['category_name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label>Physical Asset Image Upload</label>
            <input type="file" name="photo" accept="image/*" required>
        </div>
        <div class="form-group">
            <label>Discovery Details & Recovery Instructions</label>
            <textarea name="description" rows="5" placeholder="Where was it found? Specify general identifiers..." required></textarea>
        </div>
        <button type="submit" class="action-btn btn-found-action" style="width:100%;">Commit Found Asset to Feed</button>
    </form>
</div>

<script>
document.addEventListener("DOMContentLoaded", () => {
    const omnibox = document.getElementById("omniboxInput");
    const dropdown = document.getElementById("categoryDropdown");

    omnibox.addEventListener("focus", () => dropdown.style.display = "block");
    document.addEventListener("click", (e) => {
        if (!omnibox.contains(e.target) && !dropdown.contains(e.target)) {
            dropdown.style.display = "none";
        }
    });

    const filterButtons = document.querySelectorAll(".filter-btn");
    const foundGrid = document.getElementById("foundGridSection");
    const lostGrid = document.getElementById("lostGridSection");

    filterButtons.forEach(btn => {
        btn.addEventListener("click", () => {
            filterButtons.forEach(b => b.classList.remove("active"));
            btn.classList.add("active");
            
            if (btn.getAttribute("data-type") === "found") {
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

    // FIX: Toggling or double-clicking won't glitch layouts. Open one, close the alternative cleanly.
    document.getElementById("openLostDrawerBtn")?.addEventListener("click", () => {
        if (lostDrawer.classList.contains("open")) {
            lostDrawer.classList.remove("open");
        } else {
            foundDrawer.classList.remove("open");
            lostDrawer.classList.add("open");
        }
    });

    document.getElementById("openFoundDrawerBtn")?.addEventListener("click", () => {
        if (foundDrawer.classList.contains("open")) {
            foundDrawer.classList.remove("open");
        } else {
            lostDrawer.classList.remove("open");
            foundDrawer.classList.add("open");
        }
    });
    
    document.querySelectorAll(".close-btn").forEach(btn => {
        btn.addEventListener("click", () => {
            const targetId = btn.getAttribute("data-target");
            document.getElementById(targetId).classList.remove("open");
        });
    });
});
</script>
</body>
</html>