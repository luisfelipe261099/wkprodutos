<?php
/**
 * Helper para detectar e exibir aviso de encoding
 * Include esse arquivo no início do dashboard ou index
 */

function checkAndAlertEncoding($conn) {
    // Verificar apenas uma vez por sessão para performance
    if (isset($_SESSION['_encoding_checked_']) && $_SESSION['_encoding_checked_'] === date('Y-m-d')) {
        return;
    }
    
    // Marcar que já verificou hoje
    $_SESSION['_encoding_checked_'] = date('Y-m-d');
    
    try {
        // Verificar se há produtos com encoding errado
        $check = $conn->query("
            SELECT COUNT(*) as total
            FROM (
                SELECT id FROM produtos 
                WHERE nome LIKE '%Ã%' OR nome LIKE '%Â%' OR nome LIKE '%â€%'
                   OR descricao LIKE '%Ã%' OR descricao LIKE '%Â%' OR descricao LIKE '%â€%'
                UNION
                SELECT id FROM clientes 
                WHERE nome LIKE '%Ã%' OR nome LIKE '%Â%' OR nome LIKE '%â€%'
                   OR endereco LIKE '%Ã%' OR endereco LIKE '%Â%' OR endereco LIKE '%â€%'
                LIMIT 1
            ) AS problemas
        ");
        
        if ($check && $row = $check->fetch_assoc()) {
            if ($row['total'] > 0) {
                // Guardar na sessão que há problema
                $_SESSION['_has_encoding_issue_'] = true;
            } else {
                // Limpar flag se não há mais problemas
                unset($_SESSION['_has_encoding_issue_']);
            }
        }
    } catch (Exception $e) {
        // Em caso de erro, não mostrar alerta
        error_log("Erro ao verificar encoding: " . $e->getMessage());
    }
}

/**
 * Exibir alerta flutuante se houver problema de encoding
 * Call isso no footer ou início do body
 */
function displayEncodingAlert() {
    // Se não há problema, não exibir nada
    if (!isset($_SESSION['_has_encoding_issue_']) || !$_SESSION['_has_encoding_issue_']) {
        return '';
    }
    
    // Não exibir se já foi mostrado hoje
    if (isset($_SESSION['_encoding_alert_shown_']) && $_SESSION['_encoding_alert_shown_'] === date('Y-m-d')) {
        return '';
    }
    
    // Marcar que foi mostrado hoje
    $_SESSION['_encoding_alert_shown_'] = date('Y-m-d');
    
    return '
    <div id="encodingAlertToast" class="position-fixed bottom-0 end-0 p-3" style="z-index: 9999; max-width: 350px;">
        <div class="toast show alert alert-warning border-0 shadow" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="toast-header bg-warning text-dark">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <strong class="me-auto">Problema de Encoding</strong>
                <small class="text-muted">Sistema</small>
                <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Fechar"></button>
            </div>
            <div class="toast-body">
                <p class="mb-2 small">Detectados caracteres especiais incorretos no banco de dados.</p>
                <div class="d-grid">
                    <a href="aviso_encoding.php" class="btn btn-warning btn-sm">
                        <i class="fas fa-tools me-1"></i> Corrigir Agora
                    </a>
                </div>
                <hr class="my-2">
                <div class="d-flex justify-content-between">
                    <button type="button" class="btn btn-link btn-sm text-muted p-0" onclick="dismissEncodingAlert()">
                        Não mostrar hoje
                    </button>
                    <button type="button" class="btn btn-link btn-sm text-muted p-0" data-bs-dismiss="toast">
                        Fechar
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <script>
    // Auto-fechar após 20 segundos
    setTimeout(function() {
        var toast = document.getElementById("encodingAlertToast");
        if (toast) {
            var bsToast = bootstrap.Toast.getInstance(toast);
            if (bsToast) {
                bsToast.hide();
            }
        }
    }, 20000);
    
    // Função para não mostrar hoje
    function dismissEncodingAlert() {
        fetch("aviso_encoding.php?action=dismiss", {
            method: "POST",
            headers: {
                "Content-Type": "application/x-www-form-urlencoded",
            },
            body: "dismiss_today=1"
        });
        
        var toast = document.getElementById("encodingAlertToast");
        if (toast) {
            var bsToast = bootstrap.Toast.getInstance(toast);
            if (!bsToast) {
                bsToast = new bootstrap.Toast(toast);
            }
            bsToast.hide();
        }
    }
    </script>
    ';
}

/**
 * Função para corrigir encoding de uma string
 */
function fixEncoding($text) {
    if (empty($text)) return $text;
    
    // Mapeamento de correções mais completo
    $replacements = [
        // Acentos básicos
        'Ã¡' => 'á', 'Ã©' => 'é', 'Ã­' => 'í', 'Ã³' => 'ó', 'Ãº' => 'ú',
        'Ã ' => 'à', 'Ã¨' => 'è', 'Ã¬' => 'ì', 'Ã²' => 'ò', 'Ã¹' => 'ù',
        'Ã¢' => 'â', 'Ãª' => 'ê', 'Ã®' => 'î', 'Ã´' => 'ô', 'Ã»' => 'û',
        'Ã£' => 'ã', 'Ã±' => 'ñ', 'Ãµ' => 'õ', 'Ã§' => 'ç',
        
        // Maiúsculas comuns
        'Ã81' => 'Á', 'Ã89' => 'É', 'Ã8D' => 'Í', 'Ã93' => 'Ó', 'Ã9A' => 'Ú',
        'Ã80' => 'À', 'Ã88' => 'È', 'Ã8C' => 'Ì', 'Ã92' => 'Ò', 'Ã99' => 'Ù',
        'Ã82' => 'Â', 'Ã8A' => 'Ê', 'Ã8E' => 'Î', 'Ã94' => 'Ô', 'Ã9B' => 'Û',
        'Ã83' => 'Ã', 'Ã91' => 'Ñ', 'Ã95' => 'Õ', 'Ã87' => 'Ç',
        
        // Caracteres especiais
        'Â°' => '°', 'Â´' => '´', 'Â¨' => '¨', 'Â¡' => '¡', 'Â¿' => '¿',
        'Â«' => '«', 'Â»' => '»', 'Â²' => '²', 'Â³' => '³', 'Â¹' => '¹',
        'Â½' => '½', 'Â¼' => '¼', 'Â¾' => '¾', 'Â±' => '±',
        
        // Aspas e hífens
        'â€œ' => '"', 'â€' => '"', 'â€™' => "'", 'â€˜' => "'",
        'â€"' => '–', 'â€"' => '—', 'â€¦' => '…',
        
        // Remover caracteres problemáticos
        'â€‹' => '', 'ï»¿' => '', 'â€Š' => ' ', 'â€‰' => ' ', 'â€ˆ' => ' ',
        
        // Corrigir espaços duplos
        '  ' => ' ', '   ' => ' ', '    ' => ' ',
    ];
    
    $fixed_text = $text;
    
    // Aplicar correções
    foreach ($replacements as $wrong => $correct) {
        $fixed_text = str_replace($wrong, $correct, $fixed_text);
    }
    
    // Limpar espaços extras
    $fixed_text = trim($fixed_text);
    
    return $fixed_text;
}
?>
