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

echo "<h2>🔍 DEBUG - Teste de Dados</h2>";

// Teste 1: Clientes
echo "<h3>1️⃣ Clientes</h3>";
$clientes_result = $conn->query("SELECT id, nome FROM clientes ORDER BY nome ASC");
if ($clientes_result) {
    echo "✅ Query executada com sucesso<br>";
    echo "📊 Total de clientes: " . $clientes_result->num_rows . "<br>";
    
    $clientes_json = [];
    if ($clientes_result->num_rows > 0) {
        while($cliente = $clientes_result->fetch_assoc()) {
            $clientes_json[] = [
                'id' => $cliente['id'],
                'nome' => $cliente['nome']
            ];
        }
    }
    echo "📋 Primeiros 3 clientes: " . json_encode(array_slice($clientes_json, 0, 3)) . "<br>";
} else {
    echo "❌ Erro na query: " . $conn->error . "<br>";
}

// Teste 2: Produtos
echo "<h3>2️⃣ Produtos</h3>";
$produtos_query = "SELECT p.id, p.nome, p.sku, p.preco_venda, p.empresa_id, e.nome_empresa
                   FROM produtos p
                   LEFT JOIN empresas_representadas e ON p.empresa_id = e.id
                   ORDER BY e.nome_empresa ASC, p.nome ASC";
$produtos_result = $conn->query($produtos_query);
if ($produtos_result) {
    echo "✅ Query executada com sucesso<br>";
    echo "📊 Total de produtos: " . $produtos_result->num_rows . "<br>";
    
    $produtos_json = [];
    if ($produtos_result->num_rows > 0) {
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
    echo "📋 Primeiros 3 produtos: " . json_encode(array_slice($produtos_json, 0, 3)) . "<br>";
} else {
    echo "❌ Erro na query: " . $conn->error . "<br>";
}

// Teste 3: Empresas
echo "<h3>3️⃣ Empresas</h3>";
$empresas_result = $conn->query("SELECT id, nome_empresa FROM empresas_representadas ORDER BY nome_empresa ASC");
if ($empresas_result) {
    echo "✅ Query executada com sucesso<br>";
    echo "📊 Total de empresas: " . $empresas_result->num_rows . "<br>";
} else {
    echo "❌ Erro na query: " . $conn->error . "<br>";
}

echo "<hr>";
echo "<a href='criar_orcamento.php'>← Voltar para Criar Orçamento</a>";
?>

