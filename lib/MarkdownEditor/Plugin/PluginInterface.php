<?php

namespace KLXM\MarkdownEditor\Plugin;

/**
 * Interface for MarkdownEditor plugins.
 *
 * Other addons can implement this interface to provide custom toolbar buttons,
 * markdown processing, or additional JS/CSS for the editor.
 *
 * Register plugins via boot.php:
 *
 *   rex_extension::register('MARKDOWNEDITOR_PLUGINS', function(rex_extension_point $ep) {
 *       $plugins = $ep->getSubject();
 *       $plugins[] = new MyAddonPlugin();
 *       return $plugins;
 *   });
 */
interface PluginInterface
{
    /**
     * Unique plugin name (e.g. 'my_addon_gallery').
     */
    public function getName(): string;

    /**
     * Custom toolbar button definitions.
     *
     * Each entry is an array with:
     *   - name: string (unique button name, e.g. 'my-gallery')
     *   - title: string (tooltip text)
     *   - icon: string (CSS class, e.g. 'fa fa-th')
     *   - action: string|null (JS function name to call on click, or null if handled via JS file)
     *
     * @return list<array{name: string, title: string, icon: string, action?: string}>
     */
    public function getToolbarButtons(): array;

    /**
     * JavaScript files to load in the backend.
     *
     * @return list<string> Absolute URLs or paths (use rex_addon::getAssetsUrl())
     */
    public function getJsFiles(): array;

    /**
     * CSS files to load in the backend.
     *
     * @return list<string> Absolute URLs or paths
     */
    public function getCssFiles(): array;

    /**
     * Custom JS properties to expose to the frontend via rex_view::setJsProperty().
     *
     * @return array<string, mixed>
     */
    public function getJsProperties(): array;

    /**
     * Process markdown content before HTML rendering.
     *
     * Return null to skip (no changes), or return the modified content string.
     */
    public function processMarkdown(string $content): ?string;

    /**
     * Process HTML content after markdown rendering.
     *
     * Return null to skip (no changes), or return the modified HTML string.
     */
    public function processHtml(string $html): ?string;
}
