#!/usr/bin/env python3

import base64
import io
import json
import sys

from brother_ql_labels import (
    UnknownLabelSize,
    normalize_printer_identifier,
    resolve_label,
    suppress_devicedependent_deprecation_warning,
)

suppress_devicedependent_deprecation_warning()


THRESHOLD = 180


def debug(message: str) -> None:
    print(message, file=sys.stderr)


def read_object(data: dict, key: str) -> dict:
    value = data.get(key)
    if isinstance(value, dict):
        return value
    return {}


def fail(message: str) -> None:
    print(message, file=sys.stderr)
    sys.exit(1)


def threshold_image(image):
    return (
        image.convert("L")
        .point(lambda pixel: 0 if pixel < THRESHOLD else 255, mode="1")
        .convert("RGB")
    )


def fit_centered(image, width: int, height: int):
    from PIL import Image

    canvas = Image.new("RGB", (width, height), (255, 255, 255))
    source = threshold_image(image)
    source.thumbnail((width, height))
    x = (width - source.width) // 2
    y = (height - source.height) // 2
    canvas.paste(source, (x, y))
    return threshold_image(canvas)


def load_image(data: dict):
    raw = data.get("imageBase64")
    if not isinstance(raw, str) or raw == "":
        fail("Missing required key: imageBase64")
    try:
        image_bytes = base64.b64decode(raw, validate=True)
    except Exception as exc:
        fail(f"Invalid imageBase64: {exc}")
    try:
        from PIL import Image

        return Image.open(io.BytesIO(image_bytes))
    except Exception as exc:
        fail(f"Could not load label image: {exc}")


def image_for_label(data: dict, spec):
    width, height = spec.dots_printable
    if height > 0:
        debug(f"Resolved label printable dots: width={width} height={height}")
        return fit_centered(load_image(data), width, height)
    debug(f"Resolved label printable dots: width={width} height=endless")
    return threshold_image(load_image(data))


def main() -> None:
    try:
        data = json.load(sys.stdin)
    except json.JSONDecodeError as exc:
        fail(f"Invalid JSON: {exc}")
    if not isinstance(data, dict):
        fail("Invalid JSON: expected an object")

    connection = read_object(data, "connection")
    settings = read_object(data, "printSettings")
    model = connection.get("model")
    printer = connection.get("printerIdentifier")
    backend = connection.get("backend")
    label = settings.get("labelSize")
    raw_red = settings.get("red")
    red = raw_red is True
    if not model or not printer or not backend or not label:
        fail(
            "Missing required keys: connection.model, connection.printerIdentifier, "
            "connection.backend, printSettings.labelSize",
        )
    printer = normalize_printer_identifier(backend, printer)

    try:
        from brother_ql.backends.helpers import send
        from brother_ql.conversion import convert
        from brother_ql.raster import BrotherQLRaster
    except ImportError as exc:
        fail(str(exc))

    debug(
        "Brother QL label image print settings: "
        f"model={model} backend={backend} printer={printer} labelSize={label} red={red}"
    )
    try:
        spec = resolve_label(label)
    except UnknownLabelSize:
        fail(f"Unknown label size: {label}")

    image = image_for_label(data, spec)

    qlr = BrotherQLRaster(model)
    instructions = convert(
        qlr,
        [image],
        label,
        rotate="auto",
        threshold=70.0,
        cut=True,
        dither=False,
        compress=False,
        red=red,
    )
    debug(f"Generated Brother QL instructions: bytes={len(instructions)}")
    send(
        instructions=instructions,
        printer_identifier=printer,
        backend_identifier=backend,
        blocking=True,
    )
    debug("Brother QL send completed")


if __name__ == "__main__":
    main()
