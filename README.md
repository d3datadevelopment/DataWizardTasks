[![deutsche Version](https://logos.oxidmodule.com/de2_xs.svg)](README.md)
[![english version](https://logos.oxidmodule.com/en2_xs.svg)](README.en.md)

# D³ Data Wizard Aufgaben für OXID eShop

Dieses Modul enthält nützliche Beispielaufgaben für den D3 `DataWizard` im OXID eShop und dient als Implementierungsreferenz für eigene Exporte oder Tasks.

## Schnellinstallation

Auf der Konsole im Shoproot (oberhalb von source und vendor) folgenden Befehl ausführen:

```bash
composer require d3/datawizardtasks
``` 

Aktivieren Sie DataWizard und dieses Modul im Shopadmin unter "Erweiterungen -> Module". Die Beispiele für querylose Exporte und Streaming-Renderer benötigen einen DataWizard-Stand mit `hasQuery()` beziehungsweise Unterstützung für eigene `StreamingRendererInterface`-Renderer; die Composer-Vorgabe `^3.0` allein stellt diese Fähigkeiten nicht sicher.

## Enthaltene Beispiele

- **Inaktive Kategorien:** listet deaktivierte Kategorien mit direkt zugeordneten Artikeln, deren `OXACTIVE`-Flag gesetzt ist.
- **Bestellungskennzahlen nach Monat:** zählt Bestellungen und berechnet eine durchschnittliche Warenkorbhöhe für den gewählten Zeitraum. Der Endtag ist eingeschlossen; die Beispielabfrage filtert nicht nach Shop oder Stornostatus. Zahlen werden im SQL-Ergebnis numerisch zurückgegeben; die Darstellung hängt von Treiber und Exportformat ab.
- **CMS-Sonderzeichen:** ein Export findet Inhalte mit `-&gt;`; eine Aktion ersetzt diese Zeichenfolge durch `->` in den berücksichtigten Sprachfeldern.
- **Fehlende `oxartextends`-Einträge:** die Aktion ergänzt Datensätze für Artikel ohne zugehörigen Eintrag. Unbekannte Pflichtfelder ohne Standardwert verhindern die Query-Erzeugung und Ausführung. Eine standardmäßig deaktivierte Checkbox erlaubt Platzhalter für unterstützte Datentypen; diese können fachlich unpassend sein. Nicht unterstützte Typen bleiben gesperrt.
- **PHP-Laufzeitinformationen:** ein Export ohne SQL zeigt ausgewählte PHP-Einstellungen und Erweiterungen des ausführenden Prozesses. Die Ausgabe kann technische Informationen enthalten.
- **JSON Lines:** ein Renderer stellt JSONL als zusätzliches Exportformat bereit; jede Zeile enthält einen Datensatz als JSON.

Die Profile sind Beispiele und können Shop- oder Modulbesonderheiten nicht allgemein berücksichtigen. Prüfen Sie insbesondere datenverändernde Aktionen und Platzhalter vor dem produktiven Einsatz in einer Testumgebung.

## Eigene Exporte, Aktionen und Exportformate entwickeln

Der DataWizard stellt Aufgaben im Shopbackend bereit. Ein Export liest Daten und stellt sie in einem unterstützten Format bereit; eine Aktion verändert Daten. Dieses Paket enthält Beispiele für beides. Eigene Aufgaben gehören in ein eigenes OXID-Modul mit Composer-Autoloading und einer Abhängigkeit auf `d3/datawizard`, nicht in dieses Beispielpaket.

Die Referenzimplementierung dient ausschließlich als Vorlage und ist nicht als Grundlage für eigene Erweiterungen vorgesehen. Legen Sie für kundenindividuelle Exporte und Aktionen ein eigenes Plugin (OXID-Modul) an und registrieren Sie Ihre Tasks dort.

### Task-Registry erweitern

Die Registry ist `D3\DataWizard\Application\Model\Configuration`. Erweitern Sie sie über die OXID-Modulkette und registrieren Sie Ihre Tasks in `configure()`. Rufen Sie immer `parent::configure()` auf, damit auch die Registrierungen anderer Module erhalten bleiben. Die mitgelieferte [Configuration](Modules/DataWizard/Application/Model/Configuration.php) zeigt die vollständige Registrierung der Beispiele.

Beispiel für die Erweiterung im eigenen Modul:

```php
namespace Acme\MyModule\Modules\DataWizard;

use D3\DataWizard\Application\Model\Configuration as Registry;
use Acme\MyModule\Export\ExampleExport;
use Acme\MyModule\Action\ExampleAction;

class Configuration extends Configuration_parent
{
    public function configure()
    {
        parent::configure();
        $this->registerExport(Registry::GROUP_SHOP, oxNew(ExampleExport::class));
        $this->registerAction(Registry::GROUP_ARTICLES, oxNew(ExampleAction::class));
    }
}
```

In der `metadata.php` des eigenen Moduls gehört die Erweiterung in das `extend`-Array:

```php
'extend' => [
    \D3\DataWizard\Application\Model\Configuration::class =>
        \Acme\MyModule\Modules\DataWizard\Configuration::class,
],
```

Verwenden Sie eine vorhandene `GROUP_*`-Konstante oder einen eigenen übersetzbaren Gruppenschlüssel. Die Registry bildet die Task-ID aus dem Klassennamen; mehrfaches Registrieren derselben Klasse innerhalb einer Gruppe ersetzt den vorherigen Eintrag. Verwenden Sie für unterschiedliche Profile eigene Task-Klassen. Aktivieren Sie das eigene Modul und prüfen Sie die Modulkette im Shopbackend.

### Task implementieren

Ein Export erweitert `ExportBase`, eine Aktion `ActionBase`. Beide implementieren mindestens `getTitle()` und `getQuery()`; `getDescription()` kann den Zweck und die Auswirkungen erklären. Verwenden Sie übersetzte, für Betreiber verständliche Texte. `getQuery()` liefert immer ein Array aus SQL und Parametern:

```php
public function getQuery(): array
{
    return [
        'SELECT OXID, OXTITLE FROM oxarticles WHERE OXACTIVE = :active',
        ['active' => 1],
    ];
}
```

Dies ist das Grundgerüst eines Exports. Die Basisklasse erwartet ein mit `SELECT` beginnendes Statement. Die Spaltennamen werden als Exportüberschriften verwendet. Parameter binden Werte; Tabellen- und Spaltennamen lassen sich nicht als Werte binden und müssen aus vertrauenswürdigen Quellen stammen und bei Bedarf mit der Datenbankverbindung als Identifier quotiert werden. Siehe [InactiveCategories](Application/Model/Exports/InactiveCategories.php) für ein vollständiges Beispiel.

Ein Export kann Daten auch ohne SQL bereitstellen: [PhpRuntimeInfo](Application/Model/Exports/PhpRuntimeInfo.php) überschreibt dazu `getExportData()` und meldet über `hasQuery()` zurück, dass keine Query-Vorschau angeboten wird. Für solche Exporte wird der reguläre Streaming-Query-Pfad nicht verwendet.

Eine Aktion gibt entsprechend ein veränderndes Statement und seine Parameter zurück. Die Basisklasse führt es mit `executeStatement()` aus und erfasst die Anzahl betroffener Zeilen; ein mit `SELECT` beginnendes Statement wird abgelehnt. Siehe [FixWysiwygSpecialChars](Application/Model/Actions/FixWysiwygSpecialChars.php). Beschreiben Sie Umfang und mögliche Auswirkungen insbesondere bei Änderungen über viele Datensätze oder Shops hinweg.

### Eigene Exportformate registrieren

Die `D3\DataWizard\Application\Model\ExportRenderer\RendererBridge` verwaltet die verfügbaren Exportformate. Der mitgelieferte [JSONL-Renderer](Application/Model/ExportRenderer/JsonLines.php) und seine [Bridge-Erweiterung](Modules/DataWizard/Application/Model/ExportRendererBridge.php) zeigen die Integration. Zusätzliche Formate, etwa PDF, werden im kundenindividuellen Modul implementiert und über die OXID-Modulkette registriert. Überschreiben Sie `getRendererList()` und übernehmen Sie die Einträge der Elternklasse:

```php
namespace Acme\MyModule\Modules\DataWizard;

use Acme\MyModule\ExportRenderer\PdfRenderer;

class RendererBridge extends RendererBridge_parent
{
    public function getRendererList(): array
    {
        $renderers = parent::getRendererList();
        $renderers['ACME_PDF'] = oxNew(PdfRenderer::class);
        return $renderers;
    }
}
```

Ergänzen Sie im `extend`-Array Ihrer `metadata.php` zusätzlich:

```php
\D3\DataWizard\Application\Model\ExportRenderer\RendererBridge::class =>
    \Acme\MyModule\Modules\DataWizard\RendererBridge::class,
```

`PdfRenderer` ist hier eine selbst zu implementierende Klasse, kein mitgelieferter Renderer. Sie implementiert `RendererInterface`: `getContent(iterable $rows, iterable $fieldNames): string` erzeugt den Dateiinhalt, `getFileExtension()` liefert die Dateiendung und `getTitleTranslationId()` den Übersetzungsschlüssel für die Formatauswahl. Stellen Sie diesen Schlüssel in den Sprachdateien Ihres Moduls bereit. Der eindeutige Format-Schlüssel erscheint in der Auswahlliste; `QUERY` ist für die SQL-Vorschau reserviert. Nutzen Sie einfache, eindeutige Schlüssel ohne Sonderzeichen und vermeiden Sie Kollisionen mit vorhandenen Formaten.

Für Streaming implementiert ein Renderer zusätzlich `StreamingRendererInterface`; [JsonLines](Application/Model/ExportRenderer/JsonLines.php) zeigt beide Ausgabewege. Der DataWizard verwendet den Streaming-Pfad auch für eigene Renderer, sofern keine relevanten Export-Erweiterungsmethoden überschrieben wurden. Andernfalls nutzt er den regulären, gepufferten Pfad. Testen Sie beide Wege und den Speicherbedarf bei großen Exporten.

### Eingaben, Vorschau und Tests

Registrieren Sie FormManager-Felder mit `registerFormElement()`, lesen und normalisieren Sie übergebene Werte und validieren Sie sie serverseitig vor der Verwendung. `run()` prüft die registrierten Felder; fachliche Regeln wie Datumsbereiche müssen Sie selbst prüfen und bei ungültigen Eingaben etwa eine `TaskException` mit verständlicher Meldung auslösen. [KeyFigures](Application/Model/Exports/KeyFigures.php) zeigt Datumsfelder, Validierung und gebundene Parameter. Vergeben Sie eindeutige Feldnamen, da Aufgaben im Backend ein Formular teilen.

Die SQL-Vorschau ruft nach `init()` direkt `getQuery()` auf, ohne `run()` und dessen Formularvalidierung. Legen Sie deshalb Prüfungen, die auch für eine sichere Query-Erzeugung nötig sind, in den gemeinsamen Query-Pfad. Die Vorschau führt die Query nicht aus. Aktionsspezifische Prüfungen unmittelbar vor der Ausführung können zusätzlich in `executeAction()` erfolgen; bei einer Überschreibung muss die Ausführung über `parent::executeAction()` erfolgen, damit das Ergebnis erfasst wird. [FixArtextendsItems](Application/Model/Actions/FixArtextendsItems.php) prüft erforderliche Felder sowohl bei der Query-Erzeugung als auch vor der Ausführung. Können Pflichtfelder nicht initialisiert werden, erscheint deshalb auch in der Vorschau eine Fehlermeldung statt der Query.

Testen Sie gültige und ungültige Eingaben, Vorschau und tatsächliche Ausführung sowie leere und große Ergebnismengen. Prüfen Sie bei Aktionen betroffene Datensätze, Wiederholbarkeit, Shop- und Sprachkontext und das Verhalten bei Fehlern zuerst in einer Testumgebung. Prüfen Sie Berechtigungen, Datenschutz und die Sicherheit von Exportformaten für die konkrete Installation.

## Changelog

Siehe [CHANGELOG](CHANGELOG.md) für weitere Informationen.

## Lizenz dieser Software (d3/datawizardtasks)
(Stand: 27.07.2021)

```
Copyright (c) D3 Data Development (Inh. Thomas Dartsch)

Diese Software wird unter der GNU GENERAL PUBLIC LICENSE Version 3 vertrieben.
```

Die vollständigen Copyright- und Lizenzinformationen entnehmen Sie bitte der [LICENSE](LICENSE.md)-Datei, die mit diesem Quellcode verteilt wurde.
