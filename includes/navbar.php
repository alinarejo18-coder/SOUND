<?php
if (!isset($current_page)) {
    $current_page = basename($_SERVER['PHP_SELF'], '.php');
}
$nav_base = '/SOUND/';
?>
<nav class="navbar">
    <a href="<?php echo $nav_base; ?>index.php" class="logo"><img src="<?php echo $nav_base; ?>assets/images/sound-logo-white.svg" alt="SOUND Logo"></a>
    <div class="nav-controls">

        <?php if (isset($_SESSION['user_id'])): 
            $nav_uid = $_SESSION['user_id'];
            $nav_tbl = ($_SESSION['role'] === 'admin') ? 'admins' : 'users';
            $nav_q = mysqli_query($conn, "SELECT profile_image, name FROM $nav_tbl WHERE id = '$nav_uid'");
            $nav_img = "";
            $nav_name = $_SESSION['name'] ?? 'User';
            if ($nav_q && mysqli_num_rows($nav_q) > 0) {
                $nav_row = mysqli_fetch_assoc($nav_q);
                $nav_img = $nav_row['profile_image'];
                $nav_name = $nav_row['name'];
            }
            $nav_dash_link = ($_SESSION['role'] === 'admin') ? 'admin/dashboard.php' : 'user/dashboard.php';
            $nav_img_path = ($_SESSION['role'] === 'admin') ? 'assets/uploads/profiles/' : 'uploads/users/';
        ?>
            <a href="<?php echo $nav_base . $nav_dash_link; ?>" class="nav-profile-link" style="display:flex; align-items:center; gap:8px; text-decoration:none; color:white;" title="Go to Dashboard">
                <?php if (!empty($nav_img)): ?>
                    <img src="<?php echo $nav_base . $nav_img_path . htmlspecialchars($nav_img); ?>" alt="Profile" style="width: 35px; height: 35px; border-radius: 50%; object-fit: cover; border: 2px solid var(--accent-cyan);">
                <?php else: ?>
                    <div style="width: 35px; height: 35px; border-radius: 50%; background: linear-gradient(135deg, var(--accent-purple), var(--accent-cyan)); display: flex; align-items: center; justify-content: center; font-size: 16px; font-weight: bold; color: white;">
                        <?php echo strtoupper(substr($nav_name, 0, 1)); ?>
                    </div>
                <?php endif; ?>
            </a>
            <a href="<?php echo $nav_base; ?>logout.php" class="btn btn-outline btn-sm">Logout</a>
        <?php else: ?>
            <a href="<?php echo $nav_base; ?>login.php" class="btn btn-outline btn-sm" style="padding: 5px 10px;">Login</a>
            <a href="<?php echo $nav_base; ?>register.php" class="btn btn-primary btn-sm" style="padding: 5px 10px;">Register</a>
        <?php endif; ?>
        
        <button class="mobile-menu-toggle" aria-label="Toggle menu" onclick="document.querySelector('.navbar').classList.toggle('mobile-menu-active')">
            <span></span>
            <span></span>
            <span></span>
        </button>
    </div>

    <?php if (!isset($_SESSION['user_id'])): ?>
    <ul class="nav-links">
        <li><a href="<?php echo $nav_base; ?>index.php" class="<?php echo $current_page === 'index' ? 'active' : ''; ?>">Home</a></li>
        <li><a href="<?php echo $nav_base; ?>music.php" class="<?php echo $current_page === 'music' ? 'active' : ''; ?>">Music</a></li>
        <li><a href="<?php echo $nav_base; ?>videos.php" class="<?php echo $current_page === 'videos' ? 'active' : ''; ?>">Videos</a></li>
        <li class="dropdown">
            <a href="#" style="cursor: default;" class="<?php echo ($current_page === 'search' || isset($_GET['genre'])) ? 'active' : ''; ?>">Categories <i data-lucide="chevron-down" class="icon-ui"></i></a>
            <div class="dropdown-menu">
                <div class="dropdown-header">Music</div>
                <?php foreach($nav_genres as $ng): ?>
                    <a href="<?php echo $nav_base; ?>music.php?genre=<?php echo $ng['id']; ?>"><?php echo htmlspecialchars($ng['genre_name']); ?></a>
                <?php endforeach; ?>
                
                <div class="dropdown-divider"></div>
                
                <div class="dropdown-header">Videos</div>
                <?php foreach($nav_genres as $ng): ?>
                    <a href="<?php echo $nav_base; ?>videos.php?genre=<?php echo $ng['id']; ?>"><?php echo htmlspecialchars($ng['genre_name']); ?></a>
                <?php endforeach; ?>
            </div>
        </li>
    </ul>
    <?php endif; ?>
    <div class="nav-actions">
        <form action="<?php echo $nav_base; ?>search.php" method="GET" style="display: flex; align-items: center;">
            <div style="position: relative; display: flex; align-items: center;">
                <input type="text" name="q" value="<?php echo htmlspecialchars($search_query ?? ''); ?>" placeholder="Search Music and Videos" required style="padding: 10px 20px 10px 42px; border-radius: 25px; border: 1px solid var(--border-color); background: var(--search-bg); color: var(--search-text); outline: none; width: 100%; max-width: 300px; font-family: inherit; font-size: 15px; transition: var(--transition);">
                <span style="position: absolute; left: 16px; color: var(--text-muted); font-size: 16px;"><i data-lucide="search"></i></span>
            </div>
        </form>
    </div>
</nav>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.querySelector('input[name="q"]');
    if (!searchInput) return;
    const searchForm = searchInput.closest('form');
    
    let isSearchPage = window.location.pathname.toLowerCase().includes('search.php');
    let searchContainer = null;

    if (!isSearchPage) {
        searchContainer = document.createElement('div');
        searchContainer.id = 'liveSearchContainer';
        searchContainer.style.display = 'none';
        searchContainer.style.minHeight = '80vh';
        
        const nav = document.querySelector('.navbar');
        if (nav && nav.parentNode) {
            nav.parentNode.insertBefore(searchContainer, nav.nextSibling);
        }
    }

    let timeout = null;

    searchInput.addEventListener('input', function(e) {
        clearTimeout(timeout);
        let q = e.target.value.trim();
        
        timeout = setTimeout(function() {
            if (q.length > 0) {
                fetch('<?php echo $nav_base; ?>search.php?q=' + encodeURIComponent(q))
                .then(res => res.text())
                .then(html => {
                    let parser = new DOMParser();
                    let doc = parser.parseFromString(html, 'text/html');
                    let newContainer = doc.querySelector('.container');
                    
                    if (isSearchPage) {
                        let currentContainer = document.querySelector('.container');
                        if (currentContainer && newContainer) {
                            currentContainer.innerHTML = newContainer.innerHTML;
                        }
                    } else {
                        if (searchContainer && newContainer) {
                            searchContainer.innerHTML = newContainer.outerHTML;
                            searchContainer.style.display = 'block';
                            
                            // Hide other elements
                            const navElement = document.querySelector('.navbar');
                            if (navElement && navElement.parentNode) {
                                Array.from(navElement.parentNode.children).forEach(el => {
                                    if (el !== navElement && el.id !== 'liveSearchContainer' && el.tagName !== 'SCRIPT' && el.tagName !== 'STYLE' && el.tagName !== 'FOOTER' && !el.classList.contains('animated-bg')) {
                                        if (!el.hasAttribute('data-original-display')) {
                                            el.setAttribute('data-original-display', getComputedStyle(el).display || '');
                                        }
                                        el.style.display = 'none';
                                    }
                                });
                            }
                        }
                    }
                });
            } else {
                if (isSearchPage) {
                    let currentContainer = document.querySelector('.container');
                    if (currentContainer) currentContainer.innerHTML = '';
                } else {
                    if (searchContainer) {
                        searchContainer.style.display = 'none';
                        searchContainer.innerHTML = '';
                        // Restore other elements
                        const navElement = document.querySelector('.navbar');
                        if (navElement && navElement.parentNode) {
                            Array.from(navElement.parentNode.children).forEach(el => {
                                if (el.hasAttribute('data-original-display')) {
                                    el.style.display = el.getAttribute('data-original-display');
                                }
                            });
                        }
                    }
                }
            }
        }, 300);
    });
    
    searchForm.addEventListener('submit', function(e) {
        let q = searchInput.value.trim();
        if (!q) {
            e.preventDefault();
        }
    });
});
</script>
