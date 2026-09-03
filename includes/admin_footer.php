<?php
$footer_site_name = $site_info['site_name'] ?? 'SOUND';

// Determine path to root directory
$root_path = '';
if (isset($admin_base)) {
    $root_path = $admin_base . '../';
} elseif (isset($user_base)) {
    $root_path = $user_base . '../';
}
?>

<footer class="admin-footer-global">
    <div class="admin-footer-container">
        <div class="admin-fb-left">
            &copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars($footer_site_name); ?>. All rights reserved.
        </div>
        <div class="admin-fb-center">
            Made for music lovers <i data-lucide="heart" class="heart-icon"></i>
        </div>
        <div class="admin-fb-right">
            <a href="<?php echo $root_path; ?>index.php" target="_blank" class="admin-footer-link">
                <i data-lucide="external-link"></i> View Public Site
            </a>
        </div>
    </div>
</footer>

<!-- Lucide Icons Script -->
<script src="https://unpkg.com/lucide@latest"></script>
<script>
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
</script>
