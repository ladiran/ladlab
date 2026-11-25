import httpx
import pytest
import respx
from fastapi.testclient import TestClient

from app import heuristics, verdict
from app.models import Evidence, Verdict
from app.providers import factcheck, gdelt, social


def ev(direction, weight, decisive=False):
    return Evidence(source="t", signal="s", direction=direction, weight=weight, decisive=decisive, detail="d")


# ---- verdict engine
def test_decisive_fake():
    assert verdict.decide([ev("fake", 100, True)])[0] == Verdict.VERIFIED_FAKE

def test_decisive_authentic():
    assert verdict.decide([ev("authentic", 90, True)])[0] == Verdict.AUTHENTICATED

def test_conflict_is_unverified():
    assert verdict.decide([ev("fake", 100, True), ev("authentic", 90, True)])[0] == Verdict.UNVERIFIED

def test_trusted_domain_alone_is_not_enough():
    assert verdict.decide([ev("authentic", 40)])[0] == Verdict.UNVERIFIED

def test_trusted_plus_corroboration_is_authentic():
    assert verdict.decide([ev("authentic", 40), ev("authentic", 40)])[0] == Verdict.AUTHENTICATED

def test_red_flags_block_soft_authentication():
    assert verdict.decide([ev("authentic", 80), ev("fake", 30)])[0] == Verdict.UNVERIFIED

def test_empty_is_unverified():
    assert verdict.decide([])[0] == Verdict.UNVERIFIED


# ---- heuristics
def test_clickbait_title():
    sigs = {e.signal for e in heuristics.analyse_title("SHOCKING!! You won't believe this miracle cure")}
    assert {"clickbait_phrases", "punctuation"} <= sigs

def test_calm_title_clean():
    assert heuristics.analyse_title("City council approves new bus routes") == []

def test_lookalike_domain():
    assert "lookalike_domain" in {e.signal for e in heuristics.analyse_url("https://bbc-news-today.top/x")}

def test_real_outlet_not_lookalike():
    sigs = {e.signal for e in heuristics.analyse_url("https://www.bbc.co.uk/news/x")}
    assert "lookalike_domain" not in sigs and "trusted_outlet" in sigs

def test_satire_site():
    assert "satire_site" in {e.signal for e in heuristics.analyse_url("https://www.theonion.com/a")}

def test_subdomain_spoof_not_trusted():
    # evil-bbc.com.example.net must not match bbc.com
    sigs = {e.signal for e in heuristics.analyse_url("https://bbc.com.example.net/a")}
    assert "trusted_outlet" not in sigs


# ---- rating classifier
@pytest.mark.parametrize("text,kind", [
    ("False", "false"), ("Pants on Fire", "false"), ("Mostly False", "false"),
    ("True", "true"), ("Mostly true", "true"), ("Half True", "mixed"),
    ("Not true", "false"), ("Mixture", "mixed"),
])
def test_classify_rating(text, kind):
    assert factcheck.classify_rating(text) == kind


# ---- providers
def test_gdelt_keywords():
    assert gdelt.keywords("The mayor says the new bridge will open in March") == "mayor bridge open March"

def test_social_parse():
    assert social.parse_handle("https://bsky.app/profile/alice.bsky.social") == ("Bluesky", "alice.bsky.social")
    assert social.parse_handle("https://x.com/someone") == ("X", "someone")
    assert social.parse_handle("@someone") == ("unknown", "someone")

@respx.mock
@pytest.mark.asyncio
async def test_factcheck_false(monkeypatch):
    monkeypatch.setenv("FACTCHECK_API_KEY", "k")
    respx.get(factcheck.ENDPOINT).mock(return_value=httpx.Response(200, json={"claims": [
        {"claimReview": [{"publisher": {"name": "Snopes"}, "textualRating": "False", "url": "https://x.test/1"},
                         {"publisher": {"name": "PolitiFact"}, "textualRating": "Pants on Fire", "url": "https://x.test/2"}]}]}))
    async with httpx.AsyncClient() as c:
        out = await factcheck.check(c, "claim")
    assert any(e.decisive and e.direction == "fake" for e in out)

@pytest.mark.asyncio
async def test_missing_key_raises(monkeypatch):
    monkeypatch.delenv("FACTCHECK_API_KEY", raising=False)
    async with httpx.AsyncClient() as c:
        with pytest.raises(RuntimeError):
            await factcheck.check(c, "x")


# ---- API end to end (all providers mocked)
@respx.mock
def test_api_end_to_end(monkeypatch):
    monkeypatch.setenv("FACTCHECK_API_KEY", "k")
    monkeypatch.setenv("SAFE_BROWSING_API_KEY", "k")
    from app.main import app
    respx.get(factcheck.ENDPOINT).mock(return_value=httpx.Response(200, json={"claims": [
        {"claimReview": [{"publisher": {"name": "Snopes"}, "textualRating": "False", "url": "https://s.test/1"}]}]}))
    respx.get(url__startswith="https://api.gdeltproject.org").mock(return_value=httpx.Response(200, json={"articles": []}))
    respx.post(url__startswith="https://safebrowsing.googleapis.com").mock(return_value=httpx.Response(200, json={}))
    respx.get(url__startswith="https://rdap.org").mock(return_value=httpx.Response(404))
    respx.get(url__startswith="https://archive.org").mock(return_value=httpx.Response(200, json={}))
    with TestClient(app) as tc:
        r = tc.post("/api/check", json={"title": "Miracle cure banned by doctors", "url": "https://93.184.216.34/x"})
    assert r.status_code == 200
    assert r.json()["verdict"] == "verified_fake"

def test_api_requires_input():
    from app.main import app
    with TestClient(app) as tc:
        assert tc.post("/api/check", json={}).status_code == 422

def test_ssrf_blocked(monkeypatch):
    monkeypatch.delenv("FACTCHECK_API_KEY", raising=False)
    from app.main import app
    with TestClient(app) as tc:
        r = tc.post("/api/check", json={"url": "http://127.0.0.1:8080/admin"})
    assert r.status_code == 200
    assert any("non-public" in s for s in r.json()["skipped"])

def test_unresolvable_lookalike_still_flagged(monkeypatch):
    monkeypatch.delenv("FACTCHECK_API_KEY", raising=False)
    from app.main import app
    with TestClient(app) as tc:
        r = tc.post("/api/check", json={"url": "http://bbc-news-today.invalid/x"})
    assert "lookalike_domain" in {e["signal"] for e in r.json()["evidence"]}


def test_health_endpoint():
    from app.main import app
    with TestClient(app) as tc:
        r = tc.get("/api/health")
    assert r.status_code == 200 and r.json()["status"] == "ok"


# ---- the source lists must ship with the program (a .gitignore rule once hid them)
def test_source_lists_present():
    from app import sources
    cfg = sources.load()
    assert set(cfg) >= {"suspicious_tlds", "trusted", "satire", "known_unreliable"}
    assert "bbc.co.uk" in cfg["trusted"] and "theonion.com" in cfg["satire"]
