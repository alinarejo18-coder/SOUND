<?php
require_once "../../includes/auth.php";
require_once "../../config/db.php";

requireAdmin();

$id = intval($_GET["id"] ?? 0);

if ($id > 0) {
    // Get file names to delete them
    $stmt = mysqli_prepare($conn, "SELECT image, music_file FROM music WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    if ($row = mysqli_fetch_assoc($result)) {
        if (!empty($row['image']) && file_exists("../../uploads/music/images/" . $row['image'])) {
            unlink("../../uploads/music/images/" . $row['image']);
        }
        if (!empty($row['music_file']) && file_exists("../../uploads/music/files/" . $row['music_file'])) {
            unlink("../../uploads/music/files/" . $row['music_file']);
        }
        
        $delete = mysqli_prepare($conn, "DELETE FROM music WHERE id = ?");
        mysqli_stmt_bind_param($delete, "i", $id);
        mysqli_stmt_execute($delete);
    }
}
header("Location: index.php");
exit;
?>
