"""Social handle checks. Only Bluesky exposes free, keyless profile data.
Other platforms (X, Facebook, Instagram, TikTok) require paid or approved API access,
so for those we report that the account could not be verified rather than guess.
Bluesky: https://docs.bsky.app/docs/api/app-bsky-actor-get-profile
"""
import re
from datetime import datetime, timezone
from urllib.parse import urlparse

import httpx

from ..models import Evidence

PLATFORMS = {
    "x.com": "X", "twitter.com": "X", "facebook.com": "Facebook", "instagram.com": "Instagram",
    "tiktok.com": "TikTok", "youtube.com": "YouTube", "bsky.app": "Bluesky",
}


def parse_handle(raw: str) -> tuple[str, str]:
    """Return (platform, handle). Accepts '@name', 'name.bsky.social', or a profile URL."""
    raw = raw.strip()
    if "/" in raw or raw.lower().startswith("http"):
        p = urlparse(raw if "://" in raw else "https://" + raw)
        host = (p.hostname or "").removeprefix("www.").removeprefix("mobile.")
        platform = PLATFORMS.get(host, host or "unknown")
        segs = [s for s in p.path.split("/") if s]
        if platform == "Bluesky" and len(segs) >= 2 and segs[0] == "profile":
            return platform, segs[1]
        handle = segs[0].lstrip("@") if segs else ""
        return platform, handle
    handle = raw.lstrip("@")
    if re.search(r"\.bsky\.social$|^[\w-]+\.[\w.-]+$", handle):
        return "Bluesky", handle
    return "unknown", handle


async def check(client: httpx.AsyncClient, raw: str) -> list[Evidence]:
    platform, handle = parse_handle(raw)
    if not handle:
        return []
    if platform != "Bluesky":
        name = platform if platform != "unknown" else "this platform"
        return [Evidence(source="social", signal="unverifiable_platform", direction="neutral",
                         detail=f"@{handle}: {name} offers no free profile API, so the account could "
                                "not be checked. Look for the verified badge and account age manually.")]
    r = await client.get("https://public.api.bsky.app/xrpc/app.bsky.actor.getProfile",
                         params={"actor": handle})
    if r.status_code in (400, 404):
        return [Evidence(source="social", signal="no_such_account", direction="fake", weight=15,
                         detail=f"No Bluesky account named {handle} exists.")]
    r.raise_for_status()
    d = r.json()
    out: list[Evidence] = []
    created = d.get("createdAt") or d.get("indexedAt")
    if created:
        days = (datetime.now(timezone.utc) - datetime.fromisoformat(created.replace("Z", "+00:00"))).days
        if days < 30:
            out.append(Evidence(source="social", signal="young_account", direction="fake", weight=12,
                                detail=f"Account is only {days} days old."))
    if d.get("followersCount", 0) < 5 and d.get("postsCount", 0) > 100:
        out.append(Evidence(source="social", signal="broadcast_account", direction="fake", weight=8,
                            detail="Many posts but almost no followers."))
    if not out:
        out.append(Evidence(source="social", signal="account_ok", direction="neutral",
                            detail=f"Bluesky account {handle} looks like an ordinary established account."))
    return out
