from pathlib import Path

from PIL import Image


SOURCE_DIR = Path(__file__).resolve().parent
PRINTABLE_DIR = SOURCE_DIR.parent
ASSET_DIR = SOURCE_DIR / "assets"

DPI = (2600, 2600)
FRONT = ASSET_DIR / "right-side-copy-paste-front-CMYK-2600dpi.tif"
BACK = ASSET_DIR / "right-side-copy-paste-back-CMYK-2600dpi.tif"


def load_cmyk(path):
    image = Image.open(path)
    if image.mode != "CMYK":
        raise ValueError(f"Expected CMYK image: {path}")
    return image


def save_rgb_previews(front, back):
    front_rgb = front.convert("RGB")
    back_rgb = back.convert("RGB")
    front_rgb.save(PRINTABLE_DIR / "RGB-preview-front.png")
    back_rgb.save(PRINTABLE_DIR / "RGB-preview-back.png")

    gap = 180
    combined = Image.new(
        "RGB",
        (front_rgb.width * 2 + gap, front_rgb.height),
        (255, 255, 255),
    )
    combined.paste(front_rgb, (0, 0))
    combined.paste(back_rgb, (front_rgb.width + gap, 0))
    combined.save(PRINTABLE_DIR / "RGB-preview-front-back.png")


def main():
    front = load_cmyk(FRONT)
    back = load_cmyk(BACK)

    front.save(PRINTABLE_DIR / "zemtab-front-CMYK-2600dpi.tif", dpi=DPI, compression="tiff_lzw")
    back.save(PRINTABLE_DIR / "zemtab-back-CMYK-2600dpi.tif", dpi=DPI, compression="tiff_lzw")
    front.save(
        PRINTABLE_DIR / "zemtab-front-back-CMYK-2600dpi.tif",
        dpi=DPI,
        compression="tiff_lzw",
        save_all=True,
        append_images=[back],
    )

    front.save(PRINTABLE_DIR / "zemtab-front-CMYK-2600dpi.pdf", "PDF", resolution=DPI[0])
    back.save(PRINTABLE_DIR / "zemtab-back-CMYK-2600dpi.pdf", "PDF", resolution=DPI[0])
    front.save(
        PRINTABLE_DIR / "zemtab-front-back-CMYK-2600dpi.pdf",
        "PDF",
        resolution=DPI[0],
        save_all=True,
        append_images=[back],
    )

    save_rgb_previews(front, back)
    print(PRINTABLE_DIR)


if __name__ == "__main__":
    main()
