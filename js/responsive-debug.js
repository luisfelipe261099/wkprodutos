// Teste de Responsividade - Debug Script
// Karla Wollinger System

document.addEventListener('DOMContentLoaded', function() {
    // Debug responsividade
    if (window.location.search.includes('debug=1')) {
        createResponsiveDebugger();
    }
});

function createResponsiveDebugger() {
    // Criar painel de debug
    const debugPanel = document.createElement('div');
    debugPanel.id = 'responsive-debugger';
    debugPanel.style.cssText = `
        position: fixed;
        top: 10px;
        right: 10px;
        background: rgba(0,0,0,0.9);
        color: white;
        padding: 15px;
        border-radius: 8px;
        font-family: monospace;
        font-size: 12px;
        z-index: 99999;
        max-width: 300px;
        backdrop-filter: blur(10px);
    `;
    
    debugPanel.innerHTML = `
        <div style="font-weight: bold; margin-bottom: 10px;">📱 Debug Responsivo</div>
        <div id="debug-info"></div>
        <button onclick="this.parentElement.style.display='none'" style="margin-top: 10px; padding: 5px 10px; background: #dc3545; color: white; border: none; border-radius: 4px; cursor: pointer;">Fechar</button>
    `;
    
    document.body.appendChild(debugPanel);
    
    // Atualizar informações
    function updateDebugInfo() {
        const info = document.getElementById('debug-info');
        const viewport = {
            width: window.innerWidth,
            height: window.innerHeight
        };
        
        let breakpoint = 'Desktop';
        if (viewport.width <= 575.98) breakpoint = 'Mobile Small';
        else if (viewport.width <= 767.98) breakpoint = 'Mobile';
        else if (viewport.width <= 1023.98) breakpoint = 'Tablet';
        else if (viewport.width <= 1199.98) breakpoint = 'Desktop Small';
        
        const sidebar = document.getElementById('sidebar');
        const sidebarVisible = sidebar ? getComputedStyle(sidebar).transform !== 'matrix(1, 0, 0, 1, -280, 0)' : false;
        
        info.innerHTML = `
            <div><strong>Viewport:</strong> ${viewport.width} x ${viewport.height}</div>
            <div><strong>Breakpoint:</strong> ${breakpoint}</div>
            <div><strong>Sidebar Visível:</strong> ${sidebarVisible ? 'Sim' : 'Não'}</div>
            <div><strong>Touch Device:</strong> ${'ontouchstart' in window ? 'Sim' : 'Não'}</div>
            <div><strong>User Agent:</strong> ${navigator.userAgent.includes('Mobile') ? 'Mobile' : 'Desktop'}</div>
            <div style="margin-top: 8px; padding-top: 8px; border-top: 1px solid #666;">
                <div><strong>Problemas Detectados:</strong></div>
                <div id="debug-issues"></div>
            </div>
        `;
        
        // Verificar problemas
        checkResponsiveIssues();
    }
    
    function checkResponsiveIssues() {
        const issues = [];
        const issuesContainer = document.getElementById('debug-issues');
        
        // Verificar se sidebar está funcionando
        const sidebarToggle = document.getElementById('sidebarToggle');
        if (!sidebarToggle) {
            issues.push('❌ Botão toggle do sidebar não encontrado');
        }
        
        // Verificar overlay
        const overlay = document.getElementById('mobileOverlay');
        if (!overlay && window.innerWidth < 1024) {
            issues.push('❌ Overlay mobile não encontrado');
        }
        
        // Verificar tabelas
        const tables = document.querySelectorAll('.table');
        if (tables.length > 0 && window.innerWidth < 768) {
            let hasDataLabels = false;
            tables.forEach(table => {
                const cells = table.querySelectorAll('td[data-label]');
                if (cells.length > 0) hasDataLabels = true;
            });
            if (!hasDataLabels) {
                issues.push('❌ Tabelas sem data-label para mobile');
            }
        }
        
        // Verificar CSS mobile
        const testElement = document.createElement('div');
        testElement.className = 'mobile-agendamento-actions';
        testElement.style.display = 'none';
        document.body.appendChild(testElement);
        
        const styles = getComputedStyle(testElement);
        if (window.innerWidth < 768 && styles.flexDirection !== 'column') {
            issues.push('❌ CSS mobile não carregado corretamente');
        }
        
        document.body.removeChild(testElement);
        
        // Verificar inputs
        const inputs = document.querySelectorAll('input, select, textarea');
        let smallInputs = 0;
        inputs.forEach(input => {
            const rect = input.getBoundingClientRect();
            if (rect.height < 44 && window.innerWidth < 768) {
                smallInputs++;
            }
        });
        
        if (smallInputs > 0) {
            issues.push(`⚠️ ${smallInputs} inputs muito pequenos para touch`);
        }
        
        // Exibir resultados
        if (issues.length === 0) {
            issuesContainer.innerHTML = '<div style="color: #28a745;">✅ Nenhum problema detectado!</div>';
        } else {
            issuesContainer.innerHTML = issues.map(issue => `<div style="color: #dc3545; margin: 2px 0;">${issue}</div>`).join('');
        }
    }
    
    // Atualizar a cada resize
    updateDebugInfo();
    window.addEventListener('resize', updateDebugInfo);
    
    // Teste do menu
    const testButton = document.createElement('button');
    testButton.textContent = 'Testar Menu';
    testButton.style.cssText = 'margin-top: 10px; padding: 5px 10px; background: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer; width: 100%;';
    testButton.onclick = function() {
        const toggle = document.getElementById('sidebarToggle');
        if (toggle) {
            toggle.click();
            setTimeout(() => {
                updateDebugInfo();
            }, 300);
        }
    };
    debugPanel.appendChild(testButton);
}

// Adicionar parâmetro de debug na URL para ativar
console.log('🔧 Para ativar o debug de responsividade, adicione ?debug=1 na URL');

// Verificações automáticas
window.addEventListener('load', function() {
    // Verificar se CSS está carregado
    const testDiv = document.createElement('div');
    testDiv.className = 'sidebar';
    testDiv.style.display = 'none';
    document.body.appendChild(testDiv);
    
    const styles = getComputedStyle(testDiv);
    if (!styles.width || styles.width === 'auto') {
        console.warn('⚠️ CSS do sidebar pode não estar carregado');
    }
    
    document.body.removeChild(testDiv);
    
    // Verificar JavaScript do menu
    const toggle = document.getElementById('sidebarToggle');
    if (toggle && typeof toggle.onclick !== 'function' && !toggle.getAttribute('data-listeners')) {
        console.warn('⚠️ JavaScript do menu pode não estar funcionando');
    }
});

// Função utilitária para testar responsividade
window.testResponsive = function(width) {
    // Simular resize para testar breakpoints
    Object.defineProperty(window, 'innerWidth', {
        writable: true,
        configurable: true,
        value: width
    });
    window.dispatchEvent(new Event('resize'));
    console.log(`📱 Testando com largura: ${width}px`);
};
