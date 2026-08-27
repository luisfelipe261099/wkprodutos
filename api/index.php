<?php
/**
 * Front controller único do sistema.
 *
 * Por que existe: o vercel.json antigo declarava `{"src": "*.php"}`, o que faz
 * a Vercel criar UMA Serverless Function por arquivo PHP. Com 67 páginas na
 * raiz, todo deploy passava do limite do plano Hobby (12 funções) e falhava
 * com `exceeded_serverless_functions_per_deployment` — o site ficou parado no
 * último build que passou. Com um único entrypoint, o projeto usa 1 função.
 *
 * Este arquivo só resolve a URL para o script correspondente e o executa. Ele
 * não altera nenhuma página: cada arquivo continua sendo o mesmo, com o mesmo
 * $_GET/$_POST/$_SESSION e o mesmo __DIR__ (que é por arquivo, não do router).
 */

$raizProjeto = dirname(__DIR__);

/** Rotas amigáveis do site institucional (equivalem aos "routes" do vercel.json). */
const ROTAS_AMIGAVEIS = [
    ''                      => 'index.php',
    'inicio'                => 'index.php',
    'catalogo'              => 'catalogo.php',
    'marcas'                => 'marcas.php',
    'sobre'                 => 'sobre.php',
    'contato'               => 'contato.php',
    'blog'                  => 'blog.php',
    'papeis'                => 'categoria.php?cat=papeis',
    'higiene'               => 'categoria.php?cat=higiene',
    'sacos-de-lixo'         => 'categoria.php?cat=sacos-de-lixo',
    'aerossol'              => 'categoria.php?cat=aerossol',
    'limpeza-profissional'  => 'categoria.php?cat=limpeza-profissional',
    'embalagens'            => 'categoria.php?cat=embalagens',
    'equipamentos'          => 'categoria.php?cat=equipamentos',
    'descartaveis'          => 'categoria.php?cat=descartaveis',
    'lavanderia'            => 'categoria.php?cat=lavanderia',
];

/**
 * Descobre qual script da raiz atende a URL pedida.
 *
 * @return array{0:string,1:array} nome do arquivo e parâmetros extras de $_GET
 */
function resolver_rota(string $uri): array
{
    $caminho = parse_url($uri, PHP_URL_PATH);
    $caminho = trim(rawurldecode($caminho === false || $caminho === null ? '/' : $caminho), '/');

    // Rota amigável tem prioridade e pode trazer query string própria
    if (array_key_exists($caminho, ROTAS_AMIGAVEIS)) {
        $destino = ROTAS_AMIGAVEIS[$caminho];
        $extras = [];
        if (str_contains($destino, '?')) {
            [$destino, $query] = explode('?', $destino, 2);
            parse_str($query, $extras);
        }
        return [$destino, $extras];
    }

    // Qualquer outra coisa: só o nome do arquivo, sem subir diretórios
    $arquivo = basename($caminho);
    if (preg_match('/^[A-Za-z0-9._-]+\.php$/', $arquivo) !== 1) {
        return ['index.php', []];
    }

    return [$arquivo, []];
}

[$script, $parametrosExtras] = resolver_rota($_SERVER['REQUEST_URI'] ?? '/');

$alvo = $raizProjeto . '/' . $script;
if (!is_file($alvo)) {
    $alvo = $raizProjeto . '/index.php';
    $script = 'index.php';
}

// Parâmetros das rotas amigáveis não sobrescrevem o que veio na URL
foreach ($parametrosExtras as $chave => $valor) {
    if (!isset($_GET[$chave])) {
        $_GET[$chave] = $valor;
    }
}

// Várias páginas usam $_SERVER['PHP_SELF'] no action dos formulários, e o
// header.php marca o menu ativo por basename(PHP_SELF). Sem isso tudo
// apontaria para /api/index.php.
$_SERVER['PHP_SELF'] = '/' . $script;
$_SERVER['SCRIPT_NAME'] = '/' . $script;
$_SERVER['SCRIPT_FILENAME'] = $alvo;

chdir($raizProjeto);
require $alvo;
