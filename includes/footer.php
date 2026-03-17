    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="js/script.js"></script>

    <script>
        (function () {
            const sidebar = document.getElementById('sidebar');
            const mainContent = document.getElementById('mainContent');
            const topbar = document.getElementById('topbar');
            const overlay = document.getElementById('mobileOverlay');
            const toggleButton = document.getElementById('sidebarToggle');
            const closeButton = document.getElementById('sidebarClose');
            const desktopMedia = window.matchMedia('(min-width: 1025px)');

            if (!sidebar || !mainContent || !topbar || !overlay || !toggleButton) {
                return;
            }

            function openMobileSidebar() {
                sidebar.classList.add('show');
                overlay.classList.add('show');
                document.body.classList.add('sidebar-open');
            }

            function closeMobileSidebar() {
                sidebar.classList.remove('show');
                overlay.classList.remove('show');
                document.body.classList.remove('sidebar-open');
            }

            function syncLayout() {
                if (desktopMedia.matches) {
                    closeMobileSidebar();
                } else {
                    sidebar.classList.remove('collapsed');
                    mainContent.classList.remove('expanded');
                    topbar.classList.remove('expanded');
                }
            }

            toggleButton.addEventListener('click', function () {
                if (desktopMedia.matches) {
                    sidebar.classList.toggle('collapsed');
                    mainContent.classList.toggle('expanded');
                    topbar.classList.toggle('expanded');
                    document.body.classList.toggle('sidebar-collapsed');
                    return;
                }

                if (sidebar.classList.contains('show')) {
                    closeMobileSidebar();
                } else {
                    openMobileSidebar();
                }
            });

            overlay.addEventListener('click', closeMobileSidebar);

            if (closeButton) {
                closeButton.addEventListener('click', closeMobileSidebar);
            }

            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape' && sidebar.classList.contains('show')) {
                    closeMobileSidebar();
                }
            });

            sidebar.querySelectorAll('.nav-link').forEach(function (link) {
                link.addEventListener('click', function () {
                    if (!desktopMedia.matches) {
                        closeMobileSidebar();
                    }
                });
            });

            window.addEventListener('resize', syncLayout);
            syncLayout();
        }());
    </script>

    <script>
        /* ===== Theme Toggle ===== */
        (function () {
            var btn = document.getElementById('themeToggle');
            var STORAGE_KEY = 'kw-theme';

            function applyTheme(theme) {
                if (theme === 'light') {
                    document.documentElement.setAttribute('data-theme', 'light');
                    document.documentElement.setAttribute('data-bs-theme', 'light');
                } else {
                    document.documentElement.removeAttribute('data-theme');
                    document.documentElement.setAttribute('data-bs-theme', 'dark');
                }
                if (btn) {
                    var icon = btn.querySelector('i');
                    if (icon) {
                        if (theme === 'light') {
                            icon.className = 'fas fa-moon';
                            btn.setAttribute('aria-label', 'Ativar modo escuro');
                            btn.title = 'Modo escuro';
                        } else {
                            icon.className = 'fas fa-sun';
                            btn.setAttribute('aria-label', 'Ativar modo claro');
                            btn.title = 'Modo claro';
                        }
                    }
                }
            }

            /* Apply saved/default theme on load */
            var savedTheme = 'dark';
            try { savedTheme = localStorage.getItem(STORAGE_KEY) || 'dark'; } catch (e) {}
            applyTheme(savedTheme);

            if (btn) {
                btn.addEventListener('click', function () {
                    var current = document.documentElement.getAttribute('data-theme') === 'light' ? 'light' : 'dark';
                    var next = current === 'light' ? 'dark' : 'light';
                    try { localStorage.setItem(STORAGE_KEY, next); } catch (e) {}
                    applyTheme(next);
                });
            }
        }());
    </script>
</body>
</html>