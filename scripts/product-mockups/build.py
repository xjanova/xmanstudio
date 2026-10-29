"""Build the app screens shown on the product pages.

    python -I scripts/product-mockups/build.py anti-x [--only name]

Reads scripts/product-mockups/<product>/manifest.json and writes every screen as .webp
into public_html/<out_dir>/. Two kinds of screen:

- "src": a real capture of the app (a PNG somewhere on this PC). `blur` boxes [x, y, w, h]
  hide anything private (our own servers' IPs and names, a user's paths) before it is
  scaled, so nothing private survives in the published file.
- "html": a mock-up, drawn in the app's own style, for a screen we have no capture of yet.
  Rendered with Playwright (Edge) at the manifest's size. The page shows it with a
  "ภาพตัวอย่าง · MOCKUP" tag; swap in a real capture under the same name when there is one.

- key art from ChatGPT is a "src" too (kept outside the repo, in xmanstudio-campaign-media),
  written at its own width; `og` is an html page at 1200×630 saved with "ext": "jpg".

Images are written at `width` (default 1600) wide, quality 86.
"""
from __future__ import annotations

import argparse
import json
import sys
from pathlib import Path

from PIL import Image, ImageFilter

ROOT = Path(__file__).resolve().parents[2]
HERE = Path(__file__).resolve().parent


def blur_boxes(im: Image.Image, boxes: list[list[int]], radius: int = 14) -> Image.Image:
    im = im.convert("RGB")
    for x, y, w, h in boxes:
        box = (x, y, x + w, y + h)
        region = im.crop(box)
        # pixelate first, then blur: no text survives, and the patch still reads as part of the UI
        small = region.resize((max(1, w // 12), max(1, h // 12)), Image.BILINEAR)
        region = small.resize((w, h), Image.BILINEAR).filter(ImageFilter.GaussianBlur(radius))
        im.paste(region, box)
    return im


def render_html(pages: list[tuple[Path, Path, int, int]]) -> None:
    from playwright.sync_api import sync_playwright

    with sync_playwright() as p:
        browser = p.chromium.launch(channel="msedge")
        for html, png, w, h in pages:
            page = browser.new_page(viewport={"width": w, "height": h}, device_scale_factor=1)
            path, _, query = str(html).partition("?")  # a shared page takes its words from the query
            page.goto(Path(path).resolve().as_uri() + ("?" + query if query else ""))
            page.wait_for_load_state("networkidle")
            page.evaluate("document.fonts.ready")
            page.screenshot(path=str(png), full_page=False)
            page.close()
        browser.close()


def main() -> int:
    ap = argparse.ArgumentParser()
    ap.add_argument("product")
    ap.add_argument("--only", help="build just this screen name")
    args = ap.parse_args()

    folder = HERE / args.product
    manifest = json.loads((folder / "manifest.json").read_text(encoding="utf-8"))
    out_dir = ROOT / "public_html" / manifest["out_dir"]
    out_dir.mkdir(parents=True, exist_ok=True)
    tmp = HERE / ".render"
    tmp.mkdir(exist_ok=True)

    screens = [s for s in manifest["screens"] if not args.only or s["name"] == args.only]
    pending = []
    for s in screens:
        if "html" in s:
            pending.append((folder / s["html"], tmp / f"{args.product}-{s['name']}.png", s.get("w", 1440), s.get("h", 900)))
    if pending:
        render_html(pending)

    for s in screens:
        src = tmp / f"{args.product}-{s['name']}.png" if "html" in s else Path(s["src"])
        im = Image.open(src)
        if s.get("crop"):
            x, y, w, h = s["crop"]
            im = im.crop((x, y, x + w, y + h))
        im = blur_boxes(im, s.get("blur", []))
        width = s.get("width", 1600)
        if im.width != width:
            im = im.resize((width, round(im.height * width / im.width)), Image.LANCZOS)
        if s.get("ext") == "jpg":  # og.jpg: social previews want JPEG
            dest = out_dir / f"{s['name']}.jpg"
            im.save(dest, "JPEG", quality=s.get("quality", 86), optimize=True, progressive=True)
        else:
            dest = out_dir / f"{s['name']}.webp"
            im.save(dest, "WEBP", quality=s.get("quality", 86), method=6)
        print(f"{dest.relative_to(ROOT)}  {im.width}x{im.height}  {dest.stat().st_size // 1024} KB  {'mock' if 'html' in s else 'real'}")
    return 0


if __name__ == "__main__":
    sys.exit(main())
