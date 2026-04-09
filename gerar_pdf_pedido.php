<?php
ob_start();
require_once __DIR__ . '/includes/db_connect.php';
require_once __DIR__ . '/includes/PDFHelper.php';

// Verificar token e pedido
$token = $_GET['token'] ?? '';
$pedido_id = (int)($_GET['id'] ?? 0);

if (empty($token) || !$pedido_id) {
    die('Parâmetros inválidos.');
}

// Validar token
$sql_token = "SELECT ml.cliente_id, c.nome as cliente_nome, c.email as cliente_email, c.telefone,
                     c.endereco, c.numero, c.cidade, c.estado, c.cep, c.cpf_cnpj, c.tipo_pessoa,
                     c.nome_fantasia, c.inscricao_estadual
              FROM marketplace_links ml
              LEFT JOIN clientes c ON ml.cliente_id = c.id
              WHERE ml.token_acesso = ? AND ml.ativo = 1";
$stmt_token = $conn->prepare($sql_token);
$stmt_token->bind_param("s", $token);
$stmt_token->execute();
$result_token = $stmt_token->get_result();

if ($result_token->num_rows === 0) {
    die('Link inválido ou expirado.');
}

$cliente = $result_token->fetch_assoc();

// Buscar pedido (verificando que pertence ao cliente)
$sql_pedido = "SELECT mp.*
               FROM marketplace_pedidos mp
               WHERE mp.id = ? AND mp.cliente_id = ?";
$stmt_pedido = $conn->prepare($sql_pedido);
$stmt_pedido->bind_param("ii", $pedido_id, $cliente['cliente_id']);
$stmt_pedido->execute();
$result_pedido = $stmt_pedido->get_result();

if ($result_pedido->num_rows === 0) {
    die('Pedido não encontrado.');
}

$pedido = $result_pedido->fetch_assoc();

// Buscar itens do pedido com dados do produto e empresa
$sql_itens = "SELECT mip.*, p.nome as produto_nome, p.sku as codigo, p.descricao,
                     e.nome_empresa, e.logo_empresa
              FROM marketplace_itens_pedido mip
              LEFT JOIN produtos p ON mip.produto_id = p.id
              LEFT JOIN empresas_representadas e ON p.empresa_id = e.id
              WHERE mip.pedido_id = ?
              ORDER BY mip.id ASC";
$stmt_itens = $conn->prepare($sql_itens);
$stmt_itens->bind_param("i", $pedido_id);
$stmt_itens->execute();
$result_itens = $stmt_itens->get_result();

$itens = [];
$empresas_logos = [];
while ($item = $result_itens->fetch_assoc()) {
    $item['produto_nome'] = $item['produto_nome'] ?? 'Produto sem nome';
    $item['nome_empresa'] = $item['nome_empresa'] ?? '';
    $item['logo_empresa'] = $item['logo_empresa'] ?? '';
    if (!empty($item['logo_empresa']) && !in_array($item['logo_empresa'], $empresas_logos)) {
        $empresas_logos[] = $item['logo_empresa'];
    }
    $itens[] = $item;
}

// Gerar PDF
try {
    $fpdf_path = __DIR__ . '/vendor/setasign/fpdf/fpdf.php';
    if (!file_exists($fpdf_path)) {
        throw new Exception("Biblioteca FPDF não encontrada.");
    }

    require_once $fpdf_path;

    class PedidoPDF extends FPDF {
        private $primaryColor = [28, 79, 140];
        private $accentColor = [13, 110, 253];
        private $lightGray = [248, 249, 250];
        private $darkGray = [52, 58, 64];

        function convertToLatin1($text) {
            return iconv('UTF-8', 'ISO-8859-1//IGNORE', $text);
        }

        function Header() {
            $this->SetFillColor($this->primaryColor[0], $this->primaryColor[1], $this->primaryColor[2]);
            $this->Rect(0, 0, 210, 30, 'F');
            $this->SetFillColor($this->accentColor[0], $this->accentColor[1], $this->accentColor[2]);
            $this->Rect(0, 28, 210, 3, 'F');

            $logo_width = 0;
            if (file_exists('logo.jpeg')) {
                $this->Image('logo.jpeg', 15, 5, 15, 0);
                $logo_width = 20;
            }

            $this->SetTextColor(255, 255, 255);
            $this->SetFont('Arial', 'B', 14);
            $this->SetXY(15 + $logo_width, 8);
            $this->Cell(0, 6, $this->convertToLatin1('Karla Wollinger'), 0, 1, 'L');

            $this->SetFont('Arial', '', 8);
            $this->SetXY(15 + $logo_width, 16);
            $this->Cell(0, 4, $this->convertToLatin1('Representação Comercial'), 0, 1, 'L');

            $this->SetFont('Arial', '', 7);
            $this->SetXY(120, 6);
            $this->Cell(0, 3, 'karlawollinger2@gmail.com', 0, 1, 'R');
            $this->SetXY(120, 11);
            $this->Cell(0, 3, '(41) 99859-3242', 0, 1, 'R');
            $this->SetXY(120, 16);
            $this->Cell(0, 3, 'CNPJ : 30.459.625/0001-87', 0, 1, 'R');

            $this->SetTextColor(0, 0, 0);
            $this->Ln(12);
        }

        function Footer() {
            $this->SetY(-15);
            $this->SetDrawColor($this->primaryColor[0], $this->primaryColor[1], $this->primaryColor[2]);
            $this->SetLineWidth(0.3);
            $this->Line(15, $this->GetY(), 195, $this->GetY());

            $this->Ln(2);
            $this->SetFont('Arial', '', 7);
            $this->SetTextColor($this->darkGray[0], $this->darkGray[1], $this->darkGray[2]);

            $this->SetX(15);
            $this->Cell(90, 3, $this->convertToLatin1('Karla Wollinger - Todos os direitos reservados'), 0, 0, 'L');
            $this->Cell(90, 3, $this->convertToLatin1('Documento gerado em: ') . date('d/m/Y H:i'), 0, 1, 'R');

            $this->SetDrawColor(0, 0, 0);
            $this->SetTextColor(0, 0, 0);
            $this->SetLineWidth(0.2);
        }

        function ShadowBox($x, $y, $w, $h, $title, $content, $bgColor = null) {
            $this->SetFillColor(200, 200, 200);
            $this->SetDrawColor(200, 200, 200);
            $this->Rect($x + 1, $y + 1, $w, $h, 'F');

            if ($bgColor) {
                $this->SetFillColor($bgColor[0], $bgColor[1], $bgColor[2]);
            } else {
                $this->SetFillColor(255, 255, 255);
            }
            $this->SetDrawColor(220, 220, 220);
            $this->Rect($x, $y, $w, $h, 'DF');

            $this->SetFont('Arial', 'B', 8);
            $this->SetTextColor($this->primaryColor[0], $this->primaryColor[1], $this->primaryColor[2]);
            $this->SetXY($x + 3, $y + 2);
            $this->Cell($w - 6, 5, $this->convertToLatin1($title), 0, 1, 'L');

            $this->SetDrawColor($this->primaryColor[0], $this->primaryColor[1], $this->primaryColor[2]);
            $this->Line($x + 3, $y + 6, $x + $w - 3, $y + 6);

            $this->SetFont('Arial', '', 7);
            $this->SetTextColor(0, 0, 0);
            $this->SetXY($x + 3, $y + 8);
            $this->MultiCell($w - 6, 3, $this->convertToLatin1($content), 0, 'L');

            $this->SetDrawColor(0, 0, 0);
        }

        function drawTableHeader($headers, $widths) {
            $start_x = 15;
            $this->SetFillColor($this->primaryColor[0], $this->primaryColor[1], $this->primaryColor[2]);
            $this->SetTextColor(255, 255, 255);
            $this->SetFont('Arial', 'B', 6);
            $this->SetDrawColor(255, 255, 255);
            $current_x = $start_x;
            for ($i = 0; $i < count($headers); $i++) {
                $this->SetXY($current_x, $this->GetY());
                $this->Cell($widths[$i], 5, $this->convertToLatin1($headers[$i]), 1, 0, 'C', true);
                $current_x += $widths[$i];
            }
            $this->Ln();
            $this->SetTextColor(0, 0, 0);
            $this->SetFont('Arial', '', 6);
            $this->SetDrawColor(220, 220, 220);
        }

        function ModernTable($headers, $data, $widths) {
            $start_x = 15;
            $this->drawTableHeader($headers, $widths);
            $fill = false;

            foreach ($data as $row) {
                $max_cell_height = 4;

                if (isset($row[1])) {
                    $this->SetFont('Arial', '', 6);
                    $text_width = $this->GetStringWidth($this->convertToLatin1($row[1]));
                    if ($text_width > $widths[1] - 2) {
                        $estimated_lines = ceil($text_width / ($widths[1] - 2));
                        $max_cell_height = max($max_cell_height, $estimated_lines * 3);
                    }
                }

                $max_cell_height = min($max_cell_height, 12);

                if ($this->GetY() + $max_cell_height > $this->GetPageHeight() - 20) {
                    $this->AddPage();
                    $this->drawTableHeader($headers, $widths);
                    $fill = false;
                }

                if ($fill) {
                    $this->SetFillColor($this->lightGray[0], $this->lightGray[1], $this->lightGray[2]);
                } else {
                    $this->SetFillColor(255, 255, 255);
                }

                $start_y = $this->GetY();
                $current_x = $start_x;

                for ($i = 0; $i < count($row); $i++) {
                    $this->SetXY($current_x, $start_y);
                    $align = ($i == 0 || $i == 3) ? 'C' : (($i >= 4) ? 'R' : 'L');

                    if ($i == 1) {
                        $this->MultiCell($widths[$i], 3, $this->convertToLatin1($row[$i]), 1, $align, true);
                        $after_y = $this->GetY();
                        $max_cell_height = max($max_cell_height, $after_y - $start_y);
                    } else {
                        $this->Cell($widths[$i], $max_cell_height, $this->convertToLatin1($row[$i]), 1, 0, $align, true);
                    }

                    $current_x += $widths[$i];
                }

                $this->SetY($start_y + $max_cell_height);
                $fill = !$fill;
            }

            $this->SetDrawColor(0, 0, 0);
            $this->SetFillColor(255, 255, 255);
        }
    }

    PDFHelper::startPdfOutput('Pedido_' . $pedido['numero_pedido'] . '.pdf');

    $pdf = new PedidoPDF('P', 'mm', 'A4');
    $pdf->SetAutoPageBreak(true, 15);
    $pdf->AddPage();

    // Título
    $pdf->SetFont('Arial', 'B', 14);
    $pdf->SetTextColor(28, 79, 140);

    $logo_empresa = null;
    if (!empty($empresas_logos)) {
        foreach ($empresas_logos as $logo_path) {
            if (file_exists($logo_path)) {
                $logo_empresa = $logo_path;
                break;
            }
        }
    }

    if ($logo_empresa) {
        $pdf->SetXY(15, $pdf->GetY());
        $pdf->Cell(140, 6, $pdf->convertToLatin1('PEDIDO #' . $pedido['numero_pedido']), 0, 0, 'L');
        $pdf->Image($logo_empresa, 165, $pdf->GetY() - 2, 15, 0);
        $pdf->Ln(6);
    } else {
        $pdf->Cell(0, 6, $pdf->convertToLatin1('PEDIDO #' . $pedido['numero_pedido']), 0, 1, 'C');
    }

    $pdf->SetFont('Arial', '', 9);
    $pdf->SetTextColor(100, 100, 100);
    $pdf->Cell(0, 4, $pdf->convertToLatin1('Marketplace - Karla Wollinger'), 0, 1, 'C');

    $pdf->SetDrawColor(28, 79, 140);
    $pdf->SetLineWidth(0.5);
    $pdf->Line(80, $pdf->GetY() + 1, 130, $pdf->GetY() + 1);
    $pdf->Ln(5);

    $currentY = $pdf->GetY();

    // Dados do cliente
    $client_details = "Cliente: " . ($cliente['cliente_nome'] ?? 'N/A') . "\n";
    if (!empty($cliente['cpf_cnpj'])) {
        $label = ($cliente['tipo_pessoa'] === 'juridica') ? 'CNPJ' : 'CPF';
        $client_details .= $label . ": " . $cliente['cpf_cnpj'];
        if (!empty($cliente['nome_fantasia']) && $cliente['tipo_pessoa'] === 'juridica') {
            $client_details .= "\nNome Fantasia: " . $cliente['nome_fantasia'];
        }
        $client_details .= "\n";
    }
    $contato = "";
    if (!empty($cliente['cliente_email'])) $contato .= "Email: " . $cliente['cliente_email'];
    if (!empty($cliente['telefone'])) {
        if ($contato) $contato .= " | ";
        $contato .= "Tel: " . $cliente['telefone'];
    }
    if ($contato) $client_details .= $contato . "\n";
    if (!empty($cliente['endereco'])) {
        $end = $cliente['endereco'];
        if (!empty($cliente['numero'])) $end .= ", " . $cliente['numero'];
        $client_details .= $end . "\n";
        if (!empty($cliente['cidade'])) {
            $client_details .= $cliente['cidade'] . '/' . $cliente['estado'];
            if (!empty($cliente['cep'])) $client_details .= " - CEP: " . $cliente['cep'];
        }
    }

    // Dados do pedido
    $data_pedido = date('d/m/Y H:i', strtotime($pedido['data_pedido']));
    $status_map = [
        'pendente' => 'Pendente',
        'confirmado' => 'Confirmado',
        'preparando' => 'Preparando',
        'entregue' => 'Entregue',
        'cancelado' => 'Cancelado'
    ];
    $status = $status_map[$pedido['status_pedido']] ?? ucfirst($pedido['status_pedido']);

    $tipo_fat_map = [
        'avista' => 'À Vista',
        '15_dias' => '15 dias',
        '20_dias' => '20 dias',
        '30_dias' => '30 dias'
    ];
    $tipo_fat = $tipo_fat_map[$pedido['tipo_faturamento']] ?? str_replace('_', ' ', $pedido['tipo_faturamento']);

    $pedido_details = "Data: " . $data_pedido . "\n";
    $pedido_details .= "Status: " . $status . "\n";
    $pedido_details .= "Faturamento: " . $tipo_fat . "\n";
    if (!empty($pedido['data_vencimento'])) {
        $pedido_details .= "Vencimento: " . date('d/m/Y', strtotime($pedido['data_vencimento'])) . "\n";
    }
    if (!empty($pedido['data_entrega_agendada'])) {
        $pedido_details .= "Entrega: " . date('d/m/Y', strtotime($pedido['data_entrega_agendada']));
    }

    $client_lines = substr_count($client_details, "\n") + 1;
    $box_height = max(22, min(30, $client_lines * 3 + 8));

    $pdf->ShadowBox(15, $currentY, 85, $box_height, 'DADOS DO CLIENTE', $client_details);
    $pdf->ShadowBox(110, $currentY, 85, $box_height, 'DADOS DO PEDIDO', $pedido_details);

    $pdf->SetY($currentY + $box_height + 3);

    // Endereço de entrega (se informado)
    if (!empty($pedido['endereco_entrega'])) {
        $pdf->SetFont('Arial', 'B', 8);
        $pdf->SetTextColor(28, 79, 140);
        $pdf->Cell(0, 5, $pdf->convertToLatin1('ENDEREÇO DE ENTREGA'), 0, 1, 'L');
        $pdf->SetFont('Arial', '', 8);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->Cell(0, 4, $pdf->convertToLatin1($pedido['endereco_entrega']), 0, 1, 'L');
        $pdf->Ln(2);
    }

    // Observações (se informadas)
    if (!empty($pedido['observacoes'])) {
        $pdf->SetFont('Arial', 'B', 8);
        $pdf->SetTextColor(28, 79, 140);
        $pdf->Cell(0, 5, $pdf->convertToLatin1('OBSERVAÇÕES'), 0, 1, 'L');
        $pdf->SetFont('Arial', '', 8);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->MultiCell(0, 4, $pdf->convertToLatin1($pedido['observacoes']), 0, 'L');
        $pdf->Ln(2);
    }

    // Tabela de itens
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->SetTextColor(28, 79, 140);
    $pdf->Cell(0, 5, $pdf->convertToLatin1('ITENS DO PEDIDO'), 0, 1, 'L');
    $pdf->Ln(1);

    $headers = ['#', 'Produto', 'Empresa', 'Qtd', 'Vlr Unit.', 'Subtotal'];
    $widths = [8, 90, 30, 10, 22, 30];

    $table_data = [];
    $total = 0;
    $item_num = 1;

    foreach ($itens as $item) {
        $quantidade = $item['quantidade'] ?? 1;
        $preco_unitario = $item['preco_unitario'] ?? 0;
        $subtotal = $item['subtotal'] ?? ($quantidade * $preco_unitario);
        $total += $subtotal;

        $table_data[] = [
            $item_num,
            $item['produto_nome'],
            $item['nome_empresa'],
            $quantidade,
            'R$ ' . number_format($preco_unitario, 2, ',', '.'),
            'R$ ' . number_format($subtotal, 2, ',', '.')
        ];
        $item_num++;
    }

    if (!empty($table_data)) {
        $pdf->ModernTable($headers, $table_data, $widths);
    }

    $pdf->Ln(3);

    // Total
    $pdf->SetFillColor(28, 79, 140);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->SetX(110);
    $pdf->Cell(50, 8, $pdf->convertToLatin1('TOTAL DO PEDIDO:'), 0, 0, 'R', true);
    $pdf->Cell(35, 8, 'R$ ' . number_format($pedido['valor_total'], 2, ',', '.'), 0, 1, 'C', true);

    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFillColor(255, 255, 255);

    $pdf->Ln(8);

    // Mensagem de agradecimento
    $pdf->SetFont('Arial', 'I', 9);
    $pdf->SetTextColor(100, 100, 100);
    $pdf->Cell(0, 5, $pdf->convertToLatin1('Obrigada pela preferência! Em caso de dúvidas, entre em contato.'), 0, 1, 'C');

    $pdf->Output('I', 'Pedido_' . $pedido['numero_pedido'] . '.pdf');

} catch (Exception $e) {
    error_log("Erro ao gerar PDF do pedido #$pedido_id: " . $e->getMessage());
    header('Content-Type: text/html; charset=utf-8');
    echo '<div style="color:#721c24; background-color:#f8d7da; border:1px solid #f5c6cb; border-radius:5px; font-family:Arial,sans-serif; padding:20px; margin:20px;">';
    echo '<h1 style="color:#721c24;">Erro ao gerar o PDF</h1>';
    echo '<p>' . htmlspecialchars($e->getMessage()) . '</p>';
    echo '<p><a href="marketplace.php?token=' . htmlspecialchars($token) . '">Voltar ao Marketplace</a></p>';
    echo '</div>';
}
?>
