<?php
// Silencia erros de PHP para nÃ£o quebrar a saÃ­da JSON, mas os registra em log.
ini_set('display_errors', 0);
ini_set('log_errors', 1);
// error_log("API chamada", 3, "/path/to/your/debug.log"); // Descomente para debug avanÃ§ado

require_once 'includes/session_bootstrap.php';
require_once 'includes/db_connect.php';

header('Content-Type: application/json');

// Apenas usuÃ¡rios logados podem acessar
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    http_response_code(401); // Unauthorized
    echo json_encode(['error' => 'Acesso nÃ£o autorizado.']);
    exit;
}

// Garante que o ID da venda foi recebido
if (!isset($_GET['venda_id']) || !is_numeric($_GET['venda_id'])) {
    http_response_code(400); // Bad Request
    echo json_encode(['error' => 'ID da venda invÃ¡lido ou nÃ£o fornecido.']);
    exit;
}

$venda_id = intval($_GET['venda_id']);

// Busca os itens da venda e os dados relevantes dos produtos
$sql = "SELECT 
            iv.quantidade,
            p.nome,
            p.dias_recompra_sugerido
        FROM itens_venda iv
        JOIN produtos p ON iv.produto_id = p.id
        WHERE iv.venda_id = ?
        ORDER BY p.dias_recompra_sugerido ASC";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    http_response_code(500); // Internal Server Error
    echo json_encode(['error' => 'Erro ao preparar a consulta: ' . $conn->error]);
    exit;
}

$stmt->bind_param("i", $venda_id);
$stmt->execute();
$result = $stmt->get_result();
$itens = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$conn->close();

// Retorna os dados como JSON
echo json_encode($itens);
?>
