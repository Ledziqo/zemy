from pathlib import Path

import qrcode
from PIL import Image, ImageDraw, ImageFilter, ImageFont


ROOT = Path(__file__).resolve().parents[1]
CARDS = ROOT / "cards"
OUT = CARDS / "new main cards"
LOGO = ROOT / "logo" / "zemtab-full-transparent-porcelain-coral.png"
LOGO_TEXT = ROOT / "logo" / "zemtab-text-transparent-porcelain-coral.png"

S = 4
W, H = 1079 * S, 725 * S
DPI = 1200
WHITE = (255, 255, 255)
PAPER_TINT = (255, 242, 241)
PANEL_TINT = (255, 234, 232)
QR_TINT = (255, 238, 236)
INK = (24, 24, 27)
MUTED = (113, 113, 122)
CORAL = (248, 76, 71)
CORAL_PALE = (255, 224, 221)
CORAL_WASH = (255, 242, 241)
CHARCOAL_LINE = (42, 42, 45)
BORDER = (218, 218, 214)


def xy(values):
    return tuple(round(v * S) for v in values)


def rgba(color, alpha=None):
    if alpha is None:
        return color
    return color + (alpha,)


def boost_logo_coral(mark):
    pixels = mark.load()
    width, height = mark.size
    for y in range(height):
        for x in range(width):
            r, g, b, a = pixels[x, y]
            if a and r > 150 and g < 150 and b < 150 and r > g * 1.25:
                strength = min(1, max(0, (r - max(g, b)) / 170))
                pixels[x, y] = (
                    round(r * (1 - strength) + CORAL[0] * strength),
                    round(g * (1 - strength) + CORAL[1] * strength),
                    round(b * (1 - strength) + CORAL[2] * strength),
                    a,
                )
    return mark


def font(size, bold=False):
    for path in [
        "C:/Windows/Fonts/arialbd.ttf" if bold else "C:/Windows/Fonts/arial.ttf",
        "C:/Windows/Fonts/segoeuib.ttf" if bold else "C:/Windows/Fonts/segoeui.ttf",
    ]:
        if Path(path).exists():
            return ImageFont.truetype(path, size * S)
    return ImageFont.load_default()


def paste_logo_visible_left(img, x, y, max_w, max_h):
    mark = boost_logo_coral(Image.open(LOGO).convert("RGBA"))
    mark.thumbnail((max_w * S, max_h * S), Image.Resampling.LANCZOS)
    bbox = mark.getbbox()
    if bbox:
        x = x * S - bbox[0]
    img.alpha_composite(mark, (round(x), y * S))


def paste_logo_text_visible_left(img, x, y, max_w, max_h):
    mark = boost_logo_coral(Image.open(LOGO_TEXT).convert("RGBA"))
    mark.thumbnail((max_w * S, max_h * S), Image.Resampling.LANCZOS)
    bbox = mark.getbbox()
    if bbox:
        x = x * S - bbox[0]
    img.alpha_composite(mark, (round(x), y * S))


def soft_shadow(base, rect, radius=30, opacity=55):
    layer = Image.new("RGBA", (W, H), (0, 0, 0, 0))
    d = ImageDraw.Draw(layer)
    d.rounded_rectangle(xy(rect), radius=radius * S, fill=(0, 0, 0, opacity))
    base.alpha_composite(layer.filter(ImageFilter.GaussianBlur(18 * S)))


def qr_image(size):
    qr = qrcode.QRCode(error_correction=qrcode.constants.ERROR_CORRECT_M, border=1, box_size=40)
    qr.add_data("https://zemtab.com")
    qr.make(fit=True)
    return qr.make_image(fill_color=INK, back_color=QR_TINT).convert("RGBA").resize((size * S, size * S), Image.Resampling.NEAREST)


def background(draw):
    draw.rectangle(xy((0, 0, 1079, 725)), fill=PAPER_TINT)
    draw.polygon([xy((0, 0))[0:2], xy((1079, 0))[0:2], xy((1079, 116))[0:2], xy((0, 28))[0:2]], fill=CORAL_WASH)
    draw.polygon([xy((0, 611))[0:2], xy((1079, 701))[0:2], xy((1079, 725))[0:2], xy((0, 725))[0:2]], fill=CORAL_WASH)
    draw.polygon([xy((770, 0))[0:2], xy((1079, 0))[0:2], xy((1079, 725))[0:2], xy((955, 725))[0:2]], fill=CORAL_PALE)
    draw.line(xy((770, 0, 955, 725)), fill=(247, 198, 198), width=2 * S)
    for x in range(-120, 1079 + 220, 128):
        draw.line(xy((x, 795, x + 265, -60)), fill=(230, 230, 226), width=S)


def add_divider(draw):
    draw.rounded_rectangle(xy((94, 292, 262, 301)), radius=5 * S, fill=rgba(CORAL, 235))
    draw.rounded_rectangle(xy((286, 295, 520, 298)), radius=2 * S, fill=(218, 218, 214, 210))
    draw.rounded_rectangle(xy((94, 314, 178, 319)), radius=3 * S, fill=rgba(CHARCOAL_LINE, 225))
    draw.rounded_rectangle(xy((198, 316, 414, 318)), radius=S, fill=rgba(CHARCOAL_LINE, 155))


def scan_rays_without_black(draw, cx, cy):
    for i, color in enumerate([rgba(CORAL, 50), (255, 118, 112, 38)]):
        pad = i * 34
        draw.rounded_rectangle(
            xy((cx - 158 - pad, cy - 158 - pad, cx + 158 + pad, cy + 158 + pad)),
            radius=(42 + pad // 2) * S,
            outline=color,
            width=5 * S,
        )


def front():
    img = Image.new("RGBA", (W, H), PAPER_TINT)
    draw = ImageDraw.Draw(img, "RGBA")
    background(draw)
    draw.rounded_rectangle(xy((84, 74, 245, 84)), radius=5 * S, fill=CORAL)
    paste_logo_visible_left(img, 92, 98, 720, 220)
    add_divider(draw)
    draw.text(xy((92, 358)), "A better guest\nexperience starts\nwith one scan.", font=font(48, True), fill=INK, spacing=4 * S)
    draw.text(xy((96, 552)), "QR menu  |  Table ordering  |  Room service", font=font(25), fill=MUTED)
    draw.rounded_rectangle(xy((92, 604, 530, 662)), radius=29 * S, fill=INK)
    draw.text(xy((311, 633)), "Scan. Order. Request. Pay.", anchor="mm", font=font(26, True), fill=WHITE)
    draw.polygon([xy((701, 0))[0:2], xy((770, 0))[0:2], xy((955, 725))[0:2], xy((886, 725))[0:2]], fill=(255, 213, 209, 150))
    scan_rays_without_black(draw, 864, 358)
    soft_shadow(img, (729, 223, 999, 493), radius=44, opacity=38)
    draw.rounded_rectangle(xy((729, 223, 999, 493)), radius=44 * S, fill=QR_TINT, outline=CORAL, width=6 * S)
    img.alpha_composite(qr_image(202), xy((763, 257)))
    return img.convert("RGB")


def back():
    img = Image.new("RGBA", (W, H), PAPER_TINT)
    draw = ImageDraw.Draw(img, "RGBA")
    background(draw)
    draw.polygon([xy((700, 0))[0:2], xy((770, 0))[0:2], xy((955, 725))[0:2], xy((885, 725))[0:2]], fill=(255, 213, 209, 150))
    draw.text(xy((82, 82)), "Contact", font=font(66, True), fill=INK)
    contact_right = draw.textbbox(xy((82, 82)), "Contact", font=font(66, True))[2] // S
    paste_logo_text_visible_left(img, contact_right + 18, 70, 420, 98)
    draw.text(xy((84, 162)), "German-built. Ethiopia-ready.", font=font(34), fill=MUTED)
    draw.rounded_rectangle(xy((84, 232, 336, 243)), radius=6 * S, fill=CORAL)
    draw.line(xy((360, 237, 1000, 237)), fill=BORDER, width=3 * S)
    soft_shadow(img, (82, 274, 1000, 654), radius=30, opacity=22)
    draw.rounded_rectangle(xy((82, 274, 1000, 654)), radius=30 * S, fill=PANEL_TINT, outline=BORDER, width=2 * S)
    draw.line(xy((535, 320, 535, 606)), fill=BORDER, width=2 * S)
    left_x, right_x, y = 118, 575, 318
    for label, value, yy in [
        ("EMAIL", "zemtab.support@gmail.com", y),
        ("WEB", "zemtab.com", y + 98),
        ("PHONE", "ET +251 974 217 074\nDE +49 160 92988456", y + 196),
    ]:
        draw.text(xy((left_x, yy)), label, font=font(21, True), fill=CORAL)
        draw.text(xy((left_x, yy + 38)), value, font=font(31, True), fill=INK, spacing=8 * S)
    for label, value, yy in [
        ("ETHIOPIA ADDRESS", "Gabon Street, Woreda 02\nAddis Ababa, Ethiopia", y),
        ("GERMANY ADDRESS", "Stuttgart,\nBaden-Württemberg\nGermany", y + 154),
    ]:
        draw.text(xy((right_x, yy)), label, font=font(21, True), fill=CORAL)
        draw.text(xy((right_x, yy + 38)), value, font=font(30, True), fill=INK, spacing=8 * S)
    draw.rounded_rectangle(xy((82, 668, 286, 678)), radius=5 * S, fill=CORAL)
    draw.rounded_rectangle(xy((310, 671, 1000, 675)), radius=2 * S, fill=BORDER)
    return img.convert("RGB")


def save_pdf(images, path):
    first, *rest = images
    first.save(path, "PDF", resolution=DPI, save_all=bool(rest), append_images=rest)


if __name__ == "__main__":
    OUT.mkdir(parents=True, exist_ok=True)
    front_img = front()
    back_img = back()
    front_img.save(OUT / "zemtab-business-card-1200dpi-front.png", "PNG", dpi=(DPI, DPI), optimize=True)
    back_img.save(OUT / "zemtab-business-card-1200dpi-back.png", "PNG", dpi=(DPI, DPI), optimize=True)
    pad = 80 * S
    combined = Image.new("RGB", (front_img.width * 2 + pad, front_img.height), PAPER_TINT)
    combined.paste(front_img, (0, 0))
    combined.paste(back_img, (front_img.width + pad, 0))
    combined.save(OUT / "zemtab-business-card-1200dpi-front-back.png", "PNG", dpi=(DPI, DPI), optimize=True)
    save_pdf([front_img], OUT / "zemtab-business-card-1200dpi-front.pdf")
    save_pdf([back_img], OUT / "zemtab-business-card-1200dpi-back.pdf")
    save_pdf([front_img, back_img], OUT / "zemtab-business-card-1200dpi-front-back.pdf")
    save_pdf([back_img, front_img], OUT / "zemtab-business-card-1200dpi-back-front.pdf")
