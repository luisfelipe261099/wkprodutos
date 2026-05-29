<?php
require_once __DIR__ . '/includes/session_bootstrap.php';

// Verifica se o usuário está logado e é admin
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}

// Verificar se é admin
if ($_SESSION["nivel_acesso"] !== "admin") {
    header("location: dashboard.php");
    exit;
}

require_once __DIR__ . '/includes/db_connect.php';

$message = '';
$message_type = '';

// --- Lógica para Exclusão de Usuário ---
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $user_id = $_GET['id'];

    // Não permitir excluir o próprio usuário
    if ($user_id == $_SESSION['id']) {
        $message = "Você não pode excluir seu próprio usuário.";
        $message_type = "warning";
    } else {
        $sql_delete = "DELETE FROM usuarios WHERE id = ?";
        if ($stmt_delete = $conn->prepare($sql_delete)) {
            $stmt_delete->bind_param("i", $user_id);
            if ($stmt_delete->execute()) {
                $message = "Usuário excluído com sucesso!";
                $message_type = "success";
            } else {
                $message = "Erro ao excluir usuário: " . $stmt_delete->error;
                $message_type = "danger";
            }
            $stmt_delete->close();
        }
    }
}

// --- Buscar todos os usuários ---
// Estatísticas gerais (query separada — independente de paginação)
$stats_usr = $conn->query("SELECT COUNT(*) as total, SUM(nivel_acesso='admin') as admins, SUM(nivel_acesso!='admin') as normais FROM usuarios")->fetch_assoc();
$total_usuarios  = (int)$stats_usr['total'];
$admins          = (int)$stats_usr['admins'];
$usuarios_normais = (int)$stats_usr['normais'];

// Paginação
$usr_por_pag   = 20;
$usr_pag_atual = max(1, (int)($_GET['page'] ?? 1));
$usr_offset    = ($usr_pag_atual - 1) * $usr_por_pag;

// Busca paginada
$sql_usuarios = "SELECT id, nome, email, nivel_acesso, data_cadastro FROM usuarios ORDER BY nome ASC";
$stmt_usr = $conn->prepare($sql_usuarios . " LIMIT ? OFFSET ?");
$stmt_usr->bind_param("ii", $usr_por_pag, $usr_offset);
$stmt_usr->execute();
$result_usuarios = $stmt_usr->get_result();
$stmt_usr->close();

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

    /* Botão de novo usuário */
    .btn-primary {
        min-height: 44px !important;
        padding: 0.75rem 1.5rem !important;
        font-weight: 500 !important;
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
        <i class="fas fa-users-cog"></i>
        Gestão de Usuários
    </h1>
    <p class="page-subtitle">Gerencie os usuários do sistema</p>
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
                <i class="fas fa-users"></i>
            </div>
            <div class="stats-value"><?php echo $total_usuarios; ?></div>
            <div class="stats-label">Total de Usuários</div>
        </div>
    </div>

    <div class="col-6 col-lg-3">
        <div class="stats-card success fade-in-up">
            <div class="stats-icon success">
                <i class="fas fa-user-shield"></i>
            </div>
            <div class="stats-value"><?php echo $admins; ?></div>
            <div class="stats-label">Administradores</div>
        </div>
    </div>

    <div class="col-6 col-lg-3">
        <div class="stats-card info fade-in-up">
            <div class="stats-icon info">
                <i class="fas fa-user"></i>
            </div>
            <div class="stats-value"><?php echo $usuarios_normais; ?></div>
            <div class="stats-label">Colaboradores</div>
        </div>
    </div>

    <div class="col-6 col-lg-3">
        <div class="stats-card warning fade-in-up">
            <div class="stats-icon warning">
                <i class="fas fa-calendar-check"></i>
            </div>
            <div class="stats-value"><?php echo date('d/m'); ?></div>
            <div class="stats-label">Data Atual</div>
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
                        <a href="cadastro_usuario.php" class="btn btn-primary">
                            <i class="fas fa-user-plus me-2"></i> Novo Usuário
                        </a>
                        <a href="perfil.php" class="btn btn-outline-primary">
                            <i class="fas fa-user-edit me-2"></i> Meu Perfil
                        </a>
                    </div>
                    <div class="d-flex gap-2 flex-grow-1 flex-md-grow-0">
                        <div class="input-group" style="max-width: 300px;">
                            <input type="text" class="form-control" placeholder="Buscar usuários..." id="searchInput">
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

<!-- Users Table -->
<div class="modern-card fade-in-up">
    <div class="card-header-modern">
        <i class="fas fa-list"></i>
        Lista de Usuários
        <div class="ms-auto">
            <span class="badge bg-primary"><?php echo $total_usuarios; ?> usuários</span>
        </div>
    </div>
    <div class="card-body-modern">
        <?php if ($total_usuarios > 0): ?>
            <!-- Desktop Table -->
            <div class="table-modern d-none d-md-block">
                <table class="table table-hover mb-0" id="usuariosTable">
                    <thead>
                        <tr>
                            <th>Usuário</th>
                            <th>Email</th>
                            <th>Nível</th>
                            <th>Cadastro</th>
                            <th>Status</th>
                            <th class="text-center">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $result_usuarios->data_seek(0);
                        while($row = $result_usuarios->fetch_assoc()) {
                            $is_current_user = ($row['id'] == $_SESSION['id']);
                            $is_active = isset($row['ativo']) ? $row['ativo'] : true;
                            ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="user-avatar me-3">
                                            <?php echo strtoupper(substr($row['nome'], 0, 1)); ?>
                                        </div>
                                        <div>
                                            <div class="fw-semibold">
                                                <?php echo htmlspecialchars($row['nome']); ?>
                                                <?php if ($is_current_user): ?>
                                                    <span class="badge bg-info ms-2">Você</span>
                                                <?php endif; ?>
                                            </div>
                                            <small class="text-muted">#<?php echo $row['id']; ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($row['email']); ?></td>
                                <td>
                                    <span class="status-badge <?php echo $row['nivel_acesso'] == 'admin' ? 'status-success' : 'status-info'; ?>">
                                        <?php echo $row['nivel_acesso'] == 'admin' ? 'Admin' : 'Colaborador'; ?>
                                    </span>
                                </td>
                                <td><?php echo date('d/m/Y', strtotime($row['data_cadastro'])); ?></td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <?php if ($is_active): ?>
                                            <i class="fas fa-circle text-success me-2" style="font-size: 0.5rem;"></i>
                                            <span>Ativo</span>
                                        <?php else: ?>
                                            <i class="fas fa-circle text-muted me-2" style="font-size: 0.5rem;"></i>
                                            <span>Inativo</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <div class="btn-group" role="group">
                                        <a href="cadastro_usuario.php?id=<?php echo $row['id']; ?>"
                                           class="btn btn-outline-primary btn-sm" title="Editar">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <?php if (!$is_current_user): ?>
                                            <button class="btn btn-outline-danger btn-sm"
                                                    onclick="confirmDelete(<?php echo $row['id']; ?>)" title="Excluir">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>

            <!-- Mobile Cards -->
            <div class="d-block d-md-none">
                        <?php
                        $result_usuarios->data_seek(0);
                        while($row = $result_usuarios->fetch_assoc()) {
                    $is_current_user = ($row['id'] == $_SESSION['id']);
                    $is_active = isset($row['ativo']) ? $row['ativo'] : true;
                    ?>
                    <div class="mobile-user-card mb-3">
                        <div class="d-flex align-items-start mb-3">
                            <div class="user-avatar me-3">
                                <?php echo strtoupper(substr($row['nome'], 0, 1)); ?>
                            </div>
                            <div class="flex-grow-1">
                                <h6 class="mb-1">
                                    <?php echo htmlspecialchars($row['nome']); ?>
                                    <?php if ($is_current_user): ?>
                                        <span class="badge bg-info ms-2">Você</span>
                                    <?php endif; ?>
                                </h6>
                                <div class="d-flex justify-content-between align-items-center">
                                    <small class="text-muted"><?php echo htmlspecialchars($row['email']); ?></small>
                                    <span class="status-badge <?php echo $row['nivel_acesso'] == 'admin' ? 'status-success' : 'status-info'; ?>">
                                        <?php echo $row['nivel_acesso'] == 'admin' ? 'Admin' : 'Colaborador'; ?>
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-6">
                                <div class="mobile-info-item">
                                    <small class="text-muted">Cadastro</small>
                                    <div><?php echo date('d/m/Y', strtotime($row['data_cadastro'])); ?></div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="mobile-info-item">
                                    <small class="text-muted">Status</small>
                                    <div class="d-flex align-items-center">
                                        <?php if ($is_active): ?>
                                            <i class="fas fa-circle text-success me-1" style="font-size: 0.5rem;"></i>
                                            <span>Ativo</span>
                                        <?php else: ?>
                                            <i class="fas fa-circle text-muted me-1" style="font-size: 0.5rem;"></i>
                                            <span>Inativo</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex gap-2">
                            <a href="cadastro_usuario.php?id=<?php echo $row['id']; ?>"
                               class="btn btn-outline-primary btn-sm flex-fill">
                                <i class="fas fa-edit me-1"></i> Editar
                            </a>
                            <?php if (!$is_current_user): ?>
                                <button class="btn btn-outline-danger btn-sm"
                                        onclick="confirmDelete(<?php echo $row['id']; ?>)">
                                    <i class="fas fa-trash"></i>
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php } ?>
            </div>
        <?php else: ?>
            <div class="text-center py-5">
                <div class="stats-icon primary mx-auto mb-3">
                    <i class="fas fa-users"></i>
                </div>
                <h5 class="text-muted mb-2">Nenhum usuário encontrado</h5>
                <p class="text-muted">Adicione usuários ao sistema.</p>
                <a href="cadastro_usuario.php" class="btn btn-primary">
                    <i class="fas fa-user-plus me-2"></i> Adicionar Primeiro Usuário
                </a>
            </div>
        <?php endif; ?>
            <?php echo render_pagination($total_usuarios, $usr_pag_atual, $usr_por_pag); ?>
    </div>
</div>

<script>
function confirmDelete(id) {
    if (confirm('Tem certeza que deseja excluir este usuário? Esta ação não pode ser desfeita.')) {
        window.location.href = `usuarios.php?action=delete&id=${id}`;
    }
}

// Real-time search
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        searchInput.addEventListener('keyup', function() {
            const filter = this.value.toLowerCase();
            const table = document.getElementById('usuariosTable');
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

            // Search in mobile cards
            const mobileCards = document.querySelectorAll('.mobile-user-card');
            mobileCards.forEach(card => {
                const text = card.textContent.toLowerCase();
                card.style.display = text.includes(filter) ? '' : 'none';
            });
        });
    }
});
</script>

<?php include_once __DIR__ . '/includes/footer.php'; ?>

