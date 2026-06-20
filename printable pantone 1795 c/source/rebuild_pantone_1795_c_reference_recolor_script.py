from pathlib import Path
from PIL import Image
import numpy as np

ROOT = Path(__file__).resolve().parents[2]
BASE = ROOT / 'printable'
OUT = Path(__file__).resolve().parent.parent
ASSETS = Path(__file__).resolve().parent / 'assets'
DPI = (2600, 2600)
ORANGE_RGB = np.array([255, 130, 0], dtype=np.float32)
TEAL_CMYK = np.array([217, 0, 115, 38], dtype=np.float32)  # C85 M0 Y45 K15 in 8-bit CMYK.
WHITE_RGB = np.array([255, 255, 255], dtype=np.float32)
WHITE_CMYK = np.array([0, 0, 0, 0], dtype=np.float32)


def orange_strength(rgb):
    arr = rgb.astype(np.float32)
    r, g, b = arr[..., 0], arr[..., 1], arr[..., 2]
    orange_like = (r > 170) & (g > 55) & (g < 235) & (b < 190) & (r > b + 35) & (g > b + 20)
    denom = np.mean(WHITE_RGB - ORANGE_RGB)
    strength = np.clip(np.mean(WHITE_RGB - arr, axis=-1) / denom, 0.0, 1.0)
    return np.where(orange_like, strength, 0.0)


def recolor_to_teal(path):
    image = Image.open(path)
    rgb = np.array(image.convert('RGB'))
    cmyk = np.array(image.convert('CMYK')).astype(np.float32)
    strength = orange_strength(rgb)
    mask = strength > 0
    teal = WHITE_CMYK * (1.0 - strength[..., None]) + TEAL_CMYK * strength[..., None]
    cmyk[mask] = teal[mask]
    return Image.fromarray(np.clip(cmyk, 0, 255).astype(np.uint8), 'CMYK')


def main():
    OUT.mkdir(exist_ok=True)
    ASSETS.mkdir(parents=True, exist_ok=True)
    front = recolor_to_teal(BASE / 'zemtab-front-CMYK-2600dpi.tif')
    back = recolor_to_teal(BASE / 'zemtab-back-CMYK-2600dpi.tif')

    front.save(OUT / 'zemtab-front-emerald-teal-CMYK-2600dpi.tif', dpi=DPI, compression='tiff_lzw')
    back.save(OUT / 'zemtab-back-emerald-teal-CMYK-2600dpi.tif', dpi=DPI, compression='tiff_lzw')
    front.save(OUT / 'zemtab-front-back-emerald-teal-CMYK-2600dpi.tif', dpi=DPI, compression='tiff_lzw', save_all=True, append_images=[back])
    front.save(OUT / 'zemtab-front-emerald-teal-CMYK-2600dpi.pdf', 'PDF', resolution=DPI[0])
    back.save(OUT / 'zemtab-back-emerald-teal-CMYK-2600dpi.pdf', 'PDF', resolution=DPI[0])
    front.save(OUT / 'zemtab-front-back-emerald-teal-CMYK-2600dpi.pdf', 'PDF', resolution=DPI[0], save_all=True, append_images=[back])

    front_rgb = front.convert('RGB')
    back_rgb = back.convert('RGB')
    front_rgb.save(OUT / 'RGB-preview-front-emerald-teal.png')
    back_rgb.save(OUT / 'RGB-preview-back-emerald-teal.png')
    gap = 180
    combo = Image.new('RGB', (front_rgb.width * 2 + gap, front_rgb.height), (255, 255, 255))
    combo.paste(front_rgb, (0, 0))
    combo.paste(back_rgb, (front_rgb.width + gap, 0))
    combo.save(OUT / 'RGB-preview-front-back-emerald-teal.png')

    front.save(ASSETS / 'zemtab-front-emerald-teal-source-CMYK-2600dpi.tif', dpi=DPI, compression='tiff_lzw')
    back.save(ASSETS / 'zemtab-back-emerald-teal-source-CMYK-2600dpi.tif', dpi=DPI, compression='tiff_lzw')
    print(OUT)


if __name__ == '__main__':
    main()
