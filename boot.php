<?php

/**
 * boot.php – MarkdownEditor AddOn.
 *
 * @var rex_addon $this
 * @psalm-scope-this rex_addon
 */

use KLXM\MarkdownEditor\Provider\AssetsProvider;

if (rex::isBackend() && rex::getUser() !== null) {
    rex_perm::register('markdowneditor_addon[]');

    rex_extension::register('PACKAGES_INCLUDED', static function () {
        AssetsProvider::provideEditorAssets();

        if (rex_be_controller::getCurrentPagePart(1) === 'markdowneditor') {
            AssetsProvider::provideBackendPageAssets();
        }
    });
}
