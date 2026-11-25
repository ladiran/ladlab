"""Safe page fetching. Users supply URLs, so we must not let them point us at
internal network addresses (SSRF)."""
import asyncio
import ipaddress
import re
import socket
from urllib.parse import urlparse, urljoin

import httpx

MAX_BYTES = 500_000
MAX_REDIRECTS = 3
UA = "FakeNewsCheckerBot/0.1 (+open-source research tool)"


class UnsafeURL(ValueError):
    pass


def normalise_url(raw: str) -> str:
    raw = raw.strip()
    if not re.match(r"^[a-zA-Z][a-zA-Z0-9+.-]*://", raw):
        raw = "https://" + raw
    return raw


async def assert_public(url: str) -> None:
    p = urlparse(url)
    if p.scheme not in ("http", "https") or not p.hostname:
        raise UnsafeURL("Only http(s) URLs are allowed.")
    loop = asyncio.get_running_loop()
    try:
        infos = await loop.getaddrinfo(p.hostname, p.port or 443, type=socket.SOCK_STREAM)
    except socket.gaierror as e:
        raise UnsafeURL(f"Could not resolve host: {p.hostname}") from e
    for info in infos:
        ip = ipaddress.ip_address(info[4][0])
        if not ip.is_global:
            raise UnsafeURL("URL resolves to a non-public address.")


async def fetch_page(client: httpx.AsyncClient, url: str) -> str:
    """Fetch HTML with SSRF checks on every hop, size and redirect limits."""
    for _ in range(MAX_REDIRECTS + 1):
        await assert_public(url)
        async with client.stream("GET", url, headers={"User-Agent": UA},
                                 follow_redirects=False) as r:
            if r.is_redirect and "location" in r.headers:
                url = urljoin(url, r.headers["location"])
                continue
            r.raise_for_status()
            ctype = r.headers.get("content-type", "")
            if "html" not in ctype and "xml" not in ctype:
                return ""
            buf = b""
            async for chunk in r.aiter_bytes():
                buf += chunk
                if len(buf) >= MAX_BYTES:
                    break
            return buf.decode(r.encoding or "utf-8", errors="replace")
    raise UnsafeURL("Too many redirects.")


_TITLE_RE = re.compile(r"<title[^>]*>(.*?)</title>", re.I | re.S)
_OG_RE = re.compile(
    r'<meta[^>]+property=["\']og:title["\'][^>]+content=["\']([^"\']+)["\']', re.I)


def extract_title(html: str) -> str:
    m = _OG_RE.search(html) or _TITLE_RE.search(html)
    if not m:
        return ""
    import html as htmllib
    return re.sub(r"\s+", " ", htmllib.unescape(m.group(1))).strip()[:300]
