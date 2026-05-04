<?php

namespace KLXM\MarkdownEditor\Api;

use rex;
use rex_addon;
use rex_api_function;
use rex_api_result;
use rex_csrf_token;
use rex_request;
use rex_response;
use rex_sql;

/**
 * API endpoint for exporting MarkdownEditor profiles as JSON.
 *
 * Usage: ?rex-api-call=markdowneditor_profile_export&id=1&_csrf_token=...
 * Use id=all to export all profiles.
 */
class ProfileExport extends rex_api_function
{
    protected $published = false;

    /**
     * @return never
     */
    public function execute(): rex_api_result
    {
        rex_response::cleanOutputBuffers();

        if (!rex_csrf_token::factory('markdowneditor_profiles')->isValid()) {
            rex_response::setStatus(rex_response::HTTP_FORBIDDEN);
            rex_response::sendJson(['error' => 'Invalid CSRF token.']);
            exit;
        }

        $user = rex::getUser();
        if ($user === null || !$user->isAdmin()) {
            rex_response::setStatus(rex_response::HTTP_FORBIDDEN);
            rex_response::sendJson(['error' => 'Permission denied.']);
            exit;
        }

        $id = rex_request::get('id', 'string', 'all');

        $sql = rex_sql::factory();

        if ($id === 'all') {
            $sql->setQuery('SELECT name, description, toolbar, min_height, max_height, status_bar, spell_checker, autosave, upload_enabled, media_category, media_type, yform_tables, options FROM ' . rex::getTable('markdowneditor_profiles') . ' ORDER BY name ASC');
            $filename = 'markdowneditor-profiles-all.json';
        } else {
            $sql->setQuery('SELECT name, description, toolbar, min_height, max_height, status_bar, spell_checker, autosave, upload_enabled, media_category, media_type, yform_tables, options FROM ' . rex::getTable('markdowneditor_profiles') . ' WHERE id = :id', ['id' => (int) $id]);
            if ($sql->getRows() === 0) {
                rex_response::setStatus(rex_response::HTTP_NOT_FOUND);
                rex_response::sendJson(['error' => 'Profile not found.']);
                exit;
            }
            $filename = 'markdowneditor-profile-' . $id . '.json';
        }

        $profiles = [];
        while ($sql->hasNext()) {
            $row = [];
            foreach (['name', 'description', 'toolbar', 'min_height', 'max_height', 'status_bar', 'spell_checker', 'autosave', 'upload_enabled', 'media_category', 'media_type', 'yform_tables', 'options'] as $col) {
                $row[$col] = $sql->getValue($col);
            }
            // Decode JSON fields for better readability in export
            foreach (['toolbar', 'yform_tables', 'options'] as $jsonCol) {
                if (!empty($row[$jsonCol])) {
                    $decoded = json_decode((string) $row[$jsonCol], true);
                    if ($decoded !== null) {
                        $row[$jsonCol] = $decoded;
                    }
                }
            }
            $profiles[] = $row;
            $sql->next();
        }

        $export = [
            'markdowneditor_export' => true,
            'version' => rex_addon::get('markdowneditor')->getVersion(),
            'exported_at' => date('Y-m-d H:i:s'),
            'profiles' => $profiles,
        ];

        $json = json_encode($export, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($json));
        echo $json;
        exit;
    }
}
