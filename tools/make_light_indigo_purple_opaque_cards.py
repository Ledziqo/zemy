from pathlib import Path

from PIL import Image


ROOT = Path(r"C:\Users\mudim\Music\ZemTab")
SRC = ROOT / "cards" / "new main cards"
OUT = SRC / "light-indigo-purple-opaque"

ACCENT = (124, 92, 255)
NAVY = (17, 24, 39)
OPAQUE_GREY = (209, 213, 219)
WHITE = (255, 255, 255)


def is_blue_accent(r, g, b):
    return b > 150 and 40 <= g <= 130 and r < 90


def is_coral_accent(r, g, b):
    return r > 200 and g < 150 and b < 150


def is_pale_decorative_grey(r, g, b):
    close_channels = abs(r - g) < 10 and abs(g - b) < 10
    return close_channels and 205 <= r <= 245


def recolor_pixel(pixel):
    r, g, b = pixel[:3]

    if r > 248 and g > 248 and b > 248:
        return WHITE

    if is_blue_accent(r, g, b) or is_coral_accent(r, g, b):
        return ACCENT

    if is_pale_decorative_grey(r, g, b):
        return OPAQUE_GREY

    return (r, g, b)


def recolor_image(src, dst):
    image = Image.open(src).convert("RGB")
    image.putdata([recolor_pixel(pixel) for pixel in image.getdata()])
    image.save(dst, dpi=(1200, 1200))


def save_pdf(png, pdf):
    Image.open(png).convert("RGB").save(pdf, resolution=1200)


def main():
    OUT.mkdir(parents=True, exist_ok=True)

    for src in SRC.glob("zemtab-business-card-1200dpi-*.png"):
        side = src.stem.replace("zemtab-business-card-1200dpi-", "")
        png = OUT / f"zemtab-light-indigo-purple-opaque-{side}.png"
        pdf = OUT / f"zemtab-light-indigo-purple-opaque-{side}.pdf"
        recolor_image(src, png)
        save_pdf(png, pdf)


if __name__ == "__main__":
    main()
