<?php
/**
 * ⚠️ AVISO: Este script tenta ALTER TABLE
 * Não funciona em Vercel/PlanetScale/Serverless
 * 
 * Use criar_orcamento.php que já tem fallback automático
 */

die(view_msg());

function view_msg() {
    return <<<'HTML'
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>⚠️ Verificação Incompatível</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; background: #f5f5f5; }
        .container { max-width: 700px; margin: 0 auto; }
        .alert { background: #ffebee; border: 1px solid #f44336; padding: 20px; border-radius: 4px; }
        .success { background: #e8f5e9; border: 1px solid #4caf50; padding: 20px; border-radius: 4px; margin-top: 20px; }
        .info { background: #e3f2fd; border: 1px solid #2196F3; padding: 20px; border-radius: 4px; margin-top: 20px; }
        h2 { color: #f44336; }
        a { color: #2196F3; font-weight: bold; text-decoration: none; }
        code { background: #f0f0f0; padding: 2px 6px; font-family: monospace; }
    </style>
</head>
<body>
    <div class="container">
        <div class="alert">
            <h2>⚠️ Verificação Incompatível com Serverless</h2>
            <p>Este script tenta usar <code>ALTER TABLE</code>, que é <strong>bloqueado em Vercel/PlanetScale</strong>.</p>
        </div>

        <div class="success">
            <h3>✅ Solução: Fallback Automático</h3>
            <p>O arquivo <strong>criar_orcamento.php</strong> já foi atualizado para:</p>
            <ul>
                <li>✓ Tentar inserir sem ID (AUTO_INCREMENT)</li>
                <li>✓ Se falhar, gera ID incrementando o máximo</li>
                <li>✓ Funciona em qualquer ambiente</li>
            </ul>
        </div>

        <div class="info">
            <h3>📝 Informações Úteis</h3>
            <p><strong>Este script:</strong></p>
            <ul>
                <li>❌ NÃO funciona em Vercel, PlanetScale, Netlify, AWS Lambda</li>
                <li>✓ Funcionaria em XAMPP/localhost (com MySQL tradicional)</li>
            </ul>
            <p><strong>Para usar em produção:</strong> Confie no fallback do criar_orcamento.php!</p>
        </div>

        <p style="text-align: center; margin-top: 30px;">
            <a href="criar_orcamento.php">← Voltar e Criar Orçamento</a>
        </p>
    </div>
</body>
</html>
HTML;
}
                        echo "❌ Erro ao adicionar PRIMARY KEY: " . $conn->error . "<br>";
                    }
                }
            }
            echo "<br>";
        }
    }
}

echo "<hr>";
echo "<h2>✅ Verificação concluída!</h2>";
echo "<p><a href='criar_orcamento.php'>← Voltar para Criar Orçamento</a></p>";
?>
