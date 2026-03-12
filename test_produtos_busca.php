<?php
// Teste de busca de produtos - SEM REQUER LOGIN

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'includes/db_connect.php';

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

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teste - Busca de Produtos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .dropdown-menu { max-height: 300px; overflow-y: auto; }
        .dropdown-item { padding: 0.75rem 1rem; cursor: pointer; }
        .dropdown-item:hover { background-color: #f8f9fa; }
        .stats { background: #f0f8ff; padding: 15px; border-radius: 5px; margin-bottom: 20px; }
    </style>
</head>
<body>
<div class="container mt-5">
    <h1>🧪 Teste - Busca de Produtos</h1>
    
    <div class="stats">
        <h5>📊 Dados Carregados:</h5>
        <p><strong>Total de Clientes:</strong> <?php echo count($clientes_json); ?></p>
        <p><strong>Total de Produtos:</strong> <?php echo count($produtos_json); ?></p>
    </div>
    
    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5>🔍 Buscar Produto</h5>
                </div>
                <div class="card-body">
                    <input type="text" class="form-control" id="produto_search" placeholder="Digite o nome ou SKU..." autocomplete="off">
                    <input type="hidden" id="produto_id">
                    <div id="produto_dropdown" class="dropdown-menu w-100" style="display: none; position: static; margin-top: 5px;"></div>
                    
                    <hr>
                    
                    <div class="alert alert-info">
                        <strong>Produto Selecionado:</strong>
                        <p>ID: <span id="selected_id">-</span></p>
                        <p>Nome: <span id="selected_name">-</span></p>
                        <p>SKU: <span id="selected_sku">-</span></p>
                        <p>Preço: R$ <span id="selected_preco">-</span></p>
                        <p>Empresa: <span id="selected_empresa">-</span></p>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5>📋 Primeiros 10 Produtos</h5>
                </div>
                <div class="card-body" style="max-height: 400px; overflow-y: auto;">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Nome</th>
                                <th>SKU</th>
                                <th>Preço</th>
                            </tr>
                        </thead>
                        <tbody id="produtos_list">
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Dados do servidor PHP
const produtosData = <?php echo json_encode($produtos_json); ?>;
const clientesData = <?php echo json_encode($clientes_json); ?>;

console.log('✅ Produtos carregados:', produtosData.length);
console.log('✅ Clientes carregados:', clientesData.length);
console.log('📋 Primeiros 3 produtos:', produtosData.slice(0, 3));

// Elementos DOM
const produtoSearch = document.getElementById('produto_search');
const produtoDropdown = document.getElementById('produto_dropdown');
const produtoIdInput = document.getElementById('produto_id');
const selectedId = document.getElementById('selected_id');
const selectedName = document.getElementById('selected_name');
const selectedSku = document.getElementById('selected_sku');
const selectedPreco = document.getElementById('selected_preco');
const selectedEmpresa = document.getElementById('selected_empresa');
const produtosList = document.getElementById('produtos_list');

// Listar primeiros 10 produtos
function listarPrimeiros10() {
    produtosList.innerHTML = '';
    produtosData.slice(0, 10).forEach(p => {
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td>${p.nome}</td>
            <td>${p.sku}</td>
            <td>R$ ${p.preco.toFixed(2)}</td>
        `;
        produtosList.appendChild(tr);
    });
}

// Buscar produtos
produtoSearch.addEventListener('input', function() {
    const termo = this.value.toLowerCase();
    produtoDropdown.innerHTML = '';

    if (termo.length === 0) {
        produtoDropdown.style.display = 'none';
        return;
    }

    console.log('🔍 Buscando por:', termo);
    console.log('📊 Total de produtos disponíveis:', produtosData.length);

    const resultados = produtosData.filter(p => 
        p.nome.toLowerCase().includes(termo) || 
        p.sku.toLowerCase().includes(termo)
    ).slice(0, 10);

    console.log('✅ Resultados encontrados:', resultados.length);

    if (resultados.length === 0) {
        produtoDropdown.innerHTML = '<div class="dropdown-item text-muted">Nenhum produto encontrado</div>';
        produtoDropdown.style.display = 'block';
        return;
    }

    resultados.forEach(produto => {
        const div = document.createElement('div');
        div.className = 'dropdown-item';
        div.innerHTML = `<strong>${produto.nome}</strong><br><small>SKU: ${produto.sku} | R$ ${produto.preco.toFixed(2)}</small>`;
        div.onclick = () => {
            produtoSearch.value = produto.nome;
            produtoIdInput.value = produto.id;
            selectedId.textContent = produto.id;
            selectedName.textContent = produto.nome;
            selectedSku.textContent = produto.sku;
            selectedPreco.textContent = produto.preco.toFixed(2);
            selectedEmpresa.textContent = produto.empresa_nome || '-';
            produtoDropdown.style.display = 'none';
            console.log('✅ Produto selecionado:', produto);
        };
        produtoDropdown.appendChild(div);
    });

    produtoDropdown.style.display = 'block';
});

// Inicializar
listarPrimeiros10();
console.log('✅ Página carregada com sucesso!');
</script>
</body>
</html>

