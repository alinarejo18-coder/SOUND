<?php
$footer_site_name = $site_info['site_name'] ?? 'SOUND';
$footer_site_desc = $site_info['description'] ?? 'SOUND brings music, videos, artists and entertainment together in one modern platform.';

// Determine path to root directory
$root_path = '';
if (isset($admin_base)) {
    $root_path = $admin_base . '../';
} elseif (isset($user_base)) {
    $root_path = $user_base . '../';
}

$current_page = basename($_SERVER['PHP_SELF']);
$show_footer = in_array($current_page, ['index.php', 'music.php', 'videos.php', 'play_music.php', 'play_video.php']);
?>

<?php if ($show_footer): ?>
<footer class="global-footer">
    <div class="footer-container">
        <!-- Bottom Bar -->
        <div class="footer-bottom">
            <div class="fb-left">
                &copy; <?php echo date('Y'); ?> SOUND. All rights reserved.
            </div>
            <div class="fb-center">
                Made for music lovers <i data-lucide="heart" class="heart-icon"></i>
            </div>
            <div class="fb-right">
                Powered by SOUND
            </div>
        </div>
    </div>
</footer>
<?php endif; ?>

<!-- Lucide Icons Script -->
<script src="https://unpkg.com/lucide@latest"></script>
<script>
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
</script>
