<?php

/**
 * uninstall.php – MarkdownEditor AddOn.
 *
 * @var rex_addon $this
 * @psalm-scope-this rex_addon
 */

rex_sql_table::get(rex::getTable('markdowneditor_profiles'))->drop();
