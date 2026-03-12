<?php
/**
 * Arquivo de teste para verificar as melhorias no PDF de orçamento
 * com muitos produtos
 */

// Simulação de dados para teste
session_start();
$_SESSION["loggedin"] = true;

// Simulação de um orçamento com muitos produtos
$orcamento_simulado = [
    'id' => 123,
    'cliente_nome' => 'Empresa Teste Ltda',
    'cliente_email' => 'teste@empresa.com',
    'telefone' => '(11) 99999-9999',
    'endereco' => 'Rua das Flores, 123 - Centro',
    'cidade' => 'São Paulo',
    'estado' => 'SP',
    'cep' => '01234-567',
    'cpf_cnpj' => '12.345.678/0001-90',
    'tipo_pessoa' => 'juridica',
    'nome_fantasia' => 'Empresa Teste',
    'inscricao_estadual' => '123.456.789.123',
    'data_orcamento' => date('Y-m-d'),
    'status_orcamento' => 'pendente',
    'observacoes' => 'Este é um teste de orçamento com muitos produtos para verificar se o PDF está se comportando corretamente com quebras de página adequadas.',
    'valor_total' => 0
];

// Simulação de 25 produtos para testar o comportamento com muitos itens
$itens_simulados = [];
$valor_total = 0;

for ($i = 1; $i <= 25; $i++) {
    $quantidade = rand(1, 5);
    $preco_unitario = rand(50, 500) + (rand(0, 99) / 100);
    $subtotal = $quantidade * $preco_unitario;
    $valor_total += $subtotal;
    
    $itens_simulados[] = [
        'produto_nome' => "Produto de Teste Número $i - Descrição Longa Para Verificar Quebra de Linha",
        'codigo' => "PROD" . str_pad($i, 3, '0', STR_PAD_LEFT),
        'descricao' => "Descrição detalhada do produto $i com várias características importantes para o cliente.",
        'nome_empresa' => "Empresa " . chr(65 + ($i % 5)),
        'logo_empresa' => '',
        'quantidade' => $quantidade,
        'preco_unitario' => $preco_unitario
    ];
}

$orcamento_simulado['valor_total'] = $valor_total;

echo "<h1>Teste do PDF Melhorado</h1>";
echo "<p>Este teste criará um PDF com {$i} produtos para verificar as melhorias implementadas.</p>";
echo "<p>Valor total simulado: R$ " . number_format($valor_total, 2, ',', '.') . "</p>";
echo "<p><a href='#' onclick='gerarPDFTeste()' style='background-color: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Gerar PDF de Teste</a></p>";

echo "<script>
function gerarPDFTeste() {
    // Simula a chamada do gerador de PDF
    var form = document.createElement('form');
    form.method = 'POST';
    form.action = 'gerar_pdf_teste_interno.php';
    document.body.appendChild(form);
    form.submit();
}
</script>";

// Salva os dados simulados em sessão para uso no gerador
$_SESSION['teste_orcamento'] = $orcamento_simulado;
$_SESSION['teste_itens'] = $itens_simulados;

?>
<!DOCTYPE html>
<html>
<head>
    <title>Teste PDF Melhorado</title>
    <meta charset="utf-8">
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; }
        .info { background: #e3f2fd; padding: 15px; border-radius: 5px; margin: 20px 0; }
        .success { background: #e8f5e8; padding: 15px; border-radius: 5px; margin: 20px 0; }
    </style>
</head>
<body>
    <div class="info">
        <h3>Melhorias Implementadas:</h3>
        <ul>
            <li>✅ Verificação adequada de espaço na página antes de adicionar novos itens</li>
            <li>✅ Redesenho automático do cabeçalho da tabela em páginas novas</li>
            <li>✅ Ajuste dinâmico do tamanho da fonte baseado na quantidade de produtos</li>
            <li>✅ Larguras de coluna otimizadas para muitos itens</li>
            <li>✅ Garantia de que o valor total sempre aparece em posição adequada</li>
            <li>✅ Melhor controle de quebra de página para observações e termos</li>
            <li>✅ Limitação da altura máxima das células para evitar distorções</li>
        </ul>
    </div>
    
    <div class="success">
        <h3>Benefícios:</h3>
        <ul>
            <li>PDFs organizados mesmo com 20+ produtos</li>
            <li>Valor total sempre visível</li>
            <li>Texto não cortado ou sobreposto</li>
            <li>Melhor aproveitamento do espaço da página</li>
            <li>Quebras de página inteligentes</li>
        </ul>
    </div>
</body>
</html>
