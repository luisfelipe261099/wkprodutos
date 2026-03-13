<?php
require_once 'includes/session_bootstrap.php';

// Verifica se o usuário está logado
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    http_response_code(401);
    echo json_encode(['error' => 'Não autorizado']);
    exit;
}

require_once 'includes/db_connect.php';

// Verifica se é uma requisição GET com termo de busca
if ($_SERVER["REQUEST_METHOD"] !== "GET" || !isset($_GET['q'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Parâmetro de busca obrigatório']);
    exit;
}

$termo_busca = trim($_GET['q']);

// Se o termo for muito curto, não buscar
if (strlen($termo_busca) < 2) {
    echo json_encode(['produtos' => []]);
    exit;
}

try {
    // Preparar termo para busca LIKE
    $termo_like = '%' . $termo_busca . '%';
    
    // Query para buscar produtos por nome, SKU, descrição ou fornecedor
    $sql = "SELECT p.id, p.nome, p.sku, p.preco_venda, p.quantidade_estoque, p.fornecedor, e.nome_empresa
            FROM produtos p
            LEFT JOIN empresas_representadas e ON p.empresa_id = e.id
            WHERE (p.nome LIKE ? 
                   OR p.sku LIKE ? 
                   OR p.descricao LIKE ? 
                   OR p.fornecedor LIKE ?)
            ORDER BY 
                CASE 
                    WHEN p.nome LIKE ? THEN 1
                    WHEN p.sku LIKE ? THEN 2
                    WHEN p.fornecedor LIKE ? THEN 3
                    ELSE 4
                END,
                p.nome ASC
            LIMIT 10";
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception("Erro na preparação da query: " . $conn->error);
    }
    
    // Bind dos parâmetros (7 vezes o mesmo termo)
    $termo_inicio = $termo_busca . '%'; // Para priorizar resultados que começam com o termo
    $stmt->bind_param("sssssss", $termo_like, $termo_like, $termo_like, $termo_like, $termo_inicio, $termo_inicio, $termo_inicio);
    
    if (!$stmt->execute()) {
        throw new Exception("Erro na execução da query: " . $stmt->error);
    }
    
    $result = $stmt->get_result();
    $produtos = [];
    
    while ($row = $result->fetch_assoc()) {
        $produtos[] = [
            'id' => $row['id'],
            'nome' => $row['nome'],
            'sku' => $row['sku'] ?: 'N/A',
            'preco_venda' => number_format($row['preco_venda'], 2, ',', '.'),
            'quantidade_estoque' => $row['quantidade_estoque'],
            'fornecedor' => $row['fornecedor'] ?: 'N/A',
            'empresa' => $row['nome_empresa'] ?: 'N/A'
        ];
    }
    
    $stmt->close();
    
    // Retornar resultados em JSON
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'produtos' => $produtos,
        'total' => count($produtos),
        'termo_busca' => $termo_busca
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erro interno do servidor: ' . $e->getMessage()]);
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
?>

