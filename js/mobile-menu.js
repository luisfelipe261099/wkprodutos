/**
 * WKPRODUTOS - Sistema de Menu Mobile
 * JavaScript limpo e eficiente para responsividade
 */

class MobileMenu {
    constructor() {
        this.sidebar = null;
        this.toggle = null;
        this.overlay = null;
        this.isOpen = false;
        
        this.init();
    }
    
    init() {
        // Aguardar DOM carregar
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => this.setup());
        } else {
            this.setup();
        }
    }
    
    setup() {
        // Encontrar elementos
        this.sidebar = document.querySelector('.sidebar');
        this.toggle = document.querySelector('.sidebar-toggle');
        
        if (!this.sidebar || !this.toggle) {
            console.warn('Elementos da sidebar não encontrados');
            return;
        }
        
        // Criar overlay
        this.createOverlay();
        
        // Configurar eventos
        this.bindEvents();
        
        console.log('✅ Menu mobile configurado');
    }
    
    createOverlay() {
        // Verificar se já existe
        this.overlay = document.querySelector('.mobile-overlay');
        
        if (!this.overlay) {
            this.overlay = document.createElement('div');
            this.overlay.className = 'mobile-overlay';
            document.body.appendChild(this.overlay);
        }
    }
    
    bindEvents() {
        // Toggle button
        this.toggle.addEventListener('click', (e) => {
            e.preventDefault();
            this.toggleSidebar();
        });
        
        // Overlay click
        this.overlay.addEventListener('click', () => {
            this.closeSidebar();
        });
        
        // ESC key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.isOpen) {
                this.closeSidebar();
            }
        });
        
        // Window resize
        window.addEventListener('resize', () => {
            if (window.innerWidth > 768 && this.isOpen) {
                this.closeSidebar();
            }
        });
        
        // Touch events para melhor responsividade
        this.toggle.addEventListener('touchend', (e) => {
            e.preventDefault();
            this.toggleSidebar();
        });
    }
    
    openSidebar() {
        this.sidebar.classList.add('show');
        this.overlay.classList.add('show');
        document.body.style.overflow = 'hidden';
        this.isOpen = true;
        
        console.log('📱 Sidebar aberta');
    }
    
    closeSidebar() {
        this.sidebar.classList.remove('show');
        this.overlay.classList.remove('show');
        document.body.style.overflow = '';
        this.isOpen = false;
        
        console.log('📱 Sidebar fechada');
    }
    
    toggleSidebar() {
        if (this.isOpen) {
            this.closeSidebar();
        } else {
            this.openSidebar();
        }
    }
}

// Inicializar quando carregar
const mobileMenu = new MobileMenu();

// Expor métodos globalmente para debug
window.mobileMenu = mobileMenu;
