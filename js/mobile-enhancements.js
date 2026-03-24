// Mobile Enhancement Script - Karla Wollinger System

document.addEventListener('DOMContentLoaded', function() {
    initMobileEnhancements();
    initSidebarMobile();
});

function initSidebarMobile() {
    console.log('Initializing mobile sidebar');
    
    const sidebarToggle = document.querySelector('.sidebar-toggle');
    const sidebar = document.querySelector('.sidebar');
    let overlay = document.querySelector('.mobile-overlay');
    const closeSidebar = document.querySelector('.sidebar-close');
    
    // Criar overlay se não existir
    if (!overlay) {
        overlay = document.createElement('div');
        overlay.className = 'mobile-overlay';
        document.body.appendChild(overlay);
    }
    
    function openSidebar() {
        console.log('Opening sidebar');
        if (sidebar && overlay) {
            sidebar.classList.add('show');
            overlay.classList.add('show');
            document.body.classList.add('sidebar-open');
            document.documentElement.classList.add('sidebar-open');
            
            // Prevenir scroll
            const scrollTop = document.documentElement.scrollTop || document.body.scrollTop;
            document.body.style.top = `-${scrollTop}px`;
            document.body.style.position = 'fixed';
            document.body.style.width = '100%';
        }
    }
    
    function closeSidebarFunction() {
        console.log('Closing sidebar');
        if (sidebar && overlay) {
            sidebar.classList.remove('show');
            overlay.classList.remove('show');
            document.body.classList.remove('sidebar-open');
            document.documentElement.classList.remove('sidebar-open');
            
            // Restaurar scroll
            const scrollTop = parseInt(document.body.style.top || '0') * -1;
            document.body.style.position = '';
            document.body.style.top = '';
            document.body.style.width = '';
            document.documentElement.scrollTop = scrollTop;
            document.body.scrollTop = scrollTop;
        }
    }
    
    // Toggle button
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            console.log('Sidebar toggle clicked');
            
            if (sidebar && sidebar.classList.contains('show')) {
                closeSidebarFunction();
            } else {
                openSidebar();
            }
        });

        // Touch feedback
        sidebarToggle.addEventListener('touchstart', function(e) {
            this.style.transform = 'scale(0.95)';
        }, { passive: true });
        
        sidebarToggle.addEventListener('touchend', function(e) {
            setTimeout(() => {
                this.style.transform = '';
            }, 150);
        }, { passive: true });
    }
    
    // Fechar com overlay
    if (overlay) {
        overlay.addEventListener('click', function(e) {
            e.preventDefault();
            closeSidebarFunction();
        });
    }
    
    // Fechar com botão X
    if (closeSidebar) {
        closeSidebar.addEventListener('click', function(e) {
            e.preventDefault();
            closeSidebarFunction();
        });
        
        // Touch feedback
        closeSidebar.addEventListener('touchstart', function(e) {
            this.style.transform = 'scale(0.95)';
        }, { passive: true });
        
        closeSidebar.addEventListener('touchend', function(e) {
            setTimeout(() => {
                this.style.transform = '';
            }, 150);
        }, { passive: true });
    }
    
    // Fechar ao pressionar ESC
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && sidebar && sidebar.classList.contains('show')) {
            closeSidebarFunction();
        }
    });
    
    // Resetar ao redimensionar
    window.addEventListener('resize', function() {
        if (window.innerWidth > 768 && sidebar) {
            closeSidebarFunction();
        }
    });

    // Swipe to close
    let startX = 0;
    let currentX = 0;
    let isDragging = false;

    if (sidebar) {
        sidebar.addEventListener('touchstart', function(e) {
            startX = e.touches[0].clientX;
            isDragging = true;
        }, { passive: true });

        sidebar.addEventListener('touchmove', function(e) {
            if (!isDragging) return;
            
            currentX = e.touches[0].clientX;
            const diffX = startX - currentX;
            
            // Swipe da direita para esquerda
            if (diffX > 100) {
                closeSidebarFunction();
                isDragging = false;
            }
        }, { passive: true });

        sidebar.addEventListener('touchend', function() {
            isDragging = false;
        }, { passive: true });
    }
}

function initMobileEnhancements() {
    // Detectar se é dispositivo móvel
    const isMobile = window.innerWidth <= 768;
    const isTablet = window.innerWidth > 768 && window.innerWidth <= 1024;
    const isTouchDevice = ('ontouchstart' in window) || (navigator.maxTouchPoints > 0);

    if (isMobile || isTablet || isTouchDevice) {
        // Melhorar experiência de toque
        improveTouchExperience();
        
        // Otimizar formulários para mobile
        optimizeForms();
        
        // Melhorar tabelas para mobile
        enhanceTables();
        
        // Adicionar gestos swipe
        addSwipeGestures();
        
        // Melhorar modais
        enhanceModals();
    }

    // Ajustar viewport dinamicamente
    adjustViewport();
}

function improveTouchExperience() {
    // Adicionar feedback visual para toques
    const touchElements = document.querySelectorAll('.btn, .nav-link, [role="button"]');
    
    touchElements.forEach(element => {
        element.addEventListener('touchstart', function() {
            this.style.opacity = '0.7';
        }, { passive: true });
        
        element.addEventListener('touchend', function() {
            this.style.opacity = '';
        }, { passive: true });
        
        element.addEventListener('touchcancel', function() {
            this.style.opacity = '';
        }, { passive: true });
    });
}

function optimizeForms() {
    // Ajustar inputs para melhor experiência mobile
    const inputs = document.querySelectorAll('input, textarea, select');
    
    inputs.forEach(input => {
        // Ajustar tipo de teclado
        if (input.type === 'email') {
            input.setAttribute('inputmode', 'email');
        } else if (input.type === 'tel') {
            input.setAttribute('inputmode', 'tel');
        } else if (input.type === 'number') {
            input.setAttribute('inputmode', 'numeric');
        }
        
        // Melhorar foco em mobile
        input.addEventListener('focus', function() {
            // Scroll suave para o campo em foco
            setTimeout(() => {
                this.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }, 300);
        });
    });
    
    // Nao inicializa Select2 globalmente em todos os selects.
    // As telas que precisam desse comportamento devem configurar explicitamente.
}

function enhanceTables() {
    const tables = document.querySelectorAll('.table');
    
    tables.forEach(table => {
        const tbody = table.querySelector('tbody');
        if (!tbody) return;
        
        const rows = tbody.querySelectorAll('tr');
        
        rows.forEach((row, index) => {
            // Adicionar animação de entrada
            row.style.animationDelay = `${index * 50}ms`;
            row.classList.add('fade-in-up');
            
            // Melhorar interação em mobile
            if (window.innerWidth <= 768) {
                row.addEventListener('click', function(e) {
                    // Se clicou em um botão, não fazer nada
                    if (e.target.closest('.btn, button, a')) return;
                    
                    // Destacar linha clicada
                    rows.forEach(r => r.classList.remove('table-row-selected'));
                    this.classList.add('table-row-selected');
                });
            }
        });
    });
}

function addSwipeGestures() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('mobileOverlay');
    
    if (!sidebar || !overlay) return;
    
    let startX = 0;
    let startY = 0;
    let deltaX = 0;
    let deltaY = 0;
    
    // Swipe para abrir sidebar
    document.addEventListener('touchstart', function(e) {
        startX = e.touches[0].clientX;
        startY = e.touches[0].clientY;
    });
    
    document.addEventListener('touchmove', function(e) {
        if (!startX || !startY) return;
        
        deltaX = e.touches[0].clientX - startX;
        deltaY = e.touches[0].clientY - startY;
    });
    
    document.addEventListener('touchend', function(e) {
        if (!startX || !startY) return;
        
        // Swipe da borda esquerda para abrir sidebar
        if (startX < 30 && deltaX > 100 && Math.abs(deltaY) < 100) {
            if (!sidebar.classList.contains('show')) {
                const event = new Event('click');
                document.getElementById('sidebarToggle').dispatchEvent(event);
            }
        }
        
        // Swipe para a esquerda para fechar sidebar
        if (sidebar.classList.contains('show') && deltaX < -100 && Math.abs(deltaY) < 100) {
            const event = new Event('click');
            overlay.dispatchEvent(event);
        }
        
        // Reset
        startX = 0;
        startY = 0;
        deltaX = 0;
        deltaY = 0;
    });
}

function enhanceModals() {
    const modals = document.querySelectorAll('.modal');
    
    modals.forEach(modal => {
        modal.addEventListener('shown.bs.modal', function() {
            // Focar primeiro input quando modal abrir
            const firstInput = this.querySelector('input, textarea, select');
            if (firstInput && window.innerWidth > 768) {
                setTimeout(() => firstInput.focus(), 100);
            }
        });
        
        // Melhorar scroll em modais mobile
        const modalBody = modal.querySelector('.modal-body');
        if (modalBody && window.innerWidth <= 768) {
            modalBody.style.maxHeight = 'calc(100vh - 200px)';
            modalBody.style.overflowY = 'auto';
            modalBody.style.webkitOverflowScrolling = 'touch';
        }
    });
}

function adjustViewport() {
    // Fix para altura do viewport em iOS
    function setViewportHeight() {
        const vh = window.innerHeight * 0.01;
        document.documentElement.style.setProperty('--vh', `${vh}px`);
    }
    
    setViewportHeight();
    window.addEventListener('resize', setViewportHeight);
    window.addEventListener('orientationchange', function() {
        setTimeout(setViewportHeight, 300);
    });
}

// Adicionar classe CSS para altura de viewport dinâmica
const style = document.createElement('style');
style.textContent = `
    .mobile-full-height {
        height: 100vh;
        height: calc(var(--vh, 1vh) * 100);
    }
`;
document.head.appendChild(style);

// Melhorar performance em dispositivos móveis
if (window.innerWidth <= 768) {
    // Desabilitar animações complexas em dispositivos com menos performance
    const isLowEndDevice = navigator.hardwareConcurrency <= 2 || 
                          navigator.deviceMemory <= 2 || 
                          /Android.*Chrome\/([0-6]\d|7[0-5])|iPhone.*OS\s([0-9]|1[0-2])/.test(navigator.userAgent);
    
    if (isLowEndDevice) {
        document.documentElement.classList.add('reduce-motion');
        
        const reduceMotionStyle = document.createElement('style');
        reduceMotionStyle.textContent = `
            .reduce-motion *,
            .reduce-motion *::before,
            .reduce-motion *::after {
                animation-duration: 0.01ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: 0.01ms !important;
            }
        `;
        document.head.appendChild(reduceMotionStyle);
    }
}

// Adicionar suporte a pull-to-refresh customizado
let pullToRefreshEnabled = false;
let startY = 0;
let pullDistance = 0;

document.addEventListener('touchstart', function(e) {
    if (window.scrollY === 0) {
        startY = e.touches[0].clientY;
        pullToRefreshEnabled = true;
    }
});

document.addEventListener('touchmove', function(e) {
    if (!pullToRefreshEnabled) return;
    
    pullDistance = e.touches[0].clientY - startY;
    
    if (pullDistance > 50 && window.scrollY === 0) {
        // Visual feedback para pull to refresh
        document.body.style.transform = `translateY(${Math.min(pullDistance * 0.3, 30)}px)`;
        document.body.style.opacity = Math.max(1 - (pullDistance * 0.005), 0.7);
    }
});

document.addEventListener('touchend', function(e) {
    if (pullToRefreshEnabled && pullDistance > 80 && window.scrollY === 0) {
        // Trigger refresh
        window.location.reload();
    }
    
    // Reset
    document.body.style.transform = '';
    document.body.style.opacity = '';
    pullToRefreshEnabled = false;
    pullDistance = 0;
    startY = 0;
});
