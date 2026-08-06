#!/usr/bin/env python3
import base64, io, os
from PIL import Image

IMG = "/tmp/claude-0/-home-user-repo/69cb0942-2f14-55e1-a36e-13d034232862/scratchpad/site_zip/images"
SRC = "/tmp/claude-0/-home-user-repo/69cb0942-2f14-55e1-a36e-13d034232862/scratchpad/build/home_src.html"
OUT = "/tmp/claude-0/-home-user-repo/69cb0942-2f14-55e1-a36e-13d034232862/scratchpad/build/index.html"

def datauri(path, mime):
    with open(path, "rb") as f:
        return f"data:{mime};base64," + base64.b64encode(f.read()).decode()

def png_resized(path, max_w):
    im = Image.open(path).convert("RGBA")
    if im.width > max_w:
        h = round(im.height * max_w / im.width)
        im = im.resize((max_w, h), Image.LANCZOS)
    buf = io.BytesIO(); im.save(buf, "PNG", optimize=True)
    return "data:image/png;base64," + base64.b64encode(buf.getvalue()).decode()

def svg_uri(path):
    with open(path, encoding="utf-8") as f:
        data = f.read().encode("utf-8")
    return "data:image/svg+xml;base64," + base64.b64encode(data).decode()

def jpg_cover(path, size):
    im = Image.open(path).convert("RGB")
    # center-crop to square then resize
    s = min(im.size); l=(im.width-s)//2; t=(im.height-s)//2
    im = im.crop((l,t,l+s,t+s)).resize((size,size), Image.LANCZOS)
    buf = io.BytesIO(); im.save(buf, "JPEG", quality=82, optimize=True, progressive=True)
    return "data:image/jpeg;base64," + base64.b64encode(buf.getvalue()).decode()

tokens = {
    "__FAVICON__": datauri(f"{IMG}/favicon.png", "image/png"),
    "__LOGO__":    png_resized(f"{IMG}/Logo-easy-mkt-transparente-blanco.png", 540),
    "__L_ALZA__":  png_resized(f"{IMG}/Logo-Alzatalent-horizontal.png", 500),
    "__L_BBS__":   png_resized(f"{IMG}/Logo-BBS-web-white.png", 260),
    "__L_KARMA__": png_resized(f"{IMG}/Logo_Banner_Karma-Fiscal-Asesores_blanco.png", 400),
    "__L_RF__":    png_resized(f"{IMG}/RF-Logo_PNG-W-Fit.png", 320),
    "__L_SPA__":   png_resized(f"{IMG}/THe-Spa-logo.png", 480),
    "__L_LINQ__":  png_resized(f"{IMG}/logo-5-linq.png", 520),
    "__L_GETNET__":  svg_uri(f"{IMG}/getnet.svg"),
    "__L_FRAMESI__": svg_uri(f"{IMG}/framesi.svg"),
    "__L_BAIT__":    png_resized(f"{IMG}/bait.png", 360),
    "__NORMAN__":  jpg_cover(f"{IMG}/Norman-Oswald-White-Compressed.jpg", 360),
}

with open(SRC, encoding="utf-8") as f:
    html = f.read()
for k, v in tokens.items():
    html = html.replace(k, v)

with open(OUT, "w", encoding="utf-8") as f:
    f.write(html)

print("Wrote", OUT, f"({os.path.getsize(OUT)/1024:.0f} KB)")
for k, v in tokens.items():
    print(f"  {k:12} {len(v)/1024:6.1f} KB (base64)")
