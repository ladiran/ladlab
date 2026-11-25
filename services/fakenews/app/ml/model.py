"""Load a trained model and score text. Missing model is a normal state, not an error."""
import os
import warnings
from functools import lru_cache
from pathlib import Path

import joblib
import sklearn

DEFAULT_PATH = Path(__file__).parent / "artifacts" / "model.joblib"


class ModelUnavailable(RuntimeError):
    pass


@lru_cache(maxsize=1)
def _load(path: str) -> dict:
    p = Path(path)
    if not p.is_file():
        raise ModelUnavailable("no trained model found (run python -m app.ml.train)")
    # joblib files are pickles: only ever load a file you trained yourself.
    with warnings.catch_warnings():
        warnings.simplefilter("error")  # sklearn version-mismatch warnings become errors
        try:
            bundle = joblib.load(p)
        except Exception as e:  # noqa: BLE001
            raise ModelUnavailable(f"model could not be loaded ({type(e).__name__}); retrain it") from e
    if bundle["card"].get("sklearn") != sklearn.__version__:
        raise ModelUnavailable("model was trained with a different scikit-learn version; retrain it")
    return bundle


def get_bundle() -> dict:
    return _load(os.getenv("FAKENEWS_MODEL_PATH", str(DEFAULT_PATH)))


def score(text: str) -> dict:
    """Return per-model and mean probability that the text is fake (0..1), plus the card."""
    b = get_bundle()
    per = {name: float(m.predict_proba([text])[0][1]) for name, m in b["models"].items()}
    return {"p_fake": sum(per.values()) / len(per), "per_model": per,
            "threshold": b["card"]["threshold"], "card": b["card"]}
