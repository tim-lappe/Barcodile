#!/usr/bin/env python3

import json
import sys
from pathlib import Path


LABEL_WIDTH = 600
LABEL_HEIGHT = 300
OUTER_MARGIN = 24
INNER_GAP = 24
QR_BORDER = 2
THRESHOLD = 180


def fail(message: str) -> None:
    print(message, file=sys.stderr)
    sys.exit(1)


def read_input() -> dict:
    try:
        data = json.load(sys.stdin)
    except json.JSONDecodeError as exc:
        fail(f"Invalid JSON: {exc}")
    if not isinstance(data, dict):
        fail("Invalid JSON: expected an object")
    return data


def threshold_image(image):
    return (
        image.convert("L")
        .point(lambda pixel: 0 if pixel < THRESHOLD else 255, mode="1")
        .convert("RGB")
    )


def load_logo(path: str):
    if path == "":
        return None
    logo_path = Path(path)
    if not logo_path.is_file():
        return None
    try:
        from PIL import Image

        return threshold_image(Image.open(logo_path))
    except Exception as exc:
        fail(f"Could not load logo: {exc}")


def paste_centered(canvas, image, box: tuple[int, int, int, int]) -> None:
    left, top, right, bottom = box
    box_width = right - left
    box_height = bottom - top
    image.thumbnail((box_width, box_height))
    x = left + (box_width - image.width) // 2
    y = top + (box_height - image.height) // 2
    canvas.paste(image, (x, y))


def create_qr(public_code: str):
    import qrcode

    qr = qrcode.QRCode(
        version=None,
        error_correction=qrcode.constants.ERROR_CORRECT_M,
        box_size=10,
        border=QR_BORDER,
    )
    qr.add_data(public_code)
    qr.make(fit=True)
    return qr.make_image(fill_color="black", back_color="white").convert("RGB")


def main() -> None:
    data = read_input()
    public_code = data.get("publicCode")
    logo_path = data.get("logoPath")
    if not isinstance(public_code, str) or public_code.strip() == "":
        fail("Missing required key: publicCode")
    if logo_path is not None and not isinstance(logo_path, str):
        fail("Invalid key: logoPath")

    try:
        from PIL import Image, ImageDraw, ImageFont
    except ImportError as exc:
        fail(str(exc))

    canvas = Image.new("RGB", (LABEL_WIDTH, LABEL_HEIGHT), (255, 255, 255))
    draw = ImageDraw.Draw(canvas)

    content_top = OUTER_MARGIN
    content_bottom = LABEL_HEIGHT - OUTER_MARGIN
    logo_left = OUTER_MARGIN
    logo_right = (LABEL_WIDTH // 2) - (INNER_GAP // 2)
    qr_left = (LABEL_WIDTH // 2) + (INNER_GAP // 2)
    qr_right = LABEL_WIDTH - OUTER_MARGIN

    logo = load_logo(logo_path or "")
    if logo is not None:
        paste_centered(
            canvas,
            logo,
            (logo_left, content_top, logo_right, content_bottom),
        )
    else:
        font = ImageFont.load_default()
        text = "Barcodile"
        bbox = draw.textbbox((0, 0), text, font=font)
        x = logo_left + ((logo_right - logo_left) - (bbox[2] - bbox[0])) // 2
        y = content_top + ((content_bottom - content_top) - (bbox[3] - bbox[1])) // 2
        draw.text((x, y), text, fill=(0, 0, 0), font=font)

    qr = threshold_image(create_qr(public_code.strip()))
    paste_centered(canvas, qr, (qr_left, content_top, qr_right, content_bottom))
    threshold_image(canvas).save(sys.stdout.buffer, format="PNG")


if __name__ == "__main__":
    main()
