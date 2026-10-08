const fs=require('node:fs'),vm=require('node:vm'),assert=require('node:assert/strict');
const source=fs.readFileSync('index.html','utf8');
const library=source.match(/<script id="localHistoryCompression">([\s\S]*?)<\/script>/);
assert(library,'Compression must travel inside index.html');
const libraryContext=vm.createContext({});vm.runInContext(library[1],libraryContext);
const LZString=libraryContext.LZString;
function fn(name){const m=source.match(new RegExp('function '+name+'\\([^]*?\\n        }'));assert(m,name);return m[0];}
const data=JSON.parse(fs.readFileSync('RESPALDO JSON - CORREGIDO.json','utf8'));
const storage=new Map();let errors=0;
const ctx=vm.createContext({console:{log(){},warn(){}},LZString,localStorage:{getItem:k=>storage.get(k)??null,setItem(k,v){const next=new Map(storage);next.set(k,v);if([...next].reduce((n,[k,v])=>n+2*(k.length+v.length),0)>5*1024*1024)throw Error('QuotaExceeded');storage.set(k,v);}},showToast(){errors++;},window:{},getFormattedNow:()=> '2026-10-07 17:00:00'});
ctx.data=data;
const names=['updateDeletedEquipmentFromMovements','isEquipmentDeleted','getAgencyLookupKeys','updateDeletedAgenciesFromMovements','isAgencyDeleted','sanitizeImportedData','autoRecoverMissingEquipmentsFromMovements','normalizeTransactionType','readStoredMovements','safeSetLocalStorage'];
vm.runInContext(`let inventory=data.inventory,agencies=data.agencies,movements=data.movements,maintenanceLog=data.maintenanceLog;
let deletedEquipmentSerials=new Set(),deletedEquipmentIds=new Set(),deletedAgencyCodes=new Set(),deletedAgencyNames=new Set(),deletedAgencyIds=new Set();
function saveDeletedEquipmentTracking(){} function saveDeletedAgenciesTracking(){}
function normSerialKey(v){return String(v??'').toUpperCase().replace(/[^A-Z0-9]/g,'');}
${names.map(fn).join('\n')}
updateDeletedEquipmentFromMovements();updateDeletedAgenciesFromMovements();
globalThis.discardedInv=inventory.filter(i=>isEquipmentDeleted(i.serial,i.id));
globalThis.discardedAg=agencies.filter(a=>isAgencyDeleted(a.id,a.code,a.name));
globalThis.san=sanitizeImportedData();
globalThis.recovered=autoRecoverMissingEquipmentsFromMovements();`,ctx);
console.log('Import filters:',ctx.discardedInv.map(i=>i.serial),ctx.discardedAg.map(a=>a.name),'sanitize',ctx.san,'autoRecovered',ctx.recovered);
assert.equal(ctx.discardedInv.length,0);assert.equal(ctx.discardedAg.length,0);
assert.equal(ctx.san.removedInv,0);assert.equal(ctx.san.fixedAg,0);assert.equal(ctx.recovered,0);
const serial=s=>String(s).toUpperCase().replace(/[^A-Z0-9]/g,'');
const inv=new Map(data.inventory.map(i=>[serial(i.serial),i]));
for(const log of data.maintenanceLog.filter(l=>l.status==='En Taller')){const i=inv.get(serial(log.equipSerial));assert(i);assert.equal(log.equipId,i.id);assert.equal(i.status,'En Mantenimiento');}
for(const i of data.inventory){if(i.status==='Asignado')assert(data.agencies.some(a=>a.id===i.agencyId&&a.name===i.agency));if(i.status==='En Mantenimiento')assert(data.maintenanceLog.some(l=>l.equipId===i.id&&l.status==='En Taller'));}
for(const [key,value] of Object.entries({lotto_inventory_v2:data.inventory,lotto_agencies_v2:data.agencies,lotto_agencies_baseline_v1:data.agencies,lotto_movements_v2:data.movements,lotto_maintenance_v2:data.maintenanceLog,lotto_categories_v2:data.categories,lotto_stock_thresholds_v2:data.stockThresholds})){
 ctx.k=key;ctx.v=JSON.stringify(value);assert.equal(vm.runInContext('safeSetLocalStorage(k,v)',ctx),true);
}
assert.deepEqual(JSON.parse(vm.runInContext('JSON.stringify(readStoredMovements())',ctx)),data.movements);
storage.set('lotto_movements_v2',JSON.stringify([{id:'LEGACY'}]));assert.equal(vm.runInContext('readStoredMovements()[0].id',ctx),'LEGACY');
storage.set('lotto_movements_v2','[{"id":"PREVIOUS"}]');ctx.v='Z'.repeat(10*1024*1024);ctx.k='lotto_inventory_v2';assert.equal(vm.runInContext('safeSetLocalStorage(k,v)',ctx),false);assert.equal(storage.get('lotto_movements_v2'),'[{"id":"PREVIOUS"}]');
console.log('PASS: backup import, reload, complete history within simulated 5 MiB quota, legacy compatibility, failed-write preservation, agency and maintenance relations.');
