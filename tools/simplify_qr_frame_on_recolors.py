from pathlib import Path
from PIL import Image, ImageDraw

ROOT = Path(r"C:\Users\mudim\Music\ZemTab")
BASE = ROOT / "cards" / "solid white recolors"

OPTIONS = {
    "option-5-purple-cyan": (109, 40, 217),
    "option-10-black-emerald": (16, 185, 129),
}

WHITE = (255, 255, 255)


def simplify_front(src, dst, accent):
    im = Image.open(src).convert("RGB")
    d = ImageDraw.Draw(im)

    # The source card is 4316x2900. These cover the old three rounded QR frames
    # while staying outside the actual QR code.
    erase_boxes = [
        (2590, 600, 4270, 2265, 240, 220),
        (2760, 740, 4145, 2115, 190, 185),
        (2890, 860, 4025, 2010, 150, 150),
    ]
    for x1, y1, x2, y2, radius, width in erase_boxes:
        d.rounded_rectangle((x1, y1, x2, y2), radius=radius, outline=WHITE, width=width)
    d.rectangle((3985, 720, 4316, 2175), fill=WHITE)
    d.rectangle((2490, 2140, 4316, 2355), fill=WHITE)
    d.rectangle((2500, 480, 4316, 780), fill=WHITE)

    # One clean colored frame around the QR.
    d.rounded_rectangle((2895, 875, 3995, 1985), radius=145, outline=accent, width=24)

    im.save(dst, dpi=(1200, 1200))
    im.save(dst.with_suffix(".pdf"), resolution=1200)


def copy_side(src, dst):
    im = Image.open(src).convert("RGB")
    im.save(dst, dpi=(1200, 1200))
    im.save(dst.with_suffix(".pdf"), resolution=1200)


def main():
    for slug, accent in OPTIONS.items():
        src_dir = BASE / slug
        out_dir = BASE / f"{slug}-single-qr-frame"
        out_dir.mkdir(parents=True, exist_ok=True)

        simplify_front(
            src_dir / f"zemtab-{slug}-front.png",
            out_dir / f"zemtab-{slug}-single-qr-frame-front.png",
            accent,
        )
        copy_side(
            src_dir / f"zemtab-{slug}-back.png",
            out_dir / f"zemtab-{slug}-single-qr-frame-back.png",
        )
        if (src_dir / f"zemtab-{slug}-front-back.png").exists():
            simplify_front(
                src_dir / f"zemtab-{slug}-front-back.png",
                out_dir / f"zemtab-{slug}-single-qr-frame-front-back.png",
                accent,
            )


if __name__ == "__main__":
    main()
