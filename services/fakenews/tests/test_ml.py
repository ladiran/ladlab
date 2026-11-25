"""Plumbing tests only. The toy data below is SYNTHETIC and says nothing about real accuracy."""
import csv
import random

import pytest

from app.ml import data, model, train
from app.providers import classifier

random.seed(0)
_FAKE_A = ["SHOCKING", "Miracle", "Secret", "Banned", "Exposed", "Unbelievable", "Leaked"]
_FAKE_B = ["cure doctors hate", "truth the elites hide", "proof they lied", "plot to silence you", "trick melts fat"]
_REAL_A = ["Council approves", "Committee reviews", "Ministry publishes", "Board schedules", "Agency releases"]
_REAL_B = ["quarterly budget report", "road maintenance plan", "annual housing figures", "new library hours", "water testing results"]


def toy(n=150):
    X, y = [], []
    for i in range(n):
        X.append(f"{random.choice(_FAKE_A)}!! {random.choice(_FAKE_B)} number {i}")
        y.append(1)
        X.append(f"{random.choice(_REAL_A)} {random.choice(_REAL_B)} item {i}")
        y.append(0)
    return X, y


def write_csv(path, X, y):
    with open(path, "w", newline="", encoding="utf-8") as f:
        w = csv.writer(f)
        w.writerow(["text", "label"])
        w.writerows(zip(X, y))


@pytest.fixture
def trained(tmp_path, monkeypatch):
    X, y = toy()
    write_csv(tmp_path / "a.csv", X, y)
    random.seed(1)
    X2, y2 = toy(60)
    write_csv(tmp_path / "ext.csv", X2, y2)
    out = tmp_path / "art"
    card = train.train([f"csv:{tmp_path/'a.csv'}"], [f"csv:{tmp_path/'ext.csv'}"], out)
    monkeypatch.setenv("FAKENEWS_MODEL_PATH", str(out / "model.joblib"))
    model._load.cache_clear()
    yield card
    model._load.cache_clear()


def test_clean_text_removes_source_tells():
    s = "WASHINGTON (Reuters) - The bill passed. Image via Getty Images https://t.co/x @bob"
    out = data.clean_text(s)
    assert "Reuters" not in out and "http" not in out and "@bob" not in out and "Getty" not in out
    assert "bill passed" in out

def test_dedupe_drops_dupes_and_short():
    X, y = data.dedupe(["Hello world this is long", "hello world this is long", "short"], [0, 0, 1])
    assert X == ["Hello world this is long"]

def test_liar_loader(tmp_path):
    rows = [["1.json", "pants-fire", "Statement one is long enough"], ["2.json", "true", "Statement two is long enough"],
            ["3.json", "half-true", "Statement three is long enough"], ["bad"]]
    with open(tmp_path / "t.tsv", "w", encoding="utf-8", newline="") as f:
        csv.writer(f, delimiter="\t").writerows(rows)
    X, y = data.load_liar(tmp_path / "t.tsv")
    assert y == [1, 0] and len(X) == 2  # half-true dropped, malformed row skipped

def test_isot_loader(tmp_path):
    for name, title in (("Fake.csv", "Fake title here"), ("True.csv", "True title here")):
        with open(tmp_path / name, "w", encoding="utf-8", newline="") as f:
            w = csv.DictWriter(f, fieldnames=["title", "text"]); w.writeheader(); w.writerow({"title": title, "text": "body"})
    X, y = data.load_isot(tmp_path)
    assert sorted(y) == [0, 1]

def test_csv_rejects_bad_labels(tmp_path):
    write_csv(tmp_path / "b.csv", ["x" * 20], ["maybe"])
    with pytest.raises(ValueError):
        data.load_csv(tmp_path / "b.csv")

def test_bad_spec():
    with pytest.raises(ValueError):
        data.load("nonsense")

def test_train_produces_card_and_external_metrics(trained):
    assert trained["metrics"]["test"]["ensemble"]["accuracy"] > 0.9  # toy data is trivially separable
    assert any(k.startswith("csv:") for k in trained["metrics"]["external"])
    assert {"word_logreg", "char_svm"} <= set(trained["metrics"]["test"])

def test_score_separates_toy_classes(trained):
    assert model.score("SHOCKING!! Secret cure doctors hate number 3")["p_fake"] > 0.5
    assert model.score("Council approves quarterly budget report item 3")["p_fake"] < 0.5

@pytest.mark.asyncio
async def test_classifier_evidence_is_weak(trained):
    out = await classifier.check(None, "SHOCKING!! Banned trick melts fat number 9")
    assert out and not any(e.decisive for e in out) and out[0].weight <= classifier.MAX_WEIGHT

@pytest.mark.asyncio
async def test_missing_model_is_reported_not_crashing(tmp_path, monkeypatch):
    monkeypatch.setenv("FAKENEWS_MODEL_PATH", str(tmp_path / "nope.joblib"))
    model._load.cache_clear()
    with pytest.raises(model.ModelUnavailable):
        await classifier.check(None, "Some headline that is long enough")
    model._load.cache_clear()

def test_too_few_examples_refuses(tmp_path):
    write_csv(tmp_path / "s.csv", [f"example text number {i} long" for i in range(10)], [i % 2 for i in range(10)])
    with pytest.raises(SystemExit):
        train.train([f"csv:{tmp_path/'s.csv'}"], [], tmp_path / "o")


# ---- mixed-source additions
import random as _random


def test_clean_text_folds_quotes_mojibake_and_suffix():
    assert data.clean_text("New Year’s Eve message goes viral online") == "New Year's Eve message goes viral online"
    assert data.clean_text("Wisconsin is Òclearly seeing a declineÓ now") == 'Wisconsin is "clearly seeing a decline" now'
    assert data.clean_text("Full List of 2018 Oscar Nominations – Variety") == "Full List of 2018 Oscar Nominations"
    assert data.clean_text("Trump - Biden") == "Trump - Biden"  # short remainder is kept, not stripped

def test_balance_equalises_classes_and_caps():
    X = [f"t{i}" for i in range(100)]
    y = [1] * 90 + [0] * 10
    bx, by = train.balance(X, y, cap=1000, rng=_random.Random(0))
    assert sum(by) == 10 and len(by) == 20
    bx, by = train.balance(X, y, cap=6, rng=_random.Random(0))
    assert len(by) == 6 and sum(by) == 3

def test_fakenewsnet_loader(tmp_path):
    for kind, title in (("fake", "A made up story about cats"), ("real", "A true report about dogs")):
        with open(tmp_path / f"gossipcop_{kind}.csv", "w", encoding="utf-8", newline="") as f:
            w = csv.DictWriter(f, fieldnames=["id", "news_url", "title", "tweet_ids"])
            w.writeheader(); w.writerow({"id": "1", "news_url": "x", "title": title, "tweet_ids": "1\t2" * 50000})
    X, y = data.load(f"gossipcop:{tmp_path}")
    assert sorted(y) == [0, 1] and len(X) == 2  # huge tweet_ids field must not break csv

def test_coaid_loader_uses_articles_only(tmp_path):
    d = tmp_path / "05-01-2020"; d.mkdir()
    for kind, rows in (("Fake", [("article", "Fake article headline here"), ("post", "Facebook claim post text")]),
                       ("Real", [("article", "Real article headline here")])):
        with open(d / f"News{kind}COVID-19.csv", "w", encoding="utf-8-sig", newline="") as f:
            w = csv.DictWriter(f, fieldnames=["", "type", "title"]); w.writeheader()
            for t, title in rows:
                w.writerow({"": "0", "type": t, "title": title})
    X, y = data.load_coaid(tmp_path)
    assert sorted(y) == [0, 1] and not any("claim post" in t for t in X)

def test_dedupe_across_sources():
    seen = set()
    a = data.dedupe(["Same headline in two sources"], [1], seen=seen)
    b = data.dedupe(["same headline in two sources", "A different headline entirely"], [0, 0], seen=seen)
    assert len(a[0]) == 1 and b[0] == ["A different headline entirely"]

@pytest.fixture
def mixed(tmp_path, monkeypatch):
    """Three toy sources with different styles so leave-one-source-out has something to do."""
    r = _random.Random(3)
    specs = []
    for k, (fa, ra) in enumerate([(_FAKE_A, _REAL_A), (["WOW", "Insane", "Crazy"], ["Mayor", "Senate", "Court"]),
                                  (["Hoax", "Lies", "Scandal"], ["Study", "Report", "Survey"])]):
        X, y = [], []
        for i in range(120):
            X.append(f"{r.choice(fa)}!! {r.choice(_FAKE_B)} variant {k}-{i}"); y.append(1)
            X.append(f"{r.choice(ra)} {r.choice(_REAL_B)} variant {k}-{i}"); y.append(0)
        write_csv(tmp_path / f"s{k}.csv", X, y)
        specs.append(f"csv:{tmp_path / f's{k}.csv'}")
    out = tmp_path / "art"
    card = train.train(specs, [], out, max_per_source=100)
    monkeypatch.setenv("FAKENEWS_MODEL_PATH", str(out / "model.joblib"))
    model._load.cache_clear()
    yield card, specs
    model._load.cache_clear()

def test_mixed_training_balances_and_runs_loso(mixed):
    card, specs = mixed
    assert set(card["metrics"]["loso"]) == set(specs)
    assert all(v["rows_used"] <= 100 for v in card["sources"].values())  # cap respected
    assert set(card["metrics"]["test_by_source"]) <= set(specs)
    assert all("roc_auc" in v for v in card["metrics"]["loso"].values())

@pytest.mark.asyncio
async def test_classifier_weight_gated_by_loso_auc(mixed):
    card, _ = mixed
    out = await classifier.check(None, "SHOCKING!! Banned trick melts fat number 9")
    if out[0].direction == "fake":
        med = sorted(v["roc_auc"] for v in card["metrics"]["loso"].values())[1]
        assert out[0].weight <= (classifier.MAX_WEIGHT if med >= classifier.WEAK_AUC else 4)
    assert not any(e.decisive for e in out)
