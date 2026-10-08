import json

agencies = [
    {"code": "AG-197", "name": "4458", "owner": "Por Especificar"}
]
# Simulate saveAgency
editingId = "AG-197"
agIndex = next((i for i, a in enumerate(agencies) if a.get("id") == editingId or a.get("code") == editingId or a.get("name") == editingId), -1)

code = "AG-197"
name = "mijagua"

if agIndex >= 0:
    agencies[agIndex] = {
        "id": agencies[agIndex].get("id") or agencies[agIndex].get("code") or code,
        **agencies[agIndex],
        "code": code,
        "name": name
    }

print("After saveAgency:", agencies)

# Simulate cloudSaveList
# ... it pushes to Firestore. Let's assume it succeeds.
cloudAg = [{"id": "AG-197", "code": "AG-197", "name": "4458", "owner": "Por Especificar"}] # Wait, if it didn't push, cloudAg has old data.

def agKey(a):
    return str(a.get("id")) if a.get("id") else (str(a.get("code")).strip().upper() if a.get("code") else str(a.get("name")).strip().lower())

agMap = {}
for a in cloudAg:
    agMap[agKey(a)] = a

hasPendingSync = False # Assuming setDoc didn't reject yet
localOnlyAgenciesFound = False
for a in agencies:
    key = agKey(a)
    if key not in agMap or hasPendingSync:
        agMap[key] = a
        localOnlyAgenciesFound = True

agencies = list(agMap.values())
print("After onSnapshot:", agencies)
