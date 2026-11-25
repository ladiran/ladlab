"""Turns the trained models' output into evidence. Deliberately weak: a model that reads
writing style cannot know whether a claim is true, so it can nudge but never decide."""
import asyncio
import statistics

from ..ml import data, model
from ..models import Evidence

MAX_WEIGHT = 15
WEAK_AUC = 0.65  # below this on an outside source, the model barely counts


async def check(client, text: str) -> list[Evidence]:  # client unused, kept for a uniform provider signature
    text = data.clean_text(text)
    if len(text) < 15:
        return []
    r = await asyncio.to_thread(model.score, text)  # sklearn is CPU-bound, keep it off the event loop
    p, t, per = r["p_fake"], r["threshold"], r["per_model"]
    agree_fake = all(v >= t for v in per.values())
    agree_real = all(v < 1 - t for v in per.values())
    ens = r["card"]["metrics"]["test"]["ensemble"]
    mets = r["card"]["metrics"]
    # Prefer leave-one-source-out scores (train without a source, test on it); fall back to extras.
    outside = mets.get("loso") or mets.get("external") or {}
    aucs = [m["roc_auc"] for m in outside.values() if "roc_auc" in m]
    outside_auc = statistics.median(aucs) if aucs else None
    cap = MAX_WEIGHT
    if outside_auc is None:
        caveat = " It was never tested on a news source it did not train on, so treat it as rough."
        cap = 8
    elif outside_auc < WEAK_AUC:
        caveat = " That is weak, so its opinion is barely counted."
        cap = 4
    else:
        caveat = ""
    # Quote the outside-source score when we have it; the same-mix accuracy flatters the model.
    quality = (f"median AUC {outside_auc:.2f} on unseen sources" if outside_auc is not None
               else f"accuracy {ens['balanced_accuracy']:.0%} on its own training mix")
    detail = (f"Writing-style models estimate a {p:.0%} chance this reads like known fake news "
              f"({quality}).{caveat} It judges wording, not facts.")
    if agree_fake:
        return [Evidence(source="classifier", signal="style_fake", direction="fake",
                         weight=cap if p >= 0.8 else cap // 2, detail=detail)]
    if agree_real:
        return [Evidence(source="classifier", signal="style_real", direction="authentic",
                         weight=min(3, cap), detail=detail)]
    return [Evidence(source="classifier", signal="style_mixed", direction="neutral", detail=detail)]
