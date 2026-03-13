<?php
// Inicia o output buffering para evitar problemas de "headers already sent"
ob_start();

// Configurar relatÃ³rio de erros para debug
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'includes/session_bootstrap.php';

// Verifica se o usuÃ¡rio estÃ¡ logado
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    if (ob_get_length()) ob_end_clean();
    header("location: index.php");
    exit;
}

require_once 'includes/db_connect.php';
require_once 'includes/PDFHelper.php';
require_once __DIR__ . '/vendor/setasign/fpdf/fpdf.php';

// Buscar os dados do usuÃ¡rio logado
$usuario_id = $_SESSION["id"];
$sql_usuario = "SELECT nome, empresa FROM usuarios WHERE id = ?";
$stmt_usuario = $conn->prepare($sql_usuario);
$stmt_usuario->bind_param("i", $usuario_id);
$stmt_usuario->execute();
$result_usuario = $stmt_usuario->get_result();
$usuario = $result_usuario->fetch_assoc();

// FunÃ§Ãµes de geraÃ§Ã£o de relatÃ³rio (reutilizadas do relatorios.php)
function gerarRelatorioVendasGeral($conn, $di, $df, $cid, $status, $eid) {
    $sql = "SELECT v.id, v.data_venda, v.valor_total, v.status_venda, c.nome as cliente_nome, c.cpf_cnpj,
                   COALESCE(SUM(iv.quantidade * iv.preco_unitario * (p.percentual_lucro / 100)), 0) as lucro_total
            FROM vendas v
            LEFT JOIN clientes c ON v.cliente_id = c.id
            LEFT JOIN itens_venda iv ON v.id = iv.venda_id
            LEFT JOIN produtos p ON iv.produto_id = p.id
            WHERE v.data_venda BETWEEN ? AND ?";
    
    $data_inicio_completa = $di . ' 00:00:00';
    $data_fim_completa = $df . ' 23:59:59';
    
    $params = [$data_inicio_completa, $data_fim_completa];
    $types = "ss";

    if ($cid) { $sql .= " AND v.cliente_id = ?"; $params[] = $cid; $types .= "i"; }
    if ($status) { $sql .= " AND v.status_venda = ?"; $params[] = $status; $types .= "s"; }
    
    if ($eid) {
        $sql .= " AND v.id IN (SELECT DISTINCT venda_id FROM itens_venda iv_e JOIN produtos p_e ON iv_e.produto_id = p_e.id WHERE p_e.empresa_id = ?)";
        $params[] = $eid;
        $types .= "i";
    }

    $sql .= " GROUP BY v.id, c.nome, c.cpf_cnpj ORDER BY v.data_venda DESC";
    $stmt = $conn->prepare($sql);
    if (!empty($params)) { $stmt->bind_param($types, ...$params); }
    $stmt->execute();
    $result = $stmt->get_result();
    $dados = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    $vendas_concluidas = array_filter($dados, function($v) { return $v['status_venda'] == 'concluida'; });
    $total_vendas = count($dados);
    $total_valor = array_sum(array_column($vendas_concluidas, 'valor_total'));
    $total_lucro = array_sum(array_column($vendas_concluidas, 'lucro_total'));

    return ['dados' => $dados, 'resumo' => ['total_vendas' => $total_vendas, 'faturamento' => $total_valor, 'lucro_bruto' => $total_lucro, 'margem_media' => $total_valor > 0 ? ($total_lucro / $total_valor) * 100 : 0]];
}

function gerarRelatorioProdutosVendidos($conn, $di, $df, $eid, $pid) {
    $sql = "SELECT p.id, p.nome as produto_nome, e.nome_empresa as empresa_nome,
                   SUM(iv.quantidade) as total_vendido,
                   SUM(iv.quantidade * iv.preco_unitario) as total_faturado,
                   SUM(iv.quantidade * iv.preco_unitario * (p.percentual_lucro / 100)) as total_lucro
            FROM itens_venda iv
            JOIN produtos p ON iv.produto_id = p.id
            LEFT JOIN empresas_representadas e ON p.empresa_id = e.id
            JOIN vendas v ON iv.venda_id = v.id
            WHERE v.data_venda BETWEEN ? AND ? AND v.status_venda = 'concluida'";
    
    $data_inicio_completa = $di . ' 00:00:00';
    $data_fim_completa = $df . ' 23:59:59';

    $params = [$data_inicio_completa, $data_fim_completa];
    $types = "ss";

    if ($eid) { $sql .= " AND p.empresa_id = ?"; $params[] = $eid; $types .= "i"; }
    if ($pid) { $sql .= " AND p.id = ?"; $params[] = $pid; $types .= "i"; }
    
    $sql .= " GROUP BY p.id, p.nome, e.nome_empresa ORDER BY total_faturado DESC";
    $stmt = $conn->prepare($sql);
    if (!empty($params)) { $stmt->bind_param($types, ...$params); }
    $stmt->execute();
    $result = $stmt->get_result();
    $dados = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    $total_faturamento = array_sum(array_column($dados, 'total_faturado'));
    $total_lucro = array_sum(array_column($dados, 'total_lucro'));

    return ['dados' => $dados, 'resumo' => ['total_faturamento' => $total_faturamento, 'total_lucro' => $total_lucro, 'margem_geral' => $total_faturamento > 0 ? ($total_lucro / $total_faturamento) * 100 : 0, 'total_produtos' => count($dados)]];
}

function gerarRelatorioEmpresas($conn, $di, $df, $eid) {
    $sql = "SELECT 
                e.id as empresa_id,
                e.nome_empresa,
                COUNT(DISTINCT v.id) as total_vendas,
                SUM(iv.quantidade) as total_itens,
                COALESCE(SUM(iv.quantidade * iv.preco_unitario), 0) as total_faturado,
                COALESCE(SUM(iv.quantidade * iv.preco_unitario * (p.percentual_lucro / 100)), 0) as total_lucro
            FROM vendas v
            JOIN itens_venda iv ON v.id = iv.venda_id
            JOIN produtos p ON iv.produto_id = p.id
            JOIN empresas_representadas e ON p.empresa_id = e.id
            WHERE v.status_venda = 'concluida' AND v.data_venda BETWEEN ? AND ?";

    $data_inicio_completa = $di . ' 00:00:00';
    $data_fim_completa = $df . ' 23:59:59';
    
    $params = [$data_inicio_completa, $data_fim_completa];
    $types = "ss";

    if ($eid) {
        $sql .= " AND e.id = ?";
        $params[] = $eid;
        $types .= "i";
    }

    $sql .= " GROUP BY e.id, e.nome_empresa ORDER BY total_lucro DESC";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    $dados = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    $faturamento_geral = array_sum(array_column($dados, 'total_faturado'));
    $lucro_geral = array_sum(array_column($dados, 'total_lucro'));
    
    return ['dados' => $dados, 'resumo' => ['faturamento_geral' => $faturamento_geral, 'lucro_geral' => $lucro_geral, 'empresas_ativas' => count($dados)]];
}

// Classe para gerar PDF de relatÃ³rios
class RelatoriosPDF extends FPDF {
    private $usuario_nome;
    private $titulo_relatorio;
    
    function __construct($usuario_nome, $titulo_relatorio) {
        parent::__construct();
        $this->usuario_nome = $usuario_nome;
        $this->titulo_relatorio = $titulo_relatorio;
    }
    
    function Header() {
        // Logo (se existir)
        if (file_exists('logo.png')) {
            $this->Image('logo.png', 10, 6, 30);
        }
        
        // TÃ­tulo do relatÃ³rio
        $this->SetFont('Arial', 'B', 16);
        $this->SetY(10);
        $this->SetX(50);
        $this->Cell(0, 10, PDFHelper::utf8ToLatin1($this->titulo_relatorio), 0, 1, 'L');
        
        // Linha descritiva
        $this->SetFont('Arial', 'I', 12);
        $this->SetX(50);
        $this->Cell(0, 8, PDFHelper::utf8ToLatin1('RelatÃ³rio Comercial para PrestaÃ§Ã£o de Contas'), 0, 1, 'L');
        
        // InformaÃ§Ãµes do cabeÃ§alho
        $this->SetFont('Arial', '', 10);
        $this->SetX(50);
        $this->Cell(0, 8, PDFHelper::utf8ToLatin1('Representante Comercial: ' . $this->usuario_nome), 0, 1, 'L');
        $this->SetX(50);
        $this->Cell(0, 8, PDFHelper::utf8ToLatin1('Data de GeraÃ§Ã£o: ' . date('d/m/Y H:i:s')), 0, 1, 'L');
        
        // Linha separadora
        $this->Ln(5);
        $this->Line(10, $this->GetY(), 200, $this->GetY());
        $this->Ln(10);
    }
    
    function Footer() {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->Cell(0, 10, PDFHelper::utf8ToLatin1('PÃ¡gina ' . $this->PageNo() . ' - RelatÃ³rio gerado em ' . date('d/m/Y H:i:s')), 0, 0, 'C');
    }
    
    function AdicionarResumo($resumo, $tipo_relatorio) {
        $this->SetFont('Arial', 'B', 14);
        $this->Cell(0, 10, PDFHelper::utf8ToLatin1('RESUMO EXECUTIVO'), 0, 1, 'C');
        $this->Ln(5);
        
        // Caixa com fundo cinza para o resumo
        $this->SetFillColor(240, 240, 240);
        $this->Rect(10, $this->GetY(), 190, 40, 'F');
        
        $this->SetFont('Arial', '', 11);
        $y_inicial = $this->GetY() + 5;
        
        if ($tipo_relatorio == 'lucro_por_empresa') {
            $this->SetXY(15, $y_inicial);
            $this->Cell(85, 8, PDFHelper::utf8ToLatin1('Faturamento Total:'), 0, 0, 'L');
            $this->SetFont('Arial', 'B', 11);
            $this->Cell(0, 8, 'R$ ' . number_format($resumo['faturamento_geral'], 2, ',', '.'), 0, 1, 'L');
            
            $this->SetFont('Arial', '', 11);
            $this->SetX(15);
            $this->Cell(85, 8, PDFHelper::utf8ToLatin1('Lucro Bruto Total:'), 0, 0, 'L');
            $this->SetFont('Arial', 'B', 11);
            $this->Cell(0, 8, 'R$ ' . number_format($resumo['lucro_geral'], 2, ',', '.'), 0, 1, 'L');
            
            $this->SetFont('Arial', '', 11);
            $this->SetX(15);
            $this->Cell(85, 8, PDFHelper::utf8ToLatin1('Empresas com Vendas:'), 0, 0, 'L');
            $this->SetFont('Arial', 'B', 11);
            $this->Cell(0, 8, $resumo['empresas_ativas'], 0, 1, 'L');
            
            $margem_geral = $resumo['faturamento_geral'] > 0 ? ($resumo['lucro_geral'] / $resumo['faturamento_geral']) * 100 : 0;
            $this->SetFont('Arial', '', 11);
            $this->SetX(15);
            $this->Cell(85, 8, PDFHelper::utf8ToLatin1('Margem MÃ©dia:'), 0, 0, 'L');
            $this->SetFont('Arial', 'B', 11);
            $this->Cell(0, 8, number_format($margem_geral, 1, ',', '.') . '%', 0, 1, 'L');
            
        } elseif ($tipo_relatorio == 'vendas_geral') {
            $this->SetXY(15, $y_inicial);
            $this->Cell(85, 8, PDFHelper::utf8ToLatin1('Total de Vendas:'), 0, 0, 'L');
            $this->SetFont('Arial', 'B', 11);
            $this->Cell(0, 8, $resumo['total_vendas'], 0, 1, 'L');
            
            $this->SetFont('Arial', '', 11);
            $this->SetX(15);
            $this->Cell(85, 8, PDFHelper::utf8ToLatin1('Faturamento (ConcluÃ­das):'), 0, 0, 'L');
            $this->SetFont('Arial', 'B', 11);
            $this->Cell(0, 8, 'R$ ' . number_format($resumo['faturamento'], 2, ',', '.'), 0, 1, 'L');
            
            $this->SetFont('Arial', '', 11);
            $this->SetX(15);
            $this->Cell(85, 8, PDFHelper::utf8ToLatin1('Lucro Bruto:'), 0, 0, 'L');
            $this->SetFont('Arial', 'B', 11);
            $this->Cell(0, 8, 'R$ ' . number_format($resumo['lucro_bruto'], 2, ',', '.'), 0, 1, 'L');
            
            $this->SetFont('Arial', '', 11);
            $this->SetX(15);
            $this->Cell(85, 8, PDFHelper::utf8ToLatin1('Margem MÃ©dia:'), 0, 0, 'L');
            $this->SetFont('Arial', 'B', 11);
            $this->Cell(0, 8, number_format($resumo['margem_media'], 1, ',', '.') . '%', 0, 1, 'L');
            
        } elseif ($tipo_relatorio == 'produtos_vendidos') {
            $this->SetXY(15, $y_inicial);
            $this->Cell(85, 8, PDFHelper::utf8ToLatin1('Faturamento Total:'), 0, 0, 'L');
            $this->SetFont('Arial', 'B', 11);
            $this->Cell(0, 8, 'R$ ' . number_format($resumo['total_faturamento'], 2, ',', '.'), 0, 1, 'L');
            
            $this->SetFont('Arial', '', 11);
            $this->SetX(15);
            $this->Cell(85, 8, PDFHelper::utf8ToLatin1('Lucro Total:'), 0, 0, 'L');
            $this->SetFont('Arial', 'B', 11);
            $this->Cell(0, 8, 'R$ ' . number_format($resumo['total_lucro'], 2, ',', '.'), 0, 1, 'L');
            
            $this->SetFont('Arial', '', 11);
            $this->SetX(15);
            $this->Cell(85, 8, PDFHelper::utf8ToLatin1('Margem Geral:'), 0, 0, 'L');
            $this->SetFont('Arial', 'B', 11);
            $this->Cell(0, 8, number_format($resumo['margem_geral'], 1, ',', '.') . '%', 0, 1, 'L');
            
            $this->SetFont('Arial', '', 11);
            $this->SetX(15);
            $this->Cell(85, 8, PDFHelper::utf8ToLatin1('Produtos Distintos:'), 0, 0, 'L');
            $this->SetFont('Arial', 'B', 11);
            $this->Cell(0, 8, $resumo['total_produtos'], 0, 1, 'L');
        }
        
        $this->Ln(15);
    }
    
    function AdicionarTabelaVendasGeral($dados) {
        $this->SetFont('Arial', 'B', 12);
        $this->Cell(0, 10, PDFHelper::utf8ToLatin1('DETALHAMENTO DAS VENDAS'), 0, 1, 'C');
        $this->Ln(3);
        
        // CabeÃ§alho da tabela
        $this->SetFont('Arial', 'B', 8);
        $this->SetFillColor(200, 200, 200);
        $this->Cell(18, 8, 'Venda', 1, 0, 'C', true);
        $this->Cell(22, 8, 'Data', 1, 0, 'C', true);
        $this->Cell(55, 8, 'Cliente', 1, 0, 'C', true);
        $this->Cell(30, 8, 'CPF/CNPJ', 1, 0, 'C', true);
        $this->Cell(25, 8, 'Faturamento', 1, 0, 'C', true);
        $this->Cell(22, 8, 'Lucro', 1, 0, 'C', true);
        $this->Cell(15, 8, 'Margem', 1, 0, 'C', true);
        $this->Cell(15, 8, 'Status', 1, 1, 'C', true);
        
        // Dados da tabela
        $this->SetFont('Arial', '', 7);
        $contador = 0;
        $total_faturamento = 0;
        $total_lucro = 0;
        
        foreach ($dados as $row) {
            // Quebra de pÃ¡gina se necessÃ¡rio
            if ($this->GetY() > 250) {
                $this->AddPage();
                // Repetir cabeÃ§alho
                $this->SetFont('Arial', 'B', 8);
                $this->SetFillColor(200, 200, 200);
                $this->Cell(18, 8, 'Venda', 1, 0, 'C', true);
                $this->Cell(22, 8, 'Data', 1, 0, 'C', true);
                $this->Cell(55, 8, 'Cliente', 1, 0, 'C', true);
                $this->Cell(30, 8, 'CPF/CNPJ', 1, 0, 'C', true);
                $this->Cell(25, 8, 'Faturamento', 1, 0, 'C', true);
                $this->Cell(22, 8, 'Lucro', 1, 0, 'C', true);
                $this->Cell(15, 8, 'Margem', 1, 0, 'C', true);
                $this->Cell(15, 8, 'Status', 1, 1, 'C', true);
                $this->SetFont('Arial', '', 7);
            }
            
            $margem = $row['valor_total'] > 0 ? ($row['lucro_total'] / $row['valor_total']) * 100 : 0;
            
            // Somar valores para totais (apenas vendas concluÃ­das)
            if ($row['status_venda'] == 'concluida') {
                $total_faturamento += $row['valor_total'];
                $total_lucro += $row['lucro_total'];
            }
            
            $this->Cell(18, 6, '#' . $row['id'], 1, 0, 'C');
            $this->Cell(22, 6, date('d/m/Y', strtotime($row['data_venda'])), 1, 0, 'C');
            $this->Cell(55, 6, PDFHelper::utf8ToLatin1(substr($row['cliente_nome'], 0, 28)), 1, 0, 'L');
            $this->Cell(30, 6, PDFHelper::utf8ToLatin1(substr($row['cpf_cnpj'], 0, 18)), 1, 0, 'L');
            $this->Cell(25, 6, 'R$ ' . number_format($row['valor_total'], 2, ',', '.'), 1, 0, 'R');
            $this->Cell(22, 6, 'R$ ' . number_format($row['lucro_total'], 2, ',', '.'), 1, 0, 'R');
            $this->Cell(15, 6, number_format($margem, 1) . '%', 1, 0, 'C');
            $this->Cell(15, 6, PDFHelper::utf8ToLatin1(substr(ucfirst($row['status_venda']), 0, 8)), 1, 1, 'C');
            
            $contador++;
        }
        
        // Linha de totais
        $this->SetFont('Arial', 'B', 8);
        $this->SetFillColor(230, 230, 230);
        $this->Cell(125, 6, PDFHelper::utf8ToLatin1('TOTAIS (Vendas ConcluÃ­das):'), 1, 0, 'R', true);
        $this->Cell(25, 6, 'R$ ' . number_format($total_faturamento, 2, ',', '.'), 1, 0, 'R', true);
        $this->Cell(22, 6, 'R$ ' . number_format($total_lucro, 2, ',', '.'), 1, 0, 'R', true);
        $margem_geral = $total_faturamento > 0 ? ($total_lucro / $total_faturamento) * 100 : 0;
        $this->Cell(15, 6, number_format($margem_geral, 1) . '%', 1, 0, 'C', true);
        $this->Cell(15, 6, '', 1, 1, 'C', true);
        
        $this->Ln(5);
        $this->SetFont('Arial', 'I', 9);
        $this->Cell(0, 6, PDFHelper::utf8ToLatin1('Total de registros: ' . $contador), 0, 1, 'R');
    }
    
    function AdicionarTabelaProdutos($dados) {
        $this->SetFont('Arial', 'B', 12);
        $this->Cell(0, 10, PDFHelper::utf8ToLatin1('PRODUTOS MAIS VENDIDOS'), 0, 1, 'C');
        $this->Ln(3);
        
        // CabeÃ§alho da tabela
        $this->SetFont('Arial', 'B', 9);
        $this->SetFillColor(200, 200, 200);
        $this->Cell(60, 8, 'Produto', 1, 0, 'C', true);
        $this->Cell(40, 8, 'Empresa', 1, 0, 'C', true);
        $this->Cell(20, 8, 'Qtd.', 1, 0, 'C', true);
        $this->Cell(30, 8, 'Faturamento', 1, 0, 'C', true);
        $this->Cell(25, 8, 'Lucro', 1, 0, 'C', true);
        $this->Cell(15, 8, 'Margem', 1, 1, 'C', true);
        
        // Dados da tabela
        $this->SetFont('Arial', '', 8);
        $contador = 0;
        foreach ($dados as $row) {
            // Quebra de pÃ¡gina se necessÃ¡rio
            if ($this->GetY() > 250) {
                $this->AddPage();
                // Repetir cabeÃ§alho
                $this->SetFont('Arial', 'B', 9);
                $this->SetFillColor(200, 200, 200);
                $this->Cell(60, 8, 'Produto', 1, 0, 'C', true);
                $this->Cell(40, 8, 'Empresa', 1, 0, 'C', true);
                $this->Cell(20, 8, 'Qtd.', 1, 0, 'C', true);
                $this->Cell(30, 8, 'Faturamento', 1, 0, 'C', true);
                $this->Cell(25, 8, 'Lucro', 1, 0, 'C', true);
                $this->Cell(15, 8, 'Margem', 1, 1, 'C', true);
                $this->SetFont('Arial', '', 8);
            }
            
            $margem = $row['total_faturado'] > 0 ? ($row['total_lucro'] / $row['total_faturado']) * 100 : 0;
            
            $this->Cell(60, 6, PDFHelper::utf8ToLatin1(substr($row['produto_nome'], 0, 30)), 1, 0, 'L');
            $this->Cell(40, 6, PDFHelper::utf8ToLatin1(substr($row['empresa_nome'], 0, 20)), 1, 0, 'L');
            $this->Cell(20, 6, $row['total_vendido'], 1, 0, 'C');
            $this->Cell(30, 6, 'R$ ' . number_format($row['total_faturado'], 2, ',', '.'), 1, 0, 'R');
            $this->Cell(25, 6, 'R$ ' . number_format($row['total_lucro'], 2, ',', '.'), 1, 0, 'R');
            $this->Cell(15, 6, number_format($margem, 1) . '%', 1, 1, 'C');
            
            $contador++;
        }
        
        $this->Ln(5);
        $this->SetFont('Arial', 'I', 9);
        $this->Cell(0, 6, PDFHelper::utf8ToLatin1('Total de registros: ' . $contador), 0, 1, 'R');
    }
    
    function AdicionarTabelaEmpresas($dados) {
        $this->SetFont('Arial', 'B', 12);
        $this->Cell(0, 10, PDFHelper::utf8ToLatin1('PERFORMANCE POR EMPRESA'), 0, 1, 'C');
        $this->Ln(3);
        
        // CabeÃ§alho da tabela
        $this->SetFont('Arial', 'B', 9);
        $this->SetFillColor(200, 200, 200);
        $this->Cell(60, 8, 'Empresa', 1, 0, 'C', true);
        $this->Cell(20, 8, 'Vendas', 1, 0, 'C', true);
        $this->Cell(20, 8, 'Itens', 1, 0, 'C', true);
        $this->Cell(35, 8, 'Faturamento', 1, 0, 'C', true);
        $this->Cell(30, 8, 'Lucro', 1, 0, 'C', true);
        $this->Cell(25, 8, 'Margem', 1, 1, 'C', true);
        
        // Dados da tabela
        $this->SetFont('Arial', '', 8);
        $contador = 0;
        foreach ($dados as $row) {
            // Quebra de pÃ¡gina se necessÃ¡rio
            if ($this->GetY() > 250) {
                $this->AddPage();
                // Repetir cabeÃ§alho
                $this->SetFont('Arial', 'B', 9);
                $this->SetFillColor(200, 200, 200);
                $this->Cell(60, 8, 'Empresa', 1, 0, 'C', true);
                $this->Cell(20, 8, 'Vendas', 1, 0, 'C', true);
                $this->Cell(20, 8, 'Itens', 1, 0, 'C', true);
                $this->Cell(35, 8, 'Faturamento', 1, 0, 'C', true);
                $this->Cell(30, 8, 'Lucro', 1, 0, 'C', true);
                $this->Cell(25, 8, 'Margem', 1, 1, 'C', true);
                $this->SetFont('Arial', '', 8);
            }
            
            $margem = $row['total_faturado'] > 0 ? ($row['total_lucro'] / $row['total_faturado']) * 100 : 0;
            
            $this->Cell(60, 6, PDFHelper::utf8ToLatin1(substr($row['nome_empresa'], 0, 30)), 1, 0, 'L');
            $this->Cell(20, 6, $row['total_vendas'], 1, 0, 'C');
            $this->Cell(20, 6, $row['total_itens'], 1, 0, 'C');
            $this->Cell(35, 6, 'R$ ' . number_format($row['total_faturado'], 2, ',', '.'), 1, 0, 'R');
            $this->Cell(30, 6, 'R$ ' . number_format($row['total_lucro'], 2, ',', '.'), 1, 0, 'R');
            $this->Cell(25, 6, number_format($margem, 1) . '%', 1, 1, 'C');
            
            $contador++;
        }
        
        $this->Ln(5);
        $this->SetFont('Arial', 'I', 9);
        $this->Cell(0, 6, PDFHelper::utf8ToLatin1('Total de registros: ' . $contador), 0, 1, 'R');
    }
    
    function AdicionarInformacoesComissao($dados_relatorio, $tipo_relatorio, $periodo) {
        // Altura estimada da caixa de informaÃ§Ãµes bancÃ¡rias (ajuste conforme necessÃ¡rio)
        $altura_necessaria = 70; // mm
        $espaco_restante = $this->h - $this->GetY() - $this->bMargin;
        if ($espaco_restante < $altura_necessaria) {
            $this->AddPage();
        }
        $this->Ln(10);
        $this->SetFont('Arial', 'B', 14);
        $this->Cell(0, 10, PDFHelper::utf8ToLatin1('INFORMAÃ‡Ã•ES PARA PAGAMENTO DE COMISSÃ•ES'), 0, 1, 'C');
        $this->Ln(5);
        
        // Caixa destacada
        $this->SetFillColor(245, 245, 245);
        $this->Rect(10, $this->GetY(), 190, 50, 'F');
        
        $y_inicial = $this->GetY() + 5;
        $this->SetFont('Arial', '', 11);
        
        // PerÃ­odo
        $this->SetXY(15, $y_inicial);
        $this->Cell(0, 8, PDFHelper::utf8ToLatin1('PerÃ­odo de ReferÃªncia: ' . $periodo), 0, 1, 'L');
        
        // Valores totais baseados no tipo de relatÃ³rio
        if ($tipo_relatorio == 'lucro_por_empresa') {
            $valor_bruto = $dados_relatorio['resumo']['faturamento_geral'];
            $lucro_obtido = $dados_relatorio['resumo']['lucro_geral'];
            $num_empresas = $dados_relatorio['resumo']['empresas_ativas'];
            
            $this->SetX(15);
            $this->Cell(0, 8, PDFHelper::utf8ToLatin1('Total Bruto Faturado: R$ ' . number_format($valor_bruto, 2, ',', '.')), 0, 1, 'L');
            $this->SetX(15);
            $this->Cell(0, 8, PDFHelper::utf8ToLatin1('Lucro Total Obtido: R$ ' . number_format($lucro_obtido, 2, ',', '.')), 0, 1, 'L');
            $this->SetX(15);
            $this->Cell(0, 8, PDFHelper::utf8ToLatin1('NÃºmero de Empresas Ativas: ' . $num_empresas), 0, 1, 'L');
            
        } elseif ($tipo_relatorio == 'vendas_geral') {
            $valor_bruto = $dados_relatorio['resumo']['faturamento'];
            $lucro_obtido = $dados_relatorio['resumo']['lucro_bruto'];
            $num_vendas = $dados_relatorio['resumo']['total_vendas'];
            
            $this->SetX(15);
            $this->Cell(0, 8, PDFHelper::utf8ToLatin1('Faturamento Total (ConcluÃ­das): R$ ' . number_format($valor_bruto, 2, ',', '.')), 0, 1, 'L');
            $this->SetX(15);
            $this->Cell(0, 8, PDFHelper::utf8ToLatin1('Lucro Total Obtido: R$ ' . number_format($lucro_obtido, 2, ',', '.')), 0, 1, 'L');
            $this->SetX(15);
            $this->Cell(0, 8, PDFHelper::utf8ToLatin1('NÃºmero de Vendas: ' . $num_vendas), 0, 1, 'L');
            
        } elseif ($tipo_relatorio == 'produtos_vendidos') {
            $valor_bruto = $dados_relatorio['resumo']['total_faturamento'];
            $lucro_obtido = $dados_relatorio['resumo']['total_lucro'];
            $num_produtos = $dados_relatorio['resumo']['total_produtos'];
            
            $this->SetX(15);
            $this->Cell(0, 8, PDFHelper::utf8ToLatin1('Faturamento Total: R$ ' . number_format($valor_bruto, 2, ',', '.')), 0, 1, 'L');
            $this->SetX(15);
            $this->Cell(0, 8, PDFHelper::utf8ToLatin1('Lucro Total Obtido: R$ ' . number_format($lucro_obtido, 2, ',', '.')), 0, 1, 'L');
            $this->SetX(15);
            $this->Cell(0, 8, PDFHelper::utf8ToLatin1('Produtos Diferentes Vendidos: ' . $num_produtos), 0, 1, 'L');
        }
        
        $this->Ln(10);
        
        // Nota explicativa
        $this->SetFont('Arial', 'I', 10);
        $this->Cell(0, 6, PDFHelper::utf8ToLatin1('Obs: Este relatÃ³rio apresenta todas as vendas realizadas no perÃ­odo para'), 0, 1, 'L');
        $this->Cell(0, 6, PDFHelper::utf8ToLatin1('verificaÃ§Ã£o e cÃ¡lculo de comissÃµes conforme acordo comercial estabelecido.'), 0, 1, 'L');
        
        $this->Ln(5);
    }
}

// Processar os parÃ¢metros recebidos
$tipo_relatorio = $_GET["tipo_relatorio"] ?? '';
$data_inicio = $_GET["data_inicio"] ?? '';
$data_fim = $_GET["data_fim"] ?? '';
$cliente_id = $_GET["cliente_id"] ?? null;
$status_filtro = $_GET["status_filtro"] ?? null;
$empresa_id = $_GET["empresa_id"] ?? null;
$produto_id = $_GET["produto_id"] ?? null;

if (empty($tipo_relatorio) || empty($data_inicio) || empty($data_fim)) {
    die('ParÃ¢metros insuficientes para gerar o relatÃ³rio.');
}

// Gerar os dados do relatÃ³rio
$dados_relatorio = [];
$titulo_relatorio = '';

try {
    switch ($tipo_relatorio) {
        case 'vendas_geral':
            $dados_relatorio = gerarRelatorioVendasGeral($conn, $data_inicio, $data_fim, $cliente_id, $status_filtro, $empresa_id);
            $titulo_relatorio = "RelatÃ³rio de Vendas Detalhadas";
            break;
        case 'produtos_vendidos':
            $dados_relatorio = gerarRelatorioProdutosVendidos($conn, $data_inicio, $data_fim, $empresa_id, $produto_id);
            $titulo_relatorio = "RelatÃ³rio de Produtos Vendidos";
            break;
        case 'lucro_por_empresa':
            $dados_relatorio = gerarRelatorioEmpresas($conn, $data_inicio, $data_fim, $empresa_id);
            $titulo_relatorio = "RelatÃ³rio de Lucratividade por Empresa";
            break;
        default:
            throw new Exception('Tipo de relatÃ³rio invÃ¡lido.');
    }

    if (empty($dados_relatorio['dados'])) {
        throw new Exception('Nenhum dado encontrado para o perÃ­odo selecionado.');
    }

    // Adicionar informaÃ§Ãµes do perÃ­odo ao tÃ­tulo
    $periodo = date('d/m/Y', strtotime($data_inicio)) . ' a ' . date('d/m/Y', strtotime($data_fim));
    $titulo_completo = $titulo_relatorio . ' - ' . $periodo;

    // Criar o PDF
    $pdf = new RelatoriosPDF($usuario['nome'], $titulo_completo);
    $pdf->AddPage();
    
    // Adicionar resumo
    $pdf->AdicionarResumo($dados_relatorio['resumo'], $tipo_relatorio);
    
    // Adicionar tabelas especÃ­ficas
    switch ($tipo_relatorio) {
        case 'vendas_geral':
            $pdf->AdicionarTabelaVendasGeral($dados_relatorio['dados']);
            break;
        case 'produtos_vendidos':
            $pdf->AdicionarTabelaProdutos($dados_relatorio['dados']);
            break;
        case 'lucro_por_empresa':
            $pdf->AdicionarTabelaEmpresas($dados_relatorio['dados']);
            break;
    }
    
    // Adicionar informaÃ§Ãµes de comissÃ£o
    $pdf->AdicionarInformacoesComissao($dados_relatorio, $tipo_relatorio, $periodo);
    
    // Limpar qualquer output que possa ter sido gerado
    if (ob_get_length()) ob_end_clean();
    
    // Definir headers para download
    $nome_arquivo = 'relatorio_' . str_replace(' ', '_', strtolower($tipo_relatorio)) . '_' . date('Y-m-d_H-i-s') . '.pdf';
    
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $nome_arquivo . '"');
    header('Cache-Control: private, max-age=0, must-revalidate');
    header('Pragma: public');
    
    $pdf->Output('D', $nome_arquivo);
    
} catch (Exception $e) {
    // Limpar output buffer se existir
    if (ob_get_length()) ob_end_clean();
    
    // Log do erro
    error_log("Erro ao gerar PDF relatÃ³rio: " . $e->getMessage());
    
    // Exibir erro detalhado para debug
    echo "<h3>Erro ao gerar relatÃ³rio:</h3>";
    echo "<p><strong>Mensagem:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p><strong>Arquivo:</strong> " . htmlspecialchars($e->getFile()) . "</p>";
    echo "<p><strong>Linha:</strong> " . $e->getLine() . "</p>";
    echo "<p><strong>Stack trace:</strong></p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    exit;
}

$conn->close();
?>

