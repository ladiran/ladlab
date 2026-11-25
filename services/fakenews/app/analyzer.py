import asyncio
from urllib.parse import urlparse

import httpx

from . import heuristics, netsafe, verdict
from .models import CheckRequest, CheckResponse, Evidence
from .providers import classifier, domain, factcheck, gdelt, safebrowsing, social


async def _run(name: str, coro, evidence: list[Evidence], skipped: list[str]) -> None:
    """Run one provider; a failure is recorded, never raised."""
    try:
        evidence.extend(await coro)
    except Exception as e:  # noqa: BLE001 - providers fail in many ways, none should kill a check
        skipped.append(f"{name}: {str(e) or type(e).__name__}")


async def analyze(req: CheckRequest, client: httpx.AsyncClient) -> CheckResponse:
    evidence: list[Evidence] = []
    skipped: list[str] = []
    title = (req.title or req.text or "").strip()
    url = netsafe.normalise_url(req.url) if req.url and req.url.strip() else None

    reachable = bool(url)
    if url:
        try:
            await netsafe.assert_public(url)
        except netsafe.UnsafeURL as e:
            skipped.append(f"url: {e}")
            reachable = False  # offline heuristics still run, network calls do not

    if url and reachable and not title:
        try:
            title = netsafe.extract_title(await netsafe.fetch_page(client, url))
        except Exception as e:  # noqa: BLE001
            skipped.append(f"page fetch: {str(e) or type(e).__name__}")

    if title:
        evidence += heuristics.analyse_title(title)
    host = ""
    if url:
        host = (urlparse(url).hostname or "").lower()
        evidence += heuristics.analyse_url(url)

    tasks = []
    if title:
        tasks.append(_run("factcheck", factcheck.check(client, title), evidence, skipped))
        tasks.append(_run("gdelt", gdelt.check(client, title), evidence, skipped))
        tasks.append(_run("classifier", classifier.check(client, title), evidence, skipped))
    if url and reachable:
        tasks.append(_run("safebrowsing", safebrowsing.check(client, url), evidence, skipped))
        tasks.append(_run("rdap", domain.domain_age(client, host), evidence, skipped))
        tasks.append(_run("wayback", domain.archive_history(client, url), evidence, skipped))
    if req.handle and req.handle.strip():
        tasks.append(_run("social", social.check(client, req.handle), evidence, skipped))
    if not tasks and not evidence:
        skipped.append("input: nothing usable was provided")
    await asyncio.gather(*tasks)

    v, score, summary = verdict.decide(evidence)
    return CheckResponse(verdict=v, score=score, summary=summary, evidence=evidence, skipped=skipped)
