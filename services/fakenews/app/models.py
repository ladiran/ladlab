from enum import Enum
from typing import Literal, Optional
from pydantic import BaseModel, Field


class Verdict(str, Enum):
    VERIFIED_FAKE = "verified_fake"
    UNVERIFIED = "unverified"
    AUTHENTICATED = "authenticated"


class CheckRequest(BaseModel):
    title: Optional[str] = Field(None, max_length=500)
    url: Optional[str] = Field(None, max_length=2000)
    handle: Optional[str] = Field(None, max_length=300)
    text: Optional[str] = Field(None, max_length=5000)


class Evidence(BaseModel):
    source: str
    signal: str
    direction: Literal["fake", "authentic", "neutral"]
    weight: int = 0
    decisive: bool = False
    detail: str = ""
    link: Optional[str] = None


class CheckResponse(BaseModel):
    verdict: Verdict
    score: int  # 0 (very doubtful) to 100 (very credible)
    summary: str
    evidence: list[Evidence]
    skipped: list[str] = []
