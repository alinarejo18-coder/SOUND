<?php

require_once "../../includes/auth.php";
require_once "../../config/db.php";

requireAdmin();

$message = "";
$error = "";

/* ADD YEAR */
if (isset($_POST["add_year"])) {

    $year = intval($_POST["year_value"]);

    if ($year < 1900 || $year > 2100) {
        $error = "Please enter a valid year.";
    } else {

        $check = mysqli_prepare(
            $conn,
            "SELECT id FROM years WHERE year_value = ?"
        );

        mysqli_stmt_bind_param($check, "i", $year);
        mysqli_stmt_execute($check);
        mysqli_stmt_store_result($check);

        if (mysqli_stmt_num_rows($check) > 0) {

            $error = "This year already exists.";

        } else {

            $stmt = mysqli_prepare(
                $conn,
                "INSERT INTO years (year_value) VALUES (?)"
            );

            mysqli_stmt_bind_param($stmt, "i", $year);

            if (mysqli_stmt_execute($stmt)) {
                $message = "Year added successfully.";
            } else {
                $error = "Failed to add year.";
            }
        }
    }
}


/* DELETE YEAR */
if (isset($_GET["delete"])) {

    $id = intval($_GET["delete"]);

    $stmt = mysqli_prepare(
        $conn,
        "DELETE FROM years WHERE id = ?"
    );

    mysqli_stmt_bind_param($stmt, "i", $id);

    if (mysqli_stmt_execute($stmt)) {
        $message = "Year deleted successfully.";
    } else {
        $error = "Cannot delete this year.";
    }
}


/* GET YEARS */
$result = mysqli_query(
    $conn,
    "SELECT * FROM years ORDER BY year_value DESC"
);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Years - SOUND Admin</title>
    <link rel="stylesheet" href="../../assets/css/style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../../assets/css/admin.css?v=<?php echo time(); ?>">
</head>
<body>

<div class="admin-layout">

    <?php 
    $current_page = 'years';
    $admin_base = '../';
    include "../../includes/admin_header.php"; 
    ?>

    <main class="admin-main">
        <div class="admin-topbar">
            <div>
                <h1>Manage Years <i data-lucide="calendar-days" class="icon-ui"></i></h1>
                <div class="breadcrumb">Add and manage music release years.</div>
            </div>
        </div>

        <div class="admin-content">
            <div class="form-panel" style="margin-bottom: 25px;">
                <?php if ($message): ?>
                    <div style="background: #064e3b; color: #34d399; padding: 12px; border-radius: 6px; margin-bottom: 20px;">
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div style="background: #7f1d1d; color: #fca5a5; padding: 12px; border-radius: 6px; margin-bottom: 20px;">
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <form method="POST" style="display: flex; gap: 10px;">
                    <input type="number" name="year_value" placeholder="Enter year e.g. 2026" min="1900" max="2100" required class="form-control" style="flex: 1;">
                    <button type="submit" name="add_year" class="btn btn-primary">Add Year</button>
                </form>
            </div>

            <div class="table-container">
                <h2 style="margin-bottom: 20px;">All Years</h2>
                
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Year</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (mysqli_num_rows($result) > 0): ?>
                        <?php while ($row = mysqli_fetch_assoc($result)): ?>
                            <tr>
                                <td><?php echo $row["id"]; ?></td>
                                <td><strong><?php echo $row["year_value"]; ?></strong></td>
                                <td style="white-space: nowrap;">
                                    <a href="?delete=<?php echo $row["id"]; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this year?');" style="margin: 0; padding: 6px 12px; font-size: 0.85rem;">Delete</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="3" style="text-align: center; padding: 30px; color: #777;">No years found.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>

<script>
    document.getElementById('sidebarToggle')?.addEventListener('click', () => {
        document.getElementById('sidebar').classList.toggle('open');
    });
</script>


</body>
</html>
