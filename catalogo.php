<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/site_layout.php';

$metaTitle = 'Produtos de limpeza e higiene profissional em Curitiba | Karla Wollinger';
$metaDescription = 'Catalogo comercial com papeis, limpeza profissional, embalagens, descartaveis e equipamentos para empresas em Curitiba.';

kw_render_head($metaTitle, $metaDescription, 'produtos');
$categories = kw_site_categories();
$products = kw_site_products();
?>

<main class="section">
    <div class="container">
        <div class="section-head reveal">
            <h1>Produtos para empresas e revendas</h1>
            <p>Catalogo comercial com filtro por categoria para solicitar orcamento rapido.</p>
        </div>

        <div class="filters reveal">
            <button class="filter-btn active" data-filter="all" type="button">Todos</button>
            <?php foreach ($categories as $category): ?>
                <button class="filter-btn" data-filter="<?php echo kw_esc($category['slug']); ?>" type="button"><?php echo kw_esc($category['nome']); ?></button>
            <?php endforeach; ?>
        </div>

        <div class="product-grid">
            <?php foreach ($products as $product): ?>
                <article class="product-card reveal" data-category="<?php echo kw_esc($product['categoria']); ?>">
                    <div class="product-thumb"><i class="fa-solid fa-layer-group"></i></div>
                    <div class="product-top">
                        <span class="pill"><?php echo kw_esc(kw_category_name($product['categoria'])); ?></span>
                        <span class="pill"><?php echo kw_esc($product['marca']); ?></span>
                    </div>
                    <h3><?php echo kw_esc($product['nome']); ?></h3>
                    <p><?php echo kw_esc($product['descricao']); ?></p>
                    <a class="btn btn-secondary" href="<?php echo kw_esc(kw_whatsapp_link('Ola! Quero cotacao para: ' . $product['nome'])); ?>" target="_blank" rel="noopener">Solicitar orcamento</a>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</main>

<?php kw_render_footer(); ?>
