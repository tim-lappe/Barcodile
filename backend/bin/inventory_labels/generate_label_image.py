#!/usr/bin/env python3

import json
import sys
from pathlib import Path


DOTS_PER_MILLIMETER = 10
OUTER_MARGIN = 24
INNER_GAP = 24
QR_BORDER = 2
THRESHOLD = 180
TEXT_PADDING = 24


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


def text_font(label_height: int):
    from PIL import ImageFont

    for size in (max(12, label_height // 5), max(12, label_height // 6), 12):
        try:
            return ImageFont.truetype("Arial.ttf", size=size)
        except OSError:
            continue
    return ImageFont.load_default()


def paste_wrapped_text(canvas, text: str) -> None:
    from PIL import ImageDraw

    draw = ImageDraw.Draw(canvas)
    font = text_font(canvas.height)
    max_width = max(1, canvas.width - (TEXT_PADDING * 2))
    words = text.split()
    lines: list[str] = []
    current = ""
    for word in words:
        candidate = word if current == "" else f"{current} {word}"
        bbox = draw.textbbox((0, 0), candidate, font=font)
        if bbox[2] - bbox[0] <= max_width:
            current = candidate
            continue
        if current != "":
            lines.append(current)
        current = word
    if current != "":
        lines.append(current)
    if not lines:
        lines = [text]
    line_boxes = [draw.textbbox((0, 0), line, font=font) for line in lines]
    line_heights = [box[3] - box[1] for box in line_boxes]
    line_gap = max(4, canvas.height // 20)
    text_height = sum(line_heights) + (line_gap * (len(lines) - 1))
    y = max(TEXT_PADDING, (canvas.height - text_height) // 2)
    for line, box, line_height in zip(lines, line_boxes, line_heights):
        line_width = box[2] - box[0]
        x = (canvas.width - line_width) // 2
        draw.text((x, y), line, fill=(0, 0, 0), font=font)
        y += line_height + line_gap


def read_dimension(data: dict, key: str) -> int:
    value = data.get(key)
    if not isinstance(value, int) or value <= 0:
        fail(f"Missing required key: {key}")
    return value


def main() -> None:
    data = read_input()
    content_type = data.get("contentType")
    content_value = data.get("contentValue")
    logo_path = data.get("logoPath")
    if not isinstance(content_value, str) or content_value.strip() == "":
        fail("Missing required key: contentValue")
    if logo_path is not None and not isinstance(logo_path, str):
        fail("Invalid key: logoPath")
    label_width = read_dimension(data, "widthMillimeters") * DOTS_PER_MILLIMETER
    label_height = read_dimension(data, "heightMillimeters") * DOTS_PER_MILLIMETER

    try:
        from PIL import Image, ImageDraw, ImageFont
    except ImportError as exc:
        fail(str(exc))

    canvas = Image.new("RGB", (label_width, label_height), (255, 255, 255))
    draw = ImageDraw.Draw(canvas)

    if content_type == "qr_code":
        content_top = OUTER_MARGIN
        content_bottom = label_height - OUTER_MARGIN
        logo_left = OUTER_MARGIN
        logo_right = (label_width // 2) - (INNER_GAP // 2)
        qr_left = (label_width // 2) + (INNER_GAP // 2)
        qr_right = label_width - OUTER_MARGIN

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

        qr = threshold_image(create_qr(content_value.strip()))
        paste_centered(canvas, qr, (qr_left, content_top, qr_right, content_bottom))
    elif content_type == "text":
        paste_wrapped_text(canvas, content_value.strip())
    else:
        fail("Unsupported label content type")

    threshold_image(canvas).save(sys.stdout.buffer, format="PNG")


if __name__ == "__main__":
    main()
