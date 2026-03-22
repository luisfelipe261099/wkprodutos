// SCRIPT DE EMERGÊNCIA - SIDEBAR MOBILE
// Este script força o funcionamento do menu mobile

console.log('🔧 Carregando script de emergência para sidebar mobile...');

// Aguardar DOM estar pronto
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initEmergencySidebar);
} else {
    initEmergencySidebar();
}

function initEmergencySidebar() {
    console.log('🚀 Inicializando sidebar de emergência...');
    
    // Aguardar um pouco para garantir que todos os elementos estão carregados
    setTimeout(function() {
        setupSidebarEmergency();
    }, 100);
}

function setupSidebarEmergency() {
    // Encontrar elementos
    const toggle = document.querySelector('.sidebar-toggle') || 
                   document.querySelector('#sidebarToggle') ||
                   document.querySelector('[data-bs-toggle="offcanvas"]');
    
    const sidebar = document.querySelector('.sidebar') || 
                    document.querySelector('#sidebar');
    
    const closeBtn = document.querySelector('.sidebar-close') || 
                     document.querySelector('#sidebarClose');
    
    console.log('Elementos encontrados:', {
        toggle: !!toggle,
        sidebar: !!sidebar,
        closeBtn: !!closeBtn
    });
    
    if (!toggle || !sidebar) {
        console.error('❌ Elementos não encontrados!');
        return;
    }
    
    // Criar overlay se não existir
    let overlay = document.querySelector('.mobile-overlay');
    if (!overlay) {
        overlay = document.createElement('div');
        overlay.className = 'mobile-overlay';
        overlay.style.cssText = `
            position: fixed !important;
            top: 0 !important;
            left: 0 !important;
            width: 100vw !important;
            height: 100vh !important;
            background: rgba(0, 0, 0, 0.7) !important;
            z-index: 1040 !important;
            display: none !important;
            opacity: 0 !important;
            transition: opacity 0.3s ease !important;
            backdrop-filter: blur(4px) !important;
        `;
        document.body.appendChild(overlay);
        console.log('✅ Overlay criado');
    }
    
    // Função para abrir sidebar
    function openSidebar() {
        console.log('🔓 Abrindo sidebar...');
        
        // Aplicar estilos diretamente
        sidebar.style.cssText += `
            position: fixed !important;
            top: 0 !important;
            left: 0 !important;
            width: 85vw !important;
            max-width: 300px !important;
            height: 100vh !important;
            z-index: 1050 !important;
            transform: translateX(0) !important;
            transition: transform 0.3s ease !important;
            background: linear-gradient(180deg, #1e293b 0%, #0f172a 100%) !important;
            box-shadow: 4px 0 25px rgba(0, 0, 0, 0.5) !important;
            overflow-y: auto !important;
        `;
        
        overlay.style.cssText += `
            display: block !important;
            opacity: 1 !important;
        `;
        
        // Adicionar classes
        sidebar.classList.add('show');
        overlay.classList.add('show');
        document.body.classList.add('sidebar-open');
        
        // Prevenir scroll
        document.body.style.overflow = 'hidden';
        document.documentElement.style.overflow = 'hidden';
        
        console.log('✅ Sidebar aberta');
    }
    
    // Função para fechar sidebar
    function closeSidebar() {
        console.log('🔒 Fechando sidebar...');
        
        sidebar.style.transform = 'translateX(-100%)';
        overlay.style.display = 'none';
        overlay.style.opacity = '0';
        
        sidebar.classList.remove('show');
        overlay.classList.remove('show');
        document.body.classList.remove('sidebar-open');
        
        document.body.style.overflow = '';
        document.documentElement.style.overflow = '';
        
        console.log('✅ Sidebar fechada');
    }
    
    // Event listeners - removendo listeners existentes primeiro
    const newToggle = toggle.cloneNode(true);
    toggle.parentNode.replaceChild(newToggle, toggle);
    
    newToggle.addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        console.log('👆 Toggle clicado');
        
        if (sidebar.classList.contains('show')) {
            closeSidebar();
        } else {
            openSidebar();
        }
    });
    
    // Touch events para o toggle
    newToggle.addEventListener('touchstart', function(e) {
        e.preventDefault();
        this.style.transform = 'scale(0.95)';
    });
    
    newToggle.addEventListener('touchend', function(e) {
        e.preventDefault();
        this.style.transform = '';
        
        // Trigger click depois de um delay
        setTimeout(() => {
            if (sidebar.classList.contains('show')) {
                closeSidebar();
            } else {
                openSidebar();
            }
        }, 50);
    });
    
    // Overlay click
    overlay.addEventListener('click', function(e) {
        e.preventDefault();
        console.log('👆 Overlay clicado');
        closeSidebar();
    });
    
    // Close button
    if (closeBtn) {
        closeBtn.addEventListener('click', function(e) {
            e.preventDefault();
            console.log('👆 Close button clicado');
            closeSidebar();
        });
    }
    
    // ESC key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && sidebar.classList.contains('show')) {
            closeSidebar();
        }
    });
    
    // Window resize
    window.addEventListener('resize', function() {
        if (window.innerWidth > 768) {
            closeSidebar();
        }
    });
    
    // Swipe to close
    let startX = 0;
    let currentX = 0;
    let isDragging = false;
    
    sidebar.addEventListener('touchstart', function(e) {
        startX = e.touches[0].clientX;
        isDragging = true;
    }, { passive: true });
    
    sidebar.addEventListener('touchmove', function(e) {
        if (!isDragging) return;
        
        currentX = e.touches[0].clientX;
        const diffX = startX - currentX;
        
        if (diffX > 100) {
            closeSidebar();
            isDragging = false;
        }
    }, { passive: true });
    
    sidebar.addEventListener('touchend', function() {
        isDragging = false;
    }, { passive: true });
    
    console.log('🎉 Sidebar de emergência configurada com sucesso!');
    
    // Aplicar CSS de emergência
    applyEmergencyCSS();
}

function applyEmergencyCSS() {
    const style = document.createElement('style');
    style.innerHTML = `
        /* CSS DE EMERGÊNCIA - SIDEBAR MOBILE */
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
                box-shadow: 4px 0 25px rgba(0, 0, 0, 0.5) !important;
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
                background: rgba(0, 0, 0, 0.7) !important;
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
                transition: all 0.3s ease !important;
                cursor: pointer !important;
                touch-action: manipulation !important;
            }
            
            .sidebar-toggle:hover,
            .sidebar-toggle:active {
                background: rgba(59, 130, 246, 0.15) !important;
                color: #3b82f6 !important;
                transform: scale(1.05) !important;
            }
            
            body.sidebar-open {
                overflow: hidden !important;
            }
            
            .nav-link {
                color: rgba(255, 255, 255, 0.85) !important;
                padding: 1rem !important;
                margin: 0.25rem 0.75rem !important;
                border-radius: 12px !important;
                transition: all 0.3s ease !important;
                min-height: 48px !important;
                display: flex !important;
                align-items: center !important;
            }
            
            .nav-link:hover {
                background: rgba(255, 255, 255, 0.12) !important;
                color: #fff !important;
                transform: translateX(6px) !important;
            }
        }
    `;
    document.head.appendChild(style);
    console.log('🎨 CSS de emergência aplicado');
}
