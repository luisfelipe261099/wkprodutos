<?php
header('Content-Type: text/html; charset=utf-8');

require_once 'includes/db_connect.php';

echo "<h2>🔧 Solução Alternativa - Sem ALTER TABLE</h2>";

// 1. Verificar a estrutura atual
echo "<h3>1️⃣ Verificando estrutura atual...</h3>";
$sql_describe = "DESCRIBE produtos";
$result = $conn->query($sql_describe);

echo "<pre style='background: #f5f5f5; padding: 10px; font-size: 12px; max-height: 300px; overflow-y: auto;'>";
while ($row = $result->fetch_assoc()) {
    echo "Campo: {$row['Field']}\n";
    echo "  Tipo: {$row['Type']}\n";
    echo "  Null: {$row['Null']}\n";
    echo "  Key: {$row['Key']}\n";
    echo "  Extra: {$row['Extra']}\n\n";
}
echo "</pre>";

// 2. Se ID não tiver AUTO_INCREMENT, usar um workaround
echo "<h3>2️⃣ Soluções:</h3>";

// Solução 1: Usar LAST_INSERT_ID() manualmente no código
echo "<p><strong>Solução A:</strong> Modificar cadastro_produto.php para não depender de AUTO_INCREMENT</p>";

// Solução 2: Criar trigger para auto-incrementar
$sql_trigger = "
CREATE TRIGGER IF NOT EXISTS tr_produtos_auto_id 
BEFORE INSERT ON produtos 
FOR EACH ROW 
BEGIN
  IF NEW.id IS NULL OR NEW.id = 0 THEN
    SET NEW.id = (SELECT COALESCE(MAX(id), 0) + 1 FROM produtos);
  END IF;
END;
";

if ($conn->multi_query($sql_trigger)) {
    echo "✅ <strong>Trigger criado com sucesso!</strong><br>";
    echo "Agora o ID será gerado automaticamente.<br>";
} else {
    echo "⚠️ Trigger já existe ou houve erro: " . $conn->error . "<br>";
}

// Solução 3: Modificar o PHP para gerar ID
echo "<p><strong>Solução B:</strong> Gerar ID no PHP (mais compatível)</p>";
echo "<code>
\$sql = \"SELECT MAX(id) as max_id FROM produtos\";<br>
\$result = \$conn->query(\$sql);<br>
\$row = \$result->fetch_assoc();<br>
\$novo_id = \$row['max_id'] + 1;<br>
</code>";

$conn->close();
?>
