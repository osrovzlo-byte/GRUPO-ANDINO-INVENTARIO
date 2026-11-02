<?php
/**
 * Setup Inventory - Antigraviti Asset Management
 * Este script conecta el GravityBridge a la base de datos local
 * e inicializa la estructura del inventario cuántico.
 */

require_once __DIR__ . '/backend-bridge/GravityBridge.php';
use Antigraviti\Core\GravityBridge;

// CONFIGURACIÓN DE BASE DE DATOS LOCAL
// Ajusta estos parámetros a tu entorno local (XAMPP, Laragon, etc.)
$host = '127.0.0.1';
$dbName = 'snipeit_a_am'; // Nombre de tu base de datos
$user = 'root';
$pass = '';

echo "<h1>A-AM // SECUENCIA DE INICIALIZACIÓN DEL NEXO</h1>";

try {
    // 1. Crear la base de datos si no existe (Requiere permisos de root)
    $tempDb = new PDO("mysql:host=$host;charset=utf8mb4", $user, $pass);
    $tempDb->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $tempDb->exec("CREATE DATABASE IF NOT EXISTS `$dbName`");
    echo "<p>[OK] Base de datos '$dbName' verificada/creada.</p>";
    
    // 2. Instanciar el GravityBridge
    $bridge = new GravityBridge($host, $dbName, $user, $pass);
    echo "<p>[OK] GravityBridge conectado a la matriz de datos local.</p>";

    // 3. Crear tabla mock de Snipe-IT (solo para desarrollo/pruebas)
    $mockSql = file_get_contents(__DIR__ . '/database/mock_snipeit_schema.sql');
    $tempDb->exec("USE `$dbName`");
    $tempDb->exec($mockSql);
    echo "<p>[OK] Tabla base de activos (Snipe-IT Mock) sincronizada.</p>";

    // 4. Inicializar el esquema Gravitacional (Pilar 2)
    $initResult = $bridge->initializeGravitySchema();
    echo "<p>[OK] $initResult</p>";

    // 5. Insertar firmas gravitacionales para los activos mock
    $pdo = new PDO("mysql:host=$host;dbname=$dbName;charset=utf8mb4", $user, $pass);
    $pdo->exec("
        INSERT IGNORE INTO gravity_asset_signatures (asset_id, gravitational_mass, signature_hash, stasis_field_active)
        VALUES 
        (1, 25.00, 'hash_cuantico_lpt1', 0),
        (2, 85.00, 'hash_cuantico_srv1', 1),
        (3, 12.00, 'hash_cuantico_lpt2', 1)
    ");
    echo "<p>[OK] Firmas gravitacionales base inyectadas en los activos.</p>";

    // 6. Probar la salida para el Nexo Frontend
    echo "<h2>Salida de Datos Gravitacionales (JSON para el Frontend 3D):</h2>";
    echo "<pre style='background:#111; color:#0f0; padding:10px; border-radius:5px;'>";
    echo $bridge->getAssetsForNexusDisplay();
    echo "</pre>";

    echo "<h3 style='color:green;'>ESTADO DEL SISTEMA: ÓPTIMO. INVENTARIO ESTRUCTURADO.</h3>";

} catch (PDOException $e) {
    echo "<p style='color:red;'>[ERROR CRÍTICO] Fluctuación en la conexión: " . $e->getMessage() . "</p>";
    echo "<p>Asegúrate de que MySQL/MariaDB esté en ejecución en tu entorno local (XAMPP/Laragon/etc).</p>";
}
