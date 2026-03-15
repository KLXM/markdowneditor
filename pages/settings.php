<?php

/**
 * settings.php – General settings for MarkdownEditor.
 *
 * @var rex_addon $this
 */

$addon = rex_addon::get('markdowneditor');

// ---------- Settings form ----------
$form = rex_config_form::factory('markdowneditor');

$form->addFieldset($addon->i18n('markdowneditor_settings'));

// Default profile
$field = $form->addTextField('default_profile');
$field->setLabel($addon->i18n('markdowneditor_default_profile'));
$field->setNotice($addon->i18n('markdowneditor_default_profile_notice'));
$field->setAttribute('placeholder', 'default');

// Default media category
$field = $form->addTextField('default_media_category');
$field->setLabel($addon->i18n('markdowneditor_media_category'));
$field->setAttribute('type', 'number');
$field->setNotice($addon->i18n('markdowneditor_media_category_notice'));

// Default media manager type
$field = $form->addTextField('default_media_type');
$field->setLabel($addon->i18n('markdowneditor_media_type'));
$field->setNotice($addon->i18n('markdowneditor_media_type_notice'));

$content = $form->get();

$fragment = new rex_fragment();
$fragment->setVar('class', 'edit', false);
$fragment->setVar('title', $addon->i18n('markdowneditor_settings'), false);
$fragment->setVar('body', $content, false);
echo $fragment->parse('core/page/section.php');

// ---------- Usage info ----------
$usageHtml = '
<h4>' . $addon->i18n('markdowneditor_usage_title') . '</h4>

<h5>MForm</h5>
<pre><code class="language-php">$mform = new MForm();
$mform-&gt;addTextAreaField(\'1\', [
    \'class\' =&gt; \'markdowneditor-editor\',
    \'data-markdowneditor-profile\' =&gt; \'default\'
]);</code></pre>

<h5>REX_VALUE / textarea</h5>
<pre><code class="language-html">&lt;textarea
    class="markdowneditor-editor"
    data-markdowneditor-profile="default"
    name="REX_INPUT_VALUE[1]"
&gt;REX_VALUE[1]&lt;/textarea&gt;</code></pre>

<h5>' . $addon->i18n('markdowneditor_usage_output') . '</h5>
<pre><code class="language-php">use KLXM\MarkdownEditor\Utils\MarkdownOutput;

// Markdown → HTML (mit REDAXO-Link-Auflösung)
$html = MarkdownOutput::parse(\'REX_VALUE[1]\');
echo $html;

// Oder markitup-kompatibel:
$html = MarkdownOutput::parseOutput(\'markdown\', \'REX_VALUE[id=1 output="html"]\');
echo $html;</code></pre>

<h5>' . $addon->i18n('markdowneditor_usage_mblock') . '</h5>
<pre><code class="language-php">// Funktioniert automatisch mit MBlock und MForm Repeater.
// Der Editor wird beim Hinzufügen/Verschieben von Blöcken
// automatisch neu initialisiert.</code></pre>

<h5>' . $addon->i18n('markdowneditor_usage_links') . '</h5>
<pre><code>Bilder:    ![bild](index.php?rex_media_type=markitupImage&rex_media_file=bild.jpg)
Dateien:   [datei.pdf](/media/datei.pdf)
Artikel:   [Linktext](redaxo://42)
YForm:     [Text](yform:tablename/123)</code></pre>
';

$fragment = new rex_fragment();
$fragment->setVar('title', $addon->i18n('markdowneditor_usage_title'), false);
$fragment->setVar('body', $usageHtml, false);
echo $fragment->parse('core/page/section.php');
