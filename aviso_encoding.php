<?php
/**
 * Página de Aviso Rápido - Encoding de Produtos
 * Mostra um banner na primeira vez que o usuário acessa após detectar produtos com encoding errado
 */

require_once 'includes/session_bootstrap.php';

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: index.php");
    exit;
}

require_once 'includes/db_connect.php';

// Verificar se há produtos com encoding errado
$check = $conn->query("
    SELECT COUNT(*) as total
    FROM produtos 
    WHERE nome LIKE '%Ã%' 
       OR nome LIKE '%Â%' 
       OR descricao LIKE '%Ã%' 
       OR descricao LIKE '%Â%'
");

$row = $check->fetch_assoc();
$tem_problema = $row['total'] > 0;

if (!$tem_problema) {
    // Se não há problema, redireciona para o dashboard
    header("location: dashboard.php");
    exit;
}

include_once 'includes/header.php';
?>

<div class="container-fluid" style="max-width: 900px; margin-top: 30px;">
    <div class="alert alert-warning alert-dismissible fade show" role="alert" style="border-left: 4px solid #ffc107;">
        <h4 class="alert-heading">
            <i class="fas fa-exclamation-triangle"></i> Detectado Problema de Encoding em Produtos
        </h4>
        <p>
            Foram encontrados <strong><?php echo $row['total']; ?> produtos</strong> com caracteres especiais incorretos 
            (acentos e símbolos exibindo como Ã¡, Ã•, Â°, etc).
        </p>
        <hr>
        <p class="mb-0">
            <strong>✅ Solução disponível:</strong> Clique no botão abaixo para corrigir automaticamente todos os produtos.
        </p>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0">
                        <i class="fas fa-tools"></i> Corrigir Encoding dos Produtos
                    </h5>
                </div>
                <div class="card-body">
                    <p>
                        O sistema detectou que alguns produtos têm caracteres especiais com encoding incorreto. 
                        Isso acontece quando há conflito de UTF-8 entre o banco de dados e a aplicação.
                    </p>
                    
                    <h6>Exemplos de problemas encontrados:</h6>
                    <ul>
                        <li><code>Ã¡lcool</code> → <code>Álcool</code></li>
                        <li><code>70Â°</code> → <code>70°</code></li>
                        <li><code>CÃ¡lix</code> → <code>Cálix</code></li>
                        <li><code>DispensadorÂ</code> → <code>Dispensador</code></li>
                    </ul>

                    <p style="margin-top: 20px;">
                        <strong>⚡ O que o script fará:</strong>
                    </p>
                    <ol>
                        <li>Detectar todos os produtos com encoding incorreto</li>
                        <li>Converter os caracteres para UTF-8 correto</li>
                        <li>Atualizar o banco de dados automaticamente</li>
                        <li>Mostrar cada produto antes e depois</li>
                    </ol>

                    <div class="alert alert-info" style="margin-top: 20px;">
                        <i class="fas fa-info-circle"></i> <strong>Seguro:</strong> 
                        O script apenas converte a codificação dos textos existentes. Nenhum dado será deletado.
                    </div>

                    <a href="corrigir_encoding_produtos.php" class="btn btn-success btn-lg" style="margin-top: 20px;">
                        <i class="fas fa-magic"></i> Corrigir Produtos Agora
                    </a>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card shadow-sm">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-info-circle"></i> Informações
                    </h5>
                </div>
                <div class="card-body">
                    <p><strong>Produtos afetados:</strong></p>
                    <p style="font-size: 2em; color: #ffc107; font-weight: bold;">
                        <?php echo htmlspecialchars($row['total']); ?>
                    </p>

                    <hr>

                    <p><strong>O que será corrigido:</strong></p>
                    <small>
                        ✓ Acentos especiais<br>
                        ✓ Símbolos especiais<br>
                        ✓ Caracteres em descrição<br>
                        ✓ Outros campos de texto
                    </small>

                    <hr>

                    <p><strong>Tempo estimado:</strong></p>
                    <small>
                        < 1 minuto (depende do número de produtos)
                    </small>

                    <hr>

                    <p><strong>Próximos passos:</strong></p>
                    <ol style="font-size: 0.9em;">
                        <li>Clique em "Corrigir Produtos Agora"</li>
                        <li>Aguarde a conclusão</li>
                        <li>Verifique os produtos na lista</li>
                        <li>Teste ao criar orçamentos</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <div style="margin-top: 30px; margin-bottom: 30px;">
        <a href="javascript:history.back()" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Voltar
        </a>
    </div>
</div>

<?php include_once 'includes/footer.php'; ?>
