<?php
require_once 'includes/session_bootstrap.php';

// Verifica se o usuário está logado
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: index.php");
    exit;
}

require_once 'includes/db_connect.php';
require_once 'includes/PDFHelper.php';

// Verificar se o ID da venda foi fornecido
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo '<div style="padding:20px; text-align:center;">';
    echo '<h2>Erro</h2>';
    echo '<p>ID da venda não fornecido ou inválido.</p>';
    echo '<p><a href="vendas.php" style="background-color:#0275d8; color:white; padding:10px 15px; text-decoration:none; border-radius:3px;">Voltar para a lista de vendas</a></p>';
    echo '</div>';
    exit;
}

$venda_id = intval($_GET['id']);

try {
    // Verificar se FPDF está disponível
    $fpdf_path = __DIR__ . '/vendor/setasign/fpdf/fpdf.php';
    if (!file_exists($fpdf_path)) {
        throw new Exception("FPDF library not found at: " . $fpdf_path . ". Please install FPDF using: composer install");
    }
    
    // Buscar dados da venda, incluindo CPF/CNPJ e tipo de pessoa do cliente
    $sql_venda = "SELECT v.*, c.nome as cliente_nome, c.email as cliente_email, c.telefone, c.inscricao_estadual,
                         c.endereco, c.numero, c.ponto_referencia, c.nome_fantasia, c.cidade, c.estado, c.cep, c.cpf_cnpj, c.tipo_pessoa
                  FROM vendas v
                  LEFT JOIN clientes c ON v.cliente_id = c.id
                  WHERE v.id = ?";
    $stmt_venda = $conn->prepare($sql_venda);
    if (!$stmt_venda) {
        throw new Exception("Database error: " . $conn->error);
    }
    
    $stmt_venda->bind_param("i", $venda_id);
    $stmt_venda->execute();
    $result_venda = $stmt_venda->get_result();
    
    if ($result_venda->num_rows === 0) {
        throw new Exception("Venda não encontrada.");
    }
    
    $venda = $result_venda->fetch_assoc();
    $stmt_venda->close();
    
    // Buscar itens da venda com informações da empresa
    $sql_itens = "SELECT iv.*, p.nome AS produto_nome, p.sku, p.descricao,
                         e.nome_empresa, e.logo_empresa
                  FROM itens_venda iv
                  LEFT JOIN produtos p ON iv.produto_id = p.id
                  LEFT JOIN empresas_representadas e ON p.empresa_id = e.id
                  WHERE iv.venda_id = ?
                  ORDER BY p.nome";
    $stmt_itens = $conn->prepare($sql_itens);
    if (!$stmt_itens) {
        throw new Exception("Database error: " . $conn->error);
    }

    $stmt_itens->bind_param("i", $venda_id);
    $stmt_itens->execute();
    $result_itens = $stmt_itens->get_result();

    $itens = [];
    $empresas_logos = [];

    while ($item = $result_itens->fetch_assoc()) {
        $item['quantidade'] = $item['quantidade'] ?? 1;
        $item['preco_unitario'] = $item['preco_unitario'] ?? 0;
        $item['produto_nome'] = $item['produto_nome'] ?? 'Produto sem nome';
        $item['sku'] = $item['sku'] ?? '';
        $item['descricao'] = $item['descricao'] ?? '';
        $item['nome_empresa'] = $item['nome_empresa'] ?? '';
        $item['logo_empresa'] = $item['logo_empresa'] ?? '';

        if (!empty($item['logo_empresa']) && !in_array($item['logo_empresa'], $empresas_logos)) {
            $empresas_logos[] = $item['logo_empresa'];
        }

        $itens[] = $item;
    }
    $stmt_itens->close();
    
    // Calcular valor total se não estiver definido
    if (!isset($venda['valor_total'])) {
        $venda['valor_total'] = 0;
        foreach ($itens as $item) {
            $venda['valor_total'] += $item['quantidade'] * $item['preco_unitario'];
        }
    }

    // Gerar o PDF
    gerarPDFVenda($venda, $itens, $empresas_logos);

} catch (Exception $e) {
    echo '<div style="padding:20px; text-align:center;">';
    echo '<h2>Erro ao gerar PDF</h2>';
    echo '<p>' . htmlspecialchars($e->getMessage()) . '</p>';
    echo '<p><a href="vendas.php" style="background-color:#0275d8; color:white; padding:10px 15px; text-decoration:none; border-radius:3px;">Voltar para a lista de vendas</a></p>';
    echo '</div>';
    exit;
}

function gerarPDFVenda($venda, $itens, $empresas_logos = []) {
    try {
        require_once __DIR__ . '/vendor/setasign/fpdf/fpdf.php';

        class VendaPDF extends FPDF {
            private $primaryColor = [28, 79, 140];
            private $accentColor = [13, 110, 253];
            private $lightGray = [248, 249, 250];
            private $darkGray = [52, 58, 64];
            private $successColor = [25, 135, 84];
            
            function convertToLatin1($text) {
                return iconv('UTF-8', 'ISO-8859-1//IGNORE', $text);
            }
            
            function Header() {
                // Logo ou título no cabeçalho
                $this->SetFont('Arial', 'B', 12);
                $this->SetTextColor(100, 100, 100);
                $this->Cell(0, 10, $this->convertToLatin1('Confirmação de Venda'), 0, 1, 'R');
                $this->Ln(5);
            }
            
            function Footer() {
                $this->SetY(-15);
                $this->SetFont('Arial', 'I', 8);
                $this->SetTextColor(100, 100, 100);
                $this->Cell(0, 10, $this->convertToLatin1('Página ') . $this->PageNo() . ' de {nb}', 0, 0, 'C');
            }
            
            function ShadowBox($x, $y, $w, $h, $title, $content) {
                // Sombra
                $this->SetFillColor(200, 200, 200);
                $this->Rect($x + 1, $y + 1, $w, $h, 'F');
                
                // Caixa principal
                $this->SetFillColor(255, 255, 255);
                $this->SetDrawColor(200, 200, 200);
                $this->Rect($x, $y, $w, $h, 'DF');
                
                // Título
                $this->SetXY($x + 2, $y + 2);
                $this->SetFont('Arial', 'B', 10);
                $this->SetTextColor($this->primaryColor[0], $this->primaryColor[1], $this->primaryColor[2]);
                $this->Cell($w - 4, 6, $this->convertToLatin1($title), 0, 1, 'L');
                
                // Conteúdo
                $this->SetX($x + 2);
                $this->SetFont('Arial', '', 9);
                $this->SetTextColor(0, 0, 0);
                
                $lines = explode("\n", $content);
                foreach ($lines as $line) {
                    $this->Cell($w - 4, 5, $this->convertToLatin1($line), 0, 1, 'L');
                    $this->SetX($x + 2);
                }
            }

            function checkPageBreak($h) {
                if ($this->GetY() + $h > $this->PageBreakTrigger) {
                    $this->AddPage($this->CurOrientation);
                }
            }

            function nbLines($w, $txt) {
                $cw = &$this->CurrentFont['cw'];
                if ($w == 0) {
                    $w = $this->w - $this->rMargin - $this->x;
                }

                $txt = $this->convertToLatin1((string)$txt);
                $wmax = ($w - 2 * $this->cMargin) * 1000 / $this->FontSize;
                $s = str_replace("\r", '', $txt);
                $nb = strlen($s);
                if ($nb > 0 && $s[$nb - 1] == "\n") {
                    $nb--;
                }

                $sep = -1;
                $i = 0;
                $j = 0;
                $l = 0;
                $nl = 1;

                while ($i < $nb) {
                    $c = $s[$i];
                    if ($c == "\n") {
                        $i++;
                        $sep = -1;
                        $j = $i;
                        $l = 0;
                        $nl++;
                        continue;
                    }

                    if ($c == ' ') {
                        $sep = $i;
                    }

                    $l += $cw[$c] ?? 0;
                    if ($l > $wmax) {
                        if ($sep == -1) {
                            if ($i == $j) {
                                $i++;
                            }
                        } else {
                            $i = $sep + 1;
                        }
                        $sep = -1;
                        $j = $i;
                        $l = 0;
                        $nl++;
                    } else {
                        $i++;
                    }
                }

                return $nl;
            }
            
            function ModernTable($headers, $data, $widths) {
                // Cabeçalho da tabela
                $this->SetFillColor($this->lightGray[0], $this->lightGray[1], $this->lightGray[2]);
                $this->SetTextColor($this->darkGray[0], $this->darkGray[1], $this->darkGray[2]);
                $this->SetDrawColor(200, 200, 200);
                $this->SetLineWidth(0.3);
                $this->SetFont('Arial', 'B', 9);
                
                for ($i = 0; $i < count($headers); $i++) {
                    $this->Cell($widths[$i], 8, $this->convertToLatin1($headers[$i]), 1, 0, 'C', true);
                }
                $this->Ln();
                
                // Dados da tabela
                $this->SetFillColor(255, 255, 255);
                $this->SetTextColor(0, 0, 0);
                $this->SetFont('Arial', '', 8);
                
                $fill = false;
                $lineHeight = 4.2;
                $minRowHeight = 6.2;
                foreach ($data as $row) {
                    $maxLines = 1;
                    for ($i = 0; $i < count($row); $i++) {
                        $maxLines = max($maxLines, $this->nbLines($widths[$i], $row[$i]));
                    }

                    $rowHeight = max($minRowHeight, $lineHeight * $maxLines);
                    $this->checkPageBreak($rowHeight);

                    if ($fill) {
                        $this->SetFillColor(248, 249, 250);
                    } else {
                        $this->SetFillColor(255, 255, 255);
                    }

                    $x = $this->GetX();
                    $y = $this->GetY();

                    for ($i = 0; $i < count($row); $i++) {
                        $align = ($i === 1 || $i === 2) ? 'L' : 'C';
                        $cellLines = $this->nbLines($widths[$i], $row[$i]);

                        // Desenha o fundo/borda da célula inteira da linha
                        $this->Rect($x, $y, $widths[$i], $rowHeight, 'DF');

                        // Mantem linhas simples compactas e centralizadas; usa quebra apenas quando necessario
                        $this->SetXY($x, $y);
                        if ($cellLines === 1) {
                            $this->Cell($widths[$i], $rowHeight, $this->convertToLatin1((string)$row[$i]), 0, 0, $align, false);
                        } else {
                            $this->MultiCell($widths[$i], $lineHeight, $this->convertToLatin1((string)$row[$i]), 0, $align, false);
                        }

                        $x += $widths[$i];
                        $this->SetXY($x, $y);
                    }

                    $this->SetY($y + $rowHeight);
                    $fill = !$fill;
                }
            }
        }

        PDFHelper::startPdfOutput('Confirmacao_Venda_' . str_pad($venda['id'], 6, '0', STR_PAD_LEFT) . '.pdf');
        
        $pdf = new VendaPDF('P', 'mm', 'A4');
        $pdf->AliasNbPages();
        $pdf->AddPage();
        
        $pdf->SetFont('Arial', 'B', 24);
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
            $pdf->Cell(140, 12, $pdf->convertToLatin1('CONFIRMAÇÃO DE VENDA'), 0, 0, 'L');
            $pdf->Image($logo_empresa, 160, $pdf->GetY() - 5, 30, 0);
            $pdf->Ln(12);
        } else {
            $pdf->Cell(0, 12, $pdf->convertToLatin1('CONFIRMAÇÃO DE VENDA'), 0, 1, 'C');
        }
        
        $pdf->SetFont('Arial', '', 14);
        $pdf->SetTextColor(100, 100, 100);
        $pdf->Cell(0, 8, $pdf->convertToLatin1('Venda #') . str_pad($venda['id'], 6, '0', STR_PAD_LEFT), 0, 1, 'C');
        $pdf->SetDrawColor(28, 79, 140);
        $pdf->SetLineWidth(1);
        $pdf->Line(80, $pdf->GetY() + 2, 130, $pdf->GetY() + 2);
        $pdf->Ln(15);
        
        // Preparar dados do cliente
        $currentY = $pdf->GetY();
        $client_name = $venda['cliente_nome'] ?? 'Cliente não informado';
        
        // Verificar se é pessoa física ou jurídica
        $tipo_pessoa = $venda['tipo_pessoa'] ?? 'fisica';
        if ($tipo_pessoa == 'juridica') {
            $client_details = ($venda['nome_fantasia'] ? $venda['nome_fantasia'] . "\n" : "") . $client_name . "\n";
            $client_details .= "CNPJ: " . ($venda['cpf_cnpj'] ?? 'Não informado') . "\n";
            if (!empty($venda['inscricao_estadual'])) {
                $client_details .= "I.E.: " . $venda['inscricao_estadual'] . "\n";
            }
        } else {
            $client_details = $client_name . "\n";
            $client_details .= "CPF: " . ($venda['cpf_cnpj'] ?? 'Não informado') . "\n";
        }
        
        if (!empty($venda['endereco'])) {
            $endereco_completo = $venda['endereco'];
            if (!empty($venda['numero'])) {
                $endereco_completo .= ", " . $venda['numero'];
            }
            if (!empty($venda['ponto_referencia'])) {
                $endereco_completo .= " - " . $venda['ponto_referencia'];
            }
            $client_details .= $endereco_completo . "\n";
        }
        if (!empty($venda['cidade']) && !empty($venda['estado'])) {
            $client_details .= $venda['cidade'] . " - " . $venda['estado'];
            if (!empty($venda['cep'])) {
                $client_details .= " - CEP: " . $venda['cep'];
            }
            $client_details .= "\n";
        }
        if (!empty($venda['telefone'])) {
            $client_details .= "Tel: " . $venda['telefone'] . "\n";
        }
        if (!empty($venda['cliente_email'])) {
            $client_details .= "Email: " . $venda['cliente_email'];
        }
        
        // Dados da venda
        $data_venda = isset($venda['data_venda']) ? date('d/m/Y H:i', strtotime($venda['data_venda'])) : date('d/m/Y H:i');
        $status = isset($venda['status_venda']) ? ucfirst($venda['status_venda']) : 'Pendente';
        $sale_details = "Data da Venda: " . $data_venda . "\n";
        $sale_details .= "Status: " . $status . "\n";
        $sale_details .= "Forma de Pagamento: " . ucfirst($venda['forma_pagamento'] ?? 'Não informado') . "\n";
        $sale_details .= "Responsavel: Equipe de Vendas";
        
        // Criar caixas de informação
        $pdf->ShadowBox(15, $currentY, 85, 45, 'DADOS DO CLIENTE', $client_details);
        $pdf->ShadowBox(110, $currentY, 85, 45, 'DADOS DA VENDA', $sale_details);
        
        $pdf->SetY($currentY + 55);
        
        // Tabela de itens
        $pdf->SetFont('Arial', 'B', 14);
        $pdf->SetTextColor(28, 79, 140);
        $pdf->Cell(0, 10, $pdf->convertToLatin1('ITENS DA VENDA'), 0, 1, 'L');
        $pdf->Ln(5);
        
        $headers = ['Item', 'Produto', 'Empresa', 'Qtd', 'Valor Unit.', 'Subtotal'];
        $widths = [12, 70, 48, 15, 22, 23];
        
        $table_data = [];
        $total = 0;
        $item_num = 1;
        
        foreach ($itens as $item) {
            $quantidade = $item['quantidade'];
            $preco_unitario = $item['preco_unitario'];
            $subtotal = $quantidade * $preco_unitario;
            $total += $subtotal;

            $produto_info = $item['produto_nome'];

            $table_data[] = [
                $item_num,
                $produto_info,
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
        
        // Total
        $pdf->Ln(10);
        $pdf->SetFont('Arial', 'B', 16);
        $pdf->SetTextColor(25, 135, 84);
        $pdf->Cell(0, 10, $pdf->convertToLatin1('VALOR TOTAL: R$ ' . number_format($total, 2, ',', '.')), 0, 1, 'R');
        
        // Observações finais
        $pdf->Ln(10);
        $pdf->SetFont('Arial', '', 10);
        $pdf->SetTextColor(100, 100, 100);
        $pdf->Cell(0, 5, $pdf->convertToLatin1('Este documento confirma a realização da venda e serve como comprovante.'), 0, 1, 'C');
        $pdf->Cell(0, 5, $pdf->convertToLatin1('Para dúvidas ou esclarecimentos, entre em contato conosco.'), 0, 1, 'C');
        
        $pdf->SetDrawColor(0, 0, 0);
        $pdf->SetLineWidth(0.2);
        $pdf->SetTextColor(0, 0, 0);
        
        $filename = 'Confirmacao_Venda_' . str_pad($venda['id'], 6, '0', STR_PAD_LEFT) . '_' . date('Y-m-d') . '.pdf';
        $pdf->Output($filename, 'I');
        
    } catch (Exception $e) {
        throw new Exception("Erro ao gerar PDF: " . $e->getMessage());
    }
}
?>

