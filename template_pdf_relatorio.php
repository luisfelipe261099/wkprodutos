<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($titulo_relatorio); ?> - PDF</title>
    <style>
        * {
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            font-size: 12px;
            line-height: 1.5;
            margin: 0;
            padding: 20px;
            color: #2c3e50;
            background-color: #ffffff;
        }
        
        .header {
            text-align: center;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 25px 20px;
            margin: -20px -20px 25px -20px;
            border-radius: 0 0 12px 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        
        .header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: 600;
            letter-spacing: 0.5px;
        }
        
        .header h2 {
            margin: 8px 0 0 0;
            font-size: 14px;
            font-weight: 300;
            opacity: 0.9;
        }
        
        .company-info {
            background-color: #f8f9fa;
            border-left: 4px solid #667eea;
            padding: 18px;
            margin-bottom: 25px;
            border-radius: 0 8px 8px 0;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }
        
        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            align-items: center;
        }
        
        .info-row:last-child {
            margin-bottom: 0;
        }
        
        .info-label {
            font-weight: 600;
            color: #495057;
            min-width: 150px;
        }
        
        .info-value {
            color: #2c3e50;
            font-weight: 500;
        }
        
        .summary-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 12px;
            margin-bottom: 25px;
        }
        
        @media (max-width: 900px) {
            .summary-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        @media (max-width: 500px) {
            .summary-grid {
                grid-template-columns: 1fr;
            }
        }
        
        .summary-card {
            background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 12px 8px;
            text-align: center;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            transition: transform 0.2s;
            min-height: 100px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        
        .summary-card:hover {
            transform: translateY(-2px);
        }
        
        .summary-card h3 {
            margin: 0 0 10px 0;
            font-size: 11px;
            color: #6c757d;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 600;
            line-height: 1.2;
        }
        
        .summary-card .value {
            font-size: 20px;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 5px;
            line-height: 1.1;
        }
        
        .summary-card.success .value { color: #28a745; }
        .summary-card.info .value { color: #17a2b8; }
        .summary-card.warning .value { color: #ffc107; }
        .summary-card.primary .value { color: #667eea; }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 25px;
            font-size: 11px;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 3px 10px rgba(0,0,0,0.08);
            background-color: #ffffff;
        }
        
        th, td {
            padding: 10px 8px;
            text-align: left;
            border-bottom: 1px solid #e9ecef;
            vertical-align: middle;
        }
        
        th {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            font-weight: 600;
            text-align: center;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            font-size: 10px;
            border: none;
        }
        
        tbody tr:nth-child(even) {
            background-color: #f8f9fa;
        }
        
        tbody tr:hover {
            background-color: #e9ecef;
            transition: background-color 0.2s ease;
        }
        
        tbody tr:last-child td {
            border-bottom: none;
        }
        
        .text-right {
            text-align: right;
        }
        
        .text-center {
            text-align: center;
        }
        
        .total-row {
            background: linear-gradient(135deg, #e9ecef 0%, #dee2e6 100%);
            font-weight: bold;
            border-top: 2px solid #667eea;
        }
        
        .page-break-helper {
            height: 1px;
            margin: 20px 0;
            page-break-after: always !important;
            break-after: page !important;
            /* FORÇA quebra de página SEMPRE */
            min-height: 1px;
            page-break-inside: avoid;
            visibility: hidden;
        }
        
        .payment-section {
            background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
            border: 2px solid #28a745;
            border-radius: 10px;
            padding: 20px;
            margin: 25px 0;
            box-shadow: 0 3px 10px rgba(255, 214, 10, 0.15);
            page-break-inside: avoid !important;
            page-break-before: always !important;
            page-break-after: avoid;
            break-inside: avoid !important;
            -webkit-column-break-inside: avoid;
            orphans: 10;
            widows: 10;
            /* SEMPRE força uma nova página */
            min-height: 250px;
        }
        
        .payment-section h3 {
            margin: 0 0 18px 0;
            color: #155724;
            font-size: 16px;
            text-align: center;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .bank-info {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 12px;
            margin-bottom: 20px;
            page-break-inside: avoid !important;
            break-inside: avoid;
        }
        
        .bank-item {
            background-color: #ffffff;
            border: 1px solid #b3d4fc;
            border-radius: 8px;
            padding: 12px 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            min-height: 50px;
            box-sizing: border-box;
            page-break-inside: avoid !important;
            break-inside: avoid;
        }
        
        .bank-label {
            font-weight: 600;
            color: #495057;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            white-space: nowrap;
            margin-right: 10px;
        }
        
        .bank-value {
            font-weight: 700;
            color: #155724;
            font-size: 12px;
            font-family: 'Courier New', monospace;
            text-align: right;
            word-break: break-all;
            flex: 1;
        }
        
        @media (max-width: 600px) {
            .bank-info {
                grid-template-columns: 1fr;
            }
        }
        
        .commission-note {
            background-color: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 6px;
            padding: 12px;
            margin-top: 15px;
            text-align: center;
        }
        
        .commission-note p {
            margin: 0;
            color: #856404;
            font-style: italic;
            font-size: 12px;
            line-height: 1.5;
        }
        
        .commission-info {
            background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%);
            border: 2px solid #ffd60a;
            border-radius: 10px;
            padding: 20px;
            margin: 25px 0;
            box-shadow: 0 3px 10px rgba(255, 214, 10, 0.15);
        }
        
        .commission-info h3 {
            margin: 0 0 18px 0;
            color: #856404;
            font-size: 16px;
            text-align: center;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .commission-info .info-box {
            background-color: transparent;
            border: none;
            padding: 0;
            margin: 0;
        }
        
        .commission-info .info-row {
            margin-bottom: 12px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0;
            border-bottom: 1px solid rgba(133, 100, 4, 0.2);
        }
        
        .commission-info .info-row:last-child {
            border-bottom: none;
            margin-bottom: 0;
        }
        
        .commission-info strong {
            color: #856404;
            font-weight: 600;
        }
        
        .commission-info span {
            color: #856404;
            font-weight: 700;
            font-family: 'Courier New', monospace;
        }
        
        .commission-info p {
            margin: 15px 0 0 0;
            color: #856404;
            font-style: italic;
            font-size: 12px;
            text-align: center;
            line-height: 1.6;
        }
        
        .footer {
            margin-top: 30px;
            padding-top: 15px;
            border-top: 2px solid #e9ecef;
            text-align: center;
            color: #6c757d;
            font-size: 10px;
        }
        
        /* Regras de impressão otimizadas */
        @media print {
            body { 
                margin: 0 !important;
                padding: 15px !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
                font-size: 11px !important;
            }
            
            .header {
                margin: -15px -15px 20px -15px !important;
                padding: 20px !important;
            }
            
            .header h1 {
                font-size: 20px !important;
            }
            
            .header h2 {
                font-size: 12px !important;
            }
            
            .summary-grid {
                grid-template-columns: repeat(4, 1fr) !important;
                gap: 8px !important;
                margin-bottom: 20px !important;
            }
            
            .summary-card {
                padding: 8px 6px !important;
                min-height: 70px !important;
            }
            
            .summary-card h3 {
                font-size: 9px !important;
                margin-bottom: 6px !important;
            }
            
            .summary-card .value {
                font-size: 16px !important;
            }
            
            .summary-card:hover {
                transform: none;
            }
            
            .page-break-helper {
                height: 0 !important;
                margin: 0 !important;
                page-break-after: always !important;
                break-after: page !important;
                /* FORÇA quebra de página SEMPRE */
                min-height: 1px !important;
                page-break-inside: avoid !important;
                visibility: hidden;
                display: block !important;
            }
            
            .payment-section {
                page-break-inside: avoid !important;
                page-break-before: always !important;
                page-break-after: auto;
                break-inside: avoid !important;
                -webkit-column-break-inside: avoid;
                margin: 15px 0 !important;
                padding: 15px !important;
                orphans: 10;
                widows: 10;
                display: block !important;
                min-height: 200px !important;
                /* SEMPRE força quebra de página */
                margin-top: 20px !important;
            }
            
            .payment-section h3 {
                font-size: 14px !important;
                margin-bottom: 12px !important;
                page-break-after: avoid !important;
            }
            
            .bank-info {
                grid-template-columns: repeat(2, 1fr) !important;
                gap: 8px !important;
                page-break-inside: avoid !important;
                break-inside: avoid !important;
                display: grid !important;
            }
            
            .bank-item {
                page-break-inside: avoid !important;
                break-inside: avoid !important;
                padding: 8px 10px !important;
                min-height: 40px !important;
                display: flex !important;
            }
            
            .bank-label {
                font-size: 9px !important;
            }
            
            .bank-value {
                font-size: 10px !important;
            }
            
            .commission-note {
                padding: 8px !important;
                margin-top: 10px !important;
                page-break-inside: avoid !important;
                break-inside: avoid !important;
            }
            
            .commission-note p {
                font-size: 10px !important;
                page-break-inside: avoid !important;
            }
            
            /* Força toda a seção bancária como um bloco único */
            .payment-section * {
                page-break-inside: avoid !important;
                break-inside: avoid !important;
            }
            
            /* Alternativa: se o grid causar problemas, use flexbox */
            @media print {
                .bank-info {
                    display: flex !important;
                    flex-wrap: wrap !important;
                    justify-content: space-between !important;
                }
                
                .bank-item {
                    flex: 0 0 48% !important;
                    margin-bottom: 8px !important;
                }
            }
            
            .commission-info {
                page-break-inside: avoid;
                margin: 15px 0 !important;
                padding: 15px !important;
            }
            
            .commission-info h3 {
                font-size: 14px !important;
                margin-bottom: 12px !important;
            }
            
            .commission-info .info-row {
                margin-bottom: 8px !important;
                padding: 4px 0 !important;
            }
            
            .commission-info strong,
            .commission-info span {
                font-size: 10px !important;
            }
            
            .commission-info p {
                font-size: 9px !important;
                margin-top: 10px !important;
            }
            
            table {
                font-size: 9px !important;
            }
            
            th {
                padding: 6px 4px !important;
                font-size: 8px !important;
            }
            
            td {
                padding: 6px 4px !important;
            }
            
            tr:hover {
                background-color: inherit;
            }
            
            .footer {
                margin-top: 20px !important;
                font-size: 8px !important;
            }
            
            .no-print {
                display: none !important;
            }
        }
        
        /* Regras específicas de impressão/PDF */
        @media print {
            @page {
                size: A4;
                margin: 2cm;
                orphans: 4;
                widows: 4;
            }
            
            /* Força quebra de página antes da seção bancária se necessário */
            .page-break-helper {
                page-break-after: auto;
                height: 200px;
                visibility: hidden;
            }
            
            .page-break-helper:last-child {
                page-break-after: always;
            }
            
            /* Regra que força nova página SEMPRE para a seção bancária */
            .payment-section {
                /* SEMPRE vai para próxima página */
                min-height: 250px;
                page-break-before: always !important;
                break-before: page !important;
            }
            
            /* Hack CSS: se a seção anterior tem mais de 70% da altura da página, força quebra */
            table + .page-break-helper {
                page-break-before: auto;
                page-break-after: always;
                height: 0;
                margin: 0;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1><?php echo htmlspecialchars($titulo_relatorio); ?></h1>
        <h2>Relatório Comercial para Prestação de Contas</h2>
    </div>

    <div class="company-info">
        <div class="info-row">
            <span class="info-label">📅 Período de Análise:</span>
            <span class="info-value"><?php echo $periodo; ?></span>
        </div>
        <div class="info-row">
            <span class="info-label">👤 Representante Comercial:</span>
            <span class="info-value"><?php echo htmlspecialchars($usuario['nome'] ?? 'N/A'); ?></span>
        </div>
        <div class="info-row">
            <span class="info-label">🏢 CNPJ:</span>
            <span class="info-value">30.459.625/0001-87</span>
        </div>
        <div class="info-row">
            <span class="info-label">⏰ Gerado em:</span>
            <span class="info-value"><?php echo date('d/m/Y H:i:s'); ?></span>
        </div>
    </div>

    <div class="summary-grid">
        <?php if ($tipo_relatorio == 'lucro_por_empresa'): ?>
            <div class="summary-card success">
                <h3>Faturamento Total</h3>
                <div class="value">R$ <?php echo number_format($dados_relatorio['resumo']['faturamento_geral'], 2, ',', '.'); ?></div>
            </div>
            <div class="summary-card info">
                <h3>Lucro Bruto Total</h3>
                <div class="value">R$ <?php echo number_format($dados_relatorio['resumo']['lucro_geral'], 2, ',', '.'); ?></div>
            </div>
            <div class="summary-card primary">
                <h3>Empresas com Vendas</h3>
                <div class="value"><?php echo $dados_relatorio['resumo']['empresas_ativas']; ?></div>
            </div>
            <div class="summary-card warning">
                <h3>Margem Média</h3>
                <div class="value"><?php echo number_format($dados_relatorio['resumo']['faturamento_geral'] > 0 ? ($dados_relatorio['resumo']['lucro_geral'] / $dados_relatorio['resumo']['faturamento_geral']) * 100 : 0, 1, ',', '.'); ?>%</div>
            </div>
        <?php elseif ($tipo_relatorio == 'vendas_geral'): ?>
            <div class="summary-card primary">
                <h3>Total de Vendas</h3>
                <div class="value"><?php echo $dados_relatorio['resumo']['total_vendas']; ?></div>
            </div>
            <div class="summary-card success">
                <h3>Faturamento (Concluídas)</h3>
                <div class="value">R$ <?php echo number_format($dados_relatorio['resumo']['faturamento'], 2, ',', '.'); ?></div>
            </div>
            <div class="summary-card info">
                <h3>Lucro Bruto</h3>
                <div class="value">R$ <?php echo number_format($dados_relatorio['resumo']['lucro_bruto'], 2, ',', '.'); ?></div>
            </div>
            <div class="summary-card warning">
                <h3>Margem Média</h3>
                <div class="value"><?php echo number_format($dados_relatorio['resumo']['margem_media'], 1, ',', '.'); ?>%</div>
            </div>
        <?php elseif ($tipo_relatorio == 'produtos_vendidos'): ?>
            <div class="summary-card success">
                <h3>Faturamento Total</h3>
                <div class="value">R$ <?php echo number_format($dados_relatorio['resumo']['total_faturamento'], 2, ',', '.'); ?></div>
            </div>
            <div class="summary-card info">
                <h3>Lucro Total</h3>
                <div class="value">R$ <?php echo number_format($dados_relatorio['resumo']['total_lucro'], 2, ',', '.'); ?></div>
            </div>
            <div class="summary-card warning">
                <h3>Margem Geral</h3>
                <div class="value"><?php echo number_format($dados_relatorio['resumo']['margem_geral'], 1, ',', '.'); ?>%</div>
            </div>
            <div class="summary-card primary">
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
                    <th>Nº de Vendas</th>
                    <th>Itens Vendidos</th>
                    <th>Faturamento</th>
                    <th>Lucro Bruto</th>
                    <th>Margem</th>
                <?php elseif($tipo_relatorio == 'vendas_geral'): ?>
                    <th>Venda</th>
                    <th>Data</th>
                    <th>Cliente</th>
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
                    <td colspan="3" class="text-right"><strong>TOTAIS (Vendas Concluídas):</strong></td>
                    <td class="text-right"><strong>R$ <?php echo number_format($total_faturamento, 2, ',', '.'); ?></strong></td>
                    <td class="text-right"><strong>R$ <?php echo number_format($total_lucro, 2, ',', '.'); ?></strong></td>
                    <td class="text-center"><strong><?php echo number_format($total_faturamento > 0 ? ($total_lucro / $total_faturamento) * 100 : 0, 1); ?>%</strong></td>
                    &nbsp;
                    &nbsp;
                    &nbsp;
                    <td></td>
                </tr>
            <?php endif; ?>
        </tbody>
        &nbsp;
        &nbsp;
    </table>

    <!-- Div para forçar quebra de página antes das informações bancárias se necessário -->
    <div class="page-break-helper"></div>

    <!-- Seção de Informações Bancárias para Pagamento -->
    <div class="payment-section">
        <h3>💳 Informações Bancárias para Pagamento</h3>
        
        <div class="bank-info">
            <div class="bank-item">
                <span class="bank-label">🏦 Banco:</span>
                <span class="bank-value">INTER - 077</span>
            </div>
            <div class="bank-item">
                <span class="bank-label">👤 Beneficiário:</span>
                <span class="bank-value">KARLA WOLLINGER DOS SANTOS</span>
            </div>
            <div class="bank-item">
                <span class="bank-label">🏢 CNPJ:</span>
                <span class="bank-value">30.459.625/0001-87</span>
            </div>
            <div class="bank-item">
                <span class="bank-label">🏪 Agência:</span>
                <span class="bank-value">0001</span>
            </div>
            <div class="bank-item">
                <span class="bank-label">💰 Conta Corrente:</span>
                <span class="bank-value">44337759-6</span>
            </div>
            <div class="bank-item">
                <span class="bank-label">🔑 Chave PIX:</span>
                <span class="bank-value">30.459.625/0001-87</span>
            </div>
        </div>
        
        <div class="commission-note">
            <p><strong>🚀 Instruções para Pagamento:</strong> Utilize as informações bancárias acima para realizar o pagamento das comissões referentes ao período demonstrado neste relatório. Para pagamentos via PIX, utilize o CNPJ como chave. Mantenha este relatório como comprovante das transações comerciais realizadas.</p>
        </div>
    </div>

    <div class="commission-info">
        <h3>INFORMAÇÕES PARA PAGAMENTO DE COMISSÕES</h3>
        <div class="info-box">
            <div class="info-row">
                <strong>Período de Referência:</strong>
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
                    <strong>Número de Empresas Ativas:</strong>
                    <span><?php echo $dados_relatorio['resumo']['empresas_ativas']; ?></span>
                </div>
            <?php elseif ($tipo_relatorio == 'vendas_geral'): ?>
                <div class="info-row">
                    <strong>Faturamento Total (Concluídas):</strong>
                    <span>R$ <?php echo number_format($dados_relatorio['resumo']['faturamento'], 2, ',', '.'); ?></span>
                </div>
                <div class="info-row">
                    <strong>Lucro Total Obtido:</strong>
                    <span>R$ <?php echo number_format($dados_relatorio['resumo']['lucro_bruto'], 2, ',', '.'); ?></span>
                </div>
                <div class="info-row">
                    <strong>Número de Vendas:</strong>
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
        <p><em><strong>Observação:</strong> Este relatório apresenta todas as vendas realizadas no período para verificação e cálculo de comissões conforme acordo comercial estabelecido.</em></p>
    </div>

    <div class="footer">
        <p><strong>Relatório gerado em:</strong> <?php echo date('d/m/Y H:i:s'); ?></p>
        <p><strong>Total de registros:</strong> <?php echo count($dados_relatorio['dados']); ?></p>
    </div>

    <div class="no-print" style="margin-top: 30px; text-align: center;">
        <button onclick="window.print()" style="background-color: #007bff; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer; margin-right: 10px;">
            🖨️ Imprimir/Salvar PDF
        </button>
        <button onclick="window.close()" style="background-color: #6c757d; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer;">
            ❌ Fechar
        </button>
    </div>

    <script>
        // Função para verificar se a seção bancária precisa de quebra de página
        function adjustBankingSectionPageBreak() {
            const paymentSection = document.querySelector('.payment-section');
            const pageBreakHelper = document.querySelector('.page-break-helper');
            
            if (paymentSection && pageBreakHelper) {
                const rect = paymentSection.getBoundingClientRect();
                const viewportHeight = window.innerHeight;
                const sectionHeight = rect.height;
                const sectionTop = rect.top;
                
                // Se a seção bancária está muito próxima do final da página (menos de 300px de espaço)
                // ou se a seção seria cortada, força uma quebra de página
                if (sectionTop > (viewportHeight - 300) || (sectionTop + sectionHeight) > viewportHeight) {
                    pageBreakHelper.style.pageBreakAfter = 'always';
                    pageBreakHelper.style.breakAfter = 'page';
                    pageBreakHelper.style.height = '1px';
                    pageBreakHelper.style.display = 'block';
                    
                    // Adiciona uma margem top maior na seção bancária
                    paymentSection.style.marginTop = '20px';
                    paymentSection.style.pageBreakBefore = 'auto';
                }
            }
        }
        
        // Auto-abrir a janela de impressão quando a página carregar
        window.addEventListener('load', function() {
            // Ajusta a quebra de página primeiro
            adjustBankingSectionPageBreak();
            
            setTimeout(function() {
                window.print();
            }, 500);
        });
        
        // Reajusta quando a janela é redimensionada
        window.addEventListener('resize', adjustBankingSectionPageBreak);
    </script>
</body>
</html>
