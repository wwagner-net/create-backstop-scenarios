# Contributing to BackstopJS Scenario Generator

Vielen Dank für dein Interesse, zu diesem Projekt beizutragen! Wir freuen uns über jeden Beitrag, ob groß oder klein.

## 🚀 Schnellstart für Contributors

### Voraussetzungen

- Git
- PHP 8.2+
- Node.js
- BackstopJS (`npm install -g backstopjs`)
- Optional: DDEV (empfohlen für lokale Entwicklung)

### Setup

1. **Repository forken**
   - Klicke auf "Fork" auf der GitHub-Seite

2. **Repository klonen**
   ```bash
   git clone https://github.com/DEIN-USERNAME/create-backstop-scenarios.git
   cd create-backstop-scenarios
   ```

3. **DDEV starten (optional)**
   ```bash
   ddev start
   ```

4. **Konfiguration erstellen**
   ```bash
   # Option 1: Interaktiver Setup-Wizard (empfohlen)
   ddev exec php setup.php

   # Option 2: Manuelle Konfiguration
   cp config.example.json config.json
   ```

5. **Branch erstellen**
   ```bash
   git checkout -b feature/deine-neue-funktion
   # oder
   git checkout -b bugfix/beschreibung-des-bugs
   ```

## 📝 Wie du beitragen kannst

### 1. Bug Reports

Hast du einen Bug gefunden? Erstelle ein Issue mit:
- Beschreibung des Problems
- Schritte zur Reproduktion
- Erwartetes vs. tatsächliches Verhalten
- Deine Umgebung (OS, PHP-Version, BackstopJS-Version)

### 2. Feature Requests

Hast du eine Idee für eine neue Funktion?
- Erstelle ein Issue mit dem Label "enhancement"
- Beschreibe die Funktion und warum sie nützlich wäre
- Diskutiere mit den Maintainern, bevor du anfängst zu coden

### 3. Code Contributions

#### Pull Request Prozess

1. **Stelle sicher, dass dein Code funktioniert**
   - Teste alle PHP-Skripte
   - Prüfe, ob BackstopJS noch korrekt läuft

2. **Code-Stil**
   - PHP: PSR-12 Coding Standard
   - Verwende aussagekräftige Variablennamen
   - Kommentare auf Englisch oder Deutsch

3. **Commit Messages**
   Folge dem Format:
   ```
   [TYPE] Kurze Beschreibung (max 50 Zeichen)

   Detailliertere Erklärung, falls nötig.
   ```

   **Types:**
   - `[FEATURE]` - Neue Funktionalität
   - `[BUGFIX]` - Fehlerbehebung
   - `[DOCS]` - Dokumentationsänderungen
   - `[TASK]` - Refactoring, Code-Verbesserungen
   - `[BREAKING]` - Breaking Changes

   **Beispiele:**
   ```
   [FEATURE] Add --timeout parameter to crawler.php

   Allows users to configure request timeout for slow servers.
   Default remains 30 seconds.
   ```

   ```
   [BUGFIX] Fix URL duplicate checking in crawler

   Hash tables were not properly initialized, causing duplicates.
   ```

4. **CHANGELOG.md aktualisieren**

   **WICHTIG:** Jeder Pull Request MUSS das CHANGELOG.md aktualisieren!

   Füge deine Änderung unter `## [Unreleased]` hinzu:

   ```markdown
   ## [Unreleased]

   ### Added
   - Deine neue Funktion hier

   ### Fixed
   - Dein Bugfix hier

   ### Changed
   - Deine Änderung hier
   ```

   Siehe [Keep a Changelog](https://keepachangelog.com/) für Details.

5. **Pull Request erstellen**
   - Klare Beschreibung: Was ändert sich und warum?
   - Referenziere relevante Issues (#123)
   - Screenshots/GIFs für UI-Änderungen

## 🔢 Versionierung

Wir folgen [Semantic Versioning](https://semver.org/):

- **MAJOR** (1.x.x): Breaking Changes
- **MINOR** (x.1.x): Neue Features (rückwärtskompatibel)
- **PATCH** (x.x.1): Bugfixes (rückwärtskompatibel)

**Beispiele:**
- Neue Funktion hinzufügen → MINOR bump
- Bug beheben → PATCH bump
- API ändern (breaking) → MAJOR bump

## 🧪 Testing

Bevor du einen Pull Request einreichst:

1. **Teste alle Skripte manuell**
   ```bash
   # Crawler testen
   ddev exec php crawler.php --sitemap https://example.com/sitemap.xml --max-urls=10

   # Scenario-Generierung testen
   ddev exec php create-backstop-scenarios.php \
     --test=https://test.ddev.site \
     --reference=https://example.com

   # Workflow testen
   ddev exec php manage-scenarios.php status
   ```

2. **BackstopJS testen**
   ```bash
   backstop reference --config ./backstop.js
   backstop test --config ./backstop.js
   ```

3. **PHP-Syntax prüfen**
   ```bash
   ddev exec php -l setup.php
   ddev exec php -l crawler.php
   ddev exec php -l create-backstop-scenarios.php
   ddev exec php -l manage-scenarios.php
   ```

## 📂 Projekt-Struktur verstehen

```
.
├── setup.php                      # Interaktiver Setup-Wizard
├── crawler.php                    # URL-Sammlung (Sitemap/Crawler)
├── create-backstop-scenarios.php  # Scenario-Generierung
├── manage-scenarios.php           # Workflow-Management
├── backstop.js                    # BackstopJS-Konfiguration
├── config.example.json            # Konfigurations-Template
├── config.json                    # Projekt-Konfiguration (generiert, nicht in Git)
├── CONTRIBUTING.md                # Diese Datei
├── CLAUDE.md                      # AI-Assistant-Anleitung
├── CHANGELOG.md                   # Versions-Historie
├── README.md                      # Haupt-Dokumentation
└── .github/                       # GitHub Templates
    ├── PULL_REQUEST_TEMPLATE.md
    └── ISSUE_TEMPLATE/
```

## 🎯 Wo du helfen kannst

### Einsteigerfreundliche Aufgaben

- Dokumentation verbessern
- Rechtschreibfehler korrigieren
- Beispiele hinzufügen
- README übersetzen

### Fortgeschrittene Aufgaben

- Unit Tests hinzufügen (PHPUnit)
- GitHub Actions CI/CD Setup
- Performance-Optimierungen
- Neue Features implementieren

## 💡 Best Practices

### DO ✅

- Einen Branch pro Feature/Bugfix
- Tests schreiben und ausführen
- CHANGELOG.md aktualisieren
- Dokumentation aktualisieren
- Code kommentieren (komplexe Logik)
- Bestehende Code-Konventionen befolgen

### DON'T ❌

- Mehrere unabhängige Änderungen in einem PR
- Breaking Changes ohne Diskussion
- Änderungen ohne CHANGELOG-Update
- Große Refactorings ohne vorherige Absprache
- Externe Dependencies ohne Grund hinzufügen

## 🤝 Code Review Prozess

1. **Automatische Checks** (wenn implementiert)
   - Syntax-Checks
   - Code-Stil

2. **Manuelle Review**
   - Ein Maintainer reviewed deinen Code
   - Feedback wird als Comments hinterlassen
   - Du kannst Änderungen pushen zum selben Branch

3. **Merge**
   - Nach Approval wird dein PR gemerged
   - Dein Name landet in den Contributors

## 📜 Code of Conduct

### Unsere Standards

- Sei respektvoll und konstruktiv
- Akzeptiere konstruktive Kritik
- Fokussiere dich auf das Beste für die Community
- Zeige Empathie für andere Community-Mitglieder

### Unakzeptables Verhalten

- Belästigung jeglicher Art
- Trolling oder beleidigende Kommentare
- Persönliche oder politische Angriffe
- Veröffentlichung privater Informationen

## 🆘 Fragen?

- **Issues:** [GitHub Issues](https://github.com/wwagner-net/create-backstop-scenarios/issues)
- **Diskussionen:** Erstelle ein Issue mit dem Label "question"

## 📄 Lizenz

Indem du zu diesem Projekt beiträgst, stimmst du zu, dass deine Beiträge unter der MIT-Lizenz lizenziert werden.

---

**Vielen Dank, dass du dieses Projekt besser machst! 🎉**
