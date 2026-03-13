<?php
require_once 'includes/session_bootstrap.php';

// Verifica se o usuÃ¡rio estÃ¡ logado
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acesso negado']);
    exit;
}

require_once 'includes/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'MÃ©todo nÃ£o permitido']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$produto_id = intval($input['produto_id'] ?? 0);

if (!$produto_id) {
    echo json_encode(['success' => false, 'message' => 'ID do produto invÃ¡lido']);
    exit;
}

try {
    // Buscar imagem atual
    $stmt_get = $conn->prepare("SELECT imagem_produto FROM produtos WHERE id = ?");
    $stmt_get->bind_param("i", $produto_id);
    $stmt_get->execute();
    $result = $stmt_get->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Produto nÃ£o encontrado']);
        exit;
    }
    
    $produto = $result->fetch_assoc();
    $imagem_atual = $produto['imagem_produto'];
    
    // Atualizar banco de dados
    $stmt_update = $conn->prepare("UPDATE produtos SET imagem_produto = NULL WHERE id = ?");
    $stmt_update->bind_param("i", $produto_id);
    
    if (!$stmt_update->execute()) {
        throw new Exception('Erro ao atualizar banco de dados');
    }
    
    // Remover arquivo fÃ­sico se existir
    if ($imagem_atual && file_exists("uploads/produtos/" . $imagem_atual)) {
        unlink("uploads/produtos/" . $imagem_atual);
    }
    
    echo json_encode(['success' => true, 'message' => 'Imagem removida com sucesso']);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$conn->close();
?>

