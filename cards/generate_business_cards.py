from pathlib import Path
import math

import qrcode
from PIL import Image, ImageDraw, ImageFont


ROOT = Path(__file__).resolve().parents[1]
OUT = ROOT / "cards"
LOGO = ROOT / "logo" / "zemtab-full-transparent-porcelain-coral.png"

W, H = 1079, 725
DPI = 300
PORCELAIN = (247, 247, 244)
CARD = (255, 255, 255)
SOFT = (241, 241, 238)
INK = (24, 24, 27)
MUTED = (113, 113, 122)
CORAL = (232, 93, 93)
BORDER = (218, 218, 214)


def font(size, bold=False):
    names = [
        "C:/Windows/Fonts/arialbd.ttf" if bold else "C:/Windows/Fonts/arial.ttf",
        "C:/Windows/Fonts/segoeuib.ttf" if bold else "C:/Windows/Fonts/segoeui.ttf",
        "/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf" if bold else "/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf",
    ]
    for name in names:
        if Path(name).exists():
            return ImageFont.truetype(name, size)
    return ImageFont.load_default()


def rounded_rect(draw, xy, radius, fill, outline=None, width=1):
    draw.rounded_rectangle(xy, radius=radius, fill=fill, outline=outline, width=width)


def add_background(draw):
    draw.rectangle((0, 0, W, H), fill=CARD)
    for x in range(75, W + 200, 115):
        draw.line((x - 85, -40, x + 150, H + 80), fill=BORDER, width=1)
    draw.polygon([(0, H - 56), (W, H - 132), (W, H), (0, H)], fill=(251, 239, 239))


def paste_logo(img, box):
    logo = Image.open(LOGO).convert("RGBA")
    logo.thumbnail((box[2] - box[0], box[3] - box[1]), Image.Resampling.LANCZOS)
    x = box[0] + ((box[2] - box[0]) - logo.width) // 2
    y = box[1] + ((box[3] - box[1]) - logo.height) // 2
    img.alpha_composite(logo, (x, y))


def draw_accent(draw, x, y, h, flip=False):
    if flip:
        draw.rounded_rectangle((x, 0, x + 29, y), radius=16, fill=INK)
        draw.rounded_rectangle((x + 31, 0, x + 60, y + 20), radius=16, fill=CORAL)
        draw.rounded_rectangle((x + 64, 0, x + 91, y - 29), radius=16, fill=(252, 228, 228))
    else:
        draw.rounded_rectangle((x, y, x + 8, y + h - 100), radius=6, fill=INK)
        draw.rounded_rectangle((x + 13, y, x + 22, y + h), radius=6, fill=CORAL)


def draw_rule(draw, x1, y, x2, coral_x=None):
    draw.line((x1, y, x2, y), fill=BORDER, width=3)
    if coral_x is not None:
        draw.rounded_rectangle((coral_x, y - 4, coral_x + 112, y + 4), radius=4, fill=CORAL)


def qr_image(size):
    qr = qrcode.QRCode(error_correction=qrcode.constants.ERROR_CORRECT_M, border=1, box_size=10)
    qr.add_data("https://zemtab.com")
    qr.make(fit=True)
    return qr.make_image(fill_color=INK, back_color=CARD).convert("RGBA").resize((size, size), Image.Resampling.NEAREST)


def front():
    img = Image.new("RGBA", (W, H), CARD)
    draw = ImageDraw.Draw(img)
    add_background(draw)
    draw_accent(draw, 892, 365, 0, flip=True)
    paste_logo(img, (215, 150, 865, 342))
    draw_rule(draw, 312, 397, 768, coral_x=(312 + 768 - 112) // 2)
    draw.text((W // 2, 470), "QR Menu, Table & Room Ordering", anchor="mm", font=font(34, True), fill=INK)
    draw.text((W // 2, 518), "for restaurants, hotels, cafes and lounges", anchor="mm", font=font(25), fill=MUTED)
    rounded_rect(draw, (351, 576, 729, 638), 31, INK)
    draw.text((540, 606), "Scan. Order. Request. Pay.", anchor="mm", font=font(25, True), fill=CARD)
    for x, label, color in [(160, "Fast setup", CORAL), (468, "Secure orders", INK), (782, "Clear support", CORAL)]:
        draw.ellipse((x - 7, 648, x + 7, 662), fill=color)
        draw.text((x + 19, 655), label, anchor="lm", font=font(16, True), fill=MUTED)
    return img.convert("RGB")


def back():
    img = Image.new("RGBA", (W, H), CARD)
    draw = ImageDraw.Draw(img)
    add_background(draw)
    draw_accent(draw, 78, 82, 408)
    paste_logo(img, (126, 48, 430, 156))
    draw.text((126, 198), "Contact Us", font=font(51, True), fill=INK)
    draw.text((126, 256), "German-made. Built in Ethiopia.", font=font(30), fill=MUTED)
    draw_rule(draw, 126, 318, 976, coral_x=126)

    qr_size = 174
    qr_x, qr_y = 789, 55
    rounded_rect(draw, (qr_x - 13, qr_y - 13, qr_x + qr_size + 13, qr_y + qr_size + 13), 18, CARD, CORAL, 4)
    rounded_rect(draw, (qr_x - 5, qr_y - 5, qr_x + qr_size + 5, qr_y + qr_size + 5), 10, CARD, BORDER, 1)
    img.alpha_composite(qr_image(qr_size), (qr_x, qr_y))
    draw.text((qr_x + qr_size // 2, qr_y + qr_size + 45), "zemtab.com", anchor="mm", font=font(30, True), fill=CORAL)

    left_x, right_x = 126, 560
    y = 352
    draw.text((left_x, y), "EMAIL", font=font(18, True), fill=CORAL)
    draw.text((left_x, y + 36), "zemtab.support@gmail.com", font=font(31, True), fill=INK)
    draw.text((left_x, y + 94), "WEB", font=font(18, True), fill=CORAL)
    draw.text((left_x, y + 130), "zemtab.com", font=font(31, True), fill=INK)
    draw.text((left_x, y + 188), "PHONE", font=font(18, True), fill=CORAL)
    draw.text((left_x, y + 224), "ET +251 974 217 074", font=font(30, True), fill=INK)
    draw.text((left_x, y + 262), "DE +49 160 92988456", font=font(30, True), fill=INK)

    draw.text((right_x, y), "ETHIOPIA ADDRESS", font=font(18, True), fill=CORAL)
    for i, line in enumerate(["Gabon Street Woreda 02,", "House no. 359", "Addis Ababa, 7202, Ethiopia"]):
        draw.text((right_x, y + 36 + i * 36), line, font=font(27, True), fill=INK)
    draw.text((right_x, y + 169), "GERMANY ADDRESS", font=font(18, True), fill=CORAL)
    for i, line in enumerate(["Baden-Württemberg, Stuttgart,", "Germany"]):
        draw.text((right_x, y + 205 + i * 36), line, font=font(27, True), fill=INK)
    draw_rule(draw, 126, 663, 976, coral_x=126)
    return img.convert("RGB")


def preview(front_img, back_img):
    canvas = Image.new("RGB", (2252, 843), PORCELAIN)
    canvas.paste(front_img.resize((1050, 706), Image.Resampling.LANCZOS), (60, 68))
    canvas.paste(back_img.resize((1050, 706), Image.Resampling.LANCZOS), (1142, 68))
    return canvas


def save_pdf(images, path):
    first, *rest = images
    first.save(path, "PDF", resolution=DPI, save_all=bool(rest), append_images=rest)


if __name__ == "__main__":
    front_img = front()
    back_img = back()
    front_path = OUT / "zemtab-business-card-porcelain-coral-open-back-v3-front.png"
    back_path = OUT / "zemtab-business-card-porcelain-coral-open-back-v3-back.png"
    preview_path = OUT / "zemtab-business-card-porcelain-coral-open-back-v3-preview.png"
    front_img.save(front_path, dpi=(DPI, DPI))
    back_img.save(back_path, dpi=(DPI, DPI))
    preview(front_img, back_img).save(preview_path, dpi=(DPI, DPI))
    save_pdf([front_img], OUT / "zemtab-business-card-porcelain-coral-open-back-v3-front.pdf")
    save_pdf([back_img], OUT / "zemtab-business-card-porcelain-coral-open-back-v3-back.pdf")
    save_pdf([front_img, back_img], OUT / "zemtab-business-card-porcelain-coral-open-back-v3-front-back.pdf")
