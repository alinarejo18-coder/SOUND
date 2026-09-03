<?php

require_once "../../includes/auth.php";
require_once "../../config/db.php";

requireAdmin();

$id = intval($_GET["id"] ?? 0);


if ($id > 0) {

    $stmt = mysqli_prepare(
        $conn,
        "DELETE FROM albums
         WHERE id = ?"
    );

    mysqli_stmt_bind_param(
        $stmt,
        "i",
        $id
    );

    mysqli_stmt_execute($stmt);

    mysqli_stmt_close($stmt);
}


header("Location: index.php");
exit;

?>
