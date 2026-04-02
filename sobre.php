<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/site_layout.php';

$metaTitle = 'Sobre a Karla Wollinger Representacoes';
$metaDescription = 'Conheca a trajetoria da Karla Wollinger, representante comercial com foco em higiene, limpeza e embalagens para empresas em Curitiba.';

kw_render_head($metaTitle, $metaDescription, 'sobre');
?>

<main class="section">
    <div class="container">
        <div class="section-head reveal">
            <h1>Sobre a Karla Wollinger</h1>
            <p>Atendimento comercial humano, consultivo e orientado a resultado para empresas e revendas.</p>
        </div>

        <div class="cards-grid">
            <article class="card reveal">
                <div class="card-icon"><i class="fa-solid fa-user-tie"></i></div>
                <h3>Quem e Karla Wollinger</h3>
                <p>Profissional com atuacao em representacao comercial de linhas de higiene, limpeza, papeis e embalagens para operacoes B2B.</p>
            </article>
            <article class="card reveal">
                <div class="card-icon"><i class="fa-solid fa-briefcase"></i></div>
                <h3>Atuacao comercial</h3>
                <p>Conexao entre marcas reconhecidas e clientes empresariais que precisam de confiabilidade no abastecimento.</p>
            </article>
            <article class="card reveal">
                <div class="card-icon"><i class="fa-solid fa-handshake"></i></div>
                <h3>Compromisso</h3>
                <p>Relacionamento de longo prazo, clareza no atendimento e recomendacoes de compra conforme necessidade real.</p>
            </article>
            <article class="card reveal">
                <div class="card-icon"><i class="fa-solid fa-store"></i></div>
                <h3>Foco em empresas e revendas</h3>
                <p>Solucoes para escritorios, condominios, clinicas, escolas, comercios e operacoes industriais.</p>
            </article>
            <article class="card reveal">
                <div class="card-icon"><i class="fa-solid fa-map"></i></div>
                <h3>Regiao atendida</h3>
                <p>Curitiba e regiao metropolitana com suporte comercial agil e proximidade no atendimento.</p>
            </article>
        </div>

        <section class="section">
            <div class="cta-band reveal">
                <div>
                    <h3>Vamos montar uma linha de abastecimento sob medida para sua empresa?</h3>
                    <p>Fale direto no WhatsApp e receba orientacao de forma rapida.</p>
                </div>
                <div class="cta-actions">
                    <a class="btn btn-primary" href="<?php echo kw_esc(kw_whatsapp_link('Oi Karla! Quero atendimento para minha empresa.')); ?>" target="_blank" rel="noopener">Falar no WhatsApp</a>
                    <a class="btn btn-secondary" href="/wkprodutos/contato.php">Ir para contato</a>
                </div>
            </div>
        </section>
    </div>
</main>

<?php kw_render_footer(); ?>
