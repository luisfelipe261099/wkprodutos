<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/site_layout.php';

$metaTitle = 'Contato | Karla Wollinger Representacoes em Curitiba';
$metaDescription = 'Entre em contato para solicitar cotacao de produtos de limpeza, higiene, papeis e embalagens para empresas em Curitiba e regiao.';

$success = false;
$nome = trim($_POST['nome'] ?? '');
$empresa = trim($_POST['empresa'] ?? '');
$whatsapp = trim($_POST['whatsapp'] ?? '');
$email = trim($_POST['email'] ?? '');
$cidade = trim($_POST['cidade'] ?? '');
$mensagem = trim($_POST['mensagem'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $success = $nome !== '' && $whatsapp !== '';
}

kw_render_head($metaTitle, $metaDescription, 'contato');
?>

<main class="section">
    <div class="container">
        <div class="section-head reveal">
            <h1>Contato comercial</h1>
            <p>Solicite atendimento para sua empresa e receba retorno rapido.</p>
        </div>

        <?php if ($success): ?>
            <div class="form-card reveal" style="margin-bottom: 1rem; border-color: #bde8dc; background: #f4fffb;">
                <strong>Mensagem recebida.</strong>
                <p>Obrigado, <?php echo kw_esc($nome); ?>. Sua solicitacao foi registrada e entraremos em contato em breve.</p>
            </div>
        <?php endif; ?>

        <div class="cards-grid" style="margin-bottom: 1rem;">
            <article class="card reveal">
                <div class="card-icon"><i class="fa-solid fa-phone"></i></div>
                <h3>WhatsApp</h3>
                <p><?php echo kw_esc(KW_WHATSAPP_LABEL); ?></p>
                <a class="btn btn-secondary" href="<?php echo kw_esc(kw_whatsapp_link('Oi! Quero atendimento comercial.')); ?>" target="_blank" rel="noopener">Abrir WhatsApp</a>
            </article>
            <article class="card reveal">
                <div class="card-icon"><i class="fa-solid fa-envelope"></i></div>
                <h3>E-mail</h3>
                <p><?php echo kw_esc(KW_CONTACT_EMAIL); ?></p>
                <a class="btn btn-secondary" href="mailto:<?php echo kw_esc(KW_CONTACT_EMAIL); ?>">Enviar e-mail</a>
            </article>
            <article class="card reveal">
                <div class="card-icon"><i class="fa-solid fa-location-dot"></i></div>
                <h3>Area atendida</h3>
                <p>Curitiba e regiao metropolitana.</p>
            </article>
        </div>

        <div class="form-card reveal">
            <form method="post" action="contato.php">
                <div class="form-grid">
                    <div>
                        <label for="nome">Nome</label>
                        <input id="nome" name="nome" type="text" value="<?php echo kw_esc($nome); ?>" required>
                    </div>
                    <div>
                        <label for="empresa">Empresa</label>
                        <input id="empresa" name="empresa" type="text" value="<?php echo kw_esc($empresa); ?>">
                    </div>
                    <div>
                        <label for="whatsapp">WhatsApp</label>
                        <input id="whatsapp" name="whatsapp" type="text" value="<?php echo kw_esc($whatsapp); ?>" required>
                    </div>
                    <div>
                        <label for="email">E-mail</label>
                        <input id="email" name="email" type="email" value="<?php echo kw_esc($email); ?>">
                    </div>
                    <div>
                        <label for="cidade">Cidade</label>
                        <input id="cidade" name="cidade" type="text" value="<?php echo kw_esc($cidade); ?>">
                    </div>
                    <div style="grid-column: 1 / -1;">
                        <label for="mensagem">Mensagem</label>
                        <textarea id="mensagem" name="mensagem" placeholder="Conte rapidamente o que sua empresa precisa."><?php echo kw_esc($mensagem); ?></textarea>
                    </div>
                    <div style="grid-column: 1 / -1;">
                        <button class="btn btn-primary" type="submit">Receber atendimento rapido</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</main>

<?php kw_render_footer(); ?>
