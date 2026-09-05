#!/usr/bin/env python3
"""Inyecta las imágenes del cliente como data URIs en el demo de una sola página.
Genera:
  demo/_content.html          -> contenido para publicar como Artifact (sin <html>/<head>/<body>)
  demo/happy-puerto-demo.html -> archivo HTML autocontenido (se abre con doble clic)
"""
import base64, os, sys, re

ROOT = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
A = os.path.join(ROOT, "public", "assets")

def datauri(path, mime):
    with open(path, "rb") as f:
        return f"data:{mime};base64," + base64.b64encode(f.read()).decode("ascii")

jpg = lambda p: datauri(p, "image/jpeg")

IMAGES = {
    "@@HERO@@": jpg(os.path.join(A, "hero-valle.jpg")),
    "@@G1@@": jpg(os.path.join(A, "galeria", "galeria-01.jpg")),
    "@@G2@@": jpg(os.path.join(A, "galeria", "galeria-02.jpg")),
    "@@G3@@": jpg(os.path.join(A, "galeria", "galeria-03.jpg")),
    "@@G4@@": jpg(os.path.join(A, "galeria", "galeria-04.jpg")),
    "@@G5@@": jpg(os.path.join(A, "galeria", "galeria-05.jpg")),
    "@@G6@@": jpg(os.path.join(A, "galeria", "galeria-06.jpg")),
    "@@G7@@": jpg(os.path.join(A, "galeria", "galeria-07.jpg")),
    "@@OCA@@": jpg(os.path.join(A, "ocasiones", "ocasion-aniversario.jpg")),
    "@@OCC@@": jpg(os.path.join(A, "ocasiones", "ocasion-cumpleanos.jpg")),
    "@@OCP@@": jpg(os.path.join(A, "ocasiones", "ocasion-pedida.jpg")),
}

# Logo -> <img> data URI (se usa 2 veces; evita colisión de IDs de <defs>)
logo_uri = datauri(os.path.join(A, "logo-happy-puerto.svg"), "image/svg+xml")
LOGO_IMG = f'<img src="{logo_uri}" alt="Happy Puerto" width="48" height="48" />'

# Cursor -> SVG inline (una sola instancia)
with open(os.path.join(A, "balloon-cursor.svg"), "r", encoding="utf-8") as f:
    CURSOR_SVG = f.read().strip()

src = open(os.path.join(ROOT, "demo", "_inner.html"), "r", encoding="utf-8").read()

for token, uri in IMAGES.items():
    src = src.replace(token, uri)
src = src.replace("@@LOGO@@", LOGO_IMG)
src = src.replace("@@CURSOR@@", CURSOR_SVG)

leftover = re.findall(r"@@[A-Z0-9_]+@@", src)
if leftover:
    print("ERROR: placeholders sin reemplazar:", set(leftover)); sys.exit(1)

# 1) contenido para Artifact
with open(os.path.join(ROOT, "demo", "_content.html"), "w", encoding="utf-8") as f:
    f.write(src)

# 2) HTML autocontenido
standalone = (
    "<!doctype html>\n<html lang=\"es\">\n<head>\n"
    "<meta charset=\"utf-8\" />\n"
    "<meta name=\"viewport\" content=\"width=device-width, initial-scale=1\" />\n"
    "<meta name=\"theme-color\" content=\"#F4B400\" />\n"
    "</head>\n<body>\n" + src + "\n</body>\n</html>\n"
)
out = os.path.join(ROOT, "demo", "happy-puerto-demo.html")
with open(out, "w", encoding="utf-8") as f:
    f.write(standalone)

print("OK")
print("  _content.html          :", os.path.getsize(os.path.join(ROOT, "demo", "_content.html")) // 1024, "KB")
print("  happy-puerto-demo.html :", os.path.getsize(out) // 1024, "KB")
