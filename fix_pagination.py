import re

with open('c:\\Users\\Grupo Andino\\Documents\\ORLANDO\\grupo_andino_inventario\\INVENTARIO\\Inventario Orlando  (1)\\index.html', 'r', encoding='utf-8') as f:
    content = f.read()

# Remove all stray renderPaginationControls
content = re.sub(r'\s*renderPaginationControls\([^)]+\);', '', content)

# Inject correctly at the end of each table loop
patterns = [
    (r"(function renderInventoryTable\(\).*?paginatedItems\.forEach\(item => \{.*?tbody\.appendChild\(tr\);\n\s+\}\);)",
     r"\1\n                renderPaginationControls(filtered.length, 'inventory', renderInventoryTable);"),
    
    (r"(function renderAgenciesTable\(\).*?paginatedItems\.forEach\(ag => \{.*?tbody\.appendChild\(tr\);\n\s+\}\);)",
     r"\1\n            renderPaginationControls(filteredAgencies.length, 'agencies', renderAgenciesTable);"),

    (r"(function renderMovementsTable\(\).*?paginatedItemsMov\.forEach\(m => \{.*?tbody\.appendChild\(tr\);\n\s+\}\);)",
     r"\1\n            renderPaginationControls(filtered.length, 'movements', renderMovementsTable);"),

    (r"(function renderMaintenanceTable\(\).*?paginatedItemsMaint\.forEach\(m => \{.*?tbody\.appendChild\(tr\);\n\s+\}\);)",
     r"\1\n            renderPaginationControls(activeMaint.length, 'maintenance', renderMaintenanceTable);")
]

for p, repl in patterns:
    content = re.sub(p, repl, content, flags=re.DOTALL)

with open('c:\\Users\\Grupo Andino\\Documents\\ORLANDO\\grupo_andino_inventario\\INVENTARIO\\Inventario Orlando  (1)\\index.html', 'w', encoding='utf-8') as f:
    f.write(content)

print("Fixed")
