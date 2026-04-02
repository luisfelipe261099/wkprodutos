<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/site_layout.php';

$metaTitle = 'Marcas representadas em higiene e limpeza | Karla Wollinger';
$metaDescription = 'Conheca as marcas representadas pela Karla Wollinger para atender empresas em Curitiba e regiao com qualidade e custo-beneficio.';

kw_render_head($metaTitle, $metaDescription, 'marcas');
$brands = kw_site_brands();
?>

<main class="section">
    <div class="container">
        <div class="section-head reveal">
            <h1>Marcas representadas</h1>
            <p>Parcerias com marcas reconhecidas para entregar variedade, padrao de qualidade e seguranca de abastecimento.</p>
        </div>
        <div class="brand-grid">
            <?php foreach ($brands as $brand): ?>
                <div class="brand-item reveal"><?php echo kw_esc($brand); ?></div>
            <?php endforeach; ?>
        </div>

        <section class="section">
            <div class="cta-band reveal">
                <div>
                    <h3>Precisa de apoio para escolher a melhor marca por categoria?</h3>
                    <p>Receba orientacao comercial com base no perfil da sua operacao.</p>
                </div>
                <div class="cta-actions">
                    <a class="btn btn-primary" href="<?php echo kw_esc(kw_whatsapp_link('Oi! Quero ajuda para escolher marcas e categorias.')); ?>" target="_blank" rel="noopener">Falar no WhatsApp</a>
                    <a class="btn btn-secondary" href="<?php echo kw_esc(kw_site_url('contato.php')); ?>">Enviar formulario</a>
                </div>
            </div>
        </section>
    </div>
</main>

<?php kw_render_footer(); ?>
