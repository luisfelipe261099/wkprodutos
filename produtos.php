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

// --- Resposta parcial (AJAX): busca e filtros sem recarregar a página ---
// A página inteira continua funcionando sem JavaScript (o formulário é um GET
// comum); quando há JS, só este trecho é recarregado, então o cursor e o
// teclado nunca são perdidos enquanto o usuário digita.
if (isset($_GET['ajax']) && $_GET['ajax'] === '1') {
    // Os partials usam fmt_brl()/render_pagination(), normalmente carregados
    // pelo header — que não é renderizado nesta resposta.
    $ui_helper_path = __DIR__ . '/includes/ui_helpers.php';
    if (file_exists($ui_helper_path)) {
        require_once $ui_helper_path;
    }

    $partials = [
        'stats'        => __DIR__ . '/includes/produtos_stats.php',
        'chips'        => __DIR__ . '/includes/produtos_chips.php',
        'resultado'    => __DIR__ . '/includes/produtos_lista.php',
        'fornecedores' => __DIR__ . '/includes/produtos_fornecedores_options.php',
    ];
    $resposta = [
        'total'    => $total_produtos_db,
        'contador' => $total_produtos_db . ' produto' . ($total_produtos_db === 1 ? '' : 's'),
    ];
    foreach ($partials as $chave => $arquivo) {
        ob_start();
        include $arquivo;
        $resposta[$chave] = ob_get_clean();
    }

    $conn->close();
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($resposta, JSON_UNESCAPED_UNICODE);
    exit;
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

<div id="produtosStats">
    <?php include __DIR__ . '/includes/produtos_stats.php'; ?>
</div>

<div class="modern-card fade-in-up">
    <div class="card-header-modern d-flex justify-content-between align-items-center flex-wrap gap-2">
        <span><i class="fas fa-list"></i> Lista de Produtos</span>
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-primary" id="produtosContador"><?php echo $total_produtos_db; ?> produto<?php echo $total_produtos_db === 1 ? '' : 's'; ?></span>
            <a href="cadastro_produto.php<?php echo $filtro_empresa !== '' && $filtro_empresa !== 'sem' ? '?empresa_id=' . (int)$filtro_empresa : ''; ?>" class="btn btn-primary">
                <i class="fas fa-plus me-2"></i> Novo Produto
            </a>
        </div>
    </div>
    <div class="card-body-modern">

        <!-- Barra de filtros -->
        <form class="filter-bar" method="get" action="produtos.php" id="filtrosProdutos">
            <div class="row g-3 align-items-end">
                <div class="col-12 col-lg-6">
                    <label class="form-label" for="searchInput">Buscar produto</label>
                    <div class="search-field">
                        <div class="search-field-control">
                            <i class="fas fa-search search-field-icon" aria-hidden="true"></i>
                            <input type="text" class="form-control search-field-input" id="searchInput" name="search"
                                   value="<?php echo htmlspecialchars($filtro_busca); ?>"
                                   placeholder="Nome, SKU, descrição, fornecedor ou empresa..."
                                   autocomplete="off" spellcheck="false"
                                   aria-describedby="searchHint" aria-controls="produtosResultado">
                            <span class="search-field-spinner" id="searchSpinner" hidden aria-hidden="true"></span>
                            <button type="button" class="search-field-clear" id="searchClear"
                                    title="Limpar busca (Esc)" aria-label="Limpar busca"
                                    <?php echo $filtro_busca === '' ? 'hidden' : ''; ?>>
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                        <button class="btn btn-primary search-field-submit" type="submit" title="Buscar">
                            <i class="fas fa-search"></i><span class="d-none d-sm-inline ms-2">Buscar</span>
                        </button>
                    </div>
                    <small class="filter-hint" id="searchHint">
                        A lista é atualizada enquanto você digita &mdash; o campo não perde o foco.
                        <span class="d-none d-lg-inline">Atalho: <kbd>Ctrl</kbd> + <kbd>K</kbd>.</span>
                    </small>
                </div>

                <div class="col-12 col-md-6 col-lg-3">
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

                <div class="col-12 col-md-6 col-lg-3">
                    <label class="form-label" for="fornecedor">Fornecedor</label>
                    <select class="form-select" id="fornecedor" name="fornecedor"
                            data-searchable data-search-placeholder="Buscar fornecedor..."
                            data-search-empty="Nenhum fornecedor encontrado">
                        <?php include __DIR__ . '/includes/produtos_fornecedores_options.php'; ?>
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
                    <a href="produtos.php" class="btn btn-outline-secondary" id="limparFiltros"
                       <?php echo $possui_filtros ? '' : 'hidden'; ?>>
                        <i class="fas fa-eraser me-2"></i> Limpar filtros
                    </a>
                </div>
            </div>

            <div id="produtosChips">
                <?php include __DIR__ . '/includes/produtos_chips.php'; ?>
            </div>
        </form>

        <p class="visually-hidden" role="status" id="produtosStatus"></p>

        <div id="produtosResultado">
            <?php include __DIR__ . '/includes/produtos_lista.php'; ?>
        </div>
    </div>
</div>

<script src="/js/searchable-select.js?v=<?php echo file_exists(__DIR__ . '/js/searchable-select.js') ? filemtime(__DIR__ . '/js/searchable-select.js') : '1'; ?>"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('filtrosProdutos');
    if (!form) {
        return;
    }

    const searchInput  = document.getElementById('searchInput');
    const searchClear  = document.getElementById('searchClear');
    const spinner      = document.getElementById('searchSpinner');
    const boxStats     = document.getElementById('produtosStats');
    const boxChips     = document.getElementById('produtosChips');
    const boxResultado = document.getElementById('produtosResultado');
    const contador     = document.getElementById('produtosContador');
    const statusBusca  = document.getElementById('produtosStatus');
    const btnLimpar    = document.getElementById('limparFiltros');
    const selFornecedor = document.getElementById('fornecedor');

    // Campos que não entram na URL quando estão no valor padrão
    const valoresPadrao = { ordem: 'nome_asc', por_pagina: '15' };
    const suportaAjax = 'fetch' in window && 'AbortController' in window && 'URLSearchParams' in window;

    /** Lê o formulário e devolve os parâmetros de filtro (sem os valores padrão). */
    function parametrosDoFormulario() {
        const params = new URLSearchParams();
        form.querySelectorAll('input[name], select[name]').forEach(function (campo) {
            const valor = (campo.value || '').trim();
            const padrao = valoresPadrao[campo.name];
            if (valor === '' || (padrao !== undefined && valor === padrao)) {
                return;
            }
            params.set(campo.name, campo.value);
        });
        return params; // trocar filtro sempre volta para a primeira página
    }

    /** Copia os parâmetros de uma URL de volta para os campos do formulário. */
    function sincronizarFormulario(params) {
        form.querySelectorAll('input[name], select[name]').forEach(function (campo) {
            const valor = params.get(campo.name);
            const novo = valor !== null ? valor : (valoresPadrao[campo.name] || '');
            if (campo.value === novo) {
                return;
            }
            campo.value = novo;
            if (campo.tagName === 'SELECT' && campo.value !== novo) {
                campo.value = ''; // opção não existe mais na lista atual
            }
        });
        atualizarBotaoLimpar();
        if (window.SearchableSelect && window.SearchableSelect.refresh) {
            window.SearchableSelect.refresh(form);
        }
    }

    function urlDosFiltros(params) {
        const query = params.toString();
        return 'produtos.php' + (query !== '' ? '?' + query : '');
    }

    function atualizarBotaoLimpar() {
        const temFiltro = parametrosDoFormulario().toString() !== '';
        if (btnLimpar) {
            btnLimpar.hidden = !temFiltro;
        }
        if (searchClear && searchInput) {
            searchClear.hidden = searchInput.value === '';
        }
    }

    function carregando(ativo) {
        if (spinner) {
            spinner.hidden = !ativo;
        }
        if (boxResultado) {
            boxResultado.classList.toggle('is-loading', ativo);
            boxResultado.setAttribute('aria-busy', ativo ? 'true' : 'false');
        }
    }

    let requisicao = null;
    let sequencia = 0;

    /** Atualiza apenas a listagem, preservando foco e cursor do campo de busca. */
    function aplicarFiltros(params, opcoes) {
        const config = opcoes || {};
        const alvo = params || parametrosDoFormulario();
        const url = urlDosFiltros(alvo);

        if (!suportaAjax) {
            window.location.href = url;
            return;
        }

        if (requisicao) {
            requisicao.abort();
        }
        requisicao = new AbortController();

        const atual = ++sequencia;
        carregando(true);

        fetch(url + (url.indexOf('?') === -1 ? '?' : '&') + 'ajax=1', {
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin',
            signal: requisicao.signal
        })
            .then(function (resposta) {
                if (!resposta.ok) {
                    throw new Error('HTTP ' + resposta.status);
                }
                return resposta.json();
            })
            .then(function (dados) {
                if (atual !== sequencia) {
                    return; // chegou uma resposta mais nova
                }
                boxStats.innerHTML = dados.stats;
                boxChips.innerHTML = dados.chips;
                boxResultado.innerHTML = dados.resultado;
                if (contador) {
                    contador.textContent = dados.contador;
                }
                if (statusBusca) {
                    statusBusca.textContent = dados.contador + ' na listagem.';
                }
                // A lista de fornecedores depende da empresa selecionada
                if (selFornecedor && typeof dados.fornecedores === 'string') {
                    selFornecedor.innerHTML = dados.fornecedores;
                    if (window.SearchableSelect && window.SearchableSelect.refresh) {
                        window.SearchableSelect.refresh(selFornecedor);
                    }
                }
                atualizarBotaoLimpar();
                carregando(false);
                if (config.historico === 'substituir') {
                    // Digitar não deve empilhar uma entrada de histórico por letra
                    window.history.replaceState({ produtos: true }, '', url);
                } else if (config.historico !== false) {
                    window.history.pushState({ produtos: true }, '', url);
                }
                if (config.rolar) {
                    boxResultado.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            })
            .catch(function (erro) {
                if (erro.name === 'AbortError') {
                    return;
                }
                // Qualquer falha cai no comportamento antigo: navegação normal
                window.location.href = url;
            });
    }

    // --- Envio do formulário (Enter ou botões) ---
    form.addEventListener('submit', function (event) {
        event.preventDefault();
        aplicarFiltros();
    });

    // --- Selects aplicam o filtro imediatamente ---
    ['empresa_id', 'fornecedor', 'estoque', 'ordem', 'por_pagina'].forEach(function (nome) {
        const campo = form.querySelector('[name="' + nome + '"]');
        if (campo) {
            campo.addEventListener('change', function () {
                aplicarFiltros();
            });
        }
    });

    // --- Busca enquanto digita, sem recarregar a página ---
    if (searchInput) {
        let temporizador;
        let ultimoTermo = searchInput.value.trim();

        searchInput.addEventListener('input', function () {
            atualizarBotaoLimpar();
            clearTimeout(temporizador);
            temporizador = setTimeout(function () {
                const termo = searchInput.value.trim();
                if (termo === ultimoTermo) {
                    return; // nada mudou de fato (espaços, acentuação em composição)
                }
                ultimoTermo = termo;
                aplicarFiltros(null, { historico: 'substituir' });
            }, 400);
        });

        searchInput.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && searchInput.value !== '') {
                event.preventDefault();
                clearTimeout(temporizador);
                searchInput.value = '';
                ultimoTermo = '';
                atualizarBotaoLimpar();
                aplicarFiltros(null, { historico: 'substituir' });
            }
        });

        if (searchClear) {
            searchClear.addEventListener('click', function () {
                clearTimeout(temporizador);
                searchInput.value = '';
                ultimoTermo = '';
                searchInput.focus();
                atualizarBotaoLimpar();
                aplicarFiltros(null, { historico: 'substituir' });
            });
        }

        // Ctrl+K / Ctrl+F focam a busca
        document.addEventListener('keydown', function (event) {
            if ((event.ctrlKey || event.metaKey) && (event.key === 'f' || event.key === 'k')) {
                event.preventDefault();
                searchInput.focus();
                searchInput.select();
            }
        });
    }

    // --- Links internos (paginação, chips, empresa da tabela) também via AJAX ---
    document.addEventListener('click', function (event) {
        if (!suportaAjax || event.defaultPrevented || event.button !== 0 ||
            event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) {
            return;
        }

        const link = event.target.closest('a[href]');
        if (!link || link.target || link.hasAttribute('download')) {
            return;
        }
        const dentroDaListagem = boxStats.contains(link) || boxChips.contains(link) ||
            boxResultado.contains(link) || link === btnLimpar;
        if (!dentroDaListagem) {
            return;
        }

        const href = link.getAttribute('href');
        if (!href || href.charAt(0) === '#') {
            return;
        }

        const destino = new URL(href, window.location.href);
        const arquivo = destino.pathname.split('/').pop();
        // Só interceptamos links da própria listagem (paginação usa "?page=2")
        if (destino.origin !== window.location.origin || (arquivo !== '' && arquivo !== 'produtos.php')) {
            return;
        }
        if (destino.searchParams.has('action')) {
            return; // exclusão continua sendo uma navegação normal
        }

        event.preventDefault();
        sincronizarFormulario(destino.searchParams);
        aplicarFiltros(destino.searchParams, { rolar: destino.searchParams.has('page') });
    });

    // --- Voltar/avançar do navegador ---
    window.addEventListener('popstate', function () {
        const params = new URLSearchParams(window.location.search);
        params.delete('ajax');
        sincronizarFormulario(params);
        aplicarFiltros(params, { historico: false });
    });

    atualizarBotaoLimpar();
});
</script>

<?php
$conn->close(); // Fechar conexão aqui
include_once __DIR__ . '/includes/footer.php';
?>
