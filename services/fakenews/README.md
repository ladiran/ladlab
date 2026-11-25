# Fake News Checker

> This copy lives inside the ClearSky Academy website at `services/fakenews/` and is shown to visitors at `/fakenews`. Run it with `run_windows.bat` or `run.sh`, which keep it on 127.0.0.1. See section 3h of the website's main README.

Paste a headline, a link, or a social media handle. The app returns one of three verdicts:

| Verdict | Meaning |
|---|---|
| **Verified fake** | A fact-checker rates it false, Google Safe Browsing flags the link, or the domain is on your unreliable list |
| **Authenticated** | Fact-checkers rate it true, or a trusted outlet plus independent coverage support it with no red flags |
| **Unverified** | Anything else. This is the honest default, not an error |

The app is deliberately conservative. It would rather say "unverified" than guess.

## Why Python
Python has the best libraries for text and machine learning (so a classifier can be added later),
FastAPI makes a small async API easy (all checks run in parallel), and one codebase serves a
web app now. The same API can later back a mobile app or a desktop wrapper.

## Free data sources
| Source | Key needed | Used for |
|---|---|---|
| Google Fact Check Tools API | Free key | Published fact-checks of the claim |
| Google Safe Browsing | Free key (non-commercial) | Malicious or phishing links |
| GDELT DOC 2.0 | None | Whether trusted outlets cover the same story |
| RDAP (rdap.org) | None | Domain age |
| Wayback Machine availability API | None | Archive history |
| Bluesky public API | None | Account age and activity (other platforms have no free API) |

Without keys the app still runs. It just skips those checks and says so.

## Run it
```bash
pip install -r requirements.txt
cp .env.example .env   # then add your free keys, and export them
export FACTCHECK_API_KEY=... SAFE_BROWSING_API_KEY=...
python -m uvicorn app.main:app --reload
```
Open http://localhost:8000. Run the tests with `python -m pytest`.

## Trained text classifiers
Two models read the headline and vote. Their opinion is a weak nudge (max 15 points) and can never
decide a verdict alone, because a model that reads writing style cannot know whether a claim is true.

| Model | Looks at |
|---|---|
| Word TF-IDF + logistic regression | Which words and word pairs appear |
| Character TF-IDF + calibrated linear SVM | How it is written: capitals, punctuation, word shapes |

**No trained model ships with the repo.** You need real labelled data, which I could not
download from the build sandbox. Get a dataset and train:

```bash
# Short claims, closest to headlines (recommended as the main training set):
#   LIAR (Wang, 2017) https://doi.org/10.18653/v1/P17-2067
# Full articles from a different source, used ONLY to test generalisation:
#   ISOT (Ahmed, Traore and Saad, 2017) https://doi.org/10.1007/978-3-319-69155-8_9
python -m app.ml.train --data liar:data/liar/train.tsv --extra isot:data/isot
```
Training prints held-out metrics and writes `app/ml/artifacts/model.joblib` plus `model_card.json`.
The `--extra` score is the one to trust: it comes from a news source the models never saw.
If it is far below the in-source score, the models learned the source, not fake news.

### Mixed-source training
Five sources are pooled: LIAR (political claims), ISOT (wire-style articles), PolitiFact and GossipCop
headlines from FakeNewsNet, and CoAID (COVID-19 news articles only).

```bash
python -m app.ml.train --data liar:data/liar isot:data/isot politifact:data/fakenewsnet \
    gossipcop:data/fakenewsnet coaid:data/coaid
```
To stop the models learning shortcuts, each source is balanced to equal fake and real rows and capped
at 6,000 rows (otherwise GossipCop's 76% real share would make "celebrity gossip" mean "real"). Curly
quotes, mojibake, trailing outlet names like "- Variety", links and "(Reuters)" are stripped, because
each of these gave away the source in the raw data. Duplicates are removed across sources.
CoAID fake social-media posts are skipped: they are claims, while its real rows are headlines.

### Measured results (mixed sources, 19,262 training rows)
"Leave one source out" trains on the other four sources and tests on the one left out. That is the
honest measure of how the models treat an outlet they have never seen.

| Source | Same-mix test AUC | Leave-one-source-out AUC | Leave-one-source-out balanced accuracy |
|---|---|---|---|
| LIAR | 0.65 | 0.51 | 51% |
| ISOT | 0.99 | 0.60 | 57% |
| PolitiFact (FakeNewsNet) | 0.84 | 0.71 | 64% |
| GossipCop (FakeNewsNet) | 0.86 | 0.51 | 51% |
| CoAID articles | 0.86 | 0.61 | 57% |

Reading this honestly: pooled same-mix accuracy looks good (78%), but that is mostly the models
recognising the style of sources they trained on. Facing a new outlet they are near chance, apart
from PolitiFact-style headlines (AUC 0.71). More sources did not fix generalisation. The app uses the
median leave-one-source-out AUC (0.60, below the 0.65 bar) to cap the classifier at 4 points.
Datasets are licensed for research use, so `data/` is git-ignored: re-download rather than commit.

Notes:
- The loaders strip source giveaways such as "(Reuters)" and links, which otherwise let a model cheat.
- `half-true` LIAR rows are dropped; `pants-fire`, `false` and `barely-true` count as fake.
- The model file is a pickle. Load only files you trained yourself. A scikit-learn version mismatch
  disables the model with a message instead of crashing.
- Without a model the app runs as before and lists the classifier under "Not checked".

## Running it on an Ubuntu server
The checker listens on `127.0.0.1:8765` only. Never publish that port, and never point a Cloudflare tunnel at it: the website talks to it, visitors do not. Run these on the server, one at a time.

```bash
sudo apt install python3-venv git
sudo useradd --system --home-dir /opt/fakenews --create-home --shell /usr/sbin/nologin fakenews
sudo mkdir -p /opt/fakenews/service
sudo rsync -a --exclude '.env' --exclude '.venv' --exclude '__pycache__' --exclude 'tests' ~/ladlab/services/fakenews/ /opt/fakenews/service/
sudo /usr/bin/python3 -m venv /opt/fakenews/venv
sudo /opt/fakenews/venv/bin/pip install fastapi uvicorn httpx pydantic python-dotenv scikit-learn joblib
sudo /opt/fakenews/venv/bin/pip freeze | sudo tee /opt/fakenews/requirements.lock > /dev/null
sudo chown -R root:root /opt/fakenews
sudo chmod -R go-w /opt/fakenews
```

Free keys (optional but recommended) go in `/etc/fakenews.env`, readable only by root (systemd reads it, the checker never sees the file):

```bash
sudo cp /opt/fakenews/service/.env.example /etc/fakenews.env
sudo nano /etc/fakenews.env
sudo chmod 600 /etc/fakenews.env
```

Start it with the unit file in `deploy/`, then check it:

```bash
sudo cp /opt/fakenews/service/deploy/fakenews.service /etc/systemd/system/fakenews.service
sudo systemctl daemon-reload
sudo systemctl enable --now fakenews
systemctl status fakenews --no-pager
curl -s http://127.0.0.1:8765/api/health
```

The last command should print `{"status":"ok",...}`. If not: `journalctl -u fakenews -n 50 --no-pager`. Finally open **Admin → Settings → Site sections** and tick *Fake news checker is live*.

To update it later: `cd ~/ladlab && git pull`, repeat the `rsync` line above, then `sudo systemctl restart fakenews`. Keep the Python packages patched too: `sudo /opt/fakenews/venv/bin/pip list --outdated`.

## Customising
Edit `app/data/sources.json` to change the trusted, satire and unreliable domain lists.

## Limits
- Headlines are matched to fact-checks by text search, so paraphrased claims can be missed.
- Brand-new stories have no fact-checks yet and will often be "unverified".
- Heuristics (clickbait wording, odd domains) only nudge the result and never decide it alone.
- Trusted-outlet lists are a judgement call. Review them for your audience.
