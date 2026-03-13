<?php
// Inicia o output buffering para evitar problemas de "headers already sent"
ob_start();
require_once 'includes/session_bootstrap.php';

// ===================================================================================
// 1. SETUP INICIAL E INCLUSÃƒO DE BIBLIOTECAS
// ===================================================================================

// Habilita exibiÃ§Ã£o de erros para depuraÃ§Ã£o (remover em produÃ§Ã£o)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Verifica se o usuÃ¡rio estÃ¡ logado
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    if (ob_get_length()) ob_end_clean();
    header("location: index.php");
    exit;
}

// Inclui PHPMailer e conexÃ£o com o banco
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once 'vendor/autoload.php';
require_once 'includes/db_connect.php';

// ===================================================================================
// 2. DEFINIÃ‡ÃƒO DE FUNÃ‡Ã•ES AUXILIARES
// ===================================================================================

function registrarHistoricoOrcamento($conn, $orcamento_id, $status_anterior, $status_novo, $observacoes = '') {
    if ($status_anterior === $status_novo && empty($observacoes)) return true;
    $usuario_id = $_SESSION['id'] ?? null;
    $status_anterior_db = $status_anterior ?? 'criado';
    
    $sql = "INSERT INTO historico_orcamentos (orcamento_id, status_anterior, status_novo, observacoes, data_alteracao, usuario_id)
            VALUES (?, ?, ?, ?, NOW(), ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("isssi", $orcamento_id, $status_anterior_db, $status_novo, $observacoes, $usuario_id);
    return $stmt->execute();
}

function gerarPDFComoString($orcamento_id, $conn) {
    try {
        $sql_orcamento = "SELECT o.*, c.nome AS nome_cliente, c.email FROM orcamentos o JOIN clientes c ON o.cliente_id = c.id WHERE o.id = ?";
        $stmt = $conn->prepare($sql_orcamento);
        $stmt->bind_param("i", $orcamento_id);
        $stmt->execute();
        $orcamento = $stmt->get_result()->fetch_assoc();
        
        if (!$orcamento) throw new Exception("OrÃ§amento nÃ£o encontrado");

        $sql_itens = "SELECT i.*, p.nome AS nome_produto FROM itens_orcamento i JOIN produtos p ON i.produto_id = p.id WHERE i.orcamento_id = ?";
        $stmt_itens = $conn->prepare($sql_itens);
        $stmt_itens->bind_param("i", $orcamento_id);
        $stmt_itens->execute();
        $itens = $stmt_itens->get_result();

        require_once __DIR__ . '/vendor/setasign/fpdf/fpdf.php';
        
        $pdf = new FPDF();
        $pdf->AddPage();
        $pdf->SetFont('Arial','B',16);
        $pdf->Cell(0,10,'Orcamento N: ' . $orcamento['id'],0,1,'C');
        $pdf->Ln(10);
        $pdf->SetFont('Arial','B',12);
        $pdf->Cell(0,10,'Informacoes do Orcamento',0,1);
        $pdf->SetFont('Arial','',12);
        $pdf->Cell(40,7,'Cliente:',0);
        $pdf->Cell(0,7,$orcamento['nome_cliente'],0,1);
        $pdf->Cell(40,7,'Data:',0);
        $pdf->Cell(0,7,date('d/m/Y', strtotime($orcamento['data_orcamento'])),0,1);
        $pdf->Cell(40,7,'Status:',0);
        $pdf->Cell(0,7,ucfirst($orcamento['status_orcamento']),0,1);
        $pdf->Ln(10);
        $pdf->SetFont('Arial','B',12);
        $pdf->Cell(0,10,'Itens do Orcamento',0,1);
        $pdf->SetFillColor(230,230,230);
        $pdf->SetFont('Arial','B',10);
        $pdf->Cell(90,7,'Produto',1,0,'C',true);
        $pdf->Cell(25,7,'Quantidade',1,0,'C',true);
        $pdf->Cell(35,7,'Valor Unit.',1,0,'C',true);
        $pdf->Cell(40,7,'Subtotal',1,1,'C',true);
        $pdf->SetFont('Arial','',10);
        
        while ($item = $itens->fetch_assoc()) {
            $preco = $item['preco_unitario'] ?? 0;
            $subtotal = $item['quantidade'] * $preco;
            $pdf->Cell(90,7,iconv('UTF-8', 'ISO-8859-1//IGNORE', $item['nome_produto']),1,0);
            $pdf->Cell(25,7,$item['quantidade'],1,0,'C');
            $pdf->Cell(35,7,'R$ '.number_format($preco, 2, ',', '.'),1,0,'R');
            $pdf->Cell(40,7,'R$ '.number_format($subtotal, 2, ',', '.'),1,1,'R');
        }
        
        $pdf->SetFont('Arial','B',11);
        $pdf->Cell(150,7,'Valor Total:',1,0,'R');
        $pdf->Cell(40,7,'R$ '.number_format($orcamento['valor_total'], 2, ',', '.'),1,1,'R');
        
        if (!empty($orcamento['observacoes'])) {
            $pdf->Ln(10);
            $pdf->SetFont('Arial','B',12);
            $pdf->Cell(0,7,'Observacoes:',0,1);
            $pdf->SetFont('Arial','',11);
            $pdf->MultiCell(0,7,iconv('UTF-8', 'ISO-8859-1//IGNORE', $orcamento['observacoes']),0);
        }
        
        return $pdf->Output('S');
    } catch (Exception $e) {
        error_log("Erro ao gerar PDF do orÃ§amento #$orcamento_id: " . $e->getMessage());
        throw $e;
    }
}

// ===================================================================================
// 3. BLOCO DE PROCESSAMENTO DE AÃ‡Ã•ES (POST e GET)
// ===================================================================================

// --- NOVA AÃ‡ÃƒO DE EXCLUIR ORÃ‡AMENTO ---
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'delete') {
    $orcamento_id = intval($_GET['id']);

    $conn->begin_transaction();
    try {
        // 1. Excluir itens do orÃ§amento
        $stmt_itens = $conn->prepare("DELETE FROM itens_orcamento WHERE orcamento_id = ?");
        $stmt_itens->bind_param("i", $orcamento_id);
        $stmt_itens->execute();

        // 2. Excluir histÃ³rico do orÃ§amento
        $stmt_hist = $conn->prepare("DELETE FROM historico_orcamentos WHERE orcamento_id = ?");
        $stmt_hist->bind_param("i", $orcamento_id);
        $stmt_hist->execute();

        // 3. Excluir o orÃ§amento principal
        $stmt_orc = $conn->prepare("DELETE FROM orcamentos WHERE id = ?");
        $stmt_orc->bind_param("i", $orcamento_id);
        $stmt_orc->execute();

        $conn->commit();
        $_SESSION['message'] = "OrÃ§amento #{$orcamento_id} e seus dados foram excluÃ­dos com sucesso.";
        $_SESSION['message_type'] = 'success';
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['message'] = "Erro ao excluir o orÃ§amento: " . $e->getMessage();
        $_SESSION['message_type'] = 'danger';
        error_log("Erro na exclusÃ£o do orÃ§amento #$orcamento_id: " . $e->getMessage());
    }
    
    if (ob_get_length()) ob_end_clean();
    header("Location: orcamentos.php");
    exit;
}

// AÃ§Ã£o de Mudar Status (via POST do Modal)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'change_status') {
    $orcamento_id = intval($_POST['orcamento_id']);
    $novo_status = $_POST['novo_status'];
    $observacoes_status = trim($_POST['observacoes']); // Renomeado para evitar conflito

    $conn->begin_transaction();
    try {
        // Buscar status anterior
        $stmt_get_status = $conn->prepare("SELECT status_orcamento FROM orcamentos WHERE id = ?");
        $stmt_get_status->bind_param("i", $orcamento_id);
        $stmt_get_status->execute();
        $result_status = $stmt_get_status->get_result();
        if ($result_status->num_rows === 0) {
            throw new Exception("OrÃ§amento nÃ£o encontrado.");
        }
        $status_anterior = $result_status->fetch_assoc()['status_orcamento'];
        $stmt_get_status->close();

        // Atualizar status do orÃ§amento
        $stmt_update_orcamento = $conn->prepare("UPDATE orcamentos SET status_orcamento = ? WHERE id = ?");
        $stmt_update_orcamento->bind_param("si", $novo_status, $orcamento_id);
        if (!$stmt_update_orcamento->execute()) {
            throw new Exception("Erro ao atualizar o status do orÃ§amento: " . $stmt_update_orcamento->error);
        }
        $stmt_update_orcamento->close();

        registrarHistoricoOrcamento($conn, $orcamento_id, $status_anterior, $novo_status, $observacoes_status);
        $_SESSION['message'] = "Status do orÃ§amento #{$orcamento_id} atualizado para '{$novo_status}'.";
        $_SESSION['message_type'] = 'success';

        // Se o novo status for 'aprovado', criar a venda automaticamente
        if ($novo_status === 'aprovado') {
            // 1. Buscar dados do orÃ§amento para a venda
            $stmt_get_orc_details = $conn->prepare("SELECT cliente_id, valor_total, data_orcamento, observacoes, forma_pagamento, empresa_id, comissao_percentual, valor_comissao FROM orcamentos WHERE id = ?");
            $stmt_get_orc_details->bind_param("i", $orcamento_id);
            $stmt_get_orc_details->execute();
            $orcamento_details = $stmt_get_orc_details->get_result()->fetch_assoc();
            $stmt_get_orc_details->close();

            if (!$orcamento_details) {
                throw new Exception("Detalhes do orÃ§amento nÃ£o encontrados para criar a venda.");
            }

            // 2. Inserir na tabela \'vendas\'
            $data_venda = date('Y-m-d H:i:s'); // Data atual para a venda
            $status_venda_padrao = 'pendente'; // Status inicial da venda

            // Campos da tabela vendas: cliente_id, data_venda, valor_total, forma_pagamento, status_venda, empresa_id, comissao_percentual, valor_comissao
            $stmt_insert_venda = $conn->prepare("INSERT INTO vendas (cliente_id, data_venda, valor_total, forma_pagamento, status_venda, empresa_id, comissao_percentual, valor_comissao) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt_insert_venda->bind_param("isdssidd", 
                $orcamento_details['cliente_id'], 
                $data_venda, 
                $orcamento_details['valor_total'], 
                $orcamento_details['forma_pagamento'], 
                $status_venda_padrao, 
                $orcamento_details['empresa_id'], 
                $orcamento_details['comissao_percentual'], 
                $orcamento_details['valor_comissao']
            );
            if (!$stmt_insert_venda->execute()) {
                throw new Exception("Erro ao registrar a venda: " . $stmt_insert_venda->error);
            }
            $venda_id = $stmt_insert_venda->insert_id;
            $stmt_insert_venda->close();

            // 3. Buscar itens do orÃ§amento e inserir em 'itens_venda'
            $stmt_get_itens = $conn->prepare("SELECT produto_id, quantidade, preco_unitario FROM itens_orcamento WHERE orcamento_id = ?");
            $stmt_get_itens->bind_param("i", $orcamento_id);
            $stmt_get_itens->execute();
            $itens_orcamento = $stmt_get_itens->get_result();
            $stmt_get_itens->close();

            // Assumindo colunas: venda_id, produto_id, quantidade, preco_unitario
            $stmt_insert_item_venda = $conn->prepare("INSERT INTO itens_venda (venda_id, produto_id, quantidade, preco_unitario) VALUES (?, ?, ?, ?)");
            while ($item = $itens_orcamento->fetch_assoc()) {
                $stmt_insert_item_venda->bind_param("iiid", $venda_id, $item['produto_id'], $item['quantidade'], $item['preco_unitario']);
                if (!$stmt_insert_item_venda->execute()) {
                    throw new Exception("Erro ao registrar item da venda: " . $stmt_insert_item_venda->error);
                }
            }
            $stmt_insert_item_venda->close();

            // Adicionar mensagem de sucesso para a venda
            $_SESSION['message'] .= " Venda #{$venda_id} registrada automaticamente.";
            
            // Registrar no histÃ³rico do orÃ§amento que a venda foi criada
            registrarHistoricoOrcamento($conn, $orcamento_id, $novo_status, $novo_status, "Venda #{$venda_id} gerada automaticamente a partir deste orÃ§amento.");
        }

        $conn->commit();

    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['message'] = "Erro: " . $e->getMessage();
        $_SESSION['message_type'] = 'danger';
        error_log("Erro na operaÃ§Ã£o de mudanÃ§a de status/criaÃ§Ã£o de venda para orÃ§amento #$orcamento_id: " . $e->getMessage());
    }
    
    if (ob_get_length()) ob_end_clean();
    header("Location: orcamentos.php");
    exit;
}

// AÃ§Ã£o de Converter OrÃ§amento em Venda (via POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'convert_to_sale') {
    $orcamento_id = intval($_POST['orcamento_id']);
    $forma_pagamento = trim($_POST['forma_pagamento']);

    $conn->begin_transaction();
    try {
        // 1. Buscar dados do orÃ§amento
        $stmt_orcamento = $conn->prepare("SELECT * FROM orcamentos WHERE id = ? AND status_orcamento = 'aprovado'");
        $stmt_orcamento->bind_param("i", $orcamento_id);
        $stmt_orcamento->execute();
        $orcamento = $stmt_orcamento->get_result()->fetch_assoc();

        if (!$orcamento) {
            throw new Exception("OrÃ§amento nÃ£o encontrado ou nÃ£o estÃ¡ aprovado.");
        }

        // 2. Criar nova venda
        $stmt_venda = $conn->prepare("INSERT INTO vendas (cliente_id, data_venda, valor_total, forma_pagamento, status_venda) VALUES (?, NOW(), ?, ?, 'concluida')");
        $stmt_venda->bind_param("ids", $orcamento['cliente_id'], $orcamento['valor_total'], $forma_pagamento);
        $stmt_venda->execute();
        $venda_id = $conn->insert_id;

        // 3. Buscar itens do orÃ§amento
        $stmt_itens_orc = $conn->prepare("SELECT * FROM itens_orcamento WHERE orcamento_id = ?");
        $stmt_itens_orc->bind_param("i", $orcamento_id);
        $stmt_itens_orc->execute();
        $itens_orcamento = $stmt_itens_orc->get_result();

        // 4. Criar itens da venda e atualizar estoque
        while ($item = $itens_orcamento->fetch_assoc()) {
            // Inserir item na venda
            $stmt_item_venda = $conn->prepare("INSERT INTO itens_venda (venda_id, produto_id, quantidade, preco_unitario) VALUES (?, ?, ?, ?)");
            $stmt_item_venda->bind_param("iiid", $venda_id, $item['produto_id'], $item['quantidade'], $item['preco_unitario']);
            $stmt_item_venda->execute();

            // Atualizar estoque do produto
            $stmt_estoque = $conn->prepare("UPDATE produtos SET quantidade_estoque = quantidade_estoque - ? WHERE id = ?");
            $stmt_estoque->bind_param("ii", $item['quantidade'], $item['produto_id']);
            $stmt_estoque->execute();
        }

        // 5. Atualizar status do orÃ§amento para 'convertido_venda'
        $stmt_update_orc = $conn->prepare("UPDATE orcamentos SET status_orcamento = 'convertido_venda' WHERE id = ?");
        $stmt_update_orc->bind_param("i", $orcamento_id);
        $stmt_update_orc->execute();

        // 6. Registrar histÃ³rico
        registrarHistoricoOrcamento($conn, $orcamento_id, 'aprovado', 'convertido_venda', "Convertido em venda #$venda_id");

        $conn->commit();

        if (ob_get_length()) ob_end_clean();
        header("Location: vendas.php?success=orcamento_convertido&venda_id=$venda_id");
        exit;

    } catch (Exception $e) {
        $conn->rollback();
        $erro_conversao = "Erro ao converter orÃ§amento: " . $e->getMessage();
    }
}

// AÃ§Ã£o de Enviar E-mail (via GET)
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'send_email') {
    $orcamento_id = intval($_GET['id']);

    // Buscar dados do cliente e orÃ§amento
    $stmt_cli = $conn->prepare("SELECT c.nome, c.email, o.status_orcamento FROM clientes c JOIN orcamentos o ON c.id = o.cliente_id WHERE o.id = ?");
    $stmt_cli->bind_param("i", $orcamento_id);
    $stmt_cli->execute();
    $data = $stmt_cli->get_result()->fetch_assoc();

    if ($data && !empty($data['email'])) {
        $mail = new PHPMailer(true);
        try {
            // Carrega configuraÃ§Ãµes do SMTP do arquivo de configuraÃ§Ã£o
            $email_config = include 'includes/email_config.php';

            // ConfiguraÃ§Ãµes do servidor SMTP
            $mail->isSMTP();
            $mail->Host = $email_config['host'];
            $mail->SMTPAuth = true;
            $mail->Username = $email_config['username'];
            $mail->Password = $email_config['password'];

            // Configurar encryption baseado no arquivo de configuraÃ§Ã£o
            if ($email_config['encryption'] === 'ssl') {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            } else {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            }

            $mail->Port = $email_config['port'];
            $mail->CharSet = 'UTF-8';

            // ConfiguraÃ§Ãµes do remetente e destinatÃ¡rio
            $mail->setFrom($email_config['from_email'], $email_config['from_name']);
            $mail->addAddress($data['email'], $data['nome']);

            // Gerar e anexar PDF
            try {
                $pdf_content = gerarPDFComoString($orcamento_id, $conn);
                $mail->addStringAttachment($pdf_content, 'Orcamento_'.$orcamento_id.'.pdf', 'base64', 'application/pdf');
            } catch (Exception $pdf_error) {
                error_log("Erro ao gerar PDF para orÃ§amento #$orcamento_id: " . $pdf_error->getMessage());
                throw new Exception("Erro ao gerar PDF do orÃ§amento: " . $pdf_error->getMessage());
            }

            // Configurar conteÃºdo do email
            $mail->isHTML(true);
            $mail->Subject = 'Seu OrÃ§amento NÂº ' . $orcamento_id . ' - LFM Tecnologia';

            $mail->Body = "
            <html>
            <head>
                <meta charset='UTF-8'>
                <style>
                    body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                    .header { background-color: #f8f9fa; padding: 20px; text-align: center; }
                    .content { padding: 20px; }
                    .footer { background-color: #f8f9fa; padding: 15px; text-align: center; font-size: 12px; }
                </style>
            </head>
            <body>
                <div class='header'>
                    <h2>LFM Tecnologia</h2>
                </div>
                <div class='content'>
                    <p>OlÃ¡, <strong>" . htmlspecialchars($data['nome']) . "</strong>!</p>

                    <p>Esperamos que esteja bem!</p>

                    <p>Segue em anexo o orÃ§amento solicitado (NÂº <strong>$orcamento_id</strong>).</p>

                    <p>Caso tenha alguma dÃºvida ou precise de esclarecimentos, nÃ£o hesite em entrar em contato conosco.</p>

                    <p>Aguardamos seu retorno!</p>

                    <br>
                    <p>Atenciosamente,<br>
                    <strong>Karla Wollinge</strong><br>
                    LFM Tecnologia</p>
                </div>
                <div class='footer'>
                    <p>Este Ã© um e-mail automÃ¡tico. Por favor, nÃ£o responda diretamente a este e-mail.</p>
                    <p>Para entrar em contato, utilize: desenvolvimento@lfmtecnologia.com</p>
                </div>
            </body>
            </html>";

            $mail->AltBody = "OlÃ¡, " . htmlspecialchars($data['nome']) . "!\n\n" .
                           "Segue em anexo o orÃ§amento solicitado (NÂº $orcamento_id).\n\n" .
                           "Caso tenha alguma dÃºvida, entre em contato conosco.\n\n" .
                           "Atenciosamente,\nKarla Wollinge\nLFM Tecnologia";

            // Enviar email
            if ($mail->send()) {
                // Log de sucesso
                error_log("EMAIL ENVIADO COM SUCESSO - OrÃ§amento #$orcamento_id para {$data['email']} em " . date('Y-m-d H:i:s'));

                // Registrar no histÃ³rico
                registrarHistoricoOrcamento($conn, $orcamento_id, $data['status_orcamento'], $data['status_orcamento'], "OrÃ§amento enviado por e-mail para {$data['email']} em " . date('d/m/Y H:i:s'));

                $_SESSION['message'] = "OrÃ§amento enviado com sucesso para " . htmlspecialchars($data['email']) . "!";
                $_SESSION['message_type'] = 'success';
            } else {
                throw new Exception("Falha no envio do email");
            }

        } catch (Exception $e) {
            // Log detalhado do erro
            error_log("ERRO NO ENVIO DE EMAIL - OrÃ§amento #$orcamento_id: " . $e->getMessage());
            error_log("Dados do cliente: " . print_r($data, true));
            error_log("ConfiguraÃ§Ãµes de email: Host=" . ($email_config['host'] ?? 'N/A') . ", Username=" . ($email_config['username'] ?? 'N/A') . ", Port=" . ($email_config['port'] ?? 'N/A'));

            $_SESSION['message'] = "Erro ao enviar e-mail: " . htmlspecialchars($e->getMessage());
            $_SESSION['message_type'] = 'danger';
        }
    } else {
        if (!$data) {
            $_SESSION['message'] = "OrÃ§amento nÃ£o encontrado.";
        } else {
            $_SESSION['message'] = "Cliente nÃ£o possui e-mail cadastrado. Por favor, atualize o cadastro do cliente.";
        }
        $_SESSION['message_type'] = 'warning';
    }

    if (ob_get_length()) ob_end_clean();
    header("Location: orcamentos.php");
    exit;
}

// ===================================================================================
// 4. BLOCO DE BUSCA DE DADOS E MENSAGENS FLASH
// ===================================================================================

// ConfiguraÃ§Ãµes de paginaÃ§Ã£o
$itens_por_pagina_orc = 10; // Nome da variÃ¡vel alterado para evitar conflito
$pagina_atual_orc = isset($_GET['pagina_orc']) ? (int)$_GET['pagina_orc'] : 1;
if ($pagina_atual_orc < 1) $pagina_atual_orc = 1;
$offset_orc = ($pagina_atual_orc - 1) * $itens_por_pagina_orc;

// Contar total de orÃ§amentos (todos, para estatÃ­sticas e total de pÃ¡ginas)
$sql_total_orcamentos_db = "SELECT COUNT(*) as total FROM orcamentos";
$result_total_orcamentos_db = $conn->query($sql_total_orcamentos_db);
$total_orcamentos_db = $result_total_orcamentos_db->fetch_assoc()['total'];

// Contar orÃ§amentos para exibir na lista (excluindo 'convertido_venda')
$sql_total_orcamentos_para_exibir_db = "SELECT COUNT(*) as total FROM orcamentos WHERE status_orcamento != 'convertido_venda'";
$result_total_orcamentos_para_exibir_db = $conn->query($sql_total_orcamentos_para_exibir_db);
$total_orcamentos_para_exibir_db = $result_total_orcamentos_para_exibir_db->fetch_assoc()['total'];
$total_paginas_orc = ceil($total_orcamentos_para_exibir_db / $itens_por_pagina_orc);


// Busca os orÃ§amentos para a pÃ¡gina atual (excluindo 'convertido_venda')
$sql_select_orcamentos = "SELECT o.id, c.nome AS nome_cliente, o.data_orcamento, o.valor_total, o.status_orcamento
                          FROM orcamentos o LEFT JOIN clientes c ON o.cliente_id = c.id
                          WHERE o.status_orcamento != 'convertido_venda'
                          ORDER BY o.id DESC
                          LIMIT ? OFFSET ?";
$stmt_orcamentos = $conn->prepare($sql_select_orcamentos);
$stmt_orcamentos->bind_param("ii", $itens_por_pagina_orc, $offset_orc);
$stmt_orcamentos->execute();
$result_orcamentos_paginados = $stmt_orcamentos->get_result();
if (!$result_orcamentos_paginados) die("Erro na consulta de orÃ§amentos paginados: " . $conn->error);
$orcamentos_para_exibir = $result_orcamentos_paginados->fetch_all(MYSQLI_ASSOC);


// EstatÃ­sticas (calculadas sobre todos os orÃ§amentos)
$sql_all_orcamentos_stats = "SELECT status_orcamento FROM orcamentos";
$result_all_orcamentos_stats = $conn->query($sql_all_orcamentos_stats);
$stats = ['pendentes' => 0, 'aprovados' => 0, 'rejeitados' => 0, 'convertidos' => 0];
$total_orcamentos_stats = 0; // Para o card "Total"
if ($result_all_orcamentos_stats) {
    while($row_stat = $result_all_orcamentos_stats->fetch_assoc()) {
        $total_orcamentos_stats++;
        if ($row_stat['status_orcamento'] == 'pendente') $stats['pendentes']++;
        if ($row_stat['status_orcamento'] == 'aprovado') $stats['aprovados']++;
        if ($row_stat['status_orcamento'] == 'rejeitado') $stats['rejeitados']++;
        if ($row_stat['status_orcamento'] == 'convertido_venda') $stats['convertidos']++;
    }
}

// ===================================================================================
// 5. RENDERIZAÃ‡ÃƒO DA PÃGINA HTML
// ===================================================================================

include_once 'includes/header.php';
?>

<div class="page-header fade-in-up">
    <h1 class="page-title"><i class="fas fa-file-invoice-dollar"></i> Gerenciamento de OrÃ§amentos</h1>
</div>

<?php 
if (isset($_SESSION['message'])) {
    echo '<div class="alert alert-' . $_SESSION['message_type'] . ' alert-dismissible fade show fade-in-up" role="alert">';
    echo '<i class="fas fa-' . ($_SESSION['message_type'] == 'success' ? 'check-circle' : 'exclamation-triangle') . ' me-2"></i>';
    echo $_SESSION['message'];
    echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
    echo '</div>';
    unset($_SESSION['message']);
    unset($_SESSION['message_type']);
}

// Exibir mensagem de erro de conversÃ£o se houver
if (isset($erro_conversao)) {
    echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">';
    echo '<i class="fas fa-exclamation-triangle me-2"></i>' . htmlspecialchars($erro_conversao);
    echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
    echo '</div>';
}
?>

<div class="row g-4 mb-4">
    <div class="col-6 col-lg-3"><div class="stats-card primary"><div class="stats-icon primary"><i class="fas fa-file-invoice-dollar"></i></div><div class="stats-value"><?php echo $total_orcamentos_stats; ?></div><div class="stats-label">Total</div></div></div>
    <div class="col-6 col-lg-3"><div class="stats-card warning"><div class="stats-icon warning"><i class="fas fa-clock"></i></div><div class="stats-value"><?php echo $stats['pendentes']; ?></div><div class="stats-label">Pendentes</div></div></div>
    <div class="col-6 col-lg-3"><div class="stats-card success"><div class="stats-icon success"><i class="fas fa-check-circle"></i></div><div class="stats-value"><?php echo $stats['aprovados']; ?></div><div class="stats-label">Aprovados</div></div></div>
    <div class="col-6 col-lg-3"><div class="stats-card info"><div class="stats-icon info"><i class="fas fa-exchange-alt"></i></div><div class="stats-value"><?php echo $stats['convertidos']; ?></div><div class="stats-label">Convertidos</div></div></div>
</div>

<div class="modern-card">
    <div class="card-header-modern d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div class="d-flex align-items-center">
            <span><i class="fas fa-list"></i> Lista de OrÃ§amentos (PÃ¡gina <?php echo $pagina_atual_orc; ?> de <?php echo $total_paginas_orc; ?>)</span>
            <div class="ms-auto">
                <span class="badge bg-primary"><?php echo count($orcamentos_para_exibir); ?> orÃ§amentos</span>
            </div>
        </div>
        <div class="d-flex gap-2 flex-grow-1 flex-md-grow-0">
            <div class="input-group" style="max-width: 300px;">
                <input type="text" class="form-control" placeholder="Buscar orÃ§amentos..." id="searchInput">
                <button class="btn btn-outline-primary" type="button">
                    <i class="fas fa-search"></i>
                </button>
            </div>
        </div>
        <a href="criar_orcamento.php" class="btn btn-primary btn-sm"><i class="fas fa-plus me-2"></i> Novo OrÃ§amento</a>
    </div>
    <div class="card-body-modern">
        <!-- Tabela para Desktop e Tablet -->
        <div class="table-responsive-custom">
            <div class="table-modern">
                <table class="table table-hover table-striped" id="orcamentosTable">
                    <thead>
                        <tr>
                            <th>ID</th><th>Cliente</th><th>Data</th><th>Valor</th><th>Status</th><th class="text-center">AÃ§Ãµes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($orcamentos_para_exibir) > 0): ?>
                            <?php foreach ($orcamentos_para_exibir as $row): ?>
                                <?php
                                $status_class = '';
                                switch ($row['status_orcamento']) {
                                    case 'aprovado': $status_class = 'bg-success'; break;
                                    case 'pendente': $status_class = 'bg-warning text-dark'; break;
                                    case 'rejeitado': $status_class = 'bg-danger'; break;
                                    default: $status_class = 'bg-secondary';
                                }
                                ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['id']); ?></td>
                                    <td><?php echo htmlspecialchars($row['nome_cliente']); ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($row['data_orcamento'])); ?></td>
                                    <td>R$ <?php echo number_format($row['valor_total'], 2, ',', '.'); ?></td>
                                    <td><span class="badge <?php echo $status_class; ?>"><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $row['status_orcamento']))); ?></span></td>
                                    <td class="text-center">
                                        <div class="btn-group">
                                            <a href="detalhes_orcamento.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-info" title="Ver Detalhes"><i class="fas fa-eye"></i></a>
                                            <a href="gerar_pdf_orcamento.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-danger" title="Baixar PDF" target="_blank"><i class="fas fa-file-pdf"></i></a>
                                            <a href="orcamentos.php?action=send_email&id=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-secondary" title="Enviar por E-mail"><i class="fas fa-envelope"></i></a>

                                            <?php if ($row['status_orcamento'] === 'aprovado'): ?>
                                                <button type="button" class="btn btn-sm btn-success" title="Converter em Venda"
                                                        data-bs-toggle="modal" data-bs-target="#convertModal"
                                                        data-id="<?php echo $row['id']; ?>"
                                                        data-cliente="<?php echo htmlspecialchars($row['nome_cliente']); ?>"
                                                        data-valor="<?php echo number_format($row['valor_total'], 2, ',', '.'); ?>">
                                                    <i class="fas fa-shopping-cart"></i>
                                                </button>
                                            <?php endif; ?>

                                            <button type="button" class="btn btn-sm btn-outline-warning" title="Mudar Status" data-bs-toggle="modal" data-bs-target="#statusModal" data-id="<?php echo $row['id']; ?>" data-status="<?php echo $row['status_orcamento']; ?>">
                                                <i class="fas fa-sync-alt"></i>
                                            </button>

                                            <a href="orcamentos.php?action=delete&id=<?php echo $row['id']; ?>" class="btn btn-sm btn-danger" title="Excluir OrÃ§amento" onclick="return confirm('Tem certeza que deseja excluir este orÃ§amento e todos os seus itens? Esta aÃ§Ã£o nÃ£o pode ser desfeita.');">
                                                <i class="fas fa-trash-alt"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="6" class="text-center">Nenhum orÃ§amento para exibir (orÃ§amentos jÃ¡ convertidos em venda nÃ£o sÃ£o listados aqui).</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Cards para Mobile -->
        <div class="mobile-orcamentos-container">
            <?php if (count($orcamentos_para_exibir) > 0): ?>
                <?php foreach ($orcamentos_para_exibir as $row): ?>
                    <?php
                    $status_class = '';
                    $status_text = '';
                    switch ($row['status_orcamento']) {
                        case 'aprovado':
                            $status_class = 'bg-success';
                            $status_text = 'Aprovado';
                            break;
                        case 'pendente':
                            $status_class = 'bg-warning text-dark';
                            $status_text = 'Pendente';
                            break;
                        case 'rejeitado':
                            $status_class = 'bg-danger';
                            $status_text = 'Rejeitado';
                            break;
                        default:
                            $status_class = 'bg-secondary';
                            $status_text = ucfirst(str_replace('_', ' ', $row['status_orcamento']));
                    }
                    ?>
                    <div class="mobile-orcamento-item mobile-orcamento-card">
                        <div class="mobile-orcamento-header">
                            <div class="mobile-orcamento-id">OrÃ§amento #<?php echo htmlspecialchars($row['id']); ?></div>
                            <span class="badge <?php echo $status_class; ?> mobile-orcamento-status">
                                <?php echo $status_text; ?>
                            </span>
                        </div>

                        <div class="mobile-orcamento-info">
                            <div class="mobile-orcamento-info-item">
                                <span class="mobile-orcamento-info-label">Cliente:</span>
                                <span class="mobile-orcamento-info-value"><?php echo htmlspecialchars($row['nome_cliente']); ?></span>
                            </div>
                            <div class="mobile-orcamento-info-item">
                                <span class="mobile-orcamento-info-label">Data:</span>
                                <span class="mobile-orcamento-info-value"><?php echo date('d/m/Y', strtotime($row['data_orcamento'])); ?></span>
                            </div>
                            <div class="mobile-orcamento-info-item">
                                <span class="mobile-orcamento-info-label">Valor Total:</span>
                                <span class="mobile-orcamento-info-value text-success fw-bold">R$ <?php echo number_format($row['valor_total'], 2, ',', '.'); ?></span>
                            </div>
                        </div>

                        <div class="mobile-orcamento-actions">
                            <a href="detalhes_orcamento.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-info" title="Ver Detalhes">
                                <i class="fas fa-eye me-1"></i> Detalhes
                            </a>
                            <a href="gerar_pdf_orcamento.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-danger" title="Baixar PDF" target="_blank">
                                <i class="fas fa-file-pdf me-1"></i> PDF
                            </a>
                            <a href="orcamentos.php?action=send_email&id=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-secondary" title="Enviar por E-mail">
                                <i class="fas fa-envelope me-1"></i> E-mail
                            </a>
                            <?php if ($row['status_orcamento'] === 'aprovado'): ?>
                                <button type="button" class="btn btn-sm btn-success" title="Converter em Venda"
                                        data-bs-toggle="modal" data-bs-target="#convertModal"
                                        data-id="<?php echo $row['id']; ?>"
                                        data-cliente="<?php echo htmlspecialchars($row['nome_cliente']); ?>"
                                        data-valor="<?php echo number_format($row['valor_total'], 2, ',', '.'); ?>">
                                    <i class="fas fa-shopping-cart me-1"></i> Converter
                                </button>
                            <?php endif; ?>
                            <button type="button" class="btn btn-sm btn-outline-warning" title="Mudar Status" data-bs-toggle="modal" data-bs-target="#statusModal" data-id="<?php echo $row['id']; ?>" data-status="<?php echo $row['status_orcamento']; ?>">
                                <i class="fas fa-sync-alt me-1"></i> Status
                            </button>
                            <a href="orcamentos.php?action=delete&id=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-danger" title="Excluir OrÃ§amento" onclick="return confirm('Tem certeza que deseja excluir este orÃ§amento e todos os seus itens? Esta aÃ§Ã£o nÃ£o pode ser desfeita.');">
                                <i class="fas fa-trash-alt me-1"></i> Excluir
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="text-center py-5">
                    <div class="stats-icon primary mx-auto mb-3">
                        <i class="fas fa-file-invoice-dollar"></i>
                    </div>
                    <h5 class="text-muted mb-2">Nenhum orÃ§amento encontrado</h5>
                    <p class="text-muted">OrÃ§amentos jÃ¡ convertidos em venda nÃ£o sÃ£o listados aqui.</p>
                    <a href="criar_orcamento.php" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i> Criar Primeiro OrÃ§amento
                    </a>
                </div>
            <?php endif; ?>
        </div>

        <!-- NavegaÃ§Ã£o da PaginaÃ§Ã£o para OrÃ§amentos -->
        <?php
        echo render_pagination([
            'current_page' => $pagina_atual_orc,
            'total_pages' => $total_paginas_orc,
            'page_param' => 'pagina_orc',
            'aria_label' => 'Paginacao de orcamentos',
            'summary' => $total_orcamentos_para_exibir_db . ' orcamentos listados',
            'window' => 5,
            'labels' => [
                'first' => '&laquo;&laquo;',
                'previous' => '&laquo;',
                'next' => '&raquo;',
                'last' => '&raquo;&raquo;'
            ]
        ]);
        ?>

    </div>
</div>

<div class="modal fade" id="statusModal" tabindex="-1" aria-labelledby="statusModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="orcamentos.php" method="POST">
                <div class="modal-header">
                    <h5 class="modal-title" id="statusModalLabel">Alterar Status do OrÃ§amento</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="change_status">
                    <input type="hidden" name="orcamento_id" id="modal_orcamento_id">
                    
                    <p>Selecione o novo status para o orÃ§amento <strong id="modal_orcamento_id_display">#</strong>.</p>
                    <div class="mb-3">
                        <label for="novo_status" class="form-label">Novo Status</label>
                        <select name="novo_status" id="modal_novo_status_orcamento" class="form-select" required>
                            <option value="pendente">Pendente</option>
                            <option value="aprovado">Aprovado (ConverterÃ¡ em Venda)</option>
                            <option value="rejeitado">Rejeitado</option>
                            <option value="cancelado">Cancelado</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="observacoes" class="form-label">ObservaÃ§Ãµes (Opcional)</label>
                        <textarea name="observacoes" id="modal_observacoes_orcamento" class="form-control" rows="3"></textarea>
                    </div>
                    <div class="alert alert-info small">
                        <strong>AtenÃ§Ã£o:</strong> Alterar o status para 'Aprovado' irÃ¡ automaticamente criar uma nova venda com os itens deste orÃ§amento e marcar este orÃ§amento como 'Convertido em Venda'.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                    <button type="submit" class="btn btn-primary">Salvar AlteraÃ§Ã£o</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal para Converter OrÃ§amento em Venda -->
<div class="modal fade" id="convertModal" tabindex="-1" aria-labelledby="convertModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="orcamentos.php" method="POST">
                <div class="modal-header">
                    <h5 class="modal-title" id="convertModalLabel">
                        <i class="fas fa-shopping-cart me-2"></i>Converter OrÃ§amento em Venda
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="convert_to_sale">
                    <input type="hidden" name="orcamento_id" id="convert_orcamento_id">

                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>AtenÃ§Ã£o:</strong> Esta aÃ§Ã£o irÃ¡ converter o orÃ§amento em uma venda e atualizar o estoque dos produtos.
                    </div>

                    <div class="mb-3">
                        <label class="form-label"><strong>OrÃ§amento:</strong></label>
                        <p class="mb-1">Cliente: <span id="convert_cliente_nome"></span></p>
                        <p class="mb-1">Valor Total: R$ <span id="convert_valor_total"></span></p>
                    </div>

                    <div class="mb-3">
                        <label for="forma_pagamento" class="form-label">Forma de Pagamento <span class="text-danger">*</span></label>
                        <select name="forma_pagamento" id="forma_pagamento" class="form-select" required>
                            <option value="">Selecione...</option>
                            <option value="dinheiro">Dinheiro</option>
                            <option value="cartao_credito">CartÃ£o de CrÃ©dito</option>
                            <option value="cartao_debito">CartÃ£o de DÃ©bito</option>
                            <option value="pix">PIX</option>
                            <option value="transferencia">TransferÃªncia BancÃ¡ria</option>
                            <option value="boleto">Boleto</option>
                            <option value="cheque">Cheque</option>
                        </select>
                    </div>

                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>ObservaÃ§Ã£o:</strong> As observaÃ§Ãµes do orÃ§amento original serÃ£o mantidas no histÃ³rico.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-2"></i>Cancelar
                    </button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-shopping-cart me-2"></i>Converter em Venda
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
include_once 'includes/footer.php';
if (ob_get_length()) ob_end_flush(); // Libera o buffer de saÃ­da
?>

<script src="js/search-utils.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Inicializar busca para orÃ§amentos
    SearchUtils.initializeSearch({
        inputId: 'searchInput',
        tableId: 'orcamentosTable',
        mobileCardsSelector: '.mobile-orcamento-card',
        searchColumns: [0, 1, 2, 4] // ID, Cliente, Data, Status
    });

    const statusModal = document.getElementById('statusModal');
    if (statusModal) {
        statusModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            const orcamentoId = button.getAttribute('data-id');
            const currentStatus = button.getAttribute('data-status'); // Pega o status atual do botÃ£o

            const modalOrcamentoIdInput = statusModal.querySelector('#modal_orcamento_id');
            const modalOrcamentoIdDisplay = statusModal.querySelector('#modal_orcamento_id_display');
            const modalStatusSelect = statusModal.querySelector('#modal_novo_status_orcamento');
            const modalObservacoes = statusModal.querySelector('#modal_observacoes_orcamento');

            modalOrcamentoIdInput.value = orcamentoId;
            modalOrcamentoIdDisplay.textContent = '#' + orcamentoId;
            modalObservacoes.value = ''; // Limpa observaÃ§Ãµes anteriores

            // Define o valor selecionado no select para o status atual
            if (currentStatus) {
                modalStatusSelect.value = currentStatus;
            }
        });
    }

    // Modal para converter orÃ§amento em venda
    const convertModal = document.getElementById('convertModal');
    if (convertModal) {
        convertModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            const orcamentoId = button.getAttribute('data-id');
            const clienteNome = button.getAttribute('data-cliente');
            const valorTotal = button.getAttribute('data-valor');

            const modalOrcamentoIdInput = convertModal.querySelector('#convert_orcamento_id');
            const modalClienteNome = convertModal.querySelector('#convert_cliente_nome');
            const modalValorTotal = convertModal.querySelector('#convert_valor_total');
            const modalFormaPagamento = convertModal.querySelector('#forma_pagamento');

            modalOrcamentoIdInput.value = orcamentoId;
            modalClienteNome.textContent = clienteNome;
            modalValorTotal.textContent = valorTotal;
            modalFormaPagamento.value = ''; // Limpa seleÃ§Ã£o anterior
        });
    }
});
</script>
