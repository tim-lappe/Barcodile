#!/usr/bin/env python3

import json
import sys


def debug(message: str) -> None:
    print(message, file=sys.stderr)


def read_object(data: dict, key: str) -> dict:
    value = data.get(key)
    if isinstance(value, dict):
        return value
    return {}


def main() -> None:
    try:
        data = json.load(sys.stdin)
    except json.JSONDecodeError as exc:
        print(f"Invalid JSON: {exc}", file=sys.stderr)
        sys.exit(1)
    if not isinstance(data, dict):
        print("Invalid JSON: expected an object", file=sys.stderr)
        sys.exit(1)

    connection = read_object(data, "connection")
    settings = read_object(data, "printSettings")
    model = connection.get("model")
    printer = connection.get("printerIdentifier")
    backend = connection.get("backend")
    label = settings.get("labelSize")
    if not model or not printer or not backend or not label:
        print(
            "Missing required keys: connection.model, connection.printerIdentifier, connection.backend, printSettings.labelSize",
            file=sys.stderr,
        )
        sys.exit(1)

    try:
        from PIL import Image, ImageDraw
        from brother_ql.backends.helpers import send
        from brother_ql.conversion import convert
        from brother_ql.labels import LabelsManager
        from brother_ql.raster import BrotherQLRaster
    except ImportError as exc:
        print(str(exc), file=sys.stderr)
        sys.exit(1)

    use_red = settings.get("red") is True
    debug(
        "Brother QL print settings: "
        f"model={model} backend={backend} printer={printer} labelSize={label} red={use_red}"
    )

    label_manager = LabelsManager()
    try:
        spec = label_manager.get_label_by_identifier(label)
    except Exception:
        print(f"Unknown label size: {label}", file=sys.stderr)
        sys.exit(1)

    w, h = spec.dots_printable
    debug(f"Resolved label printable dots: width={w} height={h}")
    im = Image.new("RGB", (w, h), (255, 255, 255))
    draw = ImageDraw.Draw(im)
    draw.text((12, 12), "Barcodile test label", fill=(0, 0, 0))

    qlr = BrotherQLRaster(model)
    instructions = convert(
        qlr,
        [im],
        label,
        rotate="auto",
        threshold=70.0,
        cut=True,
        dither=False,
        compress=False,
        red=use_red,
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
