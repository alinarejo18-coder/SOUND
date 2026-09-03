<?php
require_once "../../includes/auth.php";
require_once "../../config/db.php";

requireAdmin();

$id = intval($_GET["id"] ?? 0);

// Prevent admin from deleting themselves
if ($id > 0 && $id != $_SESSION["user_id"]) {
    $delete = mysqli_prepare($conn, "DELETE FROM users WHERE id = ?");
    mysqli_stmt_bind_param($delete, "i", $id);
    mysqli_stmt_execute($delete);
}

header("Location: index.php");
exit;
?>
