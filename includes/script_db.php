<script>
// Mobile menu toggle dengan perbaikan
        const mobileMenuButton = document.getElementById('mobile-menu-button');
        const sidebar = document.getElementById('sidebar');

        if (mobileMenuButton && sidebar) {
            mobileMenuButton.addEventListener('click', (e) => {
                e.stopPropagation(); // Prevent event bubbling
                sidebar.classList.toggle('-translate-x-full');
                sidebar.classList.toggle('mobile-menu-open');
            });

            // Close sidebar when clicking outside
            document.addEventListener('click', (e) => {
                if (window.innerWidth < 768) { // Only on mobile
                    if (!sidebar.contains(e.target) && !mobileMenuButton.contains(e.target)) {
                        sidebar.classList.add('-translate-x-full');
                        sidebar.classList.remove('mobile-menu-open');
                    }
                }
            });
        }

        // Submenu toggles
        const submenuButtons = document.querySelectorAll('.submenu-button');

        submenuButtons.forEach(button => {
            button.addEventListener('click', () => {
                const submenu = button.nextElementSibling;
                const arrow = button.querySelector('.submenu-arrow');

                if (submenu) {
                    submenu.classList.toggle('hidden');
                }
                if (arrow) {
                    arrow.classList.toggle('rotate-180');
                }
            });
        });

        // Profile dropdown
        const profileButton = document.getElementById('profile-button');
        const profileDropdown = document.getElementById('profile-dropdown');

        if (profileButton && profileDropdown) {
            profileButton.addEventListener('click', (e) => {
                e.stopPropagation();
                profileDropdown.classList.toggle('hidden');
            });

            document.addEventListener('click', (e) => {
                if (!profileDropdown.contains(e.target) && !profileButton.contains(e.target)) {
                    profileDropdown.classList.add('hidden');
                }
            });
        }

        // --- THEME TOGGLE LOGIC ---
        const themeToggleBtn = document.getElementById('theme-toggle');
        const darkIcon = document.getElementById('theme-toggle-dark-icon');
        const lightIcon = document.getElementById('theme-toggle-light-icon');

        if (themeToggleBtn) {
            // Initial toggle based on theme
            const setToggleState = (isDark) => {
                if (isDark) {
                    lightIcon.classList.add('opacity-0', 'scale-0');
                    darkIcon.classList.remove('opacity-0', 'scale-0');
                } else {
                    lightIcon.classList.remove('opacity-0', 'scale-0');
                    darkIcon.classList.add('opacity-0', 'scale-0');
                }
            };

            setToggleState(document.documentElement.classList.contains('dark'));

            themeToggleBtn.addEventListener('click', () => {
                const isDark = !document.documentElement.classList.contains('dark');
                
                if (isDark) {
                    document.documentElement.classList.add('dark');
                    localStorage.setItem('theme', 'dark');
                } else {
                    document.documentElement.classList.remove('dark');
                    localStorage.setItem('theme', 'light');
                }
                
                setToggleState(isDark);
                
                // Reload page to correctly re-initialize charts and JS components
                setTimeout(() => {
                    location.reload();
                }, 150);
            });
        }
</script>