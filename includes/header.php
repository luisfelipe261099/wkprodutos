<?php
$current_page = basename($_SERVER['PHP_SELF'] ?? 'dashboard.php');
$page_titles = [
    'dashboard.php' => 'Dashboard',
    'clientes.php' => 'Clientes',
    'produtos.php' => 'Produtos',
    'vendas.php' => 'Vendas',
    'orcamentos.php' => 'Orçamentos',
    'financeiro.php' => 'Financeiro',
    'relatorios.php' => 'Relatórios',
    'perfil.php' => 'Perfil',
    'agendamentos.php' => 'Agendamentos'
];
$current_page_title = $page_titles[$current_page] ?? 'Sistema';
$user_role_label = (isset($_SESSION['nivel_acesso']) && $_SESSION['nivel_acesso'] === 'admin') ? 'Administrador' : 'Operação';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="format-detection" content="telephone=no">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <title>Karla Wollinger - <?php echo htmlspecialchars($current_page_title); ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
    
    <!-- CSS Principal -->
    <link rel="stylesheet" href="css/main.css">
</head>
<body>
    <!-- Sidebar -->
    <nav class="sidebar">
        <div class="sidebar-header">
            <div class="sidebar-brand">
                <i class="fas fa-chart-line"></i>
                <span>Karla Wollinger</span>
            </div>
        </div>
        
        <div class="sidebar-nav">
            <div class="nav-item">
                <a href="dashboard.php" class="nav-link <?php echo ($current_page === 'dashboard.php') ? 'active' : ''; ?>">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
            </div>
            <div class="nav-item">
                <a href="clientes.php" class="nav-link <?php echo ($current_page === 'clientes.php') ? 'active' : ''; ?>">
                    <i class="fas fa-users"></i>
                    <span>Clientes</span>
                </a>
            </div>
            <div class="nav-item">
                <a href="produtos.php" class="nav-link <?php echo ($current_page === 'produtos.php') ? 'active' : ''; ?>">
                    <i class="fas fa-box"></i>
                    <span>Produtos</span>
                </a>
            </div>
            <div class="nav-item">
                <a href="vendas.php" class="nav-link <?php echo ($current_page === 'vendas.php') ? 'active' : ''; ?>">
                    <i class="fas fa-shopping-cart"></i>
                    <span>Vendas</span>
                </a>
            </div>
            <div class="nav-item">
                <a href="orcamentos.php" class="nav-link <?php echo ($current_page === 'orcamentos.php') ? 'active' : ''; ?>">
                    <i class="fas fa-file-invoice-dollar"></i>
                    <span>Orçamentos</span>
                </a>
            </div>
            <div class="nav-item">
                <a href="financeiro.php" class="nav-link <?php echo ($current_page === 'financeiro.php') ? 'active' : ''; ?>">
                    <i class="fas fa-chart-bar"></i>
                    <span>Financeiro</span>
                </a>
            </div>
            <div class="nav-item">
                <a href="relatorios.php" class="nav-link <?php echo ($current_page === 'relatorios.php') ? 'active' : ''; ?>">
                    <i class="fas fa-chart-line"></i>
                    <span>Relatórios</span>
                </a>
            </div>
            <div class="nav-item">
                <a href="perfil.php" class="nav-link <?php echo ($current_page === 'perfil.php') ? 'active' : ''; ?>">
                    <i class="fas fa-user"></i>
                    <span>Perfil</span>
                </a>
            </div>
            <div class="nav-item">
                <a href="agendamentos.php" class="nav-link <?php echo ($current_page === 'agendamentos.php') ? 'active' : ''; ?>">
                    <i class="fas fa-calendar-alt"></i>
                    <span>Agendamentos</span>
                </a>
            </div>
            <div class="nav-item">
                <a href="logout.php" class="nav-link">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Sair</span>
                </a>
            </div>
        </div>
    </nav>

    <!-- Topbar -->
    <div class="topbar">
        <div class="d-flex align-items-center gap-3">
            <button class="sidebar-toggle" type="button">
                <i class="fas fa-bars"></i>
            </button>
            <h1 class="mb-0" style="font-size: 1.5rem; font-weight: 600;"><?php echo htmlspecialchars($current_page_title); ?></h1>
        </div>
        
        <div class="topbar-user">
            <div class="user-info">
                <div class="user-name"><?php echo htmlspecialchars($_SESSION['nome'] ?? 'Usuário'); ?></div>
                <div class="user-role"><?php echo htmlspecialchars($user_role_label); ?></div>
            </div>
            <div class="user-avatar">
                <?php echo strtoupper(substr($_SESSION['nome'] ?? 'U', 0, 1)); ?>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="container-fluid">
