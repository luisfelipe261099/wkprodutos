<?php
require_once 'includes/session_bootstrap.php';

// Verifica se o usuÃ¡rio estÃ¡ logado
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acesso negado']);
    exit;
}

require_once 'includes/db_connect.php';

$cliente_id = intval($_GET['cliente_id'] ?? 0);

if (!$cliente_id) {
    echo json_encode(['success' => false, 'message' => 'Cliente ID invÃ¡lido']);
    exit;
}

try {
    $sql = "SELECT empresa_id FROM marketplace_cliente_empresas WHERE cliente_id = ? AND ativo = 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $cliente_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $empresas = [];
    while ($row = $result->fetch_assoc()) {
        $empresas[] = $row['empresa_id'];
    }
    
    echo json_encode(['success' => true, 'empresas' => $empresas]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Erro ao buscar empresas: ' . $e->getMessage()]);
}

$conn->close();
?>

