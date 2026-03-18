<?php
/**
 * Helper para detectar e exibir aviso de encoding
 * Include esse arquivo no início do dashboard ou index
 */

function checkAndAlertEncoding($conn) {
    // Verificar apenas uma vez por sessão
    if (isset($_SESSION['_encoding_checked_'])) {
        return;
    }
    
    // Marcar que já verificou
    $_SESSION['_encoding_checked_'] = true;
    
    // Verificar se há produtos com encoding errado
    $check = $conn->query("
        SELECT COUNT(*) as total
        FROM produtos 
        WHERE nome LIKE '%Ã%' 
           OR nome LIKE '%Â%' 
           OR descricao LIKE '%Ã%' 
           OR descricao LIKE '%Â%'
        LIMIT 1
    ");
    
    if ($check && $row = $check->fetch_assoc()) {
        if ($row['total'] > 0) {
            // Guardar na sessão que há problema
            $_SESSION['_has_encoding_issue_'] = true;
        }
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
    
    // Exibir apenas uma vez
    unset($_SESSION['_has_encoding_issue_']);
    
    return '
    <div id="encodingAlertToast" class="position-fixed bottom-0 end-0 p-3" style="z-index: 11; margin: 20px;">
        <div class="toast show alert alert-warning" role="alert">
            <div class="toast-header bg-warning">
                <strong class="me-auto">
                    <i class="fas fa-exclamation-triangle"></i> Problema de Encoding Detectado
                </strong>
                <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
            <div class="toast-body">
                <p>Alguns produtos têm caracteres especiais incorretos (Ã¡, Ã•, Â°, etc).</p>
                <a href="aviso_encoding.php" class="btn btn-sm btn-warning" style="margin-top: 10px;">
                    🔧 Corrigir Agora
                </a>
            </div>
        </div>
    </div>
    
    <script>
    // Auto-fechar após 15 segundos
    setTimeout(function() {
        var toast = document.getElementById("encodingAlertToast");
        if (toast) {
            var bsToast = new bootstrap.Toast(toast);
            bsToast.hide();
        }
    }, 15000);
    </script>
    ';
}
?>
