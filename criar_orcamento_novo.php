<?php
require_once 'includes/session_bootstrap.php';

// Verifica se o usuário está logado
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: index.php");
    exit;
}

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
$itens_do_orcamento = [];

// Buscar todos os clientes
$clientes_options = $conn->query("SELECT id, nome FROM clientes ORDER BY nome ASC");

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

    $itens_do_orcamento_post = json_decode($itens_selecionados_json, true);

    $calculated_valor_total = 0;
    foreach ($itens_do_orcamento_post as $item) {
        $calculated_valor_total += ($item['preco_unitario'] * $item['quantidade']);
    }
    $valor_total = $calculated_valor_total;

    if (empty($cliente_id) || empty($status_orcamento) || empty($itens_do_orcamento_post)) {
        $message = "Por favor, preencha todos os campos obrigatórios e adicione pelo menos um produto ao orçamento.";
        $message_type = "danger";
    } else {
        $conn->begin_transaction();

        try {
            if (empty($orcamento_id)) {
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
                    }
                } else {
                    $sql = "INSERT INTO orcamentos (cliente_id, valor_total, status_orcamento, observacoes, data_orcamento) VALUES (?, ?, ?, ?, NOW())";
                    if ($stmt = $conn->prepare($sql)) {
                        $stmt->bind_param("idss", $cliente_id, $valor_total, $status_orcamento, $observacoes);
                        if (!$stmt->execute()) {
                            throw new Exception("Erro ao registrar orçamento: " . $stmt->error);
                        }
                        $orcamento_id = $conn->insert_id;
                        $stmt->close();
                    }
                }
            }

            // Inserir itens do orçamento
            $sql_itens = "INSERT INTO itens_orcamento (orcamento_id, produto_id, quantidade, preco_unitario) VALUES (?, ?, ?, ?)";
            if ($stmt_itens = $conn->prepare($sql_itens)) {
                foreach ($itens_do_orcamento_post as $item) {
                    $stmt_itens->bind_param("iidi", $orcamento_id, $item['id'], $item['quantidade'], $item['preco_unitario']);
                    if (!$stmt_itens->execute()) {
                        throw new Exception("Erro ao inserir item: " . $stmt_itens->error);
                    }
                }
                $stmt_itens->close();
            }

            $conn->commit();
            $message = "Orçamento criado com sucesso!";
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
.modern-card { background: white; border-radius: 0.5rem; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-bottom: 2rem; }
.card-header-modern { background: #f8f9fa; padding: 1.5rem; border-bottom: 1px solid #dee2e6; border-radius: 0.5rem 0.5rem 0 0; font-weight: 600; }
.card-body-modern { padding: 1.5rem; }
.form-label { font-weight: 600; margin-bottom: 0.5rem; }
.btn-primary { background: #007bff; border: none; }
.btn-primary:hover { background: #0056b3; }
.table-hover tbody tr:hover { background-color: #f5f5f5; }
.badge { padding: 0.5rem 0.75rem; }
.dropdown-item { padding: 0.75rem 1rem; }
.dropdown-item:hover { background-color: #f8f9fa; }
#produto_dropdown { max-height: 300px; overflow-y: auto; }
.produto-item { padding: 0.75rem 1rem; cursor: pointer; border-bottom: 1px solid #eee; }
.produto-item:hover { background-color: #f8f9fa; }
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
                    <input type="text" class="form-control" id="cliente_search" placeholder="Buscar cliente..." autocomplete="off">
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
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>Produto</th>
                            <th>Quantidade</th>
                            <th>Preço Unit.</th>
                            <th>Subtotal</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody id="tabelaItens">
                    </tbody>
                    <tfoot>
                        <tr class="table-light">
                            <td colspan="3" class="text-end"><strong>Total:</strong></td>
                            <td><strong id="valor_total_display">R$ 0,00</strong></td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <div class="d-flex gap-2 justify-content-end">
                <a href="orcamentos.php" class="btn btn-secondary">Cancelar</a>
                <button type="submit" class="btn btn-primary"><?php echo $submit_button_text; ?></button>
            </div>
        </form>
    </div>
</div>

<script>
const produtosData = <?php echo json_encode($produtos_json); ?>;
const itensCarregados = <?php echo json_encode($itens_do_orcamento); ?>;

let itensOrcamento = itensCarregados.length > 0 ? itensCarregados : [];
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
        const matchEmpresa = !empresaSelecionada || p.empresa_id == empresaSelecionada;
        const matchBusca = p.nome.toLowerCase().includes(termo) || p.sku.toLowerCase().includes(termo);
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
        div.innerHTML = `<strong>${produto.nome}</strong><br><small>SKU: ${produto.sku} | R$ ${produto.preco.toFixed(2)}</small>`;
        div.style.cursor = 'pointer';
        div.onclick = () => selecionarProduto(produto);
        produtoDropdown.appendChild(div);
    });

    produtoDropdown.style.display = 'block';
}

function selecionarProduto(produto) {
    produtoSelecionado = produto;
    produtoSearch.value = produto.nome;
    precoInput.value = produto.preco.toFixed(2);
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

    const itemExistente = itensOrcamento.find(i => i.id === produtoSelecionado.id);
    if (itemExistente) {
        itemExistente.quantidade += quantidade;
    } else {
        itensOrcamento.push({
            id: produtoSelecionado.id,
            nome: produtoSelecionado.nome,
            quantidade: quantidade,
            preco_unitario: preco
        });
    }

    produtoSearch.value = '';
    precoInput.value = '';
    quantidadeInput.value = 1;
    produtoSelecionado = null;
    renderizarTabela();
}

function removerItem(id) {
    itensOrcamento = itensOrcamento.filter(i => i.id !== id);
    renderizarTabela();
}

function renderizarTabela() {
    tabelaItens.innerHTML = '';
    let total = 0;

    itensOrcamento.forEach(item => {
        const subtotal = item.quantidade * item.preco_unitario;
        total += subtotal;

        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td>${item.nome}</td>
            <td>${item.quantidade}</td>
            <td>R$ ${item.preco_unitario.toFixed(2)}</td>
            <td>R$ ${subtotal.toFixed(2)}</td>
            <td><button type="button" class="btn btn-sm btn-danger" onclick="removerItem(${item.id})">Remover</button></td>
        `;
        tabelaItens.appendChild(tr);
    });

    valorTotalDisplay.textContent = 'R$ ' + total.toFixed(2).replace('.', ',');
    itemsCount.textContent = itensOrcamento.length;
}

// Event listeners
produtoSearch.addEventListener('input', buscarProdutos);
empresaFilter.addEventListener('change', buscarProdutos);
addItemBtn.addEventListener('click', adicionarItem);

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

    const clientes = <?php echo json_encode($clientes_options ? array_map(function($c) { return ['id' => $c['id'], 'nome' => $c['nome']]; }, iterator_to_array($clientes_options)) : []); ?>;
    
    const resultados = clientes.filter(c => c.nome.toLowerCase().includes(termo)).slice(0, 10);

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

// Renderizar itens ao carregar
renderizarTabela();
</script>

<?php include_once 'includes/footer.php'; ?>


