"""Empaqueta lo necesario para subir a Hostinger. Uso: python3 tools/build-zip.py SALIDA.zip [--plano]"""
import os, sys, zipfile
root = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
out = sys.argv[1]
flat = '--plano' in sys.argv  # public/ queda en la raíz del zip: todo va en una sola carpeta
include = ['app', 'config', 'database', 'tools', 'public', 'storage']
skip_names = {'config.php', 'joyas-pruebas.zip', 'admin-attempts.json', 'content.json', 'dev.sqlite', '.DS_Store'}
with zipfile.ZipFile(out, 'w', zipfile.ZIP_DEFLATED) as z:
    for d in include:
        for base, dirs, files in os.walk(os.path.join(root, d)):
            dirs[:] = [x for x in dirs if x not in ('node_modules', '__pycache__')]
            for f in files:
                if f in skip_names or f.endswith(('.zip', '.sqlite', '.sqlite-wal', '.sqlite-shm')):
                    continue
                full = os.path.join(base, f)
                rel = os.path.relpath(full, root)
                if flat and rel.startswith('public' + os.sep):
                    rel = rel[len('public' + os.sep):]
                z.write(full, rel)
    pass
print('Listo:', out, round(os.path.getsize(out) / 1e6, 1), 'MB')
