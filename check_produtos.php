<?php
require 'includes/db_connect.php';

echo "=== Verificando Produtos ===\n\n";

$result = $conn->query("SELECT id, nome, LENGTH(nome) as tamanho FROM produtos LIMIT 10");
if ($result) {
    while($row = $result->fetch_assoc()) {
        echo "ID: " . $row['id'] . " | Tamanho: " . $row['tamanho'] . " | Nome: [" . $row['nome'] . "]\n";
    }
}

echo "\n\n=== Procurando 'Acool' ===\n\n";
$search = $conn->query("SELECT id, nome FROM produtos WHERE nome LIKE '%Acool%' OR nome LIKE '%acool%'");
if ($search) {
    while($row = $search->fetch_assoc()) {
        echo "ID: " . $row['id'] . " | Nome: [" . $row['nome'] . "]\n";
    }
} else {
    echo "Erro: " . $conn->error . "\n";
}

$conn->close();
?>
