"""Dataset loaders and text cleaning.

We do NOT bundle any dataset. Download one yourself (see README) and point the
trainer at it. Supported layouts:

* liar  : LIAR tab-separated files (train.tsv). Short political claims, six-way labels.
          Close to the length of a headline, so it suits this app best.
* isot  : ISOT-style folder with Fake.csv and True.csv (columns title, text).
* politifact, gossipcop : FakeNewsNet CSVs (titles of PolitiFact / GossipCop articles).
* coaid : CoAID COVID-19 folders (article rows only, see load_coaid).
* csv   : any CSV with a text column and a 0/1 label column (1 = fake).
"""
import csv
import re
import sys
import unicodedata
from pathlib import Path

csv.field_size_limit(sys.maxsize)  # FakeNewsNet has huge tweet-id fields

FAKE, REAL = 1, 0

# LIAR labels. "half-true" is dropped by default because it is neither side.
LIAR_MAP = {"pants-fire": FAKE, "false": FAKE, "barely-true": FAKE,
            "mostly-true": REAL, "true": REAL}

# Source "tells" that let a model cheat by recognising the outlet, not the content.
_LEAKS = [
    re.compile(r"^[A-Z][A-Z .,/'-]{2,40}\((Reuters|AP|AFP)\)\s*[-–]?\s*", re.M),
    re.compile(r"\((Reuters|AP|AFP)\)", re.I),
    re.compile(r"https?://\S+"),
    re.compile(r"\b(pic\.twitter\.com|twitter\.com)/\S+", re.I),
    re.compile(r"\b(featured image|image via|photo by|getty images?)\b[^.\n]*", re.I),
    re.compile(r"@\w+"),
]


# Typographic characters differ by outlet (ISOT fake titles use curly quotes, real ones do not),
# and some CoAID rows contain Mac-Roman mojibake. Fold everything to plain ASCII punctuation.
_FOLD = str.maketrans({
    "\u2018": "'", "\u2019": "'", "\u201c": '"', "\u201d": '"', "\u2013": "-", "\u2014": "-",
    "\u2026": "...", "\u00a0": " ",
    "\u00d2": '"', "\u00d3": '"', "\u00d4": "'", "\u00d5": "'", "\u00d0": "-", "\u00d1": "-",
})
# Trailing outlet names like "... - Variety" or "... | Gossip Cop" give away the source.
_SUFFIX = re.compile(r"\s+[-|]\s+[A-Z0-9][\w.&' ]{1,28}$")


def clean_text(s: str) -> str:
    s = s.translate(_FOLD)
    s = unicodedata.normalize("NFKD", s).encode("ascii", "ignore").decode("ascii")
    for rx in _LEAKS:
        s = rx.sub(" ", s)
    s = re.sub(r"\s+", " ", s).strip()
    stripped = _SUFFIX.sub("", s)
    return stripped if len(stripped) >= 20 else s


def load_liar(path: str | Path) -> tuple[list[str], list[int]]:
    """A single .tsv, or a folder: then train, valid and test are all read as one source."""
    path = Path(path)
    files = [path] if path.is_file() else [path / n for n in ("train.tsv", "valid.tsv", "test.tsv")]
    X, y = [], []
    for file in files:
        with open(file, encoding="utf-8", newline="") as f:
            for row in csv.reader(f, delimiter="\t"):
                if len(row) < 3:
                    continue
                label, text = row[1].strip().lower(), row[2]
                if label in LIAR_MAP:
                    X.append(clean_text(text))
                    y.append(LIAR_MAP[label])
    return X, y


def load_isot(folder: str | Path, use_body: bool = False) -> tuple[list[str], list[int]]:
    folder = Path(folder)
    X, y = [], []
    for fname, label in (("Fake.csv", FAKE), ("True.csv", REAL)):
        with open(folder / fname, encoding="utf-8", newline="") as f:
            for row in csv.DictReader(f):
                text = row.get("title", "")
                if use_body:
                    text += ". " + row.get("text", "")
                X.append(clean_text(text))
                y.append(label)
    return X, y


def load_csv(path: str | Path, text_col: str = "text", label_col: str = "label") -> tuple[list[str], list[int]]:
    X, y = [], []
    with open(path, encoding="utf-8", newline="") as f:
        for row in csv.DictReader(f):
            lab = str(row[label_col]).strip()
            if lab not in ("0", "1"):
                raise ValueError(f"label column must be 0 or 1, got {lab!r}")
            X.append(clean_text(row[text_col]))
            y.append(int(lab))
    return X, y


def load_fakenewsnet(folder: str | Path, site: str) -> tuple[list[str], list[int]]:
    """FakeNewsNet: {site}_fake.csv and {site}_real.csv, column 'title'."""
    X, y = [], []
    for kind, label in (("fake", FAKE), ("real", REAL)):
        with open(Path(folder) / f"{site}_{kind}.csv", encoding="utf-8", newline="") as f:
            for row in csv.DictReader(f):
                X.append(clean_text(row.get("title", "")))
                y.append(label)
    return X, y


def load_coaid(folder: str | Path) -> tuple[list[str], list[int]]:
    """CoAID. Only rows of type 'article' are used. Fake 'post' rows are social-media claims,
    real rows are news headlines; mixing genres would teach the model genre, not misinformation."""
    X, y = [], []
    for kind, label in (("Fake", FAKE), ("Real", REAL)):
        for path in sorted(Path(folder).glob(f"*/News{kind}COVID-19.csv")):
            with open(path, encoding="utf-8-sig", newline="") as f:
                for row in csv.DictReader(f):
                    if row.get("type") == "article":
                        X.append(clean_text(row.get("title", "")))
                        y.append(label)
    return X, y


def load(spec: str, **kw) -> tuple[list[str], list[int]]:
    """spec looks like 'liar:path/train.tsv', 'isot:folder', 'politifact:folder',
    'gossipcop:folder', 'coaid:folder' or 'csv:path/file.csv'."""
    kind, _, path = spec.partition(":")
    loaders = {"liar": load_liar, "isot": load_isot, "csv": load_csv, "coaid": load_coaid,
               "politifact": lambda p: load_fakenewsnet(p, "politifact"),
               "gossipcop": lambda p: load_fakenewsnet(p, "gossipcop")}
    if kind not in loaders or not path:
        raise ValueError("dataset spec must look like kind:PATH, kind one of " + ", ".join(loaders))
    return loaders[kind](path, **kw)


def dedupe(X: list[str], y: list[int], min_len: int = 15,
           seen: set | None = None) -> tuple[list[str], list[int]]:
    """Drop short and duplicate texts. Duplicates across train and test inflate scores.
    Pass one shared `seen` set to also remove duplicates across different sources."""
    seen = set() if seen is None else seen
    ox, oy = [], []
    for t, l in zip(X, y):
        key = t.lower()
        if len(t) < min_len or key in seen:
            continue
        seen.add(key)
        ox.append(t)
        oy.append(l)
    return ox, oy
