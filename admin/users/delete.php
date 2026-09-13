<?php
require_once "../../includes/auth.php";
require_once "../../config/db.php";

requireAdmin();

$id = intval($_GET["id"] ?? 0);

// Prevent admin from deleting themselves
if ($id > 0 && $id != $_SESSION["user_id"]) {
    
    // Fetch the target user's role and profile_image
    $stmt = mysqli_prepare($conn, "SELECT role, profile_image FROM users WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    
    if ($result && $result['role'] !== 'admin') {
        
        // 1. File Cleanup: Delete profile image if exists
        if ($result['profile_image']) {
            $file_path = "../../uploads/users/" . $result['profile_image'];
            if (file_exists($file_path)) {
                unlink($file_path);
            }
        }
        
        // 2. Database Cleanup: Delete orphaned playlist_items
        $delete_items = mysqli_prepare($conn, "DELETE FROM playlist_items WHERE playlist_id IN (SELECT id FROM playlists WHERE user_id = ?)");
        mysqli_stmt_bind_param($delete_items, "i", $id);
        mysqli_stmt_execute($delete_items);
        
        // 3. Database Cleanup: Delete the user (cascades playlists, ratings, reviews)
        $delete_user = mysqli_prepare($conn, "DELETE FROM users WHERE id = ?");
        mysqli_stmt_bind_param($delete_user, "i", $id);
        mysqli_stmt_execute($delete_user);
    }
}

header("Location: index.php");
exit;
?>
