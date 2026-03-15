<?php

/**
 * update.php – MarkdownEditor AddOn.
 *
 * @var rex_addon $this
 * @psalm-scope-this rex_addon
 */

// Re-run schema to apply column changes
$this->includeFile(__DIR__ . '/install.php');
