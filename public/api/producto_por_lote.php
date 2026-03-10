<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../models/SAPCatalog.php';
require_once __DIR__ . '/../../helpers/qr_parser.php';

use Models\SAPCatalog;
use function Helpers\parse_qr;

try {

    $lote  = isset($_GET['lote']) ? trim((string)$_GET['lote']) : '';
    $qrRaw = isset($_GET['qr']) ? trim((string)$_GET['qr']) : '';
    $debug = isset($_GET['debug']) ? (bool)intval($_GET['debug']) : false;

    if (!function_exists('sap_b1_select')) {
        throw new RuntimeException('sap_b1_select() no disponible');
    }

    /**
     * =========================================
     * CASO 1: VIENE QR COMPLETO
     * =========================================
     */

    if ($qrRaw !== '') {

        $parsed = parse_qr($qrRaw);

        $lote = $parsed['lote'] ?? '';

        if ($lote === '') {

            echo json_encode([
                'ok' => false,
                'error' => 'QR_NO_CONTIENE_LOTE'
            ]);

            exit;
        }

        $data = SAPCatalog::findByBatchFlexible($lote, $debug);

        if ($debug) {

            echo json_encode([
                'ok' => true,
                'parsed' => $parsed,
                'debug' => $data
            ], JSON_UNESCAPED_UNICODE);

            exit;
        }

        if (!$data) {

            echo json_encode([
                'ok' => false,
                'error' => 'LOTE_NO_ENCONTRADO_EN_SAP',
                'lote' => $lote
            ]);

            exit;
        }

        echo json_encode([
            'ok' => true,
            'source' => 'SAP',
            'item_code' => $data['item_code'] ?? null,
            'item_name' => $data['item_name'] ?? null
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }


    /**
     * =========================================
     * CASO 2: BUSQUEDA DIRECTA POR LOTE
     * =========================================
     */

    if ($lote !== '') {

        $data = SAPCatalog::findByBatchFlexible($lote, $debug);

        if ($debug) {

            echo json_encode([
                'ok' => true,
                'debug' => $data
            ], JSON_UNESCAPED_UNICODE);

            exit;
        }

        if (!$data) {

            echo json_encode([
                'ok' => false,
                'error' => 'LOTE_NO_ENCONTRADO',
                'lote' => $lote
            ]);

            exit;
        }

        echo json_encode([
            'ok' => true,
            'source' => 'SAP',
            'item_code' => $data['item_code'] ?? null,
            'item_name' => $data['item_name'] ?? null
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }


    /**
     * =========================================
     * ERROR PARAMETROS
     * =========================================
     */

    echo json_encode([
        'ok' => false,
        'error' => 'PARAMETROS_INVALIDOS',
        'hint' => 'Use ?qr=... o ?lote=...'
    ]);

} catch (Throwable $e) {

    http_response_code(500);

    echo json_encode([
        'ok' => false,
        'error' => 'SERVER_ERROR',
        'msg' => $e->getMessage()
    ]);
}