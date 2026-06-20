from pathlib import Path
import os
from PIL import Image, ImageDraw, ImageFilter, ImageFont
import numpy as np
import cv2

ROOT = Path(__file__).resolve().parents[2]
BASE = ROOT / 'printable'
STYLE = os.environ.get('ZEMTAB_BAND_STYLE', 'tonal-red')
PATTERN = os.environ.get('ZEMTAB_BACKGROUND_PATTERN', 'lines')
FRONT_LAYOUT = os.environ.get('ZEMTAB_FRONT_LAYOUT', 'standard')
PATTERN_FOLDERS = {
    'qr-modules': '01 Subtle QR Modules',
    'service-nodes': '02 Connected Service Nodes',
    'z-marks': '03 Faint Z Marks',
    'square-ripple': '04 Squared Digital Ripple',
    'topo-centered': '05 Centered Topographic Contours',
    'topo-islands': '06 Topographic Islands',
    'topo-flow': '07 Flowing Square Topography',
}
STYLES = {
    'tonal-red': {
        'folder': '01 tonal red',
        'first_cmyk': [0, 238, 224, 48],
        'first_opacity': 0.85,
        'second_cmyk': [0, 245, 237, 5],
        'second_opacity': 0.60,
        'frame_rgb': (250, 10, 18),
    },
    'burgundy': {
        'folder': '02 burgundy',
        'first_cmyk': [0, 220, 178, 100],
        'first_opacity': 0.82,
        'second_cmyk': [0, 225, 205, 35],
        'second_opacity': 0.65,
        'frame_rgb': (112, 22, 38),
    },
    'warm-charcoal': {
        'folder': '03 warm red charcoal qr',
        'first_cmyk': [0, 245, 237, 5],
        'first_opacity': 0.80,
        'second_cmyk': [0, 205, 190, 25],
        'second_opacity': 0.58,
        'frame_rgb': (42, 42, 46),
    },
    'deep-ruby': {
        'folder': '04 deep ruby bands',
        'first_cmyk': [0, 235, 210, 78],
        'first_opacity': 0.90,
        'second_cmyk': [0, 225, 205, 48],
        'second_opacity': 0.72,
        'frame_rgb': (232, 20, 31),
    },
    'light-bands-dark-qr': {
        'folder': '05 light bands deep red qr',
        'first_cmyk': [0, 245, 237, 5],
        'first_opacity': 0.68,
        'second_cmyk': [0, 235, 220, 10],
        'second_opacity': 0.48,
        'frame_rgb': (164, 24, 38),
    },
    'balanced-crimson': {
        'folder': '06 balanced crimson',
        'first_cmyk': [0, 240, 225, 42],
        'first_opacity': 0.82,
        'second_cmyk': [0, 235, 220, 20],
        'second_opacity': 0.62,
        'frame_rgb': (205, 30, 43),
    },
    'balanced-crimson-final': {
        'folder': 'Balanced Crimson Final Print Ready',
        'first_cmyk': [0, 245, 237, 5],
        'first_opacity': 0.54,
        'second_cmyk': [0, 245, 237, 5],
        'second_opacity': 0.36,
        'frame_rgb': (250, 10, 18),
    },
    'wine-coral': {
        'folder': '07 wine and coral',
        'first_cmyk': [0, 218, 178, 105],
        'first_opacity': 0.88,
        'second_cmyk': [0, 198, 180, 12],
        'second_opacity': 0.64,
        'frame_rgb': (239, 32, 42),
    },
    'bright-soft': {
        'folder': '08 bright qr soft bands',
        'first_cmyk': [0, 245, 237, 5],
        'first_opacity': 0.74,
        'second_cmyk': [0, 230, 215, 5],
        'second_opacity': 0.52,
        'frame_rgb': (255, 15, 24),
    },
}
STYLE_CONFIG = STYLES[STYLE]
OUT = (
    ROOT / 'ZemTab Alternative Background Concepts' / '10 Squared Ripple Clean Headline'
    if PATTERN == 'square-ripple' and FRONT_LAYOUT == 'headline-services'
    else
    ROOT / 'ZemTab Alternative Background Concepts' / '09 Squared Ripple Stacked Services'
    if PATTERN == 'square-ripple' and FRONT_LAYOUT == 'stacked-services'
    else
    ROOT / 'ZemTab Alternative Background Concepts' / '08 Squared Ripple Simplified CTA'
    if PATTERN == 'square-ripple' and FRONT_LAYOUT == 'simplified-cta'
    else
    ROOT / 'ZemTab Alternative Background Concepts' / PATTERN_FOLDERS[PATTERN]
    if PATTERN in PATTERN_FOLDERS
    else ROOT / 'ZemTab FINAL CMYK RGB 2600dpi' / 'SKIBIDI FINAL CONTINUOUS' / 'Continuous Back Final'
    if STYLE == 'balanced-crimson-final'
    else ROOT / 'red band design concepts' / STYLE_CONFIG['folder']
)
ASSETS = Path(__file__).resolve().parent / 'assets'
DPI = (2600, 2600)
ORANGE_RGB = np.array([255, 130, 0], dtype=np.float32)
RED_RGB = np.array([210, 38, 48], dtype=np.float32)  # Pantone 1795 C common screen approximation.
RED_CMYK = np.array([0, 245, 237, 5], dtype=np.float32)  # Approx C0 M96 Y93 K2 in 8-bit CMYK.
FIRST_BAND_CMYK = np.array(STYLE_CONFIG['first_cmyk'], dtype=np.float32)
SECOND_BAND_CMYK = np.array(STYLE_CONFIG['second_cmyk'], dtype=np.float32)
WHITE_RGB = np.array([255, 255, 255], dtype=np.float32)
WHITE_CMYK = np.array([0, 0, 0, 0], dtype=np.float32)


def orange_strength(rgb):
    arr = rgb.astype(np.float32)
    r, g, b = arr[..., 0], arr[..., 1], arr[..., 2]
    orange_like = (r > 170) & (g > 55) & (g < 235) & (b < 190) & (r > b + 35) & (g > b + 20)
    denom = np.mean(WHITE_RGB - ORANGE_RGB)
    strength = np.clip(np.mean(WHITE_RGB - arr, axis=-1) / denom, 0.0, 1.0)
    return np.where(orange_like, strength, 0.0)


def recolor_to_red(path):
    image = Image.open(path)
    rgb = np.array(image.convert('RGB'))
    cmyk = np.array(image.convert('CMYK')).astype(np.float32)
    strength = orange_strength(rgb)

    # The large decorative side bands need to read as red, rather than pink.
    # Pull their two original tones closer to the ZemTab logo red while leaving
    # the rest of the artwork (logos, rules and text) unchanged. Apply the
    # adjustment by source-band pixels—not a fixed x cutoff—so no third-colour
    # seam can appear where the two diagonal bands pass behind the QR frame.
    h, w = strength.shape
    side_bands = np.zeros((h, w), dtype=bool)
    side_bands[:, int(w * 0.60):] = strength[:, int(w * 0.60):] > 0
    # Set the two source bands to explicit logo-red opacity levels. The source
    # artwork has stable strengths of about 0.63 for the first/darker band and
    # 0.38 for the second/lighter band. Fully saturated details such as the QR
    # frame remain untouched.
    decorative_bands = side_bands & (strength < 0.80)
    first_band = decorative_bands & (strength >= 0.50)
    second_band = decorative_bands & (strength < 0.50)
    strength[first_band] = STYLE_CONFIG['first_opacity']
    strength[second_band] = STYLE_CONFIG['second_opacity']

    mask = strength > 0
    target_red = np.broadcast_to(RED_CMYK, cmyk.shape).copy()
    target_red[first_band] = FIRST_BAND_CMYK
    target_red[second_band] = SECOND_BAND_CMYK
    red = WHITE_CMYK * (1.0 - strength[..., None]) + target_red * strength[..., None]
    cmyk[mask] = red[mask]
    return Image.fromarray(np.clip(cmyk, 0, 255).astype(np.uint8), 'CMYK')


def strengthen_background_lines(image, side):
    rgb = image.convert('RGB')
    arr = np.array(rgb)
    original_arr = arr.copy()
    h, w = arr.shape[:2]

    # Remove the two legacy line treatments from otherwise white background
    # pixels before drawing the single final print pattern. Protected artwork
    # regions are never touched by this cleanup.
    pale_neutral = (
        (arr[..., 0] > 190) & (arr[..., 1] > 190) & (arr[..., 2] > 190) &
        ((arr.max(axis=-1) - arr.min(axis=-1)) < 38) &
        (arr.mean(axis=-1) < 253)
    )

    # Preserve genuine long horizontal design rules while removing diagonal
    # fragments from every exposed white area. A horizontal morphology test
    # distinguishes the rules without relying on broad rectangular masks.
    horizontal_details = cv2.morphologyEx(
        pale_neutral.astype(np.uint8),
        cv2.MORPH_OPEN,
        np.ones((1, 101), dtype=np.uint8),
    ).astype(bool)

    structural_panel = np.zeros((h, w), dtype=bool)
    if side == 'front':
        structural_panel[1300:4750, 6100:9050] = True
    else:
        panel_shape = Image.new('L', (w, h), 0)
        ImageDraw.Draw(panel_shape).rounded_rectangle(
            (690, 2420, 8820, 5760),
            radius=250,
            fill=255,
        )
        structural_panel = np.array(panel_shape) > 0
    if side == 'back':
        # Back is rebuilt from a completely line-free pale background. Do not
        # restore any source pixels: that was the cause of the isolated legacy
        # fragment beside the phone section.
        legacy_neutral = (
            (arr.min(axis=-1) > 155) &
            ((arr.max(axis=-1) - arr.min(axis=-1)) < 45) &
            (arr.mean(axis=-1) < 253)
        )
        arr[legacy_neutral] = (255, 255, 255)
        rebuilt = Image.fromarray(arr, 'RGB')
        rebuilt_draw = ImageDraw.Draw(rebuilt)
        pale_rule = (217, 225, 222)
        rebuilt_draw.line((3180, 2085, 5850, 2085), fill=pale_rule, width=16)
        rebuilt_draw.line((2730, 5840, 5850, 5840), fill=pale_rule, width=16)
        rebuilt_draw.rounded_rectangle(
            (690, 2420, 8820, 5760),
            radius=250,
            outline=pale_rule,
            width=16,
        )
        arr = np.array(rebuilt)
    else:
        legacy_neutral = (
            (arr.min(axis=-1) > 155) &
            ((arr.max(axis=-1) - arr.min(axis=-1)) < 45) &
            (arr.mean(axis=-1) < 253)
        )
        cleanup = legacy_neutral & ~horizontal_details
        arr[cleanup] = (255, 255, 255)

    rgb = Image.fromarray(arr, 'RGB')

    layer = Image.new('RGBA', (w, h), (0, 0, 0, 0))
    d = ImageDraw.Draw(layer)

    if PATTERN == 'qr-modules':
        for row, y in enumerate(range(180, h, 520)):
            for col, x in enumerate(range(180, w, 520)):
                if (col * 3 + row * 5) % 4 in (0, 1):
                    size = 92 if (col + row) % 3 else 126
                    d.rounded_rectangle(
                        (x, y, x + size, y + size),
                        radius=22,
                        outline=(177, 196, 192, 92),
                        width=12,
                    )
    elif PATTERN == 'service-nodes':
        step = 880
        nodes = []
        for row, y in enumerate(range(260, h, step)):
            for col, x in enumerate(range(260, w, step)):
                if (row + col) % 3 != 2:
                    nodes.append((x, y, row, col))
                    d.ellipse((x - 22, y - 22, x + 22, y + 22), fill=(174, 194, 190, 104))
        node_lookup = {(r, c): (x, y) for x, y, r, c in nodes}
        for x, y, row, col in nodes:
            target = node_lookup.get((row, col + 1)) or node_lookup.get((row + 1, col))
            if target:
                tx, ty = target
                mid_x = (x + tx) // 2
                d.line((x, y, mid_x, y, mid_x, ty, tx, ty), fill=(174, 194, 190, 76), width=10)
    elif PATTERN == 'z-marks':
        for row, y in enumerate(range(180, h, 760)):
            for col, x in enumerate(range(180, w, 900)):
                if (row + col) % 2 == 0:
                    size = 190
                    d.line((x, y, x + size, y, x, y + size, x + size, y + size), fill=(180, 198, 194, 72), width=14)
    elif PATTERN == 'square-ripple':
        if side == 'front':
            # Start as an even 300 px echo around the outer QR frame, matching
            # the spacing between the inner and outer red frames, then
            # expand in matching rounded-square steps across the card.
            first_box = (5600, 1050, 9550, 5000)
            for index in range(9):
                expansion = index * 700
                d.rounded_rectangle(
                    (
                        first_box[0] - expansion,
                        first_box[1] - expansion,
                        first_box[2] + expansion,
                        first_box[3] + expansion,
                    ),
                    radius=470 + index * 80,
                    outline=(158, 183, 178, 175),
                    width=15,
                )
        else:
            cx, cy = (7350, 3025)
            for radius in range(2490, 8400, 720):
                half_h = int(radius * 0.64)
                d.rounded_rectangle(
                    (cx - radius, cy - half_h, cx + radius, cy + half_h),
                    radius=260,
                    outline=(158, 183, 178, 175),
                    width=15,
                )
    elif PATTERN == 'topo-centered':
        cx, cy = ((6900, 3100) if side == 'front' else (7000, 3100))
        for index, radius in enumerate(range(700, 7600, 430)):
            offset_x = ((index % 5) - 2) * 42
            offset_y = (((index * 3) % 7) - 3) * 28
            half_h = int(radius * (0.56 + (index % 3) * 0.025))
            d.rounded_rectangle(
                (cx - radius + offset_x, cy - half_h + offset_y,
                 cx + radius + offset_x, cy + half_h + offset_y),
                radius=230 + (index % 4) * 24,
                outline=(164, 187, 182, 132),
                width=17,
            )
    elif PATTERN == 'topo-islands':
        centers = [(1550, 1450), (4550, 4300), (7900, 1700), (8000, 5300)]
        if side == 'back':
            centers = [(1350, 1350), (4300, 4700), (7350, 1450), (8200, 5050)]
        for island, (cx, cy) in enumerate(centers):
            for index, radius in enumerate(range(300, 1850, 280)):
                dx = ((index * 37 + island * 61) % 150) - 75
                dy = ((index * 53 + island * 29) % 120) - 60
                half_h = int(radius * 0.72)
                d.rounded_rectangle(
                    (cx - radius + dx, cy - half_h + dy,
                     cx + radius + dx, cy + half_h + dy),
                    radius=180 + index * 16,
                    outline=(162, 186, 181, 126),
                    width=17,
                )
    elif PATTERN == 'topo-flow':
        for index, radius in enumerate(range(900, 9000, 470)):
            cx = 5600 + ((index % 6) - 3) * 150
            cy = 3150 + (((index * 2) % 7) - 3) * 95
            half_h = int(radius * 0.48)
            d.rounded_rectangle(
                (cx - radius, cy - half_h, cx + radius, cy + half_h),
                radius=300 + (index % 5) * 38,
                outline=(160, 185, 180, 138),
                width=18,
            )
    else:
        # One consistent top-to-bottom diagonal rhythm, dark enough to survive
        # print but still subordinate to the content.
        line_start = 100 if side == 'back' else -900
        line_slant = 900 if side == 'back' else 1180
        for x in range(line_start, w + 1400, 1120):
            d.line((x + line_slant, -220, x, h + 220), fill=(180, 197, 193, 164), width=16)

    clean_arr = np.array(rgb)

    # Continuous lines remain aligned from top to bottom. Near exposed text
    # they fade to half strength with a soft 120 px transition instead of
    # stopping or creating rectangular holes.
    fade = Image.new('L', (w, h), 255)
    fade_draw = ImageDraw.Draw(fade)
    if side == 'front':
        fade_draw.rectangle((390, 520, 5100, 2720), fill=128)    # Logo lockup
        fade_draw.rectangle((570, 3260, 5100, 4470), fill=128)   # Main heading
        fade_draw.rectangle((600, 4720, 5250, 5480), fill=128)   # Subtitle
        fade_draw.rectangle((1400, 5360, 4350, 6210), fill=128)  # CTA
    else:
        fade_draw.rectangle((560, 580, 5100, 1840), fill=128)    # Contact heading
        fade_draw.rectangle((560, 1880, 7900, 2320), fill=128)   # Exposed accents
    fade = fade.filter(ImageFilter.GaussianBlur(120))

    mask = (
        (clean_arr[..., 0] > 248) &
        (clean_arr[..., 1] > 248) &
        (clean_arr[..., 2] > 248) &
        ~structural_panel
    )

    alpha = np.array(layer)[..., 3].astype(np.float32)
    alpha *= np.array(fade, dtype=np.float32) / 255.0
    if PATTERN == 'square-ripple':
        # Smooth right-to-left print gradient: a trace remains at the left,
        # builds through the centre, and holds at full strength on the right.
        x = np.linspace(0.0, 1.0, w, dtype=np.float32)
        horizontal_strength = np.interp(
            x,
            [0.0, 0.20, 0.75, 1.0],
            [35.0 / 175.0, 0.30, 1.0, 1.0],
        )
        alpha *= horizontal_strength[None, :]
    final_mask = Image.fromarray(((mask & (alpha > 0)).astype(np.uint8) * 255), 'L')
    final_alpha = Image.fromarray(np.where(mask, alpha, 0).astype(np.uint8), 'L')
    rgb.paste(layer.convert('RGB'), (0, 0), final_alpha)
    return rgb.convert('CMYK')


def make_qr_frames_red(image):
    """Redraw the two decorative QR squares in the vivid logo red."""
    rgb = image.convert('RGB')
    d = ImageDraw.Draw(rgb)
    frame_color = STYLE_CONFIG['frame_rgb']
    d.rounded_rectangle((5900, 1350, 9250, 4700), radius=400, outline=frame_color, width=46)
    d.rounded_rectangle((6200, 1650, 8950, 4400), radius=330, outline=frame_color, width=42)
    return rgb.convert('CMYK')


def simplify_front_cta(image, stacked_services=False, headline_services=False):
    """Remove the headline and promote the service line and CTA button."""
    rgb = image.convert('RGB')
    arr = np.array(rgb)

    def foreground_cutout(box, darkness_limit):
        x0, y0, x1, y1 = box
        crop_arr = arr[y0:y1, x0:x1].copy()
        mean = crop_arr.mean(axis=-1)
        alpha = np.where(
            mean < darkness_limit,
            np.clip((darkness_limit - mean) * (255.0 / 55.0), 0, 255),
            0,
        ).astype(np.uint8)
        return Image.fromarray(np.dstack([crop_arr, alpha]), 'RGBA')

    # End above the original CTA button; the wider crop previously captured
    # its top edge and produced a duplicated black pill after scaling.
    subtitle = foreground_cutout((650, 4740, 5300, 5310), 180)
    button = foreground_cutout((1350, 5310, 4400, 6270), 150)

    # Clear the former headline, subtitle and button. The selected background
    # pattern is subsequently drawn through this newly opened white space.
    ImageDraw.Draw(rgb).rectangle((500, 3150, 5450, 6283), fill=(255, 255, 255))

    if not stacked_services and not headline_services:
        subtitle = subtitle.resize(
            (round(subtitle.width * 1.20), round(subtitle.height * 1.20)),
            Image.Resampling.LANCZOS,
        )
    button = button.resize(
        (round(button.width * 1.20), round(button.height * 1.20)),
        Image.Resampling.LANCZOS,
    )

    button_x = 2850 - button.width // 2
    if headline_services:
        text_draw = ImageDraw.Draw(rgb)
        headline_font = ImageFont.truetype(r'C:\Windows\Fonts\arialbd.ttf', 390)
        service_font = ImageFont.truetype(r'C:\Windows\Fonts\arial.ttf', 188)
        text_draw.text((800, 3490), 'Scan. Order. Pay.', font=headline_font, fill=(0, 0, 0))
        text_draw.text(
            (820, 4170),
            'QR menu  ·  Table ordering  ·  Room service',
            font=service_font,
            fill=(128, 130, 139),
        )
    elif stacked_services:
        text_draw = ImageDraw.Draw(rgb)
        font = ImageFont.truetype(r'C:\Windows\Fonts\arial.ttf', 285)
        text_color = (128, 130, 139)
        for index, label in enumerate(('QR menu', 'Table ordering', 'Room service')):
            text_draw.text((900, 3370 + index * 470), label, font=font, fill=text_color)
        rgb.paste(button, (button_x, 5000), button)
    else:
        subtitle_x = 2850 - subtitle.width // 2
        rgb.paste(subtitle, (subtitle_x, 3570), subtitle)
        rgb.paste(button, (button_x, 4520), button)
    return rgb.convert('CMYK')


def main():
    OUT.mkdir(parents=True, exist_ok=True)
    ASSETS.mkdir(parents=True, exist_ok=True)
    front = recolor_to_red(BASE / 'zemtab-front-CMYK-2600dpi.tif')
    back = recolor_to_red(BASE / 'zemtab-back-CMYK-2600dpi.tif')
    if FRONT_LAYOUT in ('simplified-cta', 'stacked-services', 'headline-services'):
        front = simplify_front_cta(
            front,
            stacked_services=FRONT_LAYOUT == 'stacked-services',
            headline_services=FRONT_LAYOUT == 'headline-services',
        )
    front = strengthen_background_lines(front, 'front')
    back = strengthen_background_lines(back, 'back')
    front = make_qr_frames_red(front)

    def save_replace(image, path, *args, **kwargs):
        temp = path.with_name(f'{path.stem}.tmp{path.suffix}')
        suffix = 2
        while temp.exists():
            temp = path.with_name(f'{path.stem}.tmp-{suffix}{path.suffix}')
            suffix += 1
        image.save(temp, *args, **kwargs)
        try:
            temp.replace(path)
        except PermissionError:
            fallback = path.with_name(f'{path.stem}-strong-lines{path.suffix}')
            suffix = 2
            while fallback.exists():
                fallback = path.with_name(f'{path.stem}-strong-lines-{suffix}{path.suffix}')
                suffix += 1
            temp.replace(fallback)
            print(f'Locked, wrote fallback: {fallback}')

    save_replace(front, OUT / 'zemtab-front-pantone-1795-c-CMYK-2600dpi.tif', dpi=DPI, compression='tiff_lzw')
    save_replace(back, OUT / 'zemtab-back-pantone-1795-c-CMYK-2600dpi.tif', dpi=DPI, compression='tiff_lzw')
    save_replace(front, OUT / 'zemtab-front-back-pantone-1795-c-CMYK-2600dpi.tif', dpi=DPI, compression='tiff_lzw', save_all=True, append_images=[back])
    save_replace(front, OUT / 'zemtab-front-pantone-1795-c-CMYK-2600dpi.pdf', 'PDF', resolution=DPI[0])
    save_replace(back, OUT / 'zemtab-back-pantone-1795-c-CMYK-2600dpi.pdf', 'PDF', resolution=DPI[0])
    save_replace(front, OUT / 'zemtab-front-back-pantone-1795-c-CMYK-2600dpi.pdf', 'PDF', resolution=DPI[0], save_all=True, append_images=[back])

    front_rgb = front.convert('RGB')
    back_rgb = back.convert('RGB')
    save_replace(front_rgb, OUT / 'zemtab-front-RGB-2600dpi.png', dpi=DPI)
    save_replace(back_rgb, OUT / 'zemtab-back-RGB-2600dpi.png', dpi=DPI)
    gap = 180
    combo = Image.new('RGB', (front_rgb.width * 2 + gap, front_rgb.height), (255, 255, 255))
    combo.paste(front_rgb, (0, 0))
    combo.paste(back_rgb, (front_rgb.width + gap, 0))
    save_replace(combo, OUT / 'zemtab-front-back-RGB-2600dpi.png', dpi=DPI)

    save_replace(front, ASSETS / 'zemtab-front-pantone-1795-c-source-CMYK-2600dpi.tif', dpi=DPI, compression='tiff_lzw')
    save_replace(back, ASSETS / 'zemtab-back-pantone-1795-c-source-CMYK-2600dpi.tif', dpi=DPI, compression='tiff_lzw')
    print(OUT)


if __name__ == '__main__':
    main()
