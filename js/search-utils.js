/**
 * Utilitários de Busca Reutilizáveis
 * Sistema de busca em tempo real para tabelas e cards mobile
 */

/**
 * Inicializa a funcionalidade de busca para uma página
 * @param {Object} config - Configuração da busca
 * @param {string} config.inputId - ID do input de busca (padrão: 'searchInput')
 * @param {string} config.tableId - ID da tabela para busca
 * @param {string} config.mobileCardsSelector - Seletor CSS para cards mobile
 * @param {Array} config.searchColumns - Índices das colunas para buscar (padrão: todas exceto a última)
 * @param {boolean} config.caseSensitive - Busca sensível a maiúsculas/minúsculas (padrão: false)
 */
function initializeSearch(config = {}) {
    const {
        inputId = 'searchInput',
        tableId,
        mobileCardsSelector,
        searchColumns = null,
        caseSensitive = false
    } = config;

    const searchInput = document.getElementById(inputId);
    if (!searchInput) {
        console.warn(`Search input with ID '${inputId}' not found`);
        return;
    }

    // Função de busca principal
    function performSearch() {
        const filter = caseSensitive ? searchInput.value : searchInput.value.toLowerCase();
        
        // Buscar na tabela desktop
        if (tableId) {
            searchInTable(tableId, filter, searchColumns, caseSensitive);
        }
        
        // Buscar nos cards mobile
        if (mobileCardsSelector) {
            searchInMobileCards(mobileCardsSelector, filter, caseSensitive);
        }
    }

    // Event listeners
    searchInput.addEventListener('keyup', performSearch);
    searchInput.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            this.value = '';
            performSearch();
        }
    });

    // Limpar busca ao focar (opcional)
    searchInput.addEventListener('focus', function() {
        this.select();
    });
}

/**
 * Busca em tabela desktop
 * @param {string} tableId - ID da tabela
 * @param {string} filter - Termo de busca
 * @param {Array} searchColumns - Colunas para buscar
 * @param {boolean} caseSensitive - Sensível a maiúsculas/minúsculas
 */
function searchInTable(tableId, filter, searchColumns, caseSensitive) {
    const table = document.getElementById(tableId);
    if (!table) return;

    const rows = table.getElementsByTagName('tr');
    
    for (let i = 1; i < rows.length; i++) { // Pula o cabeçalho
        const cells = rows[i].getElementsByTagName('td');
        let found = false;

        // Determinar colunas para buscar
        const columnsToSearch = searchColumns || Array.from({length: cells.length - 1}, (_, i) => i);
        
        for (let j of columnsToSearch) {
            if (cells[j]) {
                const cellText = caseSensitive ? cells[j].textContent : cells[j].textContent.toLowerCase();
                if (cellText.includes(filter)) {
                    found = true;
                    break;
                }
            }
        }

        rows[i].style.display = found || filter === '' ? '' : 'none';
    }
}

/**
 * Busca em cards mobile
 * @param {string} selector - Seletor CSS dos cards
 * @param {string} filter - Termo de busca
 * @param {boolean} caseSensitive - Sensível a maiúsculas/minúsculas
 */
function searchInMobileCards(selector, filter, caseSensitive) {
    const cards = document.querySelectorAll(selector);
    
    cards.forEach(card => {
        const cardText = caseSensitive ? card.textContent : card.textContent.toLowerCase();
        card.style.display = cardText.includes(filter) || filter === '' ? '' : 'none';
    });
}

/**
 * Cria o HTML da barra de busca
 * @param {Object} options - Opções da barra de busca
 * @param {string} options.placeholder - Placeholder do input
 * @param {string} options.inputId - ID do input (padrão: 'searchInput')
 * @param {string} options.containerClass - Classes CSS do container
 * @returns {string} HTML da barra de busca
 */
function createSearchBarHTML(options = {}) {
    const {
        placeholder = 'Buscar...',
        inputId = 'searchInput',
        containerClass = 'input-group'
    } = options;

    return `
        <div class="${containerClass}" style="max-width: 300px;">
            <input type="text" class="form-control" placeholder="${placeholder}" id="${inputId}">
            <button class="btn btn-outline-primary" type="button">
                <i class="fas fa-search"></i>
            </button>
        </div>
    `;
}

/**
 * Adiciona contador de resultados
 * @param {string} tableId - ID da tabela
 * @param {string} counterId - ID do elemento contador
 */
function updateResultsCounter(tableId, counterId) {
    const table = document.getElementById(tableId);
    const counter = document.getElementById(counterId);
    
    if (!table || !counter) return;
    
    const rows = table.getElementsByTagName('tr');
    let visibleCount = 0;
    
    for (let i = 1; i < rows.length; i++) { // Pula o cabeçalho
        if (rows[i].style.display !== 'none') {
            visibleCount++;
        }
    }
    
    counter.textContent = `${visibleCount} resultado${visibleCount !== 1 ? 's' : ''}`;
}

/**
 * Destaca termos de busca nos resultados
 * @param {string} tableId - ID da tabela
 * @param {string} searchTerm - Termo a destacar
 */
function highlightSearchTerms(tableId, searchTerm) {
    if (!searchTerm) return;
    
    const table = document.getElementById(tableId);
    if (!table) return;
    
    const rows = table.getElementsByTagName('tr');
    
    for (let i = 1; i < rows.length; i++) {
        const cells = rows[i].getElementsByTagName('td');
        
        for (let cell of cells) {
            const originalText = cell.textContent;
            const regex = new RegExp(`(${searchTerm})`, 'gi');
            const highlightedText = originalText.replace(regex, '<mark>$1</mark>');
            
            if (highlightedText !== originalText) {
                cell.innerHTML = highlightedText;
            }
        }
    }
}

// Exportar funções para uso global
window.SearchUtils = {
    initializeSearch,
    searchInTable,
    searchInMobileCards,
    createSearchBarHTML,
    updateResultsCounter,
    highlightSearchTerms
};
