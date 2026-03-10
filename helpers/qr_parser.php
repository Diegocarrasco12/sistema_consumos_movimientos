<?php

declare(strict_types=1);

namespace Helpers;

/**
 * ===============================================================
 * QR PARSER — VERSION INDUSTRIAL PARA SAP + ZEBRA
 * ===============================================================
 *
 * OBJETIVO PRINCIPAL
 *
 * Extraer SOLO el dato confiable del QR:
 *
 *    LOTE
 *
 * Con ese lote el sistema consulta SAP:
 *
 *    SAPCatalog::findByBatchFlexible($lote)
 *
 * y SAP devuelve:
 *
 *    ItemCode
 *    ItemName
 *    Quantity
 *
 * ===============================================================
 */


/**
 * Normaliza el string leído desde el QR
 */
function _qr_normalize(string $raw): string
{
    $s = str_replace(["\r", "\n", "\t"], ' ', $raw);
    $s = preg_replace('/\s+/', ' ', $s ?? '');
    return trim($s);
}



/**
 * ===============================================================
 * DETECTOR INTELIGENTE DE LOTES
 * ===============================================================
 *
 * Detecta lotes aunque tengan basura o ceros:
 *
 * 000103551-20  → 3551-20
 * 000010108-04  → 108-04
 * 00001095-09   → 95-09
 *
 */
function detectar_lote_inteligente(string $s): ?string
{

    if (!preg_match('/0+1?0*(\d{2,6})-(\d{1,4})/', $s, $m)) {
        return null;
    }

    $num = ltrim($m[1], '0');
    $suf = $m[2];

    if ($num === '') {
        $num = $m[1];
    }

    if (preg_match('/^10(\d{2,})$/', $num, $mm)) {
        $num = $mm[1];
    }

    return $num . '-' . $suf;
}

/**
 * ===============================================================
 * DETECTOR EXACTO DE PESO DESDE QR
 * ===============================================================
 *
 * Casos detectados en tus ejemplos:
 *
 * 0211700029300130371188,000103389-3#911000021   -> 1188
 * 021145003801010337850,0000104601-7#911000076   -> 850
 * 020411100811812137000020001072450-1#9121394    -> 20
 * 02040010010019573700012000107450-2#9121690     -> 120
 * 020400100100045537000180001073540-4#9121572    -> 180
 * 020454201304554137000005001074798-5#913692     -> 5
 */
function detectar_peso_inteligente(string $s): ?float
{
    $s = _qr_normalize($s);

    if ($s === '') {
        return null;
    }

    // Buscar el bloque completo del lote codificado al final
    if (!preg_match('/0+1?0*\d{2,6}-\d{1,4}(?=#|$)/', $s, $m, PREG_OFFSET_CAPTURE)) {
        return null;
    }

    $loteCodificado = $m[0][0] ?? '';
    $posLote        = $m[0][1] ?? 0;

    // Todo lo que está antes del lote
    $antesDelLote = substr($s, 0, $posLote);
    $antesDelLote = preg_replace('/\s+/', '', $antesDelLote ?? '');

    if ($antesDelLote === '') {
        return null;
    }

    // Buscar el último bloque que empieza con 37 y termina justo antes del lote
    if (!preg_match('/37([0-9,]+)$/', $antesDelLote, $pesoMatch)) {
        return null;
    }

    $rawPeso = $pesoMatch[1] ?? '';

    if ($rawPeso === '') {
        return null;
    }

    /**
     * CASO A
     * Viene con coma como separador antes del lote:
     * 371188,
     * 37850,
     */
    if (strpos($rawPeso, ',') !== false) {
        [$parteEntera] = explode(',', $rawPeso, 2);

        $digits = preg_replace('/\D+/', '', $parteEntera);

        if ($digits === '') {
            return null;
        }

        $digits = ltrim($digits, '0');
        if ($digits === '') {
            return 0.0;
        }

        return (float)$digits;
    }

    /**
     * CASO B
     * Viene sin coma, con 2 decimales implícitos:
     * 00002000 -> 20
     * 00012000 -> 120
     * 00018000 -> 180
     * 00000500 -> 5
     */
    $digits = preg_replace('/\D+/', '', $rawPeso);

    if ($digits === '') {
        return null;
    }

    $valor = ((float)$digits) / 100;

    // Si queda entero, devolver entero en float limpio
    if ((float)(int)$valor === $valor) {
        return (float)(int)$valor;
    }

    return $valor;
}



/**
 * ===============================================================
 * PARSER PRINCIPAL
 * ===============================================================
 */
function parse_qr(string $raw): array
{

    $s = _qr_normalize($raw);

    $out = [
        'lote'        => null,
        'docnum'      => null,
        'tarja_kg'    => null
    ];

    if ($s === '') {
        return $out;
    }



    /**
     * ============================================================
     * 1️⃣ DETECCION PRIORITARIA DE LOTE
     * ============================================================
     */

    $loteNuevo = detectar_lote_inteligente($s);

    if ($loteNuevo !== null) {
        $out['lote'] = $loteNuevo;
    } else {

        /**
         * ============================================================
         * MOTOR CLASICO DE LOTE (FALLBACK)
         * ============================================================
         */

        if (preg_match('/(?:^|[^\d])0*(\d{3,6}-\d{1,4})(?:[^\d]|$)/', $s, $m)) {

            $lote = ltrim($m[1], '0');

            [$num, $suf] = explode('-', $lote, 2);

            if (preg_match('/^10(\d{2,})$/', $num, $mm)) {
                $num = $mm[1];
            }

            $out['lote'] = $num . '-' . $suf;
        }
    }



    /**
     * ============================================================
     * 2️⃣ DETECTAR DOCNUM (si existe)
     * ============================================================
     */

    if (preg_match('/#(\d{6,})/', $s, $m)) {
        $out['docnum'] = $m[1];
    }



    /**
     * ============================================================
     * 3️⃣ DETECTAR TARJA KG (EXACTO)
     * ============================================================
     */

    $out['tarja_kg'] = detectar_peso_inteligente($s);

    return $out;
}




/**
 * ===============================================================
 * EXTRA: BUSCAR CANDIDATOS EN QR (UTIL PARA DEBUG)
 * ===============================================================
 */
function qr_extract_candidates(string $raw): array
{

    $s = _qr_normalize($raw);

    $cands = [];

    if (preg_match_all('/\b\d{12,18}\b/', $s, $nums)) {
        foreach ($nums[0] as $n) $cands[] = $n;
    }

    if (preg_match_all('/\b(?!NULL\b)[A-Z0-9\-_\.]{8,}\b/i', $s, $alnums)) {
        foreach ($alnums[0] as $t) {
            if (strcasecmp($t, 'NULL') !== 0) $cands[] = $t;
        }
    }

    if (strpos($s, ';') !== false) {
        foreach (explode(';', $s) as $p) {
            $p = trim($p);

            if ($p !== '' && strcasecmp($p, 'NULL') !== 0 && strlen($p) >= 5) {
                $cands[] = $p;
            }
        }
    }

    $cands = array_map(function ($x) {
        return trim(str_replace([",", "\t"], "", $x));
    }, $cands);
    $cands = array_values(array_unique(array_filter($cands)));

    return [$s, $cands];
}
