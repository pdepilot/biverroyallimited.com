<?php
/**
 * Handle uploaded property media for public submissions.
 */

declare(strict_types=1);

require_once __DIR__ . '/site_paths.php';

class PropertyUploadService
{
    private const IMAGE_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
    private const VIDEO_EXTENSIONS = ['mp4', 'webm', 'mov', 'avi', 'mkv'];
    private const MAX_IMAGE_BYTES = 5_242_880;
    private const MAX_VIDEO_BYTES = 52_428_800;

    /**
     * @return array{imageUrl:?string,videoUrl:?string,extraImages:list<string>}
     */
    public static function storeSubmissionMedia(int $propertyId, array $files): array
    {
        $baseDir = dirname(__DIR__) . '/assets/uploads/properties/' . $propertyId;
        if (!is_dir($baseDir) && !mkdir($baseDir, 0755, true) && !is_dir($baseDir)) {
            throw new RuntimeException('Unable to create upload directory.');
        }

        $imageUrl = null;
        $videoUrl = null;
        $extraImages = [];

        if (!empty($files['propertyImages'])) {
            $images = self::normalizeFiles($files['propertyImages']);
            if ($images === []) {
                throw new RuntimeException('Please upload at least one valid property image.');
            }

            foreach ($images as $index => $file) {
                self::validateImage($file);
                $name = self::saveFile($file, $baseDir, 'img_' . ($index + 1));
                $url = self::storagePath($propertyId, $name);
                if ($imageUrl === null) {
                    $imageUrl = $url;
                } else {
                    $extraImages[] = $url;
                }
            }
        }

        if (!empty($files['propertyVideos'])) {
            $videos = self::normalizeFiles($files['propertyVideos']);
            if (!empty($videos[0])) {
                self::validateVideo($videos[0]);
                $name = self::saveFile($videos[0], $baseDir, 'video');
                $videoUrl = self::storagePath($propertyId, $name);
            }
        }

        return [
            'imageUrl'    => $imageUrl,
            'videoUrl'    => $videoUrl,
            'extraImages' => $extraImages,
        ];
    }

    /**
     * Update stored media for an existing submission (admin review).
     *
     * @param list<string> $keepImagePaths Relative paths the admin chose to keep
     * @return array{imageUrl:?string,videoUrl:?string,galleryUrls:list<string>}
     */
    public static function updateSubmissionMedia(
        int $propertyId,
        array $keepImagePaths,
        array $files,
        bool $removeVideo,
        ?string $currentVideoPath = null
    ): array {
        $prefix = self::propertyUploadPrefix($propertyId);
        $validatedKeep = [];

        foreach ($keepImagePaths as $path) {
            if (!is_string($path) || $path === '') {
                continue;
            }
            $normalized = str_replace('\\', '/', $path);
            if (!str_starts_with($normalized, $prefix)) {
                throw new RuntimeException('Invalid image path for this property.');
            }
            if (is_file(self::absolutePath($normalized))) {
                $validatedKeep[] = $normalized;
            }
        }

        $existingPaths = self::listStoredPaths($propertyId);
        $keepAll = $validatedKeep;
        if (!$removeVideo && $currentVideoPath !== null && $currentVideoPath !== '') {
            $keepAll[] = str_replace('\\', '/', $currentVideoPath);
        }

        foreach ($existingPaths as $existingPath) {
            if (!in_array($existingPath, $keepAll, true)) {
                self::deleteStoredPath($existingPath);
            }
        }

        $newPaths = [];
        if (!empty($files['propertyImages'])) {
            $baseDir = self::uploadDirectory($propertyId);
            foreach (self::normalizeFiles($files['propertyImages']) as $index => $file) {
                self::validateImage($file);
                $name = self::saveFile($file, $baseDir, 'img_' . ($index + 1));
                $newPaths[] = self::storagePath($propertyId, $name);
            }
        }

        $allImages = array_values(array_unique(array_merge($validatedKeep, $newPaths)));
        if ($allImages === []) {
            throw new RuntimeException('At least one property image is required.');
        }

        $imageUrl = $allImages[0];
        $galleryUrls = array_slice($allImages, 1);

        $videoUrl = null;
        if ($removeVideo) {
            self::deleteStoredPath($currentVideoPath);
        } elseif (!empty($files['propertyVideos'])) {
            self::deleteStoredPath($currentVideoPath);
            $videos = self::normalizeFiles($files['propertyVideos']);
            if (!empty($videos[0])) {
                self::validateVideo($videos[0]);
                $name = self::saveFile($videos[0], self::uploadDirectory($propertyId), 'video');
                $videoUrl = self::storagePath($propertyId, $name);
            }
        } elseif ($currentVideoPath !== null && $currentVideoPath !== '') {
            $normalizedVideo = str_replace('\\', '/', $currentVideoPath);
            if (str_starts_with($normalizedVideo, $prefix) && is_file(self::absolutePath($normalizedVideo))) {
                $videoUrl = $normalizedVideo;
            }
        }

        return [
            'imageUrl'    => $imageUrl,
            'videoUrl'    => $videoUrl,
            'galleryUrls' => $galleryUrls,
        ];
    }

    public static function deleteStoredPath(?string $relativePath): void
    {
        if ($relativePath === null || $relativePath === '') {
            return;
        }

        $full = self::absolutePath($relativePath);
        if (is_file($full)) {
            unlink($full);
        }
    }

    private static function propertyUploadPrefix(int $propertyId): string
    {
        return 'assets/uploads/properties/' . $propertyId . '/';
    }

    private static function uploadDirectory(int $propertyId): string
    {
        $baseDir = dirname(__DIR__) . '/assets/uploads/properties/' . $propertyId;
        if (!is_dir($baseDir) && !mkdir($baseDir, 0755, true) && !is_dir($baseDir)) {
            throw new RuntimeException('Unable to create upload directory.');
        }

        return $baseDir;
    }

    private static function absolutePath(string $relativePath): string
    {
        return dirname(__DIR__) . '/' . ltrim(str_replace('\\', '/', $relativePath), '/');
    }

    /**
     * @return list<string>
     */
    private static function listStoredPaths(int $propertyId): array
    {
        $dir = self::uploadDirectory($propertyId);
        $paths = [];
        foreach (glob($dir . DIRECTORY_SEPARATOR . '*') ?: [] as $file) {
            if (!is_file($file)) {
                continue;
            }
            $paths[] = self::storagePath($propertyId, basename($file));
        }

        return $paths;
    }

    /**
     * @param array<string, mixed> $file
     */
    private static function validateImage(array $file): void
    {
        self::assertUploadOk($file, 'Image upload failed.');
        if (($file['size'] ?? 0) > self::MAX_IMAGE_BYTES) {
            throw new RuntimeException('Each image must be 5MB or less.');
        }
        if (!self::isAllowedImage($file)) {
            throw new RuntimeException('Unsupported image format. Use JPG, PNG, WEBP, or GIF.');
        }
    }

    /**
     * @param array<string, mixed> $file
     */
    private static function validateVideo(array $file): void
    {
        self::assertUploadOk($file, 'Video upload failed.');
        if (($file['size'] ?? 0) > self::MAX_VIDEO_BYTES) {
            throw new RuntimeException('Video must be 50MB or less.');
        }
        if (!self::isAllowedVideo($file)) {
            throw new RuntimeException('Unsupported video format. Use MP4, WEBM, or MOV.');
        }
    }

    /**
     * @param array<string, mixed> $file
     */
    private static function assertUploadOk(array $file, string $message): void
    {
        $error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($error === UPLOAD_ERR_OK) {
            return;
        }

        $details = match ($error) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'File is too large for the server upload limit.',
            UPLOAD_ERR_PARTIAL => 'Upload was interrupted. Please try again.',
            default => $message,
        };

        throw new RuntimeException($details);
    }

    /**
     * @param array<string, mixed> $file
     */
    private static function isAllowedImage(array $file): bool
    {
        $ext = self::extension($file['name'] ?? '');
        if (in_array($ext, self::IMAGE_EXTENSIONS, true)) {
            return true;
        }

        $mime = self::detectMime($file['tmp_name'] ?? '', $file['type'] ?? '');
        return in_array($mime, [
            'image/jpeg', 'image/jpg', 'image/pjpeg', 'image/png', 'image/webp', 'image/gif',
        ], true);
    }

    /**
     * @param array<string, mixed> $file
     */
    private static function isAllowedVideo(array $file): bool
    {
        $ext = self::extension($file['name'] ?? '');
        if (in_array($ext, self::VIDEO_EXTENSIONS, true)) {
            return true;
        }

        $mime = self::detectMime($file['tmp_name'] ?? '', $file['type'] ?? '');
        return in_array($mime, [
            'video/mp4', 'video/webm', 'video/quicktime', 'video/x-msvideo', 'video/avi', 'video/mpeg',
        ], true);
    }

    private static function detectMime(string $tmpPath, string $clientType): string
    {
        if ($tmpPath !== '' && is_file($tmpPath)) {
            if (function_exists('finfo_open')) {
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                if ($finfo !== false) {
                    $detected = finfo_file($finfo, $tmpPath) ?: '';
                    finfo_close($finfo);
                    if ($detected !== '') {
                        return strtolower($detected);
                    }
                }
            }

            if (function_exists('mime_content_type')) {
                $detected = mime_content_type($tmpPath);
                if (is_string($detected) && $detected !== '') {
                    return strtolower($detected);
                }
            }
        }

        return strtolower(trim($clientType));
    }

    private static function extension(string $filename): string
    {
        return strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    }

    /**
     * @param array<string, mixed> $file
     */
    private static function saveFile(array $file, string $dir, string $prefix): string
    {
        $ext = self::extension($file['name'] ?? '') ?: 'bin';
        $filename = $prefix . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        $target = $dir . DIRECTORY_SEPARATOR . $filename;

        if (!move_uploaded_file($file['tmp_name'], $target)) {
            throw new RuntimeException('Failed to save uploaded file.');
        }

        return $filename;
    }

    private static function storagePath(int $propertyId, string $filename): string
    {
        return 'assets/uploads/properties/' . $propertyId . '/' . $filename;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private static function normalizeFiles(array $input): array
    {
        if (!is_array($input['name'] ?? null)) {
            return (($input['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) ? [] : [$input];
        }

        $files = [];
        $count = count($input['name']);
        for ($i = 0; $i < $count; $i++) {
            if (($input['error'][$i] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
                continue;
            }
            $files[] = [
                'name'     => $input['name'][$i],
                'type'     => $input['type'][$i] ?? '',
                'tmp_name' => $input['tmp_name'][$i],
                'error'    => $input['error'][$i],
                'size'     => $input['size'][$i] ?? 0,
            ];
        }

        return $files;
    }

    public static function parsePrice(string $raw): int
    {
        $digits = preg_replace('/[^\d]/', '', $raw);
        return $digits !== '' ? (int) $digits : 0;
    }

    public static function mapListingType(string $listingPurpose): string
    {
        return in_array($listingPurpose, ['rent', 'shortlet', 'lease'], true) ? 'rent' : 'sale';
    }
}
