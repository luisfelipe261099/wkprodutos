    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="js/script.js"></script>
    <script src="js/mobile-enhancements.js"></script>
    
    <?php if (isset($_GET['debug']) && $_GET['debug'] == '1'): ?>
    <script src="js/responsive-debug.js"></script>
    <?php endif; ?>

    <script>
        (function () {
            const sidebar = document.getElementById('sidebar');
            const mainContent = document.getElementById('mainContent');
            const topbar = document.getElementById('topbar');
            const overlay = document.getElementById('mobileOverlay');
            const toggleButton = document.getElementById('sidebarToggle');
            const closeButton = document.getElementById('sidebarClose');
            const desktopMedia = window.matchMedia('(min-width: 1025px)');
            const tabletMedia = window.matchMedia('(min-width: 769px) and (max-width: 1024px)');
            const mobileMedia = window.matchMedia('(max-width: 768px)');

            if (!sidebar || !mainContent || !topbar || !overlay || !toggleButton) {
                return;
            }

            function openMobileSidebar() {
                sidebar.classList.add('show');
                overlay.classList.add('show');
                document.body.classList.add('sidebar-open');
                document.body.style.overflow = 'hidden'; // Previne scroll do body
            }

            function closeMobileSidebar() {
                sidebar.classList.remove('show');
                overlay.classList.remove('show');
                document.body.classList.remove('sidebar-open');
                document.body.style.overflow = ''; // Restaura scroll
            }

            function syncLayout() {
                if (desktopMedia.matches) {
                    closeMobileSidebar();
                    document.body.style.overflow = '';
                } else {
                    sidebar.classList.remove('collapsed');
                    mainContent.classList.remove('expanded');
                    topbar.classList.remove('expanded');
                }
            }

            toggleButton.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();
                
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

            // Melhorar eventos de toque
            overlay.addEventListener('click', closeMobileSidebar);
            overlay.addEventListener('touchstart', closeMobileSidebar);

            if (closeButton) {
                closeButton.addEventListener('click', function(e) {
                    e.preventDefault();
                    closeMobileSidebar();
                });
            }

            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape' && sidebar.classList.contains('show')) {
                    closeMobileSidebar();
                }
            });

            // Fechar sidebar ao clicar em link no mobile/tablet
            sidebar.querySelectorAll('.nav-link').forEach(function (link) {
                link.addEventListener('click', function () {
                    if (mobileMedia.matches || tabletMedia.matches) {
                        setTimeout(closeMobileSidebar, 300); // Delay para animação suave
                    }
                });
            });

            // Melhor handling de resize
            let resizeTimer;
            window.addEventListener('resize', function() {
                clearTimeout(resizeTimer);
                resizeTimer = setTimeout(syncLayout, 100);
            });
            
            syncLayout();

            // Prevenir zoom duplo toque no iOS
            let lastTouchEnd = 0;
            document.addEventListener('touchend', function (event) {
                const now = (new Date()).getTime();
                if (now - lastTouchEnd <= 300) {
                    event.preventDefault();
                }
                lastTouchEnd = now;
            }, false);
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

    <!-- ✅ Alerta de Encoding se houver problema -->
    <?php
    if (function_exists('displayEncodingAlert')) {
        echo displayEncodingAlert();
    }
    ?>
</body>
</html>