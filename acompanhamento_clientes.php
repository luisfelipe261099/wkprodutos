<?php
require_once 'includes/session_bootstrap.php';
require_once 'includes/db_connect.php';

// Verifica se o usuÃ¡rio estÃ¡ logado
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: index.php");
    exit;
}

$message = $_SESSION['flash_message'] ?? '';
$message_type = $_SESSION['flash_message_type'] ?? '';
unset($_SESSION['flash_message'], $_SESSION['flash_message_type']);

// --- LÃ“GICA DA PÃGINA ---

// LÃ³gica de Concluir lembrete (movida para o topo para executar antes da listagem)
if (isset($_GET['action']) && $_GET['action'] == 'concluir' && isset($_GET['id'])) {
    $lembrete_id_acao = intval($_GET['id']);
    $sql_update_status = "UPDATE lembretes_acompanhamento SET status_lembrete = 'ConcluÃ­do' WHERE id = ?";
    $stmt_update = $conn->prepare($sql_update_status);
    $stmt_update->bind_param("i", $lembrete_id_acao);
    if ($stmt_update->execute()) {
        $_SESSION['flash_message'] = "Lembrete marcado como concluÃ­do!";
        $_SESSION['flash_message_type'] = "success";
    } else {
        $_SESSION['flash_message'] = "Erro ao atualizar status do lembrete.";
        $_SESSION['flash_message_type'] = "danger";
    }
    $stmt_update->close();
    // Limpa os parÃ¢metros GET da URL para evitar re-execuÃ§Ã£o ao recarregar
    header("Location: acompanhamento_clientes.php");
    exit;
}

// 1. DEFINIÃ‡Ã•ES DE PAGINAÃ‡ÃƒO E FILTROS
$registros_por_pagina = 15;
$pagina_atual = isset($_GET['pagina']) && is_numeric($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$offset = ($pagina_atual - 1) * $registros_por_pagina;

$filtro_status = $_GET['filtro_status'] ?? 'Pendente'; 
$termo_busca = $_GET['q'] ?? '';

// 2. MONTAGEM DINÃ‚MICA DA CONSULTA SQL COM FILTROS
$params = [];
$types = "";
$where_conditions = [];

// Filtro por status
if ($filtro_status != 'Todos') {
    $where_conditions[] = "la.status_lembrete = ?";
    $params[] = $filtro_status;
    $types .= "s";
}

// Filtro por termo de busca (no nome do cliente ou na descriÃ§Ã£o)
if (!empty($termo_busca)) {
    $where_conditions[] = "(c.nome LIKE ? OR la.descricao LIKE ?)";
    $like_term = "%{$termo_busca}%";
    $params[] = $like_term;
    $params[] = $like_term;
    $types .= "ss";
}

$base_sql = "FROM lembretes_acompanhamento la 
             JOIN clientes c ON la.cliente_id = c.id
             LEFT JOIN usuarios u ON la.usuario_id = u.id";

$where_sql = "";
if (!empty($where_conditions)) {
    $where_sql = " WHERE " . implode(" AND ", $where_conditions);
}

// 3. OBTER TOTAL DE REGISTROS PARA PAGINAÃ‡ÃƒO
$sql_total = "SELECT COUNT(la.id) as total " . $base_sql . $where_sql;
$stmt_total = $conn->prepare($sql_total);
if (!empty($types)) {
    $stmt_total->bind_param($types, ...$params);
}
$stmt_total->execute();
$total_registros = $stmt_total->get_result()->fetch_assoc()['total'];
$total_paginas = ceil($total_registros / $registros_por_pagina);
$stmt_total->close();

// 4. OBTER REGISTROS DA PÃGINA ATUAL
$sql_lembretes = "SELECT la.*, c.nome AS nome_cliente, u.nome as nome_usuario " . $base_sql . $where_sql . " ORDER BY la.data_lembrete ASC, la.id DESC LIMIT ? OFFSET ?";
$types .= "ii"; // Adiciona os tipos para LIMIT e OFFSET
$params[] = $registros_por_pagina;
$params[] = $offset;

$stmt_lembretes = $conn->prepare($sql_lembretes);
if (!empty($types)) {
    $stmt_lembretes->bind_param($types, ...$params);
}
$stmt_lembretes->execute();
$result_lembretes = $stmt_lembretes->get_result();
$lembretes = $result_lembretes->fetch_all(MYSQLI_ASSOC);
$stmt_lembretes->close();

include_once 'includes/header.php';
?>

<div class="page-header fade-in-up">
    <h1 class="page-title"><i class="fas fa-tasks"></i> GestÃ£o de Acompanhamentos</h1>
    <p class="page-subtitle">Gerencie seus lembretes de forma eficiente com busca e paginaÃ§Ã£o.</p>
</div>

<?php if (!empty($message)): ?>
    <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show fade-in-up" role="alert">
        <i class="fas fa-<?php echo $message_type == 'success' ? 'check-circle' : 'info-circle'; ?> me-2"></i>
        <?php echo htmlspecialchars($message); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="modern-card fade-in-up mb-4">
    <div class="card-header-modern">
        <i class="fas fa-filter"></i> Filtros de Busca
    </div>
    <div class="card-body-modern">
        <form method="GET" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" class="row g-3 align-items-end">
            <div class="col-md-5">
                <label for="q" class="form-label">Buscar por Cliente ou DescriÃ§Ã£o</label>
                <input type="search" name="q" id="q" class="form-control" value="<?php echo htmlspecialchars($termo_busca); ?>" placeholder="Ex: JoÃ£o Silva ou 'recompra'">
            </div>
            <div class="col-md-4">
                <label for="filtro_status" class="form-label">Filtrar por Status</label>
                <select name="filtro_status" id="filtro_status" class="form-select">
                    <option value="Pendente" <?php echo ($filtro_status == 'Pendente' ? 'selected' : ''); ?>>Pendentes</option>
                    <option value="ConcluÃ­do" <?php echo ($filtro_status == 'ConcluÃ­do' ? 'selected' : ''); ?>>ConcluÃ­dos</option>
                    <option value="Todos" <?php echo ($filtro_status == 'Todos' ? 'selected' : ''); ?>>Todos</option>
                </select>
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-primary w-100"><i class="fas fa-search me-2"></i>Aplicar Filtros</button>
            </div>
        </form>
    </div>
</div>

<div class="modern-card fade-in-up">
    <div class="card-header-modern d-flex justify-content-between align-items-center">
        <span><i class="fas fa-list"></i> Lista de Lembretes</span>
        <span class="badge bg-primary rounded-pill">
            <?php echo $total_registros; ?> registro(s) encontrado(s)
        </span>
    </div>
    <div class="card-body-modern">
        <div class="table-responsive">
            <table class="table table-hover table-striped">
                <thead>
                    <tr>
                        <th>Prioridade</th>
                        <th>Data</th>
                        <th>Cliente</th>
                        <th>DescriÃ§Ã£o</th>
                        <th>Venda Assoc.</th>
                        <th>Status</th>
                        <th>Criado por</th>
                        <th class="text-center">AÃ§Ãµes</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($lembretes) > 0): ?>
                        <?php foreach ($lembretes as $lembrete): 
                            $is_overdue = (strtotime($lembrete['data_lembrete']) < strtotime('today')) && $lembrete['status_lembrete'] == 'Pendente';
                        ?>
                            <tr class="<?php echo $is_overdue ? 'table-danger' : ''; ?>">
                                <td class="text-center align-middle">
                                    <?php if($is_overdue): ?><i class="fas fa-exclamation-circle text-danger fs-5" title="Atrasado!"></i>
                                    <?php elseif($lembrete['status_lembrete'] == 'Pendente'): ?><i class="fas fa-clock text-warning fs-5" title="Pendente"></i>
                                    <?php else: ?><i class="fas fa-check-circle text-success fs-5" title="ConcluÃ­do"></i><?php endif; ?>
                                </td>
                                <td class="align-middle fw-bold"><?php echo date('d/m/Y', strtotime($lembrete['data_lembrete'])); ?></td>
                                <td class="align-middle"><?php echo htmlspecialchars($lembrete['nome_cliente']); ?></td>
                                <td class="align-middle" style="max-width: 350px; white-space: pre-wrap; word-wrap: break-word;"><?php echo nl2br(htmlspecialchars($lembrete['descricao'])); ?></td>
                                <td class="align-middle text-center">
                                    <?php if (!empty($lembrete['venda_id'])): ?>
                                        <a href="ver_venda.php?id=<?php echo $lembrete['venda_id']; ?>" class="btn btn-sm btn-outline-secondary" title="Ver Venda Associada">#<?php echo $lembrete['venda_id']; ?></a>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                                <td class="align-middle">
                                    <span class="badge fs-6 bg-<?php 
                                        switch ($lembrete['status_lembrete']) {
                                            case 'Pendente': echo $is_overdue ? 'danger' : 'warning text-dark'; break;
                                            case 'ConcluÃ­do': echo 'success'; break;
                                            default: echo 'secondary';
                                        }
                                    ?>"><?php echo htmlspecialchars($lembrete['status_lembrete']); ?></span>
                                </td>
                                <td class="align-middle"><?php echo htmlspecialchars($lembrete['nome_usuario'] ?? 'Sistema'); ?></td>
                                <td class="text-center align-middle">
                                    <div class="btn-group">
                                        <?php if ($lembrete['status_lembrete'] == 'Pendente'): ?>
                                            <a href="acompanhamento_clientes.php?action=concluir&id=<?php echo $lembrete['id']; ?>" class="btn btn-sm btn-success" title="Marcar como ConcluÃ­do" onclick="return confirm('Marcar este lembrete como concluÃ­do?');"><i class="fas fa-check"></i></a>
                                        <?php endif; ?>
                                        <a href="criar_acompanhamento_venda.php?venda_id=<?php echo $lembrete['venda_id']; ?>" class="btn btn-sm btn-info" title="Criar Novo Acompanhamento para esta Venda"><i class="fas fa-plus"></i></a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="8" class="text-center p-4">Nenhum lembrete encontrado para os filtros selecionados.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <?php
        echo render_pagination([
            'current_page' => $pagina_atual,
            'total_pages' => $total_paginas,
            'page_param' => 'pagina',
            'base_params' => [
                'filtro_status' => $filtro_status,
                'q' => $termo_busca
            ],
            'aria_label' => 'Paginacao de acompanhamentos',
            'summary' => 'Mostrando de ' . min($total_registros, $offset + 1) . ' a ' . min($total_registros, $offset + $registros_por_pagina) . ' de ' . $total_registros . ' registros',
            'window' => 5
        ]);
        ?>

    </div>
</div>

<?php
$conn->close();
include_once 'includes/footer.php';
?>
