<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/site_layout.php';

$cat = trim($_GET['cat'] ?? 'papeis');
$categories = kw_site_categories();
$currentCategory = null;

foreach ($categories as $category) {
    if ($category['slug'] === $cat) {
        $currentCategory = $category;
        break;
    }
}

if ($currentCategory === null) {
    http_response_code(404);
    $currentCategory = [
        'slug' => 'categoria',
        'nome' => 'Categoria nao encontrada',
        'descricao' => 'A categoria solicitada nao foi encontrada.'
    ];
}

$metaTitle = $currentCategory['nome'] . ' em Curitiba | Karla Wollinger Representacoes';
$metaDescription = 'Cotacao de ' . strtolower($currentCategory['nome']) . ' para empresas em Curitiba e regiao com atendimento comercial rapido.';

kw_render_head($metaTitle, $metaDescription, 'produtos');

$products = array_filter(kw_site_products(), static fn(array $product): bool => $product['categoria'] === $currentCategory['slug']);
?>

<main class="section">
    <div class="container">
        <div class="section-head reveal">
            <h1><?php echo kw_esc($currentCategory['nome']); ?></h1>
            <p><?php echo kw_esc($currentCategory['descricao']); ?></p>
        </div>

        <?php if (!empty($products)): ?>
            <div class="product-grid">
                <?php foreach ($products as $product): ?>
                    <article class="product-card reveal" data-category="<?php echo kw_esc($product['categoria']); ?>">
                        <div class="product-thumb"><i class="fa-solid fa-box-open"></i></div>
                        <div class="product-top">
                            <span class="pill"><?php echo kw_esc($product['marca']); ?></span>
                        </div>
                        <h3><?php echo kw_esc($product['nome']); ?></h3>
                        <p><?php echo kw_esc($product['descricao']); ?></p>
                        <a class="btn btn-secondary" href="<?php echo kw_esc(kw_whatsapp_link('Quero cotacao de ' . $product['nome'])); ?>" target="_blank" rel="noopener">Solicitar orcamento</a>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="form-card reveal">
                <p>Estamos preparando esta categoria no catalogo. Fale com nossa equipe para receber recomendacoes imediatas.</p>
                <a class="btn btn-primary" href="<?php echo kw_esc(kw_whatsapp_link('Oi! Quero saber mais sobre ' . $currentCategory['nome'])); ?>" target="_blank" rel="noopener">Falar no WhatsApp</a>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php kw_render_footer(); ?>
