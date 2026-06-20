from pathlib import Path
from PIL import Image, ImageDraw

ROOT = Path(r"C:\Users\mudim\Music\ZemTab")
SRC = ROOT / "cards" / "new main cards"
OUT = ROOT / "cards" / "solid white recolors" / "surprise-blue-violet-mint"

WHITE = (255, 255, 255)
INK = (17, 24, 39)
VIOLET = (79, 70, 229)
BLUE = (14, 116, 144)
MINT = (45, 212, 191)
PALE_LINE = (229, 231, 235)


def is_bg(r, g, b):
    return r > 238 and g > 222 and b > 218


def is_old_coral(r, g, b):
    return r > 200 and 110 <= g <= 230 and 105 <= b <= 230 and r > g + 18


def is_strong_coral(r, g, b):
    return r > 210 and g < 135 and b < 135


def is_faint_grey(r, g, b):
    return abs(r - g) < 12 and abs(g - b) < 12 and 185 <= r <= 245


def recolor_base(im):
    out = Image.new("RGB", im.size, WHITE)
    px = []
    for r, g, b in im.convert("RGB").getdata():
        if is_bg(r, g, b):
            px.append(WHITE)
        elif is_old_coral(r, g, b):
            px.append(BLUE)
        elif is_strong_coral(r, g, b):
            px.append(VIOLET)
        elif is_faint_grey(r, g, b):
            px.append(PALE_LINE)
        else:
            px.append((r, g, b))
    out.putdata(px)
    return out


def add_background_front(im):
    d = ImageDraw.Draw(im)
    w, h = im.size
    s = w / 4096

    # More solid background motion, kept away from main text and QR code.
    d.polygon([(int(2280*s), 0), (int(2395*s), 0), (int(2115*s), h), (int(2000*s), h)], fill=MINT)
    d.polygon([(int(2440*s), 0), (int(2580*s), 0), (int(2305*s), h), (int(2165*s), h)], fill=BLUE)
    d.polygon([(int(3880*s), 0), (w, 0), (w, h), (int(3980*s), h)], fill=VIOLET)
    d.polygon([(int(3465*s), 0), (int(3560*s), 0), (int(3360*s), h), (int(3265*s), h)], fill=INK)
    d.line((int(260*s), int(250*s), int(930*s), int(250*s)), fill=VIOLET, width=int(22*s))
    d.line((int(245*s), int(1135*s), int(1030*s), int(1135*s)), fill=BLUE, width=int(16*s))
    d.line((int(1110*s), int(120*s), int(1880*s), int(120*s)), fill=MINT, width=int(12*s))
    d.line((int(1680*s), int(2380*s), int(2300*s), int(2380*s)), fill=VIOLET, width=int(14*s))


def add_background_back(im):
    d = ImageDraw.Draw(im)
    w, h = im.size
    s = w / 4096

    d.rectangle((0, 0, w, int(42*s)), fill=VIOLET)
    d.rectangle((0, h - int(42*s), w, h), fill=BLUE)
    d.polygon([(int(2600*s), 0), (int(2765*s), 0), (int(2895*s), int(950*s)), (int(2730*s), int(950*s))], fill=MINT)
    d.polygon([(int(2880*s), 0), (int(3025*s), 0), (int(3155*s), int(950*s)), (int(3010*s), int(950*s))], fill=BLUE)
    d.polygon([(int(3425*s), 0), (int(3520*s), 0), (int(3390*s), int(950*s)), (int(3295*s), int(950*s))], fill=INK)
    d.polygon([(int(3090*s), int(2315*s)), (int(3255*s), int(2315*s)), (int(3390*s), h), (int(3225*s), h)], fill=MINT)
    d.polygon([(int(3440*s), int(2315*s)), (int(3585*s), int(2315*s)), (int(3450*s), h), (int(3305*s), h)], fill=INK)


def simplify_qr_frame(im):
    # Remove extra colored QR frame outlines, then draw one clean violet frame.
    d = ImageDraw.Draw(im)
    w, _ = im.size
    s = w / 4096
    d.rounded_rectangle(
        (int(2520*s), int(515*s), int(3965*s), int(2020*s)),
        radius=int(165*s),
        outline=WHITE,
        width=int(165*s),
    )
    d.rounded_rectangle(
        (int(2780*s), int(815*s), int(3715*s), int(1745*s)),
        radius=int(120*s),
        outline=VIOLET,
        width=int(24*s),
    )


def process(side):
    src = SRC / f"zemtab-business-card-1200dpi-{side}.png"
    im = recolor_base(Image.open(src))
    if side.startswith("front"):
        add_background_front(im)
        simplify_qr_frame(im)
    else:
        add_background_back(im)
    OUT.mkdir(parents=True, exist_ok=True)
    png = OUT / f"zemtab-surprise-blue-violet-mint-{side}.png"
    pdf = OUT / f"zemtab-surprise-blue-violet-mint-{side}.pdf"
    im.save(png, dpi=(1200, 1200))
    im.convert("RGB").save(pdf, resolution=1200)


def main():
    for side in ("front", "back", "front-back"):
        process(side)


if __name__ == "__main__":
    main()
