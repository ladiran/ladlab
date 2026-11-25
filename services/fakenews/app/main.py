from contextlib import asynccontextmanager
from pathlib import Path

import httpx
from fastapi import FastAPI, HTTPException
from fastapi.responses import FileResponse
from fastapi.staticfiles import StaticFiles

from .analyzer import analyze
from .models import CheckRequest, CheckResponse

STATIC = Path(__file__).parent / "static"


@asynccontextmanager
async def lifespan(app: FastAPI):
    app.state.client = httpx.AsyncClient(timeout=httpx.Timeout(10.0, connect=5.0),
                                         headers={"User-Agent": "FakeNewsCheckerBot/0.1"})
    yield
    await app.state.client.aclose()


app = FastAPI(title="Fake News Checker", version="0.1.0", lifespan=lifespan)


@app.post("/api/check", response_model=CheckResponse)
async def check(req: CheckRequest) -> CheckResponse:
    if not any((req.title, req.url, req.handle, req.text)):
        raise HTTPException(422, "Provide at least one of: title, url, handle, text.")
    return await analyze(req, app.state.client)


@app.get("/api/health")
async def health() -> dict:
    """Used by the host website to show whether the checker is online."""
    return {"status": "ok", "version": app.version}


@app.get("/")
async def index() -> FileResponse:
    return FileResponse(STATIC / "index.html")


app.mount("/static", StaticFiles(directory=STATIC), name="static")
