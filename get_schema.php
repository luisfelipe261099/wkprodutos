<?php
putenv('APP_ENV=development');
require_once 'includes/db_connect.php';
$res = $conn->query('SHOW CREATE TABLE orcamentos');
if ($res) {
    $row = $res->fetch_row();
    echo "--- ORCAMENTOS ---\n" . $row[1] . "\n";
} else {
    echo "ERROR (orcamentos): " . $conn->error . "\n";
}

$res = $conn->query('SHOW CREATE TABLE itens_orcamento');
if ($res) {
    $row = $res->fetch_row();
    echo "--- ITENS ORCAMENTO ---\n" . $row[1] . "\n";
} else {
    echo "ERROR (itens): " . $conn->error . "\n";
}
?>
