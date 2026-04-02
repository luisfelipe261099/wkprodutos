<?php

declare(strict_types=1);

require_once __DIR__ . '/site_data.php';

function kw_esc(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function kw_site_url(string $path = ''): string
{
    $normalizedPath = ltrim($path, '/');
    return '/' . $normalizedPath;
}

function kw_whatsapp_link(string $message): string
{
    return 'https://wa.me/' . KW_WHATSAPP_NUMBER . '?text=' . rawurlencode($message);
}

function kw_render_head(string $metaTitle, string $metaDescription, string $active = 'inicio'): void
{
    $origin = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https://' : 'http://')
        . ($_SERVER['HTTP_HOST'] ?? 'localhost');
    $canonical = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https://' : 'http://')
        . ($_SERVER['HTTP_HOST'] ?? 'localhost')
        . ($_SERVER['REQUEST_URI'] ?? '/');
    $logoPath = kw_site_url('logo.svg');
    ?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo kw_esc($metaTitle); ?></title>
    <meta name="description" content="<?php echo kw_esc($metaDescription); ?>">
    <meta name="robots" content="index,follow,max-image-preview:large">
    <link rel="canonical" href="<?php echo kw_esc($canonical); ?>">
    <meta property="og:type" content="website">
    <meta property="og:title" content="<?php echo kw_esc($metaTitle); ?>">
    <meta property="og:description" content="<?php echo kw_esc($metaDescription); ?>">
    <meta property="og:image" content="<?php echo kw_esc($origin . $logoPath); ?>">
    <meta property="og:locale" content="pt_BR">
    <meta name="theme-color" content="#0c2f58">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&family=Manrope:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer">
        <link rel="stylesheet" href="<?php echo kw_esc(kw_site_url('css/institucional.css')); ?>">
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "Organization",
      "name": "Karla Wollinger Representacoes",
      "url": "<?php echo kw_esc((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https://' : 'http://') . ($_SERVER['HTTP_HOST'] ?? 'localhost')); ?>",
            "logo": "<?php echo kw_esc($origin . $logoPath); ?>",
      "email": "<?php echo kw_esc(KW_CONTACT_EMAIL); ?>",
      "contactPoint": {
        "@type": "ContactPoint",
        "telephone": "+<?php echo kw_esc(KW_WHATSAPP_NUMBER); ?>",
        "contactType": "sales",
        "areaServed": "Curitiba"
      }
    }
    </script>
</head>
<body>
<?php kw_render_header($active); ?>
<?php
}

function kw_render_header(string $active): void
{
    $items = [
        'inicio' => ['label' => 'Inicio', 'url' => kw_site_url('index.php')],
        'produtos' => ['label' => 'Produtos', 'url' => kw_site_url('catalogo.php')],
        'marcas' => ['label' => 'Marcas', 'url' => kw_site_url('marcas.php')],
        'sobre' => ['label' => 'Sobre', 'url' => kw_site_url('sobre.php')],
        'contato' => ['label' => 'Contato', 'url' => kw_site_url('contato.php')],
    ];
    ?>
<a class="floating-whatsapp" href="<?php echo kw_esc(kw_whatsapp_link('Ola! Quero solicitar um orcamento com a Karla Wollinger Representacoes.')); ?>" target="_blank" rel="noopener" aria-label="Falar no WhatsApp">
    <i class="fa-brands fa-whatsapp"></i>
</a>

<header class="site-header" id="topo">
    <div class="container nav-shell">
        <a class="brand" href="<?php echo kw_esc(kw_site_url('index.php')); ?>" aria-label="Karla Wollinger Representacoes">
            <img src="<?php echo kw_esc(kw_site_url('logo.svg')); ?>" alt="Logo Karla Wollinger Representacoes" loading="eager" width="280" height="75">
        </a>
        <button class="menu-toggle" id="menuToggle" aria-label="Abrir menu" aria-expanded="false">
            <i class="fa-solid fa-bars"></i>
        </button>
        <nav class="main-nav" id="mainNav">
            <?php foreach ($items as $key => $item): ?>
                <a class="<?php echo $active === $key ? 'active' : ''; ?>" href="<?php echo kw_esc($item['url']); ?>"><?php echo kw_esc($item['label']); ?></a>
            <?php endforeach; ?>
        </nav>
        <div class="header-cta-group">
            <a class="btn btn-secondary" href="<?php echo kw_esc(kw_site_url('login.php')); ?>">Area do Cliente</a>
            <a class="btn btn-primary" href="<?php echo kw_esc(kw_whatsapp_link('Oi! Preciso de um orcamento para minha empresa.')); ?>" target="_blank" rel="noopener">Solicitar Orcamento</a>
        </div>
    </div>
</header>
    <?php
}

function kw_render_footer(): void
{
    ?>
<footer class="site-footer">
    <div class="container footer-grid">
        <div>
            <img class="footer-logo" src="<?php echo kw_esc(kw_site_url('logo.svg')); ?>" alt="Karla Wollinger Representacoes" width="240" height="64">
            <p>Representacao comercial de produtos de limpeza, higiene, papeis, embalagens e descartaveis para empresas em Curitiba e regiao.</p>
            <p class="footer-cnpj">CNPJ: 30.459.625/0001-85</p>
        </div>
        <div>
            <h3>Menu rapido</h3>
            <a href="<?php echo kw_esc(kw_site_url('index.php')); ?>">Inicio</a>
            <a href="<?php echo kw_esc(kw_site_url('catalogo.php')); ?>">Produtos</a>
            <a href="<?php echo kw_esc(kw_site_url('marcas.php')); ?>">Marcas</a>
            <a href="<?php echo kw_esc(kw_site_url('sobre.php')); ?>">Sobre</a>
            <a href="<?php echo kw_esc(kw_site_url('contato.php')); ?>">Contato</a>
        </div>
        <div>
            <h3>Marcas</h3>
            <?php foreach (kw_site_brands() as $brand): ?>
                <span class="brand-tag"><?php echo kw_esc($brand); ?></span>
            <?php endforeach; ?>
        </div>
        <div>
            <h3>Contato</h3>
            <a href="mailto:<?php echo kw_esc(KW_CONTACT_EMAIL); ?>"><?php echo kw_esc(KW_CONTACT_EMAIL); ?></a>
            <a href="<?php echo kw_esc(kw_whatsapp_link('Oi! Vim pelo site e quero atendimento.')); ?>" target="_blank" rel="noopener">WhatsApp <?php echo kw_esc(KW_WHATSAPP_LABEL); ?></a>
            <p>Atendimento em Curitiba e regiao metropolitana</p>
            <div class="social-line">
                <a href="#" aria-label="Instagram"><i class="fa-brands fa-instagram"></i></a>
                <a href="#" aria-label="Facebook"><i class="fa-brands fa-facebook-f"></i></a>
                <a href="#" aria-label="LinkedIn"><i class="fa-brands fa-linkedin-in"></i></a>
            </div>
        </div>
    </div>
    <div class="footer-bottom">&copy; <?php echo date('Y'); ?> Karla Wollinger Representacoes. Todos os direitos reservados.</div>
</footer>
<script src="<?php echo kw_esc(kw_site_url('js/institucional.js')); ?>"></script>
</body>
</html>
    <?php
}
