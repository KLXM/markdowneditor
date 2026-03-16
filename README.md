# MarkdownEditor für REDAXO

Ein moderner Markdown-Editor für REDAXO CMS, basierend auf [EasyMDE](https://github.com/Ionaru/easy-markdown-editor).
Kann als Drop-in-Ersatz für das **markitup**-Addon verwendet werden (kompatible Link-Formate).

![REDAXO](https://img.shields.io/badge/REDAXO-%3E%3D5.13-red)
![PHP](https://img.shields.io/badge/PHP-%3E%3D8.1-blue)

## Features

- **EasyMDE** als Basis – bewährt, leichtgewichtig, CodeMirror-basiert
- **markitup-kompatibel** – gleiche Link-Formate, kann markitup direkt ersetzen ✅
- **Plugin-System** – andere Addons können Toolbar-Buttons und Verarbeitung erweitern ✅
- **Medienpool-Integration** – Bilder direkt aus dem REDAXO-Medienpool einfügen ✅
- **Struktur-Links** – Artikel aus der REDAXO-Struktur verlinken ✅
- **YForm-Datensätze** – YForm-Datensätze als Links einfügen (nutzt YForm's eigene UI) ✅
- **Tabellen-Generator** – Visueller Grid-Picker zum Einfügen von Markdown-Tabellen ✅
- **Drag & Drop Upload** – Bilder per Drag & Drop in den Medienpool hochladen ✅
- **Profilsystem** – Verschiedene Toolbar-Konfigurationen für unterschiedliche Einsatzzwecke ✅
- **Dark Mode** – Vollständige Unterstützung für REDAXO Light, Dark und Auto Theme ✅
- **MBlock-kompatibel** – Funktioniert in MBlock-Formularen ✅
- **MForm Repeater-kompatibel** – Funktioniert in MForm-Repeater-Feldern ✅
- **Live-Vorschau** – Side-by-Side und Fullscreen Preview ✅
- **Tastaturkürzel** – `Ctrl+Shift+M` für Medienpool, `Ctrl+Shift+L` für Linkmap ✅

## Installation

### Über den REDAXO Installer

1. Im Backend unter **Installer** nach „markdowneditor" suchen
2. AddOn installieren und aktivieren

### Manuell

1. Repository nach `redaxo/src/addons/markdowneditor/` klonen
2. Vendor-Dateien installieren:
   ```bash
   cd redaxo/src/addons/markdowneditor
   npm install
   npm run build
   ```
3. AddOn im Backend installieren und aktivieren

## Migration von markitup

Das Addon verwendet die gleichen Markdown-Link-Formate wie markitup. Bestehende Inhalte werden ohne Änderungen korrekt gerendert.

### Modul-Eingabe anpassen

```diff
- <textarea class="markitupEditor-markdown_full" name="REX_INPUT_VALUE[1]">REX_VALUE[1]</textarea>
+ <textarea class="markdowneditor-editor" data-markdowneditor-profile="full" name="REX_INPUT_VALUE[1]">REX_VALUE[1]</textarea>
```

### Modul-Ausgabe anpassen

```diff
- use FriendsOfRedaxo\MarkItUp\MarkItUp;
- echo MarkItUp::parseOutput('markdown', 'REX_VALUE[id=1 output="html"]');
+ use KLXM\MarkdownEditor\Utils\MarkdownOutput;
+ echo MarkdownOutput::parseOutput('markdown', 'REX_VALUE[id=1 output="html"]');
```

Die `parseOutput('markdown', ...)`-Methode ist vollständig kompatibel mit markitup.

## Verwendung

### In Modulen (mit MForm)

```php
$mform = new MForm();
$mform->addTextAreaField('1', [
    'class' => 'markdowneditor-editor',
    'data-markdowneditor-profile' => 'default'
]);
echo $mform->show();
```

### In Modulen (ohne MForm)

```html
<textarea
    class="markdowneditor-editor"
    data-markdowneditor-profile="default"
    name="REX_INPUT_VALUE[1]"
>REX_VALUE[1]</textarea>
```

### Ausgabe im Template/Modul

```php
use KLXM\MarkdownEditor\Utils\MarkdownOutput;

// Markdown → HTML (mit REDAXO-Link-Auflösung)
echo MarkdownOutput::parse('REX_VALUE[1]');

// Oder markitup-kompatibel:
echo MarkdownOutput::parseOutput('markdown', 'REX_VALUE[id=1 output="html"]');
```

### Mit MBlock

```php
use FriendsOfRedaxo\MForm;
use FriendsOfRedaxo\MBlock\MBlock;

$mform = new MForm();
$mform->addTextAreaField('1.0.text', [
    'class' => 'markdowneditor-editor',
    'data-markdowneditor-profile' => 'minimal'
]);
echo MBlock::show('1', $mform->show());
```

### Mit MForm Repeater

```php
use FriendsOfRedaxo\MForm;

$formtorepeat = MForm::factory();
$formtorepeat->addFieldsetArea('Inhalt', MForm::factory()
    ->addTextAreaField('text', [
        'class' => 'markdowneditor-editor',
        'data-markdowneditor-profile' => 'default',
        'label' => 'Text'
    ])
);

$mform = MForm::factory();
$mform->addRepeaterElement(1, $formtorepeat);

echo $mform->show();
```

Der Editor wird beim Hinzufügen und Verschieben von Repeater-Elementen automatisch neu initialisiert.

### In YForm

Im YForm Table Manager bei einem `textarea`-Feld folgendes JSON in das Attribut-Feld eintragen:

```json
{"class":"markdowneditor-editor","data-markdowneditor-profile":"default"}
```

## REDAXO-Link-Formate (markitup-kompatibel)

Der Editor verwendet die gleichen Link-Formate wie markitup:

| Format | Beispiel | Beschreibung |
|--------|----------|-------------|
| Medienpool-Bild | `![bild](index.php?rex_media_type=markitupImage&rex_media_file=foto.jpg)` | Bild aus dem Medienpool |
| Datei-Link | `[dokument.pdf](/media/dokument.pdf)` | Download-Link |
| Artikel-Link | `[Seite](redaxo://42)` | REDAXO-Artikel (ID) |
| YForm-Link | `[Eintrag](yform:news/5)` | YForm-Datensatz |

Zusätzlich wird auch das Legacy-Format `media://datei.jpg` und `yform://table/id` in der Ausgabe korrekt aufgelöst.

## Profile

Profile steuern die Toolbar-Konfiguration und das Verhalten des Editors.

### Standard-Profile

- **default** – Alle wichtigen Funktionen
- **minimal** – Nur Basis-Formatierung
- **full** – Alle Funktionen inklusive Undo/Redo und Codeblöcke

### Eigene Profile erstellen

Im Backend unter **MarkdownEditor → Profile** können neue Profile angelegt werden.

### Verfügbare Toolbar-Buttons

**Standard EasyMDE:**
`bold`, `italic`, `strikethrough`, `heading`, `heading-smaller`, `heading-bigger`, `code`, `quote`, `unordered-list`, `ordered-list`, `clean-block`, `link`, `image`, `horizontal-rule`, `preview`, `side-by-side`, `fullscreen`, `undo`, `redo`, `guide`

**REDAXO-spezifisch:**
`rex-media`, `rex-link`, `rex-yform-link`

**Erweitert:**
`table` – Visueller Grid-Picker (Zeilen/Spalten per Mausauswahl)

**Separator:** `|`

## Vendor aktualisieren (Entwickler)

```bash
cd redaxo/src/addons/markdowneditor

# EasyMDE auf neueste Version aktualisieren
npm run update-vendor

# Oder manuell:
npm update easymde
npm run build
```

## Extension Points

### MARKDOWNEDITOR_PLUGINS

Ermöglicht es anderen Addons, Plugins für den Editor zu registrieren (eigene Toolbar-Buttons, Markdown-Verarbeitung, JS/CSS-Dateien).

```php
// In boot.php eines anderen Addons:
rex_extension::register('MARKDOWNEDITOR_PLUGINS', function (rex_extension_point $ep) {
    $plugins = $ep->getSubject();
    $plugins[] = new MyCustomPlugin(); // implementiert KLXM\MarkdownEditor\Plugin\PluginInterface
    return $plugins;
});
```

Siehe [Plugin-System](#plugin-system) für Details.

### MARKDOWNEDITOR_RESOLVE_YFORM_LINK

Wird bei der Auflösung von `yform:TABLE/ID`-Links in der Ausgabe aufgerufen.

```php
rex_extension::register('MARKDOWNEDITOR_RESOLVE_YFORM_LINK', function (rex_extension_point $ep) {
    $table = $ep->getParam('table');
    $id = $ep->getParam('id');

    if ($table === 'news') {
        return '/news/' . $id . '/';
    }
});
```

## Plugin-System

Andere Addons können das MarkdownEditor-Addon um eigene Funktionen erweitern, indem sie die `PluginInterface` implementieren.

### Plugin erstellen

```php
namespace MyAddon;

use KLXM\MarkdownEditor\Plugin\PluginInterface;

class GalleryPlugin implements PluginInterface
{
    public function getName(): string
    {
        return 'my-gallery';
    }

    public function getToolbarButtons(): array
    {
        return [
            [
                'name' => 'my-gallery',
                'title' => 'Galerie einfügen',
                'icon' => 'fa fa-th',
                'action' => 'myGalleryAction', // JS-Funktionsname
            ],
        ];
    }

    public function getJsFiles(): array
    {
        return [rex_addon::get('my_addon')->getAssetsUrl('js/gallery-plugin.js')];
    }

    public function getCssFiles(): array
    {
        return [];
    }

    public function getJsProperties(): array
    {
        return [];
    }

    public function processMarkdown(string $content): ?string
    {
        // Optional: Eigene Markdown-Syntax vor dem Parsen verarbeiten
        return null; // null = keine Änderung
    }

    public function processHtml(string $html): ?string
    {
        // Optional: HTML nach dem Parsen nachbearbeiten
        return null;
    }
}
```

### Plugin registrieren

In der `boot.php` des anderen Addons:

```php
if (rex_addon::get('markdowneditor')->isAvailable()) {
    rex_extension::register('MARKDOWNEDITOR_PLUGINS', function (rex_extension_point $ep) {
        $plugins = $ep->getSubject();
        $plugins[] = new \MyAddon\GalleryPlugin();
        return $plugins;
    });
}
```

### Plugin-Button in Profil verwenden

Den Button-Namen (z.B. `my-gallery`) in die Toolbar-Konfiguration eines Profils aufnehmen. Plugin-Buttons werden automatisch erkannt.

### JS-Seitige Plugin-Events

```javascript
// Plugin kann auf Custom Events reagieren
MarkdownEditor.on('plugin-action', function (event, buttonName, editor) {
    if (buttonName === 'my-gallery') {
        // Galerie-Auswahl öffnen, Markdown einfügen
        var cm = editor.codemirror;
        cm.replaceSelection('![Galerie](gallery://1)');
    }
});
```

## Tabellen-Generator

Der `table`-Button öffnet ein visuelles Grid-Overlay (10 × 8 Zellen). Per Hover wird die gewünschte Tabellengröße angezeigt, per Klick wird die Markdown-Tabelle eingefügt:

```markdown
| Spalte 1 | Spalte 2 | Spalte 3 |
| --- | --- | --- |
|  |  |  |
|  |  |  |
```

## JavaScript API

```javascript
// Editor-Instanz für ein Textarea holen
var editor = MarkdownEditor.getInstance(textarea);

// Editor manuell erstellen
MarkdownEditor.create(textarea);

// Editor zerstören
MarkdownEditor.destroy(textarea);

// Alle Editoren in einem Container initialisieren
MarkdownEditor.init(container);

// Plugin-Events abhören
MarkdownEditor.on('eventName', callback);

// Plugin-Events auslösen
MarkdownEditor.trigger('eventName', data);
```

## Lizenz

MIT License – siehe [LICENSE.md](LICENSE.md)

## Credits

- [EasyMDE](https://github.com/Ionaru/easy-markdown-editor) – Markdown Editor
- [CodeMirror](https://codemirror.net/) – Code Editor Engine
- [KLXM Crossmedia](https://klxm.de/) – Entwicklung
