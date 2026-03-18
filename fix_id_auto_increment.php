<?php
header('Content-Type: text/html; charset=utf-8');

echo "<h2>🔧 Corrigindo Campo ID da Tabela Produtos</h2>";

require_once 'includes/db_connect.php';

// SQL para corrigir o ID
$sql_correcao = "ALTER TABLE `produtos` MODIFY COLUMN `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY";

echo "<p>Executando: <code>$sql_correcao</code></p>";

if ($conn->query($sql_correcao)) {
    echo "✅ <strong>Campo ID corrigido com sucesso!</strong><br>";
    echo "O campo ID agora tem AUTO_INCREMENT.<br><br>";
    
    // Verificar a estrutura
    $sql_check = "SHOW CREATE TABLE produtos";
    $result = $conn->query($sql_check);
    if ($result) {
        $row = $result->fetch_assoc();
        echo "<h3>Estrutura da tabela:</h3>";
        echo "<pre style='background: #f5f5f5; padding: 10px; border-radius: 5px;'>";
        echo htmlspecialchars($row['Create Table']);
        echo "</pre>";
    }
    
    echo "<br><a href='cadastro_produto.php' class='btn btn-success'>Ir para Cadastro de Produtos</a>";
} else {
    echo "❌ Erro ao corrigir: " . $conn->error . "<br>";
    echo "<br>Tente fazer um BACKUP e executar manualmente no phpMyAdmin.<br>";
    echo "SQL: <code>ALTER TABLE `produtos` MODIFY COLUMN `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY;</code>";
}

$conn->close();
?>
