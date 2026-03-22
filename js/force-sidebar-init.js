// FORÇAR INICIALIZAÇÃO IMEDIATA DA SIDEBAR
(function() {
    'use strict';
    
    console.log('🚀 FORÇA SIDEBAR: Iniciando...');
    
    // Executar imediatamente quando DOM estiver pronto
    function forceSidebarInit() {
        console.log('🚀 FORÇA SIDEBAR: DOM pronto, inicializando...');
        
        setTimeout(function() {
            const toggle = document.querySelector('.sidebar-toggle');
            const sidebar = document.querySelector('.sidebar');
            
            if (!toggle || !sidebar) {
                console.error('❌ FORÇA SIDEBAR: Elementos não encontrados');
                return;
            }
            
            console.log('✅ FORÇA SIDEBAR: Elementos encontrados');
            
            // Aplicar estilos diretamente via JavaScript
            sidebar.style.position = 'fixed';
            sidebar.style.top = '0';
            sidebar.style.left = '0';
            sidebar.style.width = '85vw';
            sidebar.style.maxWidth = '300px';
            sidebar.style.height = '100vh';
            sidebar.style.transform = 'translateX(-100%)';
            sidebar.style.transition = 'transform 0.3s ease';
            sidebar.style.zIndex = '1050';
            sidebar.style.background = 'linear-gradient(180deg, #1e293b 0%, #0f172a 100%)';
            sidebar.style.boxShadow = '4px 0 25px rgba(0, 0, 0, 0.5)';
            
            toggle.style.background = 'transparent';
            toggle.style.border = 'none';
            toggle.style.color = '#64748b';
            toggle.style.fontSize = '1.25rem';
            toggle.style.padding = '0.75rem';
            toggle.style.borderRadius = '10px';
            toggle.style.minWidth = '48px';
            toggle.style.minHeight = '48px';
            toggle.style.display = 'flex';
            toggle.style.alignItems = 'center';
            toggle.style.justifyContent = 'center';
            toggle.style.cursor = 'pointer';
            
            // Criar overlay se não existir
            let overlay = document.querySelector('.mobile-overlay');
            if (!overlay) {
                overlay = document.createElement('div');
                overlay.className = 'mobile-overlay';
                overlay.style.position = 'fixed';
                overlay.style.top = '0';
                overlay.style.left = '0';
                overlay.style.width = '100vw';
                overlay.style.height = '100vh';
                overlay.style.background = 'rgba(0, 0, 0, 0.7)';
                overlay.style.zIndex = '1040';
                overlay.style.display = 'none';
                overlay.style.opacity = '0';
                overlay.style.transition = 'opacity 0.3s ease';
                overlay.style.backdropFilter = 'blur(4px)';
                document.body.appendChild(overlay);
            }
            
            // Função toggle simplificada
            function toggleSidebar() {
                console.log('🔄 FORÇA SIDEBAR: Toggle executado');
                
                if (sidebar.style.transform === 'translateX(0px)' || sidebar.classList.contains('show')) {
                    // Fechar
                    sidebar.style.transform = 'translateX(-100%)';
                    overlay.style.display = 'none';
                    overlay.style.opacity = '0';
                    sidebar.classList.remove('show');
                    overlay.classList.remove('show');
                    document.body.style.overflow = '';
                } else {
                    // Abrir
                    sidebar.style.transform = 'translateX(0px)';
                    overlay.style.display = 'block';
                    overlay.style.opacity = '1';
                    sidebar.classList.add('show');
                    overlay.classList.add('show');
                    document.body.style.overflow = 'hidden';
                }
            }
            
            // Remover todos os event listeners existentes clonando o elemento
            const newToggle = toggle.cloneNode(true);
            toggle.parentNode.replaceChild(newToggle, toggle);
            
            // Adicionar evento
            newToggle.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                toggleSidebar();
            });
            
            newToggle.addEventListener('touchend', function(e) {
                e.preventDefault();
                e.stopPropagation();
                toggleSidebar();
            });
            
            // Evento para fechar com overlay
            overlay.addEventListener('click', function() {
                toggleSidebar();
            });
            
            // Evento para fechar com botão X
            const closeBtn = document.querySelector('.sidebar-close');
            if (closeBtn) {
                closeBtn.addEventListener('click', function() {
                    toggleSidebar();
                });
            }
            
            console.log('✅ FORÇA SIDEBAR: Configurado com sucesso!');
            
        }, 100);
    }
    
    // Executar quando DOM estiver pronto
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', forceSidebarInit);
    } else {
        forceSidebarInit();
    }
    
})();
