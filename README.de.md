# BackstopJS Scenario Generator

**Version 1.4.0**

[English](README.md)

Visuelles Regressionstest-Tool: Websites crawlen, Szenarien erzeugen und Screenshots von Live- vs. lokaler Instanz vergleichen.

## Sprache (EN / DE)

GUI, API-Meldungen und CLI-Hilfe unterstützen **Englisch** und **Deutsch**.

| Weg | Verhalten |
|-----|-----------|
| Auto-Erkennung | Browser `Accept-Language` (GUI/API) bzw. `$LANG` / `$LC_ALL` (CLI) |
| GUI-Umschalter | **EN \| DE** im Header — schreibt `"locale"` in `config.json` |
| Config | Optional `"locale": "en"` oder `"de"` in `config.json` (gemeinsam für GUI + CLI) |
| CLI-Override | `--lang=en` oder `--lang=de` für einen Lauf (schreibt nicht in die Config) |

Fallback: **en**.

## Voraussetzungen

1. **DDEV** — [Installation](https://ddev.com/)

Node.js 20, BackstopJS und Chromium werden beim ersten `ddev start` im Container installiert.

## Schnellstart

### Option A: Browser-GUI (empfohlen)

```bash
git clone https://github.com/wwagner-net/create-backstop-scenarios.git
cd create-backstop-scenarios
ddev start
ddev exec backstop init
ddev launch
```

Die GUI führt durch 5 Schritte:

| Schritt | Inhalt |
|---------|--------|
| 1. Konfiguration | Projekt-ID, Selektoren, Delay, Viewports → `config.json` |
| 2. URLs sammeln | Sitemap oder Crawl |
| 3. URLs prüfen | Liste filtern/bearbeiten |
| 4. Szenarien | Test-Domain (DDEV) + Referenz-Domain (Live) |
| 5. Tests | Referenz-Screenshots, Testlauf, Bericht |

### Option B: CLI

```bash
ddev exec php crawler.php --url=https://www.example.com --lang=de
ddev exec php create-backstop-scenarios.php \
  --test=https://example.ddev.site \
  --reference=https://www.example.com
ddev exec php manage-scenarios.php next
ddev exec bash -lc 'PUPPETEER_EXECUTABLE_PATH=/usr/bin/chromium backstop reference --config ./backstop.js'
ddev exec bash -lc 'PUPPETEER_EXECUTABLE_PATH=/usr/bin/chromium backstop test --config ./backstop.js'
```

## Config

Siehe `config.example.json`. Neu:

```json
"locale": "de"
```

Wenn `locale` fehlt, gilt Auto-Erkennung.

## Weitere Dokumentation

Die ausführliche englische Anleitung (Batches, Cleanup, Troubleshooting) steht in [README.md](README.md).
