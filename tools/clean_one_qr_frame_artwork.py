from pathlib import Path
from PIL import Image, ImageDraw
import cv2
import numpy as np

ROOT = Path(r"C:\Users\mudim\Music\ZemTab")
SRC = ROOT / "cards" / "solid white recolors" / "option-10-black-emerald-one-qr-frame" / "zemtab-option-10-black-emerald-front-back-one-qr-frame.png"
OUT = ROOT / "cards" / "solid white recolors" / "option-10-black-emerald-one-qr-frame-clean"

INK = (17, 24, 39)
GREEN = (16, 185, 129)
WHITE = (255, 255, 255)
LIGHT = (229, 231, 235)


def draw_clean_front_qr_path(im, source):
    d = ImageDraw.Draw(im)

    # Clear only the rough front QR path area, away from the QR itself.
    d.rectangle((2680, 0, 3375, 840), fill=WHITE)
    d.rectangle((3515, 470, 4316, 1150), fill=WHITE)
    d.rectangle((3190, 1010, 3840, 2900), fill=WHITE)

    # Rebuild the black path as clean solid geometry: down from top, around the
    # right side of the QR, then down again.
    d.polygon([(2850, 0), (3070, 0), (3440, 1320), (3220, 1320)], fill=INK)
    d.rounded_rectangle((3050, 760, 4045, 2050), radius=170, outline=INK, width=150)
    d.polygon([(3250, 1340), (3470, 1340), (3850, im.height), (3630, im.height)], fill=INK)

    # Restore the QR card area after drawing the black path, so the path sits
    # behind the QR and not across the scannable code.
    qr_card_box = (2970, 875, 4015, 1995)
    im.paste(source.crop(qr_card_box), qr_card_box)

    # Keep just one green frame around the QR, drawn last for crispness.
    d.rounded_rectangle((2916, 892, 3996, 1972), radius=145, outline=GREEN, width=22)


def clean_small_dark_artifacts(im):
    # Fill pinholes/salt artifacts in dark/green text without touching the QR module area.
    qr_exclusion = (2860, 820, 4060, 2040)
    arr = np.array(im)
    x1, y1, x2, y2 = qr_exclusion

    kernel = np.ones((3, 3), np.uint8)
    dark = ((arr[:, :, 0] < 55) & (arr[:, :, 1] < 60) & (arr[:, :, 2] < 70)).astype(np.uint8)
    green = (
        (arr[:, :, 1] > 135)
        & (arr[:, :, 0] < 80)
        & (arr[:, :, 2] < 160)
        & (arr[:, :, 1] > arr[:, :, 0] + 45)
        & (arr[:, :, 1] > arr[:, :, 2] + 10)
    ).astype(np.uint8)

    dark_closed = cv2.morphologyEx(dark, cv2.MORPH_CLOSE, kernel)
    green_closed = cv2.morphologyEx(green, cv2.MORPH_CLOSE, kernel)
    dark_fill = (dark_closed == 1) & (dark == 0)
    green_fill = (green_closed == 1) & (green == 0)
    dark_fill[y1:y2 + 1, x1:x2 + 1] = False
    green_fill[y1:y2 + 1, x1:x2 + 1] = False
    arr[dark_fill] = INK
    arr[green_fill] = GREEN
    im.paste(Image.fromarray(arr))


def save_outputs(im):
    OUT.mkdir(parents=True, exist_ok=True)
    png = OUT / "zemtab-option-10-black-emerald-front-back-one-qr-frame-clean.png"
    im.save(png, dpi=(1200, 1200))
    im.convert("RGB").save(png.with_suffix(".pdf"), resolution=1200)

    hi = im.resize((im.width * 2, im.height * 2), Image.Resampling.LANCZOS)
    hi_png = OUT / "zemtab-option-10-black-emerald-front-back-one-qr-frame-clean-2400dpi.png"
    hi.save(hi_png, dpi=(2400, 2400))
    hi.convert("RGB").save(hi_png.with_suffix(".pdf"), resolution=2400)


def main():
    source = Image.open(SRC).convert("RGB")
    im = source.copy()
    clean_small_dark_artifacts(im)
    draw_clean_front_qr_path(im, source)
    save_outputs(im)


if __name__ == "__main__":
    main()
