<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Get the base URL path for the SOUND project.
 * Works regardless of which subfolder the calling script is in.
 */
function getBasePath()
{
    // __DIR__ is the includes/ folder; go one level up to get the project root
    $projectRoot = realpath(__DIR__ . '/..');
    $docRoot = realpath($_SERVER['DOCUMENT_ROOT']);
    $basePath = str_replace($docRoot, '', $projectRoot);
    $basePath = str_replace('\\', '/', $basePath); // normalize Windows paths
    return rtrim($basePath, '/');
}

/*
|--------------------------------------------------------------------------
| Check Login
|--------------------------------------------------------------------------
*/
function requireLogin()
{
    if (!isset($_SESSION['user_id'])) {
        header("Location: " . getBasePath() . "/login.php");
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
        header("Location: " . getBasePath() . "/index.php");
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
        header("Location: " . getBasePath() . "/index.php");
        exit;
    }
}
