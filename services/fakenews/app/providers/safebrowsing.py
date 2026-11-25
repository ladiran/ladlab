"""Google Safe Browsing v4 (free for non-commercial use, needs an API key).
Docs: https://developers.google.com/safe-browsing/v4/lookup-api
"""
import os

import httpx

from ..models import Evidence

ENDPOINT = "https://safebrowsing.googleapis.com/v4/threatMatches:find"


async def check(client: httpx.AsyncClient, url: str) -> list[Evidence]:
    key = os.getenv("SAFE_BROWSING_API_KEY")
    if not key:
        raise RuntimeError("SAFE_BROWSING_API_KEY not set")
    body = {
        "client": {"clientId": "fakenews-checker", "clientVersion": "0.1"},
        "threatInfo": {
            "threatTypes": ["MALWARE", "SOCIAL_ENGINEERING", "UNWANTED_SOFTWARE"],
            "platformTypes": ["ANY_PLATFORM"],
            "threatEntryTypes": ["URL"],
            "threatEntries": [{"url": url}],
        },
    }
    r = await client.post(ENDPOINT, params={"key": key}, json=body)
    r.raise_for_status()
    matches = r.json().get("matches", [])
    if matches:
        kinds = sorted({m.get("threatType", "?") for m in matches})
        return [Evidence(source="safebrowsing", signal="flagged", direction="fake", weight=100,
                         decisive=True, detail=f"Google Safe Browsing flags this URL: {', '.join(kinds)}.")]
    return [Evidence(source="safebrowsing", signal="clean", direction="neutral",
                     detail="Not on Google Safe Browsing lists.")]
