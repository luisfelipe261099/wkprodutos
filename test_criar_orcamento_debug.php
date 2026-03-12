<?php
session_start();

// Verifica se o usuário está logado
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: index.php");
    exit;
}

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'includes/db_connect.php';

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

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teste Debug - Criar Orçamento</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .dropdown-menu { max-height: 200px; overflow-y: auto; }
        .dropdown-item { padding: 0.75rem 1rem; cursor: pointer; }
        .dropdown-item:hover { background-color: #f8f9fa; }
    </style>
</head>
<body>
<div class="container mt-5">
    <h1>🧪 Teste Debug - Busca de Clientes (PHP)</h1>
    
    <div class="alert alert-info">
        <h5>✅ Dados Carregados do Servidor:</h5>
        <p><strong>Total de Clientes:</strong> <?php echo count($clientes_json); ?></p>
        <p><strong>Total de Produtos:</strong> <?php echo count($produtos_json); ?></p>
    </div>
    
    <div class="card">
        <div class="card-body">
            <label class="form-label">Buscar Cliente</label>
            <input type="text" class="form-control" id="cliente_search" placeholder="Digite o nome do cliente..." autocomplete="off">
            <input type="hidden" id="cliente_id">
            <div id="cliente_dropdown" class="dropdown-menu w-100" style="display: none; position: static;"></div>
            
            <hr>
            
            <div class="alert alert-info">
                <strong>Cliente Selecionado:</strong>
                <p>ID: <span id="selected_id">-</span></p>
                <p>Nome: <span id="selected_name">-</span></p>
            </div>
        </div>
    </div>
</div>

<script>
// Dados do servidor PHP
const clientesData = <?php echo json_encode($clientes_json); ?>;
const produtosData = <?php echo json_encode($produtos_json); ?>;

console.log('✅ Clientes carregados:', clientesData.length);
console.log('✅ Produtos carregados:', produtosData.length);
console.log('📋 Primeiros 3 clientes:', clientesData.slice(0, 3));

// Elementos DOM
const clienteSearch = document.getElementById('cliente_search');
const clienteDropdown = document.getElementById('cliente_dropdown');
const clienteIdInput = document.getElementById('cliente_id');
const selectedId = document.getElementById('selected_id');
const selectedName = document.getElementById('selected_name');

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
            selectedId.textContent = cliente.id;
            selectedName.textContent = cliente.nome;
            clienteDropdown.style.display = 'none';
            console.log('✅ Cliente selecionado:', cliente);
        };
        clienteDropdown.appendChild(div);
    });

    clienteDropdown.style.display = 'block';
});
</script>
</body>
</html>

