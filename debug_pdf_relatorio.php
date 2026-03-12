<?php
// Debug básico para testar o gerar_pdf_relatorio.php

session_start();

// Verifica se o usuário está logado e é admin
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}

if (!isset($_SESSION["nivel_acesso"]) || $_SESSION["nivel_acesso"] !== "admin") {
    echo "Acesso negado. Apenas administradores podem acessar esta página.";
    exit;
}

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== DEBUG GERAÇÃO PDF RELATÓRIO ===\n";

// Testar se as classes e arquivos existem
echo "1. Verificando arquivos necessários:\n";

if (file_exists('includes/db_connect.php')) {
    echo "✓ db_connect.php encontrado\n";
    require_once 'includes/db_connect.php';
} else {
    echo "✗ db_connect.php NÃO encontrado\n";
}

if (file_exists('includes/PDFHelper.php')) {
    echo "✓ PDFHelper.php encontrado\n";
    require_once 'includes/PDFHelper.php';
} else {
    echo "✗ PDFHelper.php NÃO encontrado\n";
}

$fpdf_path = __DIR__ . '/vendor/setasign/fpdf/fpdf.php';
if (file_exists($fpdf_path)) {
    echo "✓ FPDF encontrado\n";
    require_once $fpdf_path;
} else {
    echo "✗ FPDF NÃO encontrado em: $fpdf_path\n";
}

echo "\n2. Testando conexão com banco:\n";
try {
    if (isset($conn)) {
        echo "✓ Conexão com banco estabelecida\n";
    } else {
        echo "✗ Variável \$conn não existe\n";
    }
} catch (Exception $e) {
    echo "✗ Erro de conexão: " . $e->getMessage() . "\n";
}

echo "\n3. Testando parâmetros de entrada:\n";
$params = [
    'tipo_relatorio' => $_GET["tipo_relatorio"] ?? 'NÃO DEFINIDO',
    'data_inicio' => $_GET["data_inicio"] ?? 'NÃO DEFINIDO',
    'data_fim' => $_GET["data_fim"] ?? 'NÃO DEFINIDO',
    'empresa_id' => $_GET["empresa_id"] ?? 'NÃO DEFINIDO'
];

foreach ($params as $key => $value) {
    echo "$key: $value\n";
}

echo "\n4. Testando se classes existem:\n";
if (class_exists('PDFHelper')) {
    echo "✓ Classe PDFHelper carregada\n";
} else {
    echo "✗ Classe PDFHelper NÃO carregada\n";
}

if (class_exists('FPDF')) {
    echo "✓ Classe FPDF carregada\n";
} else {
    echo "✗ Classe FPDF NÃO carregada\n";
}

echo "\n5. Testando se funções do arquivo principal existem:\n";
// Incluir apenas as funções do arquivo principal
try {
    
    // Função simples de teste de SQL
    if (isset($conn)) {
        $test_sql = "SELECT COUNT(*) as count FROM vendas WHERE data_venda >= '2025-06-01' AND data_venda <= '2025-07-01'";
        $result = $conn->query($test_sql);
        if ($result) {
            $row = $result->fetch_assoc();
            echo "✓ Teste de consulta SQL: " . $row['count'] . " vendas encontradas no período\n";
        } else {
            echo "✗ Erro na consulta SQL de teste: " . $conn->error . "\n";
        }
    }
    
} catch (Exception $e) {
    echo "✗ Erro no teste SQL: " . $e->getMessage() . "\n";
}

echo "\n=== FIM DEBUG ===\n";
?>
