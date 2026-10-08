const fs = require('node:fs');
const vm = require('node:vm');
const assert = require('node:assert/strict');
const source = fs.readFileSync(__dirname + '/index.html', 'utf8');
function fn(name) {
    const match = source.match(new RegExp('function ' + name + '\\([^]*?\\n        }'));
    assert.ok(match, name);
    return match[0];
}
const functions = ['normalizeTransactionType', 'transactionTypeLabel', 'processDispatch',
    'processBatchDispatch', 'processReturnToWarehouse', 'exportSignature', 'viewReceipt', 'escapeWordXml', 'renderInventoryTable', 'resetFilters'];
function setup() {
    const fields = new Map();
    const noop = () => {};
    function element() {
        return {value: '', innerText: '', innerHTML: '', style: {}, children: [],
            classList: {add: noop, remove: noop}, focus: noop, reset: noop,
            appendChild(child) { this.children.push(child); }, dispatchEvent: noop,
            getContext: () => ({fillRect: noop, drawImage: noop}), toDataURL: () => 'mock-signature'};
    }
    const get = id => { if (!fields.has(id)) fields.set(id, element()); return fields.get(id); };
    const context = vm.createContext({console: {...console, error: (...args) => { throw new Error(args.join(' ')); }}, Map, Set, Date, Array, String, Event, setTimeout: noop,
        document: {getElementById: get, createElement: element,
            querySelectorAll: () => [{value:'1'}, {value:'2'}]},
        checkPermission: () => true, isWarehouse: s => s === 'En Depósito',
        isAssigned: s => s === 'Asignado', isMaintenance: s => s === 'En Mantenimiento',
        isCanvasBlank: c => !c || !c.signed, getFormattedNow: () => '2026-10-07 15:00:00',
        getActiveUserName: () => 'Prueba', newMovId: () => 'MOV-' + Date.now(),
        saveData: noop, closeModal: noop, renderAll: noop, showToast: noop,
        triggerStockToastAlerts: noop, clearBatchSignature: noop, getEquipmentIcon: () => '',
        OFFICIAL_LOGO_BASE64: '',
    });
    vm.runInContext(`let inventory = [1,2].map(id => ({id:String(id), serial:'TEST-'+id,
        type:'Laptop', brand:'Marca', model:'Modelo', status:'En Depósito', agency:'-', receptor:'-', owner:'-'}));
        let agencies = [{id:'AG-TEST', name:'Agencia prueba', owner:'Responsable'}], movements = [];
        let currentReceiptMovId = null, currentRole = 'admin';
        let canvas = {width:400, height:120, signed:true}, batchCanvas = canvas, returnCanvas = canvas;
        function addMovement(m) { movements.push(m); }
        ${functions.map(fn).join('\n')}`, context);
    for (const prefix of ['dispatch','batch']) {
        get(prefix+'AgencySelect').value = 'Agencia prueba';
        get(prefix+'TargetType').value = 'Dueño Directo';
        get(prefix+'OwnerName').value = 'Responsable';
    }
    get('dispatchEquipmentId').value = '1';
    return {get, run: code => vm.runInContext(code,context)};
}
let count = 0;
for (const category of ['Compra','Venta','Asignación']) {
    for (const mode of ['dispatch','batch']) {
        const t = setup();
        t.get(mode+'TransactionType').value = category;
        t.run(`${mode === 'dispatch' ? 'processDispatch' : 'processBatchDispatch'}({preventDefault() {}})`);
        assert.equal(t.run('movements.length'), 1);
        assert.equal(t.run('movements[0].transactionType'), category);
        assert.equal(t.run('inventory[0].transactionType'), category);
        assert.equal(t.get('receiptTransactionType').innerText, category);
        assert.equal(t.get('receiptTargetType').innerText, 'Dueño Directo');
        assert.equal(t.run('inventory[0].status'), 'Asignado');
        if (mode === 'batch') assert.equal(t.run('inventory[1].transactionType'), category);
        count++;
    }
}
for (const category of ['', 'Otro']) {
    for (const mode of ['dispatch','batch']) {
        const t = setup();
        t.get(mode+'TransactionType').value = category;
        t.run(`${mode === 'dispatch' ? 'processDispatch' : 'processBatchDispatch'}({preventDefault() {}})`);
        assert.equal(t.run('movements.length'), 0);
        assert.equal(t.run('inventory[0].status'), 'En Depósito');
        count++;
    }
}
let t = setup();
t.run(`movements = [{id:'OLD', type:'Salida / Asignación', items:[], agency:'Agencia prueba'}]; viewReceipt('OLD');`);
assert.equal(t.get('receiptTransactionType').innerText, 'Sin registrar');
count++;
t = setup();
t.run(`inventory[0].transactionType = 'Venta'; inventory[1].transactionType = 'Compra';`);
t.get('filterTransactionType').value = 'Venta';
t.run('renderInventoryTable()');
assert.equal(t.get('inventoryTableBody').children.length, 1);
assert.match(t.get('inventoryTableBody').children[0].innerHTML, /TEST-1/);
assert.match(t.get('inventoryTableBody').children[0].innerHTML, /Última entrega: <strong>Venta/);
count++;
t = setup();
t.run(`inventory[0].transactionType = 'Venta';`);
t.get('filterTransactionType').value = 'unrecorded';
t.run('renderInventoryTable()');
assert.equal(t.get('inventoryTableBody').children.length, 1);
assert.match(t.get('inventoryTableBody').children[0].innerHTML, /TEST-2/);
t.run('resetFilters()');
assert.equal(t.get('filterTransactionType').value, '');
count++;
t = setup();
t.get('dispatchTransactionType').value = 'Compra';
t.run('processDispatch({preventDefault() {}}); inventory[0].status = "En Depósito";');
t.run('movements[0].id = "FIRST";');
t.get('dispatchTransactionType').value = 'Venta';
t.run('processDispatch({preventDefault() {}}); viewReceipt("FIRST");');
assert.equal(t.run('inventory[0].transactionType'), 'Venta');
assert.equal(t.get('receiptTransactionType').innerText, 'Compra', 'old receipt keeps its original category');
count++;
assert.match(source, /<w:t>TIPO DE MOVIMIENTO<\/w:t>/);
assert.match(source, /escapeWordXml\(transactionTypeLabel\(mov.transactionType\)\)/);
for (const signed of [false, true]) {
    for (const mode of ['dispatch','batch','return']) {
        const optional = setup();
        optional.run(`canvas.signed = ${signed};`);
        if (mode === 'return') {
            optional.run(`inventory[0].status = 'Asignado'; inventory[0].agency = 'Agencia prueba';`);
            optional.get('returnEquipmentId').value = '1';
            optional.get('returnPersonName').value = 'Responsable';
            optional.get('returnObs').value = 'Devolución de prueba';
        } else {
            optional.get(mode+'TransactionType').value = 'Asignación';
        }
        const action = mode === 'dispatch' ? 'processDispatch' : mode === 'batch' ? 'processBatchDispatch' : 'processReturnToWarehouse';
        optional.run(`${action}({preventDefault() {}})`);
        assert.equal(optional.run('movements.length'), 1, `${mode} must allow ${signed ? 'signed' : 'unsigned'} movements`);
        assert.equal(optional.run('movements[0].signature'), signed ? 'mock-signature' : '');
        assert.equal(optional.get('receiptSignatureImg').style.display, signed ? 'block' : 'none');
        assert.equal(optional.get('receiptSignaturePlaceholder').style.display, signed ? 'none' : 'block');
        assert.equal(optional.run('inventory[0].status'), mode === 'return' ? 'En Depósito' : 'Asignado');
        count++;
    }
}
console.log(`PASS: ${count} movement scenarios (including optional signatures in all three flows).`);
