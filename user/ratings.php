<?php
require_once "../includes/auth.php";
require_once "../config/db.php";
requireUser();

$user_id = $_SESSION["user_id"];
$message = "";

// Handle Delete (AJAX support)
if (isset($_GET['delete'])) {
    $delete_id = intval($_GET['delete']);
    $stmt = mysqli_prepare($conn, "DELETE FROM ratings WHERE id = ? AND user_id = ?");
    mysqli_stmt_bind_param($stmt, "ii", $delete_id, $user_id);
    if (mysqli_stmt_execute($stmt)) {
        $message = "Rating deleted successfully.";
    }
    if (isset($_GET['ajax'])) {
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => $message, 'deleted_id' => $delete_id]);
        exit;
    }
}

// Fetch Ratings
$params = [];
$types = "i"; // user_id is integer

// Base query
$query = "
    SELECT r.id, r.rating,
           m.title AS music_title,
           v.title AS video_title
    FROM ratings r
    LEFT JOIN music m ON r.music_id = m.id
    LEFT JOIN videos v ON r.video_id = v.id
    WHERE r.user_id = ?
";

// Search filter
if (!empty($_GET['search'])) {
    $search = "%" . $_GET['search'] . "%";
    $query .= " AND (m.title LIKE ? OR v.title LIKE ?)";
    $params[] = $search;
    $params[] = $search;
    $types .= "ss";
}

// Media type filter
if (!empty($_GET['media'])) {
    if ($_GET['media'] === 'music') {
        $query .= " AND r.music_id IS NOT NULL";
    } elseif ($_GET['media'] === 'video') {
        $query .= " AND r.video_id IS NOT NULL";
    }
}

// Rating filter
if (!empty($_GET['rating'])) {
    $query .= " AND r.rating = ?";
    $params[] = intval($_GET['rating']);
    $types .= "i";
}

$query .= " ORDER BY r.id DESC";

$stmt = mysqli_prepare($conn, $query);
if ($stmt) {
    // Bind parameters with proper reference handling
    $bind_params = array_merge([$types, $user_id], $params);
    $refs = [];
    foreach ($bind_params as $key => $value) {
        $refs[$key] = &$bind_params[$key];
    }
    call_user_func_array([$stmt, 'bind_param'], $refs);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
} else {
    $result = false;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Ratings - SOUND</title>
    <link rel="stylesheet" href="../assets/css/style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../assets/css/admin.css?v=<?php echo time(); ?>">
    <script src="../assets/js/theme.js?v=<?php echo time(); ?>"></script>
</head>
<body>

<div class="admin-layout">

    <?php 
    $current_page = 'dashboard';
    $user_base = ''; 
    include "../includes/user_header.php"; 
    ?>

    <main class="admin-main">
        
        <div class="admin-topbar">
            <div>
                <h1>My Ratings <i data-lucide="star" class="icon-ui"></i></h1>
                <div class="breadcrumb"><a href="dashboard.php">Dashboard</a> / My Ratings</div>
            </div>
            <div>

            </div>
        </div>
<form method="GET" action="ratings.php" class="filter-section ajax-filter-form" data-results-container="ratings-results-container">
    <div class="filter-group">
        <input type="text" name="search" class="filter-input" placeholder="Search media..." value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>" />
    </div>
    <div class="filter-group">
        <select name="media" class="filter-input">
            <option value="" <?php if (empty($_GET['media'])) echo 'selected'; ?>>All Media</option>
            <option value="music" <?php if (($_GET['media'] ?? '') == 'music') echo 'selected'; ?>>Music</option>
            <option value="video" <?php if (($_GET['media'] ?? '') == 'video') echo 'selected'; ?>>Video</option>
        </select>
    </div>
    <div class="filter-group">
        <select name="rating" class="filter-input">
            <option value="" <?php if (empty($_GET['rating'])) echo 'selected'; ?>>All Ratings</option>
            <?php for ($i = 1; $i <= 5; $i++): ?>
                <option value="<?php echo $i; ?>" <?php if (($_GET['rating'] ?? '') == (string)$i) echo 'selected'; ?>><?php echo $i; ?> Stars</option>
            <?php endfor; ?>
        </select>
    </div>
    <button type="submit" class="filter-btn-apply btn btn-primary">Apply Filters</button>
<button type="reset" class="filter-btn-clear btn btn-secondary">Clear / Reset</button>
</form>
        <div class="admin-content">
            <?php if ($message): ?>
                <div style="background: rgba(16, 185, 129, 0.1); color: var(--accent-green); padding: 12px; border-radius: 6px; margin-bottom: 20px; font-weight: bold; border: 1px solid rgba(16, 185, 129, 0.3);">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <div class="table-container" id="ratings-results-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Media</th>
                            <th>Rating</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (mysqli_num_rows($result) > 0): ?>
                        <?php while ($row = mysqli_fetch_assoc($result)): ?>
                            <tr>
                                <td>
                                    <?php 
                                    if ($row['music_title']) {
                                        echo '<i data-lucide="music" class="icon-ui"></i> ' . htmlspecialchars($row['music_title']);
                                    } elseif ($row['video_title']) {
                                        echo '<i data-lucide="video" class="icon-ui"></i> ' . htmlspecialchars($row['video_title']);
                                    } else {
                                        echo 'Deleted Media';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <?php 
                                    $rating = $row['rating'];
                                    echo str_repeat('<i data-lucide="star" class="icon-ui"></i>', $rating) . str_repeat('<i data-lucide="star" class="icon-ui" style="opacity:.28"></i>', 5 - $rating);
                                    ?>
                                </td>
                                <td>
                                    <a href="?delete=<?php echo $row['id']; ?>" class="ajax-delete-rating btn btn-sm btn-danger" data-id="<?php echo $row['id']; ?>" onclick="return false;">Delete</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="3" style="text-align: center; padding: 20px; color: var(--text-muted);">You haven't rated anything yet.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>

        </div>
        
        <?php include '../includes/footer.php'; ?>
    </main>
</div>

<script>
    document.getElementById('sidebarToggle')?.addEventListener('click', () => {
        document.getElementById('sidebar').classList.toggle('open');
    });

    // AJAX delete rating
    document.querySelectorAll('.ajax-delete-rating').forEach(btn => {
        btn.addEventListener('click', e => {
            e.preventDefault();
            const id = btn.dataset.id;
            if (!confirm('Delete this rating?')) return;
            fetch(`?delete=${id}&ajax=1`)
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        const row = btn.closest('tr');
                        if (row) row.remove();
                        const msgDiv = document.createElement('div');
                        msgDiv.style.background = 'rgba(16,185,129,0.1)';
                        msgDiv.style.color = 'var(--accent-green)';
                        msgDiv.style.padding = '12px';
                        msgDiv.style.borderRadius = '6px';
                        msgDiv.style.marginBottom = '20px';
                        msgDiv.style.fontWeight = 'bold';
                        msgDiv.style.border = '1px solid rgba(16,185,129,0.3)';
                        msgDiv.textContent = data.message;
                        const container = document.querySelector('.admin-content');
                        if (container) container.insertBefore(msgDiv, container.firstChild);
                    }
                })
                .catch(() => {});
        });
    });
</script>


</body>
</html>
