<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/*
|--------------------------------------------------------------------------
| Check Login
|--------------------------------------------------------------------------
*/
function requireLogin()
{
    if (!isset($_SESSION['user_id'])) {
        header("Location: /SOUND/login.php");
        exit;
    }
    
    // Prevent browser from caching authenticated pages
    header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
    header("Cache-Control: post-check=0, pre-check=0", false);
    header("Pragma: no-cache");
}


/*
|--------------------------------------------------------------------------
| Check Admin
|--------------------------------------------------------------------------
*/
function requireAdmin()
{
    requireLogin();

    if ($_SESSION['role'] !== 'admin') {
        header("Location: /SOUND/index.php");
        exit;
    }
}


/*
|--------------------------------------------------------------------------
| Check User
|--------------------------------------------------------------------------
*/
function requireUser()
{
    requireLogin();

    if ($_SESSION['role'] !== 'user') {
        header("Location: /SOUND/index.php");
        exit;
    }
}
