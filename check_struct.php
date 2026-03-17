<?php
putenv('APP_ENV=development');
require_once 'includes/db_connect.php';

$tables = ['orcamentos', 'itens_orcamento'];
foreach ($tables as $table) {
    echo "Estrutura da tabela: $table\n";
    $result = $conn->query("DESCRIBE $table");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            echo "Field: {$row['Field']} - Type: {$row['Type']} - Key: {$row['Key']} - Extra: {$row['Extra']}\n";
        }
    } else {
        echo "Erro ao descrever tabela $table: " . $conn->error . "\n";
    }
    echo "-------------------\n";
}
?>
