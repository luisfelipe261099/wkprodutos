<?php
require_once 'includes/session_bootstrap.php';

// Verifica se o usuÃ¡rio estÃ¡ logado
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: index.php");
    exit;
}

require_once 'includes/db_connect.php';

$message = '';
$message_type = '';

// --- LÃ³gica para ExclusÃ£o SEGURA de Produto ---
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $produto_id_to_delete = intval($_GET['id']);

    // 1. VERIFICAR DEPENDÃŠNCIAS ANTES DE EXCLUIR
    $sql_check_vendas = "SELECT COUNT(*) FROM itens_venda WHERE produto_id = ?";
    $stmt_check_vendas = $conn->prepare($sql_check_vendas);
    $stmt_check_vendas->bind_param("i", $produto_id_to_delete);
    $stmt_check_vendas->execute();
    $vendas_count = $stmt_check_vendas->get_result()->fetch_row()[0];

    $sql_check_orcamentos = "SELECT COUNT(*) FROM itens_orcamento WHERE produto_id = ?";
    $stmt_check_orcamentos = $conn->prepare($sql_check_orcamentos);
    $stmt_check_orcamentos->bind_param("i", $produto_id_to_delete);
    $stmt_check_orcamentos->execute();
    $orcamentos_count = $stmt_check_orcamentos->get_result()->fetch_row()[0];

    if ($vendas_count > 0 || $orcamentos_count > 0) {
        // Se houver dependÃªncias, impede a exclusÃ£o
        $message = "Este produto nÃ£o pode ser excluÃ­do, pois estÃ¡ associado a vendas ou orÃ§amentos existentes.";
        $message_type = "danger";
    } else {
        // Se nÃ£o houver dependÃªncias, procede com a exclusÃ£o
        $sql_delete = "DELETE FROM produtos WHERE id = ?";
        if ($stmt_delete = $conn->prepare($sql_delete)) {
            $stmt_delete->bind_param("i", $produto_id_to_delete);
            if ($stmt_delete->execute()) {
                $message = "Produto excluÃ­do com sucesso!";
                $message_type = "success";
            } else {
                $message = "Erro ao excluir o produto: " . $stmt_delete->error;
                $message_type = "danger";
            }
            $stmt_delete->close();
        }
    }
}

// --- LÃ³gica para buscar todos os produtos com PAGINAÃ‡ÃƒO E FILTROS ---
$itens_por_pagina = 15; // Defina quantos itens por pÃ¡gina
$pagina_atual = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($pagina_atual - 1) * $itens_por_pagina;

// Filtro por fornecedor
$filtro_fornecedor = isset($_GET['fornecedor']) ? trim($_GET['fornecedor']) : '';

// Filtro por busca (nome do produto)
$filtro_busca = isset($_GET['search']) ? trim($_GET['search']) : '';

// Construir WHERE clause para filtros
$where_conditions = [];
$params = [];
$types = "";

if (!empty($filtro_fornecedor)) {
    $where_conditions[] = "fornecedor = ?";
    $params[] = $filtro_fornecedor;
    $types .= "s";
}

// Adicionar filtro de busca por nome
if (!empty($filtro_busca)) {
    $where_conditions[] = "(nome LIKE ? OR sku LIKE ? OR fornecedor LIKE ?)";
    $search_term = "%{$filtro_busca}%";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
    $types .= "sss";
}

$where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Contar o total de produtos para a paginaÃ§Ã£o (com filtros)
$sql_total_produtos = "SELECT COUNT(*) AS total FROM produtos $where_clause";
$stmt_total = $conn->prepare($sql_total_produtos);
if (!empty($types)) {
    $stmt_total->bind_param($types, ...$params);
}
$stmt_total->execute();
$result_total = $stmt_total->get_result();
$total_produtos_db = $result_total->fetch_assoc()['total'];
$total_paginas = ceil($total_produtos_db / $itens_por_pagina);

// Buscar produtos com filtros
$sql_select_produtos = "SELECT id, nome, sku, percentual_lucro, preco_venda, quantidade_estoque, estoque_minimo, fornecedor
                        FROM produtos
                        $where_clause
                        ORDER BY nome ASC
                        LIMIT ? OFFSET ?";

// Adicionar parÃ¢metros de paginaÃ§Ã£o
$params[] = $itens_por_pagina;
$params[] = $offset;
$types .= "ii";

$stmt_produtos = $conn->prepare($sql_select_produtos);
$stmt_produtos->bind_param($types, ...$params);
$stmt_produtos->execute();
$result_produtos = $stmt_produtos->get_result();

// Buscar lista de fornecedores para o dropdown
$sql_fornecedores = "SELECT DISTINCT fornecedor FROM produtos WHERE fornecedor IS NOT NULL AND fornecedor != '' ORDER BY fornecedor ASC";
$result_fornecedores = $conn->query($sql_fornecedores);
$fornecedores_list = [];
if ($result_fornecedores && $result_fornecedores->num_rows > 0) {
    while ($row = $result_fornecedores->fetch_assoc()) {
        $fornecedores_list[] = $row['fornecedor'];
    }
}

// Carregar todos os dados em um array para reutilizaÃ§Ã£o
$produtos_data = ($result_produtos->num_rows > 0) ? $result_produtos->fetch_all(MYSQLI_ASSOC) : [];

// Calcular estatÃ­sticas (AGORA COM BASE NOS DADOS DA PÃGINA ATUAL, ou manter global se necessÃ¡rio)
// Para estatÃ­sticas globais, vocÃª precisaria de outra query sem paginaÃ§Ã£o ou usar $total_produtos_db
$total_produtos_na_pagina = count($produtos_data); // Isso Ã© o total na pÃ¡gina atual

// As estatÃ­sticas de cards (Total de Produtos, Estoque CrÃ­tico, etc.) devem usar os totais globais.
// Vamos recalcular as estatÃ­sticas globais para os cards, pois a query principal agora Ã© paginada.
$sql_stats_globais = "SELECT quantidade_estoque, estoque_minimo, preco_venda, fornecedor FROM produtos";
$result_stats_globais = $conn->query($sql_stats_globais);
$criticos = 0;
$valor_total_estoque = 0;
$fornecedores_unicos = [];
if ($result_stats_globais->num_rows > 0) {
    while($produto_stat = $result_stats_globais->fetch_assoc()) {
        if ($produto_stat['quantidade_estoque'] <= $produto_stat['estoque_minimo']) {
            $criticos++;
        }
        $valor_total_estoque += $produto_stat['preco_venda'] * $produto_stat['quantidade_estoque'];
        if (!empty($produto_stat['fornecedor']) && !in_array($produto_stat['fornecedor'], $fornecedores_unicos)) {
            $fornecedores_unicos[] = $produto_stat['fornecedor'];
        }
    }
}

include_once 'includes/header.php';
?>

<div class="page-header fade-in-up">
    <h1 class="page-title"><i class="fas fa-box"></i> Gerenciamento de Produtos</h1>
    <p class="page-subtitle">Controle completo do seu catÃ¡logo e estoque.</p>
</div>

<?php if (!empty($message)): ?>
    <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show fade-in-up" role="alert">
        <i class="fas fa-<?php echo $message_type == 'success' ? 'check-circle' : 'exclamation-triangle'; ?> me-2"></i>
        <?php echo $message; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="row g-4 mb-4">
    <div class="col-6 col-lg-3"><div class="stats-card primary fade-in-up"><div class="stats-icon primary"><i class="fas fa-boxes"></i></div><div class="stats-value"><?php echo $total_produtos_db; ?></div><div class="stats-label">Total de Produtos</div></div></div>
    <div class="col-6 col-lg-3"><div class="stats-card danger fade-in-up"><div class="stats-icon danger"><i class="fas fa-exclamation-triangle"></i></div><div class="stats-value"><?php echo $criticos; ?></div><div class="stats-label">Estoque CrÃ­tico</div></div></div>
    <div class="col-6 col-lg-3"><div class="stats-card success fade-in-up"><div class="stats-icon success"><i class="fas fa-dollar-sign"></i></div><div class="stats-value"><?php echo fmt_brl($valor_total_estoque); ?></div><div class="stats-label">Valor em Estoque</div></div></div>
    <div class="col-6 col-lg-3"><div class="stats-card info fade-in-up"><div class="stats-icon info"><i class="fas fa-truck"></i></div><div class="stats-value"><?php echo count($fornecedores_unicos); ?></div><div class="stats-label">Fornecedores</div></div></div>
</div>

<div class="modern-card fade-in-up">
    <div class="card-header-modern d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div class="d-flex align-items-center">
            <span><i class="fas fa-list"></i> Lista de Produtos</span>
            <div class="ms-auto">
                <span class="badge bg-primary"><?php echo $total_produtos_db; ?> produtos</span>
            </div>
        </div>
        <div class="d-flex gap-2 flex-grow-1 flex-md-grow-0">
            <div class="input-group" style="max-width: 400px;">
                <input type="text" class="form-control" placeholder="Buscar por nome, cÃ³digo, fornecedor ou qualquer termo..." id="searchInput" autocomplete="off">
                <button class="btn btn-outline-primary" type="button" onclick="document.getElementById('searchInput').focus()">
                    <i class="fas fa-search"></i>
                </button>
                <button class="btn btn-outline-secondary" type="button" onclick="clearSearch()" title="Limpar busca">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="d-none d-md-block">
                <span class="badge bg-secondary" style="font-size: 0.8em; padding: 8px 12px;">
                    <?php echo $total_produtos_db; ?> resultado<?php echo $total_produtos_db !== 1 ? 's' : ''; ?>
                </span>
            </div>
            <div class="dropdown">
                <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="fas fa-filter me-1"></i>
                    <?php echo !empty($filtro_fornecedor) ? htmlspecialchars($filtro_fornecedor) : 'Fornecedor'; ?>
                </button>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item" href="?<?php echo http_build_query(array_merge($_GET, ['fornecedor' => '', 'page' => 1])); ?>">
                        <i class="fas fa-times me-2"></i>Todos os fornecedores
                    </a></li>
                    <li><hr class="dropdown-divider"></li>
                    <?php foreach ($fornecedores_list as $fornecedor): ?>
                        <li><a class="dropdown-item <?php echo ($filtro_fornecedor === $fornecedor) ? 'active' : ''; ?>"
                               href="?<?php echo http_build_query(array_merge($_GET, ['fornecedor' => $fornecedor, 'page' => 1])); ?>">
                            <i class="fas fa-building me-2"></i><?php echo htmlspecialchars($fornecedor); ?>
                        </a></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
        <a href="cadastro_produto.php" class="btn btn-primary"><i class="fas fa-plus me-2"></i> Novo Produto</a>
    </div>
    <div class="card-body-modern">
        <?php if (!empty($produtos_data)): ?>
            <div class="table-responsive d-none d-lg-block">
                <table class="table table-hover mb-0" id="produtosTable">
                    <thead>
                        <tr>
                            <th>Produto</th>
                            <th>SKU</th>
                            <th>PreÃ§o / Lucro</th>
                            <th class="text-center">Estoque</th>
                            <th>Fornecedor</th>
                            <th class="text-center">AÃ§Ãµes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($produtos_data as $row): ?>
                            <?php $estoque_baixo = $row['quantidade_estoque'] <= $row['estoque_minimo']; ?>
                            <tr>
                                <td>
                                    <div class="fw-semibold"><?php echo htmlspecialchars($row['nome']); ?></div>
                                    <small class="text-muted">#<?php echo $row['id']; ?></small>
                                </td>
                                <td><code class="bg-light px-2 py-1 rounded"><?php echo htmlspecialchars($row['sku'] ?: 'N/A'); ?></code></td>
                                <td>
                                    <div class="fw-semibold text-success">R$ <?php echo number_format($row['preco_venda'], 2, ',', '.'); ?></div>
                                    <small class="text-muted">Lucro: <?php echo number_format($row['percentual_lucro'], 2, ',', '.'); ?>%</small>
                                </td>
                                <td class="text-center">
                                    <span class="status-badge <?php echo $estoque_baixo ? 'status-danger' : 'status-success'; ?>" title="MÃ­nimo: <?php echo $row['estoque_minimo']; ?>">
                                        <?php echo $row['quantidade_estoque']; ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($row['fornecedor'] ?: 'N/A'); ?></td>
                                <td class="text-center">
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                            <i class="fas fa-cog"></i>
                                        </button>
                                        <ul class="dropdown-menu">
                                            <li><a class="dropdown-item" href="cadastro_produto.php?id=<?php echo $row['id']; ?>"><i class="fas fa-edit me-2"></i>Editar</a></li>
                                            <li><hr class="dropdown-divider"></li>
                                            <li><a class="dropdown-item text-danger" href="produtos.php?action=delete&id=<?php echo $row['id']; ?>" onclick="return confirm('Tem certeza que deseja excluir este produto? A exclusÃ£o sÃ³ serÃ¡ permitida se ele nÃ£o estiver em nenhuma venda ou orÃ§amento.');"><i class="fas fa-trash-alt me-2"></i>Excluir</a></li>
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Cards para Mobile -->
            <div class="mobile-products-container">
                <?php foreach ($produtos_data as $row): ?>
                    <?php $estoque_baixo = $row['quantidade_estoque'] <= $row['estoque_minimo']; ?>
                    <div class="mobile-product-item">
                        <div class="mobile-product-header">
                            <div class="mobile-product-name"><?php echo htmlspecialchars($row['nome']); ?></div>
                            <?php if (!empty($row['sku'])): ?>
                                <div class="mobile-product-sku">SKU: <?php echo htmlspecialchars($row['sku']); ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="mobile-product-info">
                            <div class="mobile-product-info-item">
                                <span class="mobile-product-info-label">ID:</span>
                                <span class="mobile-product-info-value">#<?php echo $row['id']; ?></span>
                            </div>
                            <div class="mobile-product-info-item">
                                <span class="mobile-product-info-label">PreÃ§o de Venda:</span>
                                <span class="mobile-product-info-value text-success fw-bold">R$ <?php echo number_format($row['preco_venda'], 2, ',', '.'); ?></span>
                            </div>
                            <div class="mobile-product-info-item">
                                <span class="mobile-product-info-label">Margem de Lucro:</span>
                                <span class="mobile-product-info-value"><?php echo number_format($row['percentual_lucro'], 2, ',', '.'); ?>%</span>
                            </div>
                            <div class="mobile-product-info-item">
                                <span class="mobile-product-info-label">Estoque:</span>
                                <span class="mobile-product-info-value">
                                    <span class="mobile-product-stock <?php echo $estoque_baixo ? 'low' : 'normal'; ?>">
                                        <?php if ($estoque_baixo): ?>
                                            <i class="fas fa-exclamation-triangle"></i>
                                        <?php else: ?>
                                            <i class="fas fa-check-circle"></i>
                                        <?php endif; ?>
                                        <?php echo $row['quantidade_estoque']; ?> unidades
                                    </span>
                                </span>
                            </div>
                            <div class="mobile-product-info-item">
                                <span class="mobile-product-info-label">Estoque MÃ­nimo:</span>
                                <span class="mobile-product-info-value"><?php echo $row['estoque_minimo']; ?> unidades</span>
                            </div>
                            <?php if (!empty($row['fornecedor'])): ?>
                            <div class="mobile-product-info-item">
                                <span class="mobile-product-info-label">Fornecedor:</span>
                                <span class="mobile-product-info-value"><?php echo htmlspecialchars($row['fornecedor']); ?></span>
                            </div>
                            <?php endif; ?>
                        </div>

                        <div class="mobile-product-actions">
                            <a href="cadastro_produto.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-primary" title="Editar Produto">
                                <i class="fas fa-edit me-1"></i> Editar
                            </a>
                            <a href="produtos.php?action=delete&id=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-danger" title="Excluir Produto" onclick="return confirm('Tem certeza que deseja excluir este produto? A exclusÃ£o sÃ³ serÃ¡ permitida se ele nÃ£o estiver em nenhuma venda ou orÃ§amento.');">
                                <i class="fas fa-trash-alt me-1"></i> Excluir
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

        <?php else: ?>
            <div class="text-center py-5">
                <div class="stats-icon primary mx-auto mb-3"><i class="fas fa-box-open"></i></div>
                <h5 class="text-muted mb-2">Nenhum produto cadastrado</h5>
                <p class="text-muted">Comece adicionando seu primeiro produto ao sistema.</p>
                <a href="cadastro_produto.php" class="btn btn-primary"><i class="fas fa-plus me-2"></i> Adicionar Primeiro Produto</a>
            </div>
        <?php endif; ?>

        <?php
        echo render_pagination([
            'current_page' => $pagina_atual,
            'total_pages' => $total_paginas,
            'page_param' => 'page',
            'base_params' => [
                'fornecedor' => $filtro_fornecedor,
                'search' => $filtro_busca
            ],
            'aria_label' => 'Paginacao de produtos',
            'summary' => $total_produtos_db . ' produtos encontrados',
            'window' => 7,
            'labels' => [
                'first' => '&laquo;&laquo;',
                'previous' => '&laquo;',
                'next' => '&raquo;',
                'last' => '&raquo;&raquo;'
            ]
        ]);
        ?>
    </div>
</div>

<style>
/* Estilos para destacar termos de busca */
.search-highlight {
    background-color: #fff3cd;
    color: #856404;
    padding: 1px 3px;
    border-radius: 3px;
    font-weight: 500;
}

/* AnimaÃ§Ã£o suave para resultados */
#produtosTable tbody tr {
    transition: opacity 0.2s ease-in-out;
}

/* Estilo para o contador de resultados */
#resultsCounter {
    transition: all 0.3s ease;
}

/* Melhorar aparÃªncia do input de busca */
#searchInput:focus {
    border-color: #0d6efd;
    box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
}

/* Estilo para cards mobile quando filtrados */
.mobile-product-item {
    transition: opacity 0.2s ease-in-out;
}
</style>

<script>
// FunÃ§Ã£o para limpar busca
function clearSearch() {
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        searchInput.value = '';
        // Redirecionar para a pÃ¡gina sem o parÃ¢metro de busca
        const url = new URL(window.location);
        url.searchParams.delete('search');
        url.searchParams.set('page', '1');
        window.location.href = url.toString();
    }
}

document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchInput');

    if (searchInput) {
        // Obter valor de busca da URL se existir
        const urlParams = new URLSearchParams(window.location.search);
        const searchValue = urlParams.get('search');
        if (searchValue) {
            searchInput.value = decodeURIComponent(searchValue);
        }

        // Adicionar evento de busca com debounce
        let searchTimeout;
        searchInput.addEventListener('keyup', function(e) {
            // Se pressionou Enter, fazer a busca imediatamente
            if (e.key === 'Enter') {
                performSearch();
                return;
            }

            // Limpar timeout anterior
            clearTimeout(searchTimeout);

            // Definir novo timeout para debounce (500ms)
            searchTimeout = setTimeout(performSearch, 500);
        });

        // Permitir busca ao clicar no botÃ£o de busca
        const searchButton = searchInput.nextElementSibling;
        if (searchButton) {
            searchButton.addEventListener('click', performSearch);
        }

        // Adicionar atalho de teclado para busca (Ctrl+F ou Ctrl+K)
        document.addEventListener('keydown', function(e) {
            if ((e.ctrlKey || e.metaKey) && (e.key === 'f' || e.key === 'k')) {
                e.preventDefault();
                searchInput.focus();
            }
        });
    }

    // FunÃ§Ã£o para executar a busca
    function performSearch() {
        const searchTerm = searchInput.value.trim();
        const url = new URL(window.location);

        if (searchTerm) {
            url.searchParams.set('search', searchTerm);
        } else {
            url.searchParams.delete('search');
        }

        // Resetar para pÃ¡gina 1 ao fazer nova busca
        url.searchParams.set('page', '1');

        window.location.href = url.toString();
    }
});
</script>

<?php
$conn->close(); // Fechar conexÃ£o aqui
include_once 'includes/footer.php';
?>
