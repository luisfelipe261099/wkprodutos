// DEBUG SCRIPT - TESTE DE SIDEBAR
console.log('🐛 Script de debug carregado');

// Mostrar informações sobre os elementos
setTimeout(function() {
    console.log('=== DEBUG SIDEBAR MOBILE ===');
    
    const toggle = document.querySelector('.sidebar-toggle');
    const sidebar = document.querySelector('.sidebar');
    const overlay = document.querySelector('.mobile-overlay');
    const close = document.querySelector('.sidebar-close');
    
    console.log('Toggle button:', {
        exists: !!toggle,
        visible: toggle ? window.getComputedStyle(toggle).display !== 'none' : false,
        position: toggle ? window.getComputedStyle(toggle).position : null,
        zIndex: toggle ? window.getComputedStyle(toggle).zIndex : null,
        clickable: toggle ? !toggle.disabled : false
    });
    
    console.log('Sidebar:', {
        exists: !!sidebar,
        transform: sidebar ? window.getComputedStyle(sidebar).transform : null,
        position: sidebar ? window.getComputedStyle(sidebar).position : null,
        zIndex: sidebar ? window.getComputedStyle(sidebar).zIndex : null,
        width: sidebar ? window.getComputedStyle(sidebar).width : null
    });
    
    console.log('Overlay:', {
        exists: !!overlay,
        display: overlay ? window.getComputedStyle(overlay).display : null,
        position: overlay ? window.getComputedStyle(overlay).position : null,
        zIndex: overlay ? window.getComputedStyle(overlay).zIndex : null
    });
    
    console.log('Window size:', window.innerWidth + 'x' + window.innerHeight);
    console.log('Is mobile?:', window.innerWidth <= 768);
    
    // Teste manual
    if (toggle) {
        console.log('🧪 Para testar manualmente:');
        console.log('- Clique no botão:', toggle);
        
        // Adicionar teste manual ao botão
        window.testSidebar = function() {
            console.log('🧪 Teste manual executado');
            if (sidebar.classList.contains('show')) {
                sidebar.classList.remove('show');
                if (overlay) overlay.classList.remove('show');
                console.log('Sidebar fechada');
            } else {
                sidebar.classList.add('show');
                if (overlay) overlay.classList.add('show');
                console.log('Sidebar aberta');
            }
        };
        
        console.log('Execute: window.testSidebar() no console para testar');
    }
    
    // Adicionar botão de teste visual
    if (window.innerWidth <= 768) {
        const testBtn = document.createElement('button');
        testBtn.innerHTML = '🧪 TEST';
        testBtn.style.cssText = `
            position: fixed !important;
            bottom: 20px !important;
            right: 20px !important;
            z-index: 9999 !important;
            background: #ff0000 !important;
            color: white !important;
            border: none !important;
            padding: 10px !important;
            border-radius: 50px !important;
            font-size: 12px !important;
            cursor: pointer !important;
        `;
        testBtn.onclick = function() {
            window.testSidebar();
        };
        document.body.appendChild(testBtn);
        console.log('🧪 Botão de teste adicionado no canto inferior direito');
    }
    
}, 1000);

// Interceptar todos os eventos de click
document.addEventListener('click', function(e) {
    if (e.target.closest('.sidebar-toggle')) {
        console.log('🖱️ CLICK NO TOGGLE DETECTADO:', e.target);
    }
    if (e.target.closest('.mobile-overlay')) {
        console.log('🖱️ CLICK NO OVERLAY DETECTADO:', e.target);
    }
}, true);

// Interceptar todos os eventos de touch
document.addEventListener('touchstart', function(e) {
    if (e.target.closest('.sidebar-toggle')) {
        console.log('👆 TOUCH NO TOGGLE DETECTADO:', e.target);
    }
}, true);
