"""Empaqueta lo necesario para subir a Hostinger. Uso: python3 tools/build-zip.py SALIDA.zip"""
import os, sys, zipfile
root = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
out = sys.argv[1]
include = ['app', 'config', 'database', 'tools', 'public', 'storage']
skip_names = {'config.php', 'joyas-pruebas.zip', 'admin-attempts.json', 'content.json', 'dev.sqlite', '.DS_Store'}
with zipfile.ZipFile(out, 'w', zipfile.ZIP_DEFLATED) as z:
    for d in include:
        for base, dirs, files in os.walk(os.path.join(root, d)):
            dirs[:] = [x for x in dirs if x not in ('node_modules', '__pycache__')]
            for f in files:
                if f in skip_names or f.endswith(('.sqlite', '.sqlite-wal', '.sqlite-shm')):
                    continue
                full = os.path.join(base, f)
                z.write(full, os.path.relpath(full, root))
    pass
print('Listo:', out, round(os.path.getsize(out) / 1e6, 1), 'MB')
