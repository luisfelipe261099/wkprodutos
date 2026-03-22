    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- SCRIPT DE SIDEBAR MOBILE FINAL - VERSÃO DEFINITIVA -->
    <script>
    console.log('🚀 === INICIANDO SIDEBAR MOBILE FINAL ===');
    
    // Função principal de inicialização
    function inicializarSidebarMobile() {
        console.log('📱 Verificando dispositivo...');
        
        // Verificar se é mobile
        const isMobile = window.innerWidth <= 768;
        console.log(`📱 É mobile? ${isMobile} (largura: ${window.innerWidth}px)`);
        
        if (!isMobile) {
            console.log('🖥️ Desktop detectado - não configurando sidebar mobile');
            return;
        }
        
        console.log('🔍 Procurando elementos da sidebar...');
        
        // Buscar elementos com múltiplos seletores
        let toggle = document.querySelector('.sidebar-toggle');
        if (!toggle) toggle = document.querySelector('#sidebarToggle');
        if (!toggle) toggle = document.querySelector('[data-toggle="sidebar"]');
        if (!toggle) toggle = document.querySelector('.navbar-toggler');
        if (!toggle) toggle = document.querySelector('.menu-toggle');
        
        let sidebar = document.querySelector('.sidebar');
        if (!sidebar) sidebar = document.querySelector('nav.sidebar');
        if (!sidebar) sidebar = document.querySelector('#sidebar');
        if (!sidebar) sidebar = document.querySelector('aside.sidebar');
        
        console.log('🔍 Elementos encontrados:', {
            toggle: toggle ? `✅ ${toggle.className || toggle.id || 'sem classe'}` : '❌ não encontrado',
            sidebar: sidebar ? `✅ ${sidebar.className || sidebar.id || 'sem classe'}` : '❌ não encontrado'
        });
        
        if (!toggle) {
            console.error('❌ ERRO: Toggle button não encontrado!');
            console.log('🔍 Elementos disponíveis:', Array.from(document.querySelectorAll('button, [data-toggle]')).map(el => el.className || el.id || el.tagName));
            return;
        }
        
        if (!sidebar) {
            console.error('❌ ERRO: Sidebar não encontrada!');
            console.log('🔍 Elementos disponíveis:', Array.from(document.querySelectorAll('nav, aside, .side')).map(el => el.className || el.id || el.tagName));
            return;
        }
        
        console.log('✅ Elementos encontrados com sucesso!');
        
        // Criar overlay se não existir
        let overlay = document.querySelector('.mobile-overlay');
        if (!overlay) {
            console.log('📱 Criando overlay...');
            overlay = document.createElement('div');
            overlay.className = 'mobile-overlay';
            document.body.appendChild(overlay);
            console.log('✅ Overlay criado');
        } else {
            console.log('✅ Overlay já existe');
        }
        
        // Aplicar estilos forçados via JavaScript
        console.log('🎨 Aplicando estilos...');
        
        // Sidebar
        Object.assign(sidebar.style, {
            position: 'fixed',
            top: '0',
            left: '0',
            width: '85vw',
            maxWidth: '300px',
            height: '100vh',
            transform: 'translateX(-100%)',
            transition: 'transform 0.3s ease',
            zIndex: '1050',
            background: 'linear-gradient(180deg, #1e293b 0%, #0f172a 100%)',
            boxShadow: '6px 0 30px rgba(0, 0, 0, 0.6)',
            overflowY: 'auto',
            overflowX: 'hidden',
            margin: '0',
            padding: '0',
            border: 'none'
        });
        
        // Overlay
        Object.assign(overlay.style, {
            position: 'fixed',
            top: '0',
            left: '0',
            width: '100vw',
            height: '100vh',
            background: 'rgba(0, 0, 0, 0.75)',
            zIndex: '1040',
            display: 'none',
            opacity: '0',
            transition: 'opacity 0.3s ease',
            backdropFilter: 'blur(4px)',
            cursor: 'pointer'
        });
        
        // Topbar
        const topbar = document.querySelector('.topbar') || document.querySelector('.navbar') || document.querySelector('header');
        if (topbar) {
            Object.assign(topbar.style, {
                position: 'fixed',
                top: '0',
                left: '0',
                right: '0',
                height: '60px',
                zIndex: '1030',
                background: 'rgba(255, 255, 255, 0.98)',
                backdropFilter: 'blur(20px)',
                display: 'flex',
                alignItems: 'center',
                padding: '0 1rem'
            });
            console.log('✅ Topbar estilizada');
        }
        
        // Main content
        const mainContent = document.querySelector('.main-content') || document.querySelector('main') || document.querySelector('.content');
        if (mainContent) {
            Object.assign(mainContent.style, {
                marginLeft: '0',
                marginTop: '60px',
                padding: '1rem',
                width: '100%'
            });
            console.log('✅ Main content estilizado');
        }
        
        console.log('✅ Estilos aplicados com sucesso!');
        
        // Variável de estado
        let sidebarAberta = false;
        
        // Funções de controle
        function abrirSidebar() {
            console.log('🔓 Abrindo sidebar...');
            sidebarAberta = true;
            sidebar.style.transform = 'translateX(0)';
            overlay.style.display = 'block';
            setTimeout(() => overlay.style.opacity = '1', 10);
            sidebar.classList.add('show');
            document.body.style.overflow = 'hidden';
            console.log('✅ Sidebar aberta');
        }
        
        function fecharSidebar() {
            console.log('🔒 Fechando sidebar...');
            sidebarAberta = false;
            sidebar.style.transform = 'translateX(-100%)';
            overlay.style.opacity = '0';
            setTimeout(() => overlay.style.display = 'none', 300);
            sidebar.classList.remove('show');
            document.body.style.overflow = '';
            console.log('✅ Sidebar fechada');
        }
        
        function alternarSidebar() {
            console.log('🔄 Alternando sidebar...');
            if (sidebarAberta) {
                fecharSidebar();
            } else {
                abrirSidebar();
            }
        }
        
        // Expor funções globalmente para debug
        window.abrirSidebar = abrirSidebar;
        window.fecharSidebar = fecharSidebar;
        window.alternarSidebar = alternarSidebar;
        
        // Clonar toggle button para remover eventos existentes
        console.log('🔄 Configurando eventos...');
        const novoToggle = toggle.cloneNode(true);
        toggle.parentNode.replaceChild(novoToggle, toggle);
        toggle = novoToggle;
        
        // Event listeners
        toggle.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            console.log('👆 Toggle clicado - click');
            alternarSidebar();
        });
        
        toggle.addEventListener('touchend', function(e) {
            e.preventDefault();
            e.stopPropagation();
            console.log('👆 Toggle tocado - touchend');
            alternarSidebar();
        });
        
        overlay.addEventListener('click', function(e) {
            e.preventDefault();
            console.log('👆 Overlay clicado');
            fecharSidebar();
        });
        
        // ESC key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && sidebarAberta) {
                console.log('⌨️ ESC pressionado');
                fecharSidebar();
            }
        });
        
        // Resize
        window.addEventListener('resize', function() {
            if (window.innerWidth > 768 && sidebarAberta) {
                console.log('📱➡️🖥️ Mudou para desktop');
                fecharSidebar();
            }
        });
        
        console.log('✅ Event listeners configurados');
        
        // Fechar modal de encoding
        setTimeout(function() {
            const encodingModal = document.querySelector('div[style*="position: fixed"]');
            if (encodingModal && encodingModal.textContent.includes('Encoding')) {
                console.log('📝 Fechando modal de encoding...');
                encodingModal.style.display = 'none';
            }
        }, 1000);
        
        // Botão de teste visual
        const botaoTeste = document.createElement('button');
        botaoTeste.innerHTML = '☰ TESTE';
        botaoTeste.style.cssText = `
            position: fixed !important;
            bottom: 20px !important;
            right: 20px !important;
            z-index: 9999 !important;
            background: linear-gradient(45deg, #ff4757, #ff6b7a) !important;
            color: white !important;
            border: none !important;
            padding: 15px 20px !important;
            border-radius: 30px !important;
            font-size: 14px !important;
            font-weight: bold !important;
            cursor: pointer !important;
            box-shadow: 0 8px 25px rgba(255, 71, 87, 0.4) !important;
            transition: all 0.3s ease !important;
            touch-action: manipulation !important;
        `;
        
        botaoTeste.onmouseover = function() {
            this.style.transform = 'translateY(-2px) scale(1.05)';
            this.style.boxShadow = '0 12px 35px rgba(255, 71, 87, 0.6)';
        };
        
        botaoTeste.onmouseout = function() {
            this.style.transform = 'translateY(0) scale(1)';
            this.style.boxShadow = '0 8px 25px rgba(255, 71, 87, 0.4)';
        };
        
        botaoTeste.onclick = function() {
            console.log('🧪 Botão de teste clicado!');
            alternarSidebar();
        };
        
        document.body.appendChild(botaoTeste);
        console.log('🧪 Botão de teste adicionado');
        
        console.log('🎉 === SIDEBAR MOBILE CONFIGURADA COM SUCESSO! ===');
        console.log('🧪 Comandos de teste disponíveis:');
        console.log('   - window.abrirSidebar()');
        console.log('   - window.fecharSidebar()');
        console.log('   - window.alternarSidebar()');
        
        return true;
    }
    
    // Executar com múltiplas estratégias
    console.log('⏰ Configurando múltiplas tentativas...');
    
    // Tentativa 1: DOM já carregado
    if (document.readyState === 'complete') {
        console.log('✅ DOM já carregado - inicializando imediatamente');
        inicializarSidebarMobile();
    }
    
    // Tentativa 2: DOMContentLoaded
    document.addEventListener('DOMContentLoaded', function() {
        console.log('✅ DOMContentLoaded disparado');
        inicializarSidebarMobile();
    });
    
    // Tentativa 3: Load event
    window.addEventListener('load', function() {
        console.log('✅ Window load disparado');
        inicializarSidebarMobile();
    });
    
    // Tentativas com timeout
    setTimeout(() => {
        console.log('⏰ Timeout 500ms - tentativa');
        inicializarSidebarMobile();
    }, 500);
    
    setTimeout(() => {
        console.log('⏰ Timeout 1000ms - tentativa');
        inicializarSidebarMobile();
    }, 1000);
    
    setTimeout(() => {
        console.log('⏰ Timeout 2000ms - tentativa final');
        inicializarSidebarMobile();
    }, 2000);
    </script>
    }
    
    // CSS Emergency inline
    const emergencyCSS = `
    @media (max-width: 768px) {
        .sidebar {
            position: fixed !important;
            top: 0 !important;
            left: 0 !important;
            width: 85vw !important;
            max-width: 300px !important;
            height: 100vh !important;
            transform: translateX(-100%) !important;
            transition: transform 0.3s ease !important;
            z-index: 1050 !important;
            background: linear-gradient(180deg, #1e293b 0%, #0f172a 100%) !important;
            box-shadow: 4px 0 25px rgba(0, 0, 0, 0.6) !important;
            overflow-y: auto !important;
        }
        
        .sidebar.show {
            transform: translateX(0) !important;
        }
        
        .mobile-overlay {
            position: fixed !important;
            top: 0 !important;
            left: 0 !important;
            width: 100vw !important;
            height: 100vh !important;
            background: rgba(0, 0, 0, 0.75) !important;
            z-index: 1040 !important;
            display: none !important;
            backdrop-filter: blur(4px) !important;
        }
        
        .mobile-overlay.show {
            display: block !important;
            opacity: 1 !important;
        }
        
        .topbar {
            position: fixed !important;
            top: 0 !important;
            left: 0 !important;
            right: 0 !important;
            height: 60px !important;
            z-index: 1030 !important;
            background: rgba(255, 255, 255, 0.98) !important;
            backdrop-filter: blur(20px) !important;
            padding: 0 1rem !important;
        }
        
        .main-content {
            margin-left: 0 !important;
            margin-top: 60px !important;
            padding: 1rem !important;
        }
        
        .sidebar-toggle {
            background: transparent !important;
            border: none !important;
            color: #64748b !important;
            font-size: 1.25rem !important;
            padding: 0.75rem !important;
            border-radius: 10px !important;
            min-width: 48px !important;
            min-height: 48px !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            cursor: pointer !important;
            transition: all 0.3s ease !important;
        }
        
        .sidebar-toggle:hover {
            background: rgba(59, 130, 246, 0.15) !important;
            color: #3b82f6 !important;
            transform: scale(1.05) !important;
        }
    }
    `;
    
    const style = document.createElement('style');
    style.innerHTML = emergencyCSS;
    document.head.appendChild(style);
    </script>
    
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