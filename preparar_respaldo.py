import json, re, copy, hashlib, unicodedata
from pathlib import Path
from collections import defaultdict
from datetime import datetime

src = Path('RESPALDO JSON.json')
original = json.loads(src.read_text(encoding='utf-8-sig'))
d = copy.deepcopy(original)
changes = []
def record(action, **fields): changes.append(dict(action=action, **fields))
def key(s): return str(s or '').strip().upper()
def serial(s): return re.sub('[^A-Z0-9]', '', key(s))
def plain(s): return ''.join(c for c in unicodedata.normalize('NFD',str(s or '').lower()) if unicodedata.category(c) != 'Mn')
def test(s): return 'prueb' in plain(s)
def uid(prefix, s): return prefix + hashlib.sha256(str(s).encode()).hexdigest()[:12].upper()

# Only identifying equipment fields mark a test. Ordinary observations are retained.
testserials = {serial(i['serial']) for i in d['inventory'] if any(test(i.get(f)) for f in ['type','brand','model','serial'])}
for m in d['movements']:
    for i in m.get('items',[]):
        if any(test(i.get(f)) for f in ['type','brandModel','model','serial']): testserials.add(serial(i.get('serial')))
testnames = {key(a['name']) for a in d['agencies'] if test(a['name'])}
kept=[]
for i in d['inventory']:
    if serial(i['serial']) in testserials: record('omit_test_equipment', id=i['id'], serial=i['serial'])
    else: kept.append(i)
d['inventory']=kept
kept=[]
for m in d['movements']:
    before=m.get('items',[])
    after=[i for i in before if serial(i.get('serial')) not in testserials]
    if key(m.get('agency')) in testnames or (before and not after) or (test(m.get('obs')) and 'categoria' in plain(m.get('type'))):
        record('omit_test_movement', id=m['id'])
        continue
    if len(after)!=len(before): record('remove_test_items', id=m['id'])
    m['items']=after
    kept.append(m)
d['movements']=kept
d['agencies']=[a for a in d['agencies'] if key(a['name']) not in testnames]
d['categories']=[c for c in d['categories'] if not test(c)]
d['stockThresholds']={k:v for k,v in d['stockThresholds'].items() if not test(k)}

# Known agency revisions share immutable IDs; distinct LA agencies remain distinct.
groups=defaultdict(list)
for a in d['agencies']: groups[key(a['id'])].append(a)
aliases={}; agencies=[]
for ident,rows in groups.items():
    if ident=='LA':
        mountain=[a for a in rows if 'COPA DE ORO' in key(a['name'])]
        good=next(a for a in mountain if 'MONTAÑITA' in a['name'])
        aliases.update({key(a['name']):good for a in mountain})
        agencies.append(good)
        record('merge_agency', id=ident, retained=good['name'], omitted=[a['name'] for a in mountain if a is not good])
        for a in rows:
            if a in mountain: continue
            old=a['id']; a['id']=uid('AG-DEP-',a['name'])
            # Existing LA is ambiguous, so give each distinct agency its own code.
            a['legacyCode']=a['code']; a['code']=a['id']
            agencies.append(a); aliases[key(a['name'])]=a
            record('separate_agency_identity', name=a['name'], oldId=old, newId=a['id'], newCode=a['code'])
        continue
    if len(rows)==1: winner=rows[0]
    elif ident=='4994': winner=next(a for a in rows if a['name']=='AG NANY') # Recorded edit MOV-3676.
    elif ident in ['AG-103','AG-114','AG-369']: winner=rows[0] # Properly accented original.
    else:
        winner=next((a for a in rows if not re.fullmatch(r'(?:AG\s*)?\d+',a['name'],re.I)), rows[-1])
    for a in rows:
        aliases[key(a['name'])]=winner
        if a is not winner:
            for f,v in a.items():
                if not winner.get(f) and v and f not in ('id','name','code'): winner[f]=v
    agencies.append(winner)
    if len(rows)>1: record('merge_agency', id=ident, retained=winner['name'], omitted=[a['name'] for a in rows if a is not winner])
d['agencies']=agencies

# Retain equipment identity and hardware details, remove repeated serial rows.
groups=defaultdict(list)
for i in d['inventory']:groups[serial(i['serial'])].append(i)
inv=[]
for sk,rows in groups.items():
    def score(i): return (1000 if i['status']=='Asignado' else 0)+(100 if i.get('processor','').strip() else 0)+(0 if str(i['id']).startswith('REC-') else 50), i.get('dateIn','')
    winner=max(rows,key=score)
    for a in rows:
        if a is winner: continue
        for f,v in a.items():
            if not winner.get(f) and v: winner[f]=v
    inv.append(winner)
    if len(rows)>1: record('merge_equipment', serial=winner['serial'], retainedId=winner['id'], omittedIds=[i['id'] for i in rows if i is not winner])
d['inventory']=inv
byserial={serial(i['serial']):i for i in inv}

# Operational history determines recorded state; edits/deletions aren't assignments.
def state(m):
    t=plain(m.get('type'))
    if 'reingreso' in t or 'devolucion' in t: return 'En Depósito'
    if 'mantenimiento' in t or 'taller' in t: return 'En Mantenimiento'
    if 'salida' in t and ('asignacion' in t or 'combo' in t): return 'Asignado'
    if 'entrada deposito' in t: return 'En Depósito'
    return None
latest={}
for m in sorted(d['movements'],key=lambda m:m.get('date','')):
    if not state(m):continue
    for it in m.get('items',[]): latest[serial(it.get('serial'))]=m
for i in inv:
    m=latest.get(serial(i['serial']))
    if m and state(m)!=i['status']:
        record('reconcile_recorded_state', serial=i['serial'], old=i['status'], new=state(m), sourceMovement=m['id'])
        i['status']=state(m)
        i['dateOut']=m['date'] if i['status']!='En Depósito' else i.get('dateOut','-')
        if i['status']=='Asignado':
            for f in ['agency','receptor','owner']:i[f]=m.get(f,'-')
        else:i['agency']='-';i['receptor']='-'
    if i['status']!='Asignado':i['agency']='-'
    else:
        a=aliases.get(key(i.get('agency')))
        if not a:
            num=re.sub(r'^AG[-\s]*','',key(i.get('agency')))
            a=next((a for a in agencies if key(a['name'])==num or key(a['code'])==num),None)
        if not a:
            name=i.get('agency')
            a={'id':uid('AG-DEP-',name),'code':uid('REC-',name),'name':name,'owner':i.get('owner','-'),'supervisor':'','phone':'','address':'','zone':'','recoveryNote':'Recuperada desde equipo asignado; sin inventar datos de contacto.'}
            agencies.append(a);aliases[key(name)]=a
            record('recover_assigned_agency', name=name, id=a['id'], sourceSerial=i['serial'])
        i['agency']=a['name']; i['agencyId']=a['id']

# Attach historical agency IDs only where the agency name identifies one entity.
for m in d['movements']:
    a=aliases.get(key(m.get('agency')))
    if a and not a.get('recoveryNote'):
        m['agencyId']=a['id']
        # Preserve original printed historical name; the immutable ID resolves edits.

for a in agencies:
    if not a.get('recoveryNote'):continue
    d['movements'].append({'id':uid('MOV-DEP-',a['id']), 'type':'Nueva Agencia', 'date':datetime.now().strftime('%Y-%m-%d %H:%M:%S'), 'agency':a['name'], 'owner':a['owner'], 'receptor':'-', 'receptorType':'Catálogo Agencias', 'items':[], 'user':'Depuración del respaldo', 'obs':'Recuperación del catálogo desde equipos que figuran asignados en el respaldo (Cód: '+a['code']+'). Sin cambio de ubicación física ni entrega nueva.'})

for log in d['maintenanceLog']:
    i=byserial.get(serial(log.get('equipSerial')))
    if not i:
        # Closed history is retained even when equipment was deleted.
        assert log['status']!='En Taller', ('Unresolved active maintenance',log['id'])
        continue
    if log.get('equipId')!=i['id']:
        record('repair_maintenance_link', id=log['id'], old=log.get('equipId'), new=i['id'])
        log['equipId']=i['id']
    if log['status']=='En Taller' and i['status']!='En Mantenimiento':
        m=latest.get(serial(i['serial']))
        assert m and state(m)!='En Mantenimiento'
        log['status']='Reparado';log['dateOut']=m['date']
        log['recoveryNote']='Cierre conciliado con movimiento '+m['id']+'; no certifica reparación física ni coste.'
        record('close_stale_maintenance', id=log['id'], sourceMovement=m['id'])
active={serial(l['equipSerial']) for l in d['maintenanceLog'] if l['status']=='En Taller'}
for i in inv:
    if i['status']=='En Mantenimiento' and serial(i['serial']) not in active:
        m=latest.get(serial(i['serial']))
        l={'id':uid('MNT-DEP-',i['serial']),'equipId':i['id'],'equipSerial':i['serial'],'equipType':i['type'],'status':'En Taller','dateIn':m['date'] if m and state(m)=='En Mantenimiento' else '', 'dateOut':'-','cost':'0.00','workshop':m.get('agency','') if m else '', 'fault':m.get('obs','') if m else '', 'recoveryNote':'Ficha recuperada para vincular el estado de inventario. Fecha/coste originales no registrados; coste 0 no constituye gasto certificado.'}
        d['maintenanceLog'].append(l);active.add(serial(i['serial']))
        record('recover_maintenance_link', id=l['id'], serial=i['serial'], sourceMovement=m['id'] if m else None)

for field in ['id','code']:
    vals=[key(a[field]) for a in agencies];assert len(vals)==len(set(vals)), ('Agency duplicate',field)
assert len({i['id'] for i in inv})==len(inv)
assert len(byserial)==len(inv)
assert all(i['type'] in d['categories'] for i in inv)
assert all(not test(i.get('brand')) and not test(i.get('model')) for i in inv)
out=Path('RESPALDO JSON - CORREGIDO.json')
out.write_text(json.dumps(d,ensure_ascii=False,indent=2),encoding='utf-8')
report={'sourceSha256':hashlib.sha256(src.read_bytes()).hexdigest(),'correctedSha256':hashlib.sha256(out.read_bytes()).hexdigest(),'originalCounts':{k:len(original[k]) for k in ['inventory','agencies','movements','maintenanceLog']},'correctedCounts':{k:len(d[k]) for k in ['inventory','agencies','movements','maintenanceLog']},'changes':changes,'limitations':['Estados conciliados con historial registrado; sin inspección física.','Firmas históricas y tipos de movimiento no registrados se conservan.','La importación no borra otros movimientos existentes en la nube.']}
Path('RESPALDO CORREGIDO - CAMBIOS.json').write_text(json.dumps(report,ensure_ascii=False,indent=2),encoding='utf-8')
print(json.dumps(report['correctedCounts']))
print('Cambios:',len(changes))
