<?php
//echo "DEBUG: vendas.php - INÍCIO<br>";
//flush();
error_reporting(E_ALL);
ini_set('display_errors', 1);

//echo "DEBUG: vendas.php - Antes de session_start()<br>";
//flush();
require_once __DIR__ . '/includes/session_bootstrap.php';
//echo "DEBUG: vendas.php - Depois de session_start()<br>";
//flush();

// Verifica se o usuário está logado
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    //echo "DEBUG: vendas.php - Usuário não logado, redirecionando...<br>";
    //flush();
    header("location: index.php");
    exit;
}
//echo "DEBUG: vendas.php - Usuário logado<br>";
//flush();

//echo "DEBUG: vendas.php - Antes de db_connect.php<br>";
//flush();
require_once __DIR__ . '/includes/db_connect.php';
//echo "DEBUG: vendas.php - Depois de db_connect.php<br>";
//flush();

$message = '';
$message_type = '';

// Verificar se há mensagem de sucesso de conversão de orçamento
if (isset($_GET['success']) && $_GET['success'] === 'orcamento_convertido') {
    $venda_id = isset($_GET['venda_id']) ? intval($_GET['venda_id']) : 0;
    if ($venda_id > 0) {
        $message = "Orçamento convertido em venda com sucesso! Venda #$venda_id criada.";
        $message_type = 'success';
    } else {
        $message = "Orçamento convertido em venda com sucesso!";
        $message_type = 'success';
    }
}

//echo "DEBUG: vendas.php - Antes do bloco de AÇÕES (POST/GET)<br>";
//flush();

// --- LÓGICA DE AÇÕES (POST E GET) ---

// AÇÃO: MUDAR STATUS DA VENDA (VIA MODAL)
if (isset($_POST['action']) && $_POST['action'] == 'change_status') {
    //echo "DEBUG: vendas.php - Entrou em AÇÃO: MUDAR STATUS DA VENDA<br>";
    //flush();
    $venda_id = isset($_POST['venda_id']) ? intval($_POST['venda_id']) : 0;
    $novo_status = $_POST['novo_status'] ?? '';
    $status_validos = ['pendente', 'concluida', 'cancelada'];

    if ($venda_id <= 0) {
        $message = "Erro ao alterar o status da venda: ID da venda inválido.";
        $message_type = 'danger';
    } elseif (!in_array($novo_status, $status_validos, true)) {
        $message = "Erro ao alterar o status da venda: status inválido.";
        $message_type = 'danger';
    } else {

        $conn->begin_transaction();
        try {
            $sql_venda_atual = "SELECT status_venda, valor_total FROM vendas WHERE id = ?";
            $stmt_venda_atual = $conn->prepare($sql_venda_atual);
            if (!$stmt_venda_atual) {
                throw new Exception("Erro ao preparar consulta da venda: " . $conn->error);
            }
            $stmt_venda_atual->bind_param("i", $venda_id);
            $stmt_venda_atual->execute();
            $venda_atual = $stmt_venda_atual->get_result()->fetch_assoc();
            $stmt_venda_atual->close();

            if (!$venda_atual) {
                throw new Exception("Venda não encontrada para o ID informado.");
            }

            $status_antigo = $venda_atual['status_venda'];
            $valor_total_venda = (float) $venda_atual['valor_total'];

            if ($status_antigo != $novo_status) {
                $sql_itens = "SELECT produto_id, quantidade FROM itens_venda WHERE venda_id = ?";
                $stmt_itens = $conn->prepare($sql_itens);
                $stmt_itens->bind_param("i", $venda_id);
                $stmt_itens->execute();
                $result_itens = $stmt_itens->get_result();
                $itens_da_venda = $result_itens->fetch_all(MYSQLI_ASSOC);
                $stmt_itens->close();
                
                // Reverte ou debita estoque
                if ($status_antigo == 'cancelada' && $novo_status != 'cancelada') { // Saindo do status cancelado
                    foreach ($itens_da_venda as $item) {
                        $conn->query("UPDATE produtos SET quantidade_estoque = quantidade_estoque - {$item['quantidade']} WHERE id = {$item['produto_id']}");
                    }
                } elseif ($status_antigo != 'cancelada' && $novo_status == 'cancelada') { // Entrando no status cancelado
                     foreach ($itens_da_venda as $item) {
                        $conn->query("UPDATE produtos SET quantidade_estoque = quantidade_estoque + {$item['quantidade']} WHERE id = {$item['produto_id']}");
                    }
                }

                // Atualiza transação financeira
                if ($novo_status == 'concluida') {
                    $stmt_check_transacao = $conn->prepare("SELECT id FROM transacoes_financeiras WHERE referencia_id = ? AND tabela_referencia = 'vendas'");
                    $stmt_check_transacao->bind_param("i", $venda_id);
                    $stmt_check_transacao->execute();
                    $check_transacao = $stmt_check_transacao->get_result();

                    if ($check_transacao->num_rows == 0) {
                        $stmt_check_transacao->close();
                        $next_transacao_id_row = $conn->query("SELECT IFNULL(MAX(id), 0) + 1 AS next_id FROM transacoes_financeiras")->fetch_assoc();
                        $transacao_id = (int)$next_transacao_id_row['next_id'];
                        $descricao_transacao = "Receita da Venda #" . $venda_id;

                        $stmt_insert_transacao = $conn->prepare("INSERT INTO transacoes_financeiras (id, tipo, valor, descricao, categoria, referencia_id, tabela_referencia, data_transacao) VALUES (?, 'entrada', ?, ?, 'Vendas', ?, 'vendas', NOW())");
                        if (!$stmt_insert_transacao) {
                            throw new Exception("Erro ao preparar inserção da transação: " . $conn->error);
                        }
                        $stmt_insert_transacao->bind_param("idsi", $transacao_id, $valor_total_venda, $descricao_transacao, $venda_id);
                        if (!$stmt_insert_transacao->execute()) {
                            throw new Exception("Erro ao registrar transação financeira: " . $stmt_insert_transacao->error);
                        }
                        $stmt_insert_transacao->close();
                    } else {
                        $stmt_check_transacao->close();
                        $stmt_update_transacao = $conn->prepare("UPDATE transacoes_financeiras SET valor = ? WHERE referencia_id = ? AND tabela_referencia = 'vendas'");
                        if (!$stmt_update_transacao) {
                            throw new Exception("Erro ao preparar atualização da transação: " . $conn->error);
                        }
                        $stmt_update_transacao->bind_param("di", $valor_total_venda, $venda_id);
                        if (!$stmt_update_transacao->execute()) {
                            throw new Exception("Erro ao atualizar transação financeira: " . $stmt_update_transacao->error);
                        }
                        $stmt_update_transacao->close();
                    }
                } else {
                    $stmt_delete_transacao = $conn->prepare("DELETE FROM transacoes_financeiras WHERE referencia_id = ? AND tabela_referencia = 'vendas'");
                    if (!$stmt_delete_transacao) {
                        throw new Exception("Erro ao preparar exclusão da transação: " . $conn->error);
                    }
                    $stmt_delete_transacao->bind_param("i", $venda_id);
                    if (!$stmt_delete_transacao->execute()) {
                        throw new Exception("Erro ao excluir transação financeira: " . $stmt_delete_transacao->error);
                    }
                    $stmt_delete_transacao->close();
                }

                // Atualiza status da venda
                $sql_update = "UPDATE vendas SET status_venda = ? WHERE id = ?";
                $stmt_update = $conn->prepare($sql_update);
                $stmt_update->bind_param("si", $novo_status, $venda_id);
                $stmt_update->execute();
                $stmt_update->close();
            }

            $conn->commit();
            $message = "Status da venda #{$venda_id} alterado com sucesso!";
            $message_type = 'success';

        } catch (Exception $e) {
            $conn->rollback();
            $message = "Erro ao alterar o status da venda: " . $e->getMessage();
            $message_type = 'danger';
        }
    }
    //echo "DEBUG: vendas.php - Saiu de AÇÃO: MUDAR STATUS DA VENDA<br>";
    //flush();
}

// AÇÃO: EXCLUIR VENDA (GET)
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    //echo "DEBUG: vendas.php - Entrou em AÇÃO: EXCLUIR VENDA<br>";
    //flush();
    $venda_id_to_delete = intval($_GET['id']);

    $conn->begin_transaction();
    try {
        // 1. Reverter o estoque dos produtos
        $sql_itens = "SELECT produto_id, quantidade FROM itens_venda WHERE venda_id = ?";
        $stmt_itens = $conn->prepare($sql_itens);
        $stmt_itens->bind_param("i", $venda_id_to_delete);
        $stmt_itens->execute();
        $result_itens = $stmt_itens->get_result();
        while ($item = $result_itens->fetch_assoc()) {
            $conn->query("UPDATE produtos SET quantidade_estoque = quantidade_estoque + {$item['quantidade']} WHERE id = {$item['produto_id']}");
        }
        $stmt_itens->close();

        // 2. Excluir a transação financeira associada (se houver)
        $stmt_trans = $conn->prepare("DELETE FROM transacoes_financeiras WHERE referencia_id = ? AND tabela_referencia = 'vendas'");
        $stmt_trans->bind_param("i", $venda_id_to_delete);
        $stmt_trans->execute();
        $stmt_trans->close();
        
        // 3. Excluir os itens da venda
        $stmt_itens_del = $conn->prepare("DELETE FROM itens_venda WHERE venda_id = ?");
        $stmt_itens_del->bind_param("i", $venda_id_to_delete);
        $stmt_itens_del->execute();
        $stmt_itens_del->close();

        // 4. CORREÇÃO: Excluir agendamentos de entrega associados
        $stmt_agend = $conn->prepare("DELETE FROM agendamentos_entrega WHERE venda_id = ?");
        $stmt_agend->bind_param("i", $venda_id_to_delete);
        $stmt_agend->execute();
        $stmt_agend->close();

        // 5. Excluir a venda principal
        $stmt_venda = $conn->prepare("DELETE FROM vendas WHERE id = ?");
        $stmt_venda->bind_param("i", $venda_id_to_delete);
        $stmt_venda->execute();
        $stmt_venda->close();

        $conn->commit();
        $message = "Venda #{$venda_id_to_delete} e todos os seus dados (itens, agendamentos, etc) foram excluídos. O estoque foi revertido.";
        $message_type = "success";

    } catch (Exception $e) {
        $conn->rollback();
        $message = "Erro ao excluir a venda: " . $e->getMessage();
        $message_type = "danger";
    }
    //echo "DEBUG: vendas.php - Saiu de AÇÃO: EXCLUIR VENDA<br>";
    //flush();
}

//echo "DEBUG: vendas.php - Depois do bloco de AÇÕES, antes da PAGINAÇÃO<br>";
//flush();

// --- Lógica para buscar todas as vendas com PAGINAÇÃO ---
$itens_por_pagina = 10; // Defina quantos itens por página
$pagina_atual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
if ($pagina_atual < 1) $pagina_atual = 1;
$offset = ($pagina_atual - 1) * $itens_por_pagina;
//echo "DEBUG: vendas.php - Paginação calculada: itens_por_pagina=$itens_por_pagina, pagina_atual=$pagina_atual, offset=$offset<br>";
//flush();

// Contar total de vendas para paginação
$sql_total_vendas = "SELECT COUNT(*) as total FROM vendas";
//echo "DEBUG: vendas.php - SQL total vendas: $sql_total_vendas<br>";
//flush();
$result_total_vendas = $conn->query($sql_total_vendas);
if (!$result_total_vendas) {
    //echo "DEBUG: vendas.php - ERRO na query total vendas: " . $conn->error . "<br>";
    //flush();
    // Em um cenário real, logar o erro e talvez mostrar uma mensagem amigável.
    die("Erro ao buscar total de vendas. Por favor, tente novamente mais tarde."); 
}
$total_vendas_db = $result_total_vendas->fetch_assoc()['total'];
$total_paginas = ceil($total_vendas_db / $itens_por_pagina);
//echo "DEBUG: vendas.php - Total vendas: $total_vendas_db, Total páginas: $total_paginas<br>";
//flush();


$sql_select_vendas = "SELECT v.id, c.nome AS nome_cliente, v.data_venda, v.valor_total, v.forma_pagamento, v.status_venda
                      FROM vendas v
                      LEFT JOIN clientes c ON v.cliente_id = c.id
                      ORDER BY v.data_venda DESC
                      LIMIT ? OFFSET ?";
//echo "DEBUG: vendas.php - SQL select vendas (antes de preparar): $sql_select_vendas<br>";
//flush();
$stmt_vendas = $conn->prepare($sql_select_vendas);
if (!$stmt_vendas) {
    //echo "DEBUG: vendas.php - ERRO ao preparar select vendas: " . $conn->error . "<br>";
    //flush();
    // Em um cenário real, logar o erro e talvez mostrar uma mensagem amigável.
    die("Erro ao preparar a consulta de vendas. Por favor, tente novamente mais tarde.");
}
//echo "DEBUG: vendas.php - Select vendas preparado<br>";
//flush();
$stmt_vendas->bind_param("ii", $itens_por_pagina, $offset);
//echo "DEBUG: vendas.php - Parâmetros do select vendas vinculados<br>";
//flush();
if (!$stmt_vendas->execute()) {
    // Em um cenário real, logar o erro.
    die("Erro ao executar a consulta de vendas. Por favor, tente novamente mais tarde.");
}
//echo "DEBUG: vendas.php - Select vendas executado<br>";
//flush();
$result_vendas = $stmt_vendas->get_result();
if (!$result_vendas) {
    //echo "DEBUG: vendas.php - ERRO ao obter resultado do select vendas: " . $stmt_vendas->error . "<br>";
    //flush();
    // Em um cenário real, logar o erro.
    die("Erro ao obter os resultados das vendas. Por favor, tente novamente mais tarde.");
}
//echo "DEBUG: vendas.php - Resultado do select vendas obtido<br>";
//flush();

include_once __DIR__ . '/includes/header.php';
//echo "DEBUG: vendas.php - Depois de incluir header.php<br>";
//flush();
?>

<!-- Page Header -->
<div class="page-header fade-in-up">
    <?php //echo "DEBUG: vendas.php - Dentro do Page Header<br>"; flush(); ?>
    <h1 class="page-title">
        <i class="fas fa-shopping-cart"></i>
        Vendas
    </h1>
    <p class="page-subtitle">
        Gerencie todas as suas vendas e acompanhe os status.
    </p>
</div>

<?php if (!empty($message)): ?>
    <?php //echo "DEBUG: vendas.php - Exibindo mensagem: $message<br>"; flush(); ?>
    <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show fade-in-up" role="alert">
        <i class="fas fa-<?php echo $message_type == 'success' ? 'check-circle' : 'exclamation-triangle'; ?> me-2"></i>
        <?php echo $message; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<?php //echo "DEBUG: vendas.php - Antes do botão Adicionar Venda<br>"; flush(); ?>
<div class="mb-4 text-end fade-in-up">
    <a href="registrar_venda.php" class="btn btn-primary btn-lg">
        <i class="fas fa-plus-circle me-2"></i> Adicionar Nova Venda
    </a>
</div>

<?php //echo "DEBUG: vendas.php - Antes do Modern Card (tabela de vendas)<br>"; flush(); ?>
<div class="modern-card fade-in-up">
    <div class="card-header-modern d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div class="d-flex align-items-center">
            <span><i class="fas fa-list"></i> Lista de Vendas</span>
            <div class="ms-auto">
                <span class="badge bg-primary"><?php echo $total_vendas_db; ?> vendas</span>
            </div>
        </div>
        <div class="d-flex gap-2 flex-grow-1 flex-md-grow-0">
            <div class="input-group" style="max-width: 300px;">
                <input type="text" class="form-control" placeholder="Buscar vendas..." id="searchInput">
                <button class="btn btn-outline-primary" type="button">
                    <i class="fas fa-search"></i>
                </button>
            </div>
        </div>
    </div>
    <div class="card-body-modern">
        <!-- Tabela para Desktop e Tablet -->
        <div class="table-responsive-custom">
            <div class="table-modern">
                <?php //echo "DEBUG: vendas.php - Dentro do card-body, antes da tabela<br>"; flush(); ?>
                <table class="table table-hover mb-0" id="vendasTable">
                    <thead>
                        <tr>
                            <th>#ID</th>
                            <th>Cliente</th>
                            <th>Data</th>
                            <th>Valor Total</th>
                            <th>Forma Pag.</th>
                            <th>Status</th>
                            <th class="text-center">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        //echo "DEBUG: vendas.php - Antes do loop de vendas (result_vendas)<br>"; flush();
                        $vendas_array = [];
                        if ($result_vendas && $result_vendas->num_rows > 0) {
                            while ($venda = $result_vendas->fetch_assoc()) {
                                $vendas_array[] = $venda;
                            }

                            // Exibir tabela
                            foreach ($vendas_array as $venda) {
                                //echo "DEBUG: vendas.php - Dentro do loop, Venda ID: " . $venda['id'] . "<br>"; flush();
                                ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($venda['id']); ?></td>
                                    <td><?php echo htmlspecialchars($venda['nome_cliente'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars(date("d/m/Y H:i", strtotime($venda['data_venda']))); ?></td>
                                    <td>R$ <?php echo htmlspecialchars(number_format($venda['valor_total'], 2, ',', '.')); ?></td>
                                    <td><?php echo htmlspecialchars(ucfirst($venda['forma_pagamento'])); ?></td>
                                    <td>
                                        <span class="badge bg-<?php
                                        switch ($venda['status_venda']) {
                                            case 'pendente': echo 'warning'; break;
                                            case 'concluida': echo 'success'; break;
                                            case 'cancelada': echo 'danger'; break;
                                            default: echo 'secondary';
                                        }
                                        ?>">
                                            <?php echo htmlspecialchars(ucfirst($venda['status_venda'])); ?>
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <a href="detalhes_venda.php?id=<?php echo $venda['id']; ?>" class="btn btn-sm btn-outline-info me-1" title="Detalhes"><i class="fas fa-eye"></i></a>
                                        <button type="button" class="btn btn-sm btn-outline-primary me-1" data-bs-toggle="modal" data-bs-target="#changeStatusModal" data-venda-id="<?php echo $venda['id']; ?>" data-current-status="<?php echo $venda['status_venda']; ?>" title="Mudar Status">
                                            <i class="fas fa-exchange-alt"></i>
                                        </button>
                                        <a href="agendar_entrega.php?venda_id=<?php echo $venda['id']; ?>" class="btn btn-sm btn-outline-secondary me-1" title="Agendar Entrega"><i class="fas fa-truck"></i></a>
                                        <a href="gerar_pdf_venda.php?id=<?php echo $venda['id']; ?>" class="btn btn-sm btn-outline-success me-1" title="Gerar PDF" target="_blank"><i class="fas fa-file-pdf"></i></a>
                                        <a href="vendas.php?action=delete&id=<?php echo $venda['id']; ?>" class="btn btn-sm btn-outline-danger" title="Excluir Venda" onclick="return confirm('Tem certeza que deseja excluir esta venda? Esta ação também reverterá o estoque e excluirá transações financeiras e agendamentos associados.');"><i class="fas fa-trash-alt"></i></a>
                                    </td>
                                </tr>
                                <?php
                                 //echo "DEBUG: vendas.php - Fim da iteração do loop, Venda ID: " . $venda['id'] . "<br>"; flush();
                            }
                        } else {
                            //echo "DEBUG: vendas.php - Nenhuma venda encontrada.<br>"; flush();
                            ?>
                            <tr>
                                <td colspan="7" class="text-center">Nenhuma venda registrada.</td>
                            </tr>
                            <?php
                        }
                        //echo "DEBUG: vendas.php - Depois do loop de vendas<br>"; flush();
                        ?>
                    </tbody>
                </table>
                <?php //echo "DEBUG: vendas.php - Depois da tabela, antes da paginação<br>"; flush(); ?>
            </div>
        </div>

        <!-- Cards para Mobile -->
        <div class="mobile-sales-container">
            <?php
            if (!empty($vendas_array)) {
                foreach ($vendas_array as $venda) {
                    ?>
                    <div class="mobile-sale-item mobile-sale-card">
                        <div class="mobile-sale-header">
                            <div class="mobile-sale-id">Venda #<?php echo htmlspecialchars($venda['id']); ?></div>
                            <span class="badge bg-<?php
                            switch ($venda['status_venda']) {
                                case 'pendente': echo 'warning'; break;
                                case 'concluida': echo 'success'; break;
                                case 'cancelada': echo 'danger'; break;
                                default: echo 'secondary';
                            }
                            ?> mobile-sale-status">
                                <?php echo htmlspecialchars(ucfirst($venda['status_venda'])); ?>
                            </span>
                        </div>

                        <div class="mobile-sale-info">
                            <div class="mobile-sale-info-item">
                                <span class="mobile-sale-info-label">Cliente:</span>
                                <span class="mobile-sale-info-value"><?php echo htmlspecialchars($venda['nome_cliente'] ?? 'N/A'); ?></span>
                            </div>
                            <div class="mobile-sale-info-item">
                                <span class="mobile-sale-info-label">Data:</span>
                                <span class="mobile-sale-info-value"><?php echo htmlspecialchars(date("d/m/Y H:i", strtotime($venda['data_venda']))); ?></span>
                            </div>
                            <div class="mobile-sale-info-item">
                                <span class="mobile-sale-info-label">Valor Total:</span>
                                <span class="mobile-sale-info-value text-success fw-bold">R$ <?php echo htmlspecialchars(number_format($venda['valor_total'], 2, ',', '.')); ?></span>
                            </div>
                            <div class="mobile-sale-info-item">
                                <span class="mobile-sale-info-label">Forma Pag.:</span>
                                <span class="mobile-sale-info-value"><?php echo htmlspecialchars(ucfirst($venda['forma_pagamento'])); ?></span>
                            </div>
                        </div>

                        <div class="mobile-sale-actions">
                            <a href="detalhes_venda.php?id=<?php echo $venda['id']; ?>" class="btn btn-sm btn-outline-info" title="Detalhes">
                                <i class="fas fa-eye me-1"></i> Detalhes
                            </a>
                            <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#changeStatusModal" data-venda-id="<?php echo $venda['id']; ?>" data-current-status="<?php echo $venda['status_venda']; ?>" title="Mudar Status">
                                <i class="fas fa-exchange-alt me-1"></i> Status
                            </button>
                            <a href="agendar_entrega.php?venda_id=<?php echo $venda['id']; ?>" class="btn btn-sm btn-outline-secondary" title="Agendar Entrega">
                                <i class="fas fa-truck me-1"></i> Entrega
                            </a>
                            <a href="gerar_pdf_venda.php?id=<?php echo $venda['id']; ?>" class="btn btn-sm btn-outline-success" title="Gerar PDF" target="_blank">
                                <i class="fas fa-file-pdf me-1"></i> PDF
                            </a>
                            <a href="vendas.php?action=delete&id=<?php echo $venda['id']; ?>" class="btn btn-sm btn-outline-danger" title="Excluir Venda" onclick="return confirm('Tem certeza que deseja excluir esta venda? Esta ação também reverterá o estoque e excluirá transações financeiras e agendamentos associados.');">
                                <i class="fas fa-trash-alt me-1"></i> Excluir
                            </a>
                        </div>
                    </div>
                    <?php
                }
            } else {
                ?>
                <div class="text-center py-5">
                    <div class="stats-icon primary mx-auto mb-3">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                    <h5 class="text-muted mb-2">Nenhuma venda registrada</h5>
                    <p class="text-muted">Suas vendas aparecerão aqui quando forem registradas.</p>
                    <a href="registrar_venda.php" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i> Registrar Primeira Venda
                    </a>
                </div>
                <?php
            }
            ?>
        </div>

        <?php
        echo render_pagination([
            'current_page' => $pagina_atual,
            'total_pages' => $total_paginas,
            'page_param' => 'pagina',
            'aria_label' => 'Paginacao de vendas',
            'summary' => $total_vendas_db . ' vendas registradas',
            'window' => 5
        ]);
        ?>
        <?php //echo "DEBUG: vendas.php - Depois da paginação<br>"; flush(); ?>
    </div>
</div>

<!-- Modal Mudar Status -->
<div class="modal fade" id="changeStatusModal" tabindex="-1" aria-labelledby="changeStatusModalLabel" aria-hidden="true">
    <?php //echo "DEBUG: vendas.php - Dentro do Modal Mudar Status<br>"; flush(); ?>
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <form action="vendas.php" method="POST" id="changeStatusForm">
                <input type="hidden" name="action" value="change_status">
                <input type="hidden" name="venda_id" id="modal_venda_id">
                <div class="modal-header">
                    <h5 class="modal-title" id="changeStatusModalLabel">Mudar Status da Venda</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="novo_status" class="form-label">Novo Status:</label>
                        <select name="novo_status" id="modal_novo_status" class="form-select">
                            <option value="pendente">Pendente</option>
                            <option value="concluida">Concluída</option>
                            <option value="cancelada">Cancelada</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Salvar Alterações</button>
                </div>
            </form>
        </div>
    </div>
</div>
<script src="js/search-utils.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Inicializar busca para vendas
    if (window.SearchUtils && typeof window.SearchUtils.initializeSearch === 'function') {
        window.SearchUtils.initializeSearch({
            inputId: 'searchInput',
            tableId: 'vendasTable',
            mobileCardsSelector: '.mobile-sale-card',
            searchColumns: [0, 1, 2, 4, 5] // ID, Cliente, Data, Forma Pagamento, Status
        });
    }

    // Modal para mudar status
    const changeStatusModal = document.getElementById('changeStatusModal');
    if (changeStatusModal) {
        const modalVendaIdInput = changeStatusModal.querySelector('#modal_venda_id');
        const modalStatusSelect = changeStatusModal.querySelector('#modal_novo_status');

        function preencherModalStatus(triggerElement) {
            if (!triggerElement || !modalVendaIdInput || !modalStatusSelect) {
                return;
            }

            const vendaId = triggerElement.getAttribute('data-venda-id');
            const currentStatus = triggerElement.getAttribute('data-current-status');

            modalVendaIdInput.value = vendaId || '';
            if (currentStatus) {
                modalStatusSelect.value = currentStatus;
            }
        }

        document.addEventListener('click', function (event) {
            const triggerElement = event.target.closest('[data-bs-target="#changeStatusModal"]');
            if (triggerElement) {
                preencherModalStatus(triggerElement);
            }
        });

        changeStatusModal.addEventListener('show.bs.modal', function (event) {
            const triggerElement = event.relatedTarget ? event.relatedTarget.closest('[data-bs-target="#changeStatusModal"]') : null;
            if (!triggerElement) {
                return;
            }

            preencherModalStatus(triggerElement);
        });

        changeStatusModal.addEventListener('hidden.bs.modal', function () {
            if (modalVendaIdInput) {
                modalVendaIdInput.value = '';
            }
        });

        const changeStatusForm = document.getElementById('changeStatusForm');
        if (changeStatusForm) {
            changeStatusForm.addEventListener('submit', function (event) {
                const vendaId = parseInt(modalVendaIdInput.value, 10);
                const submitButton = changeStatusForm.querySelector('button[type="submit"]');

                if (!Number.isInteger(vendaId) || vendaId <= 0) {
                    event.preventDefault();
                    alert('Nao foi possivel identificar a venda para alterar o status. Feche o modal e tente novamente.');
                    return;
                }

                if (submitButton) {
                    submitButton.disabled = true;
                    submitButton.textContent = 'Salvando...';
                }
            });
        }
    }
});
</script>

<?php //echo "DEBUG: vendas.php - Antes de incluir footer.php<br>"; flush(); ?>
<?php include_once __DIR__ . '/includes/footer.php'; ?>
<?php //echo "DEBUG: vendas.php - Depois de incluir footer.php<br>"; flush(); ?>


