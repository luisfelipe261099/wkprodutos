<?php
// Test script to verify database connection handling in relatorios.php

// Start session for testing
session_start();

// Verifica se o usuário está logado e é admin
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}

if (!isset($_SESSION["nivel_acesso"]) || $_SESSION["nivel_acesso"] !== "admin") {
    echo "Acesso negado. Apenas administradores podem acessar esta página.";
    exit;
}

// Include database connection
require_once 'includes/db_connect.php';

echo "<h1>Test Database Connection for Relatórios</h1>";

try {
    // Test if connection is working
    if ($conn && !$conn->connect_error) {
        echo "<p style='color: green;'>✓ Database connection successful</p>";
        
        // Test user query
        $usuario_id = 1;
        $sql_usuario = "SELECT nome FROM usuarios WHERE id = ?";
        $stmt_usuario = $conn->prepare($sql_usuario);
        
        if ($stmt_usuario) {
            echo "<p style='color: green;'>✓ User query preparation successful</p>";
            
            $stmt_usuario->bind_param("i", $usuario_id);
            $stmt_usuario->execute();
            $result_usuario = $stmt_usuario->get_result();
            
            if ($result_usuario && $result_usuario->num_rows > 0) {
                $usuario = $result_usuario->fetch_assoc();
                echo "<p style='color: green;'>✓ User data fetched: " . htmlspecialchars($usuario['nome']) . "</p>";
            } else {
                echo "<p style='color: orange;'>⚠ No user found with ID 1</p>";
            }
            
            $stmt_usuario->close();
            echo "<p style='color: green;'>✓ User statement closed successfully</p>";
        } else {
            echo "<p style='color: red;'>✗ Failed to prepare user query</p>";
        }
        
        // Test queries for filters
        $clientes_test = $conn->query("SELECT COUNT(*) as total FROM clientes");
        if ($clientes_test) {
            $count = $clientes_test->fetch_assoc();
            echo "<p style='color: green;'>✓ Clients table accessible - " . $count['total'] . " clients found</p>";
        }
        
        $empresas_test = $conn->query("SELECT COUNT(*) as total FROM empresas_representadas");
        if ($empresas_test) {
            $count = $empresas_test->fetch_assoc();
            echo "<p style='color: green;'>✓ Companies table accessible - " . $count['total'] . " companies found</p>";
        }
        
        // Close connection
        $conn->close();
        echo "<p style='color: green;'>✓ Connection closed successfully</p>";
        
    } else {
        echo "<p style='color: red;'>✗ Database connection failed</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Error: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<p><a href='relatorios.php'>← Back to Reports</a></p>";
?>
