<?php
function parse_qr_payload(string $raw): array {

    // 1) Normalizar
    $raw_norm = preg_replace('/\s+/', '', trim($raw));

    // 2) Separar sufijo # (si existe)
    $nv = '';
    $gs1 = $raw_norm;

    if (strpos($raw_norm, '#') !== false) {
        [$gs1, $nv] = explode('#', $raw_norm, 2);
        $nv = trim($nv);
    }

    $out = [
        'qr_raw'   => $raw_norm,
        'gs1'      => $gs1,
        'nv'       => $nv,
        'gtin14'   => '',
        'ean13'    => '',
        'cantidad' => 0,
        'lote'     => '',
    ];

    // 3) Parser simple AIs: 02, 37, 10
    $i = 0;
    $len = strlen($gs1);

    while ($i < $len) {
        if ($i + 2 > $len) break;

        $ai = substr($gs1, $i, 2);
        $i += 2;

        if ($ai === '02') { // GTIN-14 fijo
            if ($i + 14 <= $len) {
                $gtin14 = substr($gs1, $i, 14);
                $i += 14;
                $out['gtin14'] = $gtin14;

                // Generar EAN13
                if ($gtin14[0] === '0') {
                    $out['ean13'] = substr($gtin14, 1);
                } else {
                    $out['ean13'] = substr($gtin14, -13);
                }
            } else {
                break;
            }
        }

        elseif ($ai === '37') { // cantidad
            $chunk = substr($gs1, $i, 8);

            if (preg_match('/^\d+$/', $chunk)) {
                $i += 8;
                $out['cantidad'] = (int)ltrim($chunk, '0');
            } else {
                if (preg_match('/^(\d{1,8})/', substr($gs1, $i), $m)) {
                    $i += strlen($m[1]);
                    $out['cantidad'] = (int)ltrim($m[1], '0');
                }
            }
        }

        elseif ($ai === '10') { // lote
            $lote = substr($gs1, $i);
            $out['lote'] = $lote !== '' ? $lote : '';
            break;
        }

        else {
            // AI desconocido → salir sin romper
            break;
        }
    }

    return $out;
}
