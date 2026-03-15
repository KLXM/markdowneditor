<?php

namespace KLXM\MarkdownEditor\Provider;

use KLXM\MarkdownEditor\Plugin\PluginRegistry;
use rex;
use rex_addon;
use rex_csrf_token;
use rex_exception;
use rex_extension;
use rex_extension_point;
use rex_logger;
use rex_sql;
use rex_view;

class AssetsProvider
{
    /**
     * Loads all editor assets (vendor + custom JS/CSS) and provides profile data.
     */
    public static function provideEditorAssets(): void
    {
        try {
            $addon = rex_addon::get('markdowneditor');

            // Vendor: EasyMDE
            rex_view::addCssFile($addon->getAssetsUrl('vendor/easymde/easymde.min.css'));
            rex_view::addJsFile($addon->getAssetsUrl('vendor/easymde/easymde.min.js'));

            // Custom styles (includes dark-mode)
            rex_view::addCssFile($addon->getAssetsUrl('css/markdowneditor.css'));

            // Theme detection for JS
            $theme = self::detectTheme();
            rex_view::setJsProperty('markdowneditor_theme', $theme);

            // Load profiles and expose to JS
            $profiles = self::loadProfiles();
            rex_view::setJsProperty('markdowneditor_profiles', $profiles);

            // Expose addon config
            rex_view::setJsProperty('markdowneditor_config', [
                'media_url' => rex::getServer() . 'media/',
                'csrf_token' => rex_csrf_token::factory('markdowneditor_upload')->getValue(),
            ]);

            // Custom editor JS (with REDAXO integration)
            rex_view::addJsFile($addon->getAssetsUrl('js/markdowneditor.js'));

            // Load plugin assets (JS, CSS, properties) from other addons
            PluginRegistry::loadPluginAssets();

            // Expose plugin toolbar buttons to JS
            $pluginButtons = PluginRegistry::getToolbarButtons();
            if ($pluginButtons !== []) {
                rex_view::setJsProperty('markdowneditor_plugin_buttons', $pluginButtons);
            }
        } catch (rex_exception $e) {
            rex_logger::logException($e);
        }
    }

    /**
     * Loads additional assets that are only needed on the addon's backend pages.
     */
    public static function provideBackendPageAssets(): void
    {
        try {
            $addon = rex_addon::get('markdowneditor');
            rex_view::addCssFile($addon->getAssetsUrl('css/markdowneditor-backend.css'));
        } catch (rex_exception $e) {
            rex_logger::logException($e);
        }
    }

    /**
     * Detects the current theme (light, dark, auto).
     */
    private static function detectTheme(): string
    {
        $user = rex::getUser();
        if ($user === null) {
            return 'auto';
        }

        $themeType = $user->getValue('theme');
        if ($themeType !== null && $themeType !== '') {
            return $themeType;
        }

        $globalTheme = rex::getProperty('theme');
        if ($globalTheme === 'light' || $globalTheme === 'dark') {
            return $globalTheme;
        }

        return 'auto';
    }

    /**
     * Loads all profiles from database.
     *
     * @return array<string, array<string, mixed>>
     */
    private static function loadProfiles(): array
    {
        $profiles = [];

        try {
            $sql = rex_sql::factory();
            $rows = $sql->getArray(
                'SELECT * FROM ' . rex::getTable('markdowneditor_profiles') . ' ORDER BY name ASC',
            );

            foreach ($rows as $row) {
                $name = (string) $row['name'];
                $toolbar = json_decode((string) $row['toolbar'], true);
                $options = json_decode((string) ($row['options'] ?? '{}'), true);

                $profiles[$name] = [
                    'toolbar' => is_array($toolbar) ? $toolbar : [],
                    'minHeight' => (int) ($row['min_height'] ?? 200),
                    'maxHeight' => (int) ($row['max_height'] ?? 600),
                    'statusBar' => (bool) ($row['status_bar'] ?? true),
                    'spellChecker' => (bool) ($row['spell_checker'] ?? false),
                    'autosave' => (bool) ($row['autosave'] ?? false),
                    'uploadEnabled' => (bool) ($row['upload_enabled'] ?? true),
                    'mediaCategory' => (int) ($row['media_category'] ?? 0),
                    'mediaType' => (string) ($row['media_type'] ?? ''),
                    'yformTables' => json_decode((string) ($row['yform_tables'] ?? '[]'), true) ?: [],
                    'options' => is_array($options) ? $options : [],
                ];
            }
        } catch (\Throwable $e) {
            rex_logger::logException($e);
        }

        // Always have a fallback default
        if (!isset($profiles['default'])) {
            $profiles['default'] = [
                'toolbar' => ['bold', 'italic', 'heading', '|', 'quote', 'unordered-list', 'ordered-list', '|', 'link', 'rex-media', '|', 'preview', 'guide'],
                'minHeight' => 200,
                'maxHeight' => 600,
                'statusBar' => true,
                'spellChecker' => false,
                'autosave' => false,
                'uploadEnabled' => true,
                'mediaCategory' => 0,
                'mediaType' => '',
                'options' => [],
            ];
        }

        return $profiles;
    }
}
