<?php

/**
 * demo.php – Interactive demo page for MarkdownEditor.
 *
 * Shows editors with all available profiles for testing.
 *
 * @var rex_addon $this
 */

$addon = rex_addon::get('markdowneditor');

// Load available profiles from DB
$sql = rex_sql::factory();
$profiles = $sql->getArray(
    'SELECT name, description FROM ' . rex::getTable('markdowneditor_profiles') . ' ORDER BY name ASC',
);

// ---------- Demo: all profiles ----------
$demoContent = '';

foreach ($profiles as $profile) {
    $profileName = rex_escape((string) $profile['name']);
    $profileDesc = rex_escape((string) $profile['description']);

    $sampleMarkdown = <<<'MD'
# Überschrift 1

## Überschrift 2

Dies ist ein **fetter** und *kursiver* Text mit ~~Durchstreichung~~.

### Listen

- Punkt eins
- Punkt zwei
- Punkt drei

1. Erster Schritt
2. Zweiter Schritt
3. Dritter Schritt

### Zitat

> REDAXO ist ein flexibles, mehrsprachiges Content-Management-System.

### Code

Inline `Code` und ein Code-Block:

```php
$article = rex_article::get(1);
echo $article->getName();
```

### Tabelle

| Spalte 1 | Spalte 2 | Spalte 3 |
| -------- | -------- | -------- |
| Zelle    | Zelle    | Zelle    |
| Zelle    | Zelle    | Zelle    |

### Links & Medien

- Artikel-Link: [Startseite](redaxo://1)
- Medien-Link: [Bild](media://beispiel.jpg)
- Externer Link: [REDAXO](https://redaxo.org)
MD;

    $demoContent .= '
    <div class="markdowneditor-demo-profile" style="margin-bottom: 30px;">
        <h4><code>' . $profileName . '</code></h4>
        <p class="text-muted">' . $profileDesc . '</p>
        <textarea class="markdowneditor-editor" data-markdowneditor-profile="' . $profileName . '" rows="12">' . rex_escape($sampleMarkdown) . '</textarea>
    </div>';
}

if ($demoContent === '') {
    $demoContent = '<p class="text-warning">' . $addon->i18n('markdowneditor_demo_no_profiles') . '</p>';
}

$fragment = new rex_fragment();
$fragment->setVar('class', 'edit', false);
$fragment->setVar('title', $addon->i18n('markdowneditor_demo_editors'), false);
$fragment->setVar('body', $demoContent, false);
echo $fragment->parse('core/page/section.php');

// ---------- Demo: Markdown output rendering ----------
$outputDemoMarkdown = <<<'MD'
## Ausgabe-Demo

Dieser Markdown-Text wird serverseitig mit `MarkdownOutput::parse()` gerendert.

- **Fett** und *kursiv*
- `Inline-Code`
- [Externer Link](https://redaxo.org)

> Ein Blockzitat

```
Code-Block
```

| Kopf A | Kopf B |
| ------ | ------ |
| 1      | 2      |
MD;

$renderedHtml = '';
if (class_exists(\KLXM\MarkdownEditor\Utils\MarkdownOutput::class)) {
    $renderedHtml = \KLXM\MarkdownEditor\Utils\MarkdownOutput::parse($outputDemoMarkdown);
}

$outputContent = '
<div class="row">
    <div class="col-sm-6">
        <h4>' . $addon->i18n('markdowneditor_demo_source') . '</h4>
        <pre style="max-height: 400px; overflow: auto;"><code>' . rex_escape($outputDemoMarkdown) . '</code></pre>
    </div>
    <div class="col-sm-6">
        <h4>' . $addon->i18n('markdowneditor_demo_rendered') . '</h4>
        <div class="markdowneditor-rendered-output" style="padding: 15px; border: 1px solid var(--mde-border, #ddd); border-radius: 4px; max-height: 400px; overflow: auto;">
            ' . $renderedHtml . '
        </div>
    </div>
</div>';

$fragment = new rex_fragment();
$fragment->setVar('class', 'info', false);
$fragment->setVar('title', $addon->i18n('markdowneditor_demo_output'), false);
$fragment->setVar('body', $outputContent, false);
echo $fragment->parse('core/page/section.php');

// ---------- Info: Verwendung ----------
$usageContent = '
<p>' . $addon->i18n('markdowneditor_demo_usage_intro') . '</p>

<h5>MForm</h5>
<pre><code>$mform = new MForm();
$mform-&gt;addTextAreaField(\'1\', [
    \'class\' =&gt; \'markdowneditor-editor\',
    \'data-markdowneditor-profile\' =&gt; \'default\'
]);</code></pre>

<h5>REX_VALUE / textarea</h5>
<pre><code>&lt;textarea
    class="markdowneditor-editor"
    data-markdowneditor-profile="full"
    name="REX_INPUT_VALUE[1]"
&gt;REX_VALUE[1]&lt;/textarea&gt;</code></pre>

<h5>' . $addon->i18n('markdowneditor_demo_output') . '</h5>
<pre><code>use KLXM\MarkdownEditor\Utils\MarkdownOutput;

$html = MarkdownOutput::parse(\'REX_VALUE[1]\');
echo $html;

// Oder markitup-kompatibel:
$html = MarkdownOutput::parseOutput(\'markdown\', \'REX_VALUE[id=1 output="html"]\');
echo $html;</code></pre>
';

$fragment = new rex_fragment();
$fragment->setVar('title', $addon->i18n('markdowneditor_demo_quick_usage'), false);
$fragment->setVar('body', $usageContent, false);
echo $fragment->parse('core/page/section.php');
