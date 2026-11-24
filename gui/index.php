<?php
require_once dirname(__DIR__) . '/src/I18n.php';
if (!defined('BASE_DIR')) {
    define('BASE_DIR', dirname(__DIR__));
}
$guiLocale = I18n::resolve(null, true, false);
$i18nCatalogs = I18n::allCatalogs();
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($guiLocale, ENT_QUOTES, 'UTF-8') ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>BackstopJS Generator</title>
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

:root {
  --blue:    #2563eb;
  --blue-d:  #1d4ed8;
  --green:   #16a34a;
  --red:     #dc2626;
  --orange:  #d97706;
  --bg:      #f1f5f9;
  --card:    #ffffff;
  --border:  #cbd5e1;
  --text:    #1e293b;
  --muted:   #64748b;
  --radius:  8px;
}

body {
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
  background: var(--bg);
  color: var(--text);
  min-height: 100vh;
}

header {
  background: var(--card);
  border-bottom: 1px solid var(--border);
  padding: 16px 24px;
  display: flex;
  align-items: center;
  gap: 12px;
}
header h1 { font-size: 1.2rem; font-weight: 700; }
header span { font-size: 0.85rem; color: var(--muted); }
header .spacer { flex: 1; }
.lang-toggle { display: flex; gap: 4px; margin-left: auto; }
.lang-toggle button {
  border: 1px solid var(--border);
  background: var(--card);
  color: var(--muted);
  border-radius: 6px;
  padding: 4px 10px;
  font-size: 0.75rem;
  font-weight: 700;
  cursor: pointer;
}
.lang-toggle button.active {
  background: var(--blue);
  border-color: var(--blue);
  color: #fff;
}

/* Stepper */
.stepper {
  display: flex;
  align-items: center;
  gap: 0;
  padding: 20px 24px;
  background: var(--card);
  border-bottom: 1px solid var(--border);
  overflow-x: auto;
}
.step-item {
  display: flex;
  align-items: center;
  gap: 8px;
  cursor: pointer;
  flex-shrink: 0;
}
.step-item:not(:last-child)::after {
  content: '';
  display: block;
  width: 40px;
  height: 2px;
  background: var(--border);
  margin: 0 8px;
}
.step-item.done:not(:last-child)::after { background: var(--green); }
.step-num {
  width: 28px; height: 28px;
  border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  font-size: 0.8rem; font-weight: 700;
  background: var(--border);
  color: var(--muted);
  flex-shrink: 0;
}
.step-item.active .step-num { background: var(--blue); color: #fff; }
.step-item.done .step-num   { background: var(--green); color: #fff; }
.step-label {
  font-size: 0.8rem;
  color: var(--muted);
  white-space: nowrap;
}
.step-item.active .step-label { color: var(--blue); font-weight: 600; }
.step-item.done .step-label   { color: var(--green); }

/* Main content */
main { max-width: 820px; margin: 0 auto; padding: 24px; }

.panel { display: none; }
.panel.active { display: block; }

.card {
  background: var(--card);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  padding: 24px;
  margin-bottom: 16px;
}
.card h2 { font-size: 1.1rem; font-weight: 700; margin-bottom: 4px; }
.card .subtitle { color: var(--muted); font-size: 0.88rem; margin-bottom: 20px; }

/* Forms */
.form-row { margin-bottom: 16px; }
.form-row label { display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px; }
.form-row small { display: block; color: var(--muted); font-size: 0.8rem; margin-top: 4px; }
input[type=text], input[type=number], input[type=url], textarea, select {
  width: 100%;
  padding: 8px 12px;
  border: 1px solid var(--border);
  border-radius: 6px;
  font-size: 0.9rem;
  font-family: inherit;
  color: var(--text);
  background: #fff;
  transition: border-color 0.15s;
}
input[type=text]:focus, input[type=number]:focus, input[type=url]:focus,
textarea:focus, select:focus {
  outline: none;
  border-color: var(--blue);
  box-shadow: 0 0 0 3px rgba(37,99,235,0.1);
}
textarea { resize: vertical; font-family: 'Monaco', 'Menlo', monospace; font-size: 0.82rem; }
input[type=checkbox] { width: auto; margin-right: 6px; }

.form-row-inline { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
@media (max-width: 600px) { .form-row-inline { grid-template-columns: 1fr; } }

/* Buttons */
.btn {
  display: inline-flex; align-items: center; gap: 6px;
  padding: 9px 18px;
  border: none; border-radius: 6px;
  font-size: 0.88rem; font-weight: 600;
  cursor: pointer;
  transition: background 0.15s, opacity 0.15s;
}
.btn:disabled { opacity: 0.4; cursor: not-allowed; }
.btn-primary  { background: var(--blue);   color: #fff; }
.btn-primary:hover:not(:disabled)  { background: var(--blue-d); }
.btn-success  { background: var(--green);  color: #fff; }
.btn-success:hover:not(:disabled)  { background: #15803d; }
.btn-danger   { background: var(--red);    color: #fff; }
.btn-danger:hover:not(:disabled)   { background: #b91c1c; }
.btn-outline  { background: #fff; color: var(--text); border: 1px solid var(--border); }
.btn-outline:hover:not(:disabled)  { background: var(--bg); }
.btn-sm { padding: 5px 10px; font-size: 0.8rem; }

.btn-row { display: flex; gap: 8px; flex-wrap: wrap; margin-top: 16px; }
.btn-row .spacer { flex: 1; }

/* Radio toggle */
.radio-group { display: flex; gap: 0; border: 1px solid var(--border); border-radius: 6px; overflow: hidden; }
.radio-group label {
  flex: 1; text-align: center;
  padding: 8px;
  cursor: pointer;
  font-size: 0.88rem;
  font-weight: 600;
  color: var(--muted);
  background: #fff;
  border-right: 1px solid var(--border);
  transition: background 0.15s, color 0.15s;
}
.radio-group label:last-child { border-right: none; }
.radio-group input[type=radio] { display: none; }
.radio-group input[type=radio]:checked + label { background: var(--blue); color: #fff; }

/* Terminal output */
.terminal {
  background: #0f172a;
  color: #94a3b8;
  border-radius: 6px;
  padding: 14px;
  font-family: 'Monaco', 'Menlo', 'Courier New', monospace;
  font-size: 0.8rem;
  line-height: 1.6;
  max-height: 320px;
  overflow-y: auto;
  white-space: pre-wrap;
  word-break: break-all;
  margin-top: 12px;
}
.terminal .t-success { color: #4ade80; }
.terminal .t-error   { color: #f87171; }
.terminal .t-info    { color: #60a5fa; }
.terminal:empty::before { content: '▸ Warte auf Ausgabe...'; color: #475569; }

/* Status badges */
.status-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 12px;
  margin-bottom: 16px;
}
.status-badge {
  background: var(--bg);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  padding: 14px;
  text-align: center;
}
.status-badge .num  { font-size: 2rem; font-weight: 800; line-height: 1; }
.status-badge .lbl  { font-size: 0.78rem; color: var(--muted); margin-top: 4px; }
.status-badge.pending .num { color: var(--orange); }
.status-badge.active  .num { color: var(--blue); }
.status-badge.done    .num { color: var(--green); }

/* Alert */
.alert {
  padding: 10px 14px;
  border-radius: 6px;
  font-size: 0.88rem;
  margin-bottom: 12px;
}
.alert-info    { background: #eff6ff; border: 1px solid #bfdbfe; color: #1d4ed8; }
.alert-success { background: #f0fdf4; border: 1px solid #bbf7d0; color: #15803d; }
.alert-error   { background: #fef2f2; border: 1px solid #fecaca; color: #b91c1c; }
.alert-warning { background: #fffbeb; border: 1px solid #fde68a; color: #92400e; }

/* Report banner */
.report-banner {
  display: flex; align-items: center; gap: 12px;
  background: #f0fdf4; border: 2px solid #4ade80;
  border-radius: var(--radius); padding: 14px 16px;
  margin-bottom: 16px;
  animation: fadeIn 0.3s ease;
}
@keyframes fadeIn { from { opacity: 0; transform: translateY(-6px); } to { opacity: 1; transform: none; } }
.report-banner .rb-icon { font-size: 1.6rem; flex-shrink: 0; }
.report-banner .rb-text { flex: 1; }
.report-banner .rb-title { font-weight: 700; color: #15803d; font-size: 0.95rem; }
.report-banner .rb-sub   { font-size: 0.82rem; color: #166534; margin-top: 2px; }
.report-banner .rb-close {
  background: none; border: none; color: #15803d;
  cursor: pointer; font-size: 1.1rem; padding: 0 4px; opacity: 0.7;
}
.report-banner .rb-close:hover { opacity: 1; }

/* Viewport table */
.vp-table { width: 100%; border-collapse: collapse; font-size: 0.88rem; }
.vp-table th {
  text-align: left; padding: 6px 8px;
  border-bottom: 1px solid var(--border);
  font-size: 0.8rem; color: var(--muted);
}
.vp-table td { padding: 4px 4px; }
.vp-table input { padding: 4px 8px; }
.vp-table .del-btn { background: none; border: none; color: var(--red); cursor: pointer; font-size: 1rem; }

/* URL list */
#urlList { height: 300px; }
.url-count { font-size: 0.85rem; color: var(--muted); margin: 8px 0; }
.filter-row { display: flex; gap: 8px; margin-bottom: 8px; }
.filter-row input { flex: 1; }

/* Active file info */
.active-info {
  background: #eff6ff;
  border: 1px solid #bfdbfe;
  border-radius: 6px;
  padding: 10px 14px;
  font-size: 0.85rem;
  margin-bottom: 12px;
  color: #1e40af;
}

/* Running indicator */
@keyframes spin { to { transform: rotate(360deg); } }
.spinner {
  display: inline-block;
  width: 14px; height: 14px;
  border: 2px solid rgba(255,255,255,0.4);
  border-top-color: #fff;
  border-radius: 50%;
  animation: spin 0.6s linear infinite;
}
</style>
</head>
<body>

<header>
  <svg width="28" height="28" viewBox="0 0 28 28" fill="none">
    <rect width="28" height="28" rx="6" fill="#2563eb"/>
    <rect x="6" y="6" width="7" height="7" rx="1" fill="white"/>
    <rect x="15" y="6" width="7" height="7" rx="1" fill="white" opacity="0.6"/>
    <rect x="6" y="15" width="7" height="7" rx="1" fill="white" opacity="0.6"/>
    <rect x="15" y="15" width="7" height="7" rx="1" fill="white"/>
  </svg>
  <h1>BackstopJS Generator</h1>
  <span data-i18n="gui.header.subtitle">Visual Regression Testing</span>
  <div class="lang-toggle" role="group" aria-label="Language">
    <button type="button" id="lang-en" data-i18n="gui.lang.en" onclick="switchLocale('en')">EN</button>
    <button type="button" id="lang-de" data-i18n="gui.lang.de" onclick="switchLocale('de')">DE</button>
  </div>
</header>

<div class="stepper" id="stepper">
  <div class="step-item active" data-step="1" onclick="goToStep(1)">
    <div class="step-num">1</div>
    <span class="step-label" data-i18n="gui.step.config">Konfiguration</span>
  </div>
  <div class="step-item" data-step="2" onclick="goToStep(2)">
    <div class="step-num">2</div>
    <span class="step-label" data-i18n="gui.step.urls_collect">URLs sammeln</span>
  </div>
  <div class="step-item" data-step="3" onclick="goToStep(3)">
    <div class="step-num">3</div>
    <span class="step-label" data-i18n="gui.step.urls_review">URLs prüfen</span>
  </div>
  <div class="step-item" data-step="4" onclick="goToStep(4)">
    <div class="step-num">4</div>
    <span class="step-label" data-i18n="gui.step.scenarios">Szenarien</span>
  </div>
  <div class="step-item" data-step="5" onclick="goToStep(5)">
    <div class="step-num">5</div>
    <span class="step-label" data-i18n="gui.step.tests">Tests</span>
  </div>
</div>

<main>

<!-- ═══════════════════════════════════════════════════════ STEP 1 -->
<div class="panel active" id="panel-1">
  <div class="card">
    <h2 data-i18n="gui.config.title">Konfiguration</h2>
    <p class="subtitle" data-i18n="gui.config.subtitle">Projekteinstellungen festlegen. Werden in config.json gespeichert.</p>

    <div class="form-row-inline">
      <div class="form-row">
        <label data-i18n="gui.config.project_id">Projekt-ID</label>
        <input type="text" id="cfg-projectId" data-i18n-placeholder="gui.config.project_id_ph" placeholder="mein-projekt">
        <small data-i18n="gui.config.project_id_hint">Eindeutiger Bezeichner (keine Leerzeichen)</small>
      </div>
      <div class="form-row">
        <label data-i18n="gui.config.chunk_size">Chunk-Grösse (URLs pro Datei)</label>
        <input type="number" id="cfg-chunkSize" value="40" min="1" max="500">
        <small data-i18n="gui.config.chunk_hint">Standard: 40. Weniger = stabiler, mehr = schneller</small>
      </div>
    </div>

    <div class="form-row">
      <label><span data-i18n="gui.config.remove_selectors">Remove-Selektoren</span> <small style="display:inline;font-weight:400" data-i18n="gui.config.remove_selectors_hint">(werden komplett entfernt, z.B. Cookie-Banner)</small></label>
      <textarea id="cfg-removeSelectors" rows="3" placeholder="#CybotCookiebotDialog&#10;.cookie-banner&#10;#chat-widget"></textarea>
      <small data-i18n="gui.config.selector_per_line">Ein CSS-Selektor pro Zeile</small>
    </div>

    <div class="form-row">
      <label><span data-i18n="gui.config.hide_selectors">Hide-Selektoren</span> <small style="display:inline;font-weight:400" data-i18n="gui.config.hide_selectors_hint">(versteckt, z.B. Timestamps)</small></label>
      <textarea id="cfg-hideSelectors" rows="2" placeholder=".timestamp&#10;.view-counter"></textarea>
      <small data-i18n="gui.config.selector_per_line">Ein CSS-Selektor pro Zeile</small>
    </div>

    <div class="form-row-inline">
      <div class="form-row">
        <label data-i18n="gui.config.delay">Delay vor Screenshot (ms)</label>
        <input type="number" id="cfg-delay" value="5000" min="0" max="30000" step="500">
        <small data-i18n="gui.config.delay_hint">Standard: 5000ms (5 Sekunden)</small>
      </div>
      <div class="form-row">
        <label data-i18n="gui.config.mismatch">Mismatch-Schwelle (%)</label>
        <input type="number" id="cfg-misMatch" value="10" min="0" max="100" step="0.5">
        <small data-i18n="gui.config.mismatch_hint">Akzeptable Abweichung. Standard: 10%</small>
      </div>
    </div>

    <div class="form-row">
      <label data-i18n="gui.config.viewports">Viewports</label>
      <table class="vp-table">
        <thead>
          <tr>
            <th data-i18n="gui.config.vp_name">Name</th><th data-i18n="gui.config.vp_width">Breite (px)</th><th data-i18n="gui.config.vp_height">Höhe (px)</th><th></th>
          </tr>
        </thead>
        <tbody id="vp-tbody"></tbody>
      </table>
      <button class="btn btn-outline btn-sm" style="margin-top:8px" onclick="addViewport()" data-i18n="gui.config.add_viewport">+ Viewport hinzufügen</button>
    </div>

    <div id="cfg-alert"></div>
    <div class="btn-row">
      <button class="btn btn-primary" onclick="saveConfig()" data-i18n="gui.config.save">Konfiguration speichern</button>
      <div class="spacer"></div>
      <button class="btn btn-outline" onclick="goToStep(2)" data-i18n="gui.nav.next">Weiter →</button>
    </div>
  </div>
</div>

<!-- ═══════════════════════════════════════════════════════ STEP 2 -->
<div class="panel" id="panel-2">
  <div class="card">
    <h2 data-i18n="gui.crawl.title">URLs sammeln</h2>
    <p class="subtitle" data-i18n="gui.crawl.subtitle">URLs von der Referenz-Website sammeln (Sitemap oder Crawlen).</p>

    <div class="form-row">
      <label data-i18n="gui.crawl.mode">Modus</label>
      <div class="radio-group">
        <input type="radio" id="mode-sitemap" name="crawl-mode" value="sitemap" checked>
        <label for="mode-sitemap" data-i18n="gui.crawl.mode_sitemap">Sitemap (empfohlen)</label>
        <input type="radio" id="mode-crawl" name="crawl-mode" value="crawl">
        <label for="mode-crawl" data-i18n="gui.crawl.mode_crawl">Website crawlen</label>
        <input type="radio" id="mode-manual" name="crawl-mode" value="manual">
        <label for="mode-manual" data-i18n="gui.crawl.mode_manual">Eigene URL-Liste</label>
      </div>
    </div>

    <div id="crawl-auto-fields">
      <div class="form-row">
        <label id="url-label" data-i18n="gui.crawl.url_sitemap">Sitemap-URL</label>
        <input type="url" id="crawl-url" placeholder="https://www.example.com/sitemap.xml">
      </div>

      <div class="form-row-inline">
        <div class="form-row">
          <label data-i18n="gui.crawl.max_urls">Max. Anzahl URLs</label>
          <input type="number" id="crawl-maxUrls" value="10000" min="1" max="100000">
        </div>
        <div class="form-row" style="display:flex;align-items:flex-end;padding-bottom:4px;">
          <label style="display:flex;align-items:center;gap:6px;cursor:pointer;margin-bottom:0;">
            <input type="checkbox" id="crawl-includeParams">
            <span data-i18n="gui.crawl.include_params">Query-Parameter einschliessen</span>
          </label>
        </div>
      </div>
    </div>

    <div class="form-row" id="crawl-manual-fields" style="display:none">
      <label data-i18n="gui.crawl.manual_label">URLs (eine pro Zeile)</label>
      <textarea id="manual-urls" rows="10" placeholder="https://www.example.com/seite-1&#10;https://www.example.com/seite-2"></textarea>
      <small style="color:var(--muted);margin-top:4px;display:block" data-i18n="gui.crawl.manual_hint">Leerzeilen und führende/nachfolgende Leerzeichen werden ignoriert.</small>
    </div>

    <div id="crawl-alert"></div>
    <div class="btn-row">
      <button class="btn btn-outline" onclick="goToStep(1)" data-i18n="gui.nav.back">← Zurück</button>
      <button class="btn btn-primary" id="btn-crawl" onclick="startCrawl()" data-i18n="gui.crawl.collect">URLs sammeln</button>
      <div class="spacer"></div>
      <button class="btn btn-outline" id="btn-crawl-next" onclick="goToStep(3)" data-i18n="gui.nav.next">Weiter →</button>
    </div>
    <div class="terminal" id="crawl-output"></div>
  </div>
</div>

<!-- ═══════════════════════════════════════════════════════ STEP 3 -->
<div class="panel" id="panel-3">
  <div class="card">
    <h2 data-i18n="gui.urls.title">URLs prüfen &amp; bearbeiten</h2>
    <p class="subtitle" data-i18n="gui.urls.subtitle">Überprüfe die gesammelten URLs und entferne unerwünschte Einträge.</p>

    <div class="filter-row">
      <input type="text" id="url-filter" data-i18n-placeholder="gui.urls.filter_ph" placeholder="Filter..." oninput="filterUrls()">
      <button class="btn btn-outline btn-sm" onclick="loadUrls()" data-i18n="gui.urls.reload">🔄 Neu laden</button>
    </div>
    <div class="url-count" id="url-count" data-i18n="gui.urls.loading">Lade...</div>
    <textarea id="urlList" data-i18n-placeholder="gui.urls.empty_ph" placeholder="Keine URLs vorhanden. Bitte erst URLs sammeln."></textarea>
    <small style="color:var(--muted);margin-top:4px;display:block" data-i18n="gui.urls.hint">Eine URL pro Zeile. Leerzeilen und führende/nachfolgende Leerzeichen werden ignoriert.</small>

    <div id="urls-alert"></div>
    <div class="btn-row">
      <button class="btn btn-outline" onclick="goToStep(2)" data-i18n="gui.nav.back">← Zurück</button>
      <button class="btn btn-success" onclick="saveUrls()" data-i18n="gui.urls.save">URLs speichern</button>
      <div class="spacer"></div>
      <button class="btn btn-outline" onclick="goToStep(4)" data-i18n="gui.nav.next">Weiter →</button>
    </div>
  </div>
</div>

<!-- ═══════════════════════════════════════════════════════ STEP 4 -->
<div class="panel" id="panel-4">
  <div class="card">
    <h2 data-i18n="gui.gen.title">Szenarien generieren</h2>
    <p class="subtitle" data-i18n="gui.gen.subtitle">Test- und Referenz-Domain angeben, dann Szenario-Dateien erstellen.</p>

    <div class="alert alert-info" data-i18n="gui.gen.info">
      Die <strong>Referenz-Domain</strong> ist deine Live-Website. Die <strong>Test-Domain</strong> ist deine lokale DDEV-Instanz.
    </div>

    <div class="form-row">
      <label data-i18n="gui.gen.test">Test-Domain (DDEV)</label>
      <input type="url" id="gen-test" placeholder="https://example.ddev.site">
    </div>
    <div class="form-row">
      <label data-i18n="gui.gen.reference">Referenz-Domain (Produktion)</label>
      <input type="url" id="gen-reference" placeholder="https://www.example.com">
    </div>

    <div id="gen-alert"></div>
    <div class="btn-row">
      <button class="btn btn-outline" onclick="goToStep(3)" data-i18n="gui.nav.back">← Zurück</button>
      <button class="btn btn-primary" id="btn-generate" onclick="generateScenarios()" data-i18n="gui.gen.run">Szenarien generieren</button>
      <div class="spacer"></div>
      <button class="btn btn-outline" id="btn-gen-next" onclick="goToStep(5)" data-i18n="gui.nav.next">Weiter →</button>
    </div>
    <div class="terminal" id="gen-output"></div>
  </div>
</div>

<!-- ═══════════════════════════════════════════════════════ STEP 5 -->
<div class="panel" id="panel-5">
  <div class="card">
    <h2 data-i18n="gui.test.title">Tests ausführen</h2>
    <p class="subtitle" data-i18n="gui.test.subtitle">Szenarios verwalten und BackstopJS-Tests durchführen.</p>

    <div class="status-grid">
      <div class="status-badge pending">
        <div class="num" id="count-pending">–</div>
        <div class="lbl" data-i18n="gui.test.pending">Pending</div>
      </div>
      <div class="status-badge active">
        <div class="num" id="count-active">–</div>
        <div class="lbl" data-i18n="gui.test.active">Aktiv</div>
      </div>
      <div class="status-badge done">
        <div class="num" id="count-done">–</div>
        <div class="lbl" data-i18n="gui.test.done">Erledigt</div>
      </div>
    </div>

    <div id="active-info" class="active-info" style="display:none"></div>

    <!-- Report banner (shown after successful test run) -->
    <div id="report-banner" class="report-banner" style="display:none">
      <div class="rb-icon">📊</div>
      <div class="rb-text">
        <div class="rb-title" data-i18n="gui.test.report_title">Testergebnis liegt vor!</div>
        <div class="rb-sub" data-i18n="gui.test.report_sub">Der Vergleichsbericht wurde automatisch geöffnet.</div>
      </div>
      <a id="report-banner-link" href="/backstop_data/html_report/index.html" target="_blank"
         class="btn btn-success btn-sm" data-i18n="gui.test.report_open">Bericht öffnen</a>
      <button class="rb-close" onclick="document.getElementById('report-banner').style.display='none'" title="Schliessen" data-i18n-title="gui.test.close">✕</button>
    </div>

    <div id="test-alert"></div>

    <!-- Workflow buttons -->
    <div style="display:flex;flex-direction:column;gap:10px;">

      <div style="display:flex;gap:8px;align-items:center;">
        <button class="btn btn-outline" id="btn-next" onclick="runManage('next')" style="flex:1" data-i18n="gui.test.next">
          ▶ Nächste Szenarien aktivieren
        </button>
        <button class="btn btn-outline btn-sm" id="btn-status" onclick="loadStatus()" title="Status aktualisieren" data-i18n-title="gui.test.refresh">⟳</button>
      </div>

      <div style="display:flex;gap:8px;">
        <button class="btn btn-primary" id="btn-reference" onclick="runBackstop('reference')" style="flex:1" data-i18n="gui.test.reference">
          📷 Referenz-Screenshots erstellen
        </button>
        <button class="btn btn-primary" id="btn-test" onclick="runBackstop('test')" style="flex:1"
                title="Nur verfügbar wenn Referenz-Screenshots existieren" data-i18n="gui.test.run" data-i18n-title="gui.test.run_title">
          🔍 Tests ausführen
        </button>
      </div>

      <div style="display:flex;gap:8px;">
        <button class="btn btn-success" id="btn-done" onclick="runManage('done')" style="flex:1" data-i18n="gui.test.mark_done">
          ✓ Als erledigt markieren &amp; weiter
        </button>
        <button class="btn btn-outline" id="btn-skip" onclick="runManage('skip')" style="flex:1" data-i18n="gui.test.skip">
          ⏭ Überspringen
        </button>
      </div>

      <div style="display:flex;gap:8px;align-items:center;border-top:1px solid var(--border);padding-top:10px;flex-wrap:wrap;">
        <button class="btn btn-outline btn-sm" id="btn-reset" onclick="confirmReset()" style="color:var(--red);border-color:var(--red)" data-i18n="gui.test.reset">
          ↺ Szenarien zurücksetzen
        </button>
        <button class="btn btn-outline btn-sm" id="btn-cleanup" onclick="confirmCleanup()" style="color:var(--red);border-color:var(--red)" data-i18n="gui.test.cleanup">
          🗑 Screenshots &amp; Berichte löschen
        </button>
        <div class="spacer"></div>
        <a id="report-link" href="/backstop_data/html_report/index.html" target="_blank"
           class="btn btn-outline btn-sm" style="display:none" data-i18n="gui.test.last_report">
          📊 Letzten Testergebnis-Bericht öffnen
        </a>
      </div>
    </div>

    <div class="terminal" id="test-output"></div>
  </div>

  <div style="text-align:center;padding:8px 0;color:var(--muted);font-size:0.8rem;">
    <button class="btn btn-outline" onclick="goToStep(4)" data-i18n="gui.nav.back">← Zurück</button>
  </div>
</div>

</main>


<script>
// ═══════════════════════════════════════════════════ I18N
const I18N_CATALOGS = <?= json_encode($i18nCatalogs, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
let currentLocale = <?= json_encode($guiLocale, JSON_UNESCAPED_UNICODE) ?>;

function t(key, vars = {}) {
  const cat = I18N_CATALOGS[currentLocale] || {};
  let text = cat[key];
  if (text == null) text = (I18N_CATALOGS.en || {})[key];
  if (text == null) text = key;
  Object.keys(vars).forEach(k => {
    text = String(text).split('{' + k + '}').join(String(vars[k]));
  });
  return text;
}

function applyI18n(root = document) {
  root.querySelectorAll('[data-i18n]').forEach(el => {
    el.textContent = t(el.getAttribute('data-i18n'));
  });
  root.querySelectorAll('[data-i18n-placeholder]').forEach(el => {
    el.setAttribute('placeholder', t(el.getAttribute('data-i18n-placeholder')));
  });
  root.querySelectorAll('[data-i18n-title]').forEach(el => {
    el.setAttribute('title', t(el.getAttribute('data-i18n-title')));
  });
  document.documentElement.lang = currentLocale;
  const enBtn = document.getElementById('lang-en');
  const deBtn = document.getElementById('lang-de');
  if (enBtn) enBtn.classList.toggle('active', currentLocale === 'en');
  if (deBtn) deBtn.classList.toggle('active', currentLocale === 'de');
  updateCrawlModeLabel();
}

async function switchLocale(locale) {
  if (locale !== 'en' && locale !== 'de') return;
  currentLocale = locale;
  applyI18n();
  // Persist into config.json without wiping other fields
  try {
    const res = await fetch('/gui/api/config.php');
    const json = await res.json();
    const config = json.config || {};
    config.locale = locale;
    if (!config.projectId) config.projectId = document.getElementById('cfg-projectId').value.trim() || 'my-project';
    await fetch('/gui/api/config.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ config, lang: locale }),
    });
  } catch (e) {
    console.warn('Could not persist locale', e);
  }
}

// ═══════════════════════════════════════════════════ STATE

let currentStep = 1;
let completedSteps = new Set();
let crawling = false;
let generating = false;
let backstopRunning = false;
let allUrls = [];

// ═══════════════════════════════════════════════════ INIT
document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('input[name="crawl-mode"]').forEach(r =>
    r.addEventListener('change', updateCrawlModeLabel));
  applyI18n();
  loadConfig();
  updateStepButtons();
});

// ═══════════════════════════════════════════════════ STEP NAV
function goToStep(n) {
  currentStep = n;
  document.querySelectorAll('.panel').forEach((p, i) => {
    p.classList.toggle('active', i + 1 === n);
  });
  document.querySelectorAll('.step-item').forEach(item => {
    const s = parseInt(item.dataset.step);
    item.classList.toggle('active', s === n);
    item.classList.toggle('done', completedSteps.has(s) && s !== n);
  });
  // Load data when entering steps
  if (n === 3) loadUrls();
  if (n === 5) loadStatus();
}

function markDone(step) {
  completedSteps.add(step);
  document.querySelectorAll('.step-item').forEach(item => {
    const s = parseInt(item.dataset.step);
    item.classList.toggle('done', completedSteps.has(s) && s !== currentStep);
    const num = item.querySelector('.step-num');
    if (completedSteps.has(s) && s !== currentStep) {
      num.textContent = '✓';
    } else if (s !== currentStep) {
      num.textContent = s;
    }
  });
}

function updateStepButtons() {}

// ═══════════════════════════════════════════════════ ALERTS
function showAlert(id, msg, type = 'error') {
  const el = document.getElementById(id);
  el.innerHTML = `<div class="alert alert-${type}">${msg}</div>`;
}
function clearAlert(id) {
  document.getElementById(id).innerHTML = '';
}

// ═══════════════════════════════════════════════════ TERMINAL
function termAppend(id, line, cls = '') {
  const el = document.getElementById(id);
  const span = document.createElement('span');
  if (cls) span.className = cls;
  span.textContent = line + '\n';
  el.appendChild(span);
  el.scrollTop = el.scrollHeight;
}
function termClear(id) {
  document.getElementById(id).innerHTML = '';
}
function classifyLine(line) {
  const l = line.toLowerCase();
  if (/error|fehler|failed|failure|✗|✖/.test(l)) return 't-error';
  if (/✓|success|done|passed|complete|saved|created/.test(l)) return 't-success';
  if (/warning|warn|⚠/.test(l)) return '';
  return '';
}

// ═══════════════════════════════════════════════════ SSE HELPER
function runStream(url, termId, onDone) {
  const es = new EventSource(url);
  es.onmessage = (e) => {
    const data = JSON.parse(e.data);
    if (data.done !== undefined) {
      es.close();
      onDone(data.exit === 0);
    } else if (data.line !== undefined) {
      termAppend(termId, data.line, classifyLine(data.line));
    }
  };
  es.onerror = () => {
    es.close();
    termAppend(termId, '[connection error]', 't-error');
    onDone(false);
  };
  return es;
}

// ═══════════════════════════════════════════════════ STEP 1: CONFIG
const defaultViewports = [
  { label: 'phone',   width: 320,  height: 480  },
  { label: 'tablet',  width: 1024, height: 768  },
  { label: 'desktop', width: 1280, height: 1024 },
];

async function loadConfig() {
  try {
    const res = await fetch('/gui/api/config.php');
    const json = await res.json();
    const c = json.config;
    if (c.locale === 'en' || c.locale === 'de') {
      currentLocale = c.locale;
      applyI18n();
    }
    document.getElementById('cfg-projectId').value = c.projectId || '';
    document.getElementById('cfg-chunkSize').value = c.chunkSize || 40;
    document.getElementById('cfg-delay').value = c.scenarios?.delay ?? 5000;
    document.getElementById('cfg-misMatch').value = c.scenarios?.misMatchThreshold ?? 10;
    document.getElementById('cfg-removeSelectors').value =
      (c.scenarios?.removeSelectors || []).join('\n');
    document.getElementById('cfg-hideSelectors').value =
      (c.scenarios?.hideSelectors || []).join('\n');
    renderViewports(c.viewports || defaultViewports);
    if (json.exists) markDone(1);
  } catch(e) {
    renderViewports(defaultViewports);
  }
}

function renderViewports(vps) {
  const tbody = document.getElementById('vp-tbody');
  tbody.innerHTML = '';
  vps.forEach((vp, i) => {
    const tr = document.createElement('tr');
    tr.innerHTML = `
      <td><input type="text" value="${vp.label}" data-vp-field="label" data-vp-idx="${i}"></td>
      <td><input type="number" value="${vp.width}" min="100" max="3840" data-vp-field="width" data-vp-idx="${i}"></td>
      <td><input type="number" value="${vp.height}" min="100" max="3000" data-vp-field="height" data-vp-idx="${i}"></td>
      <td><button class="del-btn" onclick="removeViewport(${i})" title="Entfernen">✕</button></td>`;
    tbody.appendChild(tr);
  });
}

function addViewport() {
  const vps = collectViewports();
  vps.push({ label: 'custom', width: 1440, height: 900 });
  renderViewports(vps);
}

function removeViewport(idx) {
  const vps = collectViewports();
  vps.splice(idx, 1);
  renderViewports(vps);
}

function collectViewports() {
  const rows = document.querySelectorAll('#vp-tbody tr');
  return Array.from(rows).map(tr => ({
    label:  tr.querySelector('[data-vp-field="label"]').value.trim(),
    width:  parseInt(tr.querySelector('[data-vp-field="width"]').value),
    height: parseInt(tr.querySelector('[data-vp-field="height"]').value),
  }));
}

function parseSelectors(text) {
  return text.split('\n').map(s => s.trim()).filter(s => s !== '');
}

async function saveConfig() {
  clearAlert('cfg-alert');
  const projectId = document.getElementById('cfg-projectId').value.trim();
  if (!projectId) { showAlert('cfg-alert', t('gui.config.project_id_required')); return; }

  const config = {
    projectId,
    locale: currentLocale,
    chunkSize: parseInt(document.getElementById('cfg-chunkSize').value) || 40,
    scenarios: {
      removeSelectors: parseSelectors(document.getElementById('cfg-removeSelectors').value),
      hideSelectors:   parseSelectors(document.getElementById('cfg-hideSelectors').value),
      delay:           parseInt(document.getElementById('cfg-delay').value) || 5000,
      misMatchThreshold: parseFloat(document.getElementById('cfg-misMatch').value) || 10,
      requireSameDimensions: true,
    },
    viewports: collectViewports(),
    engine: { asyncCaptureLimit: 5, asyncCompareLimit: 50, debug: false, debugWindow: false },
    report: ['browser'],
  };

  try {
    const res = await fetch('/gui/api/config.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ config }),
    });
    const json = await res.json();
    if (json.success) {
      showAlert('cfg-alert', t('gui.config.saved'), 'success');
      markDone(1);
    } else {
      showAlert('cfg-alert', json.error || t('gui.config.save_error'));
    }
  } catch(e) {
    showAlert('cfg-alert', t('gui.config.network_error', {message: e.message}));
  }
}

// ═══════════════════════════════════════════════════ STEP 2: CRAWL
function updateCrawlModeLabel() {
  const mode = document.querySelector('input[name="crawl-mode"]:checked').value;
  document.getElementById('crawl-auto-fields').style.display = mode === 'manual' ? 'none' : '';
  document.getElementById('crawl-manual-fields').style.display = mode === 'manual' ? '' : 'none';
  document.getElementById('btn-crawl').textContent = mode === 'manual' ? t('gui.crawl.adopt') : t('gui.crawl.collect');
  if (mode !== 'manual') {
    document.getElementById('url-label').textContent =
      mode === 'sitemap' ? t('gui.crawl.url_sitemap') : t('gui.crawl.url_start');
    document.getElementById('crawl-url').placeholder =
      mode === 'sitemap' ? 'https://www.example.com/sitemap.xml' : 'https://www.example.com';
  }
}

function startCrawl() {
  if (crawling) return;
  clearAlert('crawl-alert');
  const mode = document.querySelector('input[name="crawl-mode"]:checked').value;

  if (mode === 'manual') {
    saveManualUrls();
    return;
  }

  const url = document.getElementById('crawl-url').value.trim();

  if (!url) { showAlert('crawl-alert', t('gui.crawl.url_required')); return; }
  if (!/^https?:\/\//i.test(url)) { showAlert('crawl-alert', t('gui.crawl.url_scheme')); return; }

  crawling = true;
  termClear('crawl-output');
  const btn = document.getElementById('btn-crawl');
  btn.disabled = true;
  btn.innerHTML = '<span class="spinner"></span> ' + t('gui.common.running');

  const maxUrls = document.getElementById('crawl-maxUrls').value;
  const includeParams = document.getElementById('crawl-includeParams').checked ? '1' : '';

  let apiUrl = `/gui/api/stream/crawl.php?mode=${mode}&url=${encodeURIComponent(url)}&maxUrls=${maxUrls}`;
  if (includeParams) apiUrl += '&includeParams=1';

  runStream(apiUrl, 'crawl-output', (success) => {
    crawling = false;
    btn.disabled = false;
    btn.innerHTML = t('gui.crawl.collect');
    if (success) {
      showAlert('crawl-alert', t('gui.crawl.success'), 'success');
      markDone(2);
    } else {
      showAlert('crawl-alert', t('gui.crawl.error'), 'error');
    }
  });
}

async function saveManualUrls() {
  const raw = document.getElementById('manual-urls').value;
  const urls = raw.split('\n').map(u => u.trim()).filter(u => u !== '');

  if (urls.length === 0) { showAlert('crawl-alert', t('gui.crawl.manual_required')); return; }
  const invalid = urls.find(u => !/^https?:\/\//i.test(u));
  if (invalid) { showAlert('crawl-alert', t('gui.crawl.manual_invalid', {url: invalid})); return; }

  const btn = document.getElementById('btn-crawl');
  btn.disabled = true;
  btn.innerHTML = '<span class="spinner"></span> ' + t('gui.common.running');

  try {
    const res = await fetch('/gui/api/urls.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ urls }),
    });
    const json = await res.json();
    btn.disabled = false;
    btn.innerHTML = t('gui.crawl.adopt');
    if (json.success) {
      showAlert('crawl-alert', t('gui.crawl.manual_success', {count: json.count}), 'success');
      markDone(2);
    } else {
      showAlert('crawl-alert', json.error || t('gui.crawl.manual_error'));
    }
  } catch(e) {
    btn.disabled = false;
    btn.innerHTML = t('gui.crawl.adopt');
    showAlert('crawl-alert', t('gui.config.network_error', {message: e.message}));
  }
}

// ═══════════════════════════════════════════════════ STEP 3: URLS
async function loadUrls() {
  try {
    const res = await fetch('/gui/api/urls.php');
    const json = await res.json();
    allUrls = json.urls || [];
    renderUrls(allUrls);
    updateUrlCount(allUrls.length);
    if (allUrls.length > 0) markDone(3);
  } catch(e) {
    showAlert('urls-alert', t('gui.urls.load_error', {message: e.message}));
  }
}

function renderUrls(urls) {
  document.getElementById('urlList').value = urls.join('\n');
}

function updateUrlCount(n) {
  document.getElementById('url-count').textContent = t('gui.urls.count', {count: n});
}

function filterUrls() {
  const filter = document.getElementById('url-filter').value.toLowerCase();
  const filtered = filter ? allUrls.filter(u => u.toLowerCase().includes(filter)) : allUrls;
  renderUrls(filtered);
  updateUrlCount(filtered.length);
}

async function saveUrls() {
  clearAlert('urls-alert');
  const text = document.getElementById('urlList').value;
  const urls = text.split('\n').map(l => l.trim()).filter(l => l !== '');

  try {
    const res = await fetch('/gui/api/urls.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ urls }),
    });
    const json = await res.json();
    if (json.success) {
      allUrls = urls;
      updateUrlCount(urls.length);
      showAlert('urls-alert', t('gui.urls.saved', {count: json.count}), 'success');
      markDone(3);
    } else {
      showAlert('urls-alert', json.error || t('gui.urls.save_error'));
    }
  } catch(e) {
    showAlert('urls-alert', t('gui.config.network_error', {message: e.message}));
  }
}

// ═══════════════════════════════════════════════════ STEP 4: GENERATE
function generateScenarios() {
  if (generating) return;
  clearAlert('gen-alert');
  const test      = document.getElementById('gen-test').value.trim();
  const reference = document.getElementById('gen-reference').value.trim();

  if (!test || !reference) { showAlert('gen-alert', t('gui.gen.domains_required')); return; }
  if (!/^https?:\/\//i.test(test))      { showAlert('gen-alert', t('gui.gen.url_scheme')); return; }
  if (!/^https?:\/\//i.test(reference)) { showAlert('gen-alert', t('gui.gen.url_scheme')); return; }

  generating = true;
  termClear('gen-output');
  const btn = document.getElementById('btn-generate');
  btn.disabled = true;
  btn.innerHTML = '<span class="spinner"></span> ' + t('gui.gen.running');

  const url = `/gui/api/stream/generate.php?test=${encodeURIComponent(test)}&reference=${encodeURIComponent(reference)}`;

  runStream(url, 'gen-output', (success) => {
    generating = false;
    btn.disabled = false;
    btn.innerHTML = t('gui.gen.run');
    if (success) {
      showAlert('gen-alert', t('gui.gen.success'), 'success');
      markDone(4);
    } else {
      showAlert('gen-alert', t('gui.gen.error'));
    }
  });
}

// ═══════════════════════════════════════════════════ STEP 5: TESTS
async function loadStatus() {
  try {
    const res = await fetch('/gui/api/scenarios-status.php');
    const json = await res.json();
    document.getElementById('count-pending').textContent = json.pending;
    document.getElementById('count-active').textContent  = json.active;
    document.getElementById('count-done').textContent    = json.done;

    const infoEl = document.getElementById('active-info');
    if (json.activeFile) {
      infoEl.style.display = 'block';
      infoEl.textContent = t('gui.test.active_info', {file: json.activeFile, count: json.activeUrlCount});
    } else {
      infoEl.style.display = 'none';
    }

    const reportLink = document.getElementById('report-link');
    reportLink.style.display = json.reportExists ? 'inline-flex' : 'none';

    updateTestButtons(json);
  } catch(e) {
    showAlert('test-alert', t('gui.test.status_error'), 'warning');
  }
}

function updateTestButtons(status) {
  const hasActive  = status.active > 0;
  const hasPending = status.pending > 0;
  const hasRef     = !!status.referenceExists;
  document.getElementById('btn-next').disabled      = !hasPending || hasActive;
  document.getElementById('btn-reference').disabled = !hasActive;
  document.getElementById('btn-test').disabled      = !hasActive || !hasRef;
  document.getElementById('btn-done').disabled      = !hasActive;
  document.getElementById('btn-skip').disabled      = !hasActive;
  document.getElementById('btn-reset').disabled     = (status.pending + status.active + status.done) === 0;

  // Hint when active but no reference yet
  const testBtn = document.getElementById('btn-test');
  if (hasActive && !hasRef) {
    testBtn.title = t('gui.test.need_reference_first');
  } else {
    testBtn.title = '';
  }
}

function runManage(action) {
  if (backstopRunning) return;
  clearAlert('test-alert');
  termClear('test-output');

  setTestRunning(true, action);
  const url = `/gui/api/stream/manage.php?action=${action}`;
  runStream(url, 'test-output', (success) => {
    setTestRunning(false, null);
    loadStatus();
    if (!success) showAlert('test-alert', t('gui.test.action_error'), 'warning');
  });
}

function runBackstop(action) {
  if (backstopRunning) return;
  clearAlert('test-alert');
  termClear('test-output');
  document.getElementById('report-banner').style.display = 'none';

  setTestRunning(true, action);
  const url = `/gui/api/stream/backstop.php?action=${action}`;
  termAppend('test-output', `$ backstop ${action} --config ./backstop.js`, 't-info');

  runStream(url, 'test-output', (success) => {
    setTestRunning(false, null);
    loadStatus();
    if (action === 'test') {
      if (success) {
        // Auto-open report and show banner
        window.open('/backstop_data/html_report/index.html', '_blank');
        document.getElementById('report-banner').style.display = 'flex';
      } else {
        showAlert('test-alert', t('gui.test.mismatches'), 'warning');
        // Still show the report link since report was generated
        document.getElementById('report-banner').querySelector('.rb-sub').textContent =
          t('gui.test.mismatches_sub');
        document.getElementById('report-banner').style.display = 'flex';
      }
    } else {
      // reference
      if (success) {
        showAlert('test-alert', t('gui.test.reference_ok'), 'success');
      } else {
        showAlert('test-alert', t('gui.test.reference_error'), 'warning');
      }
    }
  });
}

function setTestRunning(running, action) {
  backstopRunning = running;
  ['btn-next','btn-reference','btn-test','btn-done','btn-skip','btn-reset'].forEach(id => {
    const btn = document.getElementById(id);
    if (running) {
      btn.disabled = true;
      if (btn.id === 'btn-' + (action === 'reference' ? 'reference' : action === 'test' ? 'test' : action)) {
        btn.innerHTML = `<span class="spinner"></span> ${t('gui.common.running')}`;
      }
    } else {
      // Restore labels
      const labels = {
        'btn-next':      t('gui.test.next'),
        'btn-reference': t('gui.test.reference'),
        'btn-test':      t('gui.test.run'),
        'btn-done':      t('gui.test.mark_done'),
        'btn-skip':      t('gui.test.skip'),
        'btn-reset':     t('gui.test.reset_all'),
      };
      btn.innerHTML = labels[btn.id] || btn.innerHTML;
    }
  });
}

function confirmReset() {
  if (!confirm(t('gui.test.reset_confirm_long'))) return;
  runManage('reset');
}

async function confirmCleanup() {
  if (!confirm(t('gui.test.cleanup_confirm_long'))) return;

  const btn = document.getElementById('btn-cleanup');
  btn.disabled = true;
  btn.textContent = t('gui.test.cleanup_running');
  clearAlert('test-alert');
  document.getElementById('report-banner').style.display = 'none';

  try {
    const res = await fetch('/gui/api/cleanup.php', { method: 'POST' });
    const json = await res.json();
    if (json.success) {
      showAlert('test-alert', t('gui.test.cleanup_ok', {count: json.deleted}), 'success');
      loadStatus();
    } else {
      showAlert('test-alert', t('gui.test.cleanup_fail', {error: json.error || '—'}));
    }
  } catch(e) {
    showAlert('test-alert', t('gui.config.network_error', {message: e.message}));
  } finally {
    btn.disabled = false;
    btn.innerHTML = t('gui.test.cleanup');
  }
}
</script>
</body>
</html>
