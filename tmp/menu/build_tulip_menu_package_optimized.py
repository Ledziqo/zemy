from __future__ import annotations

import copy
import json
import io
import zipfile
from datetime import datetime, timezone
from pathlib import Path

from PIL import Image, ImageOps

ROOT = Path(__file__).resolve().parents[2]
SOURCE_PACKAGE = ROOT / "output/menu/tulip-olympia-final-menu-v3.zip"
OUTPUT_PACKAGE = ROOT / "output/menu/tulip-olympia-final-menu-optimized.zip"
SOURCE_IMAGES = ROOT / "public/uploads/menu-items"
MAX_EDGE = 640
WEBP_QUALITY = 78


def optimize_image(source: Path) -> bytes:
    with Image.open(source) as original:
        image = ImageOps.exif_transpose(original)
        image.thumbnail((MAX_EDGE, MAX_EDGE), Image.Resampling.LANCZOS)

        if image.mode in ("RGBA", "LA") or "transparency" in image.info:
            rgba = image.convert("RGBA")
            background = Image.new("RGB", rgba.size, "white")
            background.paste(rgba, mask=rgba.getchannel("A"))
            image = background
        else:
            image = image.convert("RGB")

        output = io.BytesIO()
        image.save(output, "WEBP", quality=WEBP_QUALITY, method=6)
        return output.getvalue()


with zipfile.ZipFile(SOURCE_PACKAGE, "r") as source_zip:
    manifest = json.loads(source_zip.read("menu.json"))

optimized_manifest = copy.deepcopy(manifest)
optimized_manifest["generated_at"] = datetime.now(timezone.utc).isoformat()
optimized_images: dict[str, bytes] = {}

for category in optimized_manifest["categories"]:
    for item in category["items"]:
        original_path = Path(item["image"])
        source_name = original_path.name
        source_path = SOURCE_IMAGES / source_name
        if not source_path.is_file():
            raise FileNotFoundError(f"Missing source image: {source_path}")

        optimized_name = f"{original_path.stem}.webp"
        optimized_path = f"photos/{optimized_name}"
        optimized_bytes = optimize_image(source_path)
        if optimized_path in optimized_images:
            raise RuntimeError(f"Duplicate optimized path: {optimized_path}")
        optimized_images[optimized_path] = optimized_bytes
        item["image"] = optimized_path

with zipfile.ZipFile(OUTPUT_PACKAGE, "w", compression=zipfile.ZIP_DEFLATED, compresslevel=9) as output_zip:
    output_zip.writestr(
        "menu.json",
        json.dumps(optimized_manifest, ensure_ascii=False, indent=2),
    )
    output_zip.writestr(
        "README.txt",
        "ZemTab optimized menu import package\n\n"
        "Upload this ZIP in Admin > Database > Replace menu from import package.\n"
        "The menu data is unchanged; photos were resized and compressed for weak Wi-Fi.\n",
    )
    for path, image_bytes in optimized_images.items():
        output_zip.writestr(path, image_bytes)

total_bytes = sum(len(value) for value in optimized_images.values())
print(f"Created {OUTPUT_PACKAGE}")
print(f"Categories: {len(optimized_manifest['categories'])} | Items: {len(optimized_images)}")
print(f"Optimized photos: {total_bytes / 1024 / 1024:.2f} MB")
