<?php

namespace KLXM\MarkdownEditor\Api;

use rex;
use rex_api_function;
use rex_api_result;
use rex_csrf_token;
use rex_request;
use rex_response;
use rex_sql;

/**
 * API endpoint for importing MarkdownEditor profiles from a JSON export file.
 *
 * Usage: POST ?rex-api-call=markdowneditor_profile_import with file upload 'profiles_json'
 * mode: 'skip' (default) = skip existing names, 'overwrite' = update existing, 'rename' = auto-rename
 */
class ProfileImport extends rex_api_function
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

        // Read JSON: either file upload or raw POST body
        $json = null;
        if (isset($_FILES['profiles_json']) && $_FILES['profiles_json']['error'] === UPLOAD_ERR_OK) {
            $finfo = new \finfo(FILEINFO_MIME_TYPE);
            $mime = $finfo->file($_FILES['profiles_json']['tmp_name']);
            if (!in_array($mime, ['application/json', 'text/plain', 'text/json'], true)) {
                rex_response::setStatus(rex_response::HTTP_BAD_REQUEST);
                rex_response::sendJson(['error' => 'Only JSON files are allowed.']);
                exit;
            }
            $json = file_get_contents($_FILES['profiles_json']['tmp_name']);
        } else {
            rex_response::setStatus(rex_response::HTTP_BAD_REQUEST);
            rex_response::sendJson(['error' => 'No file uploaded.']);
            exit;
        }

        $data = json_decode($json, true);
        if (!is_array($data) || empty($data['markdowneditor_export']) || !isset($data['profiles'])) {
            rex_response::setStatus(rex_response::HTTP_BAD_REQUEST);
            rex_response::sendJson(['error' => 'Invalid export file format.']);
            exit;
        }

        $mode = rex_request::post('import_mode', 'string', 'skip');
        $now = date('Y-m-d H:i:s');
        $login = rex::requireUser()->getLogin();

        $imported = 0;
        $skipped = 0;
        $updated = 0;
        $errors = [];

        foreach ($data['profiles'] as $profileData) {
            $name = trim((string) ($profileData['name'] ?? ''));
            if ($name === '') {
                $errors[] = 'Profile without name skipped.';
                continue;
            }

            // Validate name pattern
            if (!preg_match('/^[a-z0-9_]+$/', $name)) {
                $errors[] = 'Invalid profile name "' . $name . '" skipped.';
                continue;
            }

            // Re-encode JSON fields if they were decoded during export
            foreach (['toolbar', 'yform_tables', 'options'] as $jsonCol) {
                if (isset($profileData[$jsonCol]) && is_array($profileData[$jsonCol])) {
                    $profileData[$jsonCol] = json_encode($profileData[$jsonCol], JSON_UNESCAPED_UNICODE);
                }
            }

            // Check existence
            $checkSql = rex_sql::factory();
            $checkSql->setQuery('SELECT id FROM ' . rex::getTable('markdowneditor_profiles') . ' WHERE name = :name', ['name' => $name]);

            if ($checkSql->getRows() > 0) {
                if ($mode === 'skip') {
                    ++$skipped;
                    continue;
                } elseif ($mode === 'overwrite') {
                    $existingId = (int) $checkSql->getValue('id');
                    $updateSql = rex_sql::factory();
                    $updateSql->setTable(rex::getTable('markdowneditor_profiles'));
                    $updateSql->setWhere(['id' => $existingId]);
                    self::applyProfileValues($updateSql, $profileData, $now, $login, false);
                    $updateSql->update();
                    ++$updated;
                    continue;
                } elseif ($mode === 'rename') {
                    // Find unique name
                    $baseName = $name;
                    $suffix = 1;
                    do {
                        $newName = $baseName . '_imported' . ($suffix > 1 ? $suffix : '');
                        ++$suffix;
                        $checkSql2 = rex_sql::factory();
                        $checkSql2->setQuery('SELECT id FROM ' . rex::getTable('markdowneditor_profiles') . ' WHERE name = :name', ['name' => $newName]);
                    } while ($checkSql2->getRows() > 0);
                    $profileData['name'] = $newName;
                }
            }

            $insertSql = rex_sql::factory();
            $insertSql->setTable(rex::getTable('markdowneditor_profiles'));
            self::applyProfileValues($insertSql, $profileData, $now, $login, true);
            $insertSql->insert();
            ++$imported;
        }

        rex_response::sendJson([
            'success' => true,
            'imported' => $imported,
            'updated' => $updated,
            'skipped' => $skipped,
            'errors' => $errors,
        ]);
        exit;
    }

    /**
     * Apply profile field values to a rex_sql instance.
     */
    private static function applyProfileValues(rex_sql $sql, array $data, string $now, string $login, bool $isInsert): void
    {
        if ($isInsert) {
            $sql->setValue('name', $data['name'] ?? '');
            $sql->setValue('createdate', $now);
            $sql->setValue('createuser', $login);
        }
        $sql->setValue('description', $data['description'] ?? '');
        $sql->setValue('toolbar', $data['toolbar'] ?? '');
        $sql->setValue('min_height', (int) ($data['min_height'] ?? 200));
        $sql->setValue('max_height', (int) ($data['max_height'] ?? 500));
        $sql->setValue('status_bar', (int) ($data['status_bar'] ?? 1));
        $sql->setValue('spell_checker', (int) ($data['spell_checker'] ?? 0));
        $sql->setValue('autosave', (int) ($data['autosave'] ?? 0));
        $sql->setValue('upload_enabled', (int) ($data['upload_enabled'] ?? 1));
        $sql->setValue('media_category', (int) ($data['media_category'] ?? 0));
        $sql->setValue('media_type', $data['media_type'] ?? '');
        $sql->setValue('yform_tables', $data['yform_tables'] ?? '');
        $sql->setValue('options', $data['options'] ?? '');
        $sql->setValue('updatedate', $now);
        $sql->setValue('updateuser', $login);
    }
}
