from pathlib import Path
from PIL import Image, ImageDraw, ImageFont

ROOT = Path(r"C:\Users\mudim\Music\ZemTab")
OLD = ROOT / "cards" / "new main cards" / "zemtab-business-card-1200dpi-front.png"
OUT = ROOT / "cards" / "solid white redesign previews"

W, H = 2048, 1280
WHITE = (255, 255, 255)
CHARCOAL = (17, 24, 39)
GREY = (100, 116, 139)
LIGHT = (229, 231, 235)

FONT_BOLD = r"C:\Windows\Fonts\arialbd.ttf"
FONT_REG = r"C:\Windows\Fonts\arial.ttf"


def font(size, bold=False):
    return ImageFont.truetype(FONT_BOLD if bold else FONT_REG, size)


def rounded_rect(draw, xy, radius, fill=None, outline=None, width=1):
    draw.rounded_rectangle(xy, radius=radius, fill=fill, outline=outline, width=width)


def make_qr_asset():
    old = Image.open(OLD).convert("RGB")
    crop = old.crop((3000, 1000, 3900, 1900)).convert("L")
    bw = crop.point(lambda p: 0 if p < 120 else 255, "1").convert("RGB")
    canvas = Image.new("RGB", (430, 430), WHITE)
    qr = bw.resize((360, 360), Image.Resampling.NEAREST)
    canvas.paste(qr, (35, 35))
    return canvas


def draw_logo(draw, x, y, accent):
    draw.line((x + 20, y + 0, x + 320, y + 0), fill=accent, width=18)
    draw.line((x + 20, y + 425, x + 320, y + 425), fill=accent, width=18)
    draw.text((x, y + 70), "Z", font=font(150, True), fill=accent)
    rounded_rect(draw, (x + 15, y + 70, x + 155, y + 235), 18, outline=CHARCOAL, width=9)
    draw.text((x + 205, y + 96), "Zem", font=font(100, True), fill=CHARCOAL)
    draw.text((x + 455, y + 96), "Tab", font=font(100, True), fill=accent)


def draw_front(name, accent, accent2):
    im = Image.new("RGB", (W, H), WHITE)
    d = ImageDraw.Draw(im)

    # Solid geometric signal marks, all fully opaque.
    d.polygon([(1540, 0), (1710, 0), (1300, H), (1130, H)], fill=accent2)
    d.polygon([(1840, 0), (2048, 0), (2048, H), (1720, H)], fill=accent)
    d.line((0, 0, 2048, 1280), fill=LIGHT, width=5)
    d.line((330, 0, 780, 1280), fill=LIGHT, width=5)
    d.line((880, 0, 1330, 1280), fill=LIGHT, width=5)

    draw_logo(d, 170, 150, accent)

    d.line((175, 560, 495, 560), fill=accent, width=18)
    d.line((545, 560, 980, 560), fill=LIGHT, width=8)
    d.line((180, 600, 330, 600), fill=CHARCOAL, width=8)
    d.line((370, 600, 780, 600), fill=CHARCOAL, width=6)

    d.text((175, 690), "A better guest", font=font(70, True), fill=CHARCOAL)
    d.text((175, 780), "experience starts", font=font(70, True), fill=CHARCOAL)
    d.text((175, 870), "with one scan.", font=font(70, True), fill=CHARCOAL)
    d.text((180, 1045), "QR menu  |  Table ordering  |  Room service", font=font(43), fill=GREY)

    rounded_rect(d, (175, 1145, 1000, 1238), 47, fill=CHARCOAL)
    d.text((275, 1166), "Scan. Order. Request. Pay.", font=font(48, True), fill=WHITE)

    qr = make_qr_asset()
    rounded_rect(d, (1270, 315, 1980, 1025), 120, outline=accent, width=14)
    rounded_rect(d, (1340, 380, 1910, 960), 86, outline=accent2, width=12)
    rounded_rect(d, (1400, 435, 1850, 895), 68, outline=accent, width=10)
    im.paste(qr, (1410, 445))

    return im


def draw_back(name, accent, accent2):
    im = Image.new("RGB", (W, H), WHITE)
    d = ImageDraw.Draw(im)

    d.rectangle((0, 0, W, 75), fill=accent)
    d.rectangle((0, H - 75, W, H), fill=accent2)
    d.polygon([(1630, 75), (1770, 75), (1375, H - 75), (1235, H - 75)], fill=LIGHT)
    d.line((0, 175, W, 760), fill=LIGHT, width=5)
    d.line((375, 75, 820, H - 75), fill=LIGHT, width=5)
    d.line((920, 75, 1365, H - 75), fill=LIGHT, width=5)

    d.text((155, 165), "Contact Zem", font=font(108, True), fill=CHARCOAL)
    d.text((755, 165), "Tab", font=font(108, True), fill=accent)
    d.text((160, 305), "German-built. Ethiopia-ready.", font=font(58), fill=GREY)
    d.line((160, 430, 640, 430), fill=accent, width=18)
    d.line((685, 430, 1890, 430), fill=LIGHT, width=8)

    rounded_rect(d, (155, 485, 1895, 1195), 55, fill=WHITE, outline=LIGHT, width=8)
    d.line((1005, 570, 1005, 1115), fill=LIGHT, width=6)

    d.text((225, 570), "EMAIL", font=font(38, True), fill=accent)
    d.text((225, 645), "zemtab.support@gmail.com", font=font(51, True), fill=CHARCOAL)
    d.text((225, 760), "WEB", font=font(38, True), fill=accent)
    d.text((225, 835), "zemtab.com", font=font(51, True), fill=CHARCOAL)
    d.text((225, 950), "PHONE", font=font(38, True), fill=accent)
    d.text((225, 1020), "ET +251 974 217 074", font=font(45, True), fill=CHARCOAL)
    d.text((225, 1080), "DE +49 160 92988456", font=font(45, True), fill=CHARCOAL)

    d.text((1080, 570), "ETHIOPIA ADDRESS", font=font(38, True), fill=accent2)
    d.text((1080, 645), "Gabon Street, Woreda 02", font=font(51, True), fill=CHARCOAL)
    d.text((1080, 710), "Addis Ababa, Ethiopia", font=font(51, True), fill=CHARCOAL)
    d.text((1080, 850), "GERMANY ADDRESS", font=font(38, True), fill=accent2)
    d.text((1080, 925), "Stuttgart,", font=font(51, True), fill=CHARCOAL)
    d.text((1080, 990), "Baden-Wurttemberg", font=font(51, True), fill=CHARCOAL)
    d.text((1080, 1055), "Germany", font=font(51, True), fill=CHARCOAL)

    return im


def save_set(slug, label, accent, accent2):
    folder = OUT / slug
    folder.mkdir(parents=True, exist_ok=True)
    front = draw_front(label, accent, accent2)
    back = draw_back(label, accent, accent2)
    front_png = folder / f"zemtab-{slug}-front.png"
    back_png = folder / f"zemtab-{slug}-back.png"
    front_print = front.resize((W * 2, H * 2), Image.Resampling.LANCZOS)
    back_print = back.resize((W * 2, H * 2), Image.Resampling.LANCZOS)
    front_print.save(front_png, dpi=(1200, 1200))
    back_print.save(back_png, dpi=(1200, 1200))
    front_print.save(folder / f"zemtab-{slug}-front.pdf", resolution=1200)
    back_print.save(folder / f"zemtab-{slug}-back.pdf", resolution=1200)


def main():
    save_set("option-5-purple-cyan", "Option 5", (109, 40, 217), (8, 145, 178))
    save_set("option-10-black-emerald", "Option 10", (16, 185, 129), (17, 24, 39))


if __name__ == "__main__":
    main()
