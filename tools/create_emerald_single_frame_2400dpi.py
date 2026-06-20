from pathlib import Path
from PIL import Image, ImageDraw

ROOT = Path(r"C:\Users\mudim\Music\ZemTab")
SRC = ROOT / "cards" / "solid white recolors" / "option-10-black-emerald"
OUT = ROOT / "cards" / "solid white recolors" / "option-10-emerald-clean-2400dpi"

GREEN = (16, 185, 129)
INK = (17, 24, 39)
WHITE = (255, 255, 255)


def save_2400(im, path):
    hi = im.resize((im.width * 2, im.height * 2), Image.Resampling.LANCZOS)
    hi.save(path, dpi=(2400, 2400))
    hi.convert("RGB").save(path.with_suffix(".pdf"), resolution=2400)


def clean_front(src):
    im = Image.open(src).convert("RGB")
    d = ImageDraw.Draw(im)

    # Clear the original multi-frame QR area while keeping the QR itself intact.
    d.rounded_rectangle((2590, 600, 4270, 2265), radius=240, outline=WHITE, width=220)
    d.rounded_rectangle((2760, 740, 4145, 2115), radius=190, outline=WHITE, width=185)
    d.rounded_rectangle((2890, 860, 4025, 2010), radius=150, outline=WHITE, width=150)
    d.rectangle((3985, 720, 4316, 2175), fill=WHITE)
    d.rectangle((2490, 2140, 4316, 2355), fill=WHITE)
    d.rectangle((2500, 480, 4316, 780), fill=WHITE)

    # Bring back one clean dark diagonal line behind the QR area.
    d.polygon([(2805, 0), (3030, 0), (3790, im.height), (3565, im.height)], fill=INK)

    # Restore one clean colored QR frame on top.
    d.rounded_rectangle((2895, 875, 3995, 1985), radius=145, outline=GREEN, width=24)

    return im


def clean_back(src):
    im = Image.open(src).convert("RGB")
    d = ImageDraw.Draw(im)

    # Remove the rough/crooked right-side dark leftovers from the old recolor.
    d.rectangle((3190, 0, im.width, im.height), fill=WHITE)

    # Rebuild the right-side design with cleaner, straighter geometry behind the card.
    d.polygon([(3380, 0), (3585, 0), (4070, im.height), (3865, im.height)], fill=INK)
    d.rounded_rectangle((3125, 1110, 4095, 2670), radius=135, outline=INK, width=28)

    # Re-mask the content card area so the line does not cross text or contact fields.
    d.rounded_rectangle((325, 1090, 3995, 2625), radius=105, fill=WHITE, outline=(229, 231, 235), width=8)

    return im


def main():
    OUT.mkdir(parents=True, exist_ok=True)
    front = clean_front(SRC / "zemtab-option-10-black-emerald-front.png")
    back = clean_back(SRC / "zemtab-option-10-black-emerald-back.png")
    save_2400(front, OUT / "zemtab-option-10-emerald-clean-2400dpi-front.png")
    save_2400(back, OUT / "zemtab-option-10-emerald-clean-2400dpi-back.png")


if __name__ == "__main__":
    main()
