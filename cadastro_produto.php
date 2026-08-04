<?php
require_once __DIR__ . '/includes/session_bootstrap.php';

// Verifica se o usuário está logado
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: index.php");
    exit;
}

require_once __DIR__ . '/includes/db_connect.php';

function getIntegerColumnLimits(mysqli $conn, string $table, string $column): array {
    $sql = "SELECT DATA_TYPE, COLUMN_TYPE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ? LIMIT 1";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return ['min' => 0, 'max' => 2147483647];
    }

    $stmt->bind_param('ss', $table, $column);
    if (!$stmt->execute()) {
        $stmt->close();
        return ['min' => 0, 'max' => 2147483647];
    }

    $result = $stmt->get_result();
    $row = $result ? $result->fetch_assoc() : null;
    $stmt->close();

    if (!$row) {
        return ['min' => 0, 'max' => 2147483647];
    }

    $dataType = strtolower((string)$row['DATA_TYPE']);
    $columnType = strtolower((string)$row['COLUMN_TYPE']);
    $isUnsigned = strpos($columnType, 'unsigned') !== false;

    $ranges = [
        'tinyint' => ['signed' => [-128, 127], 'unsigned' => [0, 255]],
        'smallint' => ['signed' => [-32768, 32767], 'unsigned' => [0, 65535]],
        'mediumint' => ['signed' => [-8388608, 8388607], 'unsigned' => [0, 16777215]],
        'int' => ['signed' => [-2147483648, 2147483647], 'unsigned' => [0, 4294967295]],
        'integer' => ['signed' => [-2147483648, 2147483647], 'unsigned' => [0, 4294967295]],
        'bigint' => ['signed' => [PHP_INT_MIN, PHP_INT_MAX], 'unsigned' => [0, PHP_INT_MAX]],
    ];

    if (!isset($ranges[$dataType])) {
        return ['min' => 0, 'max' => 2147483647];
    }

    $range = $isUnsigned ? $ranges[$dataType]['unsigned'] : $ranges[$dataType]['signed'];
    return ['min' => $range[0], 'max' => $range[1]];
}

function parseIntegerStrict(string $value): ?int {
    $clean = trim($value);
    if ($clean === '' || !preg_match('/^-?\d+$/', $clean)) {
        return null;
    }

    if (!is_numeric($clean)) {
        return null;
    }

    return (int)$clean;
}

// Função auxiliar para gerar o próximo ID de produto (compatível com TiDB)
function getProximoProdutoId($conexao) {
    $result = $conexao->query("SELECT MAX(id) as max_id FROM produtos");
    if ($result && $row = $result->fetch_assoc()) {
        return intval($row['max_id']) + 1;
    }
    return 1;
}

// Variáveis para o formulário
$id = $nome = $descricao = $sku = $preco_venda = $percentual_lucro = $quantidade_estoque = $estoque_minimo = $fornecedor = $empresa_id = $imagem_produto = "";
$title = "Cadastrar Novo Produto";
$submit_button_text = "Cadastrar Produto";
$message = '';
$message_type = '';

// Processar formulário quando enviado
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Coleta e sanitiza os dados do formulário
    $id = trim($_POST["id"] ?? '');
    $nome = trim($_POST["nome"]);
    $descricao = trim($_POST["descricao"]);
    $sku = trim($_POST["sku"]);
    // Usar str_replace para converter vírgulas em pontos para valores decimais
    $preco_venda = str_replace(',', '.', trim($_POST["preco_venda"]));
    $percentual_lucro = str_replace(',', '.', trim($_POST["percentual_lucro"]));
    
    // Verificar se estoque é ilimitado
    $estoque_ilimitado = isset($_POST["estoque_ilimitado"]) && $_POST["estoque_ilimitado"] === 'on';
    $quantidade_estoque = $estoque_ilimitado ? -1 : trim($_POST["quantidade_estoque"]);
    $estoque_minimo = $estoque_ilimitado ? -1 : trim($_POST["estoque_minimo"]);
    
    $fornecedor = trim($_POST["fornecedor"]);
    $empresa_id = trim($_POST["empresa_id"]);

    // Validação dos campos
    if ($estoque_ilimitado) {
        // Se estoque ilimitado, usar -1 como sentinel value
        $quantidade_int = -1;
        $estoque_minimo_int = -1;
        $estoqueForaDoLimite = false;
    } else {
        // Validação normal para estoque limitado
        $quantidade_int = parseIntegerStrict($quantidade_estoque);
        $estoque_minimo_int = parseIntegerStrict($estoque_minimo);

        $limitesEstoque = getIntegerColumnLimits($conn, 'produtos', 'quantidade_estoque');
        $limitesEstoqueMinimo = getIntegerColumnLimits($conn, 'produtos', 'estoque_minimo');

        $estoqueForaDoLimite = (
            $quantidade_int === null
            || $quantidade_int < $limitesEstoque['min']
            || $quantidade_int > $limitesEstoque['max']
            || $estoque_minimo_int === null
            || $estoque_minimo_int < $limitesEstoqueMinimo['min']
            || $estoque_minimo_int > $limitesEstoqueMinimo['max']
        );
    }

    if (empty($nome) || empty($empresa_id) || !is_numeric($preco_venda) || !is_numeric($percentual_lucro) || $estoqueForaDoLimite) {
        $message = "Por favor, preencha todos os campos obrigatórios (*) e garanta que os valores numéricos estejam corretos.";
        if ($estoqueForaDoLimite) {
            $message .= " Quantidade em estoque deve estar entre {$limitesEstoque['min']} e {$limitesEstoque['max']}.";
        }
        $message_type = "danger";
    } else {
        $quantidade_estoque = $quantidade_int;
        $estoque_minimo = $estoque_minimo_int;
        if (empty($id)) { // Inserir Novo Produto
            // Gerar ID manualmente (compatível com TiDB Cloud)
            $novo_id = getProximoProdutoId($conn);
            
            $sql = "INSERT INTO produtos (id, nome, descricao, sku, preco_venda, percentual_lucro, quantidade_estoque, estoque_minimo, fornecedor, empresa_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            if ($stmt = $conn->prepare($sql)) {
                $stmt->bind_param("isssddiisi", $novo_id, $nome, $descricao, $sku, $preco_venda, $percentual_lucro, $quantidade_estoque, $estoque_minimo, $fornecedor, $empresa_id);
                try {
                    if ($stmt->execute()) {
                        $message = "Produto cadastrado com sucesso!";
                        $message_type = "success";
                        // Mantém a empresa selecionada para agilizar o cadastro em sequência
                        $id = $nome = $descricao = $sku = $preco_venda = $percentual_lucro = $quantidade_estoque = $estoque_minimo = $fornecedor = "";
                    } else {
                        $message = "Erro ao cadastrar produto: " . $stmt->error;
                        $message_type = "danger";
                    }
                } catch (Throwable $e) {
                    $message = "Erro ao cadastrar produto. Verifique os limites de estoque e tente novamente.";
                    $message_type = "danger";
                }
                $stmt->close();
            }
        } else { // Editar Produto Existente
            $sql = "UPDATE produtos SET nome = ?, descricao = ?, sku = ?, preco_venda = ?, percentual_lucro = ?, quantidade_estoque = ?, estoque_minimo = ?, fornecedor = ?, empresa_id = ? WHERE id = ?";
            if ($stmt = $conn->prepare($sql)) {
                // CORREÇÃO APLICADA: Os tipos também foram ajustados para 'd' (decimal) na atualização.
                $stmt->bind_param("sssddiisii", $nome, $descricao, $sku, $preco_venda, $percentual_lucro, $quantidade_estoque, $estoque_minimo, $fornecedor, $empresa_id, $id);
                try {
                    if ($stmt->execute()) {
                        $message = "Produto atualizado com sucesso!";
                        $message_type = "success";
                    } else {
                        $message = "Erro ao atualizar produto: " . $stmt->error;
                        $message_type = "danger";
                    }
                } catch (Throwable $e) {
                    $message = "Erro ao atualizar produto. Verifique os limites de estoque e tente novamente.";
                    $message_type = "danger";
                }
                $stmt->close();
            }
        }
    }
}

// Pré-selecionar a empresa quando o usuário vem da listagem já filtrada por empresa
if ($_SERVER["REQUEST_METHOD"] !== "POST" && !isset($_GET["id"]) && isset($_GET["empresa_id"]) && ctype_digit((string)$_GET["empresa_id"])) {
    $empresa_id = (int)$_GET["empresa_id"];
}

// Preencher formulário para edição se um ID for passado via GET
if (isset($_GET["id"]) && empty($message)) {
    $id = trim($_GET["id"]);
    $sql_edit = "SELECT id, nome, descricao, sku, preco_venda, percentual_lucro, quantidade_estoque, estoque_minimo, fornecedor, empresa_id, imagem_produto FROM produtos WHERE id = ?";
    if ($stmt_edit = $conn->prepare($sql_edit)) {
        $stmt_edit->bind_param("i", $id);
        if ($stmt_edit->execute()) {
            $result_edit = $stmt_edit->get_result();
            if ($result_edit->num_rows == 1) {
                $row = $result_edit->fetch_assoc();
                $nome = $row['nome'];
                $descricao = $row['descricao'];
                $sku = $row['sku'];
                $preco_venda = number_format($row['preco_venda'], 2, ',', '.');
                $percentual_lucro = number_format($row['percentual_lucro'], 2, ',', '.');
                $quantidade_estoque = $row['quantidade_estoque'];
                $estoque_minimo = $row['estoque_minimo'];
                $fornecedor = $row['fornecedor'];
                $empresa_id = $row['empresa_id'];
                $imagem_produto = $row['imagem_produto'];
                $title = "Editar Produto";
                $submit_button_text = "Atualizar Produto";
            } else {
                $message = "Produto não encontrado.";
                $message_type = "danger";
                $id = "";
            }
        }
        $stmt_edit->close();
    }
}

// Buscar empresas representadas para o dropdown. A empresa já vinculada ao produto
// entra na lista mesmo se estiver inativa, para não ser perdida ao salvar a edição.
$empresa_id_atual = ($empresa_id !== '' && ctype_digit((string)$empresa_id)) ? (int)$empresa_id : 0;
$sql_empresas = "SELECT id, nome_empresa, status FROM empresas_representadas
                 WHERE status = 'ativo' OR id = ?
                 ORDER BY nome_empresa ASC";
$stmt_empresas = $conn->prepare($sql_empresas);
$stmt_empresas->bind_param("i", $empresa_id_atual);
$stmt_empresas->execute();
$empresas_list = $stmt_empresas->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_empresas->close();

// -1 é o valor sentinela usado para "estoque ilimitado"
$estoque_e_ilimitado = ($quantidade_estoque !== '' && (int)$quantidade_estoque === -1);

$conn->close();

include_once __DIR__ . '/includes/header.php';
?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const checkboxIlimitado = document.getElementById('estoque_ilimitado');
    const inputQuantidade = document.getElementById('quantidade_estoque');
    const inputEstoqueMinimo = document.getElementById('estoque_minimo');

    function atualizarCamposEstoque() {
        const ilimitado = checkboxIlimitado.checked;
        inputQuantidade.disabled = ilimitado;
        inputEstoqueMinimo.disabled = ilimitado;
        inputQuantidade.required = !ilimitado;
        inputEstoqueMinimo.required = !ilimitado;

        if (ilimitado) {
            inputQuantidade.value = '';
            inputEstoqueMinimo.value = '';
        }
    }

    checkboxIlimitado.addEventListener('change', atualizarCamposEstoque);
    // Executar na carga para aplicar estado inicial
    atualizarCamposEstoque();
});
</script>

<div class="page-header fade-in-up d-flex flex-wrap justify-content-between align-items-center gap-3">
    <div>
        <h1 class="page-title"><i class="fas fa-<?php echo ($id ? 'edit' : 'plus-circle'); ?>"></i> <?php echo $title; ?></h1>
        <p class="page-subtitle mb-0">
            <?php echo $id
                ? 'Atualize os dados do produto e mantenha o estoque em dia.'
                : 'Vincule o produto a uma empresa representada para facilitar a busca depois.'; ?>
        </p>
    </div>
    <a href="produtos.php<?php echo (!$id && $empresa_id !== '' ? '?empresa_id=' . (int)$empresa_id : ''); ?>" class="btn btn-outline-secondary">
        <i class="fas fa-list me-2"></i> Ver produtos
    </a>
</div>

<?php if (!empty($message)): ?>
    <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show fade-in-up" role="alert">
        <i class="fas fa-<?php echo $message_type === 'success' ? 'check-circle' : 'exclamation-triangle'; ?> me-2"></i>
        <?php echo htmlspecialchars($message); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="card shadow-sm mb-4">
    <div class="card-body">
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <input type="hidden" name="id" value="<?php echo htmlspecialchars($id); ?>">

            <!-- Campo de busca rápida para verificar produtos existentes -->
            <?php if (empty($id)): // Só mostrar na criação de novos produtos ?>
            <div class="row mb-3">
                <div class="col-12">
                    <div class="alert alert-info">
                        <i class="fas fa-search me-2"></i>
                        <strong>Busca Rápida:</strong> Antes de cadastrar, verifique se o produto já existe
                        <div class="mt-2">
                            <input type="text" class="form-control" id="quickSearch" placeholder="Digite o nome, código ou qualquer termo para buscar produtos existentes..." autocomplete="off">
                            <div class="form-check mt-2">
                                <input class="form-check-input" type="checkbox" id="quickSearchEmpresa" checked>
                                <label class="form-check-label small" for="quickSearchEmpresa">
                                    Buscar somente na empresa selecionada
                                </label>
                            </div>
                            <div id="quickSearchResults" class="mt-2" style="display: none;">
                                <div class="card">
                                    <div class="card-body p-2">
                                        <div id="searchResultsList"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="empresa_id" class="form-label">Empresa Representada <span class="text-danger">*</span></label>
                    <select class="form-select" id="empresa_id" name="empresa_id" required
                            data-searchable data-search-placeholder="Buscar empresa por nome..."
                            data-search-empty="Nenhuma empresa encontrada">
                        <option value="">Selecione a empresa...</option>
                        <?php foreach ($empresas_list as $empresa): ?>
                            <option value="<?php echo (int)$empresa['id']; ?>" <?php echo ((string)$empresa['id'] === (string)$empresa_id) ? 'selected' : ''; ?>>
                                <?php
                                echo htmlspecialchars($empresa['nome_empresa']);
                                echo $empresa['status'] !== 'ativo' ? ' (inativa)' : '';
                                ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="form-text">Usada para filtrar os produtos na listagem.</div>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="nome" class="form-label">Nome do Produto <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="nome" name="nome" value="<?php echo htmlspecialchars($nome); ?>" required>
                </div>
            </div>

            <div class="row">
                <div class="col-md-4 mb-3">
                    <label for="sku" class="form-label">SKU (Código)</label>
                    <input type="text" class="form-control" id="sku" name="sku" value="<?php echo htmlspecialchars($sku); ?>">
                </div>
                <div class="col-md-8 mb-3">
                    <label for="fornecedor" class="form-label">Fornecedor</label>
                    <input type="text" class="form-control" id="fornecedor" name="fornecedor" value="<?php echo htmlspecialchars($fornecedor); ?>" placeholder="Nome do fornecedor original">
                </div>
            </div>

            <div class="mb-3">
                <label for="descricao" class="form-label">Descrição</label>
                <textarea class="form-control" id="descricao" name="descricao" rows="3"><?php echo htmlspecialchars($descricao); ?></textarea>
            </div>

            <!-- Seção de Imagem do Produto -->
            <?php if ($id): ?>
            <div class="mb-4">
                <label class="form-label">Imagem do Produto</label>
                <div class="row">
                    <div class="col-md-6">
                        <div class="border rounded p-3 text-center" id="imagemPreview">
                            <?php if ($imagem_produto && file_exists("uploads/produtos/" . $imagem_produto)): ?>
                                <img src="uploads/produtos/<?php echo htmlspecialchars($imagem_produto); ?>"
                                     alt="Imagem do produto"
                                     class="img-fluid rounded"
                                     style="max-height: 200px;">
                                <p class="mt-2 mb-0 text-muted small">Imagem atual</p>
                            <?php else: ?>
                                <div class="text-muted">
                                    <i class="fas fa-image fa-3x mb-2"></i>
                                    <p>Nenhuma imagem cadastrada</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <input type="file" class="form-control" id="imagemProduto" accept="image/*">
                            <div class="form-text">
                                Formatos aceitos: JPEG, PNG, GIF, WebP<br>
                                Tamanho máximo: 5MB
                            </div>
                        </div>
                        <button type="button" class="btn btn-outline-primary" id="uploadImagem" disabled>
                            <i class="fas fa-upload me-2"></i>Enviar Imagem
                        </button>
                        <?php if ($imagem_produto): ?>
                        <button type="button" class="btn btn-outline-danger ms-2" id="removerImagem">
                            <i class="fas fa-trash me-2"></i>Remover
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <div class="row">
                <div class="col-lg-4 col-md-6 mb-3">
                    <label for="preco_venda" class="form-label">Preço de Venda (R$) <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="preco_venda" name="preco_venda" value="<?php echo htmlspecialchars($preco_venda); ?>" required placeholder="0,00">
                </div>
                <div class="col-lg-2 col-md-6 mb-3">
                    <label for="percentual_lucro" class="form-label">% de Lucro <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="percentual_lucro" name="percentual_lucro" value="<?php echo htmlspecialchars($percentual_lucro); ?>" required placeholder="0,00">
                </div>
                <div class="col-lg-3 col-6 mb-3">
                    <label for="quantidade_estoque" class="form-label">Estoque Atual <span class="text-danger">*</span></label>
                    <input type="number" class="form-control" id="quantidade_estoque" name="quantidade_estoque" value="<?php echo htmlspecialchars($estoque_e_ilimitado ? '' : $quantidade_estoque); ?>" required min="0">
                </div>
                <div class="col-lg-3 col-6 mb-3">
                    <label for="estoque_minimo" class="form-label">Estoque Mínimo <span class="text-danger">*</span></label>
                    <input type="number" class="form-control" id="estoque_minimo" name="estoque_minimo" value="<?php echo htmlspecialchars($estoque_e_ilimitado ? '' : $estoque_minimo); ?>" required min="0">
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-12">
                    <div class="border rounded p-3 bg-light">
                        <div class="form-check mb-0">
                            <input class="form-check-input" type="checkbox" id="estoque_ilimitado" name="estoque_ilimitado" <?php echo ($estoque_e_ilimitado ? 'checked' : ''); ?>>
                            <label class="form-check-label" for="estoque_ilimitado">
                                <i class="fas fa-infinity me-2"></i><strong>Estoque Ilimitado</strong> (Fábrica/Fornecedor com quantidade indefinida)
                            </label>
                        </div>
                        <small class="text-muted d-block mt-2">Marque esta opção quando puder obter quantidades ilimitadas do produto sem necessidade de rastreamento de estoque. Os campos de estoque acima ficam desativados.</small>
                    </div>
                </div>
            </div>

            <div class="d-flex flex-column-reverse flex-sm-row justify-content-between gap-2 mt-4">
                <a href="produtos.php" class="btn btn-secondary btn-lg w-100 w-sm-auto">
                    <i class="fas fa-arrow-left me-2"></i> Voltar para Produtos
                </a>
                <button type="submit" class="btn btn-success btn-lg w-100 w-sm-auto">
                    <i class="fas fa-<?php echo ($id ? 'save' : 'plus'); ?> me-2"></i> <?php echo $submit_button_text; ?>
                </button>
            </div>
        </form>
    </div>
</div>

<style>
/* Estilos para busca rápida */
#quickSearchResults {
    position: relative;
    z-index: 1000;
    max-height: 400px;
    overflow-y: auto;
}

#quickSearchResults .card {
    border: 1px solid #dee2e6;
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
}

#quickSearchResults mark {
    background-color: #fff3cd;
    color: #856404;
    padding: 1px 3px;
    border-radius: 3px;
    font-weight: 500;
}

#quickSearch:focus {
    border-color: #0d6efd;
    box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
}

/* Animação suave para os resultados */
#quickSearchResults {
    animation: fadeIn 0.2s ease-in-out;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const imagemInput = document.getElementById('imagemProduto');
    const uploadBtn = document.getElementById('uploadImagem');
    const removerBtn = document.getElementById('removerImagem');
    const imagemPreview = document.getElementById('imagemPreview');
    const produtoId = <?php echo $id ? $id : 'null'; ?>;

    if (imagemInput) {
        imagemInput.addEventListener('change', function() {
            uploadBtn.disabled = !this.files.length;
        });
    }

    if (uploadBtn) {
        uploadBtn.addEventListener('click', function() {
            const file = imagemInput.files[0];
            if (!file) return;

            const formData = new FormData();
            formData.append('imagem', file);
            formData.append('produto_id', produtoId);

            uploadBtn.disabled = true;
            uploadBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Enviando...';

            fetch('upload_produto_imagem.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Atualizar preview da imagem
                    imagemPreview.innerHTML = `
                        <img src="uploads/produtos/${data.filename}"
                             alt="Imagem do produto"
                             class="img-fluid rounded"
                             style="max-height: 200px;">
                        <p class="mt-2 mb-0 text-muted small">Imagem atual</p>
                    `;

                    // Mostrar botão de remover se não existir
                    if (!removerBtn) {
                        const newRemoverBtn = document.createElement('button');
                        newRemoverBtn.type = 'button';
                        newRemoverBtn.className = 'btn btn-outline-danger ms-2';
                        newRemoverBtn.id = 'removerImagem';
                        newRemoverBtn.innerHTML = '<i class="fas fa-trash me-2"></i>Remover';
                        uploadBtn.parentNode.appendChild(newRemoverBtn);
                    }

                    alert('Imagem enviada com sucesso!');
                    imagemInput.value = '';
                } else {
                    alert('Erro ao enviar imagem: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                alert('Erro ao enviar imagem');
            })
            .finally(() => {
                uploadBtn.disabled = false;
                uploadBtn.innerHTML = '<i class="fas fa-upload me-2"></i>Enviar Imagem';
            });
        });
    }

    if (removerBtn) {
        removerBtn.addEventListener('click', function() {
            if (!confirm('Tem certeza que deseja remover a imagem?')) return;

            fetch('remover_produto_imagem.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ produto_id: produtoId })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    imagemPreview.innerHTML = `
                        <div class="text-muted">
                            <i class="fas fa-image fa-3x mb-2"></i>
                            <p>Nenhuma imagem cadastrada</p>
                        </div>
                    `;
                    removerBtn.remove();
                    alert('Imagem removida com sucesso!');
                } else {
                    alert('Erro ao remover imagem: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                alert('Erro ao remover imagem');
            });
        });
    }

    // Implementar busca rápida de produtos
    const quickSearchInput = document.getElementById('quickSearch');
    const quickSearchResults = document.getElementById('quickSearchResults');
    const searchResultsList = document.getElementById('searchResultsList');
    const quickSearchEmpresa = document.getElementById('quickSearchEmpresa');
    const empresaSelect = document.getElementById('empresa_id');
    let searchTimeout;

    function dispararBusca() {
        const termo = quickSearchInput.value.trim();

        clearTimeout(searchTimeout);

        if (termo.length < 2) {
            quickSearchResults.style.display = 'none';
            return;
        }

        // Debounce - aguardar 300ms após parar de digitar
        searchTimeout = setTimeout(() => {
            buscarProdutos(termo);
        }, 300);
    }

    if (quickSearchInput) {
        quickSearchInput.addEventListener('input', dispararBusca);

        // Refazer a busca ao trocar a empresa ou o escopo da busca
        if (quickSearchEmpresa) {
            quickSearchEmpresa.addEventListener('change', dispararBusca);
        }
        if (empresaSelect) {
            empresaSelect.addEventListener('change', dispararBusca);
        }

        // Esconder resultados ao clicar fora
        document.addEventListener('click', function(e) {
            if (!quickSearchInput.contains(e.target) && !quickSearchResults.contains(e.target)) {
                quickSearchResults.style.display = 'none';
            }
        });
    }

    function buscarProdutos(termo) {
        // Mostrar loading
        searchResultsList.innerHTML = '<div class="text-center p-2"><i class="fas fa-spinner fa-spin"></i> Buscando...</div>';
        quickSearchResults.style.display = 'block';

        // Restringe a busca à empresa selecionada, quando solicitado
        let url = `api_busca_produtos.php?q=${encodeURIComponent(termo)}`;
        const empresaId = empresaSelect ? empresaSelect.value : '';
        if (quickSearchEmpresa && quickSearchEmpresa.checked && empresaId) {
            url += `&empresa_id=${encodeURIComponent(empresaId)}`;
        }

        fetch(url)
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    throw new Error(data.error);
                }

                exibirResultados(data.produtos, termo);
            })
            .catch(error => {
                console.error('Erro na busca:', error);
                searchResultsList.innerHTML = '<div class="text-danger p-2"><i class="fas fa-exclamation-triangle"></i> Erro ao buscar produtos</div>';
            });
    }

    function exibirResultados(produtos, termo) {
        const escopoEmpresa = quickSearchEmpresa && quickSearchEmpresa.checked && empresaSelect && empresaSelect.value;

        if (produtos.length === 0) {
            searchResultsList.innerHTML = '<div class="text-muted p-2"><i class="fas fa-info-circle"></i> Nenhum produto encontrado' +
                (escopoEmpresa ? ' nesta empresa. Desmarque a opção acima para buscar em todas.' : '.') + '</div>';
            return;
        }

        let html = `<div class="mb-2"><strong>Produtos encontrados (${produtos.length}):</strong>` +
            (escopoEmpresa ? ' <span class="badge bg-secondary">apenas na empresa selecionada</span>' : '') + '</div>';

        produtos.forEach(produto => {
            const estoque = Number(produto.quantidade_estoque) < 0 ? 'Ilimitado' : produto.quantidade_estoque;
            html += `
                <div class="border-bottom pb-2 mb-2">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="flex-grow-1">
                            <div class="fw-semibold">${destacarTermo(produto.nome, termo)}</div>
                            <small class="text-muted d-block">
                                <i class="fas fa-building me-1"></i>${destacarTermo(produto.empresa, termo)}
                            </small>
                            <small class="text-muted">
                                SKU: ${destacarTermo(produto.sku, termo)} |
                                Fornecedor: ${destacarTermo(produto.fornecedor, termo)} |
                                Estoque: ${escapeHtml(estoque)}
                            </small>
                            <div class="text-primary">R$ ${escapeHtml(produto.preco_venda)}</div>
                        </div>
                        <div class="ms-2">
                            <a href="cadastro_produto.php?id=${encodeURIComponent(produto.id)}" class="btn btn-sm btn-outline-primary" title="Editar produto">
                                <i class="fas fa-edit"></i>
                            </a>
                        </div>
                    </div>
                </div>
            `;
        });

        searchResultsList.innerHTML = html;
    }

    function escapeHtml(texto) {
        return (texto === null || texto === undefined ? '' : String(texto))
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function destacarTermo(texto, termo) {
        const seguro = escapeHtml(texto);
        if (!termo || !seguro) return seguro;

        const regex = new RegExp(`(${escapeHtml(termo).replace(/[.*+?^${}()|[\]\\]/g, '\\$&')})`, 'gi');
        return seguro.replace(regex, '<mark>$1</mark>');
    }
});
</script>

<!-- Select com busca (vanilla JS, sem jQuery/Select2) -->
<script src="/js/searchable-select.js?v=<?php echo file_exists(__DIR__ . '/js/searchable-select.js') ? filemtime(__DIR__ . '/js/searchable-select.js') : '1'; ?>"></script>

<?php include_once __DIR__ . '/includes/footer.php'; ?>
