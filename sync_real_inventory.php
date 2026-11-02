<?php
/**
 * Sincronizador Limpio con la Nube de Grupo Andino
 * Vacía por completo cualquier dato de muestra anterior y carga únicamente
 * el inventario, agencias y movimientos reales de la Nube de Grupo Andino.
 */

require_once __DIR__ . '/backend-bridge/GravityBridge.php';

try {
    $config = require __DIR__ . '/config.php';
    $pdo = new PDO("mysql:host={$config['db_host']};dbname={$config['db_name']};charset=utf8mb4", $config['db_user'], $config['db_pass']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS agencies (
            id INT AUTO_INCREMENT PRIMARY KEY,
            cloud_id VARCHAR(100) NULL,
            agency_code VARCHAR(100),
            name VARCHAR(255) NOT NULL,
            owner VARCHAR(255) NULL,
            supervisor VARCHAR(255) NULL,
            zone VARCHAR(255) NULL,
            address VARCHAR(255) NULL,
            phone VARCHAR(100) NULL,
            UNIQUE KEY idx_agency_name (name)
        );
    ");

    $pdo->exec("ALTER TABLE agencies ADD COLUMN IF NOT EXISTS cloud_id VARCHAR(100) NULL DEFAULT NULL;");
    $pdo->exec("ALTER TABLE assets ADD COLUMN IF NOT EXISTS cloud_id VARCHAR(100) NULL DEFAULT NULL;");
    $pdo->exec("ALTER TABLE assets ADD COLUMN IF NOT EXISTS category_name VARCHAR(150) NULL DEFAULT NULL;");
    $pdo->exec("ALTER TABLE assets ADD COLUMN IF NOT EXISTS model_name VARCHAR(150) NULL DEFAULT NULL;");
    $pdo->exec("ALTER TABLE assets ADD COLUMN IF NOT EXISTS agency_name VARCHAR(255) NULL DEFAULT NULL;");
    $pdo->exec("ALTER TABLE assets ADD COLUMN IF NOT EXISTS owner_name VARCHAR(255) NULL DEFAULT NULL;");
    $pdo->exec("ALTER TABLE assets ADD COLUMN IF NOT EXISTS receptor_name VARCHAR(255) NULL DEFAULT NULL;");
    $pdo->exec("ALTER TABLE grav_logs ADD COLUMN IF NOT EXISTS agency_name VARCHAR(255) NULL DEFAULT NULL;");

    // Limpiar por completo todas las tablas para eliminar cualquier equipo, modelo, categoría, agencia o log de muestra
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
    $pdo->exec("TRUNCATE TABLE grav_logs;");
    $pdo->exec("TRUNCATE TABLE gravity_asset_signatures;");
    $pdo->exec("TRUNCATE TABLE assets;");
    $pdo->exec("TRUNCATE TABLE models;");
    $pdo->exec("TRUNCATE TABLE categories;");
    $pdo->exec("TRUNCATE TABLE agencies;");
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");

    $jsonPath = __DIR__ . '/Respaldo_Inventario_GrupoAndino_respaldo.json';
    if (!file_exists($jsonPath)) {
        die("No se encontró el archivo Respaldo_Inventario_GrupoAndino_respaldo.json\n");
    }

    $data = json_decode(file_get_contents($jsonPath), true);

    $pdo->beginTransaction();

    // 1. Insertar únicamente las Agencias reales de la Nube de Grupo Andino
    $stmtAg = $pdo->prepare("INSERT IGNORE INTO agencies (cloud_id, agency_code, name, owner, supervisor, zone, address, phone) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $agCount = 0;
    foreach ($data['agencies'] as $ag) {
        $name = trim($ag['name'] ?? '');
        if (!$name) continue;
        $stmtAg->execute([
            $ag['id'] ?? '',
            $ag['code'] ?? $ag['id'] ?? '',
            $name,
            $ag['owner'] ?? '-',
            $ag['supervisor'] ?? '-',
            $ag['zone'] ?? '-',
            $ag['address'] ?? '-',
            $ag['phone'] ?? '-'
        ]);
        $agCount++;
    }

    // 2. Insertar únicamente el Inventario real de la Nube de Grupo Andino
    $catMap = [];
    $stmtInsCat = $pdo->prepare("INSERT INTO categories (name) VALUES (?)");
    $stmtInsMod = $pdo->prepare("INSERT INTO models (name, category_id) VALUES (?, ?)");
    $modMap = [];

    $stmtInsAsset = $pdo->prepare("
        INSERT INTO assets (
            cloud_id, asset_tag, name, brand, category_name, model_name, model_id,
            purchase_cost, qr_code, barcode, ram, storage, processor,
            agency_name, owner_name, receptor_name
        ) VALUES (?, ?, ?, ?, ?, ?, ?, 100.00, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmtInsSig = $pdo->prepare("
        INSERT INTO gravity_asset_signatures (
            asset_id, gravitational_mass, orbital_radius, signature_hash, stasis_field_active, asset_state
        ) VALUES (?, ?, ?, ?, ?, ?)
    ");

    $serialToAssetId = [];
    $invImported = 0;

    foreach ($data['inventory'] as $item) {
        $serial = trim($item['serial'] ?? '');
        if (!$serial) continue;
        $serialKey = mb_strtoupper($serial);
        if (isset($serialToAssetId[$serialKey])) continue;

        $typeName = trim($item['type'] ?? 'Equipo General') ?: 'Equipo General';
        $typeKey = mb_strtolower($typeName);
        if (!isset($catMap[$typeKey])) {
            $stmtInsCat->execute([$typeName]);
            $catMap[$typeKey] = $pdo->lastInsertId();
        }
        $catId = $catMap[$typeKey];

        $modelName = trim($item['model'] ?? 'Estándar') ?: 'Estándar';
        $compositeModKey = $catId . '::' . mb_strtolower($modelName);
        if (!isset($modMap[$compositeModKey])) {
            $stmtInsMod->execute([$modelName, $catId]);
            $modMap[$compositeModKey] = $pdo->lastInsertId();
        }
        $modelId = $modMap[$compositeModKey];

        $brand = trim($item['brand'] ?? 'Genérico') ?: 'Genérico';
        $ram = trim($item['ram'] ?? '') ?: 'N/A';
        $disk = trim($item['disk'] ?? '') ?: 'N/A';
        $proc = trim($item['processor'] ?? '') ?: 'N/A';
        $agency = trim($item['agency'] ?? '-');
        if ($agency === '-' || $agency === '') $agency = null;
        $owner = trim($item['owner'] ?? '-');
        $receptor = trim($item['receptor'] ?? '-');

        $qr = 'QR-' . $serial;
        $bc = $serial;

        $stmtInsAsset->execute([
            $item['id'] ?? '',
            $serial,
            $typeName . ' ' . $brand,
            $brand,
            $typeName,
            $modelName,
            $modelId,
            $qr,
            $bc,
            $ram,
            $disk,
            $proc,
            $agency,
            $owner,
            $receptor
        ]);
        $assetId = $pdo->lastInsertId();
        $serialToAssetId[$serialKey] = $assetId;

        $rawStatus = trim($item['status'] ?? 'En Depósito');
        $dbState = 'BODEGA';
        $stasis = 1;
        if ($rawStatus === 'Asignado') {
            $dbState = 'ASIGNADO';
            $stasis = 0;
        } elseif ($rawStatus === 'En Mantenimiento') {
            $dbState = 'MANTENIMIENTO';
            $stasis = 1;
        }

        $mass = (stripos($typeName, 'laptop') !== false || stripos($typeName, 'cpu') !== false) ? 240 : 60;
        $radius = rand(25, 110) / 10;
        $hash = hash('sha256', $serial . $dbState);

        $stmtInsSig->execute([$assetId, $mass, $radius, $hash, $stasis, $dbState]);
        $invImported++;
    }

    // 3. Importar el historial real de movimientos de la Nube hacia grav_logs
    if (!empty($data['movements']) && is_array($data['movements'])) {
        $stmtLog = $pdo->prepare("
            INSERT INTO grav_logs (asset_id, action_type, previous_signature, new_signature, gravitational_shift, timestamp, motive, agency_name)
            VALUES (?, ?, 'CLOUD_SYNC', ?, 0.0, ?, ?, ?)
        ");
        foreach (array_slice($data['movements'], 0, 120) as $mov) {
            $items = $mov['items'] ?? [];
            if (!is_array($items) || count($items) === 0) continue;
            $firstSerial = mb_strtoupper(trim($items[0]['serial'] ?? ''));
            $assetId = $serialToAssetId[$firstSerial] ?? null;
            if (!$assetId) continue;

            $mType = mb_strtolower($mov['type'] ?? '');
            $act = 'ASIGNADO';
            if (strpos($mType, 'reingreso') !== false || strpos($mType, 'devolucion') !== false || strpos($mType, 'entrada') !== false) {
                $act = 'DEPÓSITO';
            } elseif (strpos($mType, 'mantenimiento') !== false || strpos($mType, 'taller') !== false) {
                $act = 'MANTENIMIENTO';
            }

            $motive = trim(($mov['transactionType'] ?? '') . ' ' . ($mov['obs'] ?? '')) ?: ($mov['type'] ?? 'Movimiento Nube');
            $agName = ($mov['agency'] ?? '-') !== '-' ? $mov['agency'] : null;
            $ts = !empty($mov['date']) ? substr($mov['date'], 0, 19) : date('Y-m-d H:i:s');
            if (strlen($ts) === 16) $ts .= ':00';

            $stmtLog->execute([
                $assetId,
                $act,
                hash('sha256', $firstSerial . $ts),
                $ts,
                $motive,
                $agName
            ]);
        }
    }

    $pdo->commit();
    echo "DEPURACIÓN COMPLETA -> Equipos Nube: $invImported | Agencias Nube: $agCount\n";
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
    echo "ERROR: " . $e->getMessage() . "\n";
}
