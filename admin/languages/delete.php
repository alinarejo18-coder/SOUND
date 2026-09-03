<?php

require_once "../../includes/auth.php";
require_once "../../config/db.php";

requireAdmin();

if (
    !isset($_GET["id"]) ||
    !is_numeric($_GET["id"])
) {
    header("Location: index.php");
    exit;
}

$id = (int) $_GET["id"];

$stmt = mysqli_prepare(
    $conn,
    "DELETE FROM languages WHERE id = ?"
);

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $id
);

mysqli_stmt_execute($stmt);

mysqli_stmt_close($stmt);

header("Location: index.php");
exit;
