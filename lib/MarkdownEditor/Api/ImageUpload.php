<?php

namespace KLXM\MarkdownEditor\Api;

use rex;
use rex_addon;
use rex_api_function;
use rex_api_result;
use rex_csrf_token;
use rex_media;
use rex_media_service;
use rex_request;
use rex_response;

/**
 * API endpoint for uploading images via drag & drop into the REDAXO mediapool.
 *
 * Called by the editor JS when a user drops an image onto the editor.
 * Usage: rex-api-call=markdowneditor_image_upload
 */
class ImageUpload extends rex_api_function
{
    protected $published = true;
    protected $csrf = false;

    /**
     * @return never
     */
    public function execute(): rex_api_result
    {
        rex_response::cleanOutputBuffers();

        // CSRF validation
        if (!rex_csrf_token::factory('markdowneditor_upload')->isValid()) {
            rex_response::setStatus(rex_response::HTTP_FORBIDDEN);
            rex_response::sendJson(['error' => 'Invalid CSRF token.']);
            exit;
        }

        // Permission check
        $user = rex::getUser();
        if ($user === null || !$user->hasPerm('mediapool[]')) {
            rex_response::setStatus(rex_response::HTTP_FORBIDDEN);
            rex_response::sendJson(['error' => 'Permission denied.']);
            exit;
        }

        // Validate file upload
        if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            rex_response::setStatus(rex_response::HTTP_BAD_REQUEST);
            rex_response::sendJson(['error' => 'No valid file uploaded.']);
            exit;
        }

        $file = $_FILES['file'];

        // Validate MIME type – only images allowed
        $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml'];
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($file['tmp_name']);
        if (!in_array($mimeType, $allowedMimes, true)) {
            rex_response::setStatus(rex_response::HTTP_BAD_REQUEST);
            rex_response::sendJson(['error' => 'Only image files are allowed.']);
            exit;
        }

        // Get media category from request
        $categoryId = rex_request::request('media_category', 'int', 0);

        // Upload to mediapool
        $data = [
            'title' => pathinfo($file['name'], PATHINFO_FILENAME),
            'category_id' => $categoryId,
            'file' => [
                'name' => $file['name'],
                'tmp_name' => $file['tmp_name'],
                'size' => $file['size'],
                'type' => $mimeType,
                'error' => 0,
            ],
        ];

        $result = rex_media_service::addMedia($data, true);

        if (is_array($result) && isset($result['ok']) && $result['ok']) {
            $uploadedFilename = $result['filename'];
            $mediaUrl = rex::getServer() . 'media/' . $uploadedFilename;

            // Apply media-manager type if provided
            $mediaType = rex_request::request('media_type', 'string', '');
            if ($mediaType !== '') {
                $mediaUrl = rex::getServer() . 'index.php?rex_media_type=' . urlencode($mediaType) . '&rex_media_file=' . urlencode($uploadedFilename);
            }

            rex_response::sendJson([
                'success' => true,
                'filename' => $uploadedFilename,
                'url' => $mediaUrl,
            ]);
        } else {
            $errorMsg = is_array($result) && isset($result['messages']) ? implode(', ', $result['messages']) : 'Upload failed.';
            rex_response::setStatus(rex_response::HTTP_INTERNAL_ERROR);
            rex_response::sendJson(['error' => $errorMsg]);
        }

        exit;
    }
}
