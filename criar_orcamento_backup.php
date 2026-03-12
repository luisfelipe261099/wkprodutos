<?php
session_start();

// Verifica se o usuário está logado
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: index.php");
    exit;
}

// Ativar exibição de erros para debug
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'includes/db_connect.php';

// Verificar se as colunas de pagamento existem
$colunas_pagamento_existem = true;
$result_check = $conn->query("SHOW COLUMNS FROM orcamentos LIKE 'forma_pagamento'");
if (!$result_check || $result_check->num_rows == 0) {
    $colunas_pagamento_existem = false;
}

$orcamento_id = $cliente_id = $data_orcamento = $valor_total = $status_orcamento = $observacoes = "";
$forma_pagamento = $tipo_faturamento = $data_vencimento = "";
$title = "Criar Novo Orçamento";
$submit_button_text = "Criar Orçamento";
$message = '';
$message_type = '';
$itens_do_orcamento = []; // Array para armazenar os produtos do orçamento (para edição)

// Buscar todos os clientes para o campo SELECT
$clientes_options = $conn->query("SELECT id, nome FROM clientes ORDER BY nome ASC");

// Buscar todas as empresas para o filtro
$empresas_options = $conn->query("SELECT id, nome_empresa FROM empresas_representadas ORDER BY nome_empresa ASC");

// Buscar todos os produtos para o campo SELECT na adição de itens (incluindo empresa e SKU)
$produtos_options = $conn->query("SELECT p.id, p.nome, p.sku, p.preco_venda, p.empresa_id, e.nome_empresa
                                   FROM produtos p
                                   LEFT JOIN empresas_representadas e ON p.empresa_id = e.id
                                   ORDER BY e.nome_empresa ASC, p.nome ASC");

// Processar formulário quando enviado
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $orcamento_id = trim($_POST["orcamento_id"] ?? '');
    $cliente_id = trim($_POST["cliente_id"]);
    $status_orcamento = trim($_POST["status_orcamento"]);
    $observacoes = trim($_POST["observacoes"]);
    $forma_pagamento = trim($_POST["forma_pagamento"] ?? '');
    $tipo_faturamento = trim($_POST["tipo_faturamento"] ?? '');
    $data_vencimento = trim($_POST["data_vencimento"] ?? '');
    $itens_selecionados_json = $_POST["itens_selecionados_json"] ?? '[]'; // Itens do orçamento em JSON

    // Decodifica os itens selecionados
    $itens_do_orcamento_post = json_decode($itens_selecionados_json, true);

    // Recalcula o valor total com base nos itens enviados (segurança)
    $calculated_valor_total = 0;
    foreach ($itens_do_orcamento_post as $item) {
        $calculated_valor_total += ($item['preco_unitario'] * $item['quantidade']);
    }
    $valor_total = $calculated_valor_total; // Usamos o valor recalculado

    // Validação básica
    if (empty($cliente_id) || empty($status_orcamento) || empty($itens_do_orcamento_post)) {
        $message = "Por favor, preencha todos os campos obrigatórios e adicione pelo menos um produto ao orçamento.";
        $message_type = "danger";
    } else {
        $conn->begin_transaction(); // Inicia transação

        try {
            if (empty($orcamento_id)) { // Novo Orçamento
                if ($colunas_pagamento_existem) {
                    $sql = "INSERT INTO orcamentos (cliente_id, valor_total, status_orcamento, observacoes, forma_pagamento, tipo_faturamento, data_vencimento, data_orcamento) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
                    if ($stmt = $conn->prepare($sql)) {
                        $data_vencimento_param = !empty($data_vencimento) ? $data_vencimento : null;
                        $stmt->bind_param("idsssss", $cliente_id, $valor_total, $status_orcamento, $observacoes, $forma_pagamento, $tipo_faturamento, $data_vencimento_param);
                        if (!$stmt->execute()) {
                            throw new Exception("Erro ao registrar orçamento: " . $stmt->error);
                        }
                        $orcamento_id = $conn->insert_id;
                        $stmt->close();
                    } else {
                        throw new Exception("Erro na preparação da query de inserção do orçamento: " . $conn->error);
                    }
                } else {
                    // Versão sem colunas de pagamento
                    $sql = "INSERT INTO orcamentos (cliente_id, valor_total, status_orcamento, observacoes, data_orcamento) VALUES (?, ?, ?, ?, NOW())";
                    if ($stmt = $conn->prepare($sql)) {
                        $stmt->bind_param("idss", $cliente_id, $valor_total, $status_orcamento, $observacoes);
                        if (!$stmt->execute()) {
                            throw new Exception("Erro ao registrar orçamento: " . $stmt->error);
                        }
                        $orcamento_id = $conn->insert_id;
                        $stmt->close();
                    } else {
                        throw new Exception("Erro na preparação da query de inserção do orçamento: " . $conn->error);
                    }
                }
            } else { // Editar Orçamento Existente
                // Exclui os itens antigos do orçamento para inserir os novos
                $sql_delete_old_items = "DELETE FROM itens_orcamento WHERE orcamento_id = ?";
                $stmt_delete_old_items = $conn->prepare($sql_delete_old_items);
                $stmt_delete_old_items->bind_param("i", $orcamento_id);
                if (!$stmt_delete_old_items->execute()) {
                    throw new Exception("Erro ao deletar itens antigos do orçamento: " . $stmt_delete_old_items->error);
                }
                $stmt_delete_old_items->close();

                // Atualiza os dados do orçamento
                if ($colunas_pagamento_existem) {
                    $sql = "UPDATE orcamentos SET cliente_id = ?, valor_total = ?, status_orcamento = ?, observacoes = ?, forma_pagamento = ?, tipo_faturamento = ?, data_vencimento = ? WHERE id = ?";
                    if ($stmt = $conn->prepare($sql)) {
                        $data_vencimento_param = !empty($data_vencimento) ? $data_vencimento : null;
                        $stmt->bind_param("idsssssi", $cliente_id, $valor_total, $status_orcamento, $observacoes, $forma_pagamento, $tipo_faturamento, $data_vencimento_param, $orcamento_id);
                        if (!$stmt->execute()) {
                            throw new Exception("Erro ao atualizar orçamento: " . $stmt->error);
                        }
                        $stmt->close();
                    } else {
                        throw new Exception("Erro na preparação da query de atualização do orçamento: " . $conn->error);
                    }
                } else {
                    // Versão sem colunas de pagamento
                    $sql = "UPDATE orcamentos SET cliente_id = ?, valor_total = ?, status_orcamento = ?, observacoes = ? WHERE id = ?";
                    if ($stmt = $conn->prepare($sql)) {
                        $stmt->bind_param("idssi", $cliente_id, $valor_total, $status_orcamento, $observacoes, $orcamento_id);
                        if (!$stmt->execute()) {
                            throw new Exception("Erro ao atualizar orçamento: " . $stmt->error);
                        }
                        $stmt->close();
                    } else {
                        throw new Exception("Erro na preparação da query de atualização do orçamento: " . $conn->error);
                    }
                }
            }

            // Inserir/Atualizar Itens do Orçamento
            foreach ($itens_do_orcamento_post as $item) {
                $produto_id = $item['id'];
                $quantidade = $item['quantidade'];
                $preco_unitario = $item['preco_unitario'];

                $sql_item = "INSERT INTO itens_orcamento (orcamento_id, produto_id, quantidade, preco_unitario) VALUES (?, ?, ?, ?)";
                if ($stmt_item = $conn->prepare($sql_item)) {
                    $stmt_item->bind_param("iiid", $orcamento_id, $produto_id, $quantidade, $preco_unitario);
                    if (!$stmt_item->execute()) {
                        throw new Exception("Erro ao inserir item do orçamento: " . $stmt_item->error);
                    }
                    $stmt_item->close();
                } else {
                    throw new Exception("Erro na preparação da query de item do orçamento: " . $conn->error);
                }
            }

            $conn->commit(); // Confirma todas as operações
            $message = (empty($_POST["orcamento_id"]) ? "Orçamento criado com sucesso!" : "Orçamento atualizado com sucesso!");
            $message_type = "success";
            header("location: orcamentos.php?message=" . urlencode($message) . "&type=" . $message_type);
            exit;

        } catch (Exception $e) {
            $conn->rollback(); // Reverte todas as operações em caso de erro
            $message = "Erro na transação: " . $e->getMessage();
            $message_type = "danger";
        }
    }
}

// Preencher formulário para edição se um ID for passado via GET
$orcamento_id_get = $_GET["id"] ?? '';
if (!empty($orcamento_id_get) && empty($message)) {
    $orcamento_id = $orcamento_id_get;
    $title = "Editar Orçamento";
    $submit_button_text = "Atualizar Orçamento";

    // Busca os dados do orçamento principal
    if ($colunas_pagamento_existem) {
        $sql_orcamento = "SELECT id, cliente_id, valor_total, status_orcamento, observacoes, forma_pagamento, tipo_faturamento, data_vencimento FROM orcamentos WHERE id = ?";
    } else {
        $sql_orcamento = "SELECT id, cliente_id, valor_total, status_orcamento, observacoes FROM orcamentos WHERE id = ?";
    }

    if ($stmt_orcamento = $conn->prepare($sql_orcamento)) {
        $stmt_orcamento->bind_param("i", $orcamento_id);
        $stmt_orcamento->execute();
        $result_orcamento = $stmt_orcamento->get_result();
        if ($result_orcamento->num_rows == 1) {
            $row_orcamento = $result_orcamento->fetch_assoc();
            $cliente_id = $row_orcamento['cliente_id'];
            $valor_total = $row_orcamento['valor_total'];
            $status_orcamento = $row_orcamento['status_orcamento'];
            $observacoes = $row_orcamento['observacoes'];

            if ($colunas_pagamento_existem) {
                $forma_pagamento = $row_orcamento['forma_pagamento'] ?? '';
                $tipo_faturamento = $row_orcamento['tipo_faturamento'] ?? '';
                $data_vencimento = $row_orcamento['data_vencimento'] ?? '';
            }
        } else {
            $message = "Orçamento não encontrado.";
            $message_type = "danger";
            $orcamento_id = "";
            $title = "Criar Novo Orçamento";
            $submit_button_text = "Criar Orçamento";
        }
        $stmt_orcamento->close();
    } else {
        $message = "Erro ao buscar orçamento para edição: " . $conn->error;
        $message_type = "danger";
    }

    // Busca os itens do orçamento
    $sql_itens = "SELECT io.produto_id, p.nome AS produto_nome, io.quantidade, io.preco_unitario
                    FROM itens_orcamento io
                    JOIN produtos p ON io.produto_id = p.id
                    WHERE io.orcamento_id = ?";
    if ($stmt_itens = $conn->prepare($sql_itens)) {
        $stmt_itens->bind_param("i", $orcamento_id);
        $stmt_itens->execute();
        $result_itens = $stmt_itens->get_result();
        while ($item = $result_itens->fetch_assoc()) {
            $itens_do_orcamento[] = [
                'id' => (int)$item['produto_id'],
                'nome' => $item['produto_nome'],
                'quantidade' => (int)$item['quantidade'],
                'preco_unitario' => (float)$item['preco_unitario']
            ];
        }
        $stmt_itens->close();

        // Debug: verificar quantos itens foram carregados
        error_log("Itens carregados para orçamento $orcamento_id: " . count($itens_do_orcamento));
        error_log("Itens JSON: " . json_encode($itens_do_orcamento));
    } else {
        $message = "Erro ao buscar itens do orçamento: " . $conn->error;
        $message_type = "danger";
    }
}

include_once 'includes/header.php';
?>

<style>
/* Estilos para busca de cliente */
#cliente_search {
    border-radius: 0.375rem;
    transition: all 0.3s ease;
}

#cliente_search:focus {
    border-color: var(--primary-color, #007bff);
    box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
}

#cliente_dropdown {
    list-style: none;
    padding: 0;
    margin: 0;
    border-radius: 0 0 0.375rem 0.375rem;
}

.cliente-item {
    transition: background-color 0.2s ease;
}

.cliente-item:hover {
    background-color: #f0f0f0;
}

.cliente-item:active {
    background-color: #e9ecef;
}

/* Estilos específicos para busca de produtos */
#produto_select {
    max-height: 200px;
    overflow-y: auto;
}

#produto_search {
    transition: all 0.3s ease;
}

#produto_search:focus {
    border-color: var(--primary-color);
    box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
}

.input-group .btn {
    border-left: none;
}

.produtos-search-container {
    background: #f8f9fa;
    border-radius: 0.375rem;
    padding: 1rem;
    margin-bottom: 1rem;
}

#produtos_count {
    font-weight: 500;
    color: #6c757d;
}

.btn-outline-secondary:hover {
    background-color: #6c757d;
    border-color: #6c757d;
    color: white;
}

/* Melhorias específicas para iPad Air */
@media (min-width: 768px) and (max-width: 1180px) {
    /* Botões de ação principais mais visíveis */
    .d-flex.gap-2.justify-content-end {
        flex-wrap: wrap;
        gap: 1rem !important;
        justify-content: center !important;
        margin-top: 2rem !important;
    }

    .btn-action {
        min-width: 140px !important;
        min-height: 44px !important;
        padding: 0.75rem 1.5rem !important;
        font-size: 1rem !important;
        font-weight: 500 !important;
        border-radius: 0.5rem !important;
        transition: all 0.3s ease !important;
    }

    .btn-action:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
    }

    /* Botões de adicionar produto */
    .btn-add-item {
        min-height: 44px !important;
        font-weight: 500 !important;
        font-size: 0.95rem !important;
        padding: 0.75rem 1rem !important;
        border-radius: 0.5rem !important;
        transition: all 0.3s ease !important;
    }

    .btn-add-item:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
    }

    /* Botões de editar/remover itens */
    .editar-item-btn,
    .remover-item-btn {
        min-width: 36px !important;
        min-height: 36px !important;
        padding: 0.5rem !important;
        margin: 0.125rem !important;
        border-radius: 0.375rem !important;
        font-size: 0.85rem !important;
        transition: all 0.3s ease !important;
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
    }

    .editar-item-btn:hover,
    .remover-item-btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.15);
    }

    /* Container dos botões de ação */
    .btn-group-actions {
        display: flex !important;
        gap: 0.25rem !important;
        justify-content: center !important;
        align-items: center !important;
        flex-wrap: nowrap !important;
    }

    /* Destaque para linha sendo editada */
    .table-warning {
        background-color: #fff3cd !important;
        border-color: #ffeaa7 !important;
    }

    .table-warning td {
        border-color: #ffeaa7 !important;
    }

    /* Botão cancelar edição */
    #cancelEditBtn {
        font-size: 0.9rem !important;
        padding: 0.5rem 1rem !important;
    }

    /* Tabela de itens mais responsiva */
    .table-responsive {
        overflow-x: auto !important;
        -webkit-overflow-scrolling: touch !important;
        border-radius: 0.5rem;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        position: relative;
    }

    /* Indicador de scroll horizontal */
    .table-responsive::after {
        content: '';
        position: absolute;
        top: 0;
        right: 0;
        width: 20px;
        height: 100%;
        background: linear-gradient(to left, rgba(255,255,255,0.8), transparent);
        pointer-events: none;
        z-index: 5;
    }

    /* Scrollbar personalizada para iPad */
    .table-responsive::-webkit-scrollbar {
        height: 8px;
    }

    .table-responsive::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 4px;
    }

    .table-responsive::-webkit-scrollbar-thumb {
        background: #c1c1c1;
        border-radius: 4px;
    }

    .table-responsive::-webkit-scrollbar-thumb:hover {
        background: #a8a8a8;
    }

    .table {
        min-width: 700px !important; /* Força largura mínima para mostrar scroll */
        margin-bottom: 0 !important;
    }

    .table th,
    .table td {
        padding: 0.75rem 0.5rem !important;
        vertical-align: middle !important;
        white-space: nowrap !important;
        font-size: 0.9rem;
    }

    /* Coluna de ações com largura fixa */
    .table th:last-child,
    .table td:last-child {
        width: 120px !important;
        min-width: 120px !important;
        text-align: center !important;
        position: sticky;
        right: 0;
        background: white;
        border-left: 1px solid #dee2e6;
        z-index: 10;
    }

    /* Cabeçalho sticky também */
    .table thead th:last-child {
        background: #f8f9fa;
        font-weight: 600;
    }

    /* Inputs mais touch-friendly */
    .form-control,
    .form-select {
        min-height: 44px;
        font-size: 1rem;
    }

    /* Melhorar área de busca de produtos */
    .produtos-search-container {
        padding: 1.5rem;
        margin-bottom: 1.5rem;
    }

    /* Botões de filtro */
    #clearFiltersBtn,
    #clearSearchBtn {
        min-height: 44px;
        padding: 0.75rem 1rem;
    }

    /* Select de produtos */
    #produto_select {
        font-size: 0.9rem;
        line-height: 1.4;
    }

    /* Cards de informação */
    .card-body-modern {
        padding: 1.5rem;
    }

    /* Alertas mais visíveis */
    .alert {
        padding: 1rem 1.5rem;
        font-size: 0.95rem;
    }
}



/* Ajustes específicos para orientação paisagem do iPad */
@media (min-width: 1024px) and (max-width: 1180px) and (orientation: landscape) {
    .d-flex.gap-2.justify-content-end {
        justify-content: flex-end !important;
    }

    .container-fluid {
        padding-left: 2rem;
        padding-right: 2rem;
    }
}

/* Estilos para nova interface de busca */
#produto_search_novo {
    border: 2px solid #dee2e6;
    transition: all 0.3s ease;
    font-size: 1rem;
}

#produto_search_novo:focus {
    border-color: #007bff;
    box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
    outline: none;
}

#produto_dropdown {
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    border: 1px solid #dee2e6;
}

.produto-item {
    padding: 1rem;
    border-bottom: 1px solid #f0f0f0;
    cursor: pointer;
    transition: all 0.2s ease;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.produto-item:hover {
    background-color: #f8f9fa;
    padding-left: 1.5rem;
}

.produto-item:active {
    background-color: #e9ecef;
}

.produto-item-nome {
    font-weight: 600;
    color: #212529;
    font-size: 1rem;
}

.produto-item-info {
    font-size: 0.85rem;
    color: #6c757d;
    margin-top: 0.25rem;
}

.produto-item-preco {
    font-weight: bold;
    color: #28a745;
    font-size: 1.1rem;
    white-space: nowrap;
    margin-left: 1rem;
}

#painel_selecao {
    animation: slideDown 0.3s ease;
}

@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.form-control-lg {
    font-size: 1rem;
    min-height: 44px;
}

.btn-lg {
    min-height: 44px;
    font-size: 1rem;
}
</style>

<div class="page-header fade-in-up">
    <h1 class="page-title">
        <i class="fas fa-<?php echo ($orcamento_id ? 'edit' : 'plus-circle'); ?>"></i>
        <?php echo $title; ?>
    </h1>
    <p class="page-subtitle">
        <?php echo $orcamento_id ? 'Atualize os dados do orçamento' : 'Crie um novo orçamento para seus clientes'; ?>
    </p>
</div>

<?php if (!empty($message)): ?>
    <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show fade-in-up" role="alert">
        <i class="fas fa-<?php echo $message_type == 'success' ? 'check-circle' : ($message_type == 'info' ? 'info-circle' : 'exclamation-triangle'); ?> me-2"></i>
        <?php echo $message; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="modern-card fade-in-up">
    <div class="card-header-modern">
        <i class="fas fa-file-invoice-dollar"></i>
        Dados do Orçamento
    </div>
    <div class="card-body-modern">
        <form id="formOrcamento" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <input type="hidden" name="orcamento_id" value="<?php echo htmlspecialchars($orcamento_id); ?>">
            <input type="hidden" name="itens_selecionados_json" id="itens_selecionados_json" value="">

            <?php if (!empty($itens_do_orcamento)): ?>
            <!-- Debug: Itens carregados -->
            <div class="alert alert-info alert-dismissible fade show" role="alert">
                <i class="fas fa-info-circle me-2"></i>
                <strong>Debug:</strong> <?php echo count($itens_do_orcamento); ?> itens carregados do banco de dados.
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php endif; ?>

            <script>
                // Definir itens do orçamento diretamente no JavaScript
                window.itensDoOrcamentoCarregados = <?php echo json_encode($itens_do_orcamento); ?>;
            </script>

            <div class="row g-4">
                <div class="col-12">
                    <h5 class="text-primary mb-3">
                        <i class="fas fa-info-circle me-2"></i>Informações Básicas
                    </h5>
                </div>

                <div class="col-md-6">
                    <label for="cliente_search" class="form-label">Cliente *</label>
                    <div class="position-relative">
                        <input type="text" class="form-control" id="cliente_search" placeholder="Digite o nome do cliente..." autocomplete="off" required>
                        <input type="hidden" id="cliente_id" name="cliente_id" value="<?php echo htmlspecialchars($cliente_id); ?>">
                        <div id="cliente_dropdown" class="position-absolute w-100 bg-white border border-top-0 rounded-bottom shadow-sm" style="display: none; max-height: 300px; overflow-y: auto; z-index: 1000; top: 100%;">
                            <!-- Resultados da busca serão inseridos aqui -->
                        </div>
                    </div>
                    <small id="cliente_selecionado" class="text-muted d-block mt-2" style="display: none;">
                        <i class="fas fa-check-circle text-success me-1"></i><span id="cliente_nome_selecionado"></span>
                    </small>
                </div>

                <div class="col-md-6">
                    <label for="status_orcamento" class="form-label">Status do Orçamento *</label>
                    <select class="form-control" id="status_orcamento" name="status_orcamento" required>
                        <option value="pendente" <?php echo ($status_orcamento == 'pendente' ? 'selected' : ''); ?>>Pendente</option>
                        <option value="aprovado" <?php echo ($status_orcamento == 'aprovado' ? 'selected' : ''); ?>>Aprovado</option>
                        <option value="rejeitado" <?php echo ($status_orcamento == 'rejeitado' ? 'selected' : ''); ?>>Rejeitado</option>
                        <option value="convertido_venda" <?php echo ($status_orcamento == 'convertido_venda' ? 'selected' : ''); ?>>Convertido em Venda</option>
                    </select>
                </div>

                <?php if ($colunas_pagamento_existem): ?>
                <div class="col-12">
                    <h5 class="text-primary mb-3 mt-4">
                        <i class="fas fa-credit-card me-2"></i>Informações de Pagamento
                    </h5>
                </div>

                <div class="col-md-4">
                    <label for="forma_pagamento" class="form-label">Forma de Pagamento *</label>
                    <select class="form-control" id="forma_pagamento" name="forma_pagamento" required>
                        <option value="faturamento" <?php echo ($forma_pagamento == 'faturamento' ? 'selected' : ''); ?>>Faturamento</option>
                        <option value="pix" <?php echo ($forma_pagamento == 'pix' ? 'selected' : ''); ?>>PIX</option>
                        <option value="debito" <?php echo ($forma_pagamento == 'debito' ? 'selected' : ''); ?>>Cartão de Débito</option>
                        <option value="credito" <?php echo ($forma_pagamento == 'credito' ? 'selected' : ''); ?>>Cartão de Crédito</option>
                        <option value="dinheiro" <?php echo ($forma_pagamento == 'dinheiro' ? 'selected' : ''); ?>>Dinheiro</option>
                    </select>
                </div>

                <div class="col-md-4" id="tipo_faturamento_container">
                    <label for="tipo_faturamento" class="form-label">Tipo de Faturamento</label>
                    <select class="form-control" id="tipo_faturamento" name="tipo_faturamento">
                        <option value="avista" <?php echo ($tipo_faturamento == 'avista' ? 'selected' : ''); ?>>À Vista</option>
                        <option value="7_dias" <?php echo ($tipo_faturamento == '7_dias' ? 'selected' : ''); ?>>7 dias</option>
                        <option value="15_dias" <?php echo ($tipo_faturamento == '15_dias' ? 'selected' : ''); ?>>15 dias</option>
                        <option value="20_dias" <?php echo ($tipo_faturamento == '20_dias' ? 'selected' : ''); ?>>20 dias</option>
                        <option value="21_dias" <?php echo ($tipo_faturamento == '21_dias' ? 'selected' : ''); ?>>21 dias</option>
                        <option value="28_dias" <?php echo ($tipo_faturamento == '28_dias' ? 'selected' : ''); ?>>28 dias</option>
                        <option value="28_35_42_dias" <?php echo ($tipo_faturamento == '28_35_42_dias' ? 'selected' : ''); ?>>28, 35, 42 dias</option>
                        <option value="28_35_42_59_dias" <?php echo ($tipo_faturamento == '28_35_42_59_dias' ? 'selected' : ''); ?>>28, 35, 42, 59 dias</option>
                        <option value="28_35_45_dias" <?php echo ($tipo_faturamento == '28_35_45_dias' ? 'selected' : ''); ?>>28, 35, 45 dias</option>
                        <option value="30_dias" <?php echo ($tipo_faturamento == '30_dias' ? 'selected' : ''); ?>>30 dias</option>
                        <option value="15_30_dias" <?php echo ($tipo_faturamento == '15_30_dias' ? 'selected' : ''); ?>>15, 30 dias</option>
                        <option value="20_30_dias" <?php echo ($tipo_faturamento == '20_30_dias' ? 'selected' : ''); ?>>20, 30 dias</option>
                        <option value="20_30_45_dias" <?php echo ($tipo_faturamento == '20_30_45_dias' ? 'selected' : ''); ?>>20, 30, 45 dias</option>
                        <option value="21_30_dias" <?php echo ($tipo_faturamento == '21_30_dias' ? 'selected' : ''); ?>>21, 30 dias</option>
                        <option value="30_45_60_dias" <?php echo ($tipo_faturamento == '30_45_60_dias' ? 'selected' : ''); ?>>30, 45, 60 dias</option>
                        <option value="45_dias" <?php echo ($tipo_faturamento == '45_dias' ? 'selected' : ''); ?>>45 dias</option>
                        <option value="60_dias" <?php echo ($tipo_faturamento == '60_dias' ? 'selected' : ''); ?>>60 dias</option>
                        <option value="90_dias" <?php echo ($tipo_faturamento == '90_dias' ? 'selected' : ''); ?>>90 dias</option>
                    </select>
                </div>

                <div class="col-md-4" id="data_vencimento_container">
                    <label for="data_vencimento" class="form-label">Data de Vencimento</label>
                    <input type="date" class="form-control" id="data_vencimento" name="data_vencimento" value="<?php echo htmlspecialchars($data_vencimento); ?>">
                </div>
                <?php else: ?>
                <div class="col-12">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Funcionalidades de Pagamento:</strong> Para habilitar as opções de forma de pagamento (PIX, débito, crédito, faturamento),
                        execute o arquivo <code>alteracoes_banco_melhorias.sql</code> no seu banco de dados.
                        <br><br>
                        <a href="verificar_estrutura_banco.php" class="btn btn-sm btn-primary">
                            <i class="fas fa-database me-1"></i>Verificar Estrutura do Banco
                        </a>
                    </div>
                </div>
                <?php endif; ?>

                <div class="col-12">
                    <label for="observacoes" class="form-label">Observações</label>
                    <textarea class="form-control" id="observacoes" name="observacoes" rows="3"
                              placeholder="Informações adicionais sobre o orçamento..."><?php echo htmlspecialchars($observacoes); ?></textarea>
                </div>

                <div class="col-12">
                    <h5 class="text-primary mb-3 mt-4">
                        <i class="fas fa-boxes me-2"></i>Itens do Orçamento
                    </h5>
                </div>
            </div>

            <div class="modern-card mt-4">
                <div class="card-header-modern">
                    <i class="fas fa-plus-circle"></i>
                    Adicionar Produtos
                </div>
                <div class="card-body-modern">
                    <!-- Select Hidden com Produtos para Busca - NOVA INTERFACE -->
                    <select id="produto_select_novo" style="display: none;">
                        <?php
                        // Debug: Verificar se a query foi executada
                        if (!$produtos_options) {
                            echo '<!-- ERRO: Query de produtos falhou: ' . htmlspecialchars($conn->error) . ' -->';
                        } elseif ($produtos_options->num_rows == 0) {
                            echo '<!-- AVISO: Nenhum produto encontrado -->';
                        } else {
                            // Renderizar todos os produtos
                            while($produto = $produtos_options->fetch_assoc()) {
                                $id = isset($produto['id']) ? $produto['id'] : '';
                                $nome = isset($produto['nome']) ? $produto['nome'] : '';
                                $sku = isset($produto['sku']) ? $produto['sku'] : '';
                                $preco = isset($produto['preco_venda']) ? $produto['preco_venda'] : '0';
                                $empresa_id = isset($produto['empresa_id']) ? $produto['empresa_id'] : '';

                                echo '<option value="' . htmlspecialchars($id) . '"
                                        data-nome="' . htmlspecialchars($nome) . '"
                                        data-sku="' . htmlspecialchars($sku) . '"
                                        data-preco="' . htmlspecialchars($preco) . '"
                                        data-empresa="' . htmlspecialchars($empresa_id) . '">'
                                        . htmlspecialchars($nome) . ' (SKU: ' . htmlspecialchars($sku) . ')</option>';
                            }
                        }
                        ?>
                    </select>

                    <!-- Nova Interface de Busca em Tempo Real -->
                    <div class="row g-3 mb-4">
                        <div class="col-md-8">
                            <label for="produto_search_novo" class="form-label fw-bold">
                                <i class="fas fa-search me-2"></i>Buscar Produto
                            </label>
                            <div class="position-relative">
                                <input type="text" class="form-control form-control-lg" id="produto_search_novo"
                                       placeholder="Digite o nome, SKU ou empresa do produto..."
                                       autocomplete="off" style="border-radius: 0.5rem; padding: 0.75rem 1rem;">
                                <button type="button" class="btn btn-outline-secondary position-absolute"
                                        id="clearSearchBtn" style="right: 5px; top: 50%; transform: translateY(-50%); border: none;">
                                    <i class="fas fa-times"></i>
                                </button>

                                <!-- Dropdown de Resultados -->
                                <div id="produto_dropdown" class="position-absolute w-100 bg-white border rounded shadow-lg"
                                     style="display: none; max-height: 400px; overflow-y: auto; z-index: 1000; top: 100%; margin-top: 0.5rem;">
                                    <div class="p-3 text-muted text-center">
                                        <small>Digite para buscar produtos...</small>
                                    </div>
                                </div>
                            </div>
                            <small class="text-muted d-block mt-2">
                                <i class="fas fa-info-circle me-1"></i>
                                <span id="produtos_count_novo">0</span> produto(s) encontrado(s)
                            </small>
                        </div>

                        <div class="col-md-4">
                            <label for="empresa_filter_novo" class="form-label fw-bold">
                                <i class="fas fa-building me-2"></i>Filtrar por Empresa
                            </label>
                            <select class="form-control form-control-lg" id="empresa_filter_novo" style="border-radius: 0.5rem; padding: 0.75rem 1rem;">
                                <option value="">Todas as empresas</option>
                                <?php
                                if ($empresas_options && $empresas_options->num_rows > 0) {
                                    $empresas_options->data_seek(0);
                                    while($empresa = $empresas_options->fetch_assoc()) {
                                        echo '<option value="' . htmlspecialchars($empresa['id']) . '">' . htmlspecialchars($empresa['nome_empresa']) . '</option>';
                                    }
                                }
                                ?>
                            </select>
                        </div>
                    </div>

                    <!-- Painel de Seleção Rápida -->
                    <div class="row g-3 align-items-end" id="painel_selecao" style="display: none; background: #f8f9fa; padding: 1.5rem; border-radius: 0.5rem; border-left: 4px solid #007bff;">
                        <div class="col-md-5">
                            <label class="form-label fw-bold">Produto Selecionado</label>
                            <div class="alert alert-info mb-0" style="border-radius: 0.5rem;">
                                <div id="produto_selecionado_nome" class="fw-bold" style="font-size: 1.1rem;"></div>
                                <small id="produto_selecionado_info" class="text-muted d-block mt-2"></small>
                                <div id="produto_selecionado_preco" class="mt-2" style="font-size: 1.2rem; color: #28a745; font-weight: bold;"></div>
                            </div>
                        </div>

                        <div class="col-md-2">
                            <label for="quantidade_item_novo" class="form-label fw-bold">Quantidade</label>
                            <input type="number" class="form-control form-control-lg" id="quantidade_item_novo"
                                   value="1" min="1" style="border-radius: 0.5rem; padding: 0.75rem 1rem; text-align: center; font-size: 1.1rem;">
                        </div>

                        <div class="col-md-2">
                            <label for="preco_unitario_item_novo" class="form-label fw-bold">Preço Unit.</label>
                            <input type="text" class="form-control form-control-lg" id="preco_unitario_item_novo"
                                   value="0,00" placeholder="0,00" style="border-radius: 0.5rem; padding: 0.75rem 1rem; text-align: center; font-size: 1.1rem;">
                        </div>

                        <div class="col-md-3 d-flex gap-2">
                            <button type="button" class="btn btn-success btn-lg flex-grow-1 btn-add-item" id="addItemBtn" style="border-radius: 0.5rem; font-weight: bold;">
                                <i class="fas fa-plus-circle me-2"></i>Adicionar
                            </button>
                            <button type="button" class="btn btn-outline-secondary btn-lg" id="cancelEditBtn" style="display: none; border-radius: 0.5rem;">
                                <i class="fas fa-times me-1"></i>Cancelar
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modern-card mt-4">
                <div class="card-header-modern">
                    <i class="fas fa-list"></i>
                    Itens do Orçamento
                    <div class="ms-auto">
                        <span class="badge bg-primary" id="totalItens">0 itens</span>
                    </div>
                </div>
                <div class="card-body-modern">
                    <div class="alert alert-info d-block d-lg-none mb-3">
                        <i class="fas fa-arrows-alt-h me-2"></i>
                        <small>Deslize horizontalmente para ver todas as colunas e botões de ação</small>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0" id="tabelaItens">
                            <thead>
                                <tr>
                                    <th>Produto</th>
                                    <th>Quantidade</th>
                                    <th>Preço Unit.</th>
                                    <th>Subtotal</th>
                                    <th class="text-center">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                            </tbody>
                            <tfoot>
                                <tr class="table-primary">
                                    <th colspan="3" class="text-end">Total do Orçamento:</th>
                                    <th id="valor_total_display">R$ 0,00</th>
                                    <th></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>

            <div class="d-flex gap-2 justify-content-end mt-4 flex-wrap">
                <a href="orcamentos.php" class="btn btn-outline-secondary btn-action">
                    <i class="fas fa-times me-2"></i>Cancelar
                </a>
                <button type="submit" class="btn btn-primary btn-action">
                    <i class="fas fa-<?php echo ($orcamento_id ? 'save' : 'check'); ?> me-2"></i><?php echo $submit_button_text; ?>
                </button>
            </div>
        </form>
    </div>
</div>

<?php
$conn->close();
include_once 'includes/footer.php';
?>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // ========== NOVA INTERFACE DE BUSCA DE PRODUTOS ==========
        const produtoSearchNovo = document.getElementById('produto_search_novo');
        const produtoDropdown = document.getElementById('produto_dropdown');
        const empresaFilterNovo = document.getElementById('empresa_filter_novo');
        const painelSelecao = document.getElementById('painel_selecao');
        const quantidadeItemNovo = document.getElementById('quantidade_item_novo');
        const precoUnitarioItemNovo = document.getElementById('preco_unitario_item_novo');
        const produtosSelecionadosNovo = {};
        let todosProdutosNovo = [];
        let debounceTimer;

        // Carregar todos os produtos
        const produtoSelectElement = document.getElementById('produto_select_novo');

        if (!produtoSelectElement) {
            console.error('❌ ERRO: Elemento produto_select_novo não encontrado!');
        } else {
            console.log('✅ Elemento produto_select_novo encontrado');
            console.log('Total de options:', produtoSelectElement.options.length);

            Array.from(produtoSelectElement.options).forEach((option, index) => {
                if (option.value) {
                    const produto = {
                        id: option.value,
                        nome: option.dataset.nome || option.textContent,
                        sku: option.dataset.sku || '',
                        preco: parseFloat(option.dataset.preco),
                        empresa_id: option.dataset.empresa || '',
                        texto_completo: option.textContent
                    };
                    todosProdutosNovo.push(produto);

                    if (index < 3) {
                        console.log('Produto ' + (index + 1) + ':', produto);
                    }
                }
            });

            console.log('✅ Total de produtos carregados:', todosProdutosNovo.length);
        }

        function formatarMoedaNovo(valor) {
            return parseFloat(valor).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
        }

        function buscarProdutos() {
            const termo = produtoSearchNovo.value.toLowerCase().trim();
            const empresaSelecionada = empresaFilterNovo.value;

            if (termo.length === 0) {
                produtoDropdown.style.display = 'none';
                return;
            }

            let produtosFiltrados = todosProdutosNovo.filter(produto => {
                const matchEmpresa = !empresaSelecionada || produto.empresa_id === empresaSelecionada;
                const matchBusca = produto.nome.toLowerCase().includes(termo) ||
                                   produto.sku.toLowerCase().includes(termo) ||
                                   produto.texto_completo.toLowerCase().includes(termo);
                return matchEmpresa && matchBusca;
            });

            // Limitar a 10 resultados
            produtosFiltrados = produtosFiltrados.slice(0, 10);

            if (produtosFiltrados.length === 0) {
                produtoDropdown.innerHTML = '<div class="p-3 text-muted text-center"><i class="fas fa-search me-2"></i>Nenhum produto encontrado</div>';
                produtoDropdown.style.display = 'block';
                document.getElementById('produtos_count_novo').textContent = '0';
                return;
            }

            produtoDropdown.innerHTML = produtosFiltrados.map(produto => `
                <div class="produto-item" data-id="${produto.id}" data-nome="${produto.nome}"
                     data-sku="${produto.sku}" data-preco="${produto.preco}" data-empresa="${produto.empresa_id}">
                    <div>
                        <div class="produto-item-nome">${produto.nome}</div>
                        <div class="produto-item-info">
                            ${produto.sku ? `<strong>SKU:</strong> ${produto.sku}` : ''}
                        </div>
                    </div>
                    <div class="produto-item-preco">${formatarMoedaNovo(produto.preco)}</div>
                </div>
            `).join('');

            produtoDropdown.style.display = 'block';
            document.getElementById('produtos_count_novo').textContent = produtosFiltrados.length;

            // Event listeners para itens
            document.querySelectorAll('.produto-item').forEach(item => {
                item.addEventListener('click', function() {
                    selecionarProduto(this);
                });
            });
        }

        function selecionarProduto(element) {
            const id = element.dataset.id;
            const nome = element.dataset.nome;
            const sku = element.dataset.sku;
            const preco = parseFloat(element.dataset.preco);

            // Armazenar produto selecionado
            produtosSelecionadosNovo.id = id;
            produtosSelecionadosNovo.nome = nome;
            produtosSelecionadosNovo.sku = sku;
            produtosSelecionadosNovo.preco = preco;

            // Atualizar painel de seleção
            document.getElementById('produto_selecionado_nome').textContent = nome;
            document.getElementById('produto_selecionado_preco').textContent = formatarMoedaNovo(preco);

            let infoText = '';
            if (sku) infoText += `<strong>SKU:</strong> ${sku}`;
            document.getElementById('produto_selecionado_info').innerHTML = infoText;

            // Preencher preço
            precoUnitarioItemNovo.value = preco.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            quantidadeItemNovo.value = 1;

            // Mostrar painel
            painelSelecao.style.display = 'block';
            produtoDropdown.style.display = 'none';
            produtoSearchNovo.value = nome;

            // Focar na quantidade
            quantidadeItemNovo.focus();
            quantidadeItemNovo.select();
        }

        // Event listeners
        produtoSearchNovo.addEventListener('input', function() {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(buscarProdutos, 300);
        });

        produtoSearchNovo.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                produtoDropdown.style.display = 'none';
                painelSelecao.style.display = 'none';
            }
        });

        empresaFilterNovo.addEventListener('change', buscarProdutos);

        document.getElementById('clearSearchBtn').addEventListener('click', function() {
            produtoSearchNovo.value = '';
            produtoDropdown.style.display = 'none';
            painelSelecao.style.display = 'none';
            document.getElementById('produtos_count_novo').textContent = '0';
            produtoSearchNovo.focus();
        });

        // Fechar dropdown ao clicar fora
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.position-relative') && !e.target.closest('.produto-item')) {
                produtoDropdown.style.display = 'none';
            }
        });

        // ========== FIM NOVA INTERFACE ==========

        // Elementos do formulário
        const clienteSearch = document.getElementById('cliente_search');
        const clienteIdInput = document.getElementById('cliente_id');
        const clienteDropdown = document.getElementById('cliente_dropdown');
        const clienteSelecionado = document.getElementById('cliente_selecionado');
        const clienteNomeSelecionado = document.getElementById('cliente_nome_selecionado');

        // Array para armazenar todos os clientes
        let todosClientes = [];

        // Carregar clientes do PHP
        const clientesData = <?php
            $clientes_array = [];
            if ($clientes_options && $clientes_options->num_rows > 0) {
                $clientes_options->data_seek(0);
                while($cliente = $clientes_options->fetch_assoc()) {
                    $clientes_array[] = [
                        'id' => $cliente['id'],
                        'nome' => $cliente['nome']
                    ];
                }
            }
            echo json_encode($clientes_array);
        ?>;
        todosClientes = clientesData;

        // Função para filtrar e exibir clientes
        function filtrarClientes() {
            const termo = clienteSearch.value.toLowerCase().trim();
            const dropdown = clienteDropdown;

            if (termo.length === 0) {
                dropdown.style.display = 'none';
                return;
            }

            const clientesFiltrados = todosClientes.filter(cliente =>
                cliente.nome.toLowerCase().includes(termo)
            );

            if (clientesFiltrados.length === 0) {
                dropdown.innerHTML = '<div class="p-3 text-muted text-center">Nenhum cliente encontrado</div>';
                dropdown.style.display = 'block';
                return;
            }

            dropdown.innerHTML = clientesFiltrados.map(cliente => `
                <div class="p-2 border-bottom cliente-item" style="cursor: pointer; padding: 0.75rem 1rem;" data-id="${cliente.id}" data-nome="${cliente.nome}">
                    <div class="fw-500">${cliente.nome}</div>
                </div>
            `).join('');

            dropdown.style.display = 'block';

            // Adicionar event listeners aos itens
            document.querySelectorAll('.cliente-item').forEach(item => {
                item.addEventListener('click', function() {
                    const id = this.dataset.id;
                    const nome = this.dataset.nome;

                    clienteIdInput.value = id;
                    clienteSearch.value = nome;
                    clienteDropdown.style.display = 'none';

                    // Mostrar confirmação
                    clienteNomeSelecionado.textContent = nome;
                    clienteSelecionado.style.display = 'block';
                });
            });
        }

        // Event listeners para busca de cliente
        if (clienteSearch) {
            clienteSearch.addEventListener('input', filtrarClientes);

            clienteSearch.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    clienteDropdown.style.display = 'none';
                }
            });

            // Fechar dropdown ao clicar fora
            document.addEventListener('click', function(e) {
                if (!e.target.closest('.col-md-6') && clienteDropdown.style.display === 'block') {
                    clienteDropdown.style.display = 'none';
                }
            });
        }

        // Se já tem cliente selecionado (modo edição), mostrar confirmação
        if (clienteIdInput.value) {
            const clienteSelecionadoObj = todosClientes.find(c => c.id == clienteIdInput.value);
            if (clienteSelecionadoObj) {
                clienteSearch.value = clienteSelecionadoObj.nome;
                clienteNomeSelecionado.textContent = clienteSelecionadoObj.nome;
                clienteSelecionado.style.display = 'block';
            }
        }

        const produtoSelect = document.getElementById('produto_select');
        const empresaFilter = document.getElementById('empresa_filter');
        const quantidadeInput = document.getElementById('quantidade_item');
        const precoUnitarioInput = document.getElementById('preco_unitario_item');
        const addItemBtn = document.getElementById('addItemBtn');
        const cancelEditBtn = document.getElementById('cancelEditBtn');
        const tabelaItensBody = document.querySelector('#tabelaItens tbody');
        const valorTotalDisplay = document.getElementById('valor_total_display');
        const itensSelecionadosJsonInput = document.getElementById('itens_selecionados_json');
        const formaPagamentoSelect = document.getElementById('forma_pagamento');
        const tipoFaturamentoContainer = document.getElementById('tipo_faturamento_container');
        const dataVencimentoContainer = document.getElementById('data_vencimento_container');
        const colunasPagamentoExistem = <?php echo $colunas_pagamento_existem ? 'true' : 'false'; ?>;

        // Carregar itens do orçamento da variável global ou do input hidden
        let itensDoOrcamento = [];
        if (window.itensDoOrcamentoCarregados && window.itensDoOrcamentoCarregados.length > 0) {
            itensDoOrcamento = window.itensDoOrcamentoCarregados;
            console.log('Itens carregados da variável global:', itensDoOrcamento);
        } else if (itensSelecionadosJsonInput && itensSelecionadosJsonInput.value) {
            itensDoOrcamento = JSON.parse(itensSelecionadosJsonInput.value || '[]');
            console.log('Itens carregados do input hidden:', itensDoOrcamento);
        } else {
            console.log('Nenhum item carregado - novo orçamento');
        }
        let todosProdutos = []; // Array para armazenar todos os produtos
        let itemEditandoIndex = -1; // Índice do item sendo editado (-1 = não está editando)

        // Carregar todos os produtos no array
        Array.from(produtoSelect.options).forEach(option => {
            if (option.value) {
                todosProdutos.push({
                    value: option.value,
                    text: option.textContent,
                    preco: option.dataset.preco,
                    empresa: option.dataset.empresa || '',
                    nome: option.dataset.nome || option.textContent,
                    sku: option.dataset.sku || ''
                });
            }
        });

        function formatarMoeda(valor) {
            return parseFloat(valor).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
        }

        // Função para controlar visibilidade dos campos de pagamento
        function controlarCamposPagamento() {
            if (!colunasPagamentoExistem || !formaPagamentoSelect) return;
            const formaPagamento = formaPagamentoSelect.value;
            if (formaPagamento === 'faturamento') {
                if (tipoFaturamentoContainer) tipoFaturamentoContainer.style.display = 'block';
                if (dataVencimentoContainer) dataVencimentoContainer.style.display = 'block';
            } else {
                if (tipoFaturamentoContainer) tipoFaturamentoContainer.style.display = 'none';
                if (dataVencimentoContainer) dataVencimentoContainer.style.display = 'none';
            }
        }

        // Função para filtrar produtos por empresa e busca
        function filtrarProdutos() {
            const empresaSelecionada = empresaFilter.value;
            const termoBusca = document.getElementById('produto_search').value.toLowerCase().trim();

            produtoSelect.innerHTML = '<option value="">Selecione um produto...</option>';
            let produtosFiltrados = 0;

            todosProdutos.forEach(produto => {
                const matchEmpresa = !empresaSelecionada || produto.empresa === empresaSelecionada;
                // Busca tanto no nome quanto no SKU
                const matchBusca = !termoBusca ||
                    produto.nome.toLowerCase().includes(termoBusca) ||
                    (produto.sku && produto.sku.toLowerCase().includes(termoBusca));

                if (matchEmpresa && matchBusca) {
                    const option = document.createElement('option');
                    option.value = produto.value;
                    option.textContent = produto.text;
                    option.dataset.preco = produto.preco;
                    option.dataset.empresa = produto.empresa;
                    option.dataset.nome = produto.nome;
                    option.dataset.sku = produto.sku;
                    produtoSelect.appendChild(option);
                    produtosFiltrados++;
                }
            });

            // Atualizar contador
            const contadorElement = document.getElementById('produtos_count');
            if (contadorElement) {
                contadorElement.textContent = produtosFiltrados;
            }

            // Se houver apenas um produto filtrado, selecionar automaticamente
            if (produtosFiltrados === 1 && termoBusca) {
                produtoSelect.selectedIndex = 1; // 0 é a opção vazia
                produtoSelect.dispatchEvent(new Event('change'));
            }
        }

        function calcularTotal() {
            let total = 0;
            itensDoOrcamento.forEach(item => {
                total += item.quantidade * item.preco_unitario;
            });
            valorTotalDisplay.textContent = formatarMoeda(total);
            itensSelecionadosJsonInput.value = JSON.stringify(itensDoOrcamento);
        }

        function renderizarItens() {
            tabelaItensBody.innerHTML = '';
            itensDoOrcamento.forEach((item, index) => {
                const subtotal = item.quantidade * item.preco_unitario;
                const row = `
                    <tr>
                        <td>${item.nome}</td>
                        <td>${item.quantidade}</td>
                        <td>${formatarMoeda(item.preco_unitario)}</td>
                        <td>${formatarMoeda(subtotal)}</td>
                        <td class="text-center">
                            <div class="btn-group-actions">
                                <button type="button" class="btn btn-warning btn-sm editar-item-btn" data-index="${index}" title="Editar">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button type="button" class="btn btn-danger btn-sm remover-item-btn" data-index="${index}" title="Remover">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                `;
                tabelaItensBody.insertAdjacentHTML('beforeend', row);
            });

            const totalItensElement = document.getElementById('totalItens');
            if (totalItensElement) {
                totalItensElement.textContent = itensDoOrcamento.length + ' itens';
            }

            calcularTotal();
        }

        produtoSelect.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            if (selectedOption.value) {
                precoUnitarioInput.value = parseFloat(selectedOption.dataset.preco).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                quantidadeInput.value = 1;
            } else {
                precoUnitarioInput.value = '0,00';
            }
        });

        addItemBtn.addEventListener('click', function() {
            // Usar dados da nova interface
            const produtoId = produtosSelecionadosNovo.id;
            const produtoNome = produtosSelecionadosNovo.nome;
            const quantidade = parseInt(quantidadeItemNovo.value);
            const precoUnitario = parseFloat(precoUnitarioItemNovo.value.replace('.', '').replace(',', '.'));

            // Validações detalhadas
            if (!produtoId) {
                alert('Por favor, selecione um produto digitando o nome.');
                produtoSearchNovo.focus();
                return;
            }

            if (isNaN(quantidade) || quantidade <= 0) {
                alert('Por favor, insira uma quantidade válida (maior que 0).');
                quantidadeItemNovo.focus();
                return;
            }

            if (isNaN(precoUnitario) || precoUnitario < 0) {
                alert('Por favor, insira um preço válido.');
                precoUnitarioItemNovo.focus();
                return;
            }

            // Se está editando um item existente
            if (itemEditandoIndex >= 0) {
                // Atualiza o item na posição original
                itensDoOrcamento[itemEditandoIndex] = {
                    id: produtoId,
                    nome: produtoNome,
                    quantidade: quantidade,
                    preco_unitario: precoUnitario
                };
                itemEditandoIndex = -1; // Reset do índice de edição
                addItemBtn.innerHTML = '<i class="fas fa-plus-circle me-2"></i>Adicionar'; // Volta o texto do botão
            } else {
                // Verifica se já existe o produto (para somar quantidades)
                let itemExistente = itensDoOrcamento.find(item => item.id == produtoId);
                if (itemExistente) {
                    itemExistente.quantidade += quantidade;
                    itemExistente.preco_unitario = precoUnitario;
                } else {
                    // Adiciona novo item no final
                    itensDoOrcamento.push({
                        id: produtoId,
                        nome: produtoNome,
                        quantidade: quantidade,
                        preco_unitario: precoUnitario
                    });
                }
            }

            renderizarItens();
            limparFormularioItemNovo();
        });

        // Função para limpar o formulário de item (nova interface)
        function limparFormularioItemNovo() {
            produtoSearchNovo.value = '';
            quantidadeItemNovo.value = '1';
            precoUnitarioItemNovo.value = '0,00';
            painelSelecao.style.display = 'none';
            produtoDropdown.style.display = 'none';
            produtosSelecionadosNovo.id = null;
            itemEditandoIndex = -1;
            addItemBtn.innerHTML = '<i class="fas fa-plus-circle me-2"></i>Adicionar';
            cancelEditBtn.style.display = 'none';
            document.getElementById('produtos_count_novo').textContent = '0';
            produtoSearchNovo.focus();
        }

        // Função para limpar o formulário de item (interface antiga - mantida para compatibilidade)
        function limparFormularioItem() {
            produtoSelect.value = '';
            quantidadeInput.value = '1';
            precoUnitarioInput.value = '0,00';
            itemEditandoIndex = -1;
            addItemBtn.innerHTML = '<i class="fas fa-plus-circle me-1"></i> Adicionar';
            cancelEditBtn.style.display = 'none';
        }

        // Botão cancelar edição
        cancelEditBtn.addEventListener('click', function() {
            limparFormularioItem();
            renderizarItens(); // Remove o destaque da linha
        });

        tabelaItensBody.addEventListener('click', function(event) {
            const removeBtn = event.target.closest('.remover-item-btn');
            const editBtn = event.target.closest('.editar-item-btn');

            if (removeBtn) {
                const indexToRemove = parseInt(removeBtn.dataset.index);
                itensDoOrcamento.splice(indexToRemove, 1);
                renderizarItens();
            }

            if (editBtn) {
                const indexToEdit = parseInt(editBtn.dataset.index);
                const itemParaEditar = itensDoOrcamento[indexToEdit];

                // Armazena o índice do item sendo editado
                itemEditandoIndex = indexToEdit;

                // Preenche os campos do formulário (nova interface)
                produtosSelecionadosNovo.id = itemParaEditar.id;
                produtosSelecionadosNovo.nome = itemParaEditar.nome;
                produtosSelecionadosNovo.preco = itemParaEditar.preco_unitario;

                produtoSearchNovo.value = itemParaEditar.nome;
                quantidadeItemNovo.value = itemParaEditar.quantidade;
                precoUnitarioItemNovo.value = parseFloat(itemParaEditar.preco_unitario).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

                // Atualizar painel de seleção
                document.getElementById('produto_selecionado_nome').textContent = itemParaEditar.nome;
                document.getElementById('produto_selecionado_preco').textContent = formatarMoedaNovo(itemParaEditar.preco_unitario);
                painelSelecao.style.display = 'block';

                // Muda o texto do botão para indicar que está editando
                addItemBtn.innerHTML = '<i class="fas fa-save me-2"></i>Atualizar Item';
                cancelEditBtn.style.display = 'block';

                // Destaca a linha sendo editada
                renderizarItens();
                const rowBeingEdited = document.querySelector(`[data-index="${indexToEdit}"]`).closest('tr');
                if (rowBeingEdited) {
                    rowBeingEdited.classList.add('table-warning');
                }

                produtoSelect.focus();
            }
        });

        document.getElementById('formOrcamento').addEventListener('submit', function(event) {
            // Validar cliente
            if (!clienteIdInput.value || clienteIdInput.value === '') {
                alert('Por favor, selecione um cliente da lista.');
                clienteSearch.focus();
                event.preventDefault();
                return;
            }

            // Validar itens
            if (itensDoOrcamento.length === 0) {
                alert('Por favor, adicione pelo menos um produto ao orçamento.');
                event.preventDefault();
                return;
            }
        });

        if (formaPagamentoSelect) {
            formaPagamentoSelect.addEventListener('change', controlarCamposPagamento);
        }
        if (empresaFilter) {
            empresaFilter.addEventListener('change', filtrarProdutos);
        }

        // Event listeners para busca de produtos
        const produtoSearch = document.getElementById('produto_search');
        const clearSearchBtn = document.getElementById('clearSearchBtn');
        const clearFiltersBtn = document.getElementById('clearFiltersBtn');
        let debounceTimer;

        if (produtoSearch) {
            // Usar debounce para melhor performance
            produtoSearch.addEventListener('input', function() {
                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(() => {
                    filtrarProdutos();
                }, 300);
            });

            produtoSearch.addEventListener('keyup', function(e) {
                if (e.key === 'Escape') {
                    this.value = '';
                    clearTimeout(debounceTimer);
                    filtrarProdutos();
                }
            });
        }

        if (clearSearchBtn) {
            clearSearchBtn.addEventListener('click', function() {
                produtoSearch.value = '';
                filtrarProdutos();
                produtoSearch.focus();
            });
        }

        if (clearFiltersBtn) {
            clearFiltersBtn.addEventListener('click', function() {
                produtoSearch.value = '';
                empresaFilter.value = '';
                filtrarProdutos();
            });
        }

        controlarCamposPagamento();
        filtrarProdutos(); // Inicializar contagem

        // Debug: verificar itens carregados
        console.log('Itens carregados do banco:', itensDoOrcamento);
        console.log('Total de itens:', itensDoOrcamento.length);

        // Renderizar itens carregados do banco de dados (modo edição)
        // Sempre chamar renderizarItens para atualizar a tabela
        renderizarItens();
    });
</script>