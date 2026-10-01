<?php

/**
 * Fuso horario de Brasilia para todo o sistema.
 *
 * Por que existe: nem o PHP nem o MySQL assumem o fuso do Brasil sozinhos. Na
 * Vercel o PHP roda com date.timezone = UTC, e o servidor de banco tem o fuso
 * dele. Sem isso, tudo que mostra ou grava hora (data dos orcamentos, vendas,
 * agendamentos, "gerado em" dos PDFs, filtros de "hoje" nos relatorios) sai 3
 * horas adiantado.
 *
 * Este arquivo nao tem efeito colateral nenhum alem de definir o fuso padrao,
 * e pode ser incluido quantas vezes for preciso (use require_once).
 */

if (!function_exists('kw_timezone')) {
    /** Fuso usado pelo sistema. Sobrescrevivel por APP_TIMEZONE. */
    function kw_timezone(): string
    {
        $tz = getenv('APP_TIMEZONE');
        if ($tz === false || $tz === '' || !in_array($tz, DateTimeZone::listIdentifiers(), true)) {
            return 'America/Sao_Paulo';
        }
        return $tz;
    }
}

if (!function_exists('kw_timezone_offset')) {
    /**
     * Deslocamento atual do fuso no formato que o MySQL aceita em SET time_zone
     * (ex.: "-03:00"). Calculado na hora, entao um eventual retorno do horario
     * de verao passa a valer sem mudar codigo.
     */
    function kw_timezone_offset(): string
    {
        return (new DateTime('now', new DateTimeZone(kw_timezone())))->format('P');
    }
}

date_default_timezone_set(kw_timezone());
