<?php
/**
 * Generic single-image upload helper for admin-managed content
 * (e.g. service areas). Stores under assets/uploads/{category}/{ownerId}/.
 */

declare(strict_types=1);

final class MediaUploadService
{
    private const IMAGE_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
    private const MAX_IMAGE_BYTES = 5_242_880;

    /**
     * @param array<string, mixed> $files The $_FILES superglobal
     */
    public static function hasUpload(array $files, string $key): bool
    {
        return isset($files[$key])
            && is_array($files[$key])
            && (int) ($files[$key]['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK
            && ($files[$key]['tmp_name'] ?? '') !== '';
    }

    /**
     * Validate and store an uploaded image, returning its relative path.
     * Deletes the previous stored file (if it was a local upload).
     *
     * @param array<string, mixed> $file A single entry from $_FILES
     */
    public static function storeImage(string $category, int $ownerId, array $file, ?string $currentRelative = null): string
    {
        return self::storeNamedImage($category, (string) $ownerId, $file, $currentRelative);
    }

    /**
     * Store an uploaded image under assets/uploads/{category}/{key}/.
     *
     * @param array<string, mixed> $file A single entry from $_FILES
     */
    public static function storeNamedImage(string $category, string $key, array $file, ?string $currentRelative = null): string
    {
        self::validate($file);

        $category = preg_replace('/[^a-z0-9_-]/i', '', $category) ?: 'misc';
        $key = preg_replace('/[^a-z0-9_-]/i', '', $key) ?: 'item';
        $dir = dirname(__DIR__) . '/assets/uploads/' . $category . '/' . $key;
        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            throw new RuntimeException('Unable to create upload directory.');
        }

        $ext = strtolower(pathinfo((string) ($file['name'] ?? ''), PATHINFO_EXTENSION)) ?: 'jpg';
        if (!in_array($ext, self::IMAGE_EXTENSIONS, true)) {
            $ext = 'jpg';
        }
        $filename = 'img_' . bin2hex(random_bytes(4)) . '.' . $ext;
        $target = $dir . DIRECTORY_SEPARATOR . $filename;

        if (!move_uploaded_file($file['tmp_name'], $target)) {
            throw new RuntimeException('Failed to save uploaded image.');
        }

        $relative = 'assets/uploads/' . $category . '/' . $key . '/' . $filename;

        if ($currentRelative !== null && $currentRelative !== '' && $currentRelative !== $relative) {
            self::delete($currentRelative);
        }

        return $relative;
    }

    public static function delete(?string $relative): void
    {
        if ($relative === null || $relative === '' || preg_match('#^https?://#i', $relative)) {
            return;
        }

        $full = dirname(__DIR__) . '/' . ltrim(str_replace('\\', '/', $relative), '/');
        if (is_file($full)) {
            unlink($full);
        }
    }

    /**
     * @param array<string, mixed> $file
     */
    private static function validate(array $file): void
    {
        $error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($error !== UPLOAD_ERR_OK) {
            throw new RuntimeException(match ($error) {
                UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'Image is too large for the server upload limit.',
                UPLOAD_ERR_PARTIAL => 'Upload was interrupted. Please try again.',
                UPLOAD_ERR_NO_FILE => 'No image was uploaded.',
                default => 'Image upload failed.',
            });
        }

        if ((int) ($file['size'] ?? 0) > self::MAX_IMAGE_BYTES) {
            throw new RuntimeException('Image must be 5MB or less.');
        }

        $ext = strtolower(pathinfo((string) ($file['name'] ?? ''), PATHINFO_EXTENSION));
        if (in_array($ext, self::IMAGE_EXTENSIONS, true)) {
            return;
        }

        $mime = self::detectMime((string) ($file['tmp_name'] ?? ''), (string) ($file['type'] ?? ''));
        if (!in_array($mime, ['image/jpeg', 'image/jpg', 'image/pjpeg', 'image/png', 'image/webp', 'image/gif'], true)) {
            throw new RuntimeException('Unsupported image format. Use JPG, PNG, WEBP, or GIF.');
        }
    }

    private static function detectMime(string $tmpPath, string $clientType): string
    {
        if ($tmpPath !== '' && is_file($tmpPath) && function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            if ($finfo !== false) {
                $detected = finfo_file($finfo, $tmpPath) ?: '';
                finfo_close($finfo);
                if ($detected !== '') {
                    return strtolower($detected);
                }
            }
        }

        return strtolower(trim($clientType));
    }
}
