<?php
require_once __DIR__ . '/includes/session_bootstrap.php';

// Verifica se o usuário está logado
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    http_response_code(401);
    echo json_encode(['error' => 'Não autorizado']);
    exit;
}

require_once __DIR__ . '/includes/db_connect.php';

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

// Filtro opcional por empresa representada
$empresa_id = isset($_GET['empresa_id']) && ctype_digit((string)$_GET['empresa_id']) ? (int)$_GET['empresa_id'] : null;

try {
    // Preparar termo para busca LIKE
    $termo_like = '%' . $termo_busca . '%';
    $termo_inicio = $termo_busca . '%'; // Para priorizar resultados que começam com o termo

    $filtro_empresa_sql = $empresa_id !== null ? " AND p.empresa_id = ?" : "";

    // Query para buscar produtos por nome, SKU, descrição, fornecedor ou empresa
    $sql = "SELECT p.id, p.nome, p.sku, p.preco_venda, p.quantidade_estoque, p.fornecedor, e.nome_empresa
            FROM produtos p
            LEFT JOIN empresas_representadas e ON p.empresa_id = e.id
            WHERE (p.nome LIKE ?
                   OR p.sku LIKE ?
                   OR p.descricao LIKE ?
                   OR p.fornecedor LIKE ?
                   OR e.nome_empresa LIKE ?)
                  {$filtro_empresa_sql}
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

    // Bind: 5 termos do WHERE (+ empresa opcional) + 3 termos da ordenação
    $tipos = "sssss";
    $valores = [$termo_like, $termo_like, $termo_like, $termo_like, $termo_like];

    if ($empresa_id !== null) {
        $tipos .= "i";
        $valores[] = $empresa_id;
    }

    $tipos .= "sss";
    array_push($valores, $termo_inicio, $termo_inicio, $termo_inicio);

    $stmt->bind_param($tipos, ...$valores);

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

