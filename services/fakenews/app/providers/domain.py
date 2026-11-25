"""Domain age through RDAP (free, no key) and archive history through the
Wayback Machine availability API (free, no key).
RDAP: https://about.rdap.org/    Wayback: https://archive.org/help/wayback_api.php
"""
from datetime import datetime, timezone

import httpx

from ..models import Evidence


async def domain_age(client: httpx.AsyncClient, host: str) -> list[Evidence]:
    # Registrable domain is approximated by the last two labels (good enough for .com/.org,
    # imperfect for .co.uk style suffixes, in which case RDAP simply returns 404).
    parts = host.removeprefix("www.").split(".")
    domain = ".".join(parts[-2:])
    r = await client.get(f"https://rdap.org/domain/{domain}", follow_redirects=True)
    if r.status_code == 404:
        return []
    r.raise_for_status()
    created = None
    for ev in r.json().get("events", []):
        if ev.get("eventAction") == "registration":
            created = datetime.fromisoformat(ev["eventDate"].replace("Z", "+00:00"))
    if not created:
        return []
    days = (datetime.now(timezone.utc) - created).days
    if days < 90:
        return [Evidence(source="rdap", signal="new_domain", direction="fake", weight=15,
                         detail=f"Domain registered only {days} days ago.")]
    if days > 365 * 5:
        return [Evidence(source="rdap", signal="old_domain", direction="authentic", weight=8,
                         detail=f"Domain registered {days // 365} years ago.")]
    return [Evidence(source="rdap", signal="domain_age", direction="neutral",
                     detail=f"Domain registered {days // 30} months ago.")]


async def archive_history(client: httpx.AsyncClient, url: str) -> list[Evidence]:
    r = await client.get("https://archive.org/wayback/available", params={"url": url})
    r.raise_for_status()
    snap = r.json().get("archived_snapshots", {}).get("closest")
    if snap and snap.get("available"):
        return [Evidence(source="wayback", signal="archived", direction="neutral",
                         detail="An archived copy exists in the Wayback Machine.", link=snap.get("url"))]
    return []
