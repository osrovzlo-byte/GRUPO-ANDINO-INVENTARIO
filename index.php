<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>A-AM // VFX Core Nexus - Grupo Andino</title>
    <style>
        body { margin: 0; overflow: hidden; background-color: #050505; color: #00ffff; font-family: monospace; }
        #canvas-container { width: 100vw; height: 100vh; display: block; }
        #ui-layer { position: absolute; top: 16px; left: 16px; z-index: 10; pointer-events: none; }
        
        #global-actions { position: absolute; top: 16px; right: 16px; z-index: 20; display: flex; flex-wrap: wrap; gap: 8px; }
        .btn-glow { background: rgba(0,255,255,0.1); border: 1px solid #00ffff; color: #00ffff; cursor: pointer; padding: 9px 14px; font-family: monospace; font-size: 12px; font-weight: bold; transition: 0.2s; box-shadow: 0 0 5px rgba(0,255,255,0.4); pointer-events: auto; }
        .btn-glow:hover { background: #00ffff; color: #000; box-shadow: 0 0 18px #00ffff; }

        h1 { margin: 0; font-size: 20px; letter-spacing: 2px; text-shadow: 0 0 8px rgba(0,255,255,0.6); }
        p { margin: 3px 0; color: #aaa; font-size: 12px; }
        
        .hud-panel {
            border: 1px solid rgba(0, 255, 255, 0.45);
            background: rgba(0, 14, 24, 0.88);
            padding: 12px;
            width: 410px;
            margin-top: 10px;
            pointer-events: auto;
            max-height: 83vh;
            display: flex;
            flex-direction: column;
            box-shadow: 0 0 25px rgba(0, 255, 255, 0.15);
            backdrop-filter: blur(6px);
        }

        .hud-tabs {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 6px;
            margin-bottom: 10px;
        }
        .hud-tab {
            padding: 8px;
            font-size: 12px;
            font-weight: bold;
            text-align: center;
            border: 1px solid rgba(0, 255, 255, 0.4);
            background: rgba(0, 255, 255, 0.05);
            color: #00ffff;
            cursor: pointer;
            transition: 0.2s;
        }
        .hud-tab.active {
            background: #00ffff;
            color: #000;
            box-shadow: 0 0 12px rgba(0, 255, 255, 0.6);
        }
        
        .search-box {
            display: flex;
            flex-direction: column;
            gap: 7px;
            padding-bottom: 10px;
            border-bottom: 1px solid rgba(0, 255, 255, 0.3);
            margin-bottom: 8px;
        }
        .search-input-wrap {
            position: relative;
            display: flex;
            align-items: center;
        }
        .search-input {
            width: 100%;
            background: rgba(0, 0, 0, 0.75);
            border: 1px solid #00ffff;
            color: #fff;
            padding: 8px 28px 8px 10px;
            font-family: monospace;
            font-size: 12px;
            box-sizing: border-box;
            outline: none;
        }
        .search-input:focus {
            box-shadow: 0 0 10px rgba(0, 255, 255, 0.6);
        }
        .search-clear {
            position: absolute;
            right: 5px;
            background: transparent;
            border: none;
            color: #aaa;
            cursor: pointer;
            font-size: 13px;
            padding: 2px 6px;
        }
        .search-clear:hover { color: #ff0044; background: transparent; box-shadow: none; }

        .filter-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 6px;
        }
        .filter-select {
            background: rgba(0, 0, 0, 0.75);
            border: 1px solid rgba(0, 255, 255, 0.5);
            color: #00ffff;
            padding: 6px;
            font-family: monospace;
            font-size: 11px;
            outline: none;
            width: 100%;
            box-sizing: border-box;
        }
        .status-pills {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 4px;
        }
        .status-pill {
            padding: 6px 3px;
            font-size: 10px;
            text-align: center;
            border: 1px solid rgba(0, 255, 255, 0.3);
            background: rgba(0, 255, 255, 0.05);
            color: #ccc;
            cursor: pointer;
            transition: 0.2s;
        }
        .status-pill.active {
            background: #00ffff;
            color: #000;
            font-weight: bold;
            box-shadow: 0 0 10px rgba(0, 255, 255, 0.6);
        }
        .status-pill[data-status="DEPÓSITO"].active {
            background: #ffaa00;
            border-color: #ffaa00;
            color: #000;
            box-shadow: 0 0 10px rgba(255, 170, 0, 0.6);
        }
        .status-pill[data-status="ASIGNADO"].active {
            background: #00ffaa;
            border-color: #00ffaa;
            color: #000;
            box-shadow: 0 0 10px rgba(0, 255, 170, 0.6);
        }
        .status-pill[data-status="MANTENIMIENTO"].active {
            background: #ff0044;
            border-color: #ff0044;
            color: #fff;
            box-shadow: 0 0 10px rgba(255, 0, 68, 0.6);
        }

        .results-counter {
            font-size: 11px;
            color: #00ffff;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 6px;
            gap: 5px;
        }

        .asset-list-container {
            overflow-y: auto;
            flex: 1;
            max-height: 46vh;
            padding-right: 4px;
        }
        .asset-list-container::-webkit-scrollbar { width: 5px; }
        .asset-list-container::-webkit-scrollbar-thumb { background: rgba(0, 255, 255, 0.5); }
        
        .asset-list { list-style: none; padding: 0; margin: 0; }
        .asset-list li {
            padding: 8px;
            border-bottom: 1px solid rgba(0,255,255,0.12);
            display: flex;
            justify-content: space-between;
            align-items: center;
            cursor: pointer;
            transition: background 0.15s;
            gap: 8px;
        }
        .asset-list li:hover {
            background: rgba(0, 255, 255, 0.12);
        }
        .asset-list li.selected-item {
            background: rgba(0, 255, 255, 0.24);
            border-left: 3px solid #00ffff;
        }
        .asset-meta {
            font-size: 11px;
            color: #bbb;
            margin-top: 2px;
        }
        .agency-badge {
            display: inline-block;
            font-size: 10px;
            color: #00ffaa;
            background: rgba(0, 255, 170, 0.1);
            border: 1px solid rgba(0, 255, 170, 0.35);
            padding: 1px 5px;
            margin-top: 3px;
        }
        
        button { background: rgba(0,255,255,0.1); border: 1px solid #00ffff; color: #00ffff; cursor: pointer; padding: 5px 10px; font-family: monospace; transition: 0.2s; }
        button:hover { background: #00ffff; color: #000; box-shadow: 0 0 10px #00ffff; }
        
        .btn-warning { border-color: #ffaa00; color: #ffaa00; background: rgba(255, 170, 0, 0.1); }
        .btn-warning:hover { background: #ffaa00; color: #000; box-shadow: 0 0 10px #ffaa00; }

        .btn-danger { border-color: #ff0044; color: #ff0044; background: rgba(255, 0, 68, 0.1); }
        .btn-danger:hover { background: #ff0044; color: #fff; box-shadow: 0 0 10px #ff0044; }

        .btn-success { border-color: #00ffaa; color: #00ffaa; background: rgba(0, 255, 170, 0.12); }
        .btn-success:hover { background: #00ffaa; color: #000; box-shadow: 0 0 10px #00ffaa; }

        #scanner-modal, #agency-modal, #logs-modal {
            position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%);
            width: 500px; background: rgba(0, 12, 22, 0.95); border: 2px solid #00ffff;
            box-shadow: 0 0 30px rgba(0, 255, 255, 0.5), inset 0 0 20px rgba(0, 255, 255, 0.15);
            color: #fff; padding: 20px; display: none; z-index: 100; pointer-events: auto; backdrop-filter: blur(8px);
            max-height: 86vh; overflow-y: auto;
        }
        #agency-modal { width: 620px; }
        #logs-modal { width: 850px; }
        
        .modal-header { font-size: 18px; border-bottom: 1px solid #00ffff; padding-bottom: 10px; margin-bottom: 12px; text-shadow: 0 0 5px #00ffff; padding-right: 35px; }
        .data-label { color: #00ffff; font-weight: bold; }
        .specs-box { margin-top: 8px; margin-bottom: 12px; padding: 10px; background: rgba(0, 255, 255, 0.07); border: 1px solid rgba(0,255,255,0.3); line-height: 1.5; font-size: 13px; }
        .qr-box { margin-top: 10px; padding: 8px; border: 1px dashed #00ffff; text-align: center; font-size: 11px; line-height: 1.4; }
        .close-btn { position: absolute; top: 14px; right: 14px; background: #ff0044; color: white; border: none; cursor: pointer; font-weight: bold; padding: 4px 9px; }
        
        .assign-box {
            margin-top: 12px;
            padding: 12px;
            background: rgba(0, 255, 170, 0.06);
            border: 1px solid rgba(0, 255, 170, 0.4);
        }
        .assign-box h4 {
            margin: 0 0 8px 0;
            color: #00ffaa;
            font-size: 13px;
        }

        .logs-table { width: 100%; border-collapse: collapse; margin-top: 12px; font-size: 12px; }
        .logs-table th, .logs-table td { border: 1px solid rgba(0,255,255,0.3); padding: 7px; text-align: left; }
        .logs-table th { color: #00ffff; background: rgba(0, 255, 255, 0.1); }
        
        #error-overlay {
            position: absolute; top: 0; left: 0; width: 100vw; height: 100vh;
            background: rgba(20, 0, 0, 0.85); z-index: 1000; display: none;
            flex-direction: column; justify-content: center; align-items: center;
        }
        #error-overlay h2 { color: #ff0044; font-size: 32px; text-shadow: 0 0 10px #ff0044; }
        #error-overlay p { color: #fff; font-size: 18px; }
        
        .action-bar { display:flex; gap:8px; margin-top: 12px; justify-content:center; flex-wrap: wrap; }
        .sub-list { list-style: none; padding: 0; margin: 6px 0 0 0; max-height: 190px; overflow-y: auto; border: 1px solid rgba(0,255,255,0.25); background: rgba(0,0,0,0.5); }
        .sub-list li { padding: 7px 10px; border-bottom: 1px solid rgba(0,255,255,0.12); display: flex; justify-content: space-between; align-items: center; font-size: 12px; }
    </style>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/three@0.128.0/examples/js/controls/OrbitControls.js"></script>
</head>
<body>
    <div id="error-overlay">
        <h2>⚠️ FALLO CRÍTICO DEL SISTEMA</h2>
        <p id="error-message">Pérdida de conexión con el núcleo de datos.</p>
        <button class="btn-glow" style="margin-top:20px; border-color:#ff0044; color:#ff0044;" onclick="location.reload()">REINTENTAR CONEXIÓN</button>
    </div>

    <div id="ui-layer">
        <h1>A-AM // GRAVITATIONAL NEXUS</h1>
        <p id="system-status">ESTADO DEL SISTEMA: INICIALIZANDO...</p>
        
        <div class="hud-panel">
            <!-- Selector de Modo: Equipos o Agencias -->
            <div class="hud-tabs">
                <button type="button" id="tab-btn-equipos" class="hud-tab active" onclick="switchHudTab('EQUIPOS')">🖥️ EQUIPOS <span id="tab-cnt-eq"></span></button>
                <button type="button" id="tab-btn-agencias" class="hud-tab" onclick="switchHudTab('AGENCIAS')">🏢 AGENCIAS <span id="tab-cnt-ag"></span></button>
            </div>

            <!-- SECCIÓN 1: BUSCADOR DE EQUIPOS -->
            <div id="panel-equipos">
                <div class="search-box">
                    <div class="search-input-wrap">
                        <input type="text" id="search-input" class="search-input" placeholder="Buscar por Serial, Marca, Modelo o Agencia..." oninput="applyNexusFilters()">
                        <button class="search-clear" title="Limpiar filtros" onclick="resetNexusFilters()">✕</button>
                    </div>

                    <div class="filter-row">
                        <select id="filter-brand" class="filter-select" onchange="applyNexusFilters()">
                            <option value="">Todas las Marcas</option>
                        </select>
                        <select id="filter-model" class="filter-select" onchange="applyNexusFilters()">
                            <option value="">Todos los Modelos</option>
                        </select>
                    </div>

                    <!-- Filtro directo por Agencia dentro del buscador de equipos -->
                    <div class="search-input-wrap">
                        <input type="text" id="filter-agency-input" list="agencies-datalist" class="search-input" style="border-color: rgba(0,255,170,0.6); color:#00ffaa;" placeholder="🏢 Filtrar por Agencia (escribe nombre o código)..." oninput="applyNexusFilters()">
                        <button class="search-clear" title="Quitar filtro de agencia" onclick="document.getElementById('filter-agency-input').value=''; applyNexusFilters();">✕</button>
                    </div>

                    <div class="status-pills">
                        <button type="button" class="status-pill active" data-status="TODOS" onclick="setStatusFilter('TODOS')">TODOS <span id="cnt-all"></span></button>
                        <button type="button" class="status-pill" data-status="DEPÓSITO" onclick="setStatusFilter('DEPÓSITO')">DEPÓSITO <span id="cnt-dep"></span></button>
                        <button type="button" class="status-pill" data-status="ASIGNADO" onclick="setStatusFilter('ASIGNADO')">ASIGNADO <span id="cnt-asig"></span></button>
                        <button type="button" class="status-pill" data-status="MANTENIMIENTO" onclick="setStatusFilter('MANTENIMIENTO')">MANTEN. <span id="cnt-mant"></span></button>
                    </div>
                </div>

                <div class="results-counter">
                    <span id="results-count-text">Cargando equipos...</span>
                    <button type="button" style="font-size:10px; padding:2px 6px;" onclick="resetCameraView()">[ VISTA GENERAL ]</button>
                </div>

                <div class="asset-list-container">
                    <ul class="asset-list" id="asset-list-ui"></ul>
                </div>
            </div>

            <!-- SECCIÓN 2: BUSCADOR DE AGENCIAS -->
            <div id="panel-agencias" style="display:none; flex-direction:column; flex:1;">
                <div class="search-box">
                    <div class="search-input-wrap">
                        <input type="text" id="agency-search-input" class="search-input" placeholder="Buscar Agencia por nombre, código o responsable..." oninput="renderAgenciesPanel()">
                        <button class="search-clear" title="Limpiar" onclick="document.getElementById('agency-search-input').value=''; renderAgenciesPanel();">✕</button>
                    </div>
                    <div style="display:flex; gap:5px;">
                        <button type="button" id="ag-filter-all" class="status-pill active" style="flex:1;" onclick="setAgencyFilterMode('ALL')">TODAS LAS AGENCIAS</button>
                        <button type="button" id="ag-filter-with-eq" class="status-pill" style="flex:1;" onclick="setAgencyFilterMode('WITH_EQ')">CON EQUIPOS ASIGNADOS</button>
                    </div>
                </div>

                <div class="results-counter">
                    <span id="agencies-count-text">Cargando agencias...</span>
                    <button type="button" style="font-size:10px; padding:2px 6px;" onclick="clearAgencySelection()">[ VER TODA LA ÓRBITA ]</button>
                </div>

                <div class="asset-list-container">
                    <ul class="asset-list" id="agency-list-ui"></ul>
                </div>
            </div>
        </div>
    </div>

    <!-- Datalist global con todas las agencias para autocompletar rápido -->
    <datalist id="agencies-datalist"></datalist>

    <div id="global-actions">
        <a href="index.html" class="btn-glow" style="text-decoration:none; border-color:#00ffaa; color:#00ffaa;">[ SISTEMA INVENTARIO ]</a>
        <a href="scanner.php" class="btn-glow" style="text-decoration:none;">[ ESCÁNER MÓVIL ]</a>
        <a href="forja.php" class="btn-glow" style="text-decoration:none;">[ FORJAR EQUIPOS ]</a>
        <button class="btn-glow" onclick="openLogs()">[ VER GRAV-LOGS (ACTAS) ]</button>
    </div>

    <!-- Modal 1: Escáner / Ficha de Equipo Individual -->
    <div id="scanner-modal">
        <button class="close-btn" onclick="closeScanner()">X</button>
        <div class="modal-header" id="modal-title">EQUIPO</div>
        <div style="line-height: 1.55; font-size: 13px;">
            <span class="data-label">Serial / Código:</span> <strong id="modal-serial" style="color:#fff; font-size:14px;"></strong><br/>
            <span class="data-label">Marca:</span> <span id="modal-brand"></span> | <span class="data-label">Modelo:</span> <span id="modal-model"></span><br/>
            <span class="data-label">Categoría:</span> <span id="modal-cat"></span> | <span class="data-label">Estatus:</span> <strong id="modal-status"></strong><br/>
            <span class="data-label">Agencia Asignada:</span> <strong id="modal-agency" style="color:#00ffaa;"></strong>
            
            <div class="specs-box">
                <span class="data-label">RAM:</span> <span id="modal-ram"></span> | 
                <span class="data-label">DISCO:</span> <span id="modal-storage"></span><br/>
                <span class="data-label">PROCESADOR:</span> <span id="modal-cpu"></span>
            </div>
            
            <div class="qr-box">
                <span class="data-label">CÓDIGO QR:</span> <strong id="modal-qr"></strong> &nbsp;|&nbsp;
                <span class="data-label">BARCODE:</span> <strong id="modal-bc"></strong>
            </div>

            <!-- Bloque para Asignar o Cambiar de Agencia directamente desde el Equipo -->
            <div class="assign-box" id="modal-assign-agency-box">
                <h4>🏢 ASIGNAR O TRASLADAR A UNA AGENCIA</h4>
                <input type="text" id="modal-agency-select" list="agencies-datalist" class="search-input" style="margin-bottom:8px;" placeholder="Escribe o selecciona la Agencia destino...">
                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:6px; margin-bottom:8px;">
                    <select id="modal-motive-type" class="filter-select">
                        <option value="Venta">Tipo: Venta</option>
                        <option value="Cambio">Tipo: Cambio de Equipo</option>
                        <option value="Asignación">Tipo: Asignación</option>
                    </select>
                    <input type="text" id="modal-receptor-input" class="search-input" style="padding:5px 8px;" placeholder="Receptor / Nota (opcional)">
                </div>
                <button type="button" class="btn-success" style="width:100%; padding:8px; font-weight:bold;" onclick="assignEquipmentFromModal()">[ CONFIRMAR ASIGNACIÓN A AGENCIA ]</button>
            </div>
            
            <div id="modal-actions" class="action-bar">
                <!-- Botones de Reintegrar a Depósito / Mantenimiento -->
            </div>
        </div>
    </div>

    <!-- Modal 2: Panel de Gestión de Agencia (Ver equipos asignados, Reintegrar a Depósito o Asignarle desde Depósito) -->
    <div id="agency-modal">
        <button class="close-btn" onclick="closeAgencyModal()">X</button>
        <div class="modal-header" id="ag-modal-title">🏢 AGENCIA</div>
        <div style="font-size:13px; line-height:1.5;">
            <div style="display:flex; justify-content:space-between; flex-wrap:wrap; gap:8px; background:rgba(0,255,255,0.06); padding:8px 10px; border:1px solid rgba(0,255,255,0.25); margin-bottom:12px;">
                <div><span class="data-label">Código:</span> <span id="ag-modal-code"></span></div>
                <div><span class="data-label">Responsable:</span> <span id="ag-modal-owner"></span></div>
                <div><span class="data-label">Zona:</span> <span id="ag-modal-zone"></span></div>
            </div>

            <!-- 1. Equipos actualmente asignados a esta agencia -->
            <div style="margin-bottom: 14px;">
                <div style="display:flex; justify-content:space-between; align-items:center;">
                    <span class="data-label" style="color:#00ffaa;">EQUIPOS ASIGNADOS A ESTA AGENCIA (<span id="ag-modal-eq-count">0</span>):</span>
                </div>
                <ul class="sub-list" id="ag-modal-assigned-list"></ul>
            </div>

            <!-- 2. Asignar un equipo del Depósito a esta agencia -->
            <div class="assign-box">
                <h4>➕ ASIGNAR EQUIPO DEL DEPÓSITO A ESTA AGENCIA</h4>
                <div style="display:grid; grid-template-columns: 1fr 140px; gap:6px; margin-bottom:6px;">
                    <input type="text" id="ag-modal-search-dep" class="search-input" placeholder="Buscar equipo en Depósito por Serial, Marca o Modelo..." oninput="renderAvailableForAgency()">
                    <select id="ag-modal-motive-type" class="filter-select">
                        <option value="Venta">Acta: Venta</option>
                        <option value="Cambio">Acta: Cambio</option>
                        <option value="Asignación">Acta: Asignación</option>
                    </select>
                </div>
                <ul class="sub-list" id="ag-modal-available-list"></ul>
            </div>
        </div>
    </div>

    <!-- Modal 3: Bitácora Grav-Logs -->
    <div id="logs-modal">
        <button class="close-btn" onclick="closeLogs()">X</button>
        <div class="modal-header">BITÁCORA CUÁNTICA (ACTAS Y OFICIOS)</div>
        <div>
            <table class="logs-table">
                <thead>
                    <tr>
                        <th>FECHA/HORA</th>
                        <th>EQUIPO (SERIAL)</th>
                        <th>ESTADO</th>
                        <th>AGENCIA</th>
                        <th>MOTIVO (ACTA)</th>
                    </tr>
                </thead>
                <tbody id="logs-table-body"></tbody>
            </table>
        </div>
    </div>

    <div id="canvas-container"></div>

    <script>
        const scene = new THREE.Scene();
        scene.fog = new THREE.FogExp2(0x050505, 0.03);

        const camera = new THREE.PerspectiveCamera(60, window.innerWidth / window.innerHeight, 0.1, 1000);
        camera.position.set(0, 6, 14);

        const renderer = new THREE.WebGLRenderer({ antialias: true });
        renderer.setSize(window.innerWidth, window.innerHeight);
        document.getElementById('canvas-container').appendChild(renderer.domElement);

        const controls = new THREE.OrbitControls(camera, renderer.domElement);
        controls.enableDamping = true;
        controls.dampingFactor = 0.05;

        const ambientLight = new THREE.AmbientLight(0x333333);
        scene.add(ambientLight);
        
        const pointLight = new THREE.PointLight(0x00ffff, 2, 65);
        scene.add(pointLight);

        const starsGeometry = new THREE.BufferGeometry();
        const starsMaterial = new THREE.PointsMaterial({color: 0xffffff, size: 0.05, transparent: true, opacity: 0.8});
        const starsVertices = [];
        for(let i = 0; i < 1800; i++) {
            starsVertices.push((Math.random() - 0.5) * 110, (Math.random() - 0.5) * 110, (Math.random() - 0.5) * 110);
        }
        starsGeometry.setAttribute('position', new THREE.Float32BufferAttribute(starsVertices, 3));
        scene.add(new THREE.Points(starsGeometry, starsMaterial));

        const nexusGeometry = new THREE.OctahedronGeometry(1.4, 0);
        const nexusMaterial = new THREE.MeshStandardMaterial({ color: 0x00ffff, wireframe: true, emissive: 0x0088ff, emissiveIntensity: 1 });
        const nexusCore = new THREE.Mesh(nexusGeometry, nexusMaterial);
        scene.add(nexusCore);

        // Geometrías compartidas para máximo rendimiento 3D con +1,100 equipos
        const sharedSphereGeoSmall = new THREE.SphereGeometry(0.18, 14, 14);
        const sharedSphereGeoLarge = new THREE.SphereGeometry(0.32, 16, 16);

        // Anillo localizador 3D
        const targetRingGeo = new THREE.RingGeometry(0.5, 0.68, 32);
        const targetRingMat = new THREE.MeshBasicMaterial({ color: 0x00ffaa, side: THREE.DoubleSide, transparent: true, opacity: 0.95 });
        const targetRing = new THREE.Mesh(targetRingGeo, targetRingMat);
        targetRing.visible = false;
        scene.add(targetRing);

        let orbitingAssets = [];
        let allAgencies = [];
        let selectedAssetId = null;
        let currentOpenedAsset = null;
        let currentOpenedAgency = null;
        let currentStatusFilter = 'TODOS';
        let agencyFilterMode = 'ALL';
        let lastAssetsSignature = '';

        function clearAssets() {
            orbitingAssets.forEach(a => scene.remove(a.mesh));
            orbitingAssets = [];
        }

        const raycaster = new THREE.Raycaster();
        const mouse = new THREE.Vector2();

        window.addEventListener('click', onMouseClick, false);

        function onMouseClick(event) {
            if(event.target.tagName === 'BUTTON' || event.target.tagName === 'A' || event.target.tagName === 'INPUT' || event.target.tagName === 'SELECT' || event.target.closest('.hud-panel') || event.target.closest('#scanner-modal') || event.target.closest('#agency-modal') || event.target.closest('#logs-modal') || event.target.closest('#error-overlay')) return;

            mouse.x = (event.clientX / window.innerWidth) * 2 - 1;
            mouse.y = -(event.clientY / window.innerHeight) * 2 + 1;

            raycaster.setFromCamera(mouse, camera);

            const visibleMeshes = orbitingAssets.filter(a => a.mesh.visible && a.matched !== false).map(a => a.mesh);
            const intersects = raycaster.intersectObjects(visibleMeshes);

            if (intersects.length > 0) {
                const clickedMesh = intersects[0].object;
                const node = orbitingAssets.find(a => a.mesh === clickedMesh);
                if (node) {
                    focusAndOpenAsset(node.data.id);
                }
            }
        }

        function switchHudTab(tab) {
            const isEq = tab === 'EQUIPOS';
            document.getElementById('tab-btn-equipos').classList.toggle('active', isEq);
            document.getElementById('tab-btn-agencias').classList.toggle('active', !isEq);
            document.getElementById('panel-equipos').style.display = isEq ? 'block' : 'none';
            document.getElementById('panel-agencias').style.display = isEq ? 'none' : 'flex';
            if (!isEq) renderAgenciesPanel();
        }

        function normalizeStatus(rawStatus) {
            const s = String(rawStatus || '').toUpperCase().trim();
            if (s === 'BODEGA' || s === 'DEPOSITO' || s === 'DEPÓSITO' || s === 'EN DEPÓSITO') return 'DEPÓSITO';
            if (s === 'MANTENIMIENTO' || s === 'EN MANTENIMIENTO') return 'MANTENIMIENTO';
            return 'ASIGNADO';
        }

        function focusAndOpenAsset(assetId) {
            const node = orbitingAssets.find(a => String(a.data.id) === String(assetId));
            if (!node) return;

            selectedAssetId = node.data.id;
            targetRing.visible = true;
            openScanner(node.data);
            applyNexusFilters();
        }

        function resetCameraView() {
            selectedAssetId = null;
            targetRing.visible = false;
            controls.target.set(0, 0, 0);
            camera.position.set(0, 6, 14);
            closeScanner();
            applyNexusFilters();
        }

        function openScanner(data) {
            currentOpenedAsset = data;
            const normStatus = normalizeStatus(data.status);
            document.getElementById('scanner-modal').style.display = 'block';
            document.getElementById('modal-title').innerText = `${data.tag}`;
            document.getElementById('modal-serial').innerText = data.serial || data.tag;
            document.getElementById('modal-brand').innerText = data.brand || 'Genérico';
            document.getElementById('modal-cat').innerText = data.category;
            document.getElementById('modal-model').innerText = data.model;
            
            const statusEl = document.getElementById('modal-status');
            statusEl.innerText = normStatus;
            statusEl.style.color = normStatus === 'ASIGNADO' ? '#00ffaa' : (normStatus === 'MANTENIMIENTO' ? '#ff0044' : '#ffaa00');

            const agEl = document.getElementById('modal-agency');
            if (normStatus === 'ASIGNADO' && data.agency) {
                agEl.innerHTML = `${data.agency} <button type="button" style="font-size:10px; padding:2px 6px; margin-left:6px;" onclick="openAgencyModalByName('${data.agency.replace(/'/g, "\\'")}')">VER AGENCIA</button>`;
            } else {
                agEl.innerText = 'Sin asignar (En Depósito / Taller)';
            }

            document.getElementById('modal-ram').innerText = data.ram;
            document.getElementById('modal-storage').innerText = data.storage;
            document.getElementById('modal-cpu').innerText = data.processor;
            
            document.getElementById('modal-qr').innerText = data.qr_code;
            document.getElementById('modal-bc').innerText = data.barcode;

            document.getElementById('modal-agency-select').value = data.agency || '';
            document.getElementById('modal-receptor-input').value = '';
            
            const actionsDiv = document.getElementById('modal-actions');
            actionsDiv.innerHTML = '';

            if(normStatus === 'DEPÓSITO') {
                actionsDiv.innerHTML = `
                    <button class="btn-danger" onclick="promptAction(${data.id}, 'MANTENIMIENTO', 'Envío a Mantenimiento')">🔧 ENVIAR A MANTENIMIENTO</button>
                `;
            } else if (normStatus === 'ASIGNADO') {
                actionsDiv.innerHTML = `
                    <button class="btn-warning" onclick="promptAction(${data.id}, 'BODEGA', 'Reingreso desde Agencia a Depósito Central')">🏢 ENVIAR A DEPÓSITO CENTRAL</button>
                    <button class="btn-danger" onclick="promptAction(${data.id}, 'MANTENIMIENTO', 'Envío a Mantenimiento')">🔧 ENVIAR A MANTENIMIENTO</button>
                `;
            } else if (normStatus === 'MANTENIMIENTO') {
                actionsDiv.innerHTML = `
                    <button class="btn-warning" onclick="promptAction(${data.id}, 'BODEGA', 'Reingreso Post-Mantenimiento a Depósito Central')">🏢 ENVIAR A DEPÓSITO CENTRAL</button>
                `;
            }
        }

        async function assignEquipmentFromModal() {
            if (!currentOpenedAsset) return;
            const agencyName = document.getElementById('modal-agency-select').value.trim();
            if (!agencyName) {
                alert('Por favor escribe o selecciona la Agencia a la que deseas asignar el equipo.');
                document.getElementById('modal-agency-select').focus();
                return;
            }
            const motiveType = document.getElementById('modal-motive-type').value;
            const receptorNote = document.getElementById('modal-receptor-input').value.trim();
            const fullMotive = receptorNote ? `${motiveType} - ${receptorNote}` : motiveType;

            await executeStateChange(currentOpenedAsset.id, 'ASIGNADO', fullMotive, agencyName, receptorNote);
            closeScanner();
        }

        async function promptAction(id, newState, defaultMotive) {
            let motive = prompt(`Motivo para Acta u Oficio (Venta, Cambio, Asignación o Reingreso):`, defaultMotive);
            if(motive === null) return;
            await executeStateChange(id, newState, motive, null, null);
            closeScanner();
        }

        async function executeStateChange(id, newState, motive, agencyName, receptorName) {
            try {
                const res = await fetch('api.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'change_state',
                        asset_id: id,
                        new_state: newState,
                        motive: motive,
                        agency: agencyName,
                        receptor: receptorName
                    })
                });
                const data = await res.json();
                if(data.error) throw new Error(data.error);

                lastAssetsSignature = '';
                await fetchGravitationalData();
                if (currentOpenedAgency) {
                    openAgencyModalByName(currentOpenedAgency);
                }
            } catch (e) {
                console.error(e);
                showCriticalError(e.message);
            }
        }

        function closeScanner() {
            document.getElementById('scanner-modal').style.display = 'none';
        }

        // ==================== GESTIÓN DE AGENCIAS ====================
        function setAgencyFilterMode(mode) {
            agencyFilterMode = mode;
            document.getElementById('ag-filter-all').classList.toggle('active', mode === 'ALL');
            document.getElementById('ag-filter-with-eq').classList.toggle('active', mode === 'WITH_EQ');
            renderAgenciesPanel();
        }

        function clearAgencySelection() {
            document.getElementById('filter-agency-input').value = '';
            closeAgencyModal();
            applyNexusFilters();
        }

        function renderAgenciesPanel() {
            const q = (document.getElementById('agency-search-input').value || '').toLowerCase().trim();
            const listUi = document.getElementById('agency-list-ui');
            listUi.innerHTML = '';

            // Contar cuántos equipos tiene cada agencia actualmente
            const countsByAgency = {};
            orbitingAssets.forEach(node => {
                const ag = (node.data.agency || '').trim();
                if (ag && normalizeStatus(node.data.status) === 'ASIGNADO') {
                    countsByAgency[ag.toUpperCase()] = (countsByAgency[ag.toUpperCase()] || 0) + 1;
                }
            });

            const filtered = allAgencies.filter(ag => {
                const eqCount = countsByAgency[(ag.name || '').toUpperCase()] || 0;
                if (agencyFilterMode === 'WITH_EQ' && eqCount === 0) return false;
                if (!q) return true;
                const hay = `${ag.name} ${ag.code} ${ag.owner} ${ag.zone}`.toLowerCase();
                return hay.includes(q);
            });

            document.getElementById('agencies-count-text').innerText = `Mostrando ${filtered.length} de ${allAgencies.length} agencias`;

            if (filtered.length === 0) {
                listUi.innerHTML = `<li style="justify-content:center; color:#ffaa00;">No se encontraron agencias.</li>`;
                return;
            }

            filtered.slice(0, 150).forEach(ag => {
                const eqCount = countsByAgency[(ag.name || '').toUpperCase()] || 0;
                const badgeColor = eqCount > 0 ? '#00ffaa' : '#777';
                const li = document.createElement('li');
                li.onclick = () => openAgencyModalByName(ag.name);
                li.innerHTML = `
                    <div>
                        <strong style="color:#00ffff; font-size:13px;">${ag.name}</strong>
                        <span style="font-size:10px; padding:1px 5px; border:1px solid ${badgeColor}; color:${badgeColor}; margin-left:6px;">${eqCount} EQUIPOS</span>
                        <div class="asset-meta">Cód: ${ag.code || 'N/A'} • Resp: ${ag.owner || '-'}</div>
                    </div>
                    <button type="button" style="font-size:11px; padding:4px 8px;" onclick="event.stopPropagation(); openAgencyModalByName('${ag.name.replace(/'/g, "\\'")}');">GESTIONAR</button>
                `;
                listUi.appendChild(li);
            });
        }

        function openAgencyModalByName(agencyName) {
            currentOpenedAgency = agencyName;
            closeScanner();

            // También filtramos la órbita 3D para resaltar los equipos de esta agencia
            document.getElementById('filter-agency-input').value = agencyName;
            applyNexusFilters();

            const agObj = allAgencies.find(a => (a.name || '').toUpperCase() === agencyName.toUpperCase()) || {
                name: agencyName, code: agencyName, owner: '-', zone: '-'
            };

            document.getElementById('agency-modal').style.display = 'block';
            document.getElementById('ag-modal-title').innerText = `🏢 ${agObj.name}`;
            document.getElementById('ag-modal-code').innerText = agObj.code || 'N/A';
            document.getElementById('ag-modal-owner').innerText = agObj.owner || '-';
            document.getElementById('ag-modal-zone').innerText = agObj.zone || '-';

            // Listar equipos asignados a esta agencia
            const assignedNodes = orbitingAssets.filter(n =>
                normalizeStatus(n.data.status) === 'ASIGNADO' &&
                (n.data.agency || '').toUpperCase() === agencyName.toUpperCase()
            );

            document.getElementById('ag-modal-eq-count').innerText = assignedNodes.length;
            const assignedList = document.getElementById('ag-modal-assigned-list');
            assignedList.innerHTML = '';

            if (assignedNodes.length === 0) {
                assignedList.innerHTML = `<li style="justify-content:center; color:#aaa;">Esta agencia no tiene equipos asignados actualmente.</li>`;
            } else {
                assignedNodes.forEach(node => {
                    const a = node.data;
                    const li = document.createElement('li');
                    li.innerHTML = `
                        <div>
                            <strong style="color:#00ffaa;">${a.serial}</strong> — ${a.brand} ${a.model}
                            <div style="font-size:10px; color:#aaa;">Categoría: ${a.category}</div>
                        </div>
                        <div style="display:flex; gap:5px;">
                            <button type="button" style="font-size:10px; padding:3px 7px;" onclick="closeAgencyModal(); focusAndOpenAsset(${a.id});">UBICAR 3D</button>
                            <button type="button" class="btn-warning" style="font-size:10px; padding:3px 7px;" onclick="reintegrateFromAgency(${a.id}, '${a.serial.replace(/'/g, "\\'")}')">↩ REINTEGRAR A DEPÓSITO</button>
                        </div>
                    `;
                    assignedList.appendChild(li);
                });
            }

            renderAvailableForAgency();
        }

        function renderAvailableForAgency() {
            const q = (document.getElementById('ag-modal-search-dep').value || '').toLowerCase().trim();
            const availList = document.getElementById('ag-modal-available-list');
            availList.innerHTML = '';

            const depNodes = orbitingAssets.filter(n => {
                if (normalizeStatus(n.data.status) !== 'DEPÓSITO') return false;
                if (!q) return true;
                const a = n.data;
                const hay = `${a.serial} ${a.brand} ${a.model} ${a.category}`.toLowerCase();
                return hay.includes(q);
            });

            if (depNodes.length === 0) {
                availList.innerHTML = `<li style="justify-content:center; color:#ffaa00;">No hay equipos en Depósito que coincidan con la búsqueda.</li>`;
                return;
            }

            depNodes.slice(0, 40).forEach(node => {
                const a = node.data;
                const li = document.createElement('li');
                li.innerHTML = `
                    <div>
                        <strong style="color:#00ffff;">${a.serial}</strong> — ${a.brand} ${a.model}
                        <div style="font-size:10px; color:#aaa;">${a.category} [EN DEPÓSITO]</div>
                    </div>
                    <button type="button" class="btn-success" style="font-size:10px; padding:4px 8px; font-weight:bold;" onclick="assignToCurrentAgency(${a.id})">+ ASIGNAR A AGENCIA</button>
                `;
                availList.appendChild(li);
            });
        }

        async function assignToCurrentAgency(assetId) {
            if (!currentOpenedAgency) return;
            const motiveType = document.getElementById('ag-modal-motive-type').value || 'Asignación';
            await executeStateChange(assetId, 'ASIGNADO', `${motiveType} a ${currentOpenedAgency}`, currentOpenedAgency, null);
        }

        async function reintegrateFromAgency(assetId, serial) {
            const motive = prompt(`Motivo del Reingreso del equipo ${serial} hacia el Depósito:`, `Reingreso desde ${currentOpenedAgency} a Depósito`);
            if (motive === null) return;
            await executeStateChange(assetId, 'BODEGA', motive, null, null);
        }

        function closeAgencyModal() {
            currentOpenedAgency = null;
            document.getElementById('agency-modal').style.display = 'none';
        }
        
        async function openLogs() {
            document.getElementById('logs-modal').style.display = 'block';
            const tbody = document.getElementById('logs-table-body');
            tbody.innerHTML = '<tr><td colspan="5" style="text-align:center;">Cargando...</td></tr>';
            
            try {
                const res = await fetch('api.php?action=get_logs');
                const data = await res.json();
                
                if(data.error) throw new Error(data.error);

                if(data.success && data.logs && data.logs.length > 0) {
                    tbody.innerHTML = '';
                    data.logs.forEach(log => {
                        let color = '#fff';
                        let displayAction = log.action_type === 'BODEGA' ? 'DEPÓSITO' : log.action_type;
                        if(displayAction === 'ASIGNADO') color = '#00ffaa';
                        if(displayAction === 'DEPÓSITO') color = '#ffaa00';
                        if(displayAction === 'MANTENIMIENTO') color = '#ff0044';
                        if(displayAction === 'ALTA_INVENTARIO') color = '#00ffff';

                        tbody.innerHTML += `
                            <tr>
                                <td>${log.timestamp}</td>
                                <td><strong>${log.asset_tag}</strong><br/><span style="color:#aaa; font-size:10px;">${log.name || ''}</span></td>
                                <td style="color:${color}; font-weight:bold;">${displayAction}</td>
                                <td style="color:#00ffaa;">${log.agency_name || '-'}</td>
                                <td>${log.motive || 'N/A'}</td>
                            </tr>
                        `;
                    });
                } else {
                    tbody.innerHTML = '<tr><td colspan="5" style="text-align:center;">No hay registros en la bitácora.</td></tr>';
                }
            } catch(e) {
                tbody.innerHTML = `<tr><td colspan="5" style="text-align:center; color:red;">${e.message}</td></tr>`;
            }
        }
        
        function closeLogs() { document.getElementById('logs-modal').style.display = 'none'; }

        function createAssetNode(asset) {
            const normStatus = normalizeStatus(asset.status);
            asset.status = normStatus;

            const isCritical = asset.criticality >= 100; 
            const geometry = isCritical ? sharedSphereGeoLarge : sharedSphereGeoSmall;
            
            const colorObj = new THREE.Color(asset.color);
            const material = new THREE.MeshStandardMaterial({ 
                color: colorObj, 
                emissive: colorObj, 
                emissiveIntensity: isCritical ? 0.9 : 0.45,
                transparent: true,
                opacity: 1.0
            });
            
            const mesh = new THREE.Mesh(geometry, material);
            scene.add(mesh);
            
            orbitingAssets.push({
                mesh: mesh,
                data: asset,
                baseEmissive: isCritical ? 0.9 : 0.45,
                angle: Math.random() * Math.PI * 2,
                speed: isCritical ? 0.8 : 0.35,
                radius: parseFloat(asset.orbitRadius) + 2,
                yOffset: parseFloat(asset.orbitHeight || 0),
                decaying: normStatus === 'DEPÓSITO',
                matched: true
            });
        }

        function populateDropdownsAndDatalist() {
            const brandSelect = document.getElementById('filter-brand');
            const modelSelect = document.getElementById('filter-model');
            const currentBrand = brandSelect.value;
            const currentModel = modelSelect.value;

            const brands = [...new Set(orbitingAssets.map(a => a.data.brand || 'Genérico'))].sort();
            const models = [...new Set(orbitingAssets.map(a => a.data.model || 'Estándar'))].sort();

            brandSelect.innerHTML = '<option value="">Todas las Marcas (' + brands.length + ')</option>' +
                brands.map(b => `<option value="${b}">${b}</option>`).join('');
            modelSelect.innerHTML = '<option value="">Todos los Modelos (' + models.length + ')</option>' +
                models.map(m => `<option value="${m}">${m}</option>`).join('');

            if (brands.includes(currentBrand)) brandSelect.value = currentBrand;
            if (models.includes(currentModel)) modelSelect.value = currentModel;

            const datalist = document.getElementById('agencies-datalist');
            datalist.innerHTML = allAgencies.map(ag => `<option value="${ag.name}">${ag.code ? ag.code + ' - ' : ''}${ag.owner || ''}</option>`).join('');

            document.getElementById('tab-cnt-eq').innerText = `(${orbitingAssets.length})`;
            document.getElementById('tab-cnt-ag').innerText = `(${allAgencies.length})`;
        }

        function setStatusFilter(status) {
            currentStatusFilter = status;
            document.querySelectorAll('#panel-equipos .status-pill').forEach(btn => {
                btn.classList.toggle('active', btn.getAttribute('data-status') === status);
            });
            applyNexusFilters();
        }

        function resetNexusFilters() {
            document.getElementById('search-input').value = '';
            document.getElementById('filter-brand').value = '';
            document.getElementById('filter-model').value = '';
            document.getElementById('filter-agency-input').value = '';
            setStatusFilter('TODOS');
        }

        function applyNexusFilters() {
            const q = (document.getElementById('search-input').value || '').toLowerCase().trim();
            const brandFilter = document.getElementById('filter-brand').value;
            const modelFilter = document.getElementById('filter-model').value;
            const agencyQuery = (document.getElementById('filter-agency-input').value || '').toLowerCase().trim();

            const listUi = document.getElementById('asset-list-ui');
            listUi.innerHTML = '';

            let countAll = 0, countDep = 0, countAsig = 0, countMant = 0;
            let matchedNodes = [];

            orbitingAssets.forEach(node => {
                const a = node.data;
                const normStatus = normalizeStatus(a.status);

                countAll++;
                if (normStatus === 'DEPÓSITO') countDep++;
                else if (normStatus === 'ASIGNADO') countAsig++;
                else if (normStatus === 'MANTENIMIENTO') countMant++;

                const haystack = [
                    a.serial, a.tag, a.brand, a.model, a.category, a.status, a.agency, a.qr_code, a.barcode
                ].join(' ').toLowerCase();

                const matchesQuery = !q || haystack.includes(q);
                const matchesBrand = !brandFilter || a.brand === brandFilter;
                const matchesModel = !modelFilter || a.model === modelFilter;
                const matchesAgency = !agencyQuery || (a.agency || '').toLowerCase().includes(agencyQuery);
                const matchesStatus = currentStatusFilter === 'TODOS' || normStatus === currentStatusFilter;

                const isMatch = matchesQuery && matchesBrand && matchesModel && matchesAgency && matchesStatus;
                node.matched = isMatch;

                const hasActiveFilter = q !== '' || brandFilter !== '' || modelFilter !== '' || agencyQuery !== '' || currentStatusFilter !== 'TODOS';
                if (!hasActiveFilter) {
                    node.mesh.material.opacity = 0.95;
                    node.mesh.material.emissiveIntensity = node.baseEmissive;
                    node.mesh.scale.set(1, 1, 1);
                } else if (isMatch) {
                    node.mesh.material.opacity = 1.0;
                    node.mesh.material.emissiveIntensity = 1.5;
                    node.mesh.scale.set(1.45, 1.45, 1.45);
                } else {
                    node.mesh.material.opacity = 0.05;
                    node.mesh.material.emissiveIntensity = 0.02;
                    node.mesh.scale.set(0.5, 0.5, 0.5);
                }

                if (isMatch) {
                    matchedNodes.push(node);
                }
            });

            document.getElementById('cnt-all').innerText = `(${countAll})`;
            document.getElementById('cnt-dep').innerText = `(${countDep})`;
            document.getElementById('cnt-asig').innerText = `(${countAsig})`;
            document.getElementById('cnt-mant').innerText = `(${countMant})`;

            document.getElementById('results-count-text').innerText = `Mostrando ${matchedNodes.length} de ${countAll} equipos`;

            if (matchedNodes.length === 0) {
                listUi.innerHTML = `<li style="justify-content:center; color:#ffaa00; cursor:default;">Sin coincidencias para este filtro.</li>`;
                return;
            }

            // Renderizar primeros 150 resultados para fluidez instantánea al escribir
            matchedNodes.slice(0, 150).forEach(node => {
                const asset = node.data;
                const normStatus = normalizeStatus(asset.status);
                const li = document.createElement('li');
                if (String(selectedAssetId) === String(asset.id)) {
                    li.className = 'selected-item';
                }

                let color = '#fff';
                if(normStatus === 'ASIGNADO') color = '#00ffaa';
                if(normStatus === 'DEPÓSITO') color = '#ffaa00';
                if(normStatus === 'MANTENIMIENTO') color = '#ff0044';

                const agencyHtml = (normStatus === 'ASIGNADO' && asset.agency)
                    ? `<div class="agency-badge">🏢 ${asset.agency}</div>`
                    : '';

                li.onclick = () => focusAndOpenAsset(asset.id);
                li.innerHTML = `
                    <div>
                        <strong style="color:${color}; font-size:13px;">${asset.serial || asset.tag}</strong>
                        <span style="font-size:10px; padding:1px 5px; border:1px solid ${color}; color:${color}; margin-left:5px;">${normStatus}</span>
                        <div class="asset-meta">${asset.brand || 'Genérico'} • ${asset.model} (${asset.category})</div>
                        ${agencyHtml}
                    </div>
                    <button type="button" style="font-size:11px; padding:4px 8px;" onclick="event.stopPropagation(); focusAndOpenAsset(${asset.id});">UBICAR</button>
                `;
                listUi.appendChild(li);
            });
        }

        function showCriticalError(msg) {
            document.getElementById('error-overlay').style.display = 'flex';
            document.getElementById('error-message').innerText = msg;
        }

        async function fetchGravitationalData() {
            try {
                const response = await fetch('api.php');
                const data = await response.json();
                
                if (data.error) {
                    throw new Error(data.error);
                }
                
                document.getElementById('system-status').innerText = `ESTADO DEL SISTEMA: ${data.nexus_status} | ${data.total_synced} EQUIPOS EN ÓRBITA`;
                
                const assets = data.assets || [];
                allAgencies = data.agencies || [];

                const currentSig = JSON.stringify(assets.map(a => `${a.id}:${a.status}:${a.agency}:${a.hash}`));
                if (currentSig === lastAssetsSignature) {
                    return;
                }
                lastAssetsSignature = currentSig;

                clearAssets();
                assets.forEach(asset => createAssetNode(asset));
                populateDropdownsAndDatalist();
                applyNexusFilters();
                if (document.getElementById('panel-agencias').style.display !== 'none') {
                    renderAgenciesPanel();
                }
            } catch (error) {
                console.error("Fetch Error:", error);
                showCriticalError(error.message);
            }
        }

        const clock = new THREE.Clock();
        
        function animate() {
            requestAnimationFrame(animate);
            const delta = clock.getDelta();
            const time = clock.getElapsedTime();

            nexusCore.rotation.y += 0.5 * delta;
            nexusCore.rotation.x += 0.2 * delta;
            
            let selectedNode = null;

            orbitingAssets.forEach(assetNode => {
                assetNode.angle += assetNode.speed * delta * 0.4;
                
                if (assetNode.decaying) {
                    assetNode.mesh.position.x = Math.cos(assetNode.angle * 0.5) * assetNode.radius;
                    assetNode.mesh.position.z = Math.sin(assetNode.angle * 0.5) * assetNode.radius;
                    assetNode.mesh.position.y = Math.sin(time + assetNode.angle) * 0.4 - 2.6; 
                } else {
                    assetNode.mesh.position.x = Math.cos(assetNode.angle) * assetNode.radius;
                    assetNode.mesh.position.z = Math.sin(assetNode.angle) * assetNode.radius;
                    assetNode.mesh.position.y = Math.cos(time * 1.5 + assetNode.angle) * 0.6 + assetNode.yOffset * 0.5; 
                }

                if (selectedAssetId !== null && String(assetNode.data.id) === String(selectedAssetId)) {
                    selectedNode = assetNode;
                }
            });

            if (selectedNode) {
                targetRing.position.copy(selectedNode.mesh.position);
                targetRing.lookAt(camera.position);
                controls.target.lerp(selectedNode.mesh.position, 0.08);
            }

            controls.update();
            renderer.render(scene, camera);
        }

        window.addEventListener('resize', () => {
            camera.aspect = window.innerWidth / window.innerHeight;
            camera.updateProjectionMatrix();
            renderer.setSize(window.innerWidth, window.innerHeight);
        });

        fetchGravitationalData().then(() => {
            animate();
            setInterval(fetchGravitationalData, 5000);
        });
    </script>
</body>
</html>
