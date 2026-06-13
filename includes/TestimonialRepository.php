<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/database.php';
require_once __DIR__ . '/site_paths.php';

final class TestimonialRepository
{
    /** @return list<array<string, mixed>> */
    public static function getAll(bool $publishedOnly = false): array
    {
        $pdo = getDatabaseConnection();
        $sql = 'SELECT * FROM testimonials';
        if ($publishedOnly) {
            $sql .= ' WHERE is_published = 1';
        }
        $sql .= ' ORDER BY sort_order ASC, id DESC';

        $stmt = $pdo->query($sql);

        return array_map([self::class, 'format'], $stmt->fetchAll());
    }

    public static function getById(int $id): ?array
    {
        $pdo = getDatabaseConnection();
        $stmt = $pdo->prepare('SELECT * FROM testimonials WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row ? self::format($row) : null;
    }

    /** @param array<string, mixed> $input */
    public static function create(array $input): int
    {
        $pdo = getDatabaseConnection();
        $data = self::sanitize($input);

        $stmt = $pdo->prepare(
            'INSERT INTO testimonials
             (client_name, message, rating, initials, image_path, role_label, sort_order, is_published)
             VALUES
             (:client_name, :message, :rating, :initials, :image_path, :role_label, :sort_order, :is_published)'
        );
        $stmt->execute($data);

        return (int) $pdo->lastInsertId();
    }

    /** @param array<string, mixed> $input */
    public static function update(int $id, array $input): bool
    {
        $pdo = getDatabaseConnection();
        $data = self::sanitize($input);
        $data['id'] = $id;

        $stmt = $pdo->prepare(
            'UPDATE testimonials SET
                client_name = :client_name,
                message = :message,
                rating = :rating,
                initials = :initials,
                image_path = :image_path,
                role_label = :role_label,
                sort_order = :sort_order,
                is_published = :is_published
             WHERE id = :id'
        );

        return $stmt->execute($data);
    }

    public static function delete(int $id): bool
    {
        $pdo = getDatabaseConnection();
        $stmt = $pdo->prepare('DELETE FROM testimonials WHERE id = :id');

        return $stmt->execute(['id' => $id]);
    }

    /** @return array<string, int> */
    public static function getStats(): array
    {
        $pdo = getDatabaseConnection();
        $stmt = $pdo->query(
            'SELECT
                COUNT(*) AS total,
                SUM(is_published = 1) AS published
             FROM testimonials'
        );
        $row = $stmt->fetch() ?: [];

        return [
            'total'     => (int) ($row['total'] ?? 0),
            'published' => (int) ($row['published'] ?? 0),
        ];
    }

    /** @return array<int, int> Keys 1-5 */
    public static function getRatingDistribution(): array
    {
        $pdo = getDatabaseConnection();
        $stmt = $pdo->query(
            'SELECT rating, COUNT(*) AS count
             FROM testimonials
             WHERE is_published = 1
             GROUP BY rating'
        );
        $dist = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0];
        foreach ($stmt->fetchAll() as $row) {
            $rating = (int) $row['rating'];
            if ($rating >= 1 && $rating <= 5) {
                $dist[$rating] = (int) $row['count'];
            }
        }

        return $dist;
    }

    /** @return list<array{label:string,count:int}> */
    public static function getMonthlyCounts(int $months = 6): array
    {
        return self::monthlyCountsFromTable('testimonials', $months);
    }

    /** @param array<string, mixed> $row */
    private static function format(array $row): array
    {
        $imagePath = (string) ($row['image_path'] ?? '');

        return [
            'id'          => (int) $row['id'],
            '_id'         => (string) $row['id'],
            'name'        => (string) $row['client_name'],
            'clientName'  => (string) $row['client_name'],
            'message'     => (string) $row['message'],
            'rating'      => (int) $row['rating'],
            'initials'    => (string) ($row['initials'] ?? ''),
            'image'       => self::publicImageUrl($imagePath),
            'imagePath'   => $imagePath,
            'roleLabel'   => (string) ($row['role_label'] ?? 'Happy Client'),
            'sortOrder'   => (int) ($row['sort_order'] ?? 0),
            'isPublished' => (bool) ($row['is_published'] ?? true),
            'createdAt'   => (string) ($row['created_at'] ?? ''),
            'updatedAt'   => (string) ($row['updated_at'] ?? ''),
        ];
    }

    /** @param array<string, mixed> $input @return array<string, mixed> */
    private static function sanitize(array $input): array
    {
        $name = trim((string) ($input['name'] ?? $input['client_name'] ?? $input['clientName'] ?? ''));
        $message = trim((string) ($input['message'] ?? ''));
        $rating = max(1, min(5, (int) ($input['rating'] ?? 5)));
        $initials = trim((string) ($input['initials'] ?? ''));
        if ($initials === '' && $name !== '') {
            $initials = self::makeInitials($name);
        }

        return [
            'client_name'  => self::clip($name, 120),
            'message'      => self::clip($message, 5000),
            'rating'       => $rating,
            'initials'     => self::clip($initials, 8),
            'image_path'   => self::clip(trim((string) ($input['image_path'] ?? $input['imagePath'] ?? '')), 512),
            'role_label'   => self::clip(trim((string) ($input['role_label'] ?? $input['roleLabel'] ?? 'Happy Client')), 80),
            'sort_order'   => (int) ($input['sort_order'] ?? $input['sortOrder'] ?? 0),
            'is_published' => filter_var($input['is_published'] ?? $input['isPublished'] ?? true, FILTER_VALIDATE_BOOLEAN) ? 1 : 0,
        ];
    }

    private static function makeInitials(string $name): string
    {
        $parts = preg_split('/\s+/', trim($name)) ?: [];
        $initials = '';
        foreach ($parts as $part) {
            if ($part !== '') {
                $initials .= strtoupper($part[0]);
            }
        }

        return substr($initials, 0, 2);
    }

    private static function publicImageUrl(string $path): ?string
    {
        if ($path === '') {
            return null;
        }
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        return publicAssetUrl($path) ?? $path;
    }

    /** @return list<array{label:string,count:int}> */
    private static function monthlyCountsFromTable(string $table, int $months): array
    {
        $pdo = getDatabaseConnection();
        $months = max(1, min($months, 24));
        $allowed = ['testimonials', 'contact_inquiries', 'properties'];
        if (!in_array($table, $allowed, true)) {
            return [];
        }

        $stmt = $pdo->prepare(
            "SELECT DATE_FORMAT(created_at, '%Y-%m') AS month_key,
                    DATE_FORMAT(created_at, '%b') AS label,
                    COUNT(*) AS count
             FROM {$table}
             WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL :months MONTH)
             GROUP BY month_key, label
             ORDER BY month_key ASC"
        );
        $stmt->execute(['months' => $months - 1]);

        return array_map(static fn (array $row): array => [
            'label' => (string) $row['label'],
            'count' => (int) $row['count'],
        ], $stmt->fetchAll());
    }

    private static function clip(string $value, int $max): string
    {
        $value = trim($value);
        return strlen($value) <= $max ? $value : substr($value, 0, $max);
    }
}
