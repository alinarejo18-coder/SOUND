/* ============================================================
   SOUND — Main JavaScript
   Audio/Video Player, Star Ratings, UI Interactions
   ============================================================ */

document.addEventListener('DOMContentLoaded', function() {

    const setPlayerIcon = function(button, icon) {
        button.innerHTML = '<i data-lucide="' + icon + '"></i>';
        if (typeof lucide !== 'undefined') lucide.createIcons();
    };

    // ---------- Mobile Sidebar Toggle ----------
    const sidebarToggle = document.querySelector('.sidebar-toggle');
    const sidebar = document.querySelector('.sidebar');

    if (sidebarToggle && sidebar) {
        sidebarToggle.addEventListener('click', function(e) {
            e.stopPropagation();
            sidebar.classList.toggle('open');
        });

        // Close on outside click
        document.addEventListener('click', function(e) {
            if (sidebar.classList.contains('open') &&
                !sidebar.contains(e.target) &&
                !sidebarToggle.contains(e.target)) {
                sidebar.classList.remove('open');
            }
        });
    }

    // ---------- Frontend Mobile Menu Toggle ----------
    const mobileMenuToggle = document.querySelector('.mobile-menu-toggle');
    const navbar = document.querySelector('.navbar');

    if (mobileMenuToggle && navbar) {
        // Close on outside click
        document.addEventListener('click', function(e) {
            if (navbar.classList.contains('mobile-menu-active') &&
                !navbar.contains(e.target) &&
                !mobileMenuToggle.contains(e.target)) {
                navbar.classList.remove('mobile-menu-active');
            }
        });
    }


    // ---------- Custom Audio Player ----------
    const audioPlayer = document.getElementById('audioPlayer');
    const playBtn = document.getElementById('playBtn');
    const progressBar = document.getElementById('progressBar');
    const progress = document.getElementById('progress');
    const currentTimeEl = document.getElementById('currentTime');
    const durationEl = document.getElementById('duration');
    const volumeSlider = document.getElementById('volumeSlider');

    if (audioPlayer && playBtn) {

        playBtn.addEventListener('click', function() {
            if (audioPlayer.paused) {
                audioPlayer.play();
                setPlayerIcon(playBtn, 'pause');
            } else {
                audioPlayer.pause();
                setPlayerIcon(playBtn, 'play');
            }
        });

        audioPlayer.addEventListener('timeupdate', function() {
            if (audioPlayer.duration) {
                const pct = (audioPlayer.currentTime / audioPlayer.duration) * 100;
                if (progress) progress.style.width = pct + '%';
                if (currentTimeEl) currentTimeEl.textContent = formatTime(audioPlayer.currentTime);
            }
        });

        audioPlayer.addEventListener('loadedmetadata', function() {
            if (durationEl) durationEl.textContent = formatTime(audioPlayer.duration);
        });

        audioPlayer.addEventListener('ended', function() {
            setPlayerIcon(playBtn, 'play');
            if (progress) progress.style.width = '0%';
        });

        if (progressBar) {
            progressBar.addEventListener('click', function(e) {
                const rect = progressBar.getBoundingClientRect();
                const pct = (e.clientX - rect.left) / rect.width;
                audioPlayer.currentTime = pct * audioPlayer.duration;
            });
        }

        if (volumeSlider) {
            volumeSlider.addEventListener('input', function() {
                audioPlayer.volume = this.value / 100;
            });
        }
    }


    // ---------- Custom Video Player ----------
    const videoPlayer = document.getElementById('videoPlayer');
    const videoPlayBtn = document.getElementById('videoPlayBtn');
    const videoProgressBar = document.getElementById('videoProgressBar');
    const videoProgress = document.getElementById('videoProgress');
    const videoCurrentTime = document.getElementById('videoCurrentTime');
    const videoDuration = document.getElementById('videoDuration');
    const videoVolumeSlider = document.getElementById('videoVolumeSlider');
    const fullscreenBtn = document.getElementById('fullscreenBtn');

    if (videoPlayer && videoPlayBtn) {

        videoPlayBtn.addEventListener('click', function() {
            if (videoPlayer.paused) {
                videoPlayer.play();
                setPlayerIcon(videoPlayBtn, 'pause');
            } else {
                videoPlayer.pause();
                setPlayerIcon(videoPlayBtn, 'play');
            }
        });

        videoPlayer.addEventListener('click', function() {
            videoPlayBtn.click();
        });

        videoPlayer.addEventListener('timeupdate', function() {
            if (videoPlayer.duration) {
                const pct = (videoPlayer.currentTime / videoPlayer.duration) * 100;
                if (videoProgress) videoProgress.style.width = pct + '%';
                if (videoCurrentTime) videoCurrentTime.textContent = formatTime(videoPlayer.currentTime);
            }
        });

        videoPlayer.addEventListener('loadedmetadata', function() {
            if (videoDuration) videoDuration.textContent = formatTime(videoPlayer.duration);
        });

        videoPlayer.addEventListener('ended', function() {
            setPlayerIcon(videoPlayBtn, 'play');
            if (videoProgress) videoProgress.style.width = '0%';
        });

        if (videoProgressBar) {
            videoProgressBar.addEventListener('click', function(e) {
                const rect = videoProgressBar.getBoundingClientRect();
                const pct = (e.clientX - rect.left) / rect.width;
                videoPlayer.currentTime = pct * videoPlayer.duration;
            });
        }

        if (videoVolumeSlider) {
            videoVolumeSlider.addEventListener('input', function() {
                videoPlayer.volume = this.value / 100;
            });
        }

        if (fullscreenBtn) {
            fullscreenBtn.addEventListener('click', function() {
                if (videoPlayer.requestFullscreen) {
                    videoPlayer.requestFullscreen();
                } else if (videoPlayer.webkitRequestFullscreen) {
                    videoPlayer.webkitRequestFullscreen();
                }
            });
        }
    }


    // ---------- Star Rating Widget ----------
    const starInputs = document.querySelectorAll('.star-rating input');

    starInputs.forEach(function(input) {
        input.addEventListener('change', function() {
            const form = this.closest('form');
            if (form && form.dataset.ajax === 'true') {
                const formData = new FormData(form);
                fetch(form.action, {
                    method: 'POST',
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        const msgEl = document.getElementById('ratingMessage');
                        if (msgEl) {
                            msgEl.textContent = 'Rating saved!';
                            msgEl.style.color = '#22c55e';
                            setTimeout(() => msgEl.textContent = '', 2000);
                        }
                    }
                })
                .catch(() => {});
            }
        });
    });


    // ---------- Delete Confirmation ----------
    document.querySelectorAll('.confirm-delete').forEach(function(el) {
        el.addEventListener('click', function(e) {
            if (!confirm('Are you sure you want to delete this? This action cannot be undone.')) {
                e.preventDefault();
            }
        });
    });


    // ---------- Fade-in on Scroll ----------
    const fadeElements = document.querySelectorAll('.fade-on-scroll');

    if (fadeElements.length > 0) {
        const observer = new IntersectionObserver(function(entries) {
            entries.forEach(function(entry) {
                if (entry.isIntersecting) {
                    entry.target.classList.add('fade-in');
                    entry.target.style.removeProperty('opacity');
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.1 });

        fadeElements.forEach(function(el) {
            el.style.opacity = '0';
            observer.observe(el);
        });
    }


    // ---------- Search Filter ----------
    const searchInput = document.getElementById('searchInput');
    const searchForm = document.getElementById('searchForm');

    if (searchInput && searchForm) {
        let timeout;
        searchInput.addEventListener('input', function() {
            clearTimeout(timeout);
            timeout = setTimeout(function() {
                searchForm.submit();
            }, 600);
        });
    }

      if (searchInput && searchForm) {
        let timeout;
        searchInput.addEventListener('input', function() {
            clearTimeout(timeout);
            timeout = setTimeout(function() {
                searchForm.submit();
            }, 600);
        });
    }

});


  


// ---------- Utility: Format Time ----------
function formatTime(seconds) {
    if (isNaN(seconds)) return '0:00';
    const mins = Math.floor(seconds / 60);
    const secs = Math.floor(seconds % 60);
    return mins + ':' + (secs < 10 ? '0' : '') + secs;
}
