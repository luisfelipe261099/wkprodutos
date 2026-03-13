<?php
//echo "DEBUG: vendas.php - INÃCIO<br>";
//flush();
error_reporting(E_ALL);
ini_set('display_errors', 1);

//echo "DEBUG: vendas.php - Antes de session_start()<br>";
//flush();
require_once 'includes/session_bootstrap.php';
//echo "DEBUG: vendas.php - Depois de session_start()<br>";
//flush();

// Verifica se o usuÃ¡rio estÃ¡ logado
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    //echo "DEBUG: vendas.php - UsuÃ¡rio nÃ£o logado, redirecionando...<br>";
    //flush();
    header("location: index.php");
    exit;
}
//echo "DEBUG: vendas.php - UsuÃ¡rio logado<br>";
//flush();

//echo "DEBUG: vendas.php - Antes de db_connect.php<br>";
//flush();
require_once 'includes/db_connect.php';
//echo "DEBUG: vendas.php - Depois de db_connect.php<br>";
//flush();

$message = '';
$message_type = '';

// Verificar se hÃ¡ mensagem de sucesso de conversÃ£o de orÃ§amento
if (isset($_GET['success']) && $_GET['success'] === 'orcamento_convertido') {
    $venda_id = isset($_GET['venda_id']) ? intval($_GET['venda_id']) : 0;
    if ($venda_id > 0) {
        $message = "OrÃ§amento convertido em venda com sucesso! Venda #$venda_id criada.";
        $message_type = 'success';
    } else {
        $message = "OrÃ§amento convertido em venda com sucesso!";
        $message_type = 'success';
    }
}

//echo "DEBUG: vendas.php - Antes do bloco de AÃ‡Ã•ES (POST/GET)<br>";
//flush();

// --- LÃ“GICA DE AÃ‡Ã•ES (POST E GET) ---

// AÃ‡ÃƒO: MUDAR STATUS DA VENDA (VIA MODAL)
if (isset($_POST['action']) && $_POST['action'] == 'change_status') {
    //echo "DEBUG: vendas.php - Entrou em AÃ‡ÃƒO: MUDAR STATUS DA VENDA<br>";
    //flush();
    $venda_id = intval($_POST['venda_id']);
    $novo_status = $_POST['novo_status'];

    $conn->begin_transaction();
    try {
        $sql_venda_atual = "SELECT status_venda FROM vendas WHERE id = ?";
        $stmt_venda_atual = $conn->prepare($sql_venda_atual);
        $stmt_venda_atual->bind_param("i", $venda_id);
        $stmt_venda_atual->execute();
        $venda_atual = $stmt_venda_atual->get_result()->fetch_assoc();
        $status_antigo = $venda_atual['status_venda'];
        $stmt_venda_atual->close();

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

            // Atualiza transaÃ§Ã£o financeira
            if ($novo_status == 'concluida') {
                $venda_data = $conn->query("SELECT valor_total FROM vendas WHERE id = $venda_id")->fetch_assoc();
                $check_transacao = $conn->query("SELECT id FROM transacoes_financeiras WHERE referencia_id = $venda_id AND tabela_referencia = 'vendas'");
                if ($check_transacao->num_rows == 0) {
                     $conn->query("INSERT INTO transacoes_financeiras (tipo, valor, descricao, categoria, referencia_id, tabela_referencia, data_transacao) VALUES ('entrada', {$venda_data['valor_total']}, 'Receita da Venda #{$venda_id}', 'Vendas', {$venda_id}, 'vendas', NOW())");
                } else {
                     $conn->query("UPDATE transacoes_financeiras SET valor = {$venda_data['valor_total']} WHERE referencia_id = $venda_id AND tabela_referencia = 'vendas'");
                }
            } else {
                $conn->query("DELETE FROM transacoes_financeiras WHERE referencia_id = $venda_id AND tabela_referencia = 'vendas'");
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
    //echo "DEBUG: vendas.php - Saiu de AÃ‡ÃƒO: MUDAR STATUS DA VENDA<br>";
    //flush();
}

// AÃ‡ÃƒO: EXCLUIR VENDA (GET)
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    //echo "DEBUG: vendas.php - Entrou em AÃ‡ÃƒO: EXCLUIR VENDA<br>";
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

        // 2. Excluir a transaÃ§Ã£o financeira associada (se houver)
        $stmt_trans = $conn->prepare("DELETE FROM transacoes_financeiras WHERE referencia_id = ? AND tabela_referencia = 'vendas'");
        $stmt_trans->bind_param("i", $venda_id_to_delete);
        $stmt_trans->execute();
        $stmt_trans->close();
        
        // 3. Excluir os itens da venda
        $stmt_itens_del = $conn->prepare("DELETE FROM itens_venda WHERE venda_id = ?");
        $stmt_itens_del->bind_param("i", $venda_id_to_delete);
        $stmt_itens_del->execute();
        $stmt_itens_del->close();

        // 4. CORREÃ‡ÃƒO: Excluir agendamentos de entrega associados
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
        $message = "Venda #{$venda_id_to_delete} e todos os seus dados (itens, agendamentos, etc) foram excluÃ­dos. O estoque foi revertido.";
        $message_type = "success";

    } catch (Exception $e) {
        $conn->rollback();
        $message = "Erro ao excluir a venda: " . $e->getMessage();
        $message_type = "danger";
    }
    //echo "DEBUG: vendas.php - Saiu de AÃ‡ÃƒO: EXCLUIR VENDA<br>";
    //flush();
}

//echo "DEBUG: vendas.php - Depois do bloco de AÃ‡Ã•ES, antes da PAGINAÃ‡ÃƒO<br>";
//flush();

// --- LÃ³gica para buscar todas as vendas com PAGINAÃ‡ÃƒO ---
$itens_por_pagina = 10; // Defina quantos itens por pÃ¡gina
$pagina_atual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
if ($pagina_atual < 1) $pagina_atual = 1;
$offset = ($pagina_atual - 1) * $itens_por_pagina;
//echo "DEBUG: vendas.php - PaginaÃ§Ã£o calculada: itens_por_pagina=$itens_por_pagina, pagina_atual=$pagina_atual, offset=$offset<br>";
//flush();

// Contar total de vendas para paginaÃ§Ã£o
$sql_total_vendas = "SELECT COUNT(*) as total FROM vendas";
//echo "DEBUG: vendas.php - SQL total vendas: $sql_total_vendas<br>";
//flush();
$result_total_vendas = $conn->query($sql_total_vendas);
if (!$result_total_vendas) {
    //echo "DEBUG: vendas.php - ERRO na query total vendas: " . $conn->error . "<br>";
    //flush();
    // Em um cenÃ¡rio real, logar o erro e talvez mostrar uma mensagem amigÃ¡vel.
    die("Erro ao buscar total de vendas. Por favor, tente novamente mais tarde."); 
}
$total_vendas_db = $result_total_vendas->fetch_assoc()['total'];
$total_paginas = ceil($total_vendas_db / $itens_por_pagina);
//echo "DEBUG: vendas.php - Total vendas: $total_vendas_db, Total pÃ¡ginas: $total_paginas<br>";
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
    // Em um cenÃ¡rio real, logar o erro e talvez mostrar uma mensagem amigÃ¡vel.
    die("Erro ao preparar a consulta de vendas. Por favor, tente novamente mais tarde.");
}
//echo "DEBUG: vendas.php - Select vendas preparado<br>";
//flush();
$stmt_vendas->bind_param("ii", $itens_por_pagina, $offset);
//echo "DEBUG: vendas.php - ParÃ¢metros do select vendas vinculados<br>";
//flush();
if (!$stmt_vendas->execute()) {
    // Em um cenÃ¡rio real, logar o erro.
    die("Erro ao executar a consulta de vendas. Por favor, tente novamente mais tarde.");
}
//echo "DEBUG: vendas.php - Select vendas executado<br>";
//flush();
$result_vendas = $stmt_vendas->get_result();
if (!$result_vendas) {
    //echo "DEBUG: vendas.php - ERRO ao obter resultado do select vendas: " . $stmt_vendas->error . "<br>";
    //flush();
    // Em um cenÃ¡rio real, logar o erro.
    die("Erro ao obter os resultados das vendas. Por favor, tente novamente mais tarde.");
}
//echo "DEBUG: vendas.php - Resultado do select vendas obtido<br>";
//flush();

include_once 'includes/header.php';
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

<?php //echo "DEBUG: vendas.php - Antes do botÃ£o Adicionar Venda<br>"; flush(); ?>
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
                            <th class="text-center">AÃ§Ãµes</th>
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
                                        <a href="vendas.php?action=delete&id=<?php echo $venda['id']; ?>" class="btn btn-sm btn-outline-danger" title="Excluir Venda" onclick="return confirm('Tem certeza que deseja excluir esta venda? Esta aÃ§Ã£o tambÃ©m reverterÃ¡ o estoque e excluirÃ¡ transaÃ§Ãµes financeiras e agendamentos associados.');"><i class="fas fa-trash-alt"></i></a>
                                    </td>
                                </tr>
                                <?php
                                 //echo "DEBUG: vendas.php - Fim da iteraÃ§Ã£o do loop, Venda ID: " . $venda['id'] . "<br>"; flush();
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
                <?php //echo "DEBUG: vendas.php - Depois da tabela, antes da paginaÃ§Ã£o<br>"; flush(); ?>
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
                            <a href="vendas.php?action=delete&id=<?php echo $venda['id']; ?>" class="btn btn-sm btn-outline-danger" title="Excluir Venda" onclick="return confirm('Tem certeza que deseja excluir esta venda? Esta aÃ§Ã£o tambÃ©m reverterÃ¡ o estoque e excluirÃ¡ transaÃ§Ãµes financeiras e agendamentos associados.');">
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
                    <p class="text-muted">Suas vendas aparecerÃ£o aqui quando forem registradas.</p>
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
        <?php //echo "DEBUG: vendas.php - Depois da paginaÃ§Ã£o<br>"; flush(); ?>
    </div>
</div>

<!-- Modal Mudar Status -->
<div class="modal fade" id="changeStatusModal" tabindex="-1" aria-labelledby="changeStatusModalLabel" aria-hidden="true">
    <?php //echo "DEBUG: vendas.php - Dentro do Modal Mudar Status<br>"; flush(); ?>
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="vendas.php" method="POST">
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
                            <option value="concluida">ConcluÃ­da</option>
                            <option value="cancelada">Cancelada</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Salvar AlteraÃ§Ãµes</button>
                </div>
            </form>
        </div>
    </div>
</div>
<script src="js/search-utils.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Inicializar busca para vendas
    SearchUtils.initializeSearch({
        inputId: 'searchInput',
        tableId: 'vendasTable',
        mobileCardsSelector: '.mobile-sale-card',
        searchColumns: [0, 1, 2, 4, 5] // ID, Cliente, Data, Forma Pagamento, Status
    });

    // Modal para mudar status
    const changeStatusModal = document.getElementById('changeStatusModal');
    if (changeStatusModal) {
        changeStatusModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            const vendaId = button.getAttribute('data-venda-id');
            const currentStatus = button.getAttribute('data-current-status');

            const modalVendaIdInput = changeStatusModal.querySelector('#modal_venda_id');
            const modalStatusSelect = changeStatusModal.querySelector('#modal_novo_status');

            modalVendaIdInput.value = vendaId;
            if (currentStatus) {
                modalStatusSelect.value = currentStatus;
            }
        });
    }
});
</script>

<?php //echo "DEBUG: vendas.php - Antes de incluir footer.php<br>"; flush(); ?>
<?php include_once 'includes/footer.php'; ?>
<?php //echo "DEBUG: vendas.php - Depois de incluir footer.php<br>"; flush(); ?>


