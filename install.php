<?php

/**
 * install.php – MarkdownEditor AddOn.
 *
 * @var rex_addon $this
 * @psalm-scope-this rex_addon
 */

// ---------- Profile table ----------
rex_sql_table::get(rex::getTable('markdowneditor_profiles'))
    ->ensurePrimaryIdColumn()
    ->ensureColumn(new rex_sql_column('name', 'varchar(40)', false))
    ->ensureColumn(new rex_sql_column('description', 'varchar(255)', true))
    ->ensureColumn(new rex_sql_column('toolbar', 'text', true))
    ->ensureColumn(new rex_sql_column('min_height', 'int(11)', true, '200'))
    ->ensureColumn(new rex_sql_column('max_height', 'int(11)', true, '600'))
    ->ensureColumn(new rex_sql_column('status_bar', 'tinyint(1)', true, '1'))
    ->ensureColumn(new rex_sql_column('spell_checker', 'tinyint(1)', true, '0'))
    ->ensureColumn(new rex_sql_column('autosave', 'tinyint(1)', true, '0'))
    ->ensureColumn(new rex_sql_column('upload_enabled', 'tinyint(1)', true, '1'))
    ->ensureColumn(new rex_sql_column('media_category', 'int(11)', true, '0'))
    ->ensureColumn(new rex_sql_column('media_type', 'varchar(255)', true))
    ->ensureColumn(new rex_sql_column('yform_tables', 'text', true))
    ->ensureColumn(new rex_sql_column('options', 'longtext', true))
    ->ensureColumn(new rex_sql_column('createdate', 'datetime', true))
    ->ensureColumn(new rex_sql_column('updatedate', 'datetime', true))
    ->ensureColumn(new rex_sql_column('createuser', 'varchar(255)', true))
    ->ensureColumn(new rex_sql_column('updateuser', 'varchar(255)', true))
    ->ensureIndex(new rex_sql_index('name', ['name'], rex_sql_index::UNIQUE))
    ->ensure();

// ---------- Insert default profiles if table is empty ----------
$sql = rex_sql::factory();
$sql->setQuery('SELECT COUNT(*) as cnt FROM ' . rex::getTable('markdowneditor_profiles'));
$count = (int) $sql->getValue('cnt');

if ($count === 0) {
    $now = date('Y-m-d H:i:s');

    // --- Profile: default ---
    $sql = rex_sql::factory();
    $sql->setTable(rex::getTable('markdowneditor_profiles'));
    $sql->setValue('name', 'default');
    $sql->setValue('description', 'Standard-Profil mit allen Funktionen');
    $sql->setValue('toolbar', json_encode([
        'bold', 'italic', 'strikethrough', 'heading', '|',
        'quote', 'unordered-list', 'ordered-list', 'checklist', '|',
        'link', 'rex-media', 'rex-link', 'table', '|',
        'preview', 'side-by-side', 'fullscreen', '|',
        'guide',
    ]));
    $sql->setValue('min_height', 250);
    $sql->setValue('max_height', 600);
    $sql->setValue('status_bar', 1);
    $sql->setValue('spell_checker', 0);
    $sql->setValue('autosave', 0);
    $sql->setValue('upload_enabled', 1);
    $sql->setValue('media_category', 0);
    $sql->setValue('media_type', '');
    $sql->setValue('yform_tables', '[]');
    $sql->setValue('options', '{}');
    $sql->setValue('createdate', $now);
    $sql->setValue('createuser', rex::getUser() ? rex::requireUser()->getLogin() : 'setup');
    $sql->insert();

    // --- Profile: minimal ---
    $sql = rex_sql::factory();
    $sql->setTable(rex::getTable('markdowneditor_profiles'));
    $sql->setValue('name', 'minimal');
    $sql->setValue('description', 'Minimales Profil für einfache Texte');
    $sql->setValue('toolbar', json_encode([
        'bold', 'italic', '|',
        'unordered-list', 'ordered-list', '|',
        'link', 'rex-media', '|',
        'preview',
    ]));
    $sql->setValue('min_height', 150);
    $sql->setValue('max_height', 400);
    $sql->setValue('status_bar', 0);
    $sql->setValue('spell_checker', 0);
    $sql->setValue('autosave', 0);
    $sql->setValue('upload_enabled', 1);
    $sql->setValue('media_category', 0);
    $sql->setValue('media_type', '');
    $sql->setValue('yform_tables', '[]');
    $sql->setValue('options', '{}');
    $sql->setValue('createdate', $now);
    $sql->setValue('createuser', rex::getUser() ? rex::requireUser()->getLogin() : 'setup');
    $sql->insert();

    // --- Profile: full ---
    $sql = rex_sql::factory();
    $sql->setTable(rex::getTable('markdowneditor_profiles'));
    $sql->setValue('name', 'full');
    $sql->setValue('description', 'Volles Profil mit allen Optionen');
    $sql->setValue('toolbar', json_encode([
        'bold', 'italic', 'strikethrough', 'heading', 'heading-smaller', 'heading-bigger', '|',
        'code', 'quote', 'unordered-list', 'ordered-list', 'checklist', '|',
        'link', 'rex-media', 'rex-link', 'rex-yform-link', 'table', 'horizontal-rule', '|',
        'preview', 'side-by-side', 'fullscreen', '|',
        'undo', 'redo', '|',
        'clean-block', 'guide',
    ]));
    $sql->setValue('min_height', 300);
    $sql->setValue('max_height', 800);
    $sql->setValue('status_bar', 1);
    $sql->setValue('spell_checker', 0);
    $sql->setValue('autosave', 1);
    $sql->setValue('upload_enabled', 1);
    $sql->setValue('media_category', 0);
    $sql->setValue('media_type', '');
    $sql->setValue('yform_tables', json_encode([
        ['table' => 'rex_yf_news', 'label' => 'name', 'display' => 'name'],
    ]));
    $sql->setValue('options', '{}');
    $sql->setValue('createdate', $now);
    $sql->setValue('createuser', rex::getUser() ? rex::requireUser()->getLogin() : 'setup');
    $sql->insert();
}
