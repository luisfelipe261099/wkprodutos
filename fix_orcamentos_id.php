<?php
require_once 'includes/db_connect.php';

echo "=== CORRIGINDO TABELA ORCAMENTOS ===\n\n";

// Verificar estrutura atual
echo "Estrutura atual da tabela orcamentos:\n";
$result = $conn->query('SHOW CREATE TABLE orcamentos');
if ($result) {
    $row = $result->fetch_row();
    echo $row[1] . "\n\n";
}

// Alterar a tabela para adicionar AUTO_INCREMENT
echo "Alterando a tabela...\n";
$alter_sql = 'ALTER TABLE orcamentos MODIFY COLUMN id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY';
if ($conn->query($alter_sql)) {
    echo "✓ Tabela alterada com sucesso!\n\n";
} else {
    echo "✗ Erro ao alterar: " . $conn->error . "\n";
    exit(1);
}

// Verificar se agora a estrutura está correta
echo "Nova estrutura da tabela:\n";
$result = $conn->query('SHOW CREATE TABLE orcamentos');
if ($result) {
    $row = $result->fetch_row();
    echo $row[1] . "\n\n";
}

echo "=== CORRECAO CONCLUIDA ===\n";
?>
