<?php

require_once __DIR__ . '/backend-bridge/GravityBridge.php';
use Antigraviti\Core\GravityBridge;

header('Content-Type: application/json');

$bridge = new GravityBridge();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (isset($_GET['action']) && $_GET['action'] === 'get_logs') {
        echo $bridge->getGravLogs();
    } elseif (isset($_GET['action']) && $_GET['action'] === 'get_asset_by_code') {
        $code = $_GET['code'] ?? '';
        echo $bridge->getAssetByCode($code);
    } else {
        echo $bridge->getAssetsForNexusDisplay();
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (isset($input['action']) && $input['action'] === 'change_state') {
        $assetId = $input['asset_id'];
        $newState = $input['new_state'];
        $motive = $input['motive'] ?? 'N/A';
        $agencyName = isset($input['agency']) ? trim($input['agency']) : null;
        $receptorName = isset($input['receptor']) ? trim($input['receptor']) : null;
        
        $pdo = new PDO("mysql:host=127.0.0.1;dbname=snipeit_a_am;charset=utf8mb4", "root", "");
        $stmt = $pdo->prepare("SELECT asset_tag FROM assets WHERE id = ?");
        $stmt->execute([$assetId]);
        $tag = $stmt->fetchColumn() ?: "UNKNOWN";
        
        $result = $bridge->changeState($assetId, $tag, $newState, $motive, $agencyName, $receptorName);
        echo json_encode($result);
    } 
    elseif (isset($input['action']) && $input['action'] === 'forjar_equipos') {
        try {
            $pdo = new PDO("mysql:host=127.0.0.1;dbname=snipeit_a_am;charset=utf8mb4", "root", "");
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            $category = trim($input['category'] ?? 'Equipo General') ?: 'Equipo General';
            $brand = trim($input['brand'] ?? 'Genérico') ?: 'Genérico';
            $model = trim($input['model'] ?? 'Estándar') ?: 'Estándar';
            $serial = strtoupper(trim($input['serial'] ?? ''));

            if ($serial === '') {
                echo json_encode(['success' => false, 'error' => 'El serial del equipo es obligatorio.']);
                exit;
            }

            $chk = $pdo->prepare("SELECT COUNT(*) FROM assets WHERE asset_tag = ?");
            $chk->execute([$serial]);
            if ($chk->fetchColumn() > 0) {
                echo json_encode(['success' => false, 'error' => "El serial {$serial} ya está registrado en el inventario."]);
                exit;
            }

            $catStmt = $pdo->prepare("SELECT id FROM categories WHERE LOWER(name) = LOWER(?) LIMIT 1");
            $catStmt->execute([$category]);
            $catId = $catStmt->fetchColumn();
            if (!$catId) {
                $insCat = $pdo->prepare("INSERT INTO categories (name) VALUES (?)");
                $insCat->execute([$category]);
                $catId = $pdo->lastInsertId();
            }

            $modStmt = $pdo->prepare("SELECT id FROM models WHERE LOWER(name) = LOWER(?) AND category_id = ? LIMIT 1");
            $modStmt->execute([$model, $catId]);
            $modelId = $modStmt->fetchColumn();
            if (!$modelId) {
                $insMod = $pdo->prepare("INSERT INTO models (name, category_id) VALUES (?, ?)");
                $insMod->execute([$model, $catId]);
                $modelId = $pdo->lastInsertId();
            }

            $qr = 'QR-' . $serial;
            $bc = $serial;

            $stmt = $pdo->prepare("INSERT INTO assets (asset_tag, name, brand, category_name, model_name, model_id, purchase_cost, qr_code, barcode, ram, storage, processor) VALUES (?, ?, ?, ?, ?, ?, 100.00, ?, ?, 'N/A', 'N/A', 'N/A')");
            $stmt->execute([$serial, $category . ' ' . $brand, $brand, $category, $model, $modelId, $qr, $bc]);
            
            $synced = $bridge->syncMissingAssets();
            
            echo json_encode(['success' => true, 'creados' => 1, 'serial' => $serial, 'sincronizados' => $synced]);
        } catch(PDOException $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }
    else {
        echo json_encode(['error' => 'Acción no válida']);
    }
}
