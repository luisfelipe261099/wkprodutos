<?php
/**
 * Gera o PDF padrão do orçamento para clientes via token (sem login).
 * Reutiliza a mesma função gerarPDFModerno() do gerar_pdf_orcamento.php.
 */
ob_start();
require_once 'includes/session_bootstrap.php';
require_once 'includes/db_connect.php';

// Autenticação via token
$token = $_GET['token'] ?? '';
$orcamento_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (empty($token) || $orcamento_id <= 0) {
    die('Dados inválidos.');
}

// Validar token
$sql_token = "SELECT ml.cliente_id
              FROM marketplace_links ml
              WHERE ml.token_acesso = ? AND ml.ativo = 1";
$stmt_token = $conn->prepare($sql_token);
$stmt_token->bind_param("s", $token);
$stmt_token->execute();
$result_token = $stmt_token->get_result();

if ($result_token->num_rows === 0) {
    die('Token inválido ou expirado.');
}

$link_data = $result_token->fetch_assoc();
$cliente_id = (int)$link_data['cliente_id'];
$stmt_token->close();

// Verificar que o orçamento pertence ao cliente
$sql_check = "SELECT id FROM orcamentos WHERE id = ? AND cliente_id = ?";
$stmt_check = $conn->prepare($sql_check);
$stmt_check->bind_param("ii", $orcamento_id, $cliente_id);
$stmt_check->execute();
$result_check = $stmt_check->get_result();

if ($result_check->num_rows === 0) {
    die('Orçamento não encontrado ou não pertence a este cliente.');
}
$stmt_check->close();

// Incluir PDFHelper e carregar função de geração de PDF
require_once 'includes/PDFHelper.php';

// Buscar dados completos do orçamento (mesma query do gerar_pdf_orcamento.php)
$sql_orcamento = "SELECT o.*, c.nome as cliente_nome, c.email as cliente_email, c.telefone, c.inscricao_estadual,
                         c.endereco, c.numero, c.ponto_referencia, c.nome_fantasia, c.cidade, c.estado, c.cep, c.cpf_cnpj, c.tipo_pessoa
                  FROM orcamentos o
                  LEFT JOIN clientes c ON o.cliente_id = c.id
                  WHERE o.id = ?";
$stmt_orcamento = $conn->prepare($sql_orcamento);
$stmt_orcamento->bind_param("i", $orcamento_id);
$stmt_orcamento->execute();
$result_orcamento = $stmt_orcamento->get_result();

if ($result_orcamento->num_rows === 0) {
    die('Orçamento não encontrado.');
}

$orcamento = $result_orcamento->fetch_assoc();

// Buscar itens do orçamento com informações da empresa
$sql_itens = "SELECT io.*, p.nome as produto_nome, p.sku as codigo, p.descricao,
                     e.nome_empresa, e.logo_empresa
              FROM itens_orcamento io
              LEFT JOIN produtos p ON io.produto_id = p.id
              LEFT JOIN empresas_representadas e ON p.empresa_id = e.id
              WHERE io.orcamento_id = ?";
$stmt_itens = $conn->prepare($sql_itens);
$stmt_itens->bind_param("i", $orcamento_id);
$stmt_itens->execute();
$result_itens = $stmt_itens->get_result();

$itens = [];
$empresas_logos = [];

while ($item = $result_itens->fetch_assoc()) {
    $item['quantidade'] = $item['quantidade'] ?? 1;
    $item['preco_unitario'] = $item['preco_unitario'] ?? 0;
    $item['produto_nome'] = $item['produto_nome'] ?? 'Produto sem nome';
    $item['codigo'] = $item['codigo'] ?? '';
    $item['descricao'] = $item['descricao'] ?? '';
    $item['nome_empresa'] = $item['nome_empresa'] ?? '';
    $item['logo_empresa'] = $item['logo_empresa'] ?? '';

    if (!empty($item['logo_empresa']) && !in_array($item['logo_empresa'], $empresas_logos)) {
        $empresas_logos[] = $item['logo_empresa'];
    }

    $itens[] = $item;
}

if (!isset($orcamento['valor_total'])) {
    $orcamento['valor_total'] = 0;
    foreach ($itens as $item) {
        $orcamento['valor_total'] += $item['quantidade'] * $item['preco_unitario'];
    }
}

// Carregar a função gerarPDFModerno() do arquivo principal
define('PDF_FUNCTION_ONLY', true);
require_once 'gerar_pdf_orcamento.php';

// Gerar o PDF usando a mesma lógica padrão
gerarPDFModerno($orcamento, $itens, $empresas_logos);

$conn->close();
