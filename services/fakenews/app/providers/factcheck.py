"""Google Fact Check Tools API (free, needs an API key).
Docs: https://developers.google.com/fact-check/tools/api/reference/rest/v1alpha1/claims/search
"""
import os
import re

import httpx

from ..models import Evidence

ENDPOINT = "https://factchecktools.googleapis.com/v1alpha1/claims:search"

_FALSE = re.compile(r"\b(false|fake|pants on fire|incorrect|fabricated|hoax|satire|scam|"
                    r"no evidence|not true|misleading|mostly false|unproven|distorted)\b", re.I)
_TRUE = re.compile(r"\b(true|correct|accurate|legit|verified|mostly true|real)\b", re.I)
_MIXED = re.compile(r"\b(mixture|half|partly|partially|mixed|needs context|unclear|outdated)\b", re.I)


def classify_rating(text: str) -> str:
    """Return 'false', 'true' or 'mixed' for a publisher's textual rating."""
    t = text.lower()
    if re.search(r"\b(not true|not accurate|not correct|isn'?t true)\b", t):
        return "false"
    if _MIXED.search(t):
        return "mixed"
    if _FALSE.search(t):
        return "false"
    if _TRUE.search(t):
        return "true"
    return "mixed"


async def check(client: httpx.AsyncClient, query: str) -> list[Evidence]:
    key = os.getenv("FACTCHECK_API_KEY")
    if not key:
        raise RuntimeError("FACTCHECK_API_KEY not set")
    r = await client.get(ENDPOINT, params={"query": query[:250], "key": key, "pageSize": 10})
    r.raise_for_status()
    claims = r.json().get("claims", [])
    ratings: list[tuple[str, str, str, str]] = []
    for c in claims:
        for rev in c.get("claimReview", []):
            publisher = rev.get("publisher", {}).get("name", "unknown")
            rating = rev.get("textualRating", "")
            ratings.append((classify_rating(rating), publisher, rating, rev.get("url", "")))
    if not ratings:
        return [Evidence(source="factcheck", signal="no_matching_claims", direction="neutral",
                         detail="No published fact-checks matched this text. That is not proof either way.")]
    out: list[Evidence] = []
    n_false = sum(1 for r_ in ratings if r_[0] == "false")
    n_true = sum(1 for r_ in ratings if r_[0] == "true")
    for kind, pub, rating, link in ratings[:5]:
        direction = {"false": "fake", "true": "authentic", "mixed": "neutral"}[kind]
        out.append(Evidence(source="factcheck", signal=f"rated_{kind}", direction=direction,
                            weight=15, detail=f'{pub} rated a matching claim "{rating}".', link=link or None))
    if n_false and n_false > n_true:
        out.append(Evidence(source="factcheck", signal="majority_false", direction="fake", weight=100,
                            decisive=True, detail=f"{n_false} of {len(ratings)} matching fact-checks rate it false."))
    elif n_true and n_true > n_false:
        out.append(Evidence(source="factcheck", signal="majority_true", direction="authentic", weight=90,
                            decisive=True, detail=f"{n_true} of {len(ratings)} matching fact-checks rate it true."))
    return out
