import json

agencies = [
    {"id": "AG-197", "code": "AG-197", "name": "4458"},
    {"id": None, "code": "AG-197", "name": "4458"} # The duplicate from the old bug
]

def agKey(a):
    return str(a.get("id")) if a.get("id") else (str(a.get("code")).strip().upper() if a.get("code") else str(a.get("name")).strip().lower())

editingId = "AG-197"
agIndex = next((i for i, a in enumerate(agencies) if a.get("id") == editingId or a.get("code") == editingId or a.get("name") == editingId), -1)

print("Before filter:", agencies)

targetKey = agKey(agencies[agIndex])

# Old filter
agencies_old = [a for idx, a in enumerate(agencies) if (a.get("id") != editingId or idx == agIndex)]
print("Old filter:", agencies_old)

# New filter
agencies_new = [a for idx, a in enumerate(agencies) if (agKey(a) != targetKey or idx == agIndex)]
print("New filter:", agencies_new)
