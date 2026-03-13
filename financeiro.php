<?php

require_once 'includes/session_bootstrap.php';

// Verifica se o usuário está logado
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: index.php");
    exit;
}

require_once 'includes/db_connect.php';

$message = '';
$message_type = '';

// Lógica para Exclusão de Transação
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $transacao_id_to_delete = $_GET['id'];
    $sql_delete = "DELETE FROM transacoes_financeiras WHERE id = ?";
    if ($stmt_delete = $conn->prepare($sql_delete)) {
        $stmt_delete->bind_param("i", $transacao_id_to_delete);
        if ($stmt_delete->execute()) {
            $message = "Transação excluída com sucesso!";
            $message_type = "success";
        } else {
            $message = "Erro ao excluir a transação: " . $stmt_delete->error;
            $message_type = "danger";
        }
        $stmt_delete->close();
    }
}

// --- NOVOS CÁLCULOS FINANCEIROS GLOBAIS ---

// 1. Calcular Receita Bruta Total (todas as vendas concluídas)
$receita_total_vendas = 0;
$sql_receita = "SELECT SUM(valor_total) FROM vendas WHERE status_venda = 'concluida'";
if ($result = $conn->query($sql_receita)) {
    $receita_total_vendas = $result->fetch_row()[0] ?? 0;
}

// 2. Calcular Lucro Total (baseado no percentual de lucro dos produtos vendidos)
$lucro_total_vendas = 0;
$sql_lucro = "SELECT SUM(iv.preco_unitario * iv.quantidade * (p.percentual_lucro / 100))
              FROM vendas v
              JOIN itens_venda iv ON v.id = iv.venda_id
              JOIN produtos p ON iv.produto_id = p.id
              WHERE v.status_venda = 'concluida'";
if ($result = $conn->query($sql_lucro)) {
    $lucro_total_vendas = $result->fetch_row()[0] ?? 0;
}

// 3. Calcular Entradas e Saídas Manuais (da tabela transacoes_financeiras)
$outras_entradas = 0;
$total_despesas = 0;
$sql_transacoes_manuais = "SELECT tipo, SUM(valor) AS total FROM transacoes_financeiras GROUP BY tipo";
if ($result = $conn->query($sql_transacoes_manuais)) {
    while($row = $result->fetch_assoc()) {
        if ($row['tipo'] == 'entrada') {
            $outras_entradas = $row['total'];
        } else {
            $total_despesas = $row['total'];
        }
    }
}

// 4. Calcular o Saldo Líquido Final - CORREÇÃO: Lucro das Vendas menos Despesas
$saldo_liquido = $lucro_total_vendas - $total_despesas;

// Lógica de paginação para transações
$resultados_por_pagina = 15;
$pagina_atual = max(1, (int) ($_GET['page'] ?? 1));
$offset = ($pagina_atual - 1) * $resultados_por_pagina;

// Contar total de transações
$sql_count_transacoes = "SELECT COUNT(*) as total FROM transacoes_financeiras";
$result_count = $conn->query($sql_count_transacoes);
$total_transacoes_registros = $result_count->fetch_assoc()['total'] ?? 0;
$total_paginas_transacoes = ceil($total_transacoes_registros / $resultados_por_pagina);

// Lógica para buscar o histórico de transações para a tabela (paginado)
$sql_select_transacoes = "SELECT id, tipo, valor, data_transacao, descricao, categoria, referencia_id, tabela_referencia
                         FROM transacoes_financeiras
                         ORDER BY data_transacao DESC
                         LIMIT ? OFFSET ?";
$stmt = $conn->prepare($sql_select_transacoes);
$stmt->bind_param("ii", $resultados_por_pagina, $offset);
$stmt->execute();
$result_transacoes = $stmt->get_result();

include_once 'includes/header.php';
?>

<div class="page-header fade-in-up">
    <h1 class="page-title">
        <i class="fas fa-chart-pie"></i>
        Visão Financeira
    </h1>
    <p class="page-subtitle">
        Acompanhe a receita, lucro, despesas e o fluxo de caixa do seu negócio.
    </p>
</div>

<?php if (!empty($message)): ?>
    <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show fade-in-up" role="alert">
        <i class="fas fa-<?php echo $message_type == 'success' ? 'check-circle' : 'exclamation-triangle'; ?> me-2"></i>
        <?php echo $message; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="row g-4 mb-4 fade-in-up">
    <div class="col-6 col-lg-3">
        <div class="stats-card primary">
            <div class="stats-icon primary">
                <i class="fas fa-hand-holding-usd"></i>
            </div>
            <div class="stats-value"><?php echo fmt_brl($receita_total_vendas); ?></div>
            <div class="stats-label">Receita de Vendas</div>
        </div>
    </div>

    <div class="col-6 col-lg-3">
        <div class="stats-card success">
            <div class="stats-icon success">
                <i class="fas fa-dollar-sign"></i>
            </div>
            <div class="stats-value"><?php echo fmt_brl($lucro_total_vendas); ?></div>
            <div class="stats-label">Lucro das Vendas</div>
        </div>
    </div>

    <div class="col-6 col-lg-3">
        <div class="stats-card danger">
            <div class="stats-icon danger">
                <i class="fas fa-arrow-down"></i>
            </div>
            <div class="stats-value"><?php echo fmt_brl($total_despesas); ?></div>
            <div class="stats-label">Despesas (Saídas)</div>
        </div>
    </div>
    
    <div class="col-6 col-lg-3">
        <div class="stats-card <?php echo ($saldo_liquido >= 0 ? 'info' : 'warning'); ?>">
            <div class="stats-icon <?php echo ($saldo_liquido >= 0 ? 'info' : 'warning'); ?>">
                <i class="fas fa-wallet"></i>
            </div>
            <div class="stats-value"><?php echo fmt_brl($saldo_liquido); ?></div>
            <div class="stats-label">Saldo Líquido</div>
        </div>
    </div>
</div>

<div class="modern-card fade-in-up">
    <div class="card-header-modern">
        <i class="fas fa-list"></i>
        Histórico de Transações Manuais
        <div class="ms-auto">
            <a href="registrar_transacao.php" class="btn btn-primary btn-sm">
                <i class="fas fa-plus me-1"></i> Nova Transação Avulsa
            </a>
        </div>
    </div>
    <div class="card-body-modern">
        <?php if ($result_transacoes && $result_transacoes->num_rows > 0): ?>
            <div class="table-modern">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Tipo</th>
                            <th>Valor</th>
                            <th>Data</th>
                            <th>Descrição</th>
                            <th>Categoria</th>
                            <th>Ref.</th>
                            <th class="text-center">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        while($row = $result_transacoes->fetch_assoc()) {
                            $tipo_class = ($row['tipo'] == 'entrada') ? 'text-success' : 'text-danger';
                            $tipo_icon = ($row['tipo'] == 'entrada') ? 'fa-arrow-up' : 'fa-arrow-down';
                            $referencia_link = 'N/A';

                            if (!empty($row['referencia_id']) && !empty($row['tabela_referencia'])) {
                                $tabela_ref = htmlspecialchars($row['tabela_referencia']);
                                $ref_id = htmlspecialchars($row['referencia_id']);
                                if ($tabela_ref == 'vendas') {
                                    $referencia_link = '<a href="detalhes_venda.php?id=' . $ref_id . '" class="text-decoration-none" title="Ver Venda"><i class="fas fa-receipt"></i> Venda #' . $ref_id . '</a>';
                                } elseif ($tabela_ref == 'orcamentos') {
                                    $referencia_link = '<a href="detalhes_orcamento.php?id=' . $ref_id . '" class="text-decoration-none" title="Ver Orçamento"><i class="fas fa-file-alt"></i> Orçam. #' . $ref_id . '</a>';
                                }
                            }
                            ?>
                            <tr>
                                <td><span class="text-muted">#<?php echo htmlspecialchars($row['id']); ?></span></td>
                                <td>
                                    <span class="badge bg-<?php echo ($row['tipo'] == 'entrada') ? 'success-light' : 'danger-light'; ?> text-<?php echo ($row['tipo'] == 'entrada') ? 'success' : 'danger'; ?>">
                                        <i class="fas <?php echo $tipo_icon; ?> me-1"></i><?php echo htmlspecialchars(ucfirst($row['tipo'])); ?>
                                    </span>
                                </td>
                                <td class="<?php echo $tipo_class; ?> fw-bold">
                                    R$ <?php echo number_format($row['valor'], 2, ',', '.'); ?>
                                </td>
                                <td><?php echo date('d/m/Y H:i', strtotime($row['data_transacao'])); ?></td>
                                <td><?php echo htmlspecialchars($row['descricao']); ?></td>
                                <td>
                                    <?php if (!empty($row['categoria'])): ?>
                                        <span class="badge bg-light text-dark"><?php echo htmlspecialchars($row['categoria']); ?></span>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo $referencia_link; ?></td>
                                <td class="text-center">
                                    <div class="btn-group" role="group">
                                        <a href="registrar_transacao.php?id=<?php echo $row['id']; ?>" class="btn btn-outline-primary btn-sm" title="Editar">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="financeiro.php?action=delete&id=<?php echo $row['id']; ?>" class="btn btn-outline-danger btn-sm" title="Excluir" onclick="return confirm('Tem certeza que deseja excluir esta transação? Isso pode afetar o balanço financeiro.');">
                                            <i class="fas fa-trash-alt"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php
                        }
                        ?>
                    </tbody>
                </table>
            </div>
            <?php if ($total_paginas_transacoes > 1): ?>
                <?php 
                echo render_pagination([
                    'current_page' => $pagina_atual,
                    'total_pages' => $total_paginas_transacoes, 
                    'page_param' => 'page',
                    'base_params' => array_filter($_GET, function($key) { return $key !== 'page'; }, ARRAY_FILTER_USE_KEY),
                    'summary' => $total_transacoes_registros . ' transações registradas',
                    'aria_label' => 'Navegação de transações financeiras'
                ]);
                ?>
            <?php endif; ?>
        <?php else: ?>
            <div class="text-center py-5">
                <div class="stats-icon info mx-auto mb-3">
                    <i class="fas fa-exchange-alt"></i>
                </div>
                <h5 class="text-muted mb-2">Nenhuma transação manual registrada</h5>
                <p class="text-muted">Registre despesas ou outras entradas para vê-las aqui.</p>
                <a href="registrar_transacao.php" class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i> Registrar Primeira Transação
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
$stmt->close();
$conn->close();
include_once 'includes/footer.php';
?>
