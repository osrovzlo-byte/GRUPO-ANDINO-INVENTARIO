const fs = require('node:fs');
const vm = require('node:vm');
const assert = require('node:assert/strict');
const source = fs.readFileSync(__dirname + '/index.html', 'utf8');
function fn(name) {
    const match = source.match(new RegExp('(?:async )?function ' + name + '\\([^]*?\\n        }'));
    assert.ok(match, `Missing function: ${name}`);
    return match[0];
}
const names = ['stableStringify', 'agKey', 'findAgencyByReference', 'newAgencyId', 'normalizeAgencies', 'applyAgencyChanges',
    'persistAgencyChanges', 'captureAgencyChanges', 'syncAgenciesToCloud', 'getAgencyLookupKeys',
    'isAgencyDeleted', 'markAgencyAsDeleted', 'unmarkAgencyAsDeleted', 'saveAgency', 'deleteAgency'];
const start = source.indexOf('            window.onSnapshot(window.doc(window.db, "lotto_data", "agencies"),');
const end = source.indexOf("}, (err) => handleFirestoreError(err, 'agencies'));", start);
const listener = source.slice(start, end + "}, (err) => handleFirestoreError(err, 'agencies'));".length);
const clone = value => JSON.parse(JSON.stringify(value));
function setup(initial) {
    const storage = new Map();
    const fields = {};
    let remote = clone(initial), callback, hold, fail = false, transactionCount = 0, fullWrites = 0;
    const noop = () => {};
    const ctx = vm.createContext({console, Date, Math, Map, Set, JSON, String, Array,
        localStorage: {getItem: k => storage.get(k) ?? null, setItem: (k,v) => storage.set(k,v), removeItem: k => storage.delete(k)},
        document: {getElementById: id => fields[id] ||= {value: '', classList: {remove: noop}}},
        safeSetLocalStorage: (k,v) => storage.set(k,v),
        saveDeletedAgenciesTracking: noop, persistMovementsSoon: noop,
        populateAgencyDropdowns: noop, renderAgenciesTable: noop, renderInventoryTable: noop,
        updateDashboardStats: noop, closeModal: noop, showToast: noop, updateCloudStatusUI: noop,
        checkPermission: () => true, showConfirm: (title, body, yes) => yes(),
        getFormattedNow: () => '2026-10-07 12:00:00', getActiveUserName: () => 'Test', newMovId: () => 'TEST',
        handleFirestoreError: err => { throw err; },
        window: {db: {}, doc: (...parts) => parts, onSnapshot: (ref, options, cb) => callback = cb,
            setDoc: async () => { fullWrites++; },
            runTransaction: async (db, body) => {
                transactionCount++;
                if (hold) { const pending = hold; hold = null; await pending; }
                if (fail) throw new Error('offline');
                let staged;
                const result = await body({get: async () => ({exists: () => true, data: () => ({list: clone(remote)})}),
                    set: (ref, data) => staged = clone(data.list)});
                remote = staged;
                return result;
            }}
    });
    vm.runInContext(`let agencies = ${JSON.stringify(initial)}, lastSavedAgencies = JSON.stringify(agencies);
        let agencyPendingChanges = new Map(), agencySyncPromise = null;
        let deletedAgencyIds = new Set(), deletedAgencyCodes = new Set(), deletedAgencyNames = new Set();
        let inventory = [], movements = [];
        function addMovement(m) { movements.push(m); }
        function saveData() { captureAgencyChanges(); }
        ${names.map(fn).join('\n')}
        ${listener}`, ctx);
    return {run: code => vm.runInContext(code, ctx), storage, fields,
        remote: () => clone(remote), stats: () => ({transactionCount, fullWrites}),
        snapshot: (list = remote, fromCache = true) => callback({exists: () => true, data: () => ({list: clone(list)}), metadata: {fromCache, hasPendingWrites: false}}),
        fail: value => fail = value,
        hold: () => { let release; hold = new Promise(resolve => release = resolve); return release; },
        edit: (id, code, name) => {
            for (const [key,value] of Object.entries({editingAgencyId: id, agencyCode: code, agencyName: name})) fields[key] = {value};
            vm.runInContext('saveAgency({preventDefault() {}})', ctx);
        }};
}
const a = {id: 'AG-1', code: 'AG-1', name: 'Original'};
const b = {id: 'AG-2', code: 'AG-2', name: 'Otra'};
async function main() {
    let t = setup([a]);
    t.edit(a.id, 'AG-9', 'Nueva');
    t.snapshot([a]);
    assert.equal(t.run('agencies[0].name'), 'Nueva', 'old snapshot must not undo pending edits');
    await t.run('syncAgenciesToCloud()');
    assert.equal(t.remote()[0].id, a.id);
    assert.equal(t.remote()[0].code, 'AG-9');
    assert.equal(t.remote().length, 1);

    t = setup([a,b]);
    t.edit(a.id, a.code, 'Local');
    t.snapshot([a, {...b, name: 'Remota'}]);
    assert.equal(t.run('agencies[1].name'), 'Remota');
    // Simulate remote mutation on a separate client before transaction reads.
    const remoteEdit = setup([a, {...b, name: 'Remota'}]);
    remoteEdit.run(`agencyPendingChanges = new Map(${JSON.stringify(JSON.parse(t.storage.get('lotto_agency_changes_v1')))});`);
    await remoteEdit.run('syncAgenciesToCloud()');
    assert.equal(remoteEdit.remote()[1].name, 'Remota');

    t = setup([a]);
    t.edit(a.id, a.code, 'Primera');
    const release = t.hold();
    const saving = t.run('syncAgenciesToCloud()');
    t.edit(a.id, a.code, 'Segunda');
    t.snapshot([a]);
    release();
    await saving;
    assert.equal(t.remote()[0].name, 'Segunda');
    assert.equal(t.stats().transactionCount, 2);
    assert.equal(t.storage.has('lotto_pending_agencies_sync'), false);

    t = setup([a]);
    t.edit(a.id, a.code, 'Temporal');
    const releaseRevert = t.hold();
    const reverting = t.run('syncAgenciesToCloud()');
    t.edit(a.id, a.code, 'Original');
    releaseRevert();
    await reverting;
    assert.equal(t.remote()[0].name, 'Original', 'revert during first save must also be sent');

    t = setup([a, {...b, name: a.name}]);
    t.run(`deleteAgency('${a.id}')`);
    t.snapshot([a, {...b, name: a.name}]);
    assert.equal(t.run('agencies.length'), 1);
    assert.equal(t.run('agencies[0].id'), b.id, 'deletion must not remove another ID with the same name');
    await t.run('syncAgenciesToCloud()');
    assert.deepEqual(t.remote().map(x => x.id), [b.id]);

    t = setup([a]);
    t.edit(a.id, a.code, 'Offline');
    t.fail(true);
    await assert.rejects(t.run('syncAgenciesToCloud()'), /offline/);
    assert.equal(t.stats().fullWrites, 0, 'failed transaction must not overwrite entire cloud catalog');
    assert.equal(t.storage.get('lotto_pending_agencies_sync'), 'true');
    const restarted = setup([a]);
    restarted.run(`agencyPendingChanges = new Map(${t.storage.get('lotto_agency_changes_v1')});`);
    restarted.snapshot([a]);
    assert.equal(restarted.run('agencies[0].name'), 'Offline');
    await restarted.run('syncAgenciesToCloud()');
    assert.equal(restarted.remote()[0].name, 'Offline');

    t = setup([a, {...a, code: 'Old copy'}, b]);
    t.edit(b.id, b.code, 'Editada');
    await t.run('syncAgenciesToCloud()');
    assert.equal(t.remote().length, 2, 'copies of one immutable ID collapse');
    assert.equal(t.remote().find(x => x.id === b.id).name, 'Editada');

    t = setup([a]);
    t.run(`inventory = [{serial: 'TEST', agency: 'Original'}]; movements = [{agency: 'Original'}];`);
    t.edit(a.id, a.code, 'Renombrada');
    assert.equal(t.run('inventory[0].agency'), 'Renombrada');
    assert.equal(t.run('movements[0].agency'), 'Renombrada');
    assert.equal(t.run('movements[1].agencyId'), a.id);

    t = setup([a,b]);
    t.snapshot([b]);
    assert.equal(t.run('agencies.length'), 1, 'remote deletion must not resurrect a stale local agency');
    assert.equal(t.run('agencyPendingChanges.size'), 0);

    t = setup([a, {...b, name: '1'}]);
    t.run(`deleteAgency('${a.id}')`);
    assert.equal(t.run('agencies.length'), 1, 'numeric names must not match another agency tombstone ID');

    t = setup([{...b, code: a.id}, {...a, code: 'AG-9'}]);
    t.edit(a.id, 'AG-9', 'Elegida por ID');
    assert.equal(t.run('agencies[1].name'), 'Elegida por ID', 'ID must take precedence over another agency code');
    assert.equal(t.run('agencies[0].name'), b.name);
    console.log('PASS: 12 agency synchronization scenarios (mock Firestore, no production writes).');
}
main().catch(err => {console.error(err); process.exitCode = 1;});
