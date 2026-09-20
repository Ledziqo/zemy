from __future__ import annotations

import io
from pathlib import Path

from PIL import Image, ImageOps

ROOT = Path(__file__).resolve().parents[2]
SOURCE = ROOT / "public/uploads/menu-items"
OUTPUT = SOURCE / "optimized"
OUTPUT.mkdir(parents=True, exist_ok=True)

count = 0
for source in sorted(SOURCE.iterdir()):
    if not source.is_file() or source.suffix.lower() not in {".png", ".jpg", ".jpeg", ".webp"}:
        continue

    with Image.open(source) as original:
        image = ImageOps.exif_transpose(original)
        image.thumbnail((640, 640), Image.Resampling.LANCZOS)
        if image.mode in ("RGBA", "LA") or "transparency" in image.info:
            rgba = image.convert("RGBA")
            background = Image.new("RGB", rgba.size, "white")
            background.paste(rgba, mask=rgba.getchannel("A"))
            image = background
        else:
            image = image.convert("RGB")

        webp = io.BytesIO()
        image.save(webp, "WEBP", quality=78, method=6)
        (OUTPUT / f"{source.stem}.webp").write_bytes(webp.getvalue())

        jpg = io.BytesIO()
        image.save(jpg, "JPEG", quality=82, optimize=True, progressive=True)
        (OUTPUT / f"{source.stem}.jpg").write_bytes(jpg.getvalue())
        count += 1

print(f"Created {count * 2} derivatives for {count} source images in {OUTPUT}")
