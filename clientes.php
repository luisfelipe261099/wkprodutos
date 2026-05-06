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

// --- Lógica para Exclusão de Cliente ---
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $cliente_id_to_delete = $_GET['id'];

    $sql_delete = "DELETE FROM clientes WHERE id = ?";
    if ($stmt_delete = $conn->prepare($sql_delete)) {
        $stmt_delete->bind_param("i", $cliente_id_to_delete);
        if ($stmt_delete->execute()) {
            $message = "Cliente excluído com sucesso!";
            $message_type = "success";
        } else {
            $message = "Erro ao excluir o cliente. Pode haver vendas ou orçamentos associados a este cliente: " . $stmt_delete->error;
            $message_type = "danger";
        }
        $stmt_delete->close();
    } else {
        $message = "Erro na preparação da query de exclusão: " . $conn->error;
        $message_type = "danger";
    }
}

// --- Lógica para buscar clientes com busca, quantidade por página e paginação ---
$opcoes_por_pagina = [15, 30, 50, 100];
$itens_por_pagina = isset($_GET['por_pagina']) && in_array((int)$_GET['por_pagina'], $opcoes_por_pagina, true) ? (int)$_GET['por_pagina'] : 15;
$pagina_atual = isset($_GET['page']) && is_numeric($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$filtro_busca = isset($_GET['q']) ? trim($_GET['q']) : '';

// Total geral para os cards
$sql_total_clientes = "SELECT COUNT(*) AS total FROM clientes";
$result_total = $conn->query($sql_total_clientes);
$total_clientes_db = $result_total->fetch_assoc()['total'];

$where_conditions = [];
$params = [];
$types = "";

if ($filtro_busca !== '') {
    $where_conditions[] = "(nome LIKE ? OR cpf_cnpj LIKE ? OR email LIKE ? OR telefone LIKE ? OR cidade LIKE ? OR estado LIKE ? OR endereco LIKE ? OR CAST(id AS CHAR) LIKE ?)";
    $search_term = "%{$filtro_busca}%";
    for ($i = 0; $i < 8; $i++) {
        $params[] = $search_term;
    }
    $types .= "ssssssss";
}

$where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Contar o total de clientes filtrados para a paginação
$sql_total_filtrado = "SELECT COUNT(*) AS total FROM clientes $where_clause";
$stmt_total_filtrado = $conn->prepare($sql_total_filtrado);
if ($types !== '') {
    $stmt_total_filtrado->bind_param($types, ...$params);
}
$stmt_total_filtrado->execute();
$result_total_filtrado = $stmt_total_filtrado->get_result();
$total_clientes_filtrados = (int)$result_total_filtrado->fetch_assoc()['total'];
$stmt_total_filtrado->close();

$total_paginas = max(1, (int)ceil($total_clientes_filtrados / $itens_por_pagina));
if ($pagina_atual > $total_paginas) {
    $pagina_atual = $total_paginas;
}
$offset = ($pagina_atual - 1) * $itens_por_pagina;

$clientes_data = [];
// As estatísticas dos cards (Total de Clientes, PF, PJ, Cidades) devem ser calculadas globalmente, não apenas na página atual.
$sql_stats_globais = "SELECT tipo_pessoa, cidade FROM clientes";
$result_stats_globais = $conn->query($sql_stats_globais);

$clientes_pf = 0;
$clientes_pj = 0;
$cidades = [];

if ($result_stats_globais) {
    while ($row_stat = $result_stats_globais->fetch_assoc()) {
        if ($row_stat['tipo_pessoa'] == 'fisica') {
            $clientes_pf++;
        } else {
            $clientes_pj++;
        }
        if (!empty($row_stat['cidade']) && !in_array($row_stat['cidade'], $cidades)) {
            $cidades[] = $row_stat['cidade'];
        }
    }
    $result_stats_globais->close();
}


$sql_select_clientes = "SELECT id, nome, tipo_pessoa, cpf_cnpj, email, telefone, endereco, cidade, estado, cep 
                        FROM clientes 
                        $where_clause
                        ORDER BY nome ASC 
                        LIMIT ? OFFSET ?";

$stmt_clientes = $conn->prepare($sql_select_clientes);
$params_clientes = $params;
$types_clientes = $types . "ii";
$params_clientes[] = $itens_por_pagina;
$params_clientes[] = $offset;
$stmt_clientes->bind_param($types_clientes, ...$params_clientes);
$stmt_clientes->execute();
$result_clientes = $stmt_clientes->get_result();

if ($result_clientes) {
    // $total_clientes já foi obtido de $total_clientes_db para os cards
    while ($row = $result_clientes->fetch_assoc()) {
        $clientes_data[] = $row;
    }
    $result_clientes->close();
}

// $conn->close(); // Fechamento do banco movido para o final do script

include_once __DIR__ . '/includes/header.php';
?>

<style>
/* Melhorias específicas para iPad Air */
@media (min-width: 768px) and (max-width: 1180px) {
    /* Botões de ação mais visíveis */
    .btn-group .btn {
        min-width: 40px !important;
        min-height: 40px !important;
        padding: 0.5rem !important;
        margin: 0.125rem !important;
        border-radius: 0.375rem !important;
        transition: all 0.3s ease !important;
    }

    .btn-group .btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.15);
    }

    /* Botões mobile mais touch-friendly */
    .mobile-client-actions .btn {
        min-height: 40px !important;
        padding: 0.5rem 1rem !important;
        margin: 0.25rem !important;
        font-size: 0.9rem !important;
    }

    /* Tabela mais responsiva */
    .table-responsive,
    .table-modern {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }

    .table th,
    .table td {
        padding: 0.75rem 0.5rem !important;
        vertical-align: middle !important;
        font-size: 0.9rem;
    }

    /* Barra de busca */
    .input-group {
        max-width: 280px !important;
        min-width: 250px;
    }

    .form-control {
        min-height: 44px !important;
        font-size: 1rem !important;
    }

    /* Cards de estatísticas */
    .stats-card {
        padding: 1.5rem !important;
        margin-bottom: 1rem;
    }

    /* Cards mobile de clientes */
    .mobile-client-card {
        padding: 1.25rem !important;
        margin-bottom: 1rem !important;
        border-radius: 0.5rem !important;
    }

    /* Cabeçalho do card */
    .card-header-modern {
        padding: 1.25rem !important;
        flex-wrap: wrap;
        gap: 1rem;
    }

    /* Paginação */
    .pagination .page-link {
        min-width: 40px !important;
        min-height: 40px !important;
        display: flex;
        align-items: center;
        justify-content: center;
    }
}



/* Ajustes para orientação paisagem do iPad */
@media (min-width: 1024px) and (max-width: 1180px) and (orientation: landscape) {
    .container-fluid {
        padding-left: 2rem;
        padding-right: 2rem;
    }

    .btn-group .btn {
        min-width: 36px !important;
        min-height: 36px !important;
    }
}
</style>

<div class="page-header fade-in-up">
    <h1 class="page-title">
        <i class="fas fa-users"></i>
        Gerenciamento de Clientes
    </h1>
    <p class="page-subtitle">Gerencie sua base de clientes</p>
</div>

<?php if (!empty($message)): ?>
    <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show fade-in-up" role="alert">
        <i class="fas fa-<?php echo $message_type == 'success' ? 'check-circle' : 'exclamation-triangle'; ?> me-2"></i>
        <?php echo $message; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="row g-4 mb-4">
    <div class="col-6 col-lg-3">
        <div class="stats-card primary fade-in-up">
            <div class="stats-icon primary"><i class="fas fa-users"></i></div>
            <div class="stats-value"><?php echo $total_clientes_db; ?></div>
            <div class="stats-label">Total de Clientes</div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stats-card success fade-in-up">
            <div class="stats-icon success"><i class="fas fa-user"></i></div>
            <div class="stats-value"><?php echo $clientes_pf; ?></div>
            <div class="stats-label">Pessoa Física</div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stats-card info fade-in-up">
            <div class="stats-icon info"><i class="fas fa-building"></i></div>
            <div class="stats-value"><?php echo $clientes_pj; ?></div>
            <div class="stats-label">Pessoa Jurídica</div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stats-card warning fade-in-up">
            <div class="stats-icon warning"><i class="fas fa-map-marker-alt"></i></div>
            <div class="stats-value"><?php echo count($cidades); ?></div>
            <div class="stats-label">Cidades</div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-12">
        <div class="modern-card fade-in-up">
            <div class="card-body-modern">
                <div class="d-flex flex-column flex-md-row gap-3 justify-content-between align-items-md-center">
                    <div class="d-flex flex-column flex-sm-row gap-2">
                        <a href="cadastro_cliente.php" class="btn btn-primary"><i class="fas fa-user-plus me-2"></i> Novo Cliente</a>
                        <button class="btn btn-outline-primary" onclick="window.print()"><i class="fas fa-print me-2"></i> Imprimir</button>
                        <button class="btn btn-outline-primary" onclick="exportClients()"><i class="fas fa-download me-2"></i> Exportar</button>
                    </div>
                    <form method="get" action="clientes.php" class="d-flex flex-column flex-sm-row gap-2 flex-grow-1 flex-md-grow-0 align-items-stretch align-items-sm-center">
                        <div class="input-group" style="max-width: 320px;">
                            <input type="text" class="form-control" placeholder="Buscar clientes..." id="searchInput" name="q" value="<?php echo htmlspecialchars($filtro_busca); ?>">
                            <button class="btn btn-outline-primary" type="submit" title="Buscar"><i class="fas fa-search"></i></button>
                        </div>
                        <select class="form-select" name="por_pagina" style="max-width: 150px;" onchange="this.form.submit()" aria-label="Clientes por pagina">
                            <?php foreach ($opcoes_por_pagina as $opcao): ?>
                                <option value="<?php echo $opcao; ?>" <?php echo $itens_por_pagina === $opcao ? 'selected' : ''; ?>><?php echo $opcao; ?> por pagina</option>
                            <?php endforeach; ?>
                        </select>
                        <?php if ($filtro_busca !== ''): ?>
                            <a href="clientes.php?por_pagina=<?php echo $itens_por_pagina; ?>" class="btn btn-outline-secondary" title="Limpar busca"><i class="fas fa-times"></i></a>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modern-card fade-in-up">
    <div class="card-header-modern">
        <i class="fas fa-list"></i>
        Lista de Clientes
        <div class="ms-auto">
            <span class="badge bg-primary"><?php echo $total_clientes_filtrados; ?> clientes</span>
        </div>
    </div>
    <div class="card-body-modern">
        <?php if ($total_clientes_filtrados > 0): ?>
            <div class="table-responsive d-none d-md-block">
                <table class="table table-hover mb-0" id="clientesTable">
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Cliente</th>
                            <th>Tipo</th>
                            <th>Documento</th>
                            <th>Contato</th>
                            <th>Localização</th>
                            <th class="text-center">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($clientes_data as $row) { ?>
                            <tr>
                                <td><span class="badge bg-primary bg-opacity-10 text-primary fw-bold"><?php echo codigo_cliente((int)$row['id']); ?></span></td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="client-avatar me-3"><?php echo strtoupper(substr($row['nome'], 0, 1)); ?></div>
                                        <div>
                                            <div class="fw-semibold"><?php echo htmlspecialchars($row['nome']); ?></div>
                                            <small class="text-muted">#<?php echo $row['id']; ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td><span class="status-badge <?php echo $row['tipo_pessoa'] == 'fisica' ? 'status-success' : 'status-info'; ?>"><?php echo $row['tipo_pessoa'] == 'fisica' ? 'PF' : 'PJ'; ?></span></td>
                                <td><code class="bg-light px-2 py-1 rounded"><?php echo htmlspecialchars($row['cpf_cnpj']); ?></code></td>
                                <td>
                                    <div>
                                        <?php if (!empty($row['email'])): ?><div class="fw-semibold"><?php echo htmlspecialchars($row['email']); ?></div><?php endif; ?>
                                        <?php if (!empty($row['telefone'])): ?><small class="text-muted"><?php echo htmlspecialchars($row['telefone']); ?></small><?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <div>
                                        <?php if (!empty($row['cidade'])): ?><div class="fw-semibold"><?php echo htmlspecialchars($row['cidade']); ?></div><?php endif; ?>
                                        <?php if (!empty($row['estado'])): ?><small class="text-muted"><?php echo htmlspecialchars($row['estado']); ?></small><?php endif; ?>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <div class="btn-group" role="group">
                                        <a href="cadastro_cliente.php?id=<?php echo $row['id']; ?>" class="btn btn-outline-primary btn-sm" title="Editar"><i class="fas fa-edit"></i></a>
                                        <button class="btn btn-outline-danger btn-sm" onclick="confirmDelete(<?php echo $row['id']; ?>)" title="Excluir"><i class="fas fa-trash"></i></button>
                                    </div>
                                </td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>

            <div class="d-block d-md-none">
                <?php foreach ($clientes_data as $row) { ?>
                    <div class="mobile-client-card">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div class="d-flex align-items-center flex-grow-1">
                                <div class="client-avatar me-3"><?php echo strtoupper(substr($row['nome'], 0, 1)); ?></div>
                                <div class="flex-grow-1">
                                    <h6 class="mb-0 fw-bold"><?php echo htmlspecialchars($row['nome']); ?></h6>
                                    <small class="text-muted"><?php echo codigo_cliente((int)$row['id']); ?></small>
                                </div>
                            </div>
                            <span class="status-badge <?php echo $row['tipo_pessoa'] == 'fisica' ? 'status-success' : 'status-info'; ?>">
                                <?php echo $row['tipo_pessoa'] == 'fisica' ? 'PF' : 'PJ'; ?>
                            </span>
                        </div>

                        <div class="mobile-client-info">
                            <div class="mobile-client-info-item">
                                <span class="mobile-client-info-label">Documento:</span>
                                <span class="mobile-client-info-value"><?php echo htmlspecialchars($row['cpf_cnpj']); ?></span>
                            </div>
                            <?php if (!empty($row['email'])): ?>
                            <div class="mobile-client-info-item">
                                <span class="mobile-client-info-label">Email:</span>
                                <span class="mobile-client-info-value"><?php echo htmlspecialchars($row['email']); ?></span>
                            </div>
                            <?php endif; ?>
                            <?php if (!empty($row['telefone'])): ?>
                            <div class="mobile-client-info-item">
                                <span class="mobile-client-info-label">Telefone:</span>
                                <span class="mobile-client-info-value"><?php echo htmlspecialchars($row['telefone']); ?></span>
                            </div>
                            <?php endif; ?>
                            <?php if (!empty($row['cidade'])): ?>
                            <div class="mobile-client-info-item">
                                <span class="mobile-client-info-label">Localização:</span>
                                <span class="mobile-client-info-value"><?php echo htmlspecialchars($row['cidade']) . ' - ' . htmlspecialchars($row['estado']); ?></span>
                            </div>
                            <?php endif; ?>
                        </div>

                        <div class="mobile-client-actions">
                            <a href="cadastro_cliente.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-primary" title="Editar Cliente">
                                <i class="fas fa-edit me-1"></i> Editar
                            </a>
                            <button class="btn btn-sm btn-outline-danger" onclick="confirmDelete(<?php echo $row['id']; ?>)" title="Excluir Cliente">
                                <i class="fas fa-trash me-1"></i> Excluir
                            </button>
                        </div>
                    </div>
                <?php } ?>
            </div>
        <?php else: ?>
            <div class="text-center py-5">
                <div class="stats-icon primary mx-auto mb-3"><i class="fas fa-user-plus"></i></div>
                <?php if ($filtro_busca !== ''): ?>
                    <h5 class="text-muted mb-2">Nenhum cliente encontrado</h5>
                    <p class="text-muted">Tente buscar por nome, documento, email, telefone, cidade ou código.</p>
                    <a href="clientes.php?por_pagina=<?php echo $itens_por_pagina; ?>" class="btn btn-outline-primary"><i class="fas fa-times me-2"></i> Limpar Busca</a>
                <?php else: ?>
                    <h5 class="text-muted mb-2">Nenhum cliente cadastrado</h5>
                    <p class="text-muted">Comece adicionando seu primeiro cliente ao sistema.</p>
                    <a href="cadastro_cliente.php" class="btn btn-primary"><i class="fas fa-user-plus me-2"></i> Adicionar Primeiro Cliente</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php
        echo render_pagination([
            'current_page' => $pagina_atual,
            'total_pages' => $total_paginas,
            'page_param' => 'page',
            'base_params' => array_filter([
                'q' => $filtro_busca,
                'por_pagina' => $itens_por_pagina
            ], function ($value) {
                return $value !== '' && $value !== null;
            }),
            'aria_label' => 'Paginacao de clientes',
            'summary' => $filtro_busca !== '' ? $total_clientes_filtrados . ' clientes encontrados' : $total_clientes_db . ' clientes cadastrados',
            'window' => 5
        ]);
        ?>
    </div>
</div>

<script>
function confirmDelete(id) {
    if (confirm('Tem certeza que deseja excluir este cliente? Isso pode afetar vendas e orçamentos relacionados.')) {
        const button = event.target.closest('button');
        button.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        button.disabled = true;
        setTimeout(() => {
            window.location.href = `clientes.php?action=delete&id=${id}`;
        }, 500);
    }
}

function exportClients() {
    const table = document.getElementById('clientesTable');
    if (!table) return;
    let csv = 'Codigo,ID,Nome,Tipo,Documento,Email,Telefone,Cidade,Estado\n';
    const rows = table.querySelectorAll('tbody tr');
    rows.forEach(row => {
        if (row.style.display !== 'none') {
            const cells = row.querySelectorAll('td');
            const data = [
                cells[0].textContent.trim(),
                cells[1].querySelector('small').textContent.replace('#', ''),
                cells[1].querySelector('.fw-semibold').textContent.trim(),
                cells[2].textContent.trim(),
                cells[3].textContent.trim(),
                cells[4].querySelector('.fw-semibold') ? cells[4].querySelector('.fw-semibold').textContent.trim() : '',
                cells[4].querySelector('small') ? cells[4].querySelector('small').textContent.trim() : '',
                cells[5].querySelector('.fw-semibold') ? cells[5].querySelector('.fw-semibold').textContent.trim() : '',
                cells[5].querySelector('small') ? cells[5].querySelector('small').textContent.trim() : ''
            ];
            csv += data.map(field => `"${field.replace(/"/g, '""')}"`).join(',') + '\n';
        }
    });

    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'clientes.csv';
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    window.URL.revokeObjectURL(url);
}

document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        searchInput.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                window.location.href = 'clientes.php?por_pagina=<?php echo $itens_por_pagina; ?>';
            }
        });
    }
});
</script>

<?php 
$conn->close(); // Fechar conexão aqui
include_once __DIR__ . '/includes/footer.php'; ?>
