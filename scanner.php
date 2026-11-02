<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>A-AM // Escáner Móvil</title>
    <style>
        body { margin: 0; padding: 0; background-color: #050505; color: #00ffff; font-family: monospace; display: flex; flex-direction: column; align-items: center; min-height: 100vh; }
        .header { background: rgba(0, 20, 30, 0.9); width: 100%; padding: 15px; text-align: center; border-bottom: 2px solid #00ffff; box-sizing: border-box; }
        h2 { margin: 0; font-size: 20px; text-shadow: 0 0 10px #00ffff; }
        
        .scanner-container { width: 100%; max-width: 400px; padding: 20px; box-sizing: border-box; flex-grow: 1; }
        
        #reader { width: 100%; min-height: 250px; background: #111; border: 1px dashed #00ffff; margin-bottom: 20px; position: relative; }
        
        .manual-input { margin-bottom: 20px; width: 100%; display: flex; flex-direction: column; gap: 10px; }
        input[type="text"] { padding: 15px; background: rgba(0,0,0,0.5); border: 1px solid #00ffff; color: #fff; font-family: monospace; font-size: 16px; text-align: center; }
        
        .btn-glow { background: rgba(0,255,255,0.1); border: 1px solid #00ffff; color: #00ffff; cursor: pointer; padding: 15px; font-family: monospace; font-size: 16px; font-weight: bold; transition: 0.2s; box-shadow: 0 0 5px rgba(0,255,255,0.5); width: 100%; }
        .btn-glow:active { background: #00ffff; color: #000; }
        .btn-warning { border-color: #ffaa00; color: #ffaa00; background: rgba(255, 170, 0, 0.1); }
        .btn-danger { border-color: #ff0044; color: #ff0044; background: rgba(255, 0, 68, 0.1); }

        #asset-info { display: none; background: rgba(0, 20, 30, 0.8); border: 1px solid #00ffff; padding: 15px; margin-bottom: 20px; }
        .data-label { color: #aaa; font-size: 12px; }
        .data-value { font-size: 16px; font-weight: bold; margin-bottom: 10px; display: block; }
        
        .action-grid { display: flex; flex-direction: column; gap: 10px; margin-top: 15px; }
    </style>
    <!-- Incluimos html5-qrcode para usar la cámara del celular -->
    <script src="https://unpkg.com/html5-qrcode"></script>
</head>
<body>
    <div class="header">
        <h2>TERM. LÁSER A-AM</h2>
    </div>

    <div class="scanner-container">
        
        <div id="reader"></div>

        <div class="manual-input">
            <span style="text-align:center; color:#aaa; font-size:12px;">O INGRESA CÓDIGO QR / BARCODE MANUALMENTE:</span>
            <input type="text" id="manual-code" placeholder="Ej. QR-6dd6f59b o BC-679523198">
            <button class="btn-glow" onclick="searchAsset()">[ BUSCAR EN NEXO ]</button>
        </div>

        <div id="asset-info">
            <span class="data-label">EQUIPO:</span>
            <span class="data-value" id="info-tag"></span>
            
            <span class="data-label">MODELO:</span>
            <span class="data-value" id="info-model"></span>

            <span class="data-label">ESTADO ACTUAL:</span>
            <span class="data-value" id="info-status" style="color:#00ffaa;"></span>

            <div class="action-grid" id="action-buttons">
                <!-- Dinámico -->
            </div>
            
            <button class="btn-glow" style="margin-top:20px; border-color:#555; color:#aaa;" onclick="resetScanner()">[ NUEVO ESCANEO ]</button>
        </div>
    </div>

    <script>
        let html5QrcodeScanner;
        let currentAssetId = null;

        // Inicializar escáner de cámara
        function initScanner() {
            html5QrcodeScanner = new Html5QrcodeScanner("reader", { fps: 10, qrbox: {width: 250, height: 250} }, /* verbose= */ false);
            html5QrcodeScanner.render(onScanSuccess, onScanFailure);
        }

        function onScanSuccess(decodedText, decodedResult) {
            document.getElementById('manual-code').value = decodedText;
            html5QrcodeScanner.clear();
            searchAsset();
        }

        function onScanFailure(error) {
            // Se ignora para no saturar la consola, está buscando constantemente.
        }

        async function searchAsset() {
            const code = document.getElementById('manual-code').value.trim();
            if(!code) return alert("Ingresa un código");

            try {
                const res = await fetch(`api.php?action=get_asset_by_code&code=${encodeURIComponent(code)}`);
                const data = await res.json();

                if(data.error) {
                    alert(data.error);
                } else {
                    displayAsset(data.asset);
                }
            } catch(e) {
                alert("Error de conexión con el Nexo.");
            }
        }

        function displayAsset(asset) {
            currentAssetId = asset.id;
            document.getElementById('reader').style.display = 'none';
            document.querySelector('.manual-input').style.display = 'none';
            document.getElementById('asset-info').style.display = 'block';

            document.getElementById('info-tag').innerText = asset.asset_tag + (asset.name ? ` (${asset.name})` : '');
            document.getElementById('info-model').innerText = asset.category + ' / ' + asset.model;
            document.getElementById('info-status').innerText = asset.asset_state;

            let color = '#fff';
            if(asset.asset_state === 'ASIGNADO') color = '#00ffaa';
            if(asset.asset_state === 'BODEGA') color = '#ffaa00';
            if(asset.asset_state === 'MANTENIMIENTO') color = '#ff0044';
            document.getElementById('info-status').style.color = color;

            const actionsDiv = document.getElementById('action-buttons');
            actionsDiv.innerHTML = '';

            if(asset.asset_state === 'BODEGA') {
                actionsDiv.innerHTML = `
                    <button class="btn-glow" onclick="executeAction('ASIGNADO', 'Asignación Nueva')">ASIGNAR A USUARIO</button>
                    <button class="btn-glow" style="border-color:#aa00ff; color:#aa00ff;" onclick="executeAction('ASIGNADO', 'Cambio de Equipo')">REEMPLAZO / CAMBIO</button>
                    <button class="btn-glow btn-danger" onclick="executeAction('MANTENIMIENTO', 'Envío a Mantenimiento')">ENVIAR A MANTENIMIENTO</button>
                `;
            } else if (asset.asset_state === 'ASIGNADO') {
                actionsDiv.innerHTML = `
                    <button class="btn-glow btn-warning" onclick="executeAction('BODEGA', 'Reingreso')">REINGRESAR A BODEGA</button>
                    <button class="btn-glow btn-danger" onclick="executeAction('MANTENIMIENTO', 'Envío a Mantenimiento')">ENVIAR A MANTENIMIENTO</button>
                `;
            } else if (asset.asset_state === 'MANTENIMIENTO') {
                actionsDiv.innerHTML = `
                    <button class="btn-glow btn-warning" onclick="executeAction('BODEGA', 'Reingreso Post-Mantenimiento')">REINGRESAR A BODEGA</button>
                `;
            }
        }

        async function executeAction(newState, defaultMotive) {
            let motive = prompt(`Motivo del Movimiento (Ej. ${defaultMotive}):`, defaultMotive);
            if(motive === null) return;

            try {
                const res = await fetch('api.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'change_state', asset_id: currentAssetId, new_state: newState, motive: motive })
                });
                const data = await res.json();
                
                if(data.error) throw new Error(data.error);

                alert(`¡ÉXITO! Equipo actualizado a ${newState}.`);
                resetScanner();
            } catch (e) {
                alert("Error: " + e.message);
            }
        }

        function resetScanner() {
            document.getElementById('manual-code').value = '';
            document.getElementById('asset-info').style.display = 'none';
            document.getElementById('reader').style.display = 'block';
            document.querySelector('.manual-input').style.display = 'flex';
            initScanner();
        }

        // Si estamos en localhost, la cámara web funciona. 
        // Si accedemos por IP local desde el cel sin HTTPS, la cámara se bloqueará, pero el input manual servirá o si usamos un lector bluetooth.
        window.onload = () => {
            initScanner();
        };
    </script>
</body>
</html>
