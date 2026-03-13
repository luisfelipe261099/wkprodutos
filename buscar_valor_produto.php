<?php
require_once 'includes/session_bootstrap.php';
if (!isset($_SESSION['user_id'])) {
    header('HTTP/1.1 401 Unauthorized');
    echo json_encode(['error' => 'UsuÃ¡rio nÃ£o autenticado']);
    exit;
}

include 'includes/db_connect.php'; // Confirme se o caminho para db_connect.php estÃ¡ correto

$response = ['valor_venda' => null];

if (isset($_POST['produto_id']) && !empty($_POST['produto_id'])) {
    $produto_id = filter_var($_POST['produto_id'], FILTER_VALIDATE_INT);

    if ($produto_id) {
        $stmt = $conn->prepare("SELECT valor_venda FROM produtos WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param("i", $produto_id);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($row = $result->fetch_assoc()) {
                $response['valor_venda'] = $row['valor_venda'];
            }
            $stmt->close();
        } else {
            error_log("Falha ao preparar statement em buscar_valor_produto.php: " . $conn->error);
            $response['error'] = "Erro ao consultar o banco de dados.";
        }
    } else {
        $response['error'] = "ID do produto invÃ¡lido.";
    }
} else {
    $response['error'] = "ID do produto nÃ£o fornecido.";
}

$conn->close();
header('Content-Type: application/json');
echo json_encode($response);
?>

