import logging


class UnknownLabelSize(ValueError):
    pass


def suppress_devicedependent_deprecation_warning() -> None:
    logging.getLogger("brother_ql.devicedependent").setLevel(logging.ERROR)


def resolve_label(label_identifier: str):
    from brother_ql.labels import LabelsManager

    for label in LabelsManager().iter_elements():
        if label.identifier == label_identifier:
            return label
    raise UnknownLabelSize(label_identifier)
