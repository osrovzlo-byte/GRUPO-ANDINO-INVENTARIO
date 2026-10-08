import json
def stableStringify(obj):
    if not obj: return json.dumps(obj)
    return json.dumps({k: obj[k] for k in sorted(obj.keys())}, separators=(',', ':'))

agencies = [{"code": "AG-197", "name": "4458", "owner": "Por Especificar"}]
lastSavedAgencies = json.dumps(agencies)

# Simulate edit
agIndex = 0
code = "AG-197"
name = "mijagua"
agencies[agIndex] = {
    "id": agencies[agIndex].get("id") or agencies[agIndex].get("code") or code,
    **agencies[agIndex],
    "code": code,
    "name": name
}

# Simulate cloudSaveList
prev = json.loads(lastSavedAgencies)
prevMap = {}
for it in prev: prevMap["AG-197"] = stableStringify(it)

curMap = {}
for it in agencies: curMap["AG-197"] = it

upserts = []
for k, it in curMap.items():
    if prevMap.get(k) != stableStringify(it):
        upserts.append((k, it))

print("Upserts:", upserts)
