# The universe guide's clips (น้อง Nova)

Nova, the guide who flies the 3D home page with the visitor, is a set of
stills (`public_html/artwork/universe/guide/*.webp`, drawn with ChatGPT) and,
where the browser can show them, clips that bring each still to life
(`public_html/artwork/universe/guide/clips/*.webm`). This is how the clips are
made, and the traps met making them. Same technique as the TPIX mascot on
tpix.online.

## What plays when (`resources/js/universe/ui/Guide.js`)

| Clip | From still | Kind | Plays |
|---|---|---|---|
| `idle` | welcome | loop | holding the welcome pose |
| `talk` | welcome | loop | while her line shows (welcome pose) |
| `think` | welcome | loop | while the visitor types a question in her bubble |
| `present` | present | loop | holding the present pose |
| `cheer` | cheer | loop | holding the cheer pose |
| `wave` | bye | loop | holding the bye pose (the footer) |
| `moon` | moon | loop | sitting on the moon |
| `fly` | fly | loop | flying between stops and alongside fast travel |
| `surprise` `heart` `kiss` `shy` | welcome | once | a poke (pointer over her) while standing |
| `heart` `kiss` `shy` | welcome | once | now and then, standing idle (every 9–18 s) |

- Frame 0 of every clip **is** its still, so a clip takes over from the
  picture without a jump. The one-off moves come from `welcome` and cut in
  from any standing pose (welcome, present, cheer, bye) behind a 0.22 s fade.
- Loops are baked **forward-then-backward** (ping-pong), so they pass through
  frame 0 at every turn and never jump.
- Stills stay the fallback: Safari/iOS (WebKit draws VP9 alpha as black),
  Save-Data, reduced motion, phones (she is mostly a corner portrait there,
  and it is their data), and any clip that has not loaded yet.
- A clip is fetched the first time it is wanted; the one-off moves load one
  at a time from 6 s after she appears. ~0.9–1.7 MB each, 18 MB for all 12.
- Measured cost: 17.3 ms/frame with a clip playing vs 16.7 ms without
  (headless Chrome, d3d11), far from the quality governor's 27 fps floor.

## Making a clip

Needs ffmpeg with libvpx-vp9 and Python with Pillow.

1. **Green input** — `python scripts/universe-guide/green.py public_html/artwork/universe/guide/welcome.webp out/nova-welcome.jpg [--pad]`.
   Flat `#00B140`, long side 1536, soft glow cut before compositing.
   Use `--pad` (8% margin) for anything livelier than breathing: without it
   Grok zoomed in on the first `cheer` and cut her legs off at the knee.
2. **Grok Imagine** (grok.com/imagine, the owner's account): Video, 720p,
   6 s, **audio off** (the `Video audio` toggle turns itself back on every
   time the composer reopens). Attach the green JPEG, write the prompt, one
   generation per clip.
3. **Key it** — `python scripts/universe-guide/key.py grok.mp4 out/nova-welcome.jpg public_html/artwork/universe/guide/clips/idle.webm pp:4.5 [--padded] [--fade 0.04]`.
   `pp:4` = ping-pong over the first 4 s (the lively part, and a smaller
   file); `once` / `once:4.5` for one-off moves. `--fade` feathers the bottom
   edge when a foot steps past the frame (`present`).
4. Add it to `CLIPS` in `Guide.js` (`pad: true` if the input was padded) and
   to `$xuClips` in `resources/views/partials/universe/guide.blade.php`.
   `UniverseHomeTest` checks both lists match and every file exists.
5. **Re-encoded a clip under the same name? Bump `$xuClipV`** in the same
   partial: Cloudflare keeps serving the old file.

### Prompt

Thai, one sentence of what she does, then a fixed tail. What was used:

```
สาวอนิเมะโกธิคผมทวินเทลยาวในชุดระบายสีดำ<ทำอะไร เช่น ยืนท่าเดิม กำลังพูดคุยกับผู้ชมอย่างร่าเริงเป็นกันเอง ปากขยับพูดเป็นจังหวะต่อเนื่อง ...>

กล้องนิ่งสนิท ไม่ซูม ไม่แพน ตัวละครอยู่ตำแหน่งเดิมกลางเฟรมขนาดเท่าเดิมตลอดคลิป พื้นหลังเป็นสีเขียวล้วนเรียบเหมือนเดิมตลอดคลิป ไม่มีฉากหรือแสงใหม่ มองเห็นเต็มตัวตั้งแต่ปลายผมจรดรองเท้าตลอดคลิป มีพื้นที่ว่างสีเขียวรอบตัว
```

(The last sentence goes with `--pad` inputs.) Grok still zooms a little on
energetic prompts (`heart`, `shy`): the padding absorbs it.

## Traps

- **"Add last frame" with the same picture does not make a loop.** Grok
  dedups the identical file: the request (`POST /rest/app-chat/conversations/new`,
  `mediaGenInput.imageToVideo.inputAssets`) carries one asset and no end
  frame. The clip drifts and never comes back (last vs first frame: 29 mean
  abs on a 192 px-wide frame). The shipped clips are baked ping-pong instead.
  **Found afterwards (TPIX session, 2026-09-27):** clicking the attached
  picture's thumbnail opens a role menu — First frame / Intermediate / Last
  frame / **Loop** / Reference. Loop sends `referenceToVideo` with the same
  asset as `firstFrameAsset` and `lastFrameAsset`, and the clip does come back
  (first vs last 1.1–1.7 YAVG). A Loop clip can be keyed with `once` and set
  `loop: true`, at half the file size of a ping-pong. First + Last with two
  different pictures makes a transition between two poses.
- The two unnamed `input[type=file]` on the composer belong to image edit:
  a file dropped there starts a new post. Attach through `input[name=files]`.
- **One click, two generations.** The composer fires `conversations/new`
  twice (`enableSideBySide`), which spends double quota. Wrapping
  `window.fetch` to let one through per submit keeps it to one (see the
  BrainX note "Grok Imagine — สูตรขับผ่าน CDP").
- **Grok crops ~1% of the sides** of a 2:3 input (it fits the height).
  `key.py` pads it back; skip that and the clip jumps sideways when it
  replaces the still.
- **No `despill`** in the key: it drains the blue from her hair. Similarity
  0.10 keeps the wispy ends; 0.14 eats them.
- **Getting the mp4 out:** fetch it in the page (`credentials: 'include'`,
  `cache: 'reload'`) into OPFS, then copy it from Chrome's profile
  (`User Data/**/File System`) by its exact byte size. Name OPFS files with
  a prefix of your own and delete only those names afterwards — the OPFS of
  grok.com is shared by every session that drives it.
