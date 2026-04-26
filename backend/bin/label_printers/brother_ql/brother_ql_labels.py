import logging
import re


class UnknownLabelSize(ValueError):
    pass


PYUSB_IDENTIFIER_PATTERN = re.compile(r"^(usb://0x[0-9a-fA-F]+:0x[0-9a-fA-F]+)")


def suppress_devicedependent_deprecation_warning() -> None:
    logging.getLogger("brother_ql.devicedependent").setLevel(logging.ERROR)


def normalize_printer_identifier(backend: str, printer_identifier: str) -> str:
    if backend != "pyusb":
        return printer_identifier
    match = PYUSB_IDENTIFIER_PATTERN.match(printer_identifier)
    if match is None:
        return printer_identifier
    return match.group(1)


def resolve_label(label_identifier: str):
    from brother_ql.labels import LabelsManager

    for label in LabelsManager().iter_elements():
        if label.identifier == label_identifier:
            return label
    raise UnknownLabelSize(label_identifier)
