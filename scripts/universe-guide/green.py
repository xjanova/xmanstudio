"""The guide's still (transparent webp/png) -> a JPEG on flat green, ready for Grok image-to-video.

Soft glow is cut first (alpha < 70 -> 0, 70..190 stretched, >= 190 -> 255): a
half-transparent fringe mixed with green would key out as a green halo.
--pad adds an 8% green margin on every side: room for Grok to move her without
cropping hands or feet (use it for anything livelier than breathing).

usage: python green.py public_html/artwork/universe/guide/welcome.webp out/nova-welcome.jpg [--pad]
"""
import argparse
from PIL import Image

GREEN = (0x00, 0xB1, 0x40)
PAD = 0.08

ap = argparse.ArgumentParser()
ap.add_argument('still')
ap.add_argument('out')
ap.add_argument('--pad', action='store_true')
a = ap.parse_args()

im = Image.open(a.still).convert('RGBA')
# Grok works from a sharper frame: long side 1536.
k = 1536 / max(im.size)
im = im.resize((round(im.width * k / 2) * 2, round(im.height * k / 2) * 2), Image.LANCZOS)
im.putalpha(im.getchannel('A').point(lambda v: 0 if v < 70 else 255 if v >= 190 else int((v - 70) * 255 / 120)))

mx, my = (round(im.width * PAD), round(im.height * PAD)) if a.pad else (0, 0)
out = Image.new('RGBA', (im.width + 2 * mx, im.height + 2 * my), GREEN + (255,))
out.alpha_composite(im, (mx, my))
out.convert('RGB').save(a.out, quality=93, subsampling=0)
print(a.out, out.size)
