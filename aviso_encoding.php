<?php
require_once __DIR__ . '/includes/session_bootstrap.php';

// Verifica se o usuário está logado
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: index.php");
    exit;
}

// Processar dispensar por hoje
if (isset($_POST['dismiss_today'])) {
    $_SESSION['_encoding_alert_shown_'] = date('Y-m-d');
    unset($_SESSION['_has_encoding_issue_']);
    echo json_encode(['success' => true]);
    exit;
}

require_once __DIR__ . '/includes/db_connect.php';

$message = '';
$message_type = '';

// Processar correção se foi solicitada
if (isset($_POST['corrigir_encoding'])) {
    try {
        // Iniciar transação
        $conn->begin_transaction();
        
        $corrected = 0;
        $errors = 0;
        
        // Correções de encoding comuns
        $replacements = array(
            // Acentos básicos
            'Ã¡' => 'á', 'Ã©' => 'é', 'Ã­' => 'í', 'Ã³' => 'ó', 'Ãº' => 'ú',
            'Ã ' => 'à', 'Ã¨' => 'è', 'Ã¬' => 'ì', 'Ã²' => 'ò', 'Ã¹' => 'ù',
            'Ã¢' => 'â', 'Ãª' => 'ê', 'Ã®' => 'î', 'Ã´' => 'ô', 'Ã»' => 'û',
            'Ã£' => 'ã', 'Ã±' => 'ñ', 'Ãµ' => 'õ', 'Ã§' => 'ç',
            
            // Caracteres especiais comuns
            'Â°' => '°', 'Â´' => '´', 'Â¨' => '¨', 'Â¡' => '¡', 'Â¿' => '¿',
            'Â«' => '«', 'Â»' => '»', 'Â²' => '²', 'Â³' => '³', 'Â¹' => '¹',
            'Â½' => '½', 'Â¼' => '¼', 'Â¾' => '¾', 'Â±' => '±',
            
            // Aspas e símbolos
            'â€œ' => '"', 'â€' => '"', 'â€™' => "'", 'â€˜' => "'",
            'â€"' => '–', 'â€"' => '—', 'â€¦' => '…',
            
            // Remover caracteres problemáticos
            'â€‹' => '', 'ï»¿' => '', 'â€Š' => ' ', 'â€‰' => ' ',
            
            // Espaços múltiplos
            '  ' => ' ', '   ' => ' '
        );
        
        // Corrigir produtos
        $stmt = $conn->prepare("SELECT id, nome, descricao FROM produtos");
        $stmt->execute();
        $produtos = $stmt->get_result();
        
        while ($produto = $produtos->fetch_assoc()) {
            $nome_original = $produto['nome'];
            $desc_original = $produto['descricao'];
            
            $nome_corrigido = $nome_original;
            $desc_corrigida = $desc_original;
            
            // Aplicar correções
            foreach ($replacements as $wrong => $correct) {
                $nome_corrigido = str_replace($wrong, $correct, $nome_corrigido);
                $desc_corrigida = str_replace($wrong, $correct, $desc_corrigida);
            }
            
            // Verificar se houve mudança
            if ($nome_corrigido !== $nome_original || $desc_corrigida !== $desc_original) {
                $update_stmt = $conn->prepare("UPDATE produtos SET nome = ?, descricao = ? WHERE id = ?");
                if ($update_stmt->execute([$nome_corrigido, $desc_corrigida, $produto['id']])) {
                    $corrected++;
                } else {
                    $errors++;
                }
            }
        }
        
        // Corrigir clientes
        $stmt = $conn->prepare("SELECT id, nome, endereco FROM clientes");
        $stmt->execute();
        $clientes = $stmt->get_result();
        
        while ($cliente = $clientes->fetch_assoc()) {
            $nome_original = $cliente['nome'];
            $endereco_original = $cliente['endereco'];
            
            $nome_corrigido = $nome_original;
            $endereco_corrigido = $endereco_original;
            
            // Aplicar correções
            foreach ($replacements as $wrong => $correct) {
                $nome_corrigido = str_replace($wrong, $correct, $nome_corrigido);
                $endereco_corrigido = str_replace($wrong, $correct, $endereco_corrigido);
            }
            
            // Verificar se houve mudança
            if ($nome_corrigido !== $nome_original || $endereco_corrigido !== $endereco_original) {
                $update_stmt = $conn->prepare("UPDATE clientes SET nome = ?, endereco = ? WHERE id = ?");
                if ($update_stmt->execute([$nome_corrigido, $endereco_corrigido, $cliente['id']])) {
                    $corrected++;
                } else {
                    $errors++;
                }
            }
        }
        
        // Commit das alterações
        $conn->commit();
        
        // Limpar flag de problema de encoding
        unset($_SESSION['_has_encoding_issue_']);
        $_SESSION['_encoding_checked_'] = false; // Permitir nova verificação
        
        if ($corrected > 0) {
            $message = "Correção concluída! $corrected registros foram corrigidos.";
            if ($errors > 0) {
                $message .= " $errors erros ocorreram.";
            }
            $message_type = "success";
        } else {
            $message = "Nenhum problema de encoding foi encontrado.";
            $message_type = "info";
        }
        
    } catch (Exception $e) {
        // Rollback em caso de erro
        $conn->rollback();
        $message = "Erro ao corrigir encoding: " . $e->getMessage();
        $message_type = "danger";
    }
}

// Verificar problemas atuais
$problemas = [];
$stmt = $conn->query("
    SELECT 'produtos' as tabela, id, nome as texto, 'nome' as campo
    FROM produtos 
    WHERE nome LIKE '%Ã%' OR nome LIKE '%Â%' OR nome LIKE '%â€%'
    UNION ALL
    SELECT 'produtos' as tabela, id, descricao as texto, 'descricao' as campo
    FROM produtos 
    WHERE descricao LIKE '%Ã%' OR descricao LIKE '%Â%' OR descricao LIKE '%â€%'
    UNION ALL
    SELECT 'clientes' as tabela, id, nome as texto, 'nome' as campo
    FROM clientes 
    WHERE nome LIKE '%Ã%' OR nome LIKE '%Â%' OR nome LIKE '%â€%'
    UNION ALL
    SELECT 'clientes' as tabela, id, endereco as texto, 'endereco' as campo
    FROM clientes 
    WHERE endereco LIKE '%Ã%' OR endereco LIKE '%Â%' OR endereco LIKE '%â€%'
    LIMIT 50
");

if ($stmt) {
    while ($row = $stmt->fetch_assoc()) {
        $problemas[] = $row;
    }
}

$conn->close();

include_once __DIR__ . '/includes/header.php';
?>

<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h1 class="page-title"><i class="fas fa-tools me-2"></i>Correção de Encoding</h1>
        <p class="page-subtitle">Corrigir caracteres especiais incorretos no banco de dados</p>
    </div>
    <a href="dashboard.php" class="btn btn-secondary">
        <i class="fas fa-arrow-left me-1"></i> Voltar
    </a>
</div>

<?php if (!empty($message)): ?>
    <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
        <?php echo $message; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="row">
    <div class="col-lg-8">
        <div class="modern-card">
            <div class="modern-card-header">
                <h5 class="modern-card-title">
                    <i class="fas fa-exclamation-triangle text-warning me-2"></i>
                    Problemas Detectados
                </h5>
            </div>
            <div class="card-body-modern">
                <?php if (count($problemas) > 0): ?>
                    <div class="alert alert-warning">
                        <strong>Encontrados <?php echo count($problemas); ?> problemas de encoding</strong><br>
                        <small>Clique em "Corrigir Automaticamente" para resolver todos os problemas.</small>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Tabela</th>
                                    <th>ID</th>
                                    <th>Campo</th>
                                    <th>Texto com Problema</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($problemas as $problema): ?>
                                    <tr>
                                        <td><span class="badge bg-info"><?php echo htmlspecialchars($problema['tabela']); ?></span></td>
                                        <td><?php echo htmlspecialchars($problema['id']); ?></td>
                                        <td><?php echo htmlspecialchars($problema['campo']); ?></td>
                                        <td>
                                            <code style="font-size: 0.8rem; word-break: break-all;">
                                                <?php echo htmlspecialchars(substr($problema['texto'], 0, 100)); ?>
                                                <?php if (strlen($problema['texto']) > 100): ?>...<?php endif; ?>
                                            </code>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <form method="post" class="mt-3" onsubmit="return confirm('Tem certeza que deseja corrigir todos os problemas de encoding? Esta ação não pode ser desfeita.');">
                        <button type="submit" name="corrigir_encoding" class="btn btn-warning btn-lg">
                            <i class="fas fa-magic me-2"></i>Corrigir Automaticamente
                        </button>
                    </form>
                    
                <?php else: ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle me-2"></i>
                        <strong>Excelente!</strong> Nenhum problema de encoding foi encontrado.
                    </div>
                    <p class="text-muted">Todos os textos estão com codificação correta.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <div class="modern-card">
            <div class="modern-card-header">
                <h5 class="modern-card-title">
                    <i class="fas fa-info-circle me-2"></i>Como Funciona
                </h5>
            </div>
            <div class="card-body-modern">
                <div class="mb-3">
                    <h6 class="text-primary">Problemas Comuns:</h6>
                    <ul class="small">
                        <li><code>Ã¡</code> → <code>á</code></li>
                        <li><code>Ã§</code> → <code>ç</code></li>
                        <li><code>Ã³</code> → <code>ó</code></li>
                        <li><code>Â°</code> → <code>°</code></li>
                        <li><code>â€œ</code> → <code>"</code></li>
                    </ul>
                </div>
                
                <div class="mb-3">
                    <h6 class="text-success">O que será corrigido:</h6>
                    <ul class="small">
                        <li>Nomes de produtos</li>
                        <li>Descrições</li>
                        <li>Nomes de clientes</li>
                        <li>Endereços</li>
                    </ul>
                </div>
                
                <div class="alert alert-info small">
                    <i class="fas fa-shield-alt me-2"></i>
                    <strong>Segurança:</strong> As alterações são feitas em uma transação. Se houver erro, tudo será revertido.
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once __DIR__ . '/includes/footer.php'; ?>
