<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Validated image upload handling.
 */
class FileUpload
{
    private const MAX_BYTES = 2 * 1024 * 1024; // 2 MB
    private const ALLOWED = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
    ];

    /**
     * Validate & store an uploaded image. Returns the web-relative path
     * (e.g. "uploads/contestants/abc.jpg") or throws \RuntimeException.
     */
    public static function image(array $file, string $subdir): string
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new \RuntimeException('File upload failed.');
        }
        if ($file['size'] > self::MAX_BYTES) {
            throw new \RuntimeException('Image must be 2 MB or smaller.');
        }

        // Verify real MIME type from content, not the client-provided one
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file['tmp_name']);
        if (!isset(self::ALLOWED[$mime])) {
            throw new \RuntimeException('Only JPG, PNG or WEBP images are allowed.');
        }

        // Confirm it's a real image with sane dimensions
        $info = @getimagesize($file['tmp_name']);
        if ($info === false) {
            throw new \RuntimeException('The uploaded file is not a valid image.');
        }
        [$width, $height] = $info;
        if ($width < 20 || $height < 20 || $width > 5000 || $height > 5000) {
            throw new \RuntimeException('Image dimensions are out of the allowed range.');
        }

        $ext = self::ALLOWED[$mime];
        $name = bin2hex(random_bytes(16)) . '.' . $ext;
        $dir = UPLOAD_PATH . '/' . trim($subdir, '/');
        if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
            throw new \RuntimeException('Upload directory is not writable.');
        }

        $dest = $dir . '/' . $name;
        if (!move_uploaded_file($file['tmp_name'], $dest)) {
            // Fallback for CLI/test contexts
            if (!rename($file['tmp_name'], $dest)) {
                throw new \RuntimeException('Could not save the uploaded file.');
            }
        }

        return 'uploads/' . trim($subdir, '/') . '/' . $name;
    }

    /** Delete a previously stored upload (web-relative path). */
    public static function delete(?string $relPath): void
    {
        if (!$relPath) {
            return;
        }
        $full = PUBLIC_PATH . '/assets/' . ltrim($relPath, '/');
        if (is_file($full)) {
            @unlink($full);
        }
    }
}
