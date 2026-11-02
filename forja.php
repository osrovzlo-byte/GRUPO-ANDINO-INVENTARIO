<?php
require_once __DIR__ . '/config.php';
$categories = ['CPU', 'Monitor', 'Impresora', 'Impresora Térmica', 'Impresora Punto de Venta', 'Regulador de Voltaje', 'UPS', 'Laptop', 'Teclado', 'Mouse', 'Punto de Venta', 'Router / Switch'];
try {
    $config = require __DIR__ . '/config.php';
    $pdo = new PDO("mysql:host={$config['db_host']};dbname={$config['db_name']};charset=utf8mb4", $config['db_user'], $config['db_pass']);
    $dbCats = $pdo->query("SELECT DISTINCT name FROM categories ORDER BY name ASC")->fetchAll(PDO::FETCH_COLUMN);
    if (!empty($dbCats)) $categories = $dbCats;
} catch (Exception $e) {}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>A-AM // Ingreso de Equipos Nuevos - Grupo Andino</title>
    <style>
        body { margin: 0; padding: 20px; background-color: #050505; color: #00ffff; font-family: monospace; display: flex; justify-content: center; align-items: center; min-height: 100vh; }
        .forja-panel {
            background: rgba(0, 20, 30, 0.88);
            border: 2px solid #00ffff;
            box-shadow: 0 0 20px rgba(0, 255, 255, 0.5), inset 0 0 20px rgba(0, 255, 255, 0.2);
            padding: 30px;
            width: 100%;
            max-width: 520px;
            backdrop-filter: blur(10px);
            border-radius: 8px;
        }
        h2 { text-align: center; letter-spacing: 2px; text-shadow: 0 0 10px #00ffff; margin-top: 0; }
        .form-group { margin-bottom: 16px; }
        label { display: block; margin-bottom: 5px; color: #aaa; font-size: 12px; letter-spacing: 1px; text-transform: uppercase; }
        input, select {
            width: 100%; padding: 10px; background: rgba(0,0,0,0.6); border: 1px solid #00ffff;
            color: #fff; font-family: monospace; font-size: 14px; box-sizing: border-box; border-radius: 4px;
        }
        input:focus, select:focus { outline: none; box-shadow: 0 0 10px #00ffff; }
        .btn-glow {
            width: 100%; background: rgba(0,255,255,0.1); border: 1px solid #00ffff; color: #00ffff;
            cursor: pointer; padding: 15px; font-family: monospace; font-size: 15px; font-weight: bold;
            transition: 0.2s; box-shadow: 0 0 5px rgba(0,255,255,0.5); margin-top: 10px; border-radius: 4px;
        }
        .btn-glow:hover { background: #00ffff; color: #000; box-shadow: 0 0 20px #00ffff; }
        .back-link { display: block; text-align: center; margin-top: 20px; color: #00ffaa; text-decoration: none; }
        .back-link:hover { text-shadow: 0 0 10px #00ffaa; }
        #result-msg { text-align: center; margin-top: 15px; font-weight: bold; }
    </style>
</head>
<body>
    <div class="forja-panel">
        <h2>INGRESO DE EQUIPOS<br><span style="font-size: 13px; color:#aaa;">INVENTARIO GRUPO ANDINO (DEPÓSITO)</span></h2>
        
        <form id="forja-form">
            <div class="form-group">
                <label>Tipo de Equipo (Categoría)</label>
                <input type="text" id="category" list="cat-list" placeholder="Ej: CPU, Monitor, Impresora Térmica..." required>
                <datalist id="cat-list">
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= htmlspecialchars($cat) ?>">
                    <?php endforeach; ?>
                </datalist>
            </div>

            <div class="form-group">
                <label>Marca del Equipo</label>
                <input type="text" id="brand" placeholder="Ej: HP, Dell, Lenovo, Epson, 3nStar..." required>
            </div>

            <div class="form-group">
                <label>Modelo</label>
                <input type="text" id="model" placeholder="Ej: Optiplex 3020, ProDesk 600, RPT006..." required>
            </div>

            <div class="form-group">
                <label>Serial del Equipo</label>
                <input type="text" id="serial" placeholder="Escanee o escriba el serial único..." required>
            </div>

            <button type="submit" class="btn-glow" id="btn-submit">[ REGISTRAR EN DEPÓSITO ]</button>
        </form>

        <div id="result-msg"></div>

        <a href="index.php" class="back-link">← Volver al Nexo 3D</a>
    </div>

    <script>
        document.getElementById('forja-form').addEventListener('submit', async (e) => {
            e.preventDefault();
            const btn = document.getElementById('btn-submit');
            const msg = document.getElementById('result-msg');
            
            btn.innerText = 'REGISTRANDO...';
            btn.disabled = true;
            msg.innerHTML = '';

            const data = {
                action: 'forjar_equipos',
                category: document.getElementById('category').value.trim(),
                brand: document.getElementById('brand').value.trim(),
                model: document.getElementById('model').value.trim(),
                serial: document.getElementById('serial').value.trim()
            };

            try {
                const response = await fetch('api.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });
                
                const result = await response.json();
                
                if(result.success) {
                    msg.style.color = '#00ffaa';
                    msg.innerHTML = `¡ÉXITO! El equipo con serial <b>${result.serial}</b> fue ingresado en DEPÓSITO y sincronizado con el Nexo 3D.`;
                    document.getElementById('serial').value = '';
                } else {
                    msg.style.color = '#ff0044';
                    msg.innerHTML = `Error: ${result.error}`;
                }
            } catch(error) {
                msg.style.color = '#ff0044';
                msg.innerHTML = `Error de conexión.`;
            }

            btn.innerText = '[ REGISTRAR EN DEPÓSITO ]';
            btn.disabled = false;
        });
    </script>
</body>
</html>
