<?php
require_once 'includes/session_bootstrap.php';

// Verifica se o usuário está logado
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: index.php");
    exit;
}

// Desabilitar cache
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: 0");

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'includes/db_connect.php';

/**
 * Função auxiliar para gerar o próximo ID da tabela de orçamentos
 * Usada em bancos que não suportam AUTO_INCREMENT
 */
function getProximoOrcamentoId($conexao) {
    $result = $conexao->query("SELECT MAX(id) as max_id FROM orcamentos");
    if ($result && $row = $result->fetch_assoc()) {
        return intval($row['max_id']) + 1;
    }
    return 1;
}

/**
 * Função auxiliar para obter o MAX(id) de uma tabela
 * Usada para gerar sequências de IDs em massa
 */
function obterMaxIdDaTabela($conexao, $tabela) {
    $result = $conexao->query("SELECT MAX(id) as max_id FROM `$tabela`");
    if ($result && $row = $result->fetch_assoc()) {
        return intval($row['max_id']) ?? 0;
    }
    return 0;
}

function obterOpcoesEnumDaColuna($conexao, $tabela, $coluna) {
    $stmt = $conexao->prepare("SHOW COLUMNS FROM `$tabela` LIKE ?");
    if (!$stmt) {
        return [];
    }

    $stmt->bind_param("s", $coluna);
    $stmt->execute();
    $resultado = $stmt->get_result();
    $colunaInfo = $resultado ? $resultado->fetch_assoc() : null;
    $stmt->close();

    if (!$colunaInfo || !isset($colunaInfo['Type'])) {
        return [];
    }

    if (!preg_match("/^enum\((.*)\)$/i", $colunaInfo['Type'], $matches)) {
        return [];
    }

    preg_match_all("/'((?:\\'|[^'])*)'/", $matches[1], $valores);

    return array_map(static function ($valor) {
        return str_replace("\\'", "'", $valor);
    }, $valores[1] ?? []);
}

function normalizarTipoFaturamento($tipoSelecionado, array $tiposPermitidos) {
    if ($tipoSelecionado === '' || in_array($tipoSelecionado, $tiposPermitidos, true)) {
        return $tipoSelecionado;
    }

    $aliases = [
        '15' => '15_dias',
        '20' => '20_dias',
        '30' => '30_dias',
        '15_dias' => '15',
        '20_dias' => '20',
        '30_dias' => '30',
    ];

    if (isset($aliases[$tipoSelecionado]) && in_array($aliases[$tipoSelecionado], $tiposPermitidos, true)) {
        return $aliases[$tipoSelecionado];
    }

    return null;
}

// Verificar se as colunas de pagamento existem
$colunas_pagamento_existem = true;
$result_check = $conn->query("SHOW COLUMNS FROM orcamentos LIKE 'forma_pagamento'");
if (!$result_check || $result_check->num_rows == 0) {
    $colunas_pagamento_existem = false;
}

$labels_tipo_faturamento = [
    'avista' => 'A vista',
    '7' => '7 dias',
    '15' => '15 dias',
    '15_dias' => '15 dias (antigo)',
    '20' => '20 dias',
    '20_dias' => '20 dias (antigo)',
    '21' => '21 dias',
    '28' => '28 dias',
    '30' => '30 dias',
    '30_dias' => '30 dias (antigo)',
    '45_dias' => '45 dias',
    '60_dias' => '60 dias',
    '90_dias' => '90 dias',
    '15_30' => '15/30 dias',
    '20_30' => '20/30 dias',
    '21_30' => '21/30 dias',
    '20_30_45' => '20/30/45 dias',
    '21_28_35_42_49_56' => '21/28/35/42/49/56 dias',
    '28_35' => '28/35 dias',
    '28_35_42' => '28/35/42 dias',
    '28_35_42_59' => '28/35/42/59 dias',
    '28_35_45' => '28/35/45 dias',
    '30_60_90' => '30/60/90 dias',
    '30_45_60' => '30/45/60 dias',
];

$tipos_faturamento_permitidos = $colunas_pagamento_existem
    ? obterOpcoesEnumDaColuna($conn, 'orcamentos', 'tipo_faturamento')
    : [];

if (empty($tipos_faturamento_permitidos)) {
    $tipos_faturamento_permitidos = ['avista'];
}

$orcamento_id = $cliente_id = $data_orcamento = $valor_total = $status_orcamento = $observacoes = "";
$forma_pagamento = $tipo_faturamento = $data_vencimento = "";
$cliente_nome_display = ""; // usado para preencher o input visível do cliente em modo edição
$title = "Criar Novo Orçamento";
$submit_button_text = "Criar Orçamento";
$message = '';
$message_type = '';
$itens_do_orcamento = [];

// Buscar todos os clientes
$clientes_result = $conn->query("SELECT id, nome FROM clientes ORDER BY nome ASC");
$clientes_json = [];
if ($clientes_result && $clientes_result->num_rows > 0) {
    while($cliente = $clientes_result->fetch_assoc()) {
        $clientes_json[] = [
            'id' => $cliente['id'],
            'nome' => $cliente['nome']
        ];
    }
}

// Buscar todas as empresas
$empresas_options = $conn->query("SELECT id, nome_empresa FROM empresas_representadas ORDER BY nome_empresa ASC");

// Buscar todos os produtos com empresa
$produtos_query = "SELECT p.id, p.nome, p.sku, p.preco_venda, p.empresa_id, e.nome_empresa
                   FROM produtos p
                   LEFT JOIN empresas_representadas e ON p.empresa_id = e.id
                   ORDER BY e.nome_empresa ASC, p.nome ASC";
$produtos_result = $conn->query($produtos_query);

// Converter produtos para JSON para JavaScript
$produtos_json = [];
if ($produtos_result && $produtos_result->num_rows > 0) {
    while($produto = $produtos_result->fetch_assoc()) {
        $produtos_json[] = [
            'id' => $produto['id'],
            'nome' => $produto['nome'],
            'sku' => $produto['sku'],
            'preco' => floatval($produto['preco_venda']),
            'empresa_id' => $produto['empresa_id'],
            'empresa_nome' => $produto['nome_empresa']
        ];
    }
}

// Processar formulário quando enviado
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $orcamento_id = trim($_POST["orcamento_id"] ?? '');
    $cliente_id = trim($_POST["cliente_id"]);
    $status_orcamento = trim($_POST["status_orcamento"]);
    $observacoes = trim($_POST["observacoes"]);
    $forma_pagamento = trim($_POST["forma_pagamento"] ?? '');
    $tipo_faturamento = trim($_POST["tipo_faturamento"] ?? '');
    $data_vencimento = trim($_POST["data_vencimento"] ?? '');
    $itens_selecionados_json = $_POST["itens_selecionados_json"] ?? '[]';

    if ($colunas_pagamento_existem) {
        $tipo_faturamento_normalizado = normalizarTipoFaturamento($tipo_faturamento, $tipos_faturamento_permitidos);
        if ($tipo_faturamento_normalizado === null) {
            $opcoes_legiveis = array_map(static function ($valor) use ($labels_tipo_faturamento) {
                return $labels_tipo_faturamento[$valor] ?? $valor;
            }, $tipos_faturamento_permitidos);

            $message = "Tipo de faturamento invalido para a configuracao atual do banco. Opcoes disponiveis: " . implode(', ', $opcoes_legiveis) . ".";
            $message_type = "danger";
        } else {
            $tipo_faturamento = $tipo_faturamento_normalizado;
        }
    }

    $itens_do_orcamento_post = json_decode($itens_selecionados_json, true);

    $calculated_valor_total = 0;
    foreach ($itens_do_orcamento_post as $item) {
        $calculated_valor_total += ($item['preco_unitario'] * $item['quantidade']);
    }
    $valor_total = $calculated_valor_total;

    if (!empty($message)) {
        // Mantem a mensagem de validacao do tipo de faturamento.
    } elseif (empty($cliente_id) || empty($status_orcamento) || empty($itens_do_orcamento_post)) {
        $message = "Por favor, preencha todos os campos obrigatórios e adicione pelo menos um produto ao orçamento.";
        $message_type = "danger";
    } else {
        $conn->begin_transaction();

        try {
            if (empty($orcamento_id)) {
                // CRIAR NOVO ORÇAMENTO
                // ✅ Estratégia: Sempre gerar ID manualmente (funciona com ou sem AUTO_INCREMENT)
                $novo_orcamento_id = getProximoOrcamentoId($conn);
                $data_vencimento_param = !empty($data_vencimento) ? $data_vencimento : null;
                
                if ($colunas_pagamento_existem) {
                    $sql = "INSERT INTO orcamentos (id, cliente_id, valor_total, status_orcamento, observacoes, forma_pagamento, tipo_faturamento, data_vencimento, data_orcamento, data_criacao) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), CURDATE())";
                    if ($stmt = $conn->prepare($sql)) {
                        $stmt->bind_param("iidsssss", $novo_orcamento_id, $cliente_id, $valor_total, $status_orcamento, $observacoes, $forma_pagamento, $tipo_faturamento, $data_vencimento_param);
                        if (!$stmt->execute()) {
                            throw new Exception("Erro ao registrar orçamento: " . $stmt->error);
                        }
                        $stmt->close();
                    }
                } else {
                    $sql = "INSERT INTO orcamentos (id, cliente_id, valor_total, status_orcamento, observacoes, data_orcamento, data_criacao) VALUES (?, ?, ?, ?, ?, NOW(), CURDATE())";
                    if ($stmt = $conn->prepare($sql)) {
                        $stmt->bind_param("iidss", $novo_orcamento_id, $cliente_id, $valor_total, $status_orcamento, $observacoes);
                        if (!$stmt->execute()) {
                            throw new Exception("Erro ao registrar orçamento: " . $stmt->error);
                        }
                        $stmt->close();
                    }
                }
                $orcamento_id = $novo_orcamento_id;
            } else {
                // ATUALIZAR ORÇAMENTO EXISTENTE
                if ($colunas_pagamento_existem) {
                    $sql = "UPDATE orcamentos SET cliente_id = ?, valor_total = ?, status_orcamento = ?, observacoes = ?, forma_pagamento = ?, tipo_faturamento = ?, data_vencimento = ?, data_atualizacao = NOW() WHERE id = ?";
                    if ($stmt = $conn->prepare($sql)) {
                        $data_vencimento_param = !empty($data_vencimento) ? $data_vencimento : null;
                        $stmt->bind_param("idsssssi", $cliente_id, $valor_total, $status_orcamento, $observacoes, $forma_pagamento, $tipo_faturamento, $data_vencimento_param, $orcamento_id);
                        if (!$stmt->execute()) {
                            throw new Exception("Erro ao atualizar orçamento: " . $stmt->error);
                        }
                        $stmt->close();
                    }
                } else {
                    $sql = "UPDATE orcamentos SET cliente_id = ?, valor_total = ?, status_orcamento = ?, observacoes = ?, data_atualizacao = NOW() WHERE id = ?";
                    if ($stmt = $conn->prepare($sql)) {
                        $stmt->bind_param("idssi", $cliente_id, $valor_total, $status_orcamento, $observacoes, $orcamento_id);
                        if (!$stmt->execute()) {
                            throw new Exception("Erro ao atualizar orçamento: " . $stmt->error);
                        }
                        $stmt->close();
                    }
                }

                // Remover itens antigos do orçamento
                $sql_delete = "DELETE FROM itens_orcamento WHERE orcamento_id = ?";
                if ($stmt_delete = $conn->prepare($sql_delete)) {
                    $stmt_delete->bind_param("i", $orcamento_id);
                    if (!$stmt_delete->execute()) {
                        throw new Exception("Erro ao remover itens antigos: " . $stmt_delete->error);
                    }
                    $stmt_delete->close();
                }
            }
            
            // Inserir itens do orçamento (novos ou atualizados)
            // ✅ Estratégia: Gerar IDs dos itens COM ANTECEDÊNCIA, depois inserir
            $max_item_atual = obterMaxIdDaTabela($conn, 'itens_orcamento');
            
            $sql_itens = "INSERT INTO itens_orcamento (id, orcamento_id, produto_id, quantidade, preco_unitario) VALUES (?, ?, ?, ?, ?)";
            if ($stmt_itens = $conn->prepare($sql_itens)) {
                foreach ($itens_do_orcamento_post as $index => $item) {
                    $item_id = $max_item_atual + $index + 1;
                    $stmt_itens->bind_param("iiiid", $item_id, $orcamento_id, $item['id'], $item['quantidade'], $item['preco_unitario']);
                    if (!$stmt_itens->execute()) {
                        throw new Exception("Erro ao inserir item: " . $stmt_itens->error);
                    }
                }
                $stmt_itens->close();
            }

            $conn->commit();
            $message = empty($_POST["orcamento_id"]) ? "Orçamento criado com sucesso!" : "Orçamento atualizado com sucesso!";
            $message_type = "success";
            header("refresh:2;url=orcamentos.php");

        } catch (Exception $e) {
            $conn->rollback();
            $message = $e->getMessage();
            $message_type = "danger";
        }
    }
}

// Carregar orçamento para edição
$orcamento_id_get = $_GET["id"] ?? '';
if (!empty($orcamento_id_get) && empty($message)) {
    $orcamento_id = $orcamento_id_get;
    $title = "Editar Orçamento";
    $submit_button_text = "Atualizar Orçamento";

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

            // Buscar nome do cliente para exibição imediata no input visível (compatível sem mysqlnd)
            if (!empty($cliente_id)) {
                if ($stmt_cli = $conn->prepare("SELECT nome FROM clientes WHERE id = ?")) {
                    $stmt_cli->bind_param("i", $cliente_id);
                    $stmt_cli->execute();
                    $stmt_cli->bind_result($nome_cli);
                    if ($stmt_cli->fetch()) {
                        $cliente_nome_display = $nome_cli ?? '';
                    }
                    $stmt_cli->close();
                }
            }
        }
        $stmt_orcamento->close();

        // Buscar itens do orçamento
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
                    'preco_unitario' => floatval($item['preco_unitario'])
                ];
            }
            $stmt_itens->close();
        }
    }
}

include_once 'includes/header.php';
?>

<style>
.page-header { margin-bottom: 2rem; }
.modern-card { 
    background: var(--app-surface, white); 
    border-radius: var(--app-radius-lg, 0.5rem); 
    border: 1px solid var(--app-border, rgba(0,0,0,0.1));
    box-shadow: var(--app-shadow, 0 1px 3px rgba(0,0,0,0.1)); 
    margin-bottom: 2rem; 
}
.card-header-modern { 
    background: var(--app-surface-muted, #f8f9fa); 
    padding: 1.5rem; 
    border-bottom: 1px solid var(--app-border, #dee2e6); 
    border-radius: var(--app-radius-lg, 0.5rem) var(--app-radius-lg, 0.5rem) 0 0; 
    font-weight: 600;
    color: var(--app-text, #333);
}
.card-body-modern { padding: 1.5rem; }
.form-label { font-weight: 600; margin-bottom: 0.5rem; color: var(--app-text, #333); }
.btn-primary { background: var(--app-primary, #007bff); border: none; }
.btn-primary:hover { background: var(--app-primary-soft, #0056b3); opacity: 0.9; }
.table-hover tbody tr:hover { background-color: var(--app-surface-muted, #f5f5f5); }
.badge { padding: 0.5rem 0.75rem; }
.dropdown-item { padding: 0.75rem 1rem; background: var(--app-surface, white); color: var(--app-text, #333); }
.dropdown-item:hover { background-color: var(--app-surface-muted, #f8f9fa); }
#produto_dropdown { max-height: 300px; overflow-y: auto; background: var(--app-surface, white); border: 1px solid var(--app-border, #ccc); }
.produto-item { padding: 0.75rem 1rem; cursor: pointer; border-bottom: 1px solid var(--app-border, #eee); }
.produto-item:hover { background-color: var(--app-surface-muted, #f8f9fa); }

/* Estilos para tabela com rolagem */
.table-container {
    max-height: 600px;
    overflow-y: auto;
    border: 1px solid #dee2e6;
    border-radius: 0.375rem;
}

.table-container table {
    margin-bottom: 0;
}

.table-container thead {
    position: sticky;
    top: 0;
    background: var(--app-surface-muted, #f8f9fa);
    z-index: 10;
    color: var(--app-text, inherit);
}

.table-container tbody tr:hover {
    background-color: var(--app-surface-muted, rgba(0,0,0,0.05));
}

/* Scrollbar customizada */
.table-container::-webkit-scrollbar {
    width: 8px;
}

.table-container::-webkit-scrollbar-track {
    background: var(--app-surface, #f1f1f1);
    border-radius: 10px;
}

.table-container::-webkit-scrollbar-thumb {
    background: #888;
    border-radius: 10px;
}

.table-container::-webkit-scrollbar-thumb:hover {
    background: #555;
}
</style>

<div class="page-header">
    <h1><i class="fas fa-<?php echo ($orcamento_id ? 'edit' : 'plus-circle'); ?>"></i> <?php echo $title; ?></h1>
</div>

<?php if ($message): ?>
<div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
    <?php echo htmlspecialchars($message); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="modern-card">
    <div class="card-header-modern">
        <i class="fas fa-file-invoice-dollar"></i> Dados do Orçamento
    </div>
    <div class="card-body-modern">
        <form id="formOrcamento" method="post">
            <input type="hidden" name="orcamento_id" value="<?php echo htmlspecialchars($orcamento_id); ?>">
            <input type="hidden" name="itens_selecionados_json" id="itens_selecionados_json" value="">

            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Cliente *</label>
                    <input type="text" class="form-control" id="cliente_search" placeholder="Buscar cliente..." autocomplete="off" value="<?php echo htmlspecialchars($cliente_nome_display); ?>">
                    <input type="hidden" id="cliente_id" name="cliente_id" value="<?php echo htmlspecialchars($cliente_id); ?>">
                    <div id="cliente_dropdown" class="dropdown-menu w-100" style="display: none; position: static; max-height: 200px; overflow-y: auto;"></div>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Status *</label>
                    <select name="status_orcamento" class="form-control" required>
                        <option value="">Selecione...</option>
                        <option value="pendente" <?php echo $status_orcamento === 'pendente' ? 'selected' : ''; ?>>Pendente</option>
                        <option value="aprovado" <?php echo $status_orcamento === 'aprovado' ? 'selected' : ''; ?>>Aprovado</option>
                        <option value="rejeitado" <?php echo $status_orcamento === 'rejeitado' ? 'selected' : ''; ?>>Rejeitado</option>
                    </select>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-12">
                    <label class="form-label">Observações</label>
                    <textarea name="observacoes" class="form-control" rows="3"><?php echo htmlspecialchars($observacoes); ?></textarea>
                </div>
            </div>

            <?php if ($colunas_pagamento_existem): ?>
            <div class="row mb-3">
                <div class="col-md-4">
                    <label class="form-label">Forma de Pagamento</label>
                    <select name="forma_pagamento" id="forma_pagamento" class="form-control">
                        <option value="faturamento" <?php echo $forma_pagamento === 'faturamento' ? 'selected' : ''; ?>>Faturamento (padrão)</option>
                        <option value="pix" <?php echo $forma_pagamento === 'pix' ? 'selected' : ''; ?>>PIX</option>
                        <option value="debito" <?php echo $forma_pagamento === 'debito' ? 'selected' : ''; ?>>Débito (Cartão de Débito)</option>
                        <option value="credito" <?php echo $forma_pagamento === 'credito' ? 'selected' : ''; ?>>Crédito (Cartão de Crédito)</option>
                        <option value="dinheiro" <?php echo $forma_pagamento === 'dinheiro' ? 'selected' : ''; ?>>Dinheiro</option>
                        <option value="boleto" <?php echo $forma_pagamento === 'boleto' ? 'selected' : ''; ?>>Boleto</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Tipo de Faturamento</label>
                    <select name="tipo_faturamento" class="form-control">
                        <?php foreach ($tipos_faturamento_permitidos as $opcao_tipo): ?>
                            <option value="<?php echo htmlspecialchars($opcao_tipo); ?>" <?php echo $tipo_faturamento === $opcao_tipo ? 'selected' : ''; ?>><?php echo htmlspecialchars($labels_tipo_faturamento[$opcao_tipo] ?? $opcao_tipo); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Data de Vencimento <span id="label_boleto" style="display:none; color: #dc3545;">(Obrigatório para Boleto)</span></label>
                    <input type="date" name="data_vencimento" id="data_vencimento" class="form-control" value="<?php echo htmlspecialchars($data_vencimento); ?>">
                </div>
            </div>
            <?php endif; ?>

            <hr>

            <div class="row mb-3">
                <div class="col-md-8">
                    <label class="form-label">Buscar Produto</label>
                    <input type="text" class="form-control" id="produto_search" placeholder="Digite o nome ou SKU..." autocomplete="off">
                    <div id="produto_dropdown" class="dropdown-menu w-100" style="display: none; position: static;"></div>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Filtrar por Empresa</label>
                    <select id="empresa_filter" class="form-control">
                        <option value="">Todas as empresas</option>
                        <?php
                        if ($empresas_options && $empresas_options->num_rows > 0) {
                            while($empresa = $empresas_options->fetch_assoc()) {
                                echo '<option value="' . htmlspecialchars($empresa['id']) . '">' . htmlspecialchars($empresa['nome_empresa']) . '</option>';
                            }
                        }
                        ?>
                    </select>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-4">
                    <label class="form-label">Quantidade</label>
                    <input type="number" class="form-control" id="quantidade_item" value="1" min="1">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Preço Unit.</label>
                    <input type="text" class="form-control" id="preco_unitario_item" readonly>
                </div>
                <div class="col-md-4">
                    <label class="form-label">&nbsp;</label>
                    <button type="button" class="btn btn-primary w-100" id="addItemBtn">Adicionar</button>
                </div>
            </div>

            <hr>

            <h5>Itens do Orçamento <span class="badge bg-primary" id="items_count">0</span></h5>

            <!-- Tabela com Rolagem -->
            <div class="table-container">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 45%;">Produto</th>
                            <th style="width: 11%;">Quantidade</th>
                            <th style="width: 13%;">Preço Unit.</th>
                            <th style="width: 13%;">Subtotal</th>
                            <th style="width: 18%;">Ações</th>
                        </tr>
                    </thead>
                    <tbody id="tabelaItens">
                    </tbody>
                </table>
            </div>

            <!-- Total fora da tabela com rolagem -->
            <div class="mt-3 p-3 rounded" style="background: var(--app-surface-muted, #f8f9fa); border: 1px solid var(--app-border, #eee);">
                <div class="row">
                    <div class="col-md-9 text-end">
                        <strong style="font-size: 1.1rem; color: var(--app-text, inherit);">Total do Orçamento:</strong>
                    </div>
                    <div class="col-md-3">
                        <strong style="font-size: 1.2rem; color: var(--app-primary, #007bff);" id="valor_total_display">R$ 0,00</strong>
                    </div>
                </div>
            </div>

            <div class="d-flex gap-2 justify-content-end">
                <a href="orcamentos.php" class="btn btn-secondary">Cancelar</a>
                <button type="submit" class="btn btn-primary"><?php echo $submit_button_text; ?></button>
            </div>
        </form>
    </div>
</div>

<!-- Modal para Editar Quantidade -->
<div class="modal fade" id="modalEditarItem" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-edit"></i> Editar Item
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Produto</label>
                    <input type="text" class="form-control" id="modal_produto_nome" readonly>
                </div>
                <div class="mb-3">
                    <label class="form-label">Quantidade Atual</label>
                    <input type="number" class="form-control" id="modal_quantidade_atual" readonly>
                </div>
                <div class="mb-3">
                    <label class="form-label">Nova Quantidade *</label>
                    <input type="number" class="form-control" id="modal_quantidade_nova" min="1" value="1">
                </div>
                <div class="mb-3">
                    <label class="form-label">Preço Unitário *</label>
                    <input type="number" class="form-control" id="modal_preco_unitario" min="0" step="0.01" placeholder="0.00">
                </div>
                <div class="alert alert-info">
                    <strong>Novo Subtotal:</strong> <span id="modal_novo_subtotal">R$ 0,00</span>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="btn_salvar_edicao">Salvar Alterações</button>
            </div>
        </div>
    </div>
</div>

<script>
const produtosData = <?php echo json_encode($produtos_json); ?>;
const itensCarregados = <?php echo json_encode($itens_do_orcamento); ?>;
const clientesData = <?php echo json_encode($clientes_json); ?>;

console.log('🆕 criar_orcamento.js versão: 2025-11-05-b');
console.log('✅ Produtos carregados:', produtosData.length);
console.log('✅ Clientes carregados:', clientesData.length);
console.log('📋 Primeiros 3 clientes:', clientesData.slice(0, 3));
console.log('📋 Primeiros 3 produtos:', produtosData.slice(0, 3));

// Se não houver clientes, mostrar erro
if (clientesData.length === 0) {
    console.error('❌ ERRO: Nenhum cliente foi carregado do servidor!');
    console.error('clientesData:', clientesData);
}

// Se não houver produtos, mostrar erro
if (produtosData.length === 0) {
    console.error('❌ ERRO: Nenhum produto foi carregado do servidor!');
    console.error('produtosData:', produtosData);
}

// Garantir que todos os itens carregados tenham UUID
let itensOrcamento = itensCarregados.length > 0 ? itensCarregados.map((item, idx) => ({
    ...item,
    _uuid: item._uuid || 'item_' + Date.now() + '_' + idx
})) : [];
let produtoSelecionado = null;

// Elementos DOM
const produtoSearch = document.getElementById('produto_search');
const produtoDropdown = document.getElementById('produto_dropdown');
const empresaFilter = document.getElementById('empresa_filter');
const quantidadeInput = document.getElementById('quantidade_item');
const precoInput = document.getElementById('preco_unitario_item');
const addItemBtn = document.getElementById('addItemBtn');
const tabelaItens = document.getElementById('tabelaItens');
const valorTotalDisplay = document.getElementById('valor_total_display');

const itemsCount = document.getElementById('items_count');
const clienteSearch = document.getElementById('cliente_search');
const clienteDropdown = document.getElementById('cliente_dropdown');
const clienteIdInput = document.getElementById('cliente_id');
const formOrcamento = document.getElementById('formOrcamento');
const itensSelecionadosJson = document.getElementById('itens_selecionados_json');

console.log('✅ Elementos DOM carregados');

// Renderizar tabela de itens ao carregar a página (ÚNICA CHAMADA)
renderizarTabela();

// Buscar produtos
function buscarProdutos() {
    const termo = produtoSearch.value.toLowerCase().trim();
    const empresaSelecionada = empresaFilter.value;



    produtoDropdown.innerHTML = '';

    if (termo.length === 0) {
        produtoDropdown.style.display = 'none';
        return;
    }

    let resultados = produtosData.filter(p => {
        const nome = (p && p.nome ? p.nome : '').toLowerCase();
        const sku = (p && p.sku ? String(p.sku) : '').toLowerCase();
        const matchEmpresa = !empresaSelecionada || String(p.empresa_id) === String(empresaSelecionada);
        const matchBusca = nome.includes(termo) || sku.includes(termo);
        return matchEmpresa && matchBusca;
    }).slice(0, 10);

    if (resultados.length === 0) {
        produtoDropdown.innerHTML = '<div class="dropdown-item text-muted">Nenhum produto encontrado</div>';
        produtoDropdown.style.display = 'block';
        return;
    }

    resultados.forEach(produto => {
        const div = document.createElement('div');
        div.className = 'dropdown-item';
        const precoNum = Number(produto.preco) || 0;
        div.innerHTML = `<strong>${produto.nome || ''}</strong><br><small>SKU: ${(produto.sku || '')} | R$ ${precoNum.toFixed(2)}</small>`;
        div.style.cursor = 'pointer';
        div.onclick = () => selecionarProduto(produto);
        produtoDropdown.appendChild(div);
    });

    produtoDropdown.style.display = 'block';
}

function selecionarProduto(produto) {
    produtoSelecionado = produto;
    produtoSearch.value = produto.nome;
    precoInput.value = (Number(produto.preco) || 0).toFixed(2);
    quantidadeInput.value = 1;
    produtoDropdown.style.display = 'none';
    quantidadeInput.focus();
}



function adicionarItem() {
    if (!produtoSelecionado) {
        alert('Selecione um produto');
        return;
    }

    const quantidade = parseInt(quantidadeInput.value) || 1;
    const preco = parseFloat(precoInput.value) || 0;

    const itemExistente = itensOrcamento.find(i => String(i.id) === String(produtoSelecionado.id));
    if (itemExistente) {
        itemExistente.quantidade += quantidade;
    } else {
        itensOrcamento.push({
            id: produtoSelecionado.id,
            nome: produtoSelecionado.nome,
            quantidade: quantidade,
            preco_unitario: preco,
            _uuid: 'item_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9)
        });
    }

    produtoSearch.value = '';
    precoInput.value = '';
    quantidadeInput.value = 1;
    produtoSelecionado = null;
    renderizarTabela();
}

function removerItem(uuid) {
    const index = itensOrcamento.findIndex(i => i._uuid === uuid);
    if (index !== -1) {
        itensOrcamento.splice(index, 1);
        renderizarTabela();
    }
}

function renderizarTabela() {
    tabelaItens.innerHTML = '';
    let total = 0;

    itensOrcamento.forEach((item, idx) => {
        const subtotal = item.quantidade * item.preco_unitario;
        total += subtotal;

        // Usar UUID se disponível, senão usar índice (para compatibilidade com dados antigos)
        const itemId = item._uuid || idx;

        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td>${item.nome}</td>
            <td>${item.quantidade}</td>
            <td>R$ ${item.preco_unitario.toFixed(2)}</td>
            <td>R$ ${subtotal.toFixed(2)}</td>
            <td>
                <button type="button" class="btn btn-sm btn-warning" onclick="abrirModalEditar(${idx})">
                    <i class="fas fa-edit"></i> Editar
                </button>
                <button type="button" class="btn btn-sm btn-danger" onclick="removerItem('${itemId}')">
                    <i class="fas fa-trash"></i> Remover
                </button>
            </td>
        `;
        tabelaItens.appendChild(tr);
    });

    valorTotalDisplay.textContent = 'R$ ' + total.toFixed(2).replace('.', ',');
    itemsCount.textContent = itensOrcamento.length;
}

// Variável para armazenar o ID do item sendo editado
let itemEmEdicaoIndex = null;

// Elementos do modal
let modalEditarItem = null; // será inicializado sob demanda
const modalProdutoNome = document.getElementById('modal_produto_nome');
const modalQuantidadeAtual = document.getElementById('modal_quantidade_atual');
const modalQuantidadeNova = document.getElementById('modal_quantidade_nova');
const modalPrecoUnitario = document.getElementById('modal_preco_unitario');
const modalNovoSubtotal = document.getElementById('modal_novo_subtotal');
const btnSalvarEdicao = document.getElementById('btn_salvar_edicao');


// Função para abrir modal de edição
function abrirModalEditar(itemIndex) {
    const item = itensOrcamento[itemIndex];
    if (!item) return;

    // Se Bootstrap não estiver disponível, usa fallback com prompt
    if (!window.bootstrap || typeof bootstrap.Modal !== 'function') {
        const entrada = prompt('Informe a nova quantidade:', item.quantidade);
        if (entrada === null) return; // cancelado
        const novaQtd = parseInt(entrada, 10);
        if (!Number.isFinite(novaQtd) || novaQtd < 1) {
            alert('Quantidade inválida');
            return;
        }
        item.quantidade = novaQtd;
        renderizarTabela();
        return;
    }

    // Inicializa Bootstrap Modal sob demanda
    if (!modalEditarItem) {
        modalEditarItem = new bootstrap.Modal(document.getElementById('modalEditarItem'));
    }

    itemEmEdicaoIndex = itemIndex;
    modalProdutoNome.value = item.nome;
    modalQuantidadeAtual.value = item.quantidade;
    modalQuantidadeNova.value = item.quantidade;
    modalPrecoUnitario.value = item.preco_unitario.toFixed(2);

    // Atualizar subtotal
    atualizarSubtotalModal();

    // Abrir modal
    modalEditarItem.show();
}


// Prefill do cliente (edição de orçamento) - robusto: usa servidor e só usa dataset como fallback
(function prefillClienteEdicao(){
    const clienteIdInicial = <?php echo json_encode($cliente_id); ?>;
    const clienteNomeServidor = <?php echo json_encode($cliente_nome_display); ?>;
    if (clienteSearch && !clienteSearch.value && clienteNomeServidor) {
        clienteSearch.value = clienteNomeServidor;
        console.log('👤 Cliente preenchido via servidor:', clienteNomeServidor);
    }
    if (clienteIdInput && !clienteIdInput.value && clienteIdInicial) {
        clienteIdInput.value = clienteIdInicial;
    }
    // Fallback: tenta resolver pelo dataset caso ainda esteja vazio
    if (clienteIdInicial && clientesData && clientesData.length && (!clienteSearch.value || !clienteIdInput.value)) {
        const clienteInicial = clientesData.find(c => String(c.id) === String(clienteIdInicial));
        if (clienteInicial) {
            if (!clienteSearch.value) clienteSearch.value = clienteInicial.nome;
            if (!clienteIdInput.value) clienteIdInput.value = clienteInicial.id;
            console.log('👤 Cliente preenchido via dataset:', clienteInicial);
        }
    }
})();

// Função para atualizar subtotal no modal
function atualizarSubtotalModal() {
    const quantidade = parseInt(modalQuantidadeNova.value) || 1;
    const preco = parseFloat(modalPrecoUnitario.value) || 0;
    const subtotal = quantidade * preco;
    modalNovoSubtotal.textContent = 'R$ ' + subtotal.toFixed(2).replace('.', ',');
}

// Event listeners para atualizar subtotal quando quantidade ou preço mudam
document.getElementById('modal_quantidade_nova').addEventListener('input', atualizarSubtotalModal);
document.getElementById('modal_preco_unitario').addEventListener('input', atualizarSubtotalModal);

// Função para salvar edição
function salvarEdicao() {
    if (itemEmEdicaoIndex === null || itemEmEdicaoIndex === undefined) return;

    const novaQuantidade = parseInt(modalQuantidadeNova.value) || 1;
    const novoPreco = parseFloat(modalPrecoUnitario.value) || 0;

    if (novaQuantidade < 1) {
        alert('Quantidade deve ser maior que 0');
        return;
    }

    if (novoPreco < 0) {
        alert('Preço não pode ser negativo');
        return;
    }

    const item = itensOrcamento[itemEmEdicaoIndex];
    if (!item) return;

    // Atualiza quantidade e preço unitário do item
    item.quantidade = novaQuantidade;
    item.preco_unitario = novoPreco;

    renderizarTabela();
    if (modalEditarItem && typeof modalEditarItem.hide === 'function') {
        modalEditarItem.hide();
    }
    itemEmEdicaoIndex = null;
}

// Event listener para botão salvar
btnSalvarEdicao.addEventListener('click', salvarEdicao);

// Event listeners
produtoSearch.addEventListener('input', buscarProdutos);
empresaFilter.addEventListener('change', buscarProdutos);
addItemBtn.addEventListener('click', adicionarItem);

// Controle de exibição do campo de data de vencimento para boleto
const formaPagamentoSelect = document.getElementById('forma_pagamento');
const dataVencimentoInput = document.getElementById('data_vencimento');
const labelBoleto = document.getElementById('label_boleto');

if (formaPagamentoSelect && dataVencimentoInput && labelBoleto) {
    formaPagamentoSelect.addEventListener('change', function() {
        if (this.value === 'boleto') {
            labelBoleto.style.display = 'inline';
            dataVencimentoInput.required = true;
        } else {
            labelBoleto.style.display = 'none';
            dataVencimentoInput.required = false;
        }
    });

    // Verifica no carregamento da página
    if (formaPagamentoSelect.value === 'boleto') {
        labelBoleto.style.display = 'inline';
        dataVencimentoInput.required = true;
    }
}

formOrcamento.addEventListener('submit', function(e) {
    e.preventDefault();
    if (!clienteIdInput.value) {
        alert('Selecione um cliente');
        return;
    }
    if (itensOrcamento.length === 0) {
        alert('Adicione pelo menos um produto');
        return;
    }
    itensSelecionadosJson.value = JSON.stringify(itensOrcamento);
    formOrcamento.submit();
});

// Buscar clientes
clienteSearch.addEventListener('input', function() {
    const termo = this.value.toLowerCase();
    clienteDropdown.innerHTML = '';

    if (termo.length === 0) {
        clienteDropdown.style.display = 'none';
        return;
    }

    console.log('🔍 Buscando por:', termo);
    console.log('📊 Total de clientes disponíveis:', clientesData.length);
    console.log('📋 Clientes:', clientesData);

    const resultados = clientesData.filter(c => c.nome.toLowerCase().includes(termo)).slice(0, 10);
    console.log('✅ Resultados encontrados:', resultados.length);

    if (resultados.length === 0) {
        clienteDropdown.innerHTML = '<div class="dropdown-item text-muted">Nenhum cliente encontrado</div>';
        clienteDropdown.style.display = 'block';
        return;
    }

    resultados.forEach(cliente => {
        const div = document.createElement('div');
        div.className = 'dropdown-item';
        div.textContent = cliente.nome;
        div.onclick = () => {
            clienteSearch.value = cliente.nome;
            clienteIdInput.value = cliente.id;
            clienteDropdown.style.display = 'none';
        };
        clienteDropdown.appendChild(div);
    });

    clienteDropdown.style.display = 'block';
});
</script>

<?php include_once 'includes/footer.php'; ?>


