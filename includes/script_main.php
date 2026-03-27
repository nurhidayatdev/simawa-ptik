<script>
    const menuToggle = document.getElementById('menuToggle');
    const mobileMenu = document.getElementById('mobileMenu');
    if (menuToggle && mobileMenu) {
        const menuIcon = menuToggle.querySelector('i');
        menuToggle.addEventListener('click', () => {
            mobileMenu.classList.toggle('hidden');
            if (mobileMenu.classList.contains('hidden')) {
                if (menuIcon) {
                    menuIcon.classList.remove('fa-xmark');
                    menuIcon.classList.add('fa-bars');
                }
            } else {
                if (menuIcon) {
                    menuIcon.classList.remove('fa-bars');
                    menuIcon.classList.add('fa-xmark');
                }
            }
        });
    }

    // --- THEME TOGGLE LOGIC ---
    const themeToggleBtn = document.getElementById('theme-toggle');
    const themeToggleMobileBtn = document.getElementById('theme-toggle-mobile');

    // Function to update Icons State (Switch & Mobile Style)
    function updateThemeIcons() {
        const isDark = document.documentElement.classList.contains('dark');
        const darkIcon = document.getElementById('theme-toggle-dark-icon');
        const lightIcon = document.getElementById('theme-toggle-light-icon');

        if (isDark) {
            // Premium Switch UI Animations (Desktop)
            if (darkIcon) {
                darkIcon.classList.remove('opacity-0', 'scale-0');
                darkIcon.classList.add('opacity-100', 'scale-100');
            }
            if (lightIcon) {
                lightIcon.classList.add('opacity-0', 'scale-0');
                lightIcon.classList.remove('opacity-100', 'scale-100');
            }

            // Simple List UI (Mobile)
            if (themeToggleMobileBtn) {
                const moon = themeToggleMobileBtn.querySelector('.moon-icon');
                const sun = themeToggleMobileBtn.querySelector('.sun-icon');
                const text = themeToggleMobileBtn.querySelector('.theme-text');
                if (moon) moon.classList.add('hidden');
                if (sun) sun.classList.remove('hidden');
                if (text) text.textContent = 'Mode Terang';
            }
        } else {
            // Premium Switch UI Animations (Desktop)
            if (darkIcon) {
                darkIcon.classList.add('opacity-0', 'scale-0');
                darkIcon.classList.remove('opacity-100', 'scale-100');
            }
            if (lightIcon) {
                lightIcon.classList.remove('opacity-0', 'scale-0');
                lightIcon.classList.add('opacity-100', 'scale-100');
            }

            // Simple List UI (Mobile)
            if (themeToggleMobileBtn) {
                const moon = themeToggleMobileBtn.querySelector('.moon-icon');
                const sun = themeToggleMobileBtn.querySelector('.sun-icon');
                const text = themeToggleMobileBtn.querySelector('.theme-text');
                if (moon) moon.classList.remove('hidden');
                if (sun) sun.classList.add('hidden');
                if (text) text.textContent = 'Mode Gelap';
            }
        }
    }

    function toggleTheme() {
        if (document.documentElement.classList.contains('dark')) {
            document.documentElement.classList.remove('dark');
            localStorage.setItem('theme', 'light');
        } else {
            document.documentElement.classList.add('dark');
            localStorage.setItem('theme', 'dark');
        }
        updateThemeIcons();
    }

    if (themeToggleBtn) themeToggleBtn.addEventListener('click', toggleTheme);
    if (themeToggleMobileBtn) themeToggleMobileBtn.addEventListener('click', toggleTheme);

    // Initialize icons on load
    document.addEventListener('DOMContentLoaded', updateThemeIcons);

    // Scroll Reveal Implementation
    document.addEventListener('DOMContentLoaded', function() {
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('active');
                } else {
                    entry.target.classList.remove('active');
                }
            });
        }, observerOptions);

        const revealElements = document.querySelectorAll('.scroll-reveal');
        revealElements.forEach(el => observer.observe(el));
    });
</script>