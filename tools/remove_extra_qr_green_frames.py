from pathlib import Path
from PIL import Image

ROOT = Path(r"C:\Users\mudim\Music\ZemTab")
SRC = ROOT / "cards" / "solid white recolors" / "option-10-black-emerald" / "zemtab-option-10-black-emerald-front-back.png"
OUT_DIR = ROOT / "cards" / "solid white recolors" / "option-10-black-emerald-one-qr-frame"

GREEN = (16, 185, 129)
WHITE = (255, 255, 255)
INK = (17, 24, 39)


def is_green(px):
    r, g, b = px
    return g > 135 and r < 80 and b < 160 and g > r + 45 and g > b + 10


def is_black(px):
    r, g, b = px
    return r < 35 and g < 45 and b < 60


def main():
    im = Image.open(SRC).convert("RGB")
    original = im.copy()
    pix = im.load()
    original_pix = original.load()

    # Coordinates are for the 8952x2900 combined front/back export.
    # Green component bboxes in the QR area:
    # outer: 2688,664,4224,2200
    # middle: 2824,800,4088,2064
    # inner: 2916,892,3996,1972
    # Remove green pixels in the outer/middle zones while preserving the inner
    # frame and every non-green pixel, including the black path behind it.
    remove_boxes = [
        (2660, 640, 4250, 2225),
        (2800, 775, 4115, 2090),
    ]
    keep_inner = (2895, 870, 4015, 1995)

    for y in range(im.height):
        for x in range(im.width):
            in_remove = any(x1 <= x <= x2 and y1 <= y <= y2 for x1, y1, x2, y2 in remove_boxes)
            in_keep = keep_inner[0] <= x <= keep_inner[2] and keep_inner[1] <= y <= keep_inner[3]
            if in_remove and not in_keep and is_green(pix[x, y]):
                near_black = False
                for yy in range(max(0, y - 18), min(im.height, y + 19), 3):
                    for xx in range(max(0, x - 18), min(im.width, x + 19), 3):
                        if is_black(original_pix[xx, yy]):
                            near_black = True
                            break
                    if near_black:
                        break
                pix[x, y] = INK if near_black else WHITE

    OUT_DIR.mkdir(parents=True, exist_ok=True)
    out = OUT_DIR / "zemtab-option-10-black-emerald-front-back-one-qr-frame.png"
    im.save(out, dpi=(1200, 1200))
    im.convert("RGB").save(out.with_suffix(".pdf"), resolution=1200)

    out_2400 = OUT_DIR / "zemtab-option-10-black-emerald-front-back-one-qr-frame-2400dpi.png"
    hi = im.resize((im.width * 2, im.height * 2), Image.Resampling.LANCZOS)
    hi.save(out_2400, dpi=(2400, 2400))
    hi.convert("RGB").save(out_2400.with_suffix(".pdf"), resolution=2400)


if __name__ == "__main__":
    main()
