from pathlib import Path
from PIL import Image

ROOT = Path(r"C:\Users\mudim\Music\ZemTab")
SRC = ROOT / "cards" / "new main cards"
OUT = ROOT / "cards" / "solid white recolors"

WHITE = (255, 255, 255)
CHARCOAL = (24, 24, 27)
COOL_GREY = (229, 231, 235)

OPTIONS = {
    "option-5-purple-cyan": {
        "accent": (109, 40, 217),
        "accent2": (8, 145, 178),
    },
    "option-10-black-emerald": {
        "accent": (16, 185, 129),
        "accent2": (17, 24, 39),
    },
}


def recolor_pixel(pixel, accent, accent2):
    r, g, b = pixel[:3]

    # Original pale pink page background.
    if r > 238 and g > 222 and b > 218:
        return WHITE

    # Original low-opacity coral bands and blocks. Make them solid.
    if r > 200 and 130 <= g <= 230 and 120 <= b <= 230 and r > g + 18:
        return accent2

    # Original strong coral/red accent.
    if r > 210 and g < 130 and b < 130:
        return accent

    # Very faint grey construction lines, kept but made print-stable.
    if abs(r - g) < 12 and abs(g - b) < 12 and 185 <= r <= 245:
        return COOL_GREY

    return (r, g, b)


def recolor_image(src, dst, accent, accent2):
    im = Image.open(src).convert("RGB")
    data = [recolor_pixel(px, accent, accent2) for px in im.getdata()]
    im.putdata(data)
    im.save(dst, dpi=(1200, 1200))


def make_pdf(png, pdf):
    Image.open(png).convert("RGB").save(pdf, resolution=1200)


def main():
    for slug, colors in OPTIONS.items():
        folder = OUT / slug
        folder.mkdir(parents=True, exist_ok=True)
        for side in ("front", "back", "front-back"):
            src = SRC / f"zemtab-business-card-1200dpi-{side}.png"
            if not src.exists():
                continue
            png = folder / f"zemtab-{slug}-{side}.png"
            pdf = folder / f"zemtab-{slug}-{side}.pdf"
            recolor_image(src, png, colors["accent"], colors["accent2"])
            make_pdf(png, pdf)


if __name__ == "__main__":
    main()
