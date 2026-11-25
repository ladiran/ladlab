"""GDELT DOC 2.0 API (free, no key). Used to see whether independent trusted outlets
are reporting the same story.
Docs: https://blog.gdeltproject.org/gdelt-doc-2-0-api-debuts/
"""
import re
from urllib.parse import urlparse

import httpx

from .. import sources
from ..models import Evidence

ENDPOINT = "https://api.gdeltproject.org/api/v2/doc/doc"
_STOP = set("the a an and or of to in on for with at by from is are was were be been this that "
            "it its as after before about into over new says say said will would".split())


def keywords(title: str, n: int = 6) -> str:
    words = [w for w in re.findall(r"[A-Za-z0-9']+", title) if w.lower() not in _STOP and len(w) > 2]
    return " ".join(words[:n])


async def check(client: httpx.AsyncClient, title: str) -> list[Evidence]:
    q = keywords(title)
    if len(q) < 8:
        return []
    r = await client.get(ENDPOINT, params={"query": q, "mode": "artlist", "format": "json",
                                            "maxrecords": 50, "timespan": "14d"})
    r.raise_for_status()
    try:
        arts = r.json().get("articles", [])
    except ValueError:  # GDELT sometimes returns plain-text errors
        raise RuntimeError("GDELT returned a non-JSON response")
    trusted = sources.load()["trusted"]
    domains = {urlparse(a.get("url", "")).hostname or "" for a in arts}
    hits = sorted(d.removeprefix("www.") for d in domains if sources.host_matches(d, trusted))
    if len(hits) >= 2:
        return [Evidence(source="gdelt", signal="corroborated", direction="authentic", weight=40,
                         detail=f"Similar coverage in the last 14 days from {len(hits)} trusted outlets: "
                                f"{', '.join(hits[:5])}.")]
    return [Evidence(source="gdelt", signal="not_corroborated", direction="neutral",
                     detail="No similar coverage found from trusted outlets in the last 14 days. "
                            "Very recent or local stories can look like this too.")]
