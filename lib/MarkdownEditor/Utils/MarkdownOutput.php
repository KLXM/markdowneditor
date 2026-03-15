<?php

namespace KLXM\MarkdownEditor\Utils;

use KLXM\MarkdownEditor\Plugin\PluginRegistry;
use rex;
use rex_addon;
use rex_article;
use rex_clang;
use rex_extension;
use rex_extension_point;
use rex_markdown;

/**
 * Helper for processing and rendering Markdown content in the frontend.
 *
 * Handles REDAXO-specific link formats (markitup-compatible):
 *   - redaxo://ARTICLE_ID                                   → resolved article URL
 *   - /media/FILENAME                                        → media path (already correct)
 *   - index.php?rex_media_type=...&rex_media_file=FILENAME   → resolved to /media/FILENAME
 *   - media://FILENAME                                       → legacy format, resolved to /media/FILENAME
 *   - yform:TABLE/ID                                         → YForm dataset link (via EP)
 *   - yform://TABLE/ID                                       → legacy format alias
 */
class MarkdownOutput
{
    /**
     * Parse Markdown to HTML with REDAXO-specific link resolution.
     *
     * Compatible with MarkItUp's parseOutput('markdown', ...) format.
     */
    public static function parse(string $markdown, int $clangId = 0): string
    {
        if ($clangId === 0) {
            $clangId = rex_clang::getCurrentId();
        }

        // Strip <br /> (markitup compatibility)
        $markdown = str_replace('<br />', '', $markdown);

        // Resolve REDAXO links before markdown parsing
        $markdown = self::resolveRexLinks($markdown, $clangId);
        $markdown = self::resolveMediaLinks($markdown);
        $markdown = self::resolveYFormLinks($markdown);

        // Run plugin markdown preprocessors
        $markdown = PluginRegistry::processMarkdown($markdown);

        $html = rex_markdown::factory()->parse($markdown);

        // Run plugin HTML postprocessors
        $html = PluginRegistry::processHtml($html);

        return $html;
    }

    /**
     * Compatibility method matching MarkItUp's parseOutput() signature.
     *
     * @param string $type   Content type ('markdown')
     * @param string $content Raw content
     * @return string|false  Parsed HTML or false on unknown type
     */
    public static function parseOutput(string $type, string $content): string|false
    {
        if ($type === 'markdown') {
            return self::parse($content);
        }

        return false;
    }

    /**
     * Resolve redaxo://ID links to actual article URLs.
     */
    public static function resolveRexLinks(string $content, int $clangId = 0): string
    {
        if ($clangId === 0) {
            $clangId = rex_clang::getCurrentId();
        }

        return (string) preg_replace_callback(
            '/redaxo:\/\/(\d+)/i',
            static function (array $matches) use ($clangId): string {
                $articleId = (int) $matches[1];
                $article = rex_article::get($articleId, $clangId);
                if ($article !== null) {
                    return rex_getUrl($articleId, $clangId);
                }
                return $matches[0]; // leave unchanged if article not found
            },
            $content,
        );
    }

    /**
     * Resolve media links to actual URLs.
     *
     * Handles three formats:
     *   1. index.php?rex_media_type=...&rex_media_file=FILENAME (markitup image format)
     *   2. media://FILENAME (legacy)
     *   3. /media/FILENAME (already correct, no change needed)
     */
    public static function resolveMediaLinks(string $content): string
    {
        // 1. markitup image format: index.php?rex_media_type=XXX&rex_media_file=FILENAME
        $content = (string) preg_replace_callback(
            '/index\.php\?rex_media_type=[^&]+&rex_media_file=([^\s\)\"\']+)/i',
            static function (array $matches): string {
                return rex::getServer() . 'media/' . $matches[1];
            },
            $content,
        );

        // 2. Legacy media:// format
        $content = (string) preg_replace_callback(
            '/media:\/\/([^\s\)\"\']+)/i',
            static function (array $matches): string {
                return rex::getServer() . 'media/' . $matches[1];
            },
            $content,
        );

        return $content;
    }

    /**
     * Resolve YForm links via extension point.
     *
     * Handles both formats:
     *   - yform:TABLE/ID  (markitup format, primary)
     *   - yform://TABLE/ID (legacy)
     *
     * Other addons can register for MARKDOWNEDITOR_RESOLVE_YFORM_LINK
     * to provide the actual URL.
     */
    public static function resolveYFormLinks(string $content): string
    {
        // Match both yform:TABLE/ID and yform://TABLE/ID
        return (string) preg_replace_callback(
            '/yform:(?:\/\/)?([^\/\s\)\"\']+)\/(\d+)/i',
            static function (array $matches): string {
                $tableName = $matches[1];
                $datasetId = (int) $matches[2];

                $url = rex_extension::registerPoint(new rex_extension_point(
                    'MARKDOWNEDITOR_RESOLVE_YFORM_LINK',
                    '',
                    [
                        'table' => $tableName,
                        'id' => $datasetId,
                    ],
                ));

                if ($url !== '') {
                    return $url;
                }

                // Try URL addon if available
                if (rex_addon::get('url')->isAvailable()) {
                    return self::resolveViaUrlAddon($tableName, $datasetId);
                }

                return $matches[0]; // unchanged
            },
            $content,
        );
    }

    /**
     * Try to resolve a YForm dataset URL via the URL addon.
     */
    private static function resolveViaUrlAddon(string $tableName, int $datasetId): string
    {
        // Default fallback – addons should use the EP for custom resolution
        return 'yform:' . $tableName . '/' . $datasetId;
    }
}
