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

// --- Lógica para Exclusão de Empresa ---
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $empresa_id = $_GET['id'];

    // Verificar se há produtos vinculados
    $sql_check = "SELECT COUNT(*) as total FROM produtos WHERE empresa_id = ?";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->bind_param("i", $empresa_id);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();
    $row_check = $result_check->fetch_assoc();

    if ($row_check['total'] > 0) {
        $message = "Não é possível excluir esta empresa pois há {$row_check['total']} produto(s) vinculado(s) a ela.";
        $message_type = "warning";
    } else {
        $sql_delete = "DELETE FROM empresas_representadas WHERE id = ?";
        if ($stmt_delete = $conn->prepare($sql_delete)) {
            $stmt_delete->bind_param("i", $empresa_id);
            if ($stmt_delete->execute()) {
                $message = "Empresa excluída com sucesso!";
                $message_type = "success";
            } else {
                $message = "Erro ao excluir a empresa: " . $stmt_delete->error;
                $message_type = "danger";
            }
            $stmt_delete->close();
        }
    }
    $stmt_check->close();
}

// --- Buscar todas as empresas ---
$sql_empresas = "SELECT 
    e.*,
    COUNT(p.id) as total_produtos,
    SUM(CASE WHEN p.quantidade_estoque <= p.estoque_minimo THEN 1 ELSE 0 END) as produtos_criticos,
    COUNT(v.id) as total_vendas,
    SUM(v.valor_total) as valor_vendas
FROM empresas_representadas e
LEFT JOIN produtos p ON e.id = p.empresa_id
LEFT JOIN vendas v ON e.id = v.empresa_id AND v.status_venda = 'concluida'
GROUP BY e.id
ORDER BY e.nome_empresa ASC";

// Estatísticas gerais (query separada — independente de paginação)
$stats_emp = $conn->query("SELECT COUNT(*) as total, SUM(status='ativo') as ativas, SUM(status!='ativo') as inativas, AVG(comissao_padrao) as comissao_media FROM empresas_representadas")->fetch_assoc();
$total_empresas    = (int)$stats_emp['total'];
$empresas_ativas   = (int)$stats_emp['ativas'];
$empresas_inativas = (int)$stats_emp['inativas'];
$comissao_media    = (float)($stats_emp['comissao_media'] ?? 0);

// Paginação
$emp_por_pag   = 20;
$emp_pag_atual = max(1, (int)($_GET['page'] ?? 1));
$emp_offset    = ($emp_pag_atual - 1) * $emp_por_pag;

// Busca paginada
$stmt_emp = $conn->prepare($sql_empresas . " LIMIT ? OFFSET ?");
$stmt_emp->bind_param("ii", $emp_por_pag, $emp_offset);
$stmt_emp->execute();
$result_empresas = $stmt_emp->get_result();
$stmt_emp->close();

$conn->close();

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

    .stats-value {
        font-size: 1.8rem !important;
    }

    /* Badges de status */
    .badge {
        font-size: 0.8rem !important;
        padding: 0.5rem 0.75rem !important;
    }

    /* Cabeçalho do card */
    .card-header-modern {
        padding: 1.25rem !important;
        flex-wrap: wrap;
        gap: 1rem;
    }

    /* Alertas */
    .alert {
        padding: 1rem 1.5rem !important;
        font-size: 0.95rem !important;
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

<!-- Page Header -->
<div class="page-header fade-in-up">
    <h1 class="page-title">
        <i class="fas fa-building"></i>
        Empresas Representadas
    </h1>
    <p class="page-subtitle">Gerencie as empresas que você representa</p>
</div>

<?php if (!empty($message)): ?>
    <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show fade-in-up" role="alert">
        <i class="fas fa-<?php echo $message_type == 'success' ? 'check-circle' : ($message_type == 'warning' ? 'exclamation-triangle' : 'times-circle'); ?> me-2"></i>
        <?php echo $message; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<!-- Stats Cards -->
<div class="row g-4 mb-4">
    <div class="col-6 col-lg-3">
        <div class="stats-card primary fade-in-up">
            <div class="stats-icon primary">
                <i class="fas fa-building"></i>
            </div>
            <div class="stats-value"><?php echo $total_empresas; ?></div>
            <div class="stats-label">Total de Empresas</div>
        </div>
    </div>
    
    <div class="col-6 col-lg-3">
        <div class="stats-card success fade-in-up">
            <div class="stats-icon success">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="stats-value"><?php echo $empresas_ativas; ?></div>
            <div class="stats-label">Empresas Ativas</div>
        </div>
    </div>
    
    <div class="col-6 col-lg-3">
        <div class="stats-card warning fade-in-up">
            <div class="stats-icon warning">
                <i class="fas fa-pause-circle"></i>
            </div>
            <div class="stats-value"><?php echo $empresas_inativas; ?></div>
            <div class="stats-label">Empresas Inativas</div>
        </div>
    </div>
    
    <div class="col-6 col-lg-3">
        <div class="stats-card info fade-in-up">
            <div class="stats-icon info">
                <i class="fas fa-percentage"></i>
            </div>
            <div class="stats-value"><?php echo number_format($comissao_media, 1); ?>%</div>
            <div class="stats-label">Comissão Média</div>
        </div>
    </div>
</div>

<!-- Action Bar -->
<div class="row g-4 mb-4">
    <div class="col-12">
        <div class="modern-card fade-in-up">
            <div class="card-body-modern">
                <div class="d-flex flex-column flex-md-row gap-3 justify-content-between align-items-md-center">
                    <div class="d-flex flex-column flex-sm-row gap-2">
                        <a href="cadastro_empresa.php" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i> Nova Empresa
                        </a>
                        <button class="btn btn-outline-primary" onclick="window.print()">
                            <i class="fas fa-print me-2"></i> Imprimir
                        </button>
                    </div>
                    <div class="d-flex gap-2 flex-grow-1 flex-md-grow-0">
                        <div class="input-group" style="max-width: 300px;">
                            <input type="text" class="form-control" placeholder="Buscar empresas..." id="searchInput">
                            <button class="btn btn-outline-primary" type="button">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Companies Table -->
<div class="modern-card fade-in-up">
    <div class="card-header-modern">
        <i class="fas fa-list"></i>
        Lista de Empresas Representadas
        <div class="ms-auto">
            <span class="badge bg-primary"><?php echo $total_empresas; ?> empresas</span>
        </div>
    </div>
    <div class="card-body-modern">
        <?php if ($total_empresas > 0): ?>
            <!-- Desktop Table -->
            <div class="table-modern d-none d-md-block">
                <table class="table table-hover mb-0" id="empresasTable">
                    <thead>
                        <tr>
                            <th>Empresa</th>
                            <th>Contato</th>
                            <th>Comissão</th>
                            <th>Produtos</th>
                            <th>Vendas</th>
                            <th>Status</th>
                            <th class="text-center">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        while($row = $result_empresas->fetch_assoc()) {
                            ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="company-avatar me-3">
                                            <?php echo strtoupper(substr($row['nome_empresa'], 0, 1)); ?>
                                        </div>
                                        <div>
                                            <div class="fw-semibold"><?php echo htmlspecialchars($row['nome_empresa']); ?></div>
                                            <small class="text-muted"><?php echo htmlspecialchars($row['cnpj'] ?: 'CNPJ não informado'); ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div>
                                        <?php if (!empty($row['contato_responsavel'])): ?>
                                            <div class="fw-semibold"><?php echo htmlspecialchars($row['contato_responsavel']); ?></div>
                                        <?php endif; ?>
                                        <?php if (!empty($row['telefone_responsavel'])): ?>
                                            <small class="text-muted"><?php echo htmlspecialchars($row['telefone_responsavel']); ?></small>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <span class="fw-bold text-success"><?php echo number_format($row['comissao_padrao'], 2); ?>%</span>
                                </td>
                                <td>
                                    <div>
                                        <span class="fw-semibold"><?php echo $row['total_produtos'] ?: 0; ?></span>
                                        <?php if ($row['produtos_criticos'] > 0): ?>
                                            <small class="text-warning d-block">
                                                <i class="fas fa-exclamation-triangle"></i> <?php echo $row['produtos_criticos']; ?> críticos
                                            </small>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <div>
                                        <div class="fw-semibold"><?php echo $row['total_vendas'] ?: 0; ?></div>
                                        <?php if ($row['valor_vendas'] > 0): ?>
                                            <small class="text-muted">R$ <?php echo number_format($row['valor_vendas'], 0, ',', '.'); ?></small>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <span class="status-badge <?php echo $row['status'] == 'ativo' ? 'status-success' : 'status-warning'; ?>">
                                        <?php echo ucfirst($row['status']); ?>
                                    </span>
                                </td>
                                <td class="text-center">
                                    <div class="btn-group" role="group">
                                        <a href="cadastro_empresa.php?id=<?php echo $row['id']; ?>"
                                           class="btn btn-outline-primary btn-sm" title="Editar">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="relatorio_empresa.php?id=<?php echo $row['id']; ?>"
                                           class="btn btn-outline-info btn-sm" title="Relatório">
                                            <i class="fas fa-chart-bar"></i>
                                        </a>
                                        <button class="btn btn-outline-danger btn-sm"
                                                onclick="confirmDelete(<?php echo $row['id']; ?>)" title="Excluir">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
            <?php echo render_pagination($total_empresas, $emp_pag_atual, $emp_por_pag); ?>
        <?php else: ?>
            <div class="text-center py-5">
                <div class="stats-icon primary mx-auto mb-3">
                    <i class="fas fa-building"></i>
                </div>
                <h5 class="text-muted mb-2">Nenhuma empresa cadastrada</h5>
                <p class="text-muted">Comece adicionando as empresas que você representa.</p>
                <a href="cadastro_empresa.php" class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i> Cadastrar Primeira Empresa
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function confirmDelete(id) {
    if (confirm('Tem certeza que deseja excluir esta empresa? Verifique se não há produtos vinculados.')) {
        window.location.href = `empresas_representadas.php?action=delete&id=${id}`;
    }
}

// Real-time search
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        searchInput.addEventListener('keyup', function() {
            const filter = this.value.toLowerCase();
            const table = document.getElementById('empresasTable');
            if (table) {
                const rows = table.getElementsByTagName('tr');
                for (let i = 1; i < rows.length; i++) {
                    const cells = rows[i].getElementsByTagName('td');
                    let found = false;
                    
                    for (let j = 0; j < cells.length - 1; j++) {
                        if (cells[j].textContent.toLowerCase().includes(filter)) {
                            found = true;
                            break;
                        }
                    }
                    
                    rows[i].style.display = found ? '' : 'none';
                }
            }
        });
    }
});
</script>

<?php include_once __DIR__ . '/includes/footer.php'; ?>

