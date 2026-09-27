"""A Grok green-screen mp4 -> the guide's transparent VP9 WebM, framed exactly like her still.

Grok fits the input to its output size and crops what does not fit (about 1%
of the sides for a 2:3 still); this pads it back, so the clip's box has the
input's aspect and frame 0 lands on the still pixel for pixel. An input made
with green.py --pad keeps its 8% margin in the WebM: pass --padded, and list
the clip with `pad: true` in Guide.js CLIPS (the page draws it 8% bigger).

mode  pp        forward then backward: a seamless loop through frame 0
      pp:4      the same over the first 4 s only (smaller file, the lively part)
      once      forward only; once:4.5 = the first 4.5 s

usage: python key.py grok.mp4 nova-welcome.jpg public_html/artwork/universe/guide/clips/idle.webm pp:4.5 [--padded] [--fade 0.04]
Needs ffmpeg/ffprobe with libvpx-vp9 on PATH, and Pillow.
"""
import argparse
import json
import subprocess
from PIL import Image

ap = argparse.ArgumentParser()
ap.add_argument('clip', help="Grok's mp4")
ap.add_argument('input', help='the green JPEG that was given to Grok (green.py)')
ap.add_argument('out')
ap.add_argument('mode')
ap.add_argument('--padded', action='store_true', help='the input was made with green.py --pad')
ap.add_argument('--fade', type=float, default=0.0, help='feather the bottom edge (share of height): a foot stepping past the frame fades instead of being cut')
ap.add_argument('--h', type=int, default=720, help='height of the still-sized box')
ap.add_argument('--crf', type=int, default=40)
ap.add_argument('--bv', default='800k', help='bitrate cap per stream (colour and alpha are encoded separately)')
a = ap.parse_args()

src = Image.open(a.input)
H = round(a.h * (1.16 if a.padded else 1.0) / 2) * 2

p = json.loads(subprocess.check_output([
    'ffprobe', '-v', 'error', '-select_streams', 'v:0', '-count_frames',
    '-show_entries', 'stream=width,height,nb_read_frames,r_frame_rate', '-of', 'json', a.clip,
]))['streams'][0]
vw, vh, n = p['width'], p['height'], int(p['nb_read_frames'])
num, den = (int(x) for x in p['r_frame_rate'].split('/'))
fps = num / den
even = lambda v: max(2, int(round(v / 2) * 2))

OW = even(H * src.width / src.height)
if src.width / src.height >= vw / vh:
    # The input is wider than the clip: Grok matched the height and cropped the sides.
    SW, SH = even(vw * H / vh), H
else:
    # The input is taller: Grok matched the width and cropped top and bottom.
    SW, SH = OW, even(vh * OW / vw)
px, py = round((OW - SW) / 2), round((H - SH) / 2)

# No despill: it drains the blue out of her hair. 0.10 keeps the wispy ends.
chain = f'chromakey=0x00B140:0.10:0.08,scale={SW}:{SH}:flags=lanczos,format=yuva420p,pad={OW}:{H}:{px}:{py}:color=black@0'
if a.fade > 0:
    chain += f",format=yuva444p,geq=lum='p(X,Y)':cb='p(X,Y)':cr='p(X,Y)':a='alpha(X,Y)*min(1,(H-1-Y)/(H*{a.fade}))',format=yuva420p"

def last_frame(spec):
    return n - 1 if ':' not in spec else min(n - 1, round(float(spec.split(':')[1]) * fps))

if a.mode.startswith('pp'):
    last = last_frame(a.mode)
    fc = (f'[0:v]trim=end_frame={last + 1},setpts=PTS-STARTPTS,{chain},split[f][b];'
          f'[b]reverse,trim=start_frame=1:end_frame={last},setpts=PTS-STARTPTS[r];[f][r]concat=n=2:v=1,format=yuva420p[o]')
elif a.mode.startswith('once'):
    fc = f'[0:v]trim=end_frame={last_frame(a.mode) + 1},setpts=PTS-STARTPTS,{chain}[o]'
else:
    raise SystemExit(f'unknown mode {a.mode}')

subprocess.check_call([
    'ffmpeg', '-hide_banner', '-loglevel', 'error', '-y', '-i', a.clip, '-filter_complex', fc, '-map', '[o]',
    '-c:v', 'libvpx-vp9', '-pix_fmt', 'yuva420p', '-b:v', a.bv, '-crf', str(a.crf), '-row-mt', '1',
    '-deadline', 'good', '-cpu-used', '2', '-g', '48', '-an', a.out,
])
q = json.loads(subprocess.check_output(['ffprobe', '-v', 'error', '-show_entries', 'format=duration,size', '-of', 'json', a.out]))['format']
print(json.dumps({'out': a.out, 'box': [OW, H], 'seconds': round(float(q['duration']), 2), 'kb': int(q['size']) // 1024}))
