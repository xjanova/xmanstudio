"""Build the PDF fonts, with Thai marks that do not collide.

    python resources/fonts/build_thai_pua_font.py

    reads   storage/fonts/Sarabun-Regular.ttf  storage/fonts/Sarabun-Bold.ttf
    writes  storage/fonts/Sarabun-PUA-Regular.ttf  storage/fonts/Sarabun-PUA-Bold.ttf

WHY THIS EXISTS. DomPDF draws every glyph at the one default position the font gives it — it has no
shaping engine and ignores GSUB/GPOS entirely. In Sarabun that default is "directly above the
consonant", which is right on its own and wrong in two cases that fill a Thai document:

  * a tone mark over an upper vowel (ใบแจ้งหนี้, งวดที่, ชื่อ, น้ำ): both are drawn at the same
    height and collide into one blob — this is what a customer sees on the invoice title;
  * a mark on a tall consonant (ป่า ฝั่ง ฟ้า ปิด): it lands on the consonant's ascender.

The fix is the one Thai fonts used for renderers that could not shape: for each case add a
ready-positioned copy of the mark (a composite glyph = the font's own mark, moved by exactly the
offset its GPOS anchors would have applied) at a Private Use Area code point.
[App\\Support\\ThaiShaper] swaps those code points in before the HTML reaches DomPDF.

Adapted from NetWix's resources/fonts/build_thai_font.py, which does the same for GD. Two
differences, both because Sarabun is a static font rather than a variable cut of Noto Sans Thai:

  * no variable-font instancing — each weight is already its own file;
  * no `drift` term. That correction exists in the NetWix script for a font whose base anchor sits
    exactly at the advance; Sarabun's does not (น: anchor 563, advance 637), and adding it there
    shifted the stacked tone 74/1000 em to the left. Both marks are zero-advance, so the tone's own
    anchor already lands on the vowel's mark-to-mark anchor with no horizontal correction at all.

THE PUA LAYOUT BELOW AND THE ONE IN ThaiShaper MUST STAY IDENTICAL — change both or neither.

    U+F700 + i          narrow mark for a tall consonant        i = index in MARKS (12)
    U+F710 + t*8 + v    tone t stacked over upper vowel v        normal consonant
    U+F740 + t*8 + v    tone t stacked over upper vowel v        tall consonant
    U+F770 / U+F771     ญ / ฐ without their descender (before ุ ู)

Sarabun 1.000 from google/fonts, SIL OFL 1.1 — see storage/fonts/Sarabun-OFL.txt.
"""

import os
import sys

from fontTools import subset
from fontTools.ttLib import TTFont
from fontTools.ttLib.tables._g_l_y_f import Glyph, GlyphComponent

UPPER = [chr(c) for c in (0x0E31, 0x0E34, 0x0E35, 0x0E36, 0x0E37, 0x0E47, 0x0E4D)]  # upper vowels
TONES = [chr(c) for c in (0x0E48, 0x0E49, 0x0E4A, 0x0E4B, 0x0E4C)]                  # tone marks, thanthakhat
MARKS = UPPER + TONES

NARROW_BASE = 0xF700
STACK_BASE = 0xF710
STACK_TALL_BASE = 0xF740
DESCLESS = {chr(0x0E0D): 0xF770, chr(0x0E10): 0xF771}   # yo ying, tho than

NORMAL_REF = chr(0x0E19)   # no nu
TALL_REF = chr(0x0E1B)     # po pla: the tall-consonant case

# What survives the subset. Thai + Latin-1 + the punctuation the documents use. The PDF templates
# also print ฿ (U+0E3F, inside the Thai block) and the en dash.
KEEP = (
    list(range(0x20, 0x7F))
    + list(range(0xA0, 0x100))
    + list(range(0x0E01, 0x0E5C))
    + [0x2013, 0x2014, 0x2018, 0x2019, 0x201C, 0x201D, 0x2022, 0x2026, 0x00B7, 0x25CC]
)


def gpos_anchors(font):
    """Collect (base top anchors, mark anchors, mark2 anchors) from the GPOS mark lookups."""
    base, mark, mark2 = {}, {}, {}
    for lookup in font['GPOS'].table.LookupList.Lookup:
        for st in lookup.SubTable:
            kind = lookup.LookupType
            if kind == 9:
                st, kind = st.ExtSubTable, st.ExtSubTable.LookupType
            if kind == 4:
                for i, g in enumerate(st.MarkCoverage.glyphs):
                    r = st.MarkArray.MarkRecord[i]
                    mark.setdefault((g, r.Class), (r.MarkAnchor.XCoordinate, r.MarkAnchor.YCoordinate))
                for i, g in enumerate(st.BaseCoverage.glyphs):
                    for cls, a in enumerate(st.BaseArray.BaseRecord[i].BaseAnchor):
                        if a is not None:
                            base.setdefault((g, cls), (a.XCoordinate, a.YCoordinate))
            elif kind == 6:
                m1 = {g: st.Mark1Array.MarkRecord[i] for i, g in enumerate(st.Mark1Coverage.glyphs)}
                for i, g in enumerate(st.Mark2Coverage.glyphs):
                    for cls, a in enumerate(st.Mark2Array.Mark2Record[i].Mark2Anchor):
                        if a is not None:
                            mark2.setdefault((g, cls), (a.XCoordinate, a.YCoordinate))
                for g, r in m1.items():
                    mark.setdefault((g, 'm1'), (r.MarkAnchor.XCoordinate, r.MarkAnchor.YCoordinate))
    return base, mark, mark2


def build(src, out):
    font = TTFont(src)
    if 'GPOS' not in font:
        raise SystemExit(f'{src}: no GPOS table — nothing to read the mark offsets from')

    cmap = font.getBestCmap()
    glyf, hmtx = font['glyf'], font['hmtx']
    order = font.getGlyphOrder()
    base, mark, mark2 = gpos_anchors(font)

    def g(ch):
        if ord(ch) not in cmap:
            raise SystemExit(f'{src}: no glyph for U+{ord(ch):04X}')

        return cmap[ord(ch)]

    def variant(name, suffix):
        return name + suffix if name + suffix in glyf.glyphs else name

    def top_mark_anchor(name):
        # Top marks attach through class 1 of the mark-to-base lookup; class 0 is the below-base one.
        for key in ((name, 1), (name, 2), (name, 0)):
            if key in mark:
                return mark[key]
        raise SystemExit(f'{src}: no mark anchor for {name}')

    def m1_anchor(name):
        return mark.get((name, 'm1')) or top_mark_anchor(name)

    def m2_anchor(name, fallback):
        anchor = mark2.get((name, 0)) or mark2.get((fallback, 0))
        if anchor is None:
            raise SystemExit(f'{src}: no mark-to-mark anchor for {name} — cannot stack a tone on it')

        return anchor

    def add(code, source, dx, dy, advance=0):
        name = f'pua{code:04X}'
        comp = GlyphComponent()
        comp.glyphName, comp.x, comp.y, comp.flags = source, int(round(dx)), int(round(dy)), 0x4
        glyph = Glyph()
        glyph.numberOfContours, glyph.components = -1, [comp]
        glyf.glyphs[name] = glyph
        order.append(name)
        glyph.recalcBounds(glyf)
        # lsb MUST equal xMin — the renderer places the outline by it, so a stale lsb shifts the mark.
        hmtx.metrics[name] = (advance, getattr(glyph, 'xMin', 0))
        for table in font['cmap'].tables:
            if table.isUnicode():
                table.cmap[code] = name

    tall, normal = g(TALL_REF), g(NORMAL_REF)
    tall_top, tall_adv = base[(tall, 1)], hmtx[tall][0]

    # Narrow marks on a tall consonant: the offset its anchor would apply, relative to where the
    # mark falls with no shaping at all (the consonant's advance).
    narrow = {}
    for i, ch in enumerate(MARKS):
        src_name = variant(g(ch), '.narrow')
        ax, ay = top_mark_anchor(src_name)
        dx, dy = tall_top[0] - ax - tall_adv, tall_top[1] - ay
        narrow[ch] = (src_name, dx, dy)
        add(NARROW_BASE + i, src_name, dx, dy)

    # A tone over an upper vowel. Both marks are zero-advance, so the unshaped tone already sits at
    # the same x as the vowel: only the vowel's own mark-to-mark anchor has to be met.
    for t, tone in enumerate(TONES):
        tone_src = variant(g(tone), '.small')
        tx, ty = m1_anchor(tone_src)
        for v, vowel in enumerate(UPPER):
            vx, vy = m2_anchor(g(vowel), g(vowel))
            add(STACK_BASE + t * 8 + v, tone_src, vx - tx, vy - ty)

            # Same, but the vowel underneath is the narrow one that was pulled left for a tall base.
            nsrc, ndx, ndy = narrow[vowel]
            nvx, nvy = m2_anchor(nsrc, g(vowel))
            add(STACK_TALL_BASE + t * 8 + v, tone_src, ndx + nvx - tx, ndy + nvy - ty)

    for ch, code in DESCLESS.items():
        less = variant(g(ch), '.less')
        add(code, less, 0, 0, advance=hmtx[less][0])

    font.setGlyphOrder(order)

    # Say which font this is, so a stray copy is recognisable as the PDF build.
    family = font['name'].getDebugName(1) or 'Sarabun'
    style = font['name'].getDebugName(2) or 'Regular'
    for nid, value in ((1, f'{family} PUA'), (4, f'{family} PUA {style}'), (6, f'{family}PUA-{style}')):
        font['name'].setName(value, nid, 3, 1, 0x409)

    opts = subset.Options()
    # The layout tables are what DomPDF ignores; dropping them keeps the file small and makes it
    # obvious that nothing here depends on shaping.
    opts.drop_tables += ['GSUB', 'GPOS', 'GDEF', 'DSIG']
    opts.layout_features = []
    opts.name_IDs = ['*']
    opts.notdef_outline = True
    opts.glyph_names = False
    pua = [NARROW_BASE + i for i in range(len(MARKS))]
    pua += [b + t * 8 + v for b in (STACK_BASE, STACK_TALL_BASE) for t in range(len(TONES)) for v in range(len(UPPER))]
    pua += list(DESCLESS.values())
    sub = subset.Subsetter(opts)
    sub.populate(unicodes=KEEP + pua)
    sub.subset(font)

    missing = [hex(c) for c in pua if c not in font.getBestCmap()]
    if missing:
        raise SystemExit(f'{out}: PUA code points missing after subset: {missing}')

    font.save(out)
    print(f'{out}: {os.path.getsize(out)} bytes, {len(pua)} PUA glyphs')


if __name__ == '__main__':
    here = os.path.dirname(os.path.abspath(__file__))
    fonts = os.path.join(os.path.dirname(here), '..', 'storage', 'fonts')
    fonts = os.path.normpath(os.path.join(here, '..', '..', 'storage', 'fonts'))
    if not os.path.isdir(fonts):
        raise SystemExit(f'storage/fonts not found at {fonts}')
    for style in ('Regular', 'Bold'):
        src = os.path.join(fonts, f'Sarabun-{style}.ttf')
        if not os.path.isfile(src):
            raise SystemExit(f'missing source font: {src}')
        build(src, os.path.join(fonts, f'Sarabun-PUA-{style}.ttf'))
    print('done — ThaiShaper must use the same PUA layout as this script')
    sys.exit(0)
