<?php
header('Content-Type: application/json; charset=utf-8');
require_once 'includes/db_connect.php';

$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['action']) || !isset($input['token'])) {
    echo json_encode(['success' => false, 'message' => 'Dados inválidos']);
    exit;
}

$action = $input['action'];
$token = $input['token'];

// Validar token
$sql_token = "SELECT ml.cliente_id, c.nome as cliente_nome, c.email as cliente_email
              FROM marketplace_links ml
              LEFT JOIN clientes c ON ml.cliente_id = c.id
              WHERE ml.token_acesso = ? AND ml.ativo = 1";
$stmt_token = $conn->prepare($sql_token);
$stmt_token->bind_param("s", $token);
$stmt_token->execute();
$result_token = $stmt_token->get_result();

if ($result_token->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Token inválido ou expirado']);
    exit;
}

$link_data = $result_token->fetch_assoc();
$cliente_id = (int)$link_data['cliente_id'];
$stmt_token->close();

switch ($action) {
    case 'enviar_orcamento':
        $empresa_id = isset($input['empresa_id']) ? (int)$input['empresa_id'] : 0;
        $forma_pagamento = $input['forma_pagamento'] ?? 'faturamento';
        $tipo_faturamento = $input['tipo_faturamento'] ?? 'avista';
        $observacoes = trim($input['observacoes'] ?? '');
        $itens = $input['itens'] ?? [];

        // Validações
        if ($empresa_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Empresa não selecionada']);
            exit;
        }

        if (empty($itens)) {
            echo json_encode(['success' => false, 'message' => 'Nenhum produto no orçamento']);
            exit;
        }

        // Validar forma_pagamento (whitelist)
        $formas_validas = ['pix', 'debito', 'credito', 'dinheiro', 'faturamento', 'boleto'];
        if (!in_array($forma_pagamento, $formas_validas)) {
            $forma_pagamento = 'faturamento';
        }

        // Validar tipo_faturamento (whitelist)
        $tipos_validos = ['avista', '7', '15', '15_dias', '20', '20_dias', '21', '28', '30', '30_dias',
                          '45_dias', '60_dias', '90_dias', '15_30', '20_30', '21_30',
                          '20_30_45', '28_35_42', '28_35_42_59', '28_35_45', '30_45_60', '30_60_90'];
        if (!in_array($tipo_faturamento, $tipos_validos)) {
            $tipo_faturamento = 'avista';
        }

        // Sanitizar observacoes
        $observacoes = mb_substr($observacoes, 0, 2000);
        // Prefixar com indicativo de que veio do cliente
        $obs_final = "[Orçamento montado pelo cliente via link externo]\n";
        if (!empty($observacoes)) {
            $obs_final .= "Obs. do cliente: " . $observacoes;
        }

        // Verificar que empresa existe e está ativa
        $stmt_emp = $conn->prepare("SELECT id, comissao_padrao FROM empresas_representadas WHERE id = ? AND status = 'ativo'");
        $stmt_emp->bind_param("i", $empresa_id);
        $stmt_emp->execute();
        $result_emp = $stmt_emp->get_result();
        if ($result_emp->num_rows === 0) {
            echo json_encode(['success' => false, 'message' => 'Empresa inválida']);
            exit;
        }
        $empresa_data = $result_emp->fetch_assoc();
        $comissao = (float)$empresa_data['comissao_padrao'];
        $stmt_emp->close();

        // Calcular valor total e validar produtos
        $valor_total = 0;
        $itens_validados = [];

        foreach ($itens as $item) {
            $produto_id = isset($item['produto_id']) ? (int)$item['produto_id'] : 0;
            $quantidade = isset($item['quantidade']) ? (int)$item['quantidade'] : 0;

            if ($produto_id <= 0 || $quantidade <= 0) continue;

            // Buscar produto real do banco (preço atualizado e verificar empresa)
            $stmt_prod = $conn->prepare("SELECT id, preco_venda, quantidade_estoque FROM produtos WHERE id = ? AND empresa_id = ? AND ativo_marketplace = 1");
            $stmt_prod->bind_param("ii", $produto_id, $empresa_id);
            $stmt_prod->execute();
            $result_prod = $stmt_prod->get_result();

            if ($result_prod->num_rows === 0) continue;

            $prod = $result_prod->fetch_assoc();
            $preco_real = (float)$prod['preco_venda'];
            $stmt_prod->close();

            // Verificar estoque
            if ($quantidade > $prod['quantidade_estoque']) {
                $quantidade = (int)$prod['quantidade_estoque'];
            }

            if ($quantidade <= 0) continue;

            $subtotal = $preco_real * $quantidade;
            $valor_total += $subtotal;

            $itens_validados[] = [
                'produto_id' => $produto_id,
                'quantidade' => $quantidade,
                'preco_unitario' => $preco_real
            ];
        }

        if (empty($itens_validados)) {
            echo json_encode(['success' => false, 'message' => 'Nenhum produto válido no orçamento']);
            exit;
        }

        // Calcular comissão
        $valor_comissao = $valor_total * ($comissao / 100);

        try {
            $conn->begin_transaction();

            // Gerar ID manualmente (mesmo padrão do sistema)
            $result_max = $conn->query("SELECT IFNULL(MAX(id), 0) + 1 AS next_id FROM orcamentos");
            $novo_orcamento_id = (int)$result_max->fetch_assoc()['next_id'];

            // Inserir orçamento
            $sql_orc = "INSERT INTO orcamentos (id, cliente_id, data_orcamento, valor_total, status_orcamento,
                         forma_pagamento, tipo_faturamento, observacoes, empresa_id,
                         comissao_percentual, valor_comissao, data_criacao)
                         VALUES (?, ?, NOW(), ?, 'pendente', ?, ?, ?, ?, ?, ?, CURDATE())";
            $stmt_orc = $conn->prepare($sql_orc);
            $stmt_orc->bind_param("iidsssidd",
                $novo_orcamento_id,
                $cliente_id,
                $valor_total,
                $forma_pagamento,
                $tipo_faturamento,
                $obs_final,
                $empresa_id,
                $comissao,
                $valor_comissao
            );

            if (!$stmt_orc->execute()) {
                throw new Exception("Erro ao criar orçamento: " . $stmt_orc->error);
            }

            $orcamento_id = $novo_orcamento_id;
            $stmt_orc->close();

            // Gerar IDs dos itens manualmente (mesmo padrão do sistema)
            $result_max_item = $conn->query("SELECT IFNULL(MAX(id), 0) AS max_id FROM itens_orcamento");
            $max_item_id = (int)$result_max_item->fetch_assoc()['max_id'];

            // Inserir itens do orçamento
            $stmt_item = $conn->prepare("INSERT INTO itens_orcamento (id, orcamento_id, produto_id, quantidade, preco_unitario) VALUES (?, ?, ?, ?, ?)");

            foreach ($itens_validados as $index => $item) {
                $item_id = $max_item_id + $index + 1;
                $stmt_item->bind_param("iiiid",
                    $item_id,
                    $orcamento_id,
                    $item['produto_id'],
                    $item['quantidade'],
                    $item['preco_unitario']
                );

                if (!$stmt_item->execute()) {
                    throw new Exception("Erro ao inserir item: " . $stmt_item->error);
                }
            }
            $stmt_item->close();

            $conn->commit();

            echo json_encode([
                'success' => true,
                'orcamento_id' => $orcamento_id,
                'valor_total' => $valor_total,
                'message' => 'Orçamento enviado com sucesso!'
            ]);

        } catch (Exception $e) {
            $conn->rollback();
            echo json_encode(['success' => false, 'message' => 'Erro ao processar orçamento: ' . $e->getMessage()]);
        }
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Ação inválida']);
        break;
}

$conn->close();
?>