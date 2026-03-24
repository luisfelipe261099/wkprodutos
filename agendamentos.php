<?php
require_once 'includes/session_bootstrap.php';

// Verifica se o usuário está logado
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: index.php");
    exit;
}

require_once 'includes/db_connect.php';
// --- Lógica para Alterar Status do Agendamento via Modal ---
if (isset($_POST['alterar_status'])) {
    $agendamento_id_update = trim($_POST['agendamento_id_status']);
    $novo_status = trim($_POST['novo_status_agendamento']);

    if (!empty($agendamento_id_update) && !empty($novo_status)) {
        $sql_update_status = "UPDATE agendamentos_entrega SET status_entrega = ? WHERE id = ?";
        if ($stmt_update_status = $conn->prepare($sql_update_status)) {
            $stmt_update_status->bind_param("si", $novo_status, $agendamento_id_update);
            if ($stmt_update_status->execute()) {
                $message = "Status do agendamento #" . htmlspecialchars($agendamento_id_update) . " atualizado para " . htmlspecialchars(ucfirst(str_replace('_', ' ', $novo_status))) . " com sucesso!";
                $message_type = "success";
            } else {
                $message = "Erro ao atualizar status do agendamento: " . $stmt_update_status->error;
                $message_type = "danger";
            }
            $stmt_update_status->close();
        } else {
            $message = "Erro na preparação da query de atualização de status: " . $conn->error;
            $message_type = "danger";
        }
    } else {
        $message = "ID do agendamento ou novo status inválido.";
        $message_type = "danger";
    }
}


if (!isset($message)) { $message = ''; $message_type = ''; }

// Stats dos agendamentos
$ag_stats = $conn->query("SELECT COUNT(*) as total,
    SUM(CASE WHEN status_entrega='agendado' THEN 1 ELSE 0 END) as agendados,
    SUM(CASE WHEN status_entrega='em_rota' THEN 1 ELSE 0 END) as em_rota,
    SUM(CASE WHEN status_entrega='entregue' THEN 1 ELSE 0 END) as entregues,
    SUM(CASE WHEN status_entrega='cancelado' THEN 1 ELSE 0 END) as cancelados
    FROM agendamentos_entrega")->fetch_assoc();

// Paginação
$ag_por_pag = 20;
$ag_pag_atual = max(1, (int)($_GET['page'] ?? 1));
$ag_offset = ($ag_pag_atual - 1) * $ag_por_pag;
$ag_total = (int)($ag_stats['total'] ?? 0);
$ag_total_pags = max(1, (int)ceil($ag_total / $ag_por_pag));

// Busca paginada
$stmt_ag = $conn->prepare("SELECT ae.id, c.nome AS nome_cliente, ae.data_hora_entrega, ae.endereco_entrega, ae.status_entrega, ae.venda_id, ae.orcamento_id
    FROM agendamentos_entrega ae
    JOIN clientes c ON ae.cliente_id = c.id
    ORDER BY ae.data_hora_entrega DESC LIMIT ? OFFSET ?");
$stmt_ag->bind_param("ii", $ag_por_pag, $ag_offset);
$stmt_ag->execute();
$result_agendamentos = $stmt_ag->get_result();
$stmt_ag->close();

$conn->close();

include_once 'includes/header.php';
?>

<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h1 class="page-title"><i class="fas fa-calendar-alt me-2"></i>Agendamentos</h1>
        <p class="page-subtitle">Gerenciamento de agendamentos de entrega</p>
    </div>
    <a href="agendar_entrega.php" class="btn btn-accent">
        <i class="fas fa-plus me-1"></i> Novo Agendamento
    </a>
</div>

<?php if (!empty($message)): ?>
    <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
        <?php echo $message; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="row g-3 mb-4 stats-grid">
    <div class="col-6 col-md-2">
        <div class="stats-card">
            <div class="stats-icon primary"><i class="fas fa-calendar-alt"></i></div>
            <div class="stats-value"><?php echo fmt_num_short((float)($ag_stats['total'] ?? 0), 0); ?></div>
            <div class="stats-label">Total</div>
        </div>
    </div>
    <div class="col-6 col-md-2">
        <div class="stats-card">
            <div class="stats-icon info"><i class="fas fa-clock"></i></div>
            <div class="stats-value"><?php echo fmt_num_short((float)($ag_stats['agendados'] ?? 0), 0); ?></div>
            <div class="stats-label">Agendados</div>
        </div>
    </div>
    <div class="col-6 col-md-2">
        <div class="stats-card">
            <div class="stats-icon warning"><i class="fas fa-truck"></i></div>
            <div class="stats-value"><?php echo fmt_num_short((float)($ag_stats['em_rota'] ?? 0), 0); ?></div>
            <div class="stats-label">Em Rota</div>
        </div>
    </div>
    <div class="col-6 col-md-2">
        <div class="stats-card">
            <div class="stats-icon success"><i class="fas fa-check-circle"></i></div>
            <div class="stats-value"><?php echo fmt_num_short((float)($ag_stats['entregues'] ?? 0), 0); ?></div>
            <div class="stats-label">Entregues</div>
        </div>
    </div>
    <div class="col-6 col-md-2">
        <div class="stats-card">
            <div class="stats-icon danger"><i class="fas fa-times-circle"></i></div>
            <div class="stats-value"><?php echo fmt_num_short((float)($ag_stats['cancelados'] ?? 0), 0); ?></div>
            <div class="stats-label">Cancelados</div>
        </div>
    </div>
</div>

<div class="modern-card">
    <div class="modern-card-header">
        <h5 class="modern-card-title"><i class="fas fa-list me-2"></i>Lista de Agendamentos</h5>
    </div>
    <div class="card-body-modern">
        <div class="table-responsive">
                <table class="table table-hover">
                <thead>
                    <tr>
                        <th>ID Agendamento</th>
                        <th>Cliente</th>
                        <th>Data/Hora Entrega</th>
                        <th>Endereço</th>
                        <th>Venda/Orçamento</th>
                        <th>Status</th>
                        <th class="text-center">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if ($result_agendamentos->num_rows > 0) {
                        while($row = $result_agendamentos->fetch_assoc()) {
                            // Define a classe de badge para o status
                            $status_class = '';
                            switch ($row['status_entrega']) {
                                case 'agendado':
                                    $status_class = 'bg-primary';
                                    break;
                                case 'em_rota':
                                    $status_class = 'bg-info';
                                    break;
                                case 'entregue':
                                    $status_class = 'bg-success';
                                    break;
                                case 'cancelado':
                                    $status_class = 'bg-danger';
                                    break;
                                default:
                                    $status_class = 'bg-secondary';
                            }

                            $referencia = 'N/A';
                            if ($row['venda_id']) {
                                $referencia = '<a href="detalhes_venda.php?id=' . htmlspecialchars($row['venda_id']) . '" class="text-decoration-none">Venda #' . htmlspecialchars($row['venda_id']) . '</a>';
                            } elseif ($row['orcamento_id']) {
                                $referencia = '<a href="detalhes_orcamento.php?id=' . htmlspecialchars($row['orcamento_id']) . '" class="text-decoration-none">Orçamento #' . htmlspecialchars($row['orcamento_id']) . '</a>';
                            }
                            ?>
                            <tr>
                                <td data-label="ID"><?php echo htmlspecialchars($row['id']); ?></td>
                                <td data-label="Cliente"><?php echo htmlspecialchars($row['nome_cliente']); ?></td>
                                <td data-label="Data/Hora"><?php echo date('d/m/Y H:i', strtotime($row['data_hora_entrega'])); ?></td>
                                <td data-label="Endereço" class="d-none d-lg-table-cell"><?php echo htmlspecialchars($row['endereco_entrega']); ?></td>
                                <td data-label="Referência" class="d-none d-md-table-cell"><?php echo $referencia; ?></td>
                                <td data-label="Status"><span class="badge <?php echo $status_class; ?>"><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $row['status_entrega']))); ?></span></td>
                                <td class="text-center" data-label="Ações">
                                    <div class="btn-group btn-group-sm mobile-agendamento-actions" role="group">
                                        <a href="detalhes_agendamento.php?id=<?php echo $row['id']; ?>" class="btn btn-info btn-sm" title="Ver Detalhes">
                                            <i class="fas fa-eye"></i>
                                            <span class="d-md-none ms-1">Detalhes</span>
                                        </a>
                                        <a href="agendar_entrega.php?id=<?php echo $row['id']; ?>" class="btn btn-primary btn-sm" title="Editar">
                                            <i class="fas fa-edit"></i>
                                            <span class="d-md-none ms-1">Editar</span>
                                        </a>
                                        <button type="button" class="btn btn-warning btn-sm" title="Alterar Status" data-bs-toggle="modal" data-bs-target="#modalStatusAgendamento" data-id="<?php echo $row['id']; ?>" data-current-status="<?php echo $row['status_entrega']; ?>">
                                            <i class="fas fa-sync-alt"></i>
                                            <span class="d-md-none ms-1">Status</span>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php
                        }
                    } else {
                        echo '<tr><td colspan="7" class="text-center">Nenhum agendamento de entrega registrado ainda.</td></tr>';
                    }
                    ?>
                </tbody>
            </table>
        </div>
        <?php echo render_pagination($ag_total, $ag_pag_atual, $ag_por_pag); ?>
    </div>
</div>

<div class="modal fade" id="modalStatusAgendamento" tabindex="-1" aria-labelledby="modalStatusAgendamentoLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalStatusAgendamentoLabel">Alterar Status do Agendamento</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="agendamentos.php" method="post">
                <div class="modal-body">
                    <input type="hidden" name="agendamento_id_status" id="agendamento_id_status">
                    <div class="mb-3">
                        <label for="novo_status_agendamento" class="form-label">Novo Status:</label>
                        <select class="form-select" id="novo_status_agendamento" name="novo_status_agendamento" required>
                            <option value="agendado">Agendado</option>
                            <option value="em_rota">Em Rota</option>
                            <option value="entregue">Entregue</option>
                            <option value="cancelado">Cancelado</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" name="alterar_status" class="btn btn-primary">Salvar Alterações</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include_once 'includes/footer.php'; ?>

<script>
    // Script para preencher o modal com os dados do agendamento
    var modalStatusAgendamento = document.getElementById('modalStatusAgendamento');
    if (modalStatusAgendamento) {
        var modalIdInput = modalStatusAgendamento.querySelector('#agendamento_id_status');
        var modalStatusSelect = modalStatusAgendamento.querySelector('#novo_status_agendamento');
        var modalForm = modalStatusAgendamento.querySelector('form');

        function preencherModalAgendamento(triggerElement) {
            if (!triggerElement || !modalIdInput || !modalStatusSelect) {
                return;
            }

            modalIdInput.value = triggerElement.getAttribute('data-id') || '';
            modalStatusSelect.value = triggerElement.getAttribute('data-current-status') || 'agendado';
        }

        document.addEventListener('click', function (event) {
            var triggerElement = event.target.closest('[data-bs-target="#modalStatusAgendamento"]');
            if (triggerElement) {
                preencherModalAgendamento(triggerElement);
            }
        });

        modalStatusAgendamento.addEventListener('show.bs.modal', function (event) {
            var triggerElement = event.relatedTarget ? event.relatedTarget.closest('[data-bs-target="#modalStatusAgendamento"]') : null;
            if (!triggerElement) {
                return;
            }

            preencherModalAgendamento(triggerElement);
        });

        if (modalForm) {
            modalForm.addEventListener('submit', function (event) {
                var agendamentoId = parseInt(modalIdInput.value, 10);
                var submitButton = modalForm.querySelector('button[type="submit"]');

                if (!Number.isInteger(agendamentoId) || agendamentoId <= 0) {
                    event.preventDefault();
                    alert('Nao foi possivel identificar o agendamento. Feche o modal e tente novamente.');
                    return;
                }

                if (submitButton) {
                    submitButton.disabled = true;
                    submitButton.textContent = 'Salvando...';
                }
            });
        }
    }
</script>
