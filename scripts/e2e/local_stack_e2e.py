#!/usr/bin/env python3
"""
Black-box e2e against local stack (frontend + core + Keycloak).

  make e2e

Expects hosts entries + stack-up + frontend-up. Seeds via Makefile first.
"""
from __future__ import annotations

import base64
import html as html_lib
import json
import re
import sys
import urllib.error
import urllib.parse
import urllib.request
from dataclasses import dataclass, field
from http.cookiejar import CookieJar

FRONTEND = "http://wannabe5.gathering.org:3000"
CORE = "http://wannabe5.gathering.org:8000"
USER = "testbruker"
PASSWORD = "testbruker"

SSR_ERROR_MARKERS = (
    "Application error",
    "Runtime TypeError",
    "Runtime Error",
    "Cannot read properties",
    "Error serializing",
    "Internal Server Error",
    "OIDC callback failed",
    "Invalid OIDC state",
    "Unhandled Runtime Error",
    "Server Error",
)


@dataclass
class Result:
    name: str
    ok: bool
    detail: str = ""


@dataclass
class Client:
    jar: CookieJar = field(default_factory=CookieJar)
    last_url: str = ""

    def _opener(self):
        return urllib.request.build_opener(urllib.request.HTTPCookieProcessor(self.jar))

    def request(
        self,
        method: str,
        url: str,
        *,
        data: dict[str, str] | None = None,
        headers: dict[str, str] | None = None,
        allow_redirects: bool = True,
        json_body: dict | None = None,
    ) -> tuple[int, dict[str, str], bytes, str]:
        body = None
        req_headers = {"User-Agent": "wannabe5-core-e2e/1.0", "Accept": "*/*"}
        if headers:
            req_headers.update(headers)
        if json_body is not None:
            body = json.dumps(json_body).encode()
            req_headers["Content-Type"] = "application/json"
        elif data is not None:
            body = urllib.parse.urlencode(data).encode()
            req_headers["Content-Type"] = "application/x-www-form-urlencoded"

        current, method_cur, body_cur = url, method, body
        for _ in range(16):
            req = urllib.request.Request(current, data=body_cur, headers=req_headers, method=method_cur)
            try:
                with self._opener().open(req, timeout=45) as resp:
                    raw = resp.read()
                    hdrs = {k.lower(): v for k, v in resp.headers.items()}
                    self.last_url = resp.geturl()
                    return resp.status, hdrs, raw, self.last_url
            except urllib.error.HTTPError as e:
                raw = e.read()
                hdrs = {k.lower(): v for k, v in e.headers.items()}
                loc = e.headers.get("Location")
                if allow_redirects and e.code in (301, 302, 303, 307, 308) and loc:
                    current = urllib.parse.urljoin(current, loc)
                    method_cur, body_cur = "GET", None
                    req_headers.pop("Content-Type", None)
                    continue
                self.last_url = current
                return e.code, hdrs, raw, current
        raise RuntimeError(f"too many redirects from {url}")

    def get(self, url: str, **kw):
        return self.request("GET", url, **kw)

    def post_form(self, url: str, data: dict[str, str], **kw):
        return self.request("POST", url, data=data, **kw)

    def cookie(self, name: str) -> str | None:
        for c in self.jar:
            if c.name == name:
                return c.value
        return None

    def auth_headers(self) -> dict[str, str]:
        jwt = self.cookie("wannabe_jwt")
        h = {"Accept": "application/json"}
        if jwt:
            h["Cookie"] = f"wannabe_jwt={jwt}"
        return h


def parse_forms(html: str) -> list[tuple[str, dict[str, str]]]:
    forms = []
    for m in re.finditer(r"<form\b[^>]*>(.*?)</form>", html, re.I | re.S):
        tag = m.group(0)
        action_m = re.search(r'action=["\']([^"\']*)["\']', tag, re.I)
        action = html_lib.unescape(action_m.group(1)) if action_m else ""
        fields: dict[str, str] = {}
        for inp in re.finditer(r"<input\b[^>]*>", tag, re.I):
            chunk = inp.group(0)
            name_m = re.search(r'name=["\']([^"\']+)["\']', chunk, re.I)
            if not name_m:
                continue
            val_m = re.search(r'value=["\']([^"\']*)["\']', chunk, re.I)
            fields[name_m.group(1)] = html_lib.unescape(val_m.group(1)) if val_m else ""
        for btn in re.finditer(r"<button\b[^>]*>", tag, re.I):
            chunk = btn.group(0)
            name_m = re.search(r'name=["\']([^"\']+)["\']', chunk, re.I)
            if not name_m:
                continue
            val_m = re.search(r'value=["\']([^"\']*)["\']', chunk, re.I)
            fields[name_m.group(1)] = html_lib.unescape(val_m.group(1)) if val_m else "Sign In"
        forms.append((action, fields))
    return forms


def check(cond: bool, name: str, detail: str = "") -> Result:
    return Result(name, cond, detail if not cond else "")


def page_ok(code: int, body: bytes, final: str) -> tuple[bool, str]:
    text = body.decode("utf-8", errors="replace")
    if code != 200:
        return False, f"status={code} final={final}"
    for marker in SSR_ERROR_MARKERS:
        if marker in text:
            idx = text.find(marker)
            return False, f"marker={marker!r} near={text[idx : idx + 160]!r} final={final}"
    return True, ""


def json_or_none(body: bytes):
    try:
        return json.loads(body)
    except Exception:
        return None


def oidc_login(c: Client, results: list[Result]) -> str | None:
    login_start = (
        f"{CORE}/api/auth/login?redirect_url="
        + urllib.parse.quote(f"{FRONTEND}/home", safe="")
    )
    code, _, _, url = c.get(login_start, headers={"Accept": "text/html"}, allow_redirects=True)
    results.append(check("keycloak.wannabe5.gathering.org" in (url or ""), "login → Keycloak", f"url={url} status={code}"))

    code, _, html_b, login_url = c.get(url)
    forms = parse_forms(html_b.decode("utf-8", errors="replace"))
    if not forms:
        results.append(Result("keycloak login form", False, "no form"))
        return None

    action, fields = forms[0]
    fields["username"] = USER
    fields["password"] = PASSWORD
    fields.setdefault("login", "Sign In")
    code, _, html_b, after = c.post_form(urllib.parse.urljoin(login_url, action), fields, allow_redirects=True)
    html = html_b.decode("utf-8", errors="replace")
    if "login-actions/consent" in after or re.search(r'name=["\']accept["\']', html, re.I):
        for action, fields in parse_forms(html):
            fields.setdefault("accept", "Yes")
            code, _, html_b, after = c.post_form(urllib.parse.urljoin(after, action), fields, allow_redirects=True)
            break

    jwt = c.cookie("wannabe_jwt")
    results.append(check(bool(jwt), "OIDC sets wannabe_jwt", f"url={after}"))
    results.append(check("/home" in after or FRONTEND in after, "redirect to frontend /home", f"url={after}"))
    return jwt


def decode_jwt(jwt: str) -> dict:
    payload_b64 = jwt.split(".")[1]
    pad = "=" * ((4 - len(payload_b64) % 4) % 4)
    return json.loads(base64.urlsafe_b64decode(payload_b64 + pad))


def run() -> int:
    results: list[Result] = []
    c = Client()

    # -------- Public / health --------
    for path, name in [
        (f"{CORE}/api", "GET /api"),
        (f"{CORE}/api/auth/health", "GET /api/auth/health"),
        (f"{CORE}/api/auth/liveness", "GET /api/auth/liveness"),
        (f"{CORE}/api/crew/liveness", "GET /api/crew/liveness"),
        (f"{CORE}/api/crew/pages/frontpage", "GET public frontpage"),
        (f"{CORE}/api/crew/events", "GET public events"),
        (f"{FRONTEND}/", "FE /"),
        (f"{FRONTEND}/liveness", "FE /liveness"),
    ]:
        code, _, body, final = c.get(path)
        ok, detail = (code == 200, f"status={code}") if path.startswith(CORE) else page_ok(code, body, final)
        if path.endswith("/frontpage") and code == 200:
            fp = json_or_none(body) or {}
            ok = ok and isinstance(fp.get("content"), dict) and fp["content"].get("type") == "doc"
            detail = detail if ok else f"bad content={fp.get('content')!r}"
        if path.endswith("/events") and code == 200:
            data = (json_or_none(body) or {}).get("data") or []
            ok = ok and isinstance(data, list) and all(e.get("visible") and e.get("active") for e in data)
            detail = detail if ok else str([e.get("slug") for e in data])
        results.append(check(ok, name, detail))

    public_events = (json_or_none(c.get(f"{CORE}/api/crew/events")[2]) or {}).get("data") or []
    results.append(check(any(e.get("slug") == "tg26" for e in public_events), "seeded tg26 in public list"))

    guest = Client()
    code, _, body, _ = guest.get(f"{CORE}/api/crew/events?hidden=1&inactive=1")
    guest_events = (json_or_none(body) or {}).get("data") or []
    results.append(
        check(
            all(e.get("visible") and e.get("active") for e in guest_events),
            "guest hidden=1 still filtered",
            str([e.get("slug") for e in guest_events]),
        )
    )

    # -------- Login --------
    jwt = oidc_login(c, results)
    if not jwt:
        _report(results)
        return 1

    claims = decode_jwt(jwt)
    results.append(check(bool(claims.get("name")), "JWT name claim", str(claims.get("name"))))
    results.append(check(bool(claims.get("username")), "JWT username claim"))
    results.append(check(bool(claims.get("email")), "JWT email claim"))
    attrs = claims.get("attr") or {}
    for key in (
        "event:owner",
        "event:reader",
        "access:owner",
        "crew:owner",
        "profile:owner",
        "com:admin",
        "crew:applications:manager",
    ):
        results.append(
            check(
                key in attrs and ("*" in (attrs.get(key) or []) or bool(attrs.get(key))),
                f"JWT attr {key}",
                str(attrs.get(key)),
            )
        )

    # -------- Authed API envelopes --------
    code, _, body, _ = c.get(f"{CORE}/api/crew/events?hidden=1&inactive=1", headers=c.auth_headers())
    results.append(check(code == 200, "authed events hidden+inactive", f"status={code}"))
    authed_events = (json_or_none(body) or {}).get("data") or []
    slugs = {e.get("slug") for e in authed_events}
    results.append(check({"tg24", "tg25", "tg26"}.issubset(slugs), "admin sees tg24/25/26", str(sorted(slugs))))

    tg26 = next((e for e in authed_events if e.get("slug") == "tg26"), None)
    results.append(check(tg26 is not None, "resolve tg26"))
    eid = tg26["id"] if tg26 else None

    if eid is not None:
        api_gets = [
            (f"/api/crew/events/{eid}", "event show", lambda j: isinstance(j.get("data"), dict) and j["data"].get("slug") == "tg26"),
            (f"/api/crew/events/tg26", "event show by slug", lambda j: isinstance(j.get("data"), dict)),
            (f"/api/crew/events/{eid}/pages/event_frontpage", "event_frontpage", lambda j: j.get("type") == "event_frontpage" or (j.get("content") or {}).get("type") == "doc"),
            (f"/api/crew/events/{eid}/pages/event_member_frontpage", "event_member_frontpage", lambda j: (j.get("content") or {}).get("type") == "doc" or j.get("type")),
            (f"/api/crew/events/{eid}/crews", "crews list", lambda j: isinstance(j.get("data"), list) and len(j["data"]) >= 1),
            (f"/api/crew/events/{eid}/users", "event users", lambda j: j is not None),
            (f"/api/auth/profile", "auth profile", lambda j: j.get("id") or j.get("sub") or j.get("username")),
            (f"/api/auth/refresh-session", "refresh-session", lambda j: True),  # may be text
            (f"/api/profile/search?nickname=test", "profile search", lambda j: j is not None),
            (f"/api/profile/image/new", "profile images new", lambda j: j is not None),
            (f"/api/profile/liveness", "profile liveness", lambda j: True),  # may be plain text
            (f"/api/access/", "access root", lambda j: j is not None),
            (f"/api/access/attribute-types", "access attribute-types", lambda j: j is not None),
            (f"/api/access/attributes", "access attributes", lambda j: j is not None),
            (f"/api/access/users/current", "access current user", lambda j: j is not None),
            (
                f"/api/access/users/current?pretty=true",
                "access current pretty",
                lambda j: (
                    j.get("sub")
                    and isinstance(j.get("attr"), dict)
                    and len(j["attr"]) >= 1
                    and all(
                        isinstance(v, dict) and "value" in v
                        for v in j["attr"].values()
                    )
                ),
            ),
            (f"/api/communication/templates", "communication templates", lambda j: j is not None),
            (f"/api/communication/lists", "communication lists", lambda j: j is not None),
            (f"/api/communication/messages", "communication messages", lambda j: j is not None),
            (f"/api/communication/notifications", "communication notifications", lambda j: j is not None),
            (f"/api/tasking/events/{eid}", "tasking event tasks", lambda j: j is not None),
            (f"/api/application/questions?crew_id=1", "application questions", lambda j: j is not None),
            (f"/api/application/user/{claims.get('sub')}/applications?event_id={eid}", "user applications", lambda j: j is not None),
        ]

        crew_id = None
        for path, name, pred in api_gets:
            code, _, body, _ = c.get(CORE + path, headers=c.auth_headers())
            # refresh-session / liveness may be plain text
            if "refresh-session" in path:
                results.append(
                    check(
                        code == 200 and (b"valid" in body or b"renewed" in body),
                        name,
                        f"status={code} body={body[:80]!r}",
                    )
                )
                continue
            if path.endswith("/liveness"):
                results.append(check(code == 200 and b"ok" in body, name, f"status={code} body={body[:80]!r}"))
                continue
            payload = json_or_none(body)
            ok = code == 200 and payload is not None and pred(payload)
            results.append(check(ok, f"API {name}", f"status={code} body={body[:160]!r}"))
            if name == "crews list" and ok:
                crew_id = payload["data"][0]["id"]

        if crew_id is not None:
            for path, name in [
                (f"/api/crew/events/{eid}/crews/{crew_id}", "crew show"),
                (f"/api/crew/events/{eid}/crews/{crew_id}/users/{claims.get('sub')}", "crew user show"),
                (f"/api/tasking/events/{eid}/crew/{crew_id}", "tasking crew tasks"),
            ]:
                code, _, body, _ = c.get(CORE + path, headers=c.auth_headers())
                # 404 acceptable for crew-user if not membership row; still must not 500
                results.append(
                    check(
                        code in (200, 404),
                        f"API {name}",
                        f"status={code} body={body[:120]!r}",
                    )
                )
                if code == 200 and "crews/" in path and path.endswith(str(crew_id)):
                    payload = json_or_none(body) or {}
                    results.append(
                        check(
                            isinstance(payload.get("data"), dict) or "id" in payload,
                            "crew show envelope",
                            str(payload)[:120],
                        )
                    )

            # FE application + crew routes need numeric ids
            fe_crew_paths = [
                f"/tg26/crew/{crew_id}",
                f"/tg26/crew/{crew_id}/show",
                f"/tg26/crew/{crew_id}/edit",
                f"/tg26/crew/{crew_id}/applications/show",
                f"/tg26/crew/{crew_id}/applications/questions",
                f"/tg26/crew/{crew_id}/tasks",
            ]
            for path in fe_crew_paths:
                code, _, body, final = c.get(FRONTEND + path, headers={"Cookie": f"wannabe_jwt={jwt}", "Accept": "text/html"})
                ok, detail = page_ok(code, body, final)
                # edit/show may redirect; 404 page without SSR crash is still a product bug — flag non-200
                results.append(check(ok, f"FE {path}", detail))

        # -------- Task gate: completed required must not keep forcing /tasks --------
        uid = claims.get("sub")
        if uid:
            req_path = f"/api/tasking/events/{eid}/user/{uid}?required=true"
            all_path = f"/api/tasking/events/{eid}/user/{uid}"
            code, _, body, _ = c.get(CORE + req_path, headers=c.auth_headers())
            required_before = (json_or_none(body) or {}).get("data") or []
            results.append(
                check(
                    code == 200 and isinstance(required_before, list),
                    "task gate required=true list",
                    f"status={code} n={len(required_before) if isinstance(required_before, list) else '?'}",
                )
            )
            for task in list(required_before):
                tid = task.get("id")
                if tid is None:
                    continue
                results.append(
                    check(
                        bool(task.get("required")) and not task.get("completed"),
                        f"task gate item {tid} incomplete+required",
                        str(task)[:120],
                    )
                )
                pc, _, pb, _ = c.request(
                    "POST",
                    f"{CORE}/api/tasking/events/{eid}/user/{uid}/task/{tid}",
                    headers=c.auth_headers(),
                    json_body={"value": {"e2e": True}, "completed": True},
                )
                results.append(
                    check(
                        pc in (200, 201),
                        f"task gate complete {tid}",
                        f"status={pc} body={pb[:80]!r}",
                    )
                )

            code, _, body, _ = c.get(CORE + req_path, headers=c.auth_headers())
            required_after = (json_or_none(body) or {}).get("data") or []
            results.append(
                check(
                    code == 200 and required_after == [],
                    "task gate empty after complete",
                    f"status={code} data={required_after!r}",
                )
            )
            code, _, body, _ = c.get(CORE + all_path, headers=c.auth_headers())
            all_after = (json_or_none(body) or {}).get("data") or []
            completed = [t for t in all_after if t.get("completed")]
            results.append(
                check(
                    code == 200 and (len(completed) >= 1 or len(all_after) == 0),
                    "task list retains completed without required filter",
                    f"all={len(all_after)} completed={len(completed)}",
                )
            )

    # -------- Access pretty contract (feeds /debug/access) --------
    code, _, body, _ = c.get(
        f"{CORE}/api/access/users/current?pretty=true",
        headers=c.auth_headers(),
    )
    pretty = json_or_none(body) or {}
    attr = pretty.get("attr") if isinstance(pretty, dict) else None
    results.append(
        check(
            code == 200
            and pretty.get("sub") == claims.get("sub")
            and isinstance(attr, dict)
            and len(attr) >= 1
            and any(
                isinstance(v, dict) and v.get("value") is not None
                for v in attr.values()
            ),
            "access pretty sub/attr for debug",
            f"status={code} keys={list(attr.keys())[:8] if isinstance(attr, dict) else pretty!r}",
        )
    )
    # Spot-check keys the debug table renders (Attribute column)
    if isinstance(attr, dict) and attr:
        sample_key = next(iter(attr))
        results.append(
            check(
                isinstance(attr[sample_key].get("description"), (dict, str)),
                "access pretty description present",
                str(attr[sample_key].get("description"))[:80],
            )
        )

    # -------- Frontend pages (logged in) --------
    fe_paths = [
        "/home",
        "/events",
        "/tg26",
        "/tg26/application",
        "/tg26/crew",
        "/tg26/crew/manage",
        "/tg26/crew/create",
        "/tg26/member",
        "/tg26/profile",
        f"/tg26/profile/show/{claims.get('sub')}",
        "/tg26/profile-images",
        "/tg26/tasks",
        "/tg26/tasks/manage",
        "/tg26/tasks/manage/create",
        "/tg26/pages/manage",
        "/tg26/lists/manage",
        "/tg26/notify",
        "/tg26/edit",
        "/tg26/edit/questions",
        "/profile",
        f"/profile/show/{claims.get('sub')}",
        f"/profile/edit/{claims.get('sub')}",
        f"/profile/profile-picture/{claims.get('sub')}",
        "/welcome",
        "/welcome/profile-creation",
        "/welcome/complete",
        "/access/user",
        f"/access/user/edit/{claims.get('sub')}",
        "/access/attribute-type",
        "/access/attribute-type/create",
        "/communication/template",
        "/communication/message",
        "/communication/notification",
        "/goodbye",
        "/ninjalogin",
        "/debug/access",
    ]

    # Resolve template/message ids if present for deeper routes
    code, _, body, _ = c.get(f"{CORE}/api/communication/templates", headers=c.auth_headers())
    templates = json_or_none(body)
    tid = None
    if isinstance(templates, dict):
        rows = templates.get("data") or templates.get("data", [])
        if isinstance(templates.get("data"), list):
            rows = templates["data"]
        elif isinstance(templates, list):
            rows = templates
        else:
            # paginated?
            rows = (templates.get("data") or []) if isinstance(templates.get("data"), list) else []
            if not rows and isinstance(templates.get("data"), dict):
                rows = templates["data"].get("data") or []
        if isinstance(rows, list) and rows:
            tid = rows[0].get("id")
    elif isinstance(templates, list) and templates:
        tid = templates[0].get("id")

    if tid is not None:
        fe_paths.extend(
            [
                f"/communication/template/edit/{tid}",
                f"/communication/template/preview/{tid}",
            ]
        )

    code, _, body, _ = c.get(f"{CORE}/api/tasking/events/{eid}", headers=c.auth_headers()) if eid else (0, {}, b"{}", "")
    tasks_payload = json_or_none(body)
    task_id = None
    if isinstance(tasks_payload, list) and tasks_payload:
        task_id = tasks_payload[0].get("id")
    elif isinstance(tasks_payload, dict):
        rows = tasks_payload.get("data") or []
        if isinstance(rows, list) and rows:
            task_id = rows[0].get("id")
    if task_id is not None:
        fe_paths.extend(
            [
                f"/tg26/tasks/{task_id}",
                f"/tg26/tasks/manage/{task_id}/responses",
            ]
        )

    code, _, body, _ = c.get(f"{CORE}/api/crew/events/{eid}/pages", headers=c.auth_headers()) if eid else (0, {}, b"{}", "")
    pages_payload = json_or_none(body)
    page_id = None
    if isinstance(pages_payload, list) and pages_payload:
        page_id = pages_payload[0].get("id")
    elif isinstance(pages_payload, dict):
        rows = pages_payload.get("data") or pages_payload
        if isinstance(rows, list) and rows:
            page_id = rows[0].get("id")
    if page_id is not None:
        fe_paths.extend([f"/tg26/pages/{page_id}", f"/tg26/pages/{page_id}/edit"])

    code, _, body, _ = c.get(f"{CORE}/api/communication/lists", headers=c.auth_headers())
    lists_payload = json_or_none(body)
    lid = None
    if isinstance(lists_payload, list) and lists_payload:
        lid = lists_payload[0].get("id")
    elif isinstance(lists_payload, dict):
        rows = lists_payload.get("data") or []
        if isinstance(rows, list) and rows:
            lid = rows[0].get("id")
    if lid is not None:
        fe_paths.append(f"/tg26/lists/{lid}")

    for path in fe_paths:
        code, _, body, final = c.get(
            FRONTEND + path,
            headers={"Cookie": f"wannabe_jwt={jwt}", "Accept": "text/html"},
        )
        ok, detail = page_ok(code, body, final)
        results.append(check(ok, f"FE {path}", detail))

    # -------- Logged-out FE smoke --------
    logged_out = Client()
    for path in ("/", "/events", "/goodbye"):
        code, _, body, final = logged_out.get(FRONTEND + path)
        # /events may redirect to login/home
        ok = code in (200, 307, 308) or code == 200
        text = body.decode("utf-8", errors="replace")
        bad = any(m in text for m in SSR_ERROR_MARKERS)
        results.append(check(code == 200 and not bad, f"FE logged-out {path}", f"status={code} final={final}"))

    return _report(results)


def _report(results: list[Result]) -> int:
    failed = [r for r in results if not r.ok]
    for r in results:
        mark = "PASS" if r.ok else "FAIL"
        extra = f" — {r.detail}" if r.detail else ""
        print(f"[{mark}] {r.name}{extra}")
    print()
    print(f"{len(results) - len(failed)}/{len(results)} passed, {len(failed)} failed")
    return 1 if failed else 0


if __name__ == "__main__":
    sys.exit(run())
