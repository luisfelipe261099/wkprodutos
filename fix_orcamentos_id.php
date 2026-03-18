<?php
/**
 * ⚠️ SCRIPT DESCONTINUADO - Incompatível com ambientes serverless
 * 
 * Plataformas como Vercel/PlanetScale bloqueiam ALTER TABLE
 * A solução está em criar_orcamento.php com fallback automático
 */

die(view_msg());

function view_msg() {
    return <<<'HTML'
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Script Descontinuado</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; background: #f5f5f5; }
        .container { max-width: 700px; margin: 0 auto; }
        .alert { background: #ffebee; border: 1px solid #f44336; padding: 20px; border-radius: 4px; }
        h2 { color: #f44336; }
        .success { background: #e8f5e9; border: 1px solid #4caf50; padding: 20px; border-radius: 4px; margin-top: 20px; }
        .info { background: #e3f2fd; border: 1px solid #2196F3; padding: 20px; border-radius: 4px; margin-top: 20px; }
        a { color: #2196F3; text-decoration: none; font-weight: bold; }
        code { background: #f0f0f0; padding: 2px 6px; border-radius: 3px; font-family: monospace; }
    </style>
</head>
<body>
    <div class="container">
        <div class="alert">
            <h2>⚠️ Script Descontinuado</h2>
            <p><strong>fix_orcamentos_id.php</strong> usa ALTER TABLE, bloqueado em Vercel/PlanetScale.</p>
        </div>

        <div class="success">
            <h3>✅ Sistema Corrigido</h3>
            <p><strong>Acesse:</strong> <a href="criar_orcamento.php">criar_orcamento.php</a></p>
            <p>Gera IDs automaticamente sem ALTER TABLE!</p>
        </div>

        <div class="info">
            <h3>💡 Por que?</h3>
            <p>Ambientes serverless restringem ALTER TABLE por segurança e performance.</p>
        </div>

        <p style="text-align: center; margin-top: 30px;">
            <a href="criar_orcamento.php">← Voltar para Criar Orçamento</a>
        </p>
    </div>
</body>
</html>
HTML;
}
?>
