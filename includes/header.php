<?php
$ui_helper_path = __DIR__ . '/ui_helpers.php';
if (file_exists($ui_helper_path)) {
    require_once $ui_helper_path;
}

$current_page = basename($_SERVER['PHP_SELF'] ?? 'dashboard.php');
$page_titles = [
    'dashboard.php' => 'Visao geral',
    'clientes.php' => 'Clientes',
    'produtos.php' => 'Produtos',
    'vendas.php' => 'Vendas',
    'orcamentos.php' => 'Orcamentos',
    'financeiro.php' => 'Financeiro',
    'relatorios.php' => 'Relatorios',
    'perfil.php' => 'Perfil',
    'agendamentos.php' => 'Agendamentos'
];
$current_page_title = $page_titles[$current_page] ?? 'Painel de gestao';
$user_role_label = (isset($_SESSION['nivel_acesso']) && $_SESSION['nivel_acesso'] === 'admin') ? 'Administrador' : 'Operacao';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, maximum-scale=1.0">
    <meta name="format-detection" content="telephone=no">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <title>Karla Wollinger - Sistema de Gestão</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <?php
    $inline_style_new = __DIR__ . '/../css/style-new.css';
    if (file_exists($inline_style_new)) {
        echo "<style>\n" . file_get_contents($inline_style_new) . "\n</style>";
    }
    
    $mobile_fixes = __DIR__ . '/../css/mobile-fixes.css';
    if (file_exists($mobile_fixes)) {
        echo "<style>\n" . file_get_contents($mobile_fixes) . "\n</style>";
    }

    $responsive_fixes = __DIR__ . '/../css/responsive-fixes.css';
    if (file_exists($responsive_fixes)) {
        echo "<style>\n" . file_get_contents($responsive_fixes) . "\n</style>";
    }
    ?>
    <script>
    try {
        const theme = localStorage.getItem('kw-theme') || 'dark';
        if (theme === 'light') {
            document.documentElement.setAttribute('data-theme', 'light');
            document.documentElement.setAttribute('data-bs-theme', 'light');
        } else {
            document.documentElement.removeAttribute('data-theme');
            document.documentElement.setAttribute('data-bs-theme', 'dark');
        }
    } catch(e) {}
    </script>
</head>
<body class="dashboard-layout">
    <!-- Mobile Overlay -->
    <div class="mobile-overlay" id="mobileOverlay"></div>

    <!-- Sidebar -->
    <nav class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <div class="sidebar-brand-row">
                <a href="dashboard.php" class="sidebar-brand">
                    <i class="fas fa-gem"></i>
                    <span>Karla Wollinger</span>
                </a>
                <button class="sidebar-close d-lg-none" id="sidebarClose" type="button" aria-label="Fechar menu">
                    <i class="fas fa-xmark"></i>
                </button>
            </div>
            <div class="sidebar-caption">Painel comercial com foco em operacao rapida</div>
            <div class="sidebar-status">
                <span class="status-dot"></span>
                Sistema online
            </div>
        </div>

        <div class="sidebar-nav">
            <div class="nav-section-label">Visão Geral</div>
            <div class="nav-item">
                <a href="dashboard.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
                    <i class="fas fa-chart-line"></i>
                    <span>Dashboard Executivo</span>
                </a>
            </div>

            <div class="nav-section-label">Gestão Comercial</div>
            <div class="nav-item">
                <a href="clientes.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'clientes.php' ? 'active' : ''; ?>">
                    <i class="fas fa-handshake"></i>
                    <span>Carteira de Clientes</span>
                </a>
            </div>
            
            <div class="nav-item">
                <a href="produtos.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'produtos.php' ? 'active' : ''; ?>">
                    <i class="fas fa-warehouse"></i>
                    <span>Catálogo de Produtos</span>
                </a>
            </div>

            <div class="nav-item">
                <a href="empresas_representadas.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'empresas_representadas.php' || basename($_SERVER['PHP_SELF']) == 'cadastro_empresa.php' ? 'active' : ''; ?>">
                    <i class="fas fa-industry"></i>
                    <span>Parceiros & Fornecedores</span>
                </a>
            </div>

            <div class="nav-section-label">Operações & Vendas</div>
            <div class="nav-item">
                <a href="orcamentos.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'orcamentos.php' ? 'active' : ''; ?>">
                    <i class="fas fa-calculator"></i>
                    <span>Cotações & Orçamentos</span>
                </a>
            </div>

            <div class="nav-item">
                <a href="vendas.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'vendas.php' ? 'active' : ''; ?>">
                    <i class="fas fa-chart-bar"></i>
                    <span>Pipeline de Vendas</span>
                </a>
            </div>

            <div class="nav-item">
                <a href="agendamentos.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'agendamentos.php' ? 'active' : ''; ?>">
                    <i class="fas fa-calendar-check"></i>
                    <span>Agenda Empresarial</span>
                </a>
            </div>

            <div class="nav-section-label">Análise Financeira</div>
            <div class="nav-item">
                <a href="financeiro.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'financeiro.php' ? 'active' : ''; ?>">
                    <i class="fas fa-chart-pie"></i>
                    <span>Centro Financeiro</span>
                </a>
            </div>

            <div class="nav-item">
                <a href="relatorios.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'relatorios.php' ? 'active' : ''; ?>">
                    <i class="fas fa-chart-line"></i>
                    <span>Business Intelligence</span>
                </a>
            </div>

            <?php if (isset($_SESSION['nivel_acesso']) && $_SESSION['nivel_acesso'] == 'admin'): ?>
            <div class="nav-section-label">Administração</div>
            <div class="nav-item">
                <a href="usuarios.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'usuarios.php' || basename($_SERVER['PHP_SELF']) == 'cadastro_usuario.php' ? 'active' : ''; ?>">
                    <i class="fas fa-user-tie"></i>
                    <span>Gestão de Usuários</span>
                </a>
            </div>

            <div class="nav-item">
                <a href="empresa_logos.php" class="nav-link">
                    <i class="fas fa-palette"></i>
                    <span>Identidade Visual</span>
                </a>
            </div>
            <?php endif; ?>

            <div class="nav-section-label">Ações Rápidas</div>

            <div class="nav-item nav-action">
                <a href="cadastro_cliente.php" class="nav-link">
                    <i class="fas fa-user-plus"></i>
                    <span>Novo Cliente</span>
                </a>
            </div>

            <div class="nav-item nav-action">
                <a href="cadastro_produto.php" class="nav-link">
                    <i class="fas fa-plus-square"></i>
                    <span>Novo Produto</span>
                </a>
            </div>

            <div class="nav-item nav-action">
                <a href="registrar_venda.php" class="nav-link">
                    <i class="fas fa-cash-register"></i>
                    <span>Registrar Venda</span>
                </a>
            </div>
        </div>
    </nav>

    <!-- Topbar -->
    <header class="topbar" id="topbar">
        <div class="topbar-start">
            <button class="sidebar-toggle" id="sidebarToggle">
                <i class="fas fa-bars"></i>
            </button>
            <div class="topbar-intro">
                <div class="topbar-eyebrow">Workspace</div>
                <div class="topbar-title"><?php echo htmlspecialchars($current_page_title); ?></div>
            </div>
        </div>

        <div class="topbar-user">
            <button class="topbar-icon-button theme-toggle-btn" id="themeToggle" type="button" aria-label="Alternar tema">
                <i class="fas fa-sun"></i>
            </button>
            <div class="user-info">
                <div class="user-name"><?php echo htmlspecialchars($_SESSION["nome"] ?? 'Usuário'); ?></div>
                <div class="user-role"><span class="role-pill"><?php echo $user_role_label; ?></span></div>
            </div>
            <div class="user-avatar">
                <?php echo strtoupper(substr($_SESSION["nome"] ?? 'U', 0, 1)); ?>
            </div>
            <div class="dropdown">
                <button class="btn btn-link text-decoration-none p-0 ms-2" type="button" data-bs-toggle="dropdown">
                    <i class="fas fa-chevron-down text-muted"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="perfil.php"><i class="fas fa-user-circle me-2"></i> Meu Perfil</a></li>
                    <?php if (isset($_SESSION['nivel_acesso']) && $_SESSION['nivel_acesso'] == 'admin'): ?>
                    <li><a class="dropdown-item" href="usuarios.php"><i class="fas fa-users-cog me-2"></i> Gerenciar Usuários</a></li>
                    <?php endif; ?>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i> Sair</a></li>
                </ul>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="main-content" id="mainContent">