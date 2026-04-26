#!/usr/bin/env python3

import json
import sys


def main() -> None:
    try:
        data = json.load(sys.stdin)
    except json.JSONDecodeError as exc:
        print(f"Invalid JSON: {exc}", file=sys.stderr)
        sys.exit(1)

    model = data.get("model")
    printer = data.get("printerIdentifier")
    backend = data.get("backend")
    label = data.get("labelSize")
    if not model or not printer or not backend or not label:
        print(
            "Missing required keys: model, printerIdentifier, backend, labelSize",
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

    label_manager = LabelsManager()
    try:
        spec = label_manager.get_label_by_identifier(label)
    except Exception:
        print(f"Unknown label size: {label}", file=sys.stderr)
        sys.exit(1)

    w, h = spec.dots_printable
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
        red=False,
    )
    send(
        instructions=instructions,
        printer_identifier=printer,
        backend_identifier=backend,
        blocking=True,
    )


if __name__ == "__main__":
    main()
