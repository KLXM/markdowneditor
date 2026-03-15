<?php

namespace KLXM\MarkdownEditor\Plugin;

use rex_extension;
use rex_extension_point;
use rex_logger;
use rex_view;

/**
 * Central registry for MarkdownEditor plugins.
 *
 * Collects plugins from other addons via the MARKDOWNEDITOR_PLUGINS extension point
 * and provides their assets and toolbar buttons to the editor.
 *
 * Usage in external addons (boot.php):
 *
 *   rex_extension::register('MARKDOWNEDITOR_PLUGINS', function(rex_extension_point $ep) {
 *       $plugins = $ep->getSubject();
 *       $plugins[] = new MyPlugin();
 *       return $plugins;
 *   });
 */
class PluginRegistry
{
    /** @var list<PluginInterface>|null */
    private static ?array $plugins = null;

    /**
     * Collect all registered plugins via Extension Point.
     *
     * @return list<PluginInterface>
     */
    public static function getPlugins(): array
    {
        if (self::$plugins !== null) {
            return self::$plugins;
        }

        /** @var list<PluginInterface> $plugins */
        $plugins = rex_extension::registerPoint(
            new rex_extension_point('MARKDOWNEDITOR_PLUGINS', []),
        );

        self::$plugins = array_filter(
            $plugins,
            static fn ($p) => $p instanceof PluginInterface,
        );

        return self::$plugins;
    }

    /**
     * Load all plugin JS and CSS files into the backend.
     */
    public static function loadPluginAssets(): void
    {
        foreach (self::getPlugins() as $plugin) {
            try {
                foreach ($plugin->getCssFiles() as $cssFile) {
                    rex_view::addCssFile($cssFile);
                }
                foreach ($plugin->getJsFiles() as $jsFile) {
                    rex_view::addJsFile($jsFile);
                }
                foreach ($plugin->getJsProperties() as $key => $value) {
                    rex_view::setJsProperty($key, $value);
                }
            } catch (\Throwable $e) {
                rex_logger::logException($e);
            }
        }
    }

    /**
     * Get custom toolbar button definitions from all plugins.
     *
     * @return list<array{name: string, title: string, icon: string, action?: string}>
     */
    public static function getToolbarButtons(): array
    {
        $buttons = [];
        foreach (self::getPlugins() as $plugin) {
            try {
                foreach ($plugin->getToolbarButtons() as $button) {
                    $buttons[] = $button;
                }
            } catch (\Throwable $e) {
                rex_logger::logException($e);
            }
        }
        return $buttons;
    }

    /**
     * Run all plugin markdown preprocessors.
     */
    public static function processMarkdown(string $content): string
    {
        foreach (self::getPlugins() as $plugin) {
            try {
                $result = $plugin->processMarkdown($content);
                if ($result !== null) {
                    $content = $result;
                }
            } catch (\Throwable $e) {
                rex_logger::logException($e);
            }
        }
        return $content;
    }

    /**
     * Run all plugin HTML postprocessors.
     */
    public static function processHtml(string $html): string
    {
        foreach (self::getPlugins() as $plugin) {
            try {
                $result = $plugin->processHtml($html);
                if ($result !== null) {
                    $html = $result;
                }
            } catch (\Throwable $e) {
                rex_logger::logException($e);
            }
        }
        return $html;
    }

    /**
     * Reset cached plugins (e.g. for testing).
     */
    public static function reset(): void
    {
        self::$plugins = null;
    }
}
