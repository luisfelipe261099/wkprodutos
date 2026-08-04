<?php
require_once __DIR__ . '/includes/session_bootstrap.php';

// Verifica se o usuário está logado
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: index.php");
    exit;
}

require_once __DIR__ . '/includes/db_connect.php';

$message = '';
$message_type = '';

// Mensagem vinda de um redirecionamento (padrão Post/Redirect/Get)
if (!empty($_SESSION['produtos_flash'])) {
    $message = $_SESSION['produtos_flash']['message'];
    $message_type = $_SESSION['produtos_flash']['type'];
    unset($_SESSION['produtos_flash']);
}

// --- Leitura e normalização dos filtros ---
$filtros_permitidos_estoque = ['critico', 'zerado', 'ok', 'ilimitado'];
$ordenacoes = [
    'nome_asc'    => ['label' => 'Nome (A-Z)',            'sql' => 'p.nome ASC'],
    'nome_desc'   => ['label' => 'Nome (Z-A)',            'sql' => 'p.nome DESC'],
    'empresa_asc' => ['label' => 'Empresa (A-Z)',         'sql' => 'e.nome_empresa ASC, p.nome ASC'],
    'preco_desc'  => ['label' => 'Maior preço',           'sql' => 'p.preco_venda DESC'],
    'preco_asc'   => ['label' => 'Menor preço',           'sql' => 'p.preco_venda ASC'],
    'estoque_asc' => ['label' => 'Menor estoque',         'sql' => 'p.quantidade_estoque ASC'],
    'recentes'    => ['label' => 'Cadastrados recentes',  'sql' => 'p.data_cadastro DESC, p.id DESC'],
];
$opcoes_por_pagina = [15, 30, 50, 100];

$filtro_busca      = isset($_GET['search']) ? trim($_GET['search']) : '';
$filtro_fornecedor = isset($_GET['fornecedor']) ? trim($_GET['fornecedor']) : '';
$filtro_empresa    = isset($_GET['empresa_id']) ? trim($_GET['empresa_id']) : '';
$filtro_estoque    = isset($_GET['estoque']) && in_array($_GET['estoque'], $filtros_permitidos_estoque, true) ? $_GET['estoque'] : '';
$ordenacao         = isset($_GET['ordem']) && isset($ordenacoes[$_GET['ordem']]) ? $_GET['ordem'] : 'nome_asc';

// "sem" = produtos ainda não vinculados a nenhuma empresa representada
$filtro_empresa_valido = ($filtro_empresa === 'sem' || ctype_digit($filtro_empresa)) ? $filtro_empresa : '';
$filtro_empresa = $filtro_empresa_valido;

$itens_por_pagina = isset($_GET['por_pagina']) && in_array((int)$_GET['por_pagina'], $opcoes_por_pagina, true)
    ? (int)$_GET['por_pagina']
    : 15;
$pagina_atual = isset($_GET['page']) && is_numeric($_GET['page']) ? max(1, (int)$_GET['page']) : 1;

// Parâmetros preservados em links (paginação, exclusão, chips de filtro)
$filtros_atuais = [
    'search'     => $filtro_busca,
    'empresa_id' => $filtro_empresa,
    'fornecedor' => $filtro_fornecedor,
    'estoque'    => $filtro_estoque,
    'ordem'      => $ordenacao === 'nome_asc' ? '' : $ordenacao,
    'por_pagina' => $itens_por_pagina === 15 ? '' : $itens_por_pagina,
];
$filtros_ativos_query = array_filter($filtros_atuais, static fn($valor) => $valor !== '' && $valor !== null);

// --- Lógica para Exclusão SEGURA de Produto ---
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $produto_id_to_delete = intval($_GET['id']);

    // 1. VERIFICAR DEPENDÊNCIAS ANTES DE EXCLUIR
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
        // Se houver dependências, impede a exclusão
        $flash_message = "Este produto não pode ser excluído, pois está associado a vendas ou orçamentos existentes.";
        $flash_type = "danger";
    } else {
        // Se não houver dependências, procede com a exclusão
        $flash_message = "Erro ao excluir o produto.";
        $flash_type = "danger";
        $sql_delete = "DELETE FROM produtos WHERE id = ?";
        if ($stmt_delete = $conn->prepare($sql_delete)) {
            $stmt_delete->bind_param("i", $produto_id_to_delete);
            if ($stmt_delete->execute()) {
                $flash_message = "Produto excluído com sucesso!";
                $flash_type = "success";
            } else {
                $flash_message = "Erro ao excluir o produto: " . $stmt_delete->error;
            }
            $stmt_delete->close();
        }
    }

    // Redireciona preservando os filtros, para que atualizar a página (F5)
    // não tente executar a exclusão novamente.
    $_SESSION['produtos_flash'] = ['message' => $flash_message, 'type' => $flash_type];
    $conn->close();
    $redirect_params = $filtros_ativos_query;
    if ($pagina_atual > 1) {
        $redirect_params['page'] = $pagina_atual;
    }
    $query_string = http_build_query($redirect_params);
    header('Location: produtos.php' . ($query_string !== '' ? '?' . $query_string : ''));
    exit;
}

// --- Montagem dos filtros (compartilhada entre listagem e estatísticas) ---
$where_conditions = [];
$params = [];
$types = "";

if ($filtro_empresa === 'sem') {
    $where_conditions[] = "p.empresa_id IS NULL";
} elseif ($filtro_empresa !== '') {
    $where_conditions[] = "p.empresa_id = ?";
    $params[] = (int)$filtro_empresa;
    $types .= "i";
}

if ($filtro_fornecedor !== '') {
    $where_conditions[] = "p.fornecedor = ?";
    $params[] = $filtro_fornecedor;
    $types .= "s";
}

if ($filtro_busca !== '') {
    $where_conditions[] = "(p.nome LIKE ? OR p.sku LIKE ? OR p.fornecedor LIKE ? OR p.descricao LIKE ? OR e.nome_empresa LIKE ?)";
    $search_term = "%{$filtro_busca}%";
    for ($i = 0; $i < 5; $i++) {
        $params[] = $search_term;
        $types .= "s";
    }
}

switch ($filtro_estoque) {
    case 'critico':
        $where_conditions[] = "(p.quantidade_estoque >= 0 AND p.quantidade_estoque <= p.estoque_minimo)";
        break;
    case 'zerado':
        $where_conditions[] = "p.quantidade_estoque = 0";
        break;
    case 'ok':
        $where_conditions[] = "(p.quantidade_estoque < 0 OR p.quantidade_estoque > p.estoque_minimo)";
        break;
    case 'ilimitado':
        $where_conditions[] = "p.quantidade_estoque < 0";
        break;
}

$where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";
$from_clause = "FROM produtos p LEFT JOIN empresas_representadas e ON e.id = p.empresa_id";

// --- Estatísticas (respeitam os filtros aplicados) ---
$sql_stats = "SELECT
                COUNT(*) AS total,
                SUM(CASE WHEN p.quantidade_estoque >= 0 AND p.quantidade_estoque <= p.estoque_minimo THEN 1 ELSE 0 END) AS criticos,
                SUM(CASE WHEN p.quantidade_estoque > 0 THEN p.preco_venda * p.quantidade_estoque ELSE 0 END) AS valor_estoque,
                SUM(CASE WHEN p.quantidade_estoque < 0 THEN 1 ELSE 0 END) AS ilimitados,
                COUNT(DISTINCT p.empresa_id) AS empresas
              $from_clause
              $where_clause";
$stmt_stats = $conn->prepare($sql_stats);
if ($types !== '') {
    $stmt_stats->bind_param($types, ...$params);
}
$stmt_stats->execute();
$stats = $stmt_stats->get_result()->fetch_assoc();
$stmt_stats->close();

$total_produtos_db = (int)($stats['total'] ?? 0);
$criticos = (int)($stats['criticos'] ?? 0);
$valor_total_estoque = (float)($stats['valor_estoque'] ?? 0);
$total_ilimitados = (int)($stats['ilimitados'] ?? 0);
$empresas_no_resultado = (int)($stats['empresas'] ?? 0);

$total_paginas = max(1, (int)ceil($total_produtos_db / $itens_por_pagina));
if ($pagina_atual > $total_paginas) {
    $pagina_atual = $total_paginas;
}
$offset = ($pagina_atual - 1) * $itens_por_pagina;

// --- Listagem paginada ---
$sql_select_produtos = "SELECT p.id, p.nome, p.sku, p.percentual_lucro, p.preco_venda,
                               p.quantidade_estoque, p.estoque_minimo, p.fornecedor,
                               p.empresa_id, e.nome_empresa
                        $from_clause
                        $where_clause
                        ORDER BY {$ordenacoes[$ordenacao]['sql']}
                        LIMIT ? OFFSET ?";

$params_listagem = $params;
$types_listagem = $types . "ii";
$params_listagem[] = $itens_por_pagina;
$params_listagem[] = $offset;

$stmt_produtos = $conn->prepare($sql_select_produtos);
$stmt_produtos->bind_param($types_listagem, ...$params_listagem);
$stmt_produtos->execute();
$result_produtos = $stmt_produtos->get_result();
$produtos_data = $result_produtos->fetch_all(MYSQLI_ASSOC);
$stmt_produtos->close();

// --- Empresas para o filtro (com a contagem de produtos de cada uma) ---
$sql_empresas = "SELECT e.id, e.nome_empresa, e.status, COUNT(p.id) AS total_produtos
                 FROM empresas_representadas e
                 LEFT JOIN produtos p ON p.empresa_id = e.id
                 GROUP BY e.id, e.nome_empresa, e.status
                 ORDER BY e.nome_empresa ASC";
$result_empresas = $conn->query($sql_empresas);
$empresas_list = [];
if ($result_empresas) {
    while ($row = $result_empresas->fetch_assoc()) {
        // Empresas inativas só aparecem se ainda possuírem produtos vinculados
        if ($row['status'] !== 'ativo' && (int)$row['total_produtos'] === 0) {
            continue;
        }
        $empresas_list[] = $row;
    }
}

$result_sem_empresa = $conn->query("SELECT COUNT(*) AS total FROM produtos WHERE empresa_id IS NULL");
$produtos_sem_empresa = $result_sem_empresa ? (int)$result_sem_empresa->fetch_assoc()['total'] : 0;

$empresa_selecionada_nome = '';
if ($filtro_empresa === 'sem') {
    $empresa_selecionada_nome = 'Sem empresa';
} elseif ($filtro_empresa !== '') {
    foreach ($empresas_list as $empresa) {
        if ((string)$empresa['id'] === $filtro_empresa) {
            $empresa_selecionada_nome = $empresa['nome_empresa'];
            break;
        }
    }
}

// --- Fornecedores para o filtro (limitados à empresa selecionada) ---
$sql_fornecedores = "SELECT DISTINCT fornecedor FROM produtos WHERE fornecedor IS NOT NULL AND fornecedor != ''";
$fornecedor_params = [];
$fornecedor_types = '';
if ($filtro_empresa === 'sem') {
    $sql_fornecedores .= " AND empresa_id IS NULL";
} elseif ($filtro_empresa !== '') {
    $sql_fornecedores .= " AND empresa_id = ?";
    $fornecedor_params[] = (int)$filtro_empresa;
    $fornecedor_types = 'i';
}
$sql_fornecedores .= " ORDER BY fornecedor ASC";

$stmt_fornecedores = $conn->prepare($sql_fornecedores);
if ($fornecedor_types !== '') {
    $stmt_fornecedores->bind_param($fornecedor_types, ...$fornecedor_params);
}
$stmt_fornecedores->execute();
$result_fornecedores = $stmt_fornecedores->get_result();
$fornecedores_list = [];
while ($row = $result_fornecedores->fetch_assoc()) {
    $fornecedores_list[] = $row['fornecedor'];
}
$stmt_fornecedores->close();

// Total geral (sem filtros) para diferenciar "catálogo vazio" de "filtro sem resultado"
$result_geral = $conn->query("SELECT COUNT(*) AS total FROM produtos");
$total_produtos_geral = $result_geral ? (int)$result_geral->fetch_assoc()['total'] : 0;

$possui_filtros = !empty($filtros_ativos_query);

/** Monta uma URL da própria página removendo/alterando filtros. */
function produtos_url(array $filtros, array $overrides = []): string
{
    $params = array_merge($filtros, $overrides);
    $params = array_filter($params, static fn($valor) => $valor !== '' && $valor !== null);
    $query = http_build_query($params);
    return 'produtos.php' . ($query !== '' ? '?' . $query : '');
}

include_once __DIR__ . '/includes/header.php';
?>

<div class="page-header fade-in-up">
    <h1 class="page-title"><i class="fas fa-box"></i> Gerenciamento de Produtos</h1>
    <p class="page-subtitle">Controle completo do seu catálogo e estoque por empresa representada.</p>
</div>

<?php if (!empty($message)): ?>
    <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show fade-in-up" role="alert">
        <i class="fas fa-<?php echo $message_type == 'success' ? 'check-circle' : 'exclamation-triangle'; ?> me-2"></i>
        <?php echo htmlspecialchars($message); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="row g-4 mb-4">
    <div class="col-6 col-lg-3">
        <div class="stats-card primary fade-in-up">
            <div class="stats-icon primary"><i class="fas fa-boxes"></i></div>
            <div class="stats-value"><?php echo $total_produtos_db; ?></div>
            <div class="stats-label"><?php echo $possui_filtros ? 'Produtos no filtro' : 'Total de Produtos'; ?></div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stats-card danger fade-in-up">
            <div class="stats-icon danger"><i class="fas fa-exclamation-triangle"></i></div>
            <div class="stats-value"><?php echo $criticos; ?></div>
            <div class="stats-label">Estoque Crítico</div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stats-card success fade-in-up">
            <div class="stats-icon success"><i class="fas fa-dollar-sign"></i></div>
            <div class="stats-value"><?php echo fmt_brl($valor_total_estoque); ?></div>
            <div class="stats-label">Valor em Estoque</div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stats-card info fade-in-up">
            <div class="stats-icon info"><i class="fas fa-building"></i></div>
            <div class="stats-value"><?php echo $filtro_empresa !== '' ? 1 : $empresas_no_resultado; ?></div>
            <div class="stats-label">Empresas no resultado</div>
        </div>
    </div>
</div>

<div class="modern-card fade-in-up">
    <div class="card-header-modern d-flex justify-content-between align-items-center flex-wrap gap-2">
        <span><i class="fas fa-list"></i> Lista de Produtos</span>
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-primary"><?php echo $total_produtos_db; ?> produto<?php echo $total_produtos_db === 1 ? '' : 's'; ?></span>
            <a href="cadastro_produto.php<?php echo $filtro_empresa !== '' && $filtro_empresa !== 'sem' ? '?empresa_id=' . (int)$filtro_empresa : ''; ?>" class="btn btn-primary">
                <i class="fas fa-plus me-2"></i> Novo Produto
            </a>
        </div>
    </div>
    <div class="card-body-modern">

        <!-- Barra de filtros -->
        <form class="filter-bar" method="get" action="produtos.php" id="filtrosProdutos">
            <div class="row g-3 align-items-end">
                <div class="col-12 col-lg-4">
                    <label class="form-label" for="searchInput">Buscar</label>
                    <div class="input-group">
                        <input type="text" class="form-control" id="searchInput" name="search"
                               value="<?php echo htmlspecialchars($filtro_busca); ?>"
                               placeholder="Nome, SKU, descrição, fornecedor ou empresa..." autocomplete="off">
                        <button class="btn btn-outline-primary" type="submit" title="Buscar">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </div>

                <div class="col-12 col-md-6 col-lg-4">
                    <label class="form-label" for="empresa_id">Empresa representada</label>
                    <select class="form-select" id="empresa_id" name="empresa_id"
                            data-searchable data-search-placeholder="Buscar empresa por nome..."
                            data-search-empty="Nenhuma empresa encontrada">
                        <option value="">Todas as empresas</option>
                        <?php foreach ($empresas_list as $empresa): ?>
                            <option value="<?php echo (int)$empresa['id']; ?>"
                                    data-hint="<?php echo (int)$empresa['total_produtos']; ?>"
                                    <?php echo ((string)$empresa['id'] === $filtro_empresa) ? 'selected' : ''; ?>>
                                <?php
                                echo htmlspecialchars($empresa['nome_empresa']);
                                echo $empresa['status'] !== 'ativo' ? ' (inativa)' : '';
                                ?>
                            </option>
                        <?php endforeach; ?>
                        <?php if ($produtos_sem_empresa > 0): ?>
                            <option value="sem" data-hint="<?php echo $produtos_sem_empresa; ?>" <?php echo $filtro_empresa === 'sem' ? 'selected' : ''; ?>>
                                Sem empresa vinculada
                            </option>
                        <?php endif; ?>
                    </select>
                </div>

                <div class="col-12 col-md-6 col-lg-4">
                    <label class="form-label" for="fornecedor">Fornecedor</label>
                    <select class="form-select" id="fornecedor" name="fornecedor"
                            data-searchable data-search-placeholder="Buscar fornecedor..."
                            data-search-empty="Nenhum fornecedor encontrado">
                        <option value="">Todos</option>
                        <?php foreach ($fornecedores_list as $fornecedor): ?>
                            <option value="<?php echo htmlspecialchars($fornecedor); ?>" <?php echo ($filtro_fornecedor === $fornecedor) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($fornecedor); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-6 col-md-4 col-lg-3">
                    <label class="form-label" for="estoque">Estoque</label>
                    <select class="form-select" id="estoque" name="estoque">
                        <option value="">Todos</option>
                        <option value="critico" <?php echo $filtro_estoque === 'critico' ? 'selected' : ''; ?>>Crítico</option>
                        <option value="zerado" <?php echo $filtro_estoque === 'zerado' ? 'selected' : ''; ?>>Zerado</option>
                        <option value="ok" <?php echo $filtro_estoque === 'ok' ? 'selected' : ''; ?>>Normal</option>
                        <option value="ilimitado" <?php echo $filtro_estoque === 'ilimitado' ? 'selected' : ''; ?>>Ilimitado</option>
                    </select>
                </div>

                <div class="col-6 col-md-4 col-lg-3">
                    <label class="form-label" for="ordem">Ordenar por</label>
                    <select class="form-select" id="ordem" name="ordem">
                        <?php foreach ($ordenacoes as $chave => $opcao): ?>
                            <option value="<?php echo $chave; ?>" <?php echo $ordenacao === $chave ? 'selected' : ''; ?>>
                                <?php echo $opcao['label']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-12 col-md-4 col-lg-2">
                    <label class="form-label" for="por_pagina">Itens por página</label>
                    <select class="form-select" id="por_pagina" name="por_pagina">
                        <?php foreach ($opcoes_por_pagina as $opcao): ?>
                            <option value="<?php echo $opcao; ?>" <?php echo $itens_por_pagina === $opcao ? 'selected' : ''; ?>><?php echo $opcao; ?> por página</option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-12 col-lg-4 d-flex flex-wrap gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-filter me-2"></i> Aplicar filtros
                    </button>
                    <?php if ($possui_filtros): ?>
                        <a href="produtos.php" class="btn btn-outline-secondary">
                            <i class="fas fa-eraser me-2"></i> Limpar filtros
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <?php if ($possui_filtros): ?>
                <div class="filter-chips">
                    <span class="filter-chips-label"><i class="fas fa-sliders-h me-1"></i> Filtros ativos:</span>
                    <?php if ($filtro_busca !== ''): ?>
                        <a class="filter-chip" href="<?php echo htmlspecialchars(produtos_url($filtros_atuais, ['search' => ''])); ?>">
                            Busca: "<?php echo htmlspecialchars($filtro_busca); ?>" <i class="fas fa-times"></i>
                        </a>
                    <?php endif; ?>
                    <?php if ($filtro_empresa !== ''): ?>
                        <a class="filter-chip" href="<?php echo htmlspecialchars(produtos_url($filtros_atuais, ['empresa_id' => '', 'fornecedor' => ''])); ?>">
                            Empresa: <?php echo htmlspecialchars($empresa_selecionada_nome ?: $filtro_empresa); ?> <i class="fas fa-times"></i>
                        </a>
                    <?php endif; ?>
                    <?php if ($filtro_fornecedor !== ''): ?>
                        <a class="filter-chip" href="<?php echo htmlspecialchars(produtos_url($filtros_atuais, ['fornecedor' => ''])); ?>">
                            Fornecedor: <?php echo htmlspecialchars($filtro_fornecedor); ?> <i class="fas fa-times"></i>
                        </a>
                    <?php endif; ?>
                    <?php if ($filtro_estoque !== ''): ?>
                        <a class="filter-chip" href="<?php echo htmlspecialchars(produtos_url($filtros_atuais, ['estoque' => ''])); ?>">
                            Estoque: <?php echo htmlspecialchars(ucfirst($filtro_estoque)); ?> <i class="fas fa-times"></i>
                        </a>
                    <?php endif; ?>
                    <?php if ($ordenacao !== 'nome_asc'): ?>
                        <a class="filter-chip" href="<?php echo htmlspecialchars(produtos_url($filtros_atuais, ['ordem' => ''])); ?>">
                            Ordem: <?php echo htmlspecialchars($ordenacoes[$ordenacao]['label']); ?> <i class="fas fa-times"></i>
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </form>

        <?php if ($total_ilimitados > 0): ?>
            <p class="text-muted small mb-3">
                <i class="fas fa-infinity me-1"></i>
                <?php echo $total_ilimitados; ?> produto<?php echo $total_ilimitados === 1 ? '' : 's'; ?> com estoque ilimitado
                <?php echo $total_ilimitados === 1 ? 'não é considerado' : 'não são considerados'; ?> no valor em estoque.
            </p>
        <?php endif; ?>

        <?php if (!empty($produtos_data)): ?>
            <div class="table-responsive table-responsive-custom">
                <table class="table table-hover mb-0" id="produtosTable">
                    <thead>
                        <tr>
                            <th>Produto</th>
                            <th>Empresa</th>
                            <th>Preço / Lucro</th>
                            <th class="text-center">Estoque</th>
                            <th>Fornecedor</th>
                            <th class="text-center">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($produtos_data as $row): ?>
                            <?php
                            $estoque = (int)$row['quantidade_estoque'];
                            $ilimitado = $estoque < 0;
                            $estoque_baixo = !$ilimitado && $estoque <= (int)$row['estoque_minimo'];
                            ?>
                            <tr>
                                <td>
                                    <div class="fw-semibold"><?php echo htmlspecialchars($row['nome']); ?></div>
                                    <small class="text-muted">
                                        #<?php echo $row['id']; ?>
                                        <?php if (!empty($row['sku'])): ?>
                                            &middot; SKU <code class="bg-light px-1 rounded"><?php echo htmlspecialchars($row['sku']); ?></code>
                                        <?php endif; ?>
                                    </small>
                                </td>
                                <td>
                                    <?php if (!empty($row['nome_empresa'])): ?>
                                        <a class="text-decoration-none"
                                           href="<?php echo htmlspecialchars(produtos_url($filtros_atuais, ['empresa_id' => (int)$row['empresa_id'], 'fornecedor' => '', 'page' => ''])); ?>"
                                           title="Filtrar por esta empresa">
                                            <i class="fas fa-building me-1 text-muted"></i><?php echo htmlspecialchars($row['nome_empresa']); ?>
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted"><i class="fas fa-minus me-1"></i>Sem empresa</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="fw-semibold text-success">R$ <?php echo number_format($row['preco_venda'], 2, ',', '.'); ?></div>
                                    <small class="text-muted">Lucro: <?php echo number_format($row['percentual_lucro'], 2, ',', '.'); ?>%</small>
                                </td>
                                <td class="text-center">
                                    <?php if ($ilimitado): ?>
                                        <span class="status-badge status-info" title="Estoque ilimitado">
                                            <i class="fas fa-infinity me-1"></i>Ilimitado
                                        </span>
                                    <?php else: ?>
                                        <span class="status-badge <?php echo $estoque_baixo ? 'status-danger' : 'status-success'; ?>" title="Mínimo: <?php echo (int)$row['estoque_minimo']; ?>">
                                            <?php echo $estoque; ?>
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($row['fornecedor'] ?: 'N/A'); ?></td>
                                <td class="text-center">
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                            <i class="fas fa-cog"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            <li><a class="dropdown-item" href="cadastro_produto.php?id=<?php echo $row['id']; ?>"><i class="fas fa-edit me-2"></i>Editar</a></li>
                                            <li><hr class="dropdown-divider"></li>
                                            <li><a class="dropdown-item text-danger"
                                                   href="<?php echo htmlspecialchars(produtos_url($filtros_atuais, ['page' => $pagina_atual > 1 ? $pagina_atual : '', 'action' => 'delete', 'id' => $row['id']])); ?>"
                                                   onclick="return confirm('Tem certeza que deseja excluir este produto? A exclusão só será permitida se ele não estiver em nenhuma venda ou orçamento.');"><i class="fas fa-trash-alt me-2"></i>Excluir</a></li>
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Cards para Mobile -->
            <div class="mobile-items-container">
                <?php foreach ($produtos_data as $row): ?>
                    <?php
                    $estoque = (int)$row['quantidade_estoque'];
                    $ilimitado = $estoque < 0;
                    $estoque_baixo = !$ilimitado && $estoque <= (int)$row['estoque_minimo'];
                    ?>
                    <div class="mobile-item-card">
                        <div class="mobile-item-title">
                            <?php echo htmlspecialchars($row['nome']); ?>
                            <small class="text-muted d-block fw-normal">
                                #<?php echo $row['id']; ?>
                                <?php if (!empty($row['sku'])): ?> &middot; SKU: <?php echo htmlspecialchars($row['sku']); ?><?php endif; ?>
                            </small>
                        </div>

                        <div class="mobile-item-meta">
                            <div>
                                <span class="mobile-item-meta-label">Empresa</span>
                                <span class="mobile-item-meta-value"><?php echo htmlspecialchars($row['nome_empresa'] ?: 'Sem empresa'); ?></span>
                            </div>
                            <div>
                                <span class="mobile-item-meta-label">Preço de Venda</span>
                                <span class="mobile-item-meta-value text-success">R$ <?php echo number_format($row['preco_venda'], 2, ',', '.'); ?></span>
                            </div>
                            <div>
                                <span class="mobile-item-meta-label">Margem de Lucro</span>
                                <span class="mobile-item-meta-value"><?php echo number_format($row['percentual_lucro'], 2, ',', '.'); ?>%</span>
                            </div>
                            <div>
                                <span class="mobile-item-meta-label">Estoque</span>
                                <span class="mobile-item-meta-value">
                                    <?php if ($ilimitado): ?>
                                        <span class="status-badge status-info"><i class="fas fa-infinity me-1"></i> Ilimitado</span>
                                    <?php else: ?>
                                        <span class="status-badge <?php echo $estoque_baixo ? 'status-danger' : 'status-success'; ?>">
                                            <?php echo $estoque; ?> un.
                                        </span>
                                    <?php endif; ?>
                                </span>
                            </div>
                            <?php if (!$ilimitado): ?>
                            <div>
                                <span class="mobile-item-meta-label">Estoque Mínimo</span>
                                <span class="mobile-item-meta-value"><?php echo (int)$row['estoque_minimo']; ?> un.</span>
                            </div>
                            <?php endif; ?>
                            <?php if (!empty($row['fornecedor'])): ?>
                            <div>
                                <span class="mobile-item-meta-label">Fornecedor</span>
                                <span class="mobile-item-meta-value"><?php echo htmlspecialchars($row['fornecedor']); ?></span>
                            </div>
                            <?php endif; ?>
                        </div>

                        <div class="mobile-item-actions">
                            <a href="cadastro_produto.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-primary" title="Editar Produto">
                                <i class="fas fa-edit me-1"></i> Editar
                            </a>
                            <a href="<?php echo htmlspecialchars(produtos_url($filtros_atuais, ['page' => $pagina_atual > 1 ? $pagina_atual : '', 'action' => 'delete', 'id' => $row['id']])); ?>"
                               class="btn btn-sm btn-outline-danger" title="Excluir Produto"
                               onclick="return confirm('Tem certeza que deseja excluir este produto? A exclusão só será permitida se ele não estiver em nenhuma venda ou orçamento.');">
                                <i class="fas fa-trash-alt me-1"></i> Excluir
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

        <?php elseif ($total_produtos_geral === 0): ?>
            <div class="text-center py-5">
                <div class="stats-icon primary mx-auto mb-3"><i class="fas fa-box-open"></i></div>
                <h5 class="text-muted mb-2">Nenhum produto cadastrado</h5>
                <p class="text-muted">Comece adicionando seu primeiro produto ao sistema.</p>
                <a href="cadastro_produto.php" class="btn btn-primary"><i class="fas fa-plus me-2"></i> Adicionar Primeiro Produto</a>
            </div>
        <?php else: ?>
            <div class="text-center py-5">
                <div class="stats-icon info mx-auto mb-3"><i class="fas fa-filter"></i></div>
                <h5 class="text-muted mb-2">Nenhum produto encontrado com os filtros aplicados</h5>
                <p class="text-muted">Ajuste a busca, a empresa ou o fornecedor para ver mais resultados.</p>
                <a href="produtos.php" class="btn btn-outline-secondary"><i class="fas fa-eraser me-2"></i> Limpar filtros</a>
            </div>
        <?php endif; ?>

        <?php
        echo render_pagination([
            'current_page' => $pagina_atual,
            'total_pages' => $total_paginas,
            'page_param' => 'page',
            'base_params' => $filtros_ativos_query,
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

<script src="/js/searchable-select.js?v=<?php echo file_exists(__DIR__ . '/js/searchable-select.js') ? filemtime(__DIR__ . '/js/searchable-select.js') : '1'; ?>"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('filtrosProdutos');
    if (!form) {
        return;
    }

    // Mantém a URL limpa: campos vazios ou no valor padrão não entram na query string
    const valoresPadrao = { ordem: 'nome_asc', por_pagina: '15' };
    function aplicarFiltros() {
        form.querySelectorAll('input[name], select[name]').forEach(function (campo) {
            const padrao = valoresPadrao[campo.name];
            if (campo.value === '' || (padrao !== undefined && campo.value === padrao)) {
                campo.disabled = true;
            }
        });
        form.submit();
    }

    form.addEventListener('submit', function (event) {
        event.preventDefault();
        aplicarFiltros();
    });

    // Selects aplicam o filtro imediatamente
    ['empresa_id', 'fornecedor', 'estoque', 'ordem', 'por_pagina'].forEach(function (campo) {
        const elemento = form.querySelector('[name="' + campo + '"]');
        if (elemento) {
            elemento.addEventListener('change', aplicarFiltros);
        }
    });

    // Busca com debounce (sem precisar clicar em "Aplicar filtros")
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        let searchTimeout;
        searchInput.addEventListener('input', function () {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(aplicarFiltros, 600);
        });

        // Ctrl+K / Ctrl+F focam a busca
        document.addEventListener('keydown', function (e) {
            if ((e.ctrlKey || e.metaKey) && (e.key === 'f' || e.key === 'k')) {
                e.preventDefault();
                searchInput.focus();
                searchInput.select();
            }
        });
    }
});
</script>

<?php
$conn->close(); // Fechar conexão aqui
include_once __DIR__ . '/includes/footer.php';
?>
