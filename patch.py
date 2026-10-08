import re

with open('index.html', 'r', encoding='utf-8') as f:
    content = f.read()

content = re.sub(r'apiKey:\s*"AIzaSyCdi[^"]+"', 'apiKey: "AIzaSyCS3u8jM4RvrFccqfOWM0KyPDBm57bgCUA"', content)
content = re.sub(r'authDomain:\s*"[^"]+"', 'authDomain: "inventario-grupo-andino.firebaseapp.com"', content)
content = re.sub(r'projectId:\s*"[^"]+"', 'projectId: "inventario-grupo-andino"', content)
content = re.sub(r'storageBucket:\s*"[^"]+"', 'storageBucket: "inventario-grupo-andino.firebasestorage.app"', content)
content = re.sub(r'messagingSenderId:\s*"[^"]+"', 'messagingSenderId: "157682372609"', content)
content = re.sub(r'appId:\s*"[^"]+"', 'appId: "1:157682372609:web:f45ade20776c4b2f2fb000"', content)

# Also let's rename the local storage keys so the app starts "empty" as requested!
content = content.replace("'lotto_inventory'", "'lotto_inventory_v2'")
content = content.replace("'lotto_agencies'", "'lotto_agencies_v2'")
content = content.replace("'lotto_movements'", "'lotto_movements_v2'")
content = content.replace("'lotto_maintenance'", "'lotto_maintenance_v2'")
content = content.replace("'lotto_categories'", "'lotto_categories_v2'")
content = content.replace("'lotto_stock_thresholds'", "'lotto_stock_thresholds_v2'")

with open('index.html', 'w', encoding='utf-8') as f:
    f.write(content)
print("Done")
