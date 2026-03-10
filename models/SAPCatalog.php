<?php

declare(strict_types=1);

namespace Models;

class SAPCatalog
{

    public static function findByBatchFlexible(string $lote, bool $debug = false): array
    {

        $lote = trim($lote);

        if ($lote === '') {
            return [];
        }

        $sql = "
        SELECT
            DB_NAME() AS db_name,
            @@SERVERNAME AS server_name,
            T0.ItemCode   AS item_code,
            T1.ItemName   AS item_name,
            T1.InvntryUom AS uom,
            T2.DistNumber AS lote,
            SUM(T0.Quantity) AS quantity
        FROM OBTQ T0
        INNER JOIN OBTN T2
            ON T0.MdAbsEntry = T2.AbsEntry
        INNER JOIN OITM T1
            ON T0.ItemCode = T1.ItemCode
        WHERE
            T2.DistNumber = ?
        GROUP BY
            T0.ItemCode,
            T1.ItemName,
            T1.InvntryUom,
            T2.DistNumber
        ";

        $rows = \sap_b1_select($sql, [$lote]);

        if ($debug) {

            echo "<pre>";
            echo "LOTE RECIBIDO:\n";
            var_dump($lote);

            echo "\nCONSULTA SQL:\n";
            echo $sql;

            echo "\n\nRESULTADO SAP:\n";
            print_r($rows);

            echo "</pre>";

            exit;
        }

        if (!$rows || !isset($rows[0])) {
            return [];
        }

        $r = $rows[0];

        return [
            'item_code' => $r['item_code'] ?? null,
            'item_name' => $r['item_name'] ?? null,
            'uom'       => $r['uom'] ?? null,
            'lote'      => $r['lote'] ?? null,
            'quantity'  => isset($r['quantity']) ? (float)$r['quantity'] : null
        ];
    }

}