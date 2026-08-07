#!/usr/bin/env python3
import base64, io, os, re
from PIL import Image

BASE = "/tmp/claude-0/-home-user-repo/69cb0942-2f14-55e1-a36e-13d034232862/scratchpad/build"
IMG = "/tmp/claude-0/-home-user-repo/69cb0942-2f14-55e1-a36e-13d034232862/scratchpad/site_zip/images"
SRC = f"{BASE}/home_src.html"
OUT = f"{BASE}/index.html"
SRV_SRC = f"{BASE}/servicios_src.html"
SRV_OUT = f"{BASE}/Servicios.html"

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

GTM_ID = "GTM-K73GRX5H"
GTM_HEAD = ("<!-- Google Tag Manager -->\n"
    "<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':new Date().getTime(),event:'gtm.js'});"
    "var f=d.getElementsByTagName(s)[0],j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;"
    "j.src='https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);})"
    "(window,document,'script','dataLayer','" + GTM_ID + "');</script>\n<!-- End Google Tag Manager -->")
GTM_BODY = ('<!-- Google Tag Manager (noscript) --><noscript><iframe src="https://www.googletagmanager.com/ns.html?id='
    + GTM_ID + '" height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>'
    '<!-- End Google Tag Manager (noscript) -->')

tokens = {
    "__GTM_HEAD__": GTM_HEAD,
    "__GTM_BODY__": GTM_BODY,
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

def fill(html):
    for k, v in tokens.items():
        html = html.replace(k, v)
    return html

# ---- build home ----
home_src = open(SRC, encoding="utf-8").read()
home = fill(home_src)
open(OUT, "w", encoding="utf-8").write(home)
print("Wrote", OUT, f"({os.path.getsize(OUT)/1024:.0f} KB)")

# ---- shared design system extracted from the home source ----
shared_style = re.search(r"<style>.*?</style>", home_src, re.S).group(0)
shared_js = re.search(r"<script>\s*\(function\(\)\{\s*var mo.*?</script>", home_src, re.S).group(0)

# ---- build pages that share the design system ----
for src_name, out_name in [("servicios_src.html", "Servicios.html"),
                           ("contact_src.html",  "Contact.html"),
                           ("gracias_src.html",  "gracias.html")]:
    src_path = f"{BASE}/{src_name}"
    if os.path.exists(src_path):
        page = open(src_path, encoding="utf-8").read()
        page = page.replace("__STYLE__", shared_style).replace("__NEURAL_JS__", shared_js)
        page = fill(page)
        out_path = f"{BASE}/{out_name}"
        open(out_path, "w", encoding="utf-8").write(page)
        print("Wrote", out_path, f"({os.path.getsize(out_path)/1024:.0f} KB)")
