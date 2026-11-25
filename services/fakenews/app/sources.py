import json
from functools import lru_cache
from pathlib import Path

_PATH = Path(__file__).parent / "data" / "sources.json"


@lru_cache(maxsize=1)
def load() -> dict:
    with _PATH.open(encoding="utf-8") as f:
        return json.load(f)


def host_matches(host: str, domains: list[str]) -> bool:
    """True if host equals a listed domain or is a subdomain of one."""
    host = host.lower().removeprefix("www.")
    return any(host == d or host.endswith("." + d) for d in domains)
