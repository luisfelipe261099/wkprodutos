<?php
require_once 'includes/session_bootstrap.php';

// Verifica se o usuÃ¡rio estÃ¡ logado
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: index.php");
    exit;
}

require_once 'includes/db_connect.php';

// Processar os parÃ¢metros recebidos via GET
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

// Buscar dados do usuÃ¡rio logado
$usuario_id = $_SESSION["id"];
$sql_usuario = "SELECT nome FROM usuarios WHERE id = ?";
$stmt_usuario = $conn->prepare($sql_usuario);
$stmt_usuario->bind_param("i", $usuario_id);
$stmt_usuario->execute();
$result_usuario = $stmt_usuario->get_result();
$usuario = $result_usuario->fetch_assoc();

// Gerar os dados do relatÃ³rio
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
        die('Tipo de relatÃ³rio invÃ¡lido.');
}

if (empty($dados_relatorio['dados'])) {
    die('Nenhum dado encontrado para o perÃ­odo selecionado.');
}

$periodo = date('d/m/Y', strtotime($data_inicio)) . ' a ' . date('d/m/Y', strtotime($data_fim));
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $titulo_relatorio; ?> - PDF</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            line-height: 1.4;
            margin: 0;
            padding: 20px;
            color: #333;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #333;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
            color: #2c3e50;
        }
        .header h2 {
            margin: 5px 0 0 0;
            font-size: 16px;
            color: #7f8c8d;
            font-weight: normal;
        }
        .info-box {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            padding: 15px;
            margin-bottom: 25px;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        .info-row:last-child {
            margin-bottom: 0;
        }
        .summary-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }
        .summary-card {
            background-color: #ffffff;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            padding: 15px;
            text-align: center;
        }
        .summary-card h3 {
            margin: 0 0 10px 0;
            font-size: 14px;
            color: #6c757d;
        }
        .summary-card .value {
            font-size: 20px;
            font-weight: bold;
            color: #2c3e50;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
            font-size: 11px;
        }
        th, td {
            border: 1px solid #dee2e6;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f8f9fa;
            font-weight: bold;
            text-align: center;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .total-row {
            background-color: #e9ecef;
            font-weight: bold;
        }
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #dee2e6;
            font-size: 10px;
            color: #6c757d;
        }
        .commission-info {
            background-color: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 5px;
            padding: 20px;
            margin-top: 30px;
        }
        .commission-info h3 {
            margin-top: 0;
            color: #856404;
        }
        @media print {
            body { margin: 0; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1><?php echo $titulo_relatorio; ?></h1>
        <h2>RelatÃ³rio Comercial para PrestaÃ§Ã£o de Contas</h2>
        <p><strong>PerÃ­odo:</strong> <?php echo $periodo; ?></p>
        <p><strong>Representante:</strong> <?php echo htmlspecialchars($usuario['nome']); ?></p>
        <p><strong>Gerado em:</strong> <?php echo date('d/m/Y H:i:s'); ?></p>
    </div>

    <div class="summary-grid">
        <?php if ($tipo_relatorio == 'lucro_por_empresa'): ?>
            <div class="summary-card">
                <h3>Faturamento Total</h3>
                <div class="value">R$ <?php echo number_format($dados_relatorio['resumo']['faturamento_geral'], 2, ',', '.'); ?></div>
            </div>
            <div class="summary-card">
                <h3>Lucro Bruto Total</h3>
                <div class="value">R$ <?php echo number_format($dados_relatorio['resumo']['lucro_geral'], 2, ',', '.'); ?></div>
            </div>
            <div class="summary-card">
                <h3>Empresas com Vendas</h3>
                <div class="value"><?php echo $dados_relatorio['resumo']['empresas_ativas']; ?></div>
            </div>
            <div class="summary-card">
                <h3>Margem MÃ©dia</h3>
                <div class="value"><?php echo number_format($dados_relatorio['resumo']['faturamento_geral'] > 0 ? ($dados_relatorio['resumo']['lucro_geral'] / $dados_relatorio['resumo']['faturamento_geral']) * 100 : 0, 1, ',', '.'); ?>%</div>
            </div>
        <?php elseif ($tipo_relatorio == 'vendas_geral'): ?>
            <div class="summary-card">
                <h3>Total de Vendas</h3>
                <div class="value"><?php echo $dados_relatorio['resumo']['total_vendas']; ?></div>
            </div>
            <div class="summary-card">
                <h3>Faturamento (ConcluÃ­das)</h3>
                <div class="value">R$ <?php echo number_format($dados_relatorio['resumo']['faturamento'], 2, ',', '.'); ?></div>
            </div>
            <div class="summary-card">
                <h3>Lucro Bruto</h3>
                <div class="value">R$ <?php echo number_format($dados_relatorio['resumo']['lucro_bruto'], 2, ',', '.'); ?></div>
            </div>
            <div class="summary-card">
                <h3>Margem MÃ©dia</h3>
                <div class="value"><?php echo number_format($dados_relatorio['resumo']['margem_media'], 1, ',', '.'); ?>%</div>
            </div>
        <?php elseif ($tipo_relatorio == 'produtos_vendidos'): ?>
            <div class="summary-card">
                <h3>Faturamento Total</h3>
                <div class="value">R$ <?php echo number_format($dados_relatorio['resumo']['total_faturamento'], 2, ',', '.'); ?></div>
            </div>
            <div class="summary-card">
                <h3>Lucro Total</h3>
                <div class="value">R$ <?php echo number_format($dados_relatorio['resumo']['total_lucro'], 2, ',', '.'); ?></div>
            </div>
            <div class="summary-card">
                <h3>Margem Geral</h3>
                <div class="value"><?php echo number_format($dados_relatorio['resumo']['margem_geral'], 1, ',', '.'); ?>%</div>
            </div>
            <div class="summary-card">
                <h3>Produtos Distintos</h3>
                <div class="value"><?php echo $dados_relatorio['resumo']['total_produtos']; ?></div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Tabela de dados -->
    <table>
        <thead>
            <tr>
                <?php if($tipo_relatorio == 'lucro_por_empresa'): ?>
                    <th>Empresa Representada</th>
                    <th>NÂº de Vendas</th>
                    <th>Itens Vendidos</th>
                    <th>Faturamento</th>
                    <th>Lucro Bruto</th>
                    <th>Margem</th>
                <?php elseif($tipo_relatorio == 'vendas_geral'): ?>
                    <th>Venda</th>
                    <th>Data</th>
                    <th>Cliente</th>
                    <th>CPF/CNPJ</th>
                    <th>Faturamento</th>
                    <th>Lucro</th>
                    <th>Margem</th>
                    <th>Status</th>
                <?php elseif($tipo_relatorio == 'produtos_vendidos'): ?>
                    <th>Produto</th>
                    <th>Empresa</th>
                    <th>Qtd.</th>
                    <th>Faturamento</th>
                    <th>Lucro</th>
                    <th>Margem</th>
                <?php endif; ?>
            </tr>
        </thead>
        <tbody>
            <?php 
            $total_faturamento = 0;
            $total_lucro = 0;
            foreach ($dados_relatorio['dados'] as $row): 
                if($tipo_relatorio == 'vendas_geral' && $row['status_venda'] == 'concluida') {
                    $total_faturamento += $row['valor_total'];
                    $total_lucro += $row['lucro_total'];
                }
            ?>
                <tr>
                <?php if($tipo_relatorio == 'lucro_por_empresa'): ?>
                    <td><?php echo htmlspecialchars($row['nome_empresa']); ?></td>
                    <td class="text-center"><?php echo $row['total_vendas']; ?></td>
                    <td class="text-center"><?php echo $row['total_itens']; ?></td>
                    <td class="text-right">R$ <?php echo number_format($row['total_faturado'], 2, ',', '.'); ?></td>
                    <td class="text-right">R$ <?php echo number_format($row['total_lucro'], 2, ',', '.'); ?></td>
                    <td class="text-center"><?php echo $row['total_faturado'] > 0 ? number_format(($row['total_lucro'] / $row['total_faturado']) * 100, 1) . '%' : 'N/A'; ?></td>
                <?php elseif($tipo_relatorio == 'vendas_geral'): ?>
                    <td class="text-center">#<?php echo $row['id']; ?></td>
                    <td class="text-center"><?php echo date('d/m/Y', strtotime($row['data_venda'])); ?></td>
                    <td><?php echo htmlspecialchars($row['cliente_nome']); ?></td>
                    <td class="text-center"><?php echo htmlspecialchars($row['cpf_cnpj'] ?? 'N/A'); ?></td>
                    <td class="text-right">R$ <?php echo number_format($row['valor_total'], 2, ',', '.'); ?></td>
                    <td class="text-right">R$ <?php echo number_format($row['lucro_total'], 2, ',', '.'); ?></td>
                    <td class="text-center"><?php echo $row['valor_total'] > 0 ? number_format(($row['lucro_total'] / $row['valor_total']) * 100, 1) : 0; ?>%</td>
                    <td class="text-center"><?php echo ucfirst($row['status_venda']); ?></td>
                <?php elseif($tipo_relatorio == 'produtos_vendidos'): ?>
                    <td><?php echo htmlspecialchars($row['produto_nome']); ?></td>
                    <td><?php echo htmlspecialchars($row['empresa_nome']); ?></td>
                    <td class="text-center"><?php echo $row['total_vendido']; ?></td>
                    <td class="text-right">R$ <?php echo number_format($row['total_faturado'], 2, ',', '.'); ?></td>
                    <td class="text-right">R$ <?php echo number_format($row['total_lucro'], 2, ',', '.'); ?></td>
                    <td class="text-center"><?php echo $row['total_faturado'] > 0 ? number_format(($row['total_lucro'] / $row['total_faturado']) * 100, 1) . '%' : 'N/A'; ?></td>
                <?php endif; ?>
                </tr>
            <?php endforeach; ?>
            
            <?php if($tipo_relatorio == 'vendas_geral' && $total_faturamento > 0): ?>
                <tr class="total-row">
                    <td colspan="4" class="text-right"><strong>TOTAIS (Vendas ConcluÃ­das):</strong></td>
                    <td class="text-right"><strong>R$ <?php echo number_format($total_faturamento, 2, ',', '.'); ?></strong></td>
                    <td class="text-right"><strong>R$ <?php echo number_format($total_lucro, 2, ',', '.'); ?></strong></td>
                    <td class="text-center"><strong><?php echo number_format($total_faturamento > 0 ? ($total_lucro / $total_faturamento) * 100 : 0, 1); ?>%</strong></td>
                    <td></td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <div class="commission-info">
        <h3>INFORMAÃ‡Ã•ES PARA PAGAMENTO DE COMISSÃ•ES</h3>
        <div class="info-box">
            <div class="info-row">
                <strong>PerÃ­odo de ReferÃªncia:</strong>
                <span><?php echo $periodo; ?></span>
            </div>
            <?php if ($tipo_relatorio == 'lucro_por_empresa'): ?>
                <div class="info-row">
                    <strong>Total Bruto Faturado:</strong>
                    <span>R$ <?php echo number_format($dados_relatorio['resumo']['faturamento_geral'], 2, ',', '.'); ?></span>
                </div>
                <div class="info-row">
                    <strong>Lucro Total Obtido:</strong>
                    <span>R$ <?php echo number_format($dados_relatorio['resumo']['lucro_geral'], 2, ',', '.'); ?></span>
                </div>
                <div class="info-row">
                    <strong>NÃºmero de Empresas Ativas:</strong>
                    <span><?php echo $dados_relatorio['resumo']['empresas_ativas']; ?></span>
                </div>
            <?php elseif ($tipo_relatorio == 'vendas_geral'): ?>
                <div class="info-row">
                    <strong>Faturamento Total (ConcluÃ­das):</strong>
                    <span>R$ <?php echo number_format($dados_relatorio['resumo']['faturamento'], 2, ',', '.'); ?></span>
                </div>
                <div class="info-row">
                    <strong>Lucro Total Obtido:</strong>
                    <span>R$ <?php echo number_format($dados_relatorio['resumo']['lucro_bruto'], 2, ',', '.'); ?></span>
                </div>
                <div class="info-row">
                    <strong>NÃºmero de Vendas:</strong>
                    <span><?php echo $dados_relatorio['resumo']['total_vendas']; ?></span>
                </div>
            <?php elseif ($tipo_relatorio == 'produtos_vendidos'): ?>
                <div class="info-row">
                    <strong>Faturamento Total:</strong>
                    <span>R$ <?php echo number_format($dados_relatorio['resumo']['total_faturamento'], 2, ',', '.'); ?></span>
                </div>
                <div class="info-row">
                    <strong>Lucro Total Obtido:</strong>
                    <span>R$ <?php echo number_format($dados_relatorio['resumo']['total_lucro'], 2, ',', '.'); ?></span>
                </div>
                <div class="info-row">
                    <strong>Produtos Diferentes Vendidos:</strong>
                    <span><?php echo $dados_relatorio['resumo']['total_produtos']; ?></span>
                </div>
            <?php endif; ?>
        </div>
        <p><em><strong>ObservaÃ§Ã£o:</strong> Este relatÃ³rio apresenta todas as vendas realizadas no perÃ­odo para verificaÃ§Ã£o e cÃ¡lculo de comissÃµes conforme acordo comercial estabelecido.</em></p>
    </div>

    <div class="footer">
        <p><strong>RelatÃ³rio gerado em:</strong> <?php echo date('d/m/Y H:i:s'); ?></p>
        <p><strong>PÃ¡gina:</strong> 1 de 1</p>
    </div>

    <div class="no-print" style="margin-top: 30px; text-align: center;">
        <button onclick="window.print()" style="background-color: #007bff; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer; margin-right: 10px;">
            ðŸ–¨ï¸ Imprimir/Salvar PDF
        </button>
        <button onclick="window.close()" style="background-color: #6c757d; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer;">
            âŒ Fechar
        </button>
    </div>

    <script>
        // Auto-abrir a janela de impressÃ£o quando a pÃ¡gina carregar
        window.addEventListener('load', function() {
            setTimeout(function() {
                window.print();
            }, 500);
        });
    </script>
</body>
</html>
<?php
$conn->close();
?>

