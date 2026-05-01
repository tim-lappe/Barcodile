#!/usr/bin/env python3
from __future__ import annotations

import re
import sys
from pathlib import Path


def find_matching_paren(s: str, open_idx: int) -> int:
    depth = 0
    for idx in range(open_idx, len(s)):
        if s[idx] == "(":
            depth += 1
        elif s[idx] == ")":
            depth -= 1
            if depth == 0:
                return idx
    raise ValueError("unbalanced parens")


def find_matching_brace(s: str, open_idx: int) -> int:
    depth = 0
    for idx in range(open_idx, len(s)):
        if s[idx] == "{":
            depth += 1
        elif s[idx] == "}":
            depth -= 1
            if depth == 0:
                return idx
    raise ValueError("unbalanced braces")


def parse_constructor_deps(ctor_paren: str) -> list[tuple[str, str]]:
    deps: list[tuple[str, str]] = []
    for m in re.finditer(
        r"private\s+readonly\s+([^\s$]+(?:\s+[^\s$]+)*)\s+\$([a-zA-Z_][a-zA-Z0-9_]*)",
        ctor_paren,
    ):
        type_hint = " ".join(m.group(1).split())
        deps.append((type_hint, m.group(2)))
    return deps


def transform(content: str) -> str | None:
    ctor_kw = re.search(r"public\s+function\s+__construct\s*\(", content)
    if not ctor_kw:
        return None
    p0 = ctor_kw.end() - 1
    p1 = find_matching_paren(content, p0)
    ctor_paren = content[p0 : p1 + 1]
    deps = parse_constructor_deps(ctor_paren)
    if not deps:
        return None
    brace_open = content.find("{", p1)
    if brace_open == -1:
        return None
    ctor_end = find_matching_brace(content, brace_open)

    invoke_kw = re.search(r"public\s+function\s+__invoke\s*\(", content)
    if not invoke_kw:
        return None
    ip0 = invoke_kw.end() - 1
    ip1 = find_matching_paren(content, ip0)
    sig_inner = content[ip0 + 1 : ip1].strip().rstrip(",")
    after_paren = content[ip1 + 1 :]
    mret = re.match(r"\s*:\s*[^{]+", after_paren)
    if not mret:
        return None
    return_type = mret.group(0)
    rest = after_paren[mret.end() :]

    inj = ", ".join(f"{t} ${n}" for t, n in deps)
    if sig_inner:
        new_inner = f"{sig_inner}, {inj}"
    else:
        new_inner = inj

    new_invoke = (
        content[invoke_kw.start() : ip0 + 1]
        + new_inner
        + ")"
        + return_type
        + rest
    )

    rebuilt = content[: ctor_kw.start()] + content[ctor_end + 1 : invoke_kw.start()] + new_invoke

    for _t, name in deps:
        rebuilt = re.sub(rf"\$this->{re.escape(name)}->", rf"${name}->", rebuilt)
        rebuilt = re.sub(rf"\$this->{re.escape(name)}\b", rf"${name}", rebuilt)

    if "function __construct" in rebuilt:
        return None
    return rebuilt


def main() -> int:
    root = Path(__file__).resolve().parents[1] / "src"
    paths = sorted(root.glob("**/Application/Controller/*.php"))
    changed = 0
    for path in paths:
        text = path.read_text(encoding="utf-8")
        if "function __construct" not in text:
            continue
        new_text = transform(text)
        if new_text is None:
            print(f"skip (no deps?): {path}", file=sys.stderr)
            continue
        if new_text == text:
            print(f"unchanged: {path}", file=sys.stderr)
            continue
        path.write_text(new_text, encoding="utf-8")
        changed += 1
        print(path.relative_to(root.parent))
    print(f"updated {changed} files", file=sys.stderr)
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
