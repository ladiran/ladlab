"""Offline checks. These never prove anything, they only add small nudges."""
import re
from urllib.parse import urlparse

from . import sources
from .models import Evidence

CLICKBAIT = [
    r"you won'?t believe", r"shocking", r"what (they|the media) (don'?t|doesn'?t) want you to",
    r"doctors hate", r"miracle", r"secret (cure|they)", r"exposed", r"wake up",
    r"share before (it'?s|they) (deleted|remove)", r"breaking!{1,}", r"100% (proof|true)",
    r"mainstream media (is )?(hiding|lying)", r"banned",
]
_CB = [re.compile(p, re.I) for p in CLICKBAIT]
_LOOKALIKE_BRANDS = ["reuters", "bbc", "cnn", "apnews", "nytimes", "guardian", "aljazeera"]


def analyse_title(title: str) -> list[Evidence]:
    out: list[Evidence] = []
    hits = [p.pattern for p in _CB if p.search(title)]
    if hits:
        out.append(Evidence(source="heuristic", signal="clickbait_phrases", direction="fake",
                            weight=min(10 * len(hits), 20),
                            detail=f"Title uses {len(hits)} sensational phrase(s) common in misleading posts."))
    letters = [c for c in title if c.isalpha()]
    if len(letters) >= 12 and sum(c.isupper() for c in letters) / len(letters) > 0.6:
        out.append(Evidence(source="heuristic", signal="all_caps", direction="fake", weight=8,
                            detail="Most of the title is in capital letters."))
    if re.search(r"[!?]{2,}", title):
        out.append(Evidence(source="heuristic", signal="punctuation", direction="fake", weight=6,
                            detail="Repeated exclamation or question marks."))
    return out


def analyse_url(url: str) -> list[Evidence]:
    out: list[Evidence] = []
    p = urlparse(url)
    host = (p.hostname or "").lower().removeprefix("www.")
    cfg = sources.load()
    if p.scheme == "http":
        out.append(Evidence(source="heuristic", signal="no_https", direction="fake", weight=5,
                            detail="Page is not served over HTTPS."))
    if "xn--" in host:
        out.append(Evidence(source="heuristic", signal="punycode", direction="fake", weight=20,
                            detail="Domain uses punycode, often used for look-alike spoofing."))
    tld = host.rsplit(".", 1)[-1] if "." in host else ""
    if tld in cfg["suspicious_tlds"]:
        out.append(Evidence(source="heuristic", signal="suspicious_tld", direction="fake", weight=8,
                            detail=f".{tld} domains are cheap and frequently abused."))
    if not sources.host_matches(host, cfg["trusted"]):
        for brand in _LOOKALIKE_BRANDS:
            if brand in host:
                out.append(Evidence(source="heuristic", signal="lookalike_domain", direction="fake",
                                    weight=30, detail=f"Domain contains '{brand}' but is not the real outlet."))
                break
    if host.count(".") >= 4:
        out.append(Evidence(source="heuristic", signal="deep_subdomain", direction="fake", weight=6,
                            detail="Unusually many subdomain levels."))
    if sources.host_matches(host, cfg["satire"]):
        out.append(Evidence(source="source_list", signal="satire_site", direction="fake", weight=60,
                            detail="Known satire site. Content is humour, not reporting."))
    if sources.host_matches(host, cfg["known_unreliable"]):
        out.append(Evidence(source="source_list", signal="known_unreliable", direction="fake",
                            weight=80, decisive=True, detail="Listed in the local unreliable-source list."))
    if sources.host_matches(host, cfg["trusted"]):
        out.append(Evidence(source="source_list", signal="trusted_outlet", direction="authentic",
                            weight=40, detail="Established outlet in the local trusted list. "
                            "This alone does not confirm a specific story."))
    return out
