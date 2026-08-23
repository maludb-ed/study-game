"""The navigate tool's routing table — generated from the Phase 1 action manifest
(docs/plan/phase-1-action-manifest.md). A screen missing here is unreachable by voice."""

SCREENS: dict[str, dict] = {
    "dashboard":        {"path": "/",                        "desc": "the home dashboard with group stats"},
    "question-list":    {"path": "/questions/",              "desc": "browse, search, or filter the question bank", "query": ["exam_id", "domain_id", "status", "q"]},
    "question-add":     {"path": "/questions/new",           "desc": "write a new question"},
    "question-view":    {"path": "/questions/{id}",          "desc": "read one question with options, explanation, and stats"},
    "question-edit":    {"path": "/questions/{id}/edit",     "desc": "change an existing question"},
    "question-import":  {"path": "/questions/import",        "desc": "bulk-import a JSON batch of questions"},
    "scenario-list":    {"path": "/scenarios/",              "desc": "browse scenario rounds (CCAR-F format)", "query": ["exam_id", "status"]},
    "scenario-add":     {"path": "/scenarios/new",           "desc": "write a new scenario"},
    "scenario-view":    {"path": "/scenarios/{id}",          "desc": "read a scenario and its linked questions"},
    "scenario-edit":    {"path": "/scenarios/{id}/edit",     "desc": "change a scenario"},
    "exam-list":        {"path": "/exams/",                  "desc": "the four Anthropic exams and bank coverage"},
    "exam-view":        {"path": "/exams/{id}",              "desc": "one exam's blueprint coverage (exam ids: 1=CCAO-F 2=CCDV-F 3=CCAR-F 4=CCAR-P)"},
    "game-new":         {"path": "/games/new",               "desc": "set up a new game night"},
    "game-list":        {"path": "/games/",                  "desc": "past and live games", "query": ["exam_id"]},
    "game-host":        {"path": "/games/{id}/host",         "desc": "the live host console of a game (PIN, players, stages)"},
    "game-report":      {"path": "/games/{id}",              "desc": "the report of a finished game"},
    "analytics-group":  {"path": "/analytics/",              "desc": "group readiness: tiles, domain performance, members", "query": ["exam_id"]},
    "analytics-member": {"path": "/analytics/members/{id}",  "desc": "one member's readiness score and domain grid"},
    "drill-list":       {"path": "/analytics/drill",         "desc": "most-missed questions to review before the exam", "query": ["exam_id", "domain_id"]},
    "settings-mcp":     {"path": "/settings/mcp",            "desc": "MCP endpoints and bearer tokens for connecting your own AI tools"},
    "settings-2fa":     {"path": "/settings/2fa",            "desc": "two-factor authentication settings"},
    "ama":              {"path": "/ama",                     "desc": "the Ask-Me-Anything page for longer conversations"},
}


def resolve(screen: str, params: dict | None) -> tuple[str | None, str | None]:
    """Returns (path, error). Path params substitute {id}; leftovers become the query string."""
    from urllib.parse import urlencode
    entry = SCREENS.get(screen)
    if entry is None:
        close = [f"{sid} — {s['desc']}" for sid, s in SCREENS.items()
                 if any(w in sid for w in screen.lower().replace("_", "-").split("-") if len(w) > 2)]
        listing = "; ".join(close[:4]) if close else "; ".join(f"{sid} — {s['desc']}" for sid, s in list(SCREENS.items())[:6])
        return None, f"No screen '{screen}'. Closest: {listing}"
    params = dict(params or {})
    path = entry["path"]
    if "{id}" in path:
        record_id = params.pop("id", None) or params.pop("record_id", None)
        if record_id is None:
            return None, f"screen '{screen}' needs an id param"
        path = path.replace("{id}", str(record_id))
    allowed = entry.get("query", [])
    query = {k: v for k, v in params.items() if k in allowed and v not in (None, "")}
    if query:
        path += "?" + urlencode(query)
    return path, None
