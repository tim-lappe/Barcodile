#!/usr/bin/env python3

import json
import sys


def main() -> None:
    try:
        from brother_ql.backends.helpers import discover as brother_discover
    except ImportError as exc:
        print(str(exc), file=sys.stderr)
        sys.exit(1)

    out = []
    seen = set()
    for backend in ("pyusb", "linux_kernel"):
        try:
            devices = brother_discover(backend_identifier=backend)
        except Exception:
            continue
        for item in devices:
            if not isinstance(item, dict):
                continue
            ident = item.get("identifier")
            if ident and ident not in seen:
                seen.add(ident)
                out.append(
                    {
                        "deviceIdentifier": ident,
                        "label": f"{ident} ({backend})",
                        "backend": backend,
                    }
                )

    json.dump(out, sys.stdout)


if __name__ == "__main__":
    main()
