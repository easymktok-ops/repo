#!/usr/bin/env python3
"""Recorta cada foto original (assets-src/) a la proporción de su espacio en el sitio
y la optimiza (JPG + WebP) en public/assets/img/.

Uso: python3 tools/optimize-images.py   (requiere: pip install pillow)

Para cambiar qué foto va en qué espacio, editar SLOTS. 'focus' es el punto de la foto
que debe quedar en el recorte: (0.5, 0.5) es el centro; (0.5, 0.3) sube el encuadre.
"""
from pathlib import Path
from PIL import Image, ImageOps

Image.MAX_IMAGE_PIXELS = None
ROOT = Path(__file__).resolve().parent.parent
SRC = ROOT / "assets-src"
OUT = ROOT / "public" / "assets" / "img"

# espacio: foto de origen, tamaño final (None = conservar proporción), punto de enfoque
SLOTS = {
    "hero-poster": {"src": "hero-poster.png", "size": None, "max_w": 1920, "focus": (0.5, 0.5)},
    "banda": {"src": "banda.jpg", "size": (1920, 900), "focus": (0.5, 0.5)},
    "contacto": {"src": "escenario.jpeg", "size": (1000, 1250), "focus": (0.5, 0.5)},
}


def crop_to_ratio(im, ratio, focus):
    w, h = im.size
    if w / h > ratio:
        new_w = round(h * ratio)
        left = min(max(round(focus[0] * w - new_w / 2), 0), w - new_w)
        return im.crop((left, 0, left + new_w, h))
    new_h = round(w / ratio)
    top = min(max(round(focus[1] * h - new_h / 2), 0), h - new_h)
    return im.crop((0, top, w, top + new_h))


def save(im, name):
    im = im.convert("RGB")
    im.save(OUT / f"{name}.jpg", "JPEG", quality=82, optimize=True, progressive=True)
    im.save(OUT / f"{name}.webp", "WEBP", quality=80, method=6)
    kb = lambda ext: (OUT / f"{name}.{ext}").stat().st_size // 1024
    print(f"{name:12} {im.size[0]}x{im.size[1]}  jpg {kb('jpg')} KB  webp {kb('webp')} KB")


def main():
    OUT.mkdir(parents=True, exist_ok=True)
    for name, cfg in SLOTS.items():
        path = SRC / cfg["src"]
        if not path.exists():
            print(f"{name:12} sin foto de origen ({cfg['src']})")
            continue
        im = ImageOps.exif_transpose(Image.open(path))
        if cfg["size"]:
            tw, th = cfg["size"]
            im = crop_to_ratio(im, tw / th, cfg["focus"])
            if im.size[0] > tw:
                im = im.resize((tw, th), Image.LANCZOS)
        elif im.size[0] > cfg["max_w"]:
            im = im.resize((cfg["max_w"], round(im.size[1] * cfg["max_w"] / im.size[0])), Image.LANCZOS)
        save(im, name)

    logo = SRC / "logo.png"
    if logo.exists():
        im = Image.open(logo).convert("RGBA")
        im.save(OUT / "logo.png", "PNG", optimize=True)
        im.save(OUT / "logo.webp", "WEBP", lossless=True)
        print(f"{'logo':12} {im.size[0]}x{im.size[1]}  png {(OUT / 'logo.png').stat().st_size // 1024} KB")


if __name__ == "__main__":
    main()
