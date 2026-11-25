"""Turns a pile of evidence into one of three verdicts.

Design rule: be conservative. "authenticated" and "verified_fake" both need strong
evidence. Everything else stays "unverified", which is an honest answer, not a failure.
"""
from .models import Evidence, Verdict

# Combined weight needed to call something authentic without a fact-check.
AUTH_THRESHOLD = 70
FAKE_CEILING_FOR_AUTH = 20


def decide(evidence: list[Evidence]) -> tuple[Verdict, int, str]:
    fake = sum(e.weight for e in evidence if e.direction == "fake")
    auth = sum(e.weight for e in evidence if e.direction == "authentic")
    dec_fake = [e for e in evidence if e.decisive and e.direction == "fake"]
    dec_auth = [e for e in evidence if e.decisive and e.direction == "authentic"]

    score = max(0, min(100, round(50 + (auth - fake) / 2)))

    if dec_fake and dec_auth:
        return (Verdict.UNVERIFIED, 50,
                "Sources disagree: some evidence says fake, other evidence says authentic. "
                "Treat with caution and read the fact-checks directly.")
    if dec_fake:
        return (Verdict.VERIFIED_FAKE, min(score, 10),
                "Strong evidence says this is false or harmful: " + dec_fake[0].detail)
    if dec_auth:
        return (Verdict.AUTHENTICATED, max(score, 90),
                "Strong evidence says this is accurate: " + dec_auth[0].detail)
    if auth >= AUTH_THRESHOLD and fake < FAKE_CEILING_FOR_AUTH:
        return (Verdict.AUTHENTICATED, score,
                "A trusted source and independent coverage support this, and no red flags were found.")
    if fake >= 40:
        return (Verdict.UNVERIFIED, score,
                "Several warning signs, but no fact-checker or blocklist has confirmed it is false. "
                "Be skeptical before sharing.")
    return (Verdict.UNVERIFIED, score,
            "Not enough evidence either way. Look for the original source and for coverage "
            "by independent outlets before trusting or sharing it.")
