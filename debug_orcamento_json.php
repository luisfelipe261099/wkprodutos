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
    <title>Debug JSON - Criar Orçamento</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <h1>🔍 Debug JSON - Dados do Orçamento</h1>
    
    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5>Clientes JSON</h5>
                </div>
                <div class="card-body">
                    <p><strong>Total:</strong> <?php echo count($clientes_json); ?></p>
                    <pre style="max-height: 300px; overflow-y: auto; background: #f5f5f5; padding: 10px; border-radius: 5px;">
<?php echo json_encode($clientes_json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE); ?>
                    </pre>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h5>Produtos JSON</h5>
                </div>
                <div class="card-body">
                    <p><strong>Total:</strong> <?php echo count($produtos_json); ?></p>
                    <pre style="max-height: 300px; overflow-y: auto; background: #f5f5f5; padding: 10px; border-radius: 5px;">
<?php echo json_encode(array_slice($produtos_json, 0, 5), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE); ?>
                    </pre>
                    <p class="text-muted">Mostrando apenas os 5 primeiros produtos...</p>
                </div>
            </div>
        </div>
    </div>
    
    <hr>
    
    <div class="alert alert-info">
        <h5>Como usar no JavaScript:</h5>
        <pre><code>const clientesData = &lt;?php echo json_encode($clientes_json); ?&gt;;
const produtosData = &lt;?php echo json_encode($produtos_json); ?&gt;;</code></pre>
    </div>
    
    <a href="criar_orcamento.php" class="btn btn-primary">← Voltar para Criar Orçamento</a>
</div>
</body>
</html>

