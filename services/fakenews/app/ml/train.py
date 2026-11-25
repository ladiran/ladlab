"""Train the classifiers.

    python -m app.ml.train --data liar:data/liar/train.tsv --extra isot:data/isot

Two different models vote, so one model's blind spot is less likely to decide the result:
  1. word 1-2 gram TF-IDF + logistic regression      (what words are used)
  2. char 3-5 gram TF-IDF + calibrated linear SVM    (how it is written: caps, punctuation)
Training sources are balanced fake/real inside each source and capped in size, so no single
source dominates. Metrics are measured on data the models never saw:
  test   held-out slice of the pooled sources (easy: same sources as training)
  loso   leave-one-source-out: train on all other sources, test on the one left out.
         This is the number to trust, because it measures generalisation to a new outlet.
  extra  --extra datasets, never trained on.
"""
import argparse
import json
import platform
import random
from datetime import datetime, timezone
from pathlib import Path

import joblib
import numpy as np
import sklearn
from sklearn.calibration import CalibratedClassifierCV
from sklearn.feature_extraction.text import TfidfVectorizer
from sklearn.linear_model import LogisticRegression
from sklearn.metrics import accuracy_score, balanced_accuracy_score, f1_score, precision_score, recall_score, roc_auc_score
from sklearn.model_selection import train_test_split
from sklearn.pipeline import Pipeline
from sklearn.svm import LinearSVC

from . import data

ARTIFACT_DIR = Path(__file__).parent / "artifacts"


def build_models(seed: int = 42) -> dict[str, Pipeline]:
    return {
        "word_logreg": Pipeline([
            ("tfidf", TfidfVectorizer(ngram_range=(1, 2), min_df=2, max_df=0.9, sublinear_tf=True,
                                      lowercase=True, strip_accents="unicode")),
            ("clf", LogisticRegression(C=2.0, max_iter=1000, class_weight="balanced", random_state=seed)),
        ]),
        "char_svm": Pipeline([
            ("tfidf", TfidfVectorizer(analyzer="char_wb", ngram_range=(3, 5), min_df=2, sublinear_tf=True,
                                      lowercase=False)),  # keep case: ALL CAPS is a signal
            ("clf", CalibratedClassifierCV(LinearSVC(C=0.5, class_weight="balanced", random_state=seed),
                                           cv=3)),
        ]),
    }


def evaluate(y_true, p_fake, threshold: float = 0.5) -> dict:
    pred = (np.asarray(p_fake) >= threshold).astype(int)
    out = {"n": int(len(y_true)),
           "fake_share": round(float(np.mean(y_true)), 3),
           "accuracy": round(accuracy_score(y_true, pred), 4),
           "balanced_accuracy": round(balanced_accuracy_score(y_true, pred), 4),
           "precision_fake": round(precision_score(y_true, pred, zero_division=0), 4),
           "recall_fake": round(recall_score(y_true, pred, zero_division=0), 4),
           "f1_fake": round(f1_score(y_true, pred, zero_division=0), 4)}
    if len(set(y_true)) == 2:
        out["roc_auc"] = round(roc_auc_score(y_true, p_fake), 4)
    return out


def ensemble_proba(models: dict, X: list[str]) -> tuple[np.ndarray, dict[str, np.ndarray]]:
    per = {name: m.predict_proba(X)[:, 1] for name, m in models.items()}
    return np.mean(list(per.values()), axis=0), per


def load_sources(specs: list[str], use_body: bool) -> dict[str, tuple[list[str], list[int]]]:
    """Load each source, cleaned, with duplicates removed across ALL sources."""
    seen: set = set()
    out = {}
    for spec in specs:
        kw = {"use_body": use_body} if spec.startswith("isot:") else {}
        X, y = data.dedupe(*data.load(spec, **kw), seen=seen)
        if len(set(y)) < 2:
            raise SystemExit(f"Source {spec} has only one class after cleaning.")
        out[spec] = (X, y)
    return out


def balance(X: list[str], y: list[int], cap: int, rng: random.Random) -> tuple[list[str], list[int]]:
    """Equal fake and real inside one source, at most cap rows in total.
    Without this, a source's topic would predict its label (GossipCop is mostly real, so
    'celebrity gossip' would mean 'real') and the model would learn topics, not fakery."""
    idx = {0: [], 1: []}
    for i, label in enumerate(y):
        idx[label].append(i)
    n = min(len(idx[0]), len(idx[1]), cap // 2)
    pick = rng.sample(idx[0], n) + rng.sample(idx[1], n)
    return [X[i] for i in pick], [y[i] for i in pick]


def pool(sources: dict, names: list[str], cap: int, rng: random.Random):
    X, y, keys = [], [], []
    for name in names:
        xs, ys = balance(*sources[name], cap, rng)
        X += xs
        y += ys
        keys += [f"{name}|{label}" for label in ys]
    return X, y, keys


def pick_threshold(y_va, p_va) -> float:
    # Balanced accuracy, not F1: F1 on the fake class rewards flagging everything as fake.
    return float(max(np.linspace(0.3, 0.7, 41), key=lambda t: balanced_accuracy_score(y_va, p_va >= t)))


def fit_all(X_tr, y_tr, seed):
    models = build_models(seed)
    for m in models.values():
        m.fit(X_tr, y_tr)
    return models


def take(items: list, idx) -> list:
    return [items[i] for i in idx]


def train(datasets: list[str], extras: list[str], out_dir: Path, seed: int = 42,
          use_body: bool = False, max_per_source: int = 6000, loso: bool = True) -> dict:
    rng = random.Random(seed)
    sources = load_sources(datasets, use_body)
    names = list(sources)
    X, y, keys = pool(sources, names, max_per_source, rng)
    if len(set(y)) < 2 or min(np.bincount(y)) < 20:
        raise SystemExit("Need at least 20 examples of each class to train.")

    idx = list(range(len(X)))
    i_tr, i_tmp = train_test_split(idx, test_size=0.3, stratify=keys, random_state=seed)
    i_va, i_te = train_test_split(i_tmp, test_size=0.5, stratify=take(keys, i_tmp), random_state=seed)

    models = fit_all(take(X, i_tr), take(y, i_tr), seed)
    p_va, _ = ensemble_proba(models, take(X, i_va))
    best_t = pick_threshold(take(y, i_va), p_va)

    X_te, y_te = take(X, i_te), take(y, i_te)
    p_te, per_te = ensemble_proba(models, X_te)
    metrics = {"test": {"ensemble": evaluate(y_te, p_te, best_t),
                        **{k: evaluate(y_te, v, 0.5) for k, v in per_te.items()}}}
    # Per-source breakdown of the same held-out split.
    by_src = {}
    for name in names:
        sel = [j for j, i in enumerate(i_te) if keys[i].startswith(name + "|")]
        if len(set(y_te[j] for j in sel)) == 2:
            by_src[name] = evaluate([y_te[j] for j in sel], p_te[sel], best_t)
    metrics["test_by_source"] = by_src

    if loso and len(names) >= 2:
        # Train on every source except one, test on the one left out. This is the number to trust.
        metrics["loso"] = {}
        for held in names:
            others = [n for n in names if n != held]
            Xo, yo, ko = pool(sources, others, max_per_source, rng)
            j_tr, j_va = train_test_split(list(range(len(Xo))), test_size=0.15, stratify=ko,
                                          random_state=seed)
            m = fit_all(take(Xo, j_tr), take(yo, j_tr), seed)
            pv, _ = ensemble_proba(m, take(Xo, j_va))
            t = pick_threshold(take(yo, j_va), pv)
            xh, yh = sources[held]
            ph, _ = ensemble_proba(m, xh)
            metrics["loso"][held] = evaluate(yh, ph, t)

    for spec in extras:  # additional sources that are never trained on
        kw = {"use_body": use_body} if spec.startswith("isot:") else {}
        xe, ye = data.dedupe(*data.load(spec, **kw))
        pe, _ = ensemble_proba(models, xe)
        metrics.setdefault("external", {})[spec] = evaluate(ye, pe, best_t)

    models = fit_all(X, y, seed)  # final models use every pooled example
    card = {
        "trained_at": datetime.now(timezone.utc).isoformat(timespec="seconds"),
        "datasets": datasets, "external_datasets": extras, "use_body": use_body,
        "max_per_source": max_per_source,
        "sources": {n: {"rows_after_cleaning": len(sources[n][1]),
                        "fake_share": round(sum(sources[n][1]) / len(sources[n][1]), 3),
                        "rows_used": sum(1 for k in keys if k.startswith(n + "|"))} for n in names},
        "n_train_total": len(X), "threshold": round(best_t, 3), "metrics": metrics,
        "sklearn": sklearn.__version__, "python": platform.python_version(),
        "note": "Writing-style classifier. It cannot check facts.",
    }
    out_dir.mkdir(parents=True, exist_ok=True)
    joblib.dump({"models": models, "card": card}, out_dir / "model.joblib", compress=3)
    (out_dir / "model_card.json").write_text(json.dumps(card, indent=2), encoding="utf-8")
    return card


def main() -> None:
    ap = argparse.ArgumentParser(description=__doc__, formatter_class=argparse.RawDescriptionHelpFormatter)
    ap.add_argument("--data", nargs="+", required=True, help="training sets, e.g. liar:train.tsv")
    ap.add_argument("--extra", nargs="*", default=[], help="held-out sets from another source")
    ap.add_argument("--out", type=Path, default=ARTIFACT_DIR)
    ap.add_argument("--use-body", action="store_true", help="ISOT only: include article body, not just title")
    ap.add_argument("--seed", type=int, default=42)
    ap.add_argument("--max-per-source", type=int, default=6000,
                    help="cap rows per source (split evenly fake/real) so big sources do not dominate")
    ap.add_argument("--no-loso", action="store_true", help="skip leave-one-source-out evaluation")
    a = ap.parse_args()
    card = train(a.data, a.extra, a.out, a.seed, a.use_body, a.max_per_source, not a.no_loso)
    print(json.dumps(card["metrics"], indent=2))
    print(f"Saved to {a.out}")


if __name__ == "__main__":
    main()
