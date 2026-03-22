    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- SCRIPT MOBILE SIDEBAR - VERSÃO AGRESSIVA FINAL -->
    <script>
    console.log('🚀 === INICIANDO SIDEBAR MOBILE AGRESSIVO ===');
    
    function forcarSidebarMobile() {
        console.log('💪 FORÇANDO configuração mobile...');
        
        // FORÇAR DETECÇÃO MOBILE (ignorar largura da tela)
        const forceMobile = true; // SEMPRE considerar como mobile
        console.log(`📱 Modo forçado: ${forceMobile}`);
        
        // BUSCAR ELEMENTOS COM FORÇA BRUTA
        console.log('🔍 Procurando elementos com força bruta...');
        
        const possibleToggles = [
            '.sidebar-toggle',
            '#sidebarToggle', 
            '[data-toggle="sidebar"]',
            '.navbar-toggler',
            '.menu-toggle',
            'button[aria-controls="sidebar"]'
        ];
        
        const possibleSidebars = [
            '.sidebar',
            '#sidebar',
            'nav.sidebar',
            'aside.sidebar'
        ];
        
        let toggle = null;
        let sidebar = null;
        
        // Procurar toggle
        for (let selector of possibleToggles) {
            toggle = document.querySelector(selector);
            if (toggle) {
                console.log(`✅ Toggle encontrado: ${selector}`);
                break;
            }
        }
        
        // Procurar sidebar
        for (let selector of possibleSidebars) {
            sidebar = document.querySelector(selector);
            if (sidebar) {
                console.log(`✅ Sidebar encontrada: ${selector}`);
                break;
            }
        }
        
        // LOG DE DEBUG DETALHADO
        console.log('🔍 ELEMENTOS DISPONÍVEIS NO DOM:');
        const allButtons = document.querySelectorAll('button');
        console.log(`   - Botões: ${allButtons.length}`, Array.from(allButtons).map(b => b.className || b.id || 'sem classe'));
        const allNavs = document.querySelectorAll('nav, aside, .sidebar');
        console.log(`   - Navegação: ${allNavs.length}`, Array.from(allNavs).map(n => n.className || n.id || 'sem classe'));
        
        if (!toggle) {
            console.error('❌ ERRO CRÍTICO: Toggle não encontrado!');
            console.log('🆘 Criando toggle de emergência...');
            
            // CRIAR TOGGLE DE EMERGÊNCIA
            const emergencyToggle = document.createElement('button');
            emergencyToggle.innerHTML = '☰';
            emergencyToggle.className = 'emergency-toggle';
            emergencyToggle.style.cssText = `
                position: fixed !important;
                top: 10px !important;
                left: 10px !important;
                z-index: 9999 !important;
                background: #ff0000 !important;
                color: white !important;
                border: none !important;
                padding: 15px !important;
                border-radius: 50% !important;
                font-size: 18px !important;
                cursor: pointer !important;
                box-shadow: 0 4px 12px rgba(0,0,0,0.3) !important;
            `;
            document.body.appendChild(emergencyToggle);
            toggle = emergencyToggle;
            console.log('🆘 Toggle de emergência criado!');
        }
        
        if (!sidebar) {
            console.error('❌ ERRO CRÍTICO: Sidebar não encontrada!');
            return false;
        }
        
        console.log('✅ Elementos confirmados - iniciando configuração...');
        
        // CRIAR OVERLAY AGRESSIVO
        let overlay = document.querySelector('.mobile-overlay') || document.querySelector('.sidebar-overlay');
        if (!overlay) {
            overlay = document.createElement('div');
            overlay.className = 'mobile-overlay emergency-overlay';
            document.body.appendChild(overlay);
            console.log('✅ Overlay de emergência criado');
        }
        
        // APLICAR ESTILOS CSS FORÇADOS DIRETAMENTE
        console.log('🎨 Aplicando estilos FORÇADOS...');
        
        // FORÇAR SIDEBAR
        sidebar.style.cssText = `
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
            box-shadow: 6px 0 30px rgba(0, 0, 0, 0.7) !important;
            overflow-y: auto !important;
            overflow-x: hidden !important;
            margin: 0 !important;
            padding: 0 !important;
            border: none !important;
            display: block !important;
            visibility: visible !important;
        `;
        
        // FORÇAR OVERLAY
        overlay.style.cssText = `
            position: fixed !important;
            top: 0 !important;
            left: 0 !important;
            width: 100vw !important;
            height: 100vh !important;
            background: rgba(0, 0, 0, 0.6) !important;
            z-index: 1040 !important;
            display: none !important;
            opacity: 0 !important;
            transition: opacity 0.3s ease !important;
            backdrop-filter: blur(3px) !important;
            cursor: pointer !important;
        `;
        
        // FORÇAR TOPBAR
        const topbar = document.querySelector('.topbar') || document.querySelector('.navbar') || document.querySelector('header');
        if (topbar) {
            topbar.style.cssText += `
                position: fixed !important;
                top: 0 !important;
                left: 0 !important;
                right: 0 !important;
                height: 60px !important;
                z-index: 1030 !important;
                background: rgba(255, 255, 255, 0.98) !important;
                backdrop-filter: blur(20px) !important;
                display: flex !important;
                align-items: center !important;
                padding: 0 1rem !important;
            `;
            console.log('✅ Topbar forçada');
        }
        
        // FORÇAR MAIN CONTENT
        const mainContent = document.querySelector('.main-content') || document.querySelector('main') || document.querySelector('.content');
        if (mainContent) {
            mainContent.style.cssText += `
                margin-left: 0 !important;
                margin-top: 60px !important;
                padding: 1rem !important;
                width: 100% !important;
            `;
            console.log('✅ Main content forçado');
        }
        
        console.log('✅ Estilos aplicados com sucesso!');
        
        // VARIÁVEIS GLOBAIS
        let sidebarAberta = false;
        
        // FUNÇÕES DE CONTROLE AGRESSIVAS
        window.abrirSidebarAgressiva = function() {
            console.log('🔓 ABRINDO sidebar agressivamente...');
            sidebarAberta = true;
            
            sidebar.style.transform = 'translateX(0)';
            sidebar.classList.add('show');
            
            overlay.style.display = 'block';
            setTimeout(() => overlay.style.opacity = '1', 10);
            overlay.classList.add('show');
            
            document.body.style.overflow = 'hidden';
            document.body.classList.add('sidebar-open');
            
            console.log('✅ Sidebar ABERTA com sucesso!');
        };
        
        window.fecharSidebarAgressiva = function() {
            console.log('🔒 FECHANDO sidebar agressivamente...');
            sidebarAberta = false;
            
            sidebar.style.transform = 'translateX(-100%)';
            sidebar.classList.remove('show');
            
            overlay.style.opacity = '0';
            setTimeout(() => {
                overlay.style.display = 'none';
                overlay.classList.remove('show');
            }, 300);
            
            document.body.style.overflow = '';
            document.body.classList.remove('sidebar-open');
            
            console.log('✅ Sidebar FECHADA com sucesso!');
        };
        
        window.alternarSidebarAgressiva = function() {
            console.log('🔄 ALTERNANDO sidebar...');
            if (sidebarAberta) {
                window.fecharSidebarAgressiva();
            } else {
                window.abrirSidebarAgressiva();
            }
        };
        
        // EVENTOS AGRESSIVOS
        console.log('🎯 Configurando eventos agressivos...');
        
        // CLONAR TOGGLE para remover eventos existentes
        const novoToggle = toggle.cloneNode(true);
        toggle.parentNode.replaceChild(novoToggle, toggle);
        
        // EVENT LISTENERS MÚLTIPLOS
        ['click', 'touchend', 'touchstart'].forEach(eventType => {
            novoToggle.addEventListener(eventType, function(e) {
                e.preventDefault();
                e.stopPropagation();
                e.stopImmediatePropagation();
                console.log(`👆 Toggle ${eventType} - ATIVADO!`);
                window.alternarSidebarAgressiva();
            }, { passive: false });
        });
        
        // OVERLAY CLICK
        overlay.addEventListener('click', function(e) {
            e.preventDefault();
            console.log('👆 Overlay clicado - FECHANDO');
            window.fecharSidebarAgressiva();
        });
        
        // ESC KEY
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && sidebarAberta) {
                console.log('⌨️ ESC - FECHANDO');
                window.fecharSidebarAgressiva();
            }
        });
        
        console.log('✅ Eventos configurados!');
        
        // BOTÃO DE TESTE SUPER VISÍVEL
        const botaoTeste = document.createElement('button');
        botaoTeste.innerHTML = '🧪 TESTE SIDEBAR';
        botaoTeste.style.cssText = `
            position: fixed !important;
            bottom: 20px !important;
            right: 20px !important;
            z-index: 9999 !important;
            background: linear-gradient(45deg, #ff4757, #ff6b7a) !important;
            color: white !important;
            border: none !important;
            padding: 15px 20px !important;
            border-radius: 25px !important;
            font-size: 14px !important;
            font-weight: bold !important;
            cursor: pointer !important;
            box-shadow: 0 8px 25px rgba(255, 71, 87, 0.4) !important;
            animation: pulse 2s infinite !important;
        `;
        
        // Animação pulsante
        const style = document.createElement('style');
        style.textContent = `
            @keyframes pulse {
                0% { transform: scale(1); }
                50% { transform: scale(1.05); }
                100% { transform: scale(1); }
            }
        `;
        document.head.appendChild(style);
        
        botaoTeste.onclick = function() {
            console.log('🧪 BOTÃO DE TESTE CLICADO!');
            window.alternarSidebarAgressiva();
        };
        
        document.body.appendChild(botaoTeste);
        console.log('🧪 Botão de teste SUPER VISÍVEL adicionado');
        
        // FECHAR MODAL DE ENCODING
        setTimeout(function() {
            const encodingModal = document.querySelector('div[style*="position: fixed"]');
            if (encodingModal && encodingModal.textContent.includes('Encoding')) {
                console.log('📝 Fechando modal de encoding...');
                encodingModal.style.display = 'none';
            }
        }, 1000);
        
        console.log('🎉 === SIDEBAR MOBILE AGRESSIVO CONFIGURADO! ===');
        console.log('🧪 COMANDOS DE TESTE:');
        console.log('   - window.abrirSidebarAgressiva()');
        console.log('   - window.fecharSidebarAgressiva()');
        console.log('   - window.alternarSidebarAgressiva()');
        
        return true;
    }
    
    // EXECUTAR COM MÚLTIPLAS ESTRATÉGIAS AGRESSIVAS
    console.log('⚡ Executando com estratégias múltiplas...');
    
    // Estratégia 1: Imediata
    if (document.readyState !== 'loading') {
        console.log('⚡ DOM pronto - executando imediatamente');
        forcarSidebarMobile();
    }
    
    // Estratégia 2: DOMContentLoaded
    document.addEventListener('DOMContentLoaded', function() {
        console.log('⚡ DOMContentLoaded - executando');
        setTimeout(forcarSidebarMobile, 100);
    });
    
    // Estratégia 3: Load completo
    window.addEventListener('load', function() {
        console.log('⚡ Window load - executando');
        setTimeout(forcarSidebarMobile, 200);
    });
    
    // Estratégias 4-7: Timeouts agressivos
    [500, 1000, 2000, 3000].forEach((delay, index) => {
        setTimeout(() => {
            console.log(`⚡ Timeout ${delay}ms (tentativa ${index + 4})`);
            forcarSidebarMobile();
        }, delay);
    });
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