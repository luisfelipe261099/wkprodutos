<?php

declare(strict_types=1);

const KW_WHATSAPP_NUMBER = '5541998593242';
const KW_WHATSAPP_LABEL = '(41) 99859-3242';
const KW_CONTACT_EMAIL = 'karlawollinger02@gmail.com';

function kw_site_categories(): array
{
    return [
        [
            'slug' => 'papeis',
            'nome' => 'Papeis',
            'descricao' => 'Papel toalha, papel higienico institucional e linhas para alto consumo.',
            'icone' => 'fa-scroll'
        ],
        [
            'slug' => 'equipamentos',
            'nome' => 'Equipamentos e Higiene',
            'descricao' => 'Dispensers, equipamentos de apoio e solucoes para higiene profissional.',
            'icone' => 'fa-pump-soap'
        ],
        [
            'slug' => 'sacos-de-lixo',
            'nome' => 'Sacos de Lixo e Embalagens',
            'descricao' => 'Linhas resistentes para coleta, organizacao e acondicionamento.',
            'icone' => 'fa-bag-shopping'
        ],
        [
            'slug' => 'aerossol',
            'nome' => 'Aerossol',
            'descricao' => 'Odorizadores e produtos de performance para ambientes corporativos.',
            'icone' => 'fa-spray-can-sparkles'
        ],
        [
            'slug' => 'limpeza-profissional',
            'nome' => 'Limpeza Profissional',
            'descricao' => 'Desengraxantes, desinfetantes e linhas tecnicas para empresas.',
            'icone' => 'fa-shield-virus'
        ],
        [
            'slug' => 'lavanderia',
            'nome' => 'Lavanderia',
            'descricao' => 'Produtos para lavanderias industriais e operacoes de alto giro.',
            'icone' => 'fa-soap'
        ],
        [
            'slug' => 'descartaveis',
            'nome' => 'Guardanapos e Descartaveis',
            'descricao' => 'Descartaveis para food service, clinicas, eventos e operacao diaria.',
            'icone' => 'fa-utensils'
        ]
    ];
}

function kw_site_brands(): array
{
    return ['BellPlus', 'Suave Lar', 'Troppel', 'Estilo', 'AFP', 'Baston', 'Ecopel'];
}

function kw_site_benefits(): array
{
    return [
        ['titulo' => 'Direto da fabrica', 'icone' => 'fa-industry', 'texto' => 'Produtos direto do fabricante, sem intermediarios. Voce paga menos e recebe com qualidade garantida.'],
        ['titulo' => 'Menor preco do mercado', 'icone' => 'fa-tag', 'texto' => 'Sem markup de distribuidores. Preco de fabrica repassado direto para sua empresa.'],
        ['titulo' => 'Atendimento rapido', 'icone' => 'fa-bolt', 'texto' => 'Resposta agil em ate 2 horas para operacoes que nao podem parar.'],
        ['titulo' => 'Variedade de produtos', 'icone' => 'fa-boxes-stacked', 'texto' => 'Mais de 200 itens em higiene, limpeza, embalagens e descartaveis.'],
        ['titulo' => 'Sem pedido minimo alto', 'icone' => 'fa-hand-holding-dollar', 'texto' => 'Condicoes acessiveis para pequenas e medias empresas comprarem direto de fabrica.'],
        ['titulo' => 'Marcas de fabrica', 'icone' => 'fa-award', 'texto' => '7 fabricas representadas com certificacoes de qualidade e registro ANVISA.'],
        ['titulo' => 'Orcamento gratis', 'icone' => 'fa-file-signature', 'texto' => 'Cotacao gratuita e personalizada por WhatsApp, formulario ou contato direto.'],
        ['titulo' => 'Entrega em Curitiba e regiao', 'icone' => 'fa-truck-fast', 'texto' => 'Logistica direta das fabricas para Curitiba, regiao metropolitana e todo o Parana.']
    ];
}

function kw_site_products(): array
{
    return [
        ['nome' => 'Papel Toalha Interfolhado', 'marca' => 'Ecopel', 'categoria' => 'papeis', 'descricao' => 'Alta absorcao para banheiros e cozinhas industriais.'],
        ['nome' => 'Papel Higienico Rolao', 'marca' => 'BellPlus', 'categoria' => 'papeis', 'descricao' => 'Maior rendimento para alto fluxo.'],
        ['nome' => 'Saco de Lixo Reforcado 100L', 'marca' => 'Baston', 'categoria' => 'sacos-de-lixo', 'descricao' => 'Resistencia superior para operacao pesada.'],
        ['nome' => 'Saco de Lixo 60L', 'marca' => 'AFP', 'categoria' => 'sacos-de-lixo', 'descricao' => 'Versatil para comercios, escritorios e condominios.'],
        ['nome' => 'Detergente Profissional', 'marca' => 'Suave Lar', 'categoria' => 'limpeza-profissional', 'descricao' => 'Limpeza eficiente com rendimento elevado.'],
        ['nome' => 'Desinfetante Hospitalar', 'marca' => 'Troppel', 'categoria' => 'limpeza-profissional', 'descricao' => 'Acao antimicrobiana para ambientes criticos.'],
        ['nome' => 'Odorizador Aerossol', 'marca' => 'Estilo', 'categoria' => 'aerossol', 'descricao' => 'Fragrancia duradoura para ambientes corporativos.'],
        ['nome' => 'Dispenser para Papel Toalha', 'marca' => 'BellPlus', 'categoria' => 'equipamentos', 'descricao' => 'Equipamento pratico e resistente para banheiros.'],
        ['nome' => 'Dispenser para Sabonete', 'marca' => 'Troppel', 'categoria' => 'equipamentos', 'descricao' => 'Controle de consumo e higiene no ponto de uso.'],
        ['nome' => 'Guardanapo Premium', 'marca' => 'Ecopel', 'categoria' => 'descartaveis', 'descricao' => 'Acabamento de qualidade para atendimento ao cliente.'],
        ['nome' => 'Copo Descartavel 200ml', 'marca' => 'AFP', 'categoria' => 'descartaveis', 'descricao' => 'Ideal para escritorios, recepcoes e eventos.'],
        ['nome' => 'Alvejante para Lavanderia', 'marca' => 'Suave Lar', 'categoria' => 'lavanderia', 'descricao' => 'Performance para operacao de lavanderia profissional.']
    ];
}

function kw_category_name(string $slug): string
{
    foreach (kw_site_categories() as $category) {
        if ($category['slug'] === $slug) {
            return $category['nome'];
        }
    }

    return 'Categoria';
}
