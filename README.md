# BackstopJS Scenario Generator

Dieses Repository enthält Skripte zur automatisierten Erzeugung von BackstopJS-Szenarien aus einer CSV-Datei mit URLs.

## Voraussetzungen

- PHP oder DDEV ist auf deinem System installiert.
- Node.js ist auf deinem System installiert.
- BackstopJS ist auf deinem System installiert.

## Dateien

1. **PHP-Skript (create-backstop-scenarios.php)**
2. **BackstopJS-Konfigurationsdatei (backstop.js)**
3. **PHP-Crawler-Skript (crawler.php)**

### 1. PHP-Crawler-Skript (crawler.php)

Dieses Skript crawlt die angegebene Referenz-Domain, extrahiert alle relevanten URLs und speichert sie in einer CSV-Datei. Es filtert dabei Links auf Dateien, JavaScript, tel:, mailto:, und URLs mit Parametern.

Es kann als Alternative zu Tools wie dem Screaming Frog SEO Spider genutzt werden:

**Wichtig:** Die erzeugte CSV-Datei sollte überpfrüft und ggf. bereinigt werden. Es kann auch sein, dass die Liste der URLs nicht vollständig ist.

### 2. PHP-Skript (create-backstop-scenarios.php)

Dieses Skript liest die URLs aus einer CSV-Datei ein, teilt sie in Blöcke von jeweils 40 URLs auf und generiert JavaScript-Dateien, die diese URLs enthalten.

### 3. BackstopJS-Konfigurationsdatei (backstop.js)
Diese Datei importiert die generierten JavaScript-Dateien und verwendet die URLs zur Konfiguration der BackstopJS-Szenarien.

## Nutzung der Skripte

1. Führe optional das PHP-Skript `crawler.php` aus, um eine CSV-Datei mit einer Liste der Referenz-URLs zu erstellen.
```shell
ddev exec php crawler.php
```
Die gesammelten URLs werden in der Datei crawled_urls.csv gespeichert. Du kannst diese Datei dann manuell prüfen und bereinigen, bevor du sie für die Tests verwendest.

2. CSV-Datei erzeugen: Wenn du nicht die `crawler.php`verwendest, kannst du ein Tool wie den "Screaming Frog SEO Spider" nutzen, um eine CSV-Datei mit URLs zu generieren. Stelle sicher, dass die Datei nur eine Spalte mit gültigen URLs enthält.

3. PHP-Skript ausführen: Führe das PHP-Skript aus, um die JavaScript-Dateien zu generieren.

```shell
ddev exec php create-backstop-scenarios.php
```

4. BackstopJS-Szenarien ausführen: Führe die BackstopJS-Befehle aus, um die Referenzbilder zu erstellen und die Tests zu starten.
```shell
backstop reference --config ./backstop.js && backstop test --config ./backstop.js
```
## In Projekten verwenden

Das Repository kann auch direkt zum Testen von Projekten genutzt werden. Beispielhafte Vorgehensweise:

1. Repository klonen
2. `ddev start`
3. Neuen Branch für Projekt erstellen: `git checkout -b projektname`
4. `backstop init`
5. Die dadurch erzeugte Datei `backstop.json` kann direkt gelöscht werden
6. Dann die Referenz- und Test-Domains in den Dateien anpassen und wie oben beschrieben vorgehen
7. Am Ende könnte man die erzeugten Dateien im Projekt-Branch committen: `git add . && git commit -m "Projektname getestet`
8. Dann wieder in den main-Branch wechseln: `git checkout main`
9. Wenn der Test-Branch nicht mehr benötigt wird, einfach löschen: `git branch -D projektname`
