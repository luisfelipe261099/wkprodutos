<?php
// Inicia o output buffering para evitar problemas de "headers already sent"
ob_start();
require_once 'includes/session_bootstrap.php';

// Verifica se o usuário está logado
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    if (ob_get_length()) ob_end_clean();
    header("location: index.php");
    exit;
}

require_once 'includes/db_connect.php';
require_once 'includes/PDFHelper.php';

// Verificar se foi passado o ID do orçamento
$orcamento_id = $_GET['id'] ?? 0;

if (!$orcamento_id) {
    die('ID do orçamento não fornecido.');
}

try {
    // Verificar se FPDF está disponível
    $fpdf_path = __DIR__ . '/vendor/setasign/fpdf/fpdf.php';
    if (!file_exists($fpdf_path)) {
        throw new Exception("FPDF library not found at: " . $fpdf_path . ". Please install FPDF using: composer install");
    }

    // Buscar dados do orçamento, incluindo CPF/CNPJ e tipo de pessoa do cliente
    $sql_orcamento = "SELECT o.*, c.nome as cliente_nome, c.email as cliente_email, c.telefone, c.inscricao_estadual,
                             c.endereco, c.numero, c.ponto_referencia, c.nome_fantasia, c.cidade, c.estado, c.cep, c.cpf_cnpj, c.tipo_pessoa
                      FROM orcamentos o
                      LEFT JOIN clientes c ON o.cliente_id = c.id
                      WHERE o.id = ?";
    $stmt_orcamento = $conn->prepare($sql_orcamento);
    if (!$stmt_orcamento) {
        throw new Exception("Database error: " . $conn->error);
    }
    
    $stmt_orcamento->bind_param("i", $orcamento_id);
    if (!$stmt_orcamento->execute()) {
        throw new Exception("Database execution error: " . $stmt_orcamento->error);
    }
    
    $result_orcamento = $stmt_orcamento->get_result();
    if ($result_orcamento->num_rows === 0) {
        throw new Exception('Orçamento #' . $orcamento_id . ' não encontrado.');
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
    if (!$stmt_itens) {
        throw new Exception("Database error (itens): " . $conn->error);
    }
    
    $stmt_itens->bind_param("i", $orcamento_id);
    if (!$stmt_itens->execute()) {
        throw new Exception("Database execution error (itens): " . $stmt_itens->error);
    }
    
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

    // Gerar o PDF
    gerarPDFModerno($orcamento, $itens, $empresas_logos);
    
} catch (Exception $e) {
    error_log("Erro ao gerar PDF do orçamento #$orcamento_id: " . $e->getMessage());
    header('Content-Type: text/html; charset=utf-8');
    echo '<div style="color:#721c24; background-color:#f8d7da; border:1px solid #f5c6cb; border-radius:5px; font-family:Arial,sans-serif; padding:20px; margin:20px; box-shadow:0 0 10px rgba(0,0,0,0.1);">';
    echo '<h1 style="color:#721c24; margin-top:0;">Erro ao gerar o PDF</h1>';
    echo '<p>Desculpe, ocorreu um erro ao tentar gerar o PDF do orçamento.</p>';
    echo '<p>Detalhes técnicos: ' . htmlspecialchars($e->getMessage()) . '</p>';
    echo '<p><a href="orcamentos.php" style="background-color:#0275d8; color:white; padding:10px 15px; text-decoration:none; border-radius:3px;">Voltar para a lista de orçamentos</a></p>';
    echo '</div>';
    exit;
}

function gerarPDFModerno($orcamento, $itens, $empresas_logos = []) {
    try {
        require_once __DIR__ . '/vendor/setasign/fpdf/fpdf.php';

        class ModernPDF extends FPDF {
            private $primaryColor = [28, 79, 140];
            private $accentColor = [13, 110, 253];
            private $lightGray = [248, 249, 250];
            private $darkGray = [52, 58, 64];
            private $successColor = [25, 135, 84];
            private $tableFontSize = 8;
            
            function setTableFontSize($size) {
                $this->tableFontSize = $size;
            }
            
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
                    $this->Image('logo.jpeg', 15, 5, 15, 0); // Reduzido de 20 para 15
                    $logo_width = 20; // Reduzido de 25 para 20
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
                $this->Cell(90, 3, $this->convertToLatin1('karla wollinger - Todos os direitos reservados'), 0, 0, 'L');
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
            
            function ModernTable($headers, $data, $widths) {
                $start_x = 15;
                $available_width = 180; // Largura total disponível

                $this->SetFillColor($this->primaryColor[0], $this->primaryColor[1], $this->primaryColor[2]);
                $this->SetTextColor(255, 255, 255);
                $this->SetFont('Arial', 'B', 6); // Reduzido de 7 para 6
                $this->SetDrawColor(255, 255, 255);

                // Desenha o cabeçalho da tabela
                $current_x = $start_x;
                for ($i = 0; $i < count($headers); $i++) {
                    $this->SetXY($current_x, $this->GetY());
                    $this->Cell($widths[$i], 5, $this->convertToLatin1($headers[$i]), 1, 0, 'C', true); // Reduzido de 7 para 5
                    $current_x += $widths[$i];
                }
                $this->Ln();

                $this->SetTextColor(0, 0, 0);
                $this->SetFont('Arial', '', 6); // Sempre usa fonte 6 para compactar
                $this->SetDrawColor(220, 220, 220);
                $fill = false;

                foreach ($data as $row) {
                    // Calcula a altura necessária para a linha (COMPACTO)
                    $max_cell_height = 4; // Reduzido de 6 para 4

                    // Verifica se a coluna de produto tem quebras de linha
                    if (isset($row[1]) && strpos($row[1], "\n") !== false) {
                        $lines = substr_count($row[1], "\n") + 1;
                        $max_cell_height = max(4, $lines * 2.5); // Reduzido
                    }

                    // Verifica se a descrição é muito longa e precisa quebrar
                    if (isset($row[1])) {
                        $text_width = $this->GetStringWidth($this->convertToLatin1($row[1]));
                        if ($text_width > $widths[1] - 4) {
                            $estimated_lines = ceil($text_width / ($widths[1] - 4));
                            $max_cell_height = max($max_cell_height, $estimated_lines * 2.5); // Reduzido
                        }
                    }

                    // Limita a altura máxima da célula para evitar células muito altas
                    $max_cell_height = min($max_cell_height, 8); // Reduzido de 12 para 8

                    // NÃO quebra página - força tudo em 1 página
                    // Removido o AddPage automático

                    
                    // Alterna a cor de fundo das linhas
                    if ($fill) {
                        $this->SetFillColor($this->lightGray[0], $this->lightGray[1], $this->lightGray[2]);
                    } else {
                        $this->SetFillColor(255, 255, 255);
                    }
                    
                    $start_y = $this->GetY();
                    $current_x = $start_x;
                    
                    // Desenha cada célula da linha
                    for ($i = 0; $i < count($row); $i++) {
                        $this->SetXY($current_x, $start_y);
                        $align = ($i == 0 || $i == 3) ? 'C' : (($i >= 4) ? 'R' : 'L');
                        
                        if ($i == 1) {
                            // Coluna de produto - usa MultiCell para quebrar texto longo
                            $this->MultiCell($widths[$i], 3, $this->convertToLatin1($row[$i]), 1, $align, true); // Reduzido de 4 para 3
                        } else {
                            // Outras colunas - usa Cell normal
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

        PDFHelper::startPdfOutput('Proposta_Comercial_' . str_pad($orcamento['id'], 6, '0', STR_PAD_LEFT) . '.pdf');

        $pdf = new ModernPDF('P', 'mm', 'A4');
        $pdf->SetAutoPageBreak(false); // Desabilita quebra automática de página
        $pdf->AddPage(); // Apenas 1 página

        // Ajusta o tamanho da fonte baseado no número de itens
        $num_itens = count($itens);
        if ($num_itens > 10) {
            $pdf->setTableFontSize(6);
        } else {
            $pdf->setTableFontSize(7);
        }
        
        $pdf->SetFont('Arial', 'B', 14); // Reduzido de 16 para 14
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
            $pdf->Cell(140, 6, $pdf->convertToLatin1('PROPOSTA COMERCIAL'), 0, 0, 'L'); // Reduzido de 8 para 6
            $pdf->Image($logo_empresa, 165, $pdf->GetY() - 2, 15, 0);
            $pdf->Ln(6); // Reduzido de 8 para 6
        } else {
            $pdf->Cell(0, 6, $pdf->convertToLatin1('PROPOSTA COMERCIAL'), 0, 1, 'C'); // Reduzido de 8 para 6
        }

        $pdf->SetFont('Arial', '', 9); // Reduzido de 10 para 9
        $pdf->SetTextColor(100, 100, 100);
        $pdf->Cell(0, 4, $pdf->convertToLatin1('Pedido #') . str_pad($orcamento['id'], 6, '0', STR_PAD_LEFT), 0, 1, 'C'); // Reduzido de 5 para 4

        $pdf->SetDrawColor(28, 79, 140);
        $pdf->SetLineWidth(0.5);
        $pdf->Line(80, $pdf->GetY() + 1, 130, $pdf->GetY() + 1);
        $pdf->Ln(5); // Reduzido de 8 para 5

        $currentY = $pdf->GetY();
        
        // --- INÍCIO DA MODIFICAÇÃO ---

        // Dados do cliente - versão compacta e organizada
        $cliente_nome = $orcamento['cliente_nome'] ?? 'Cliente não especificado';
        $client_details = "Cliente: " . $cliente_nome . "\n";

        // CPF/CNPJ na mesma linha do tipo de pessoa
        if (!empty($orcamento['cpf_cnpj'])) {
            $label = ($orcamento['tipo_pessoa'] === 'juridica') ? 'CNPJ' : 'CPF';
            $client_details .= $label . ": " . $orcamento['cpf_cnpj'];

            // Adiciona nome fantasia se for PJ
            if (!empty($orcamento['nome_fantasia']) && $orcamento['tipo_pessoa'] === 'juridica') {
                $client_details .= "\nNome Fantasia: " . $orcamento['nome_fantasia'];
            }
            $client_details .= "\n";
        }

        // Email e telefone na mesma linha se ambos existirem
        $contato_linha = "";
        if (!empty($orcamento['cliente_email'])) {
            $contato_linha .= "Email: " . $orcamento['cliente_email'];
        }
        if (!empty($orcamento['telefone'])) {
            if ($contato_linha) $contato_linha .= " | ";
            $contato_linha .= "Tel: " . $orcamento['telefone'];
        }
        if ($contato_linha) {
            $client_details .= $contato_linha . "\n";
        }

        // Endereço completo em formato compacto
        if (!empty($orcamento['endereco'])) {
            $endereco_completo = $orcamento['endereco'];
            if (!empty($orcamento['numero'])) {
                $endereco_completo .= ", " . $orcamento['numero'];
            }
            $client_details .= $endereco_completo . "\n";

            // Cidade, Estado e CEP na mesma linha
            if (!empty($orcamento['cidade'])) {
                $client_details .= $orcamento['cidade'] . '/' . $orcamento['estado'];
                if (!empty($orcamento['cep'])) {
                    $client_details .= " - CEP: " . $orcamento['cep'];
                }
            }
        }
        
        // Dados do orçamento - versão compacta
        $data_orcamento = isset($orcamento['data_orcamento']) ? date('d/m/Y', strtotime($orcamento['data_orcamento'])) : date('d/m/Y');
        $status = isset($orcamento['status_orcamento']) ? ucfirst($orcamento['status_orcamento']) : 'Pendente';

        // Formatar forma de pagamento
        $forma_pagamento_texto = 'A combinar';
        if (!empty($orcamento['forma_pagamento'])) {
            $formas_pagamento = [
                'pix' => 'PIX',
                'debito' => 'Debito',
                'credito' => 'Credito',
                'dinheiro' => 'Dinheiro',
                'faturamento' => 'Faturamento',
                'boleto' => 'Boleto'
            ];
            $forma_pagamento_texto = $formas_pagamento[$orcamento['forma_pagamento']] ?? ucfirst($orcamento['forma_pagamento']);
        }

        // Formatar tipo de faturamento
        $tipo_faturamento_texto = '';
        if (!empty($orcamento['tipo_faturamento'])) {
            $tipos_faturamento = [
                'avista' => 'A vista',
                '7' => '7 dias',
                '15' => '15 dias',
                '15_dias' => '15 dias',
                '20' => '20 dias',
                '20_dias' => '20 dias',
                '21' => '21 dias',
                '28' => '28 dias',
                '30' => '30 dias',
                '30_dias' => '30 dias',
                '45_dias' => '45 dias',
                '60_dias' => '60 dias',
                '90_dias' => '90 dias',
                '15_30' => '15/30 dias',
                '20_30' => '20/30 dias',
                '21_30' => '21/30 dias',
                '20_30_45' => '20/30/45 dias',
                '21_28_35_42_49_56' => '21/28/35/42/49/56 dias',
                '28_35' => '28/35 dias',
                '28_35_42' => '28/35/42 dias',
                '28_35_42_59' => '28/35/42/59 dias',
                '28_35_45' => '28/35/45 dias',
                '30_60_90' => '30/60/90 dias',
                '30_45_60' => '30/45/60 dias'
            ];
            $tipo_faturamento_texto = $tipos_faturamento[$orcamento['tipo_faturamento']] ?? str_replace('_', '/', $orcamento['tipo_faturamento']);
        }

        $budget_details = "Data: " . $data_orcamento . " | Status: " . $status . "\n";

        // Monta a linha de condições de pagamento
        $condicoes = "Pagamento: " . $forma_pagamento_texto;
        if (!empty($tipo_faturamento_texto)) {
            $condicoes .= " - " . $tipo_faturamento_texto;
        }
        $budget_details .= $condicoes . "\n";

        // Adiciona data de vencimento se existir
        if (!empty($orcamento['data_vencimento'])) {
            $data_venc = date('d/m/Y', strtotime($orcamento['data_vencimento']));
            $budget_details .= "Vencimento: " . $data_venc . "\n";
        }

        $budget_details .= "Validade: 30 dias";

        // Criar caixas de informação com altura ajustada dinamicamente (COMPACTO)
        // Calcula altura necessária baseada no conteúdo
        $client_lines = substr_count($client_details, "\n") + 1;
        $box_height = max(22, min(28, $client_lines * 3 + 8)); // Reduzido: Entre 22 e 28mm

        $pdf->ShadowBox(15, $currentY, 85, $box_height, 'DADOS DO CLIENTE', $client_details);
        $pdf->ShadowBox(110, $currentY, 85, $box_height, 'DADOS DO ORCAMENTO', $budget_details);

        $pdf->SetY($currentY + $box_height + 3); // Reduzido de 5 para 3

        // --- FIM DA MODIFICAÇÃO ---

        $pdf->SetFont('Arial', 'B', 10);
        $pdf->SetTextColor(28, 79, 140);
        $pdf->Cell(0, 5, $pdf->convertToLatin1('ITENS DA PROPOSTA'), 0, 1, 'L');
        $pdf->Ln(1);
        
        // Verifica o número de itens para ajustar a fonte se necessário
        $num_itens = count($itens);
        // Sempre usa fonte compacta para caber em 1 página
        $table_font_size = 6;
        $headers = ['#', 'Produto/Serviço', 'Empresa', 'Qtd', 'Vlr Unit.', 'Subtotal'];
        $widths = [8, 90, 30, 10, 22, 30]; // Aumentado coluna de produto para 90mm para não truncar nomes
        
        $table_data = [];
        $total = 0;
        $item_num = 1;
        
        foreach ($itens as $item) {
            $quantidade = $item['quantidade'] ?? 1;
            $preco_unitario = $item['preco_unitario'] ?? 0;
            $subtotal = $quantidade * $preco_unitario;
            $total += $subtotal;
            
            $produto_info = $item['produto_nome'] ?? 'Produto sem nome';
            
            $table_data[] = [
                $item_num,
                $produto_info,
                $item['nome_empresa'] ?? '',
                $quantidade,
                'R$ ' . number_format($preco_unitario, 2, ',', '.'),
                'R$ ' . number_format($subtotal, 2, ',', '.')
            ];
            $item_num++;
        }
        
        if (!empty($table_data)) {
            $pdf->ModernTable($headers, $table_data, $widths);
        }
        
        // Espaçamento reduzido para o total
        $pdf->Ln(3); // Reduzido de 10 para 3
        
        // --- SEÇÃO DE FORNECEDORES ---
        // Extrair fornecedores únicos dos itens
        $fornecedores_unicos = [];
        foreach ($itens as $item) {
            if (!empty($item['nome_empresa'])) {
                $fornecedores_unicos[$item['nome_empresa']] = $item['logo_empresa'] ?? '';
            }
        }
        
        if (!empty($fornecedores_unicos)) {
            $fornecedores_text = implode(", ", array_keys($fornecedores_unicos));
            $fornecedor_y = $pdf->GetY();
            
            // Se houver logo do fornecedor e for um único fornecedor, mostrar em fundo especial
            if (count($fornecedores_unicos) === 1) {
                $logo_path = reset($fornecedores_unicos);
                $fornecedor_nome = key($fornecedores_unicos);
                
                if (!empty($logo_path) && file_exists($logo_path)) {
                    // Caixa com logo
                    $pdf->SetFillColor(230, 240, 250);
                    $pdf->SetDrawColor(28, 79, 140);
                    $pdf->SetLineWidth(0.3);
                    $pdf->Rect(15, $fornecedor_y, 85, 15, 'DF');
                    
                    $pdf->SetFont('Arial', 'B', 8);
                    $pdf->SetTextColor(28, 79, 140);
                    $pdf->SetXY(17, $fornecedor_y + 1);
                    $pdf->Cell(40, 3, 'FORNECEDOR PRINCIPAL:', 0, 1);
                    
                    // Logo e nome lado a lado
                    $pdf->SetXY(17, $fornecedor_y + 5);
                    $pdf->Image($logo_path, 17, $fornecedor_y + 5, 8, 0);
                    
                    $pdf->SetXY(27, $fornecedor_y + 6);
                    $pdf->SetFont('Arial', 'B', 9);
                    $pdf->Cell(70, 4, $pdf->convertToLatin1(strtoupper($fornecedor_nome)), 0, 1);
                    
                    $pdf->Ln(17);
                } else {
                    // Caixa só com texto (sem logo)
                    $pdf->ShadowBox(15, $fornecedor_y, 85, 10, 'FORNECEDOR PRINCIPAL', $pdf->convertToLatin1(strtoupper($fornecedor_nome)), [230, 240, 250]);
                    $pdf->Ln(12);
                }
            } else {
                // Múltiplos fornecedores
                $pdf->ShadowBox(15, $fornecedor_y, 85, 10, 'FORNECEDOR(ES)', $fornecedores_text, [230, 240, 250]);
                $pdf->Ln(12);
            }
        }
        
        $total_y = $pdf->GetY();
        
        $valor_total = $orcamento['valor_total'] ?? $total;

        $pdf->SetFillColor(25, 135, 84);
        $pdf->Rect(115, $total_y, 80, 14, 'F'); // Reduzido de 18 para 14
        $pdf->SetDrawColor(25, 135, 84);
        $pdf->SetLineWidth(0.5);
        $pdf->Rect(115, $total_y, 80, 14, 'D');

        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFont('Arial', 'B', 7); // Reduzido de 8 para 7
        $pdf->SetXY(120, $total_y + 1);
        $pdf->Cell(70, 3, $pdf->convertToLatin1('VALOR TOTAL'), 0, 1, 'C');

        $pdf->SetFont('Arial', 'B', 11); // Reduzido de 12 para 11
        $pdf->SetXY(120, $total_y + 5);
        $pdf->Cell(70, 5, 'R$ ' . number_format($valor_total, 2, ',', '.'), 0, 1, 'C');

        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetY($total_y + 18); // Reduzido de 30 para 18

        if (!empty($orcamento['observacoes'])) {
            $pdf->Ln(1);
            $pdf->ShadowBox(15, $pdf->GetY(), 180, 14, 'OBSERVACOES', $orcamento['observacoes'], [255, 251, 235]); // Reduzido de 18 para 14
            $pdf->Ln(15); // Reduzido de 20 para 15
        }

        // Seção compacta de contato e assinatura
        $pdf->Ln(1);
        $contact_y = $pdf->GetY();

        $contact_info = "Equipe Comercial\n";
        $contact_info .= "Email: karlawollinger2@gmail.com\n";
        $contact_info .= "Tel: (41) 99859-3242";

        $signature_info = "Assinatura:\n";
        $signature_info .= "_________________\n";
        $signature_info .= "Data: ____/____/____";

        $pdf->ShadowBox(15, $contact_y, 85, 24, 'CONTATO', $contact_info); // Reduzido de 30 para 24
        $pdf->ShadowBox(110, $contact_y, 85, 24, 'ACEITE', $signature_info); // Reduzido de 30 para 24
        
        $pdf->SetDrawColor(0, 0, 0);
        $pdf->SetLineWidth(0.2);
        $pdf->SetTextColor(0, 0, 0);

        // Garante que só há 1 página
        $pdf->SetAutoPageBreak(false);

        $filename = 'Proposta_Comercial_' . str_pad($orcamento['id'], 6, '0', STR_PAD_LEFT) . '_' . date('Y-m-d') . '.pdf';

        // Limpa qualquer output buffer antes de enviar o PDF
        if (ob_get_length()) ob_end_clean();

        $pdf->Output($filename, 'I');
        
    } catch (Exception $e) {
        error_log("Erro ao gerar PDF moderno: " . $e->getMessage());
        
        echo '<div style="color:#721c24; background-color:#f8d7da; border:1px solid #f5c6cb; border-radius:5px; font-family:Arial,sans-serif; padding:20px; margin:20px; box-shadow:0 0 10px rgba(0,0,0,0.1);">';
        echo '<h2>Erro ao gerar o PDF</h2>';
        echo '<p>Ocorreu um erro ao tentar gerar o PDF do orçamento.</p>';
        echo '<p>Detalhes técnicos: ' . htmlspecialchars($e->getMessage()) . '</p>';
        echo '<p><a href="javascript:history.back()" style="background-color:#0275d8; color:white; padding:10px 15px; text-decoration:none; border-radius:3px;">Voltar</a></p>';
        echo '</div>';
    }
}

$conn->close();
?>

