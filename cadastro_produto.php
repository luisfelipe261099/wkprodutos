<?php
require_once 'includes/session_bootstrap.php';

// Verifica se o usuÃ¡rio estÃ¡ logado
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: index.php");
    exit;
}

require_once 'includes/db_connect.php';

// VariÃ¡veis para o formulÃ¡rio
$id = $nome = $descricao = $sku = $preco_venda = $percentual_lucro = $quantidade_estoque = $estoque_minimo = $fornecedor = $empresa_id = $imagem_produto = "";
$title = "Cadastrar Novo Produto";
$submit_button_text = "Cadastrar Produto";
$message = '';
$message_type = '';

// Buscar empresas representadas para o dropdown
$sql_empresas = "SELECT id, nome_empresa FROM empresas_representadas WHERE status = 'ativo' ORDER BY nome_empresa ASC";
$result_empresas = $conn->query($sql_empresas);

// Processar formulÃ¡rio quando enviado
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Coleta e sanitiza os dados do formulÃ¡rio
    $id = trim($_POST["id"] ?? '');
    $nome = trim($_POST["nome"]);
    $descricao = trim($_POST["descricao"]);
    $sku = trim($_POST["sku"]);
    // Usar str_replace para converter vÃ­rgulas em pontos para valores decimais
    $preco_venda = str_replace(',', '.', trim($_POST["preco_venda"]));
    $percentual_lucro = str_replace(',', '.', trim($_POST["percentual_lucro"]));
    $quantidade_estoque = trim($_POST["quantidade_estoque"]);
    $estoque_minimo = trim($_POST["estoque_minimo"]);
    $fornecedor = trim($_POST["fornecedor"]);
    $empresa_id = trim($_POST["empresa_id"]);

    // ValidaÃ§Ã£o dos campos
    if (empty($nome) || empty($empresa_id) || !is_numeric($preco_venda) || !is_numeric($percentual_lucro) || !is_numeric($quantidade_estoque) || !is_numeric($estoque_minimo)) {
        $message = "Por favor, preencha todos os campos obrigatÃ³rios (*) e garanta que os valores numÃ©ricos estejam corretos.";
        $message_type = "danger";
    } else {
        if (empty($id)) { // Inserir Novo Produto
            // O campo `percentual_lucro` jÃ¡ existe na sua tabela, entÃ£o o SQL estÃ¡ correto.
            $sql = "INSERT INTO produtos (nome, descricao, sku, preco_venda, percentual_lucro, quantidade_estoque, estoque_minimo, fornecedor, empresa_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            if ($stmt = $conn->prepare($sql)) {
                // CORREÃ‡ÃƒO APLICADA: Os tipos de 'preco_venda' e 'percentual_lucro' sÃ£o 'd' (decimal), para nÃ£o arredondar.
                $stmt->bind_param("sssddiisi", $nome, $descricao, $sku, $preco_venda, $percentual_lucro, $quantidade_estoque, $estoque_minimo, $fornecedor, $empresa_id);
                if ($stmt->execute()) {
                    $message = "Produto cadastrado com sucesso!";
                    $message_type = "success";
                    $id = $nome = $descricao = $sku = $preco_venda = $percentual_lucro = $quantidade_estoque = $estoque_minimo = $fornecedor = $empresa_id = "";
                } else {
                    $message = "Erro ao cadastrar produto: " . $stmt->error;
                    $message_type = "danger";
                }
                $stmt->close();
            }
        } else { // Editar Produto Existente
            $sql = "UPDATE produtos SET nome = ?, descricao = ?, sku = ?, preco_venda = ?, percentual_lucro = ?, quantidade_estoque = ?, estoque_minimo = ?, fornecedor = ?, empresa_id = ? WHERE id = ?";
            if ($stmt = $conn->prepare($sql)) {
                // CORREÃ‡ÃƒO APLICADA: Os tipos tambÃ©m foram ajustados para 'd' (decimal) na atualizaÃ§Ã£o.
                $stmt->bind_param("sssddiisii", $nome, $descricao, $sku, $preco_venda, $percentual_lucro, $quantidade_estoque, $estoque_minimo, $fornecedor, $empresa_id, $id);
                if ($stmt->execute()) {
                    $message = "Produto atualizado com sucesso!";
                    $message_type = "success";
                } else {
                    $message = "Erro ao atualizar produto: " . $stmt->error;
                    $message_type = "danger";
                }
                $stmt->close();
            }
        }
    }
}

// Preencher formulÃ¡rio para ediÃ§Ã£o se um ID for passado via GET
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
                $message = "Produto nÃ£o encontrado.";
                $message_type = "danger";
                $id = "";
            }
        }
        $stmt_edit->close();
    }
}

$conn->close();

include_once 'includes/header.php';
?>

<!-- Select2 CSS -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />

<h2 class="mb-4"><i class="fas fa-<?php echo ($id ? 'edit' : 'plus-circle'); ?> me-2"></i> <?php echo $title; ?></h2>

<?php if (!empty($message)): ?>
    <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
        <?php echo $message; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="card shadow-sm mb-4">
    <div class="card-body">
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <input type="hidden" name="id" value="<?php echo htmlspecialchars($id); ?>">

            <!-- Campo de busca rÃ¡pida para verificar produtos existentes -->
            <?php if (empty($id)): // SÃ³ mostrar na criaÃ§Ã£o de novos produtos ?>
            <div class="row mb-3">
                <div class="col-12">
                    <div class="alert alert-info">
                        <i class="fas fa-search me-2"></i>
                        <strong>Busca RÃ¡pida:</strong> Antes de cadastrar, verifique se o produto jÃ¡ existe
                        <div class="mt-2">
                            <input type="text" class="form-control" id="quickSearch" placeholder="Digite o nome, cÃ³digo ou qualquer termo para buscar produtos existentes..." autocomplete="off">
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
                    <select class="form-select" id="empresa_id" name="empresa_id" required>
                        <option value="">Selecione a empresa...</option>
                        <?php
                        if ($result_empresas && $result_empresas->num_rows > 0) {
                            $result_empresas->data_seek(0);
                            while($empresa = $result_empresas->fetch_assoc()) {
                                $selected = ($empresa['id'] == $empresa_id) ? 'selected' : '';
                                echo "<option value='{$empresa['id']}' {$selected}>" . htmlspecialchars($empresa['nome_empresa']) . "</option>";
                            }
                        }
                        ?>
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="nome" class="form-label">Nome do Produto <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="nome" name="nome" value="<?php echo htmlspecialchars($nome); ?>" required>
                </div>
            </div>

            <div class="row">
                <div class="col-md-4 mb-3">
                    <label for="sku" class="form-label">SKU (CÃ³digo)</label>
                    <input type="text" class="form-control" id="sku" name="sku" value="<?php echo htmlspecialchars($sku); ?>">
                </div>
                <div class="col-md-8 mb-3">
                    <label for="fornecedor" class="form-label">Fornecedor</label>
                    <input type="text" class="form-control" id="fornecedor" name="fornecedor" value="<?php echo htmlspecialchars($fornecedor); ?>" placeholder="Nome do fornecedor original">
                </div>
            </div>

            <div class="mb-3">
                <label for="descricao" class="form-label">DescriÃ§Ã£o</label>
                <textarea class="form-control" id="descricao" name="descricao" rows="3"><?php echo htmlspecialchars($descricao); ?></textarea>
            </div>

            <!-- SeÃ§Ã£o de Imagem do Produto -->
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
                                Tamanho mÃ¡ximo: 5MB
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
                    <label for="preco_venda" class="form-label">PreÃ§o de Venda (R$) <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="preco_venda" name="preco_venda" value="<?php echo htmlspecialchars($preco_venda); ?>" required placeholder="0,00">
                </div>
                <div class="col-lg-2 col-md-6 mb-3">
                    <label for="percentual_lucro" class="form-label">% de Lucro <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="percentual_lucro" name="percentual_lucro" value="<?php echo htmlspecialchars($percentual_lucro); ?>" required placeholder="0,00">
                </div>
                <div class="col-lg-3 col-6 mb-3">
                    <label for="quantidade_estoque" class="form-label">Estoque Atual <span class="text-danger">*</span></label>
                    <input type="number" class="form-control" id="quantidade_estoque" name="quantidade_estoque" value="<?php echo htmlspecialchars($quantidade_estoque); ?>" required min="0">
                </div>
                <div class="col-lg-3 col-6 mb-3">
                    <label for="estoque_minimo" class="form-label">Estoque MÃ­nimo <span class="text-danger">*</span></label>
                    <input type="number" class="form-control" id="estoque_minimo" name="estoque_minimo" value="<?php echo htmlspecialchars($estoque_minimo); ?>" required min="0">
                </div>
            </div>

            <div class="d-flex justify-content-between mt-4">
                <button type="submit" class="btn btn-success btn-lg">
                    <i class="fas fa-<?php echo ($id ? 'save' : 'plus'); ?> me-2"></i> <?php echo $submit_button_text; ?>
                </button>
                <a href="produtos.php" class="btn btn-secondary btn-lg">
                    <i class="fas fa-arrow-left me-2"></i> Voltar para Produtos
                </a>
            </div>
        </form>
    </div>
</div>

<style>
/* Estilos para busca rÃ¡pida */
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

/* AnimaÃ§Ã£o suave para os resultados */
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

                    // Mostrar botÃ£o de remover se nÃ£o existir
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

    // Implementar busca rÃ¡pida de produtos
    const quickSearchInput = document.getElementById('quickSearch');
    const quickSearchResults = document.getElementById('quickSearchResults');
    const searchResultsList = document.getElementById('searchResultsList');
    let searchTimeout;

    if (quickSearchInput) {
        quickSearchInput.addEventListener('input', function() {
            const termo = this.value.trim();

            // Limpar timeout anterior
            clearTimeout(searchTimeout);

            if (termo.length < 2) {
                quickSearchResults.style.display = 'none';
                return;
            }

            // Debounce - aguardar 300ms apÃ³s parar de digitar
            searchTimeout = setTimeout(() => {
                buscarProdutos(termo);
            }, 300);
        });

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

        fetch(`api_busca_produtos.php?q=${encodeURIComponent(termo)}`)
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
        if (produtos.length === 0) {
            searchResultsList.innerHTML = '<div class="text-muted p-2"><i class="fas fa-info-circle"></i> Nenhum produto encontrado</div>';
            return;
        }

        let html = `<div class="mb-2"><strong>Produtos encontrados (${produtos.length}):</strong></div>`;

        produtos.forEach(produto => {
            html += `
                <div class="border-bottom pb-2 mb-2">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="flex-grow-1">
                            <div class="fw-semibold">${destacarTermo(produto.nome, termo)}</div>
                            <small class="text-muted">
                                SKU: ${destacarTermo(produto.sku, termo)} |
                                Fornecedor: ${destacarTermo(produto.fornecedor, termo)} |
                                Estoque: ${produto.quantidade_estoque}
                            </small>
                            <div class="text-primary">R$ ${produto.preco_venda}</div>
                        </div>
                        <div class="ms-2">
                            <a href="cadastro_produto.php?id=${produto.id}" class="btn btn-sm btn-outline-primary" title="Editar produto">
                                <i class="fas fa-edit"></i>
                            </a>
                        </div>
                    </div>
                </div>
            `;
        });

        searchResultsList.innerHTML = html;
    }

    function destacarTermo(texto, termo) {
        if (!termo || !texto) return texto;

        const regex = new RegExp(`(${termo.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')})`, 'gi');
        return texto.replace(regex, '<mark>$1</mark>');
    }
});
</script>

<!-- Select2 JS -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Inicializar Select2 para o campo de empresa
        $('#empresa_id').select2({
            placeholder: 'Buscar empresa por nome...',
            allowClear: true,
            language: 'pt-BR',
            width: '100%',
            theme: 'bootstrap-5'
        });
    });
</script>
});
</script>

<?php include_once 'includes/footer.php'; ?>
