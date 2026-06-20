from pathlib import Path

import numpy as np
import cv2
import qrcode
from PIL import Image, ImageDraw, ImageFilter


ROOT = Path(r"C:\Users\mudim\Music\ZemTab")
MAIN_DIR = ROOT / "black orange pantone 1375 print"
DONOR_DIR = ROOT / "copy paste"
OUT_DIR = ROOT / "right side copy paste"
SOURCE_DIR = OUT_DIR / "source"

MAIN_FRONT = MAIN_DIR / "ZemTab-black-orange-pantone-1375-front-CMYK-2600dpi.tif"
MAIN_BACK = MAIN_DIR / "ZemTab-black-orange-pantone-1375-back-CMYK-2600dpi.tif"
DONOR_FRONT = DONOR_DIR / "zemtab-business-card-mega-final-front.png"
ICON_LOGO = MAIN_DIR / "zemtab-black-orange-pantone-1375-icon-transparent.png"

EXPECTED_SIZE = (9351, 6283)
DPI = (2600, 2600)
ORANGE_RGB = np.array([255, 130, 0], dtype=np.float32)
WHITE_RGB = np.array([255, 255, 255], dtype=np.float32)
QR_PAYLOAD = "https://zemtab.com"
QR_CENTER = (7575, 3025)
QR_MODULE_SIZE = 76


def antialias_orange_frames(donor_arr):
    # Soften only the existing orange frame edges. This avoids redrawing the
    # rounded squares, which can create doubled corner lines.
    r, g, b = donor_arr[..., 0], donor_arr[..., 1], donor_arr[..., 2]
    frame_zone = np.zeros(donor_arr.shape[:2], dtype=bool)
    frame_zone[1350:5100, 5750:9280] = True
    orange = frame_zone & (r > 210) & (g > 70) & (g < 180) & (b < 70)

    image = Image.fromarray(donor_arr, "RGB")
    blur = image.filter(ImageFilter.GaussianBlur(0.42))
    mask = Image.fromarray((orange.astype(np.uint8) * 255), "L")
    mask = mask.filter(ImageFilter.MaxFilter(5)).filter(ImageFilter.GaussianBlur(0.35))
    image.paste(blur, (0, 0), mask)
    return np.array(image)


def draw_clean_qr_frames(image):
    # Draw the QR frames from vector-like masks at oversized resolution, then
    # downsample. This avoids inheriting jagged pixels from the donor card.
    scale = 4
    frame_mask = Image.new("L", (image.width * scale, image.height * scale), 0)
    d = ImageDraw.Draw(frame_mask)

    frames = [
        ((5900, 1350, 9250, 4700), 400, 46),
        ((6200, 1650, 8950, 4400), 330, 42),
    ]
    for box, radius, width in frames:
        scaled_box = tuple(v * scale for v in box)
        d.rounded_rectangle(
            scaled_box,
            radius=radius * scale,
            outline=255,
            width=width * scale,
        )

    frame_mask = frame_mask.resize(image.size, Image.Resampling.LANCZOS)
    orange_layer = Image.new("RGB", image.size, tuple(ORANGE_RGB.astype(np.uint8)))
    image.paste(orange_layer, (0, 0), frame_mask)
    return image


def draw_right_side_background(image, back_rgb):
    d = ImageDraw.Draw(image, "RGBA")

    # Colors sampled from the back card's right-side pillars.
    peach = (255, 208, 158, 255)
    orange = (255, 177, 95, 255)

    # Straight, constant-width diagonal bands matching the back-card direction.
    # They extend beyond the canvas so the visible edge never tapers.
    d.polygon(
        [(6500, -350), (10950, -350), (12450, 6630), (8000, 6630)],
        fill=peach,
    )
    d.polygon(
        [(5900, -350), (7000, -350), (8500, 6630), (7400, 6630)],
        fill=orange,
    )
    return image


def draw_clean_qr_code(image, back_rgb):
    qr = qrcode.QRCode(
        error_correction=qrcode.constants.ERROR_CORRECT_H,
        border=4,
        box_size=1,
    )
    qr.add_data(QR_PAYLOAD)
    qr.make(fit=True)

    qr_image = qr.make_image(fill_color="black", back_color="white").convert("RGB")
    qr_size = qr_image.width * QR_MODULE_SIZE
    qr_image = qr_image.resize((qr_size, qr_size), Image.Resampling.NEAREST)

    # Clear old raster QR/frame remnants, including the third outside square
    # inherited from the donor artwork, then paste a freshly generated tile.
    image.paste((255, 255, 255), (5500, 1050, 9350, 5200))
    image = draw_right_side_background(image, back_rgb)
    ImageDraw.Draw(image).rounded_rectangle(
        (6240, 1690, 8925, 4385),
        radius=285,
        fill=(255, 255, 255),
    )
    qr_left = QR_CENTER[0] - qr_size // 2
    qr_top = QR_CENTER[1] - qr_size // 2
    qr_layer = Image.new("RGBA", image.size, (255, 255, 255, 0))
    qr_layer.paste(qr_image.convert("RGBA"), (qr_left, qr_top))
    tile_mask = Image.new("L", image.size, 0)
    ImageDraw.Draw(tile_mask).rounded_rectangle(
        (6240, 1690, 8925, 4385),
        radius=285,
        fill=255,
    )
    image.paste(qr_layer.convert("RGB"), (0, 0), tile_mask)

    logo = Image.open(ICON_LOGO).convert("RGBA")
    logo_width = 610
    logo_height = round(logo.height * (logo_width / logo.width))
    logo = logo.resize((logo_width, logo_height), Image.Resampling.LANCZOS)
    logo_left = QR_CENTER[0] - logo_width // 2
    logo_top = QR_CENTER[1] - logo_height // 2

    pad = 760
    pad_box = (
        QR_CENTER[0] - pad // 2,
        QR_CENTER[1] - pad // 2,
        QR_CENTER[0] + pad // 2,
        QR_CENTER[1] + pad // 2,
    )
    ImageDraw.Draw(image).rounded_rectangle(pad_box, radius=90, fill=(255, 255, 255))
    image.paste(logo, (logo_left, logo_top), logo)
    return image


def load_main(path):
    image = Image.open(path)
    if image.mode != "CMYK":
        raise ValueError(f"Expected CMYK source: {path}")
    if image.size != EXPECTED_SIZE:
        raise ValueError(f"Expected {EXPECTED_SIZE}, got {image.size}: {path}")
    return image


def transplant_right_side(main_front, main_back):
    base = main_front.convert("RGB")
    back_bg = np.array(main_back.convert("RGB"))
    donor = Image.open(DONOR_FRONT).convert("RGB")
    donor = donor.resize(EXPECTED_SIZE, Image.Resampling.LANCZOS)

    base_arr = np.array(base)
    donor_arr = np.array(donor)

    h, w = EXPECTED_SIZE[1], EXPECTED_SIZE[0]

    # Remove the old donor's pink/gray background hue from the transplanted
    # side and remap the decorative red accents into the orange theme.
    donor_f = donor_arr.astype(np.float32)
    r, g, b = donor_arr[..., 0], donor_arr[..., 1], donor_arr[..., 2]
    dark_detail = (r < 80) & (g < 80) & (b < 80)
    red_accent = (r > 150) & (r > g + 18) & (r > b + 18)
    source_red = np.array([238, 61, 74], dtype=np.float32)
    strength = np.clip(
        np.mean(WHITE_RGB - donor_f, axis=-1) / np.mean(WHITE_RGB - source_red),
        0.0,
        1.0,
    )
    strong_red_accent = red_accent & (strength > 0.22)
    pale_red_wash = red_accent & ~strong_red_accent
    orange_blend = (WHITE_RGB * (1.0 - strength[..., None]) + ORANGE_RGB * strength[..., None]).clip(0, 255).astype(np.uint8)
    donor_arr[~(dark_detail | red_accent)] = np.array([255, 255, 255], dtype=np.uint8)
    donor_arr[pale_red_wash] = np.array([255, 255, 255], dtype=np.uint8)
    donor_arr[strong_red_accent] = orange_blend[strong_red_accent]

    # Clean only the weak orange ghosting around the QR frame area. Strong
    # orange pixels are the actual rounded-square strokes and should remain.
    qr_frame_zone = np.zeros((h, w), dtype=bool)
    qr_frame_zone[1550:5000, 5850:9250] = True
    weak_orange_ghost = qr_frame_zone & red_accent & (strength < 0.52) & ~dark_detail
    donor_arr[weak_orange_ghost] = np.array([255, 255, 255], dtype=np.uint8)
    # The copied frame pixels are visibly jagged and slightly warped. Keep the
    # QR itself from the donor, but redraw the decorative frames from scratch.
    donor_arr[qr_frame_zone & red_accent & ~dark_detail] = np.array([255, 255, 255], dtype=np.uint8)

    out = base_arr.copy()
    right_side = np.zeros((h, w), dtype=bool)
    right_side[:, 5600:w] = True
    out[right_side] = np.array([255, 255, 255], dtype=np.uint8)

    # Add only the back design's orange diagonal bands behind the QR, avoiding
    # the contact text and information box.
    br, bg, bb = back_bg[..., 0], back_bg[..., 1], back_bg[..., 2]
    back_orange = right_side & (br > 220) & (bg > 110) & (bb < 190)
    labels_count, labels, stats, _ = cv2.connectedComponentsWithStats(back_orange.astype(np.uint8), 8)
    back_bands = np.zeros((h, w), dtype=bool)
    for idx in range(1, labels_count):
        x, y, width, height, area = stats[idx]
        if area > 50000 and x > 6200:
            back_bands |= labels == idx
    out[back_bands] = back_bg[back_bands]

    # Keep the copied QR/frame design on top of those background bands.
    rr, gg, bb = donor_arr[..., 0], donor_arr[..., 1], donor_arr[..., 2]
    dark_qr = right_side & (rr < 80) & (gg < 80) & (bb < 80)
    orange_frame = right_side & (rr > 210) & (gg > 70) & (gg < 180) & (bb < 70)
    qr_tile = np.zeros((h, w), dtype=bool)
    qr_tile[1932:4282, 6318:8667] = True
    qr_tile &= right_side
    foreground = dark_qr | orange_frame | qr_tile
    out[foreground] = donor_arr[foreground]
    image = Image.fromarray(out, "RGB")
    image = draw_clean_qr_code(image, Image.fromarray(back_bg, "RGB"))
    image = draw_clean_qr_frames(image)
    return image.convert("CMYK")


def save_outputs(front, back):
    OUT_DIR.mkdir(parents=True, exist_ok=True)
    SOURCE_DIR.mkdir(parents=True, exist_ok=True)

    front_tif = OUT_DIR / "front-CMYK-2600dpi.tif"
    back_tif = OUT_DIR / "back-CMYK-2600dpi.tif"
    front_pdf = OUT_DIR / "front-CMYK-2600dpi.pdf"
    back_pdf = OUT_DIR / "back-CMYK-2600dpi.pdf"
    combo_pdf = OUT_DIR / "front-back-CMYK-2600dpi.pdf"

    def save_replace(image, path, *args, **kwargs):
        temp = path.with_name(f"{path.stem}.tmp{path.suffix}")
        suffix = 2
        while temp.exists():
            temp = path.with_name(f"{path.stem}.tmp-{suffix}{path.suffix}")
            suffix += 1
        image.save(temp, *args, **kwargs)
        try:
            temp.replace(path)
        except PermissionError:
            fallback = path.with_name(f"{path.stem}-no-third-square-{len(path.stem)}{path.suffix}")
            suffix = 2
            while fallback.exists():
                fallback = path.with_name(f"{path.stem}-no-third-square-{suffix}{path.suffix}")
                suffix += 1
            temp.replace(fallback)
            print(f"Locked, wrote fallback: {fallback}")

    save_replace(front, front_tif, dpi=DPI, compression="tiff_lzw")
    save_replace(back, back_tif, dpi=DPI, compression="tiff_lzw")
    save_replace(front, front_pdf, "PDF", resolution=DPI[0])
    save_replace(back, back_pdf, "PDF", resolution=DPI[0])
    save_replace(front, combo_pdf, "PDF", resolution=DPI[0], save_all=True, append_images=[back])

    front.convert("RGB").save(OUT_DIR / "RGB-preview-front.png")
    back.convert("RGB").save(OUT_DIR / "RGB-preview-back.png")

    save_replace(front, SOURCE_DIR / "right-side-copy-paste-front-CMYK-2600dpi.tif", dpi=DPI, compression="tiff_lzw")
    save_replace(back, SOURCE_DIR / "right-side-copy-paste-back-CMYK-2600dpi.tif", dpi=DPI, compression="tiff_lzw")


def main():
    back = load_main(MAIN_BACK)
    front = transplant_right_side(load_main(MAIN_FRONT), back)
    save_outputs(front, back)
    print(OUT_DIR)


if __name__ == "__main__":
    main()
