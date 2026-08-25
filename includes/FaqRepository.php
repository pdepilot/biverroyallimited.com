<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/database.php';

final class FaqRepository
{
    /** Shared category keys used by admin + public FAQs page. */
    public const CATEGORIES = [
        'general'    => 'General',
        'buying'     => 'Buying',
        'renting'    => 'Renting',
        'selling'    => 'Selling',
        'payments'   => 'Payments',
        'viewing'    => 'Viewings',
        'legal'      => 'Legal',
        'property'   => 'Property',
        'investment' => 'Investment',
    ];

    public static function ensureSchema(): void
    {
        static $done = false;
        if ($done) {
            return;
        }

        $pdo = getDatabaseConnection();
        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS chatbot_faqs (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                question VARCHAR(500) NOT NULL,
                answer TEXT NOT NULL,
                keywords JSON DEFAULT NULL,
                category VARCHAR(80) DEFAULT \'general\',
                priority INT NOT NULL DEFAULT 50,
                match_score_threshold DECIMAL(4,2) NOT NULL DEFAULT 0.40,
                is_active TINYINT(1) NOT NULL DEFAULT 1,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY idx_chatbot_faqs_category (category, is_active),
                KEY idx_chatbot_faqs_active_priority (is_active, priority)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );

        $done = true;
    }

    public static function categoryLabel(string $category): string
    {
        $key = self::normalizeCategory($category);

        return self::CATEGORIES[$key] ?? ucwords(str_replace(['_', '-'], ' ', $key));
    }

    public static function normalizeCategory(string $category): string
    {
        $category = strtolower(trim($category));
        $category = preg_replace('/[^a-z0-9_-]+/', '-', $category) ?? 'general';
        $category = trim($category, '-_');

        return $category !== '' ? substr($category, 0, 80) : 'general';
    }

    /** @return list<array<string, mixed>> */
    public static function getAll(bool $activeOnly = false): array
    {
        self::ensureSchema();
        $pdo = getDatabaseConnection();
        $sql = 'SELECT * FROM chatbot_faqs';
        if ($activeOnly) {
            $sql .= ' WHERE is_active = 1';
        }
        $sql .= ' ORDER BY priority DESC, id ASC';

        return array_map([self::class, 'format'], $pdo->query($sql)->fetchAll());
    }

    /** Active FAQs for the public FAQs + Contact pages (same source as admin). */
    public static function getPublic(): array
    {
        return self::getAll(true);
    }

    public static function getById(int $id): ?array
    {
        self::ensureSchema();
        $pdo = getDatabaseConnection();
        $stmt = $pdo->prepare('SELECT * FROM chatbot_faqs WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row ? self::format($row) : null;
    }

    /** @param array<string, mixed> $input */
    public static function create(array $input): int
    {
        self::ensureSchema();
        $pdo = getDatabaseConnection();
        $data = self::sanitize($input);

        $stmt = $pdo->prepare(
            'INSERT INTO chatbot_faqs
             (question, answer, keywords, category, priority, match_score_threshold, is_active)
             VALUES
             (:question, :answer, :keywords, :category, :priority, :match_score_threshold, :is_active)'
        );
        $stmt->execute($data);
        self::invalidateCaches();

        return (int) $pdo->lastInsertId();
    }

    /** @param array<string, mixed> $input */
    public static function update(int $id, array $input): bool
    {
        self::ensureSchema();
        $pdo = getDatabaseConnection();
        $data = self::sanitize($input);
        $data['id'] = $id;

        $stmt = $pdo->prepare(
            'UPDATE chatbot_faqs SET
                question = :question,
                answer = :answer,
                keywords = :keywords,
                category = :category,
                priority = :priority,
                match_score_threshold = :match_score_threshold,
                is_active = :is_active
             WHERE id = :id'
        );
        $ok = $stmt->execute($data);
        self::invalidateCaches();

        return $ok;
    }

    public static function setActive(int $id, bool $active): bool
    {
        self::ensureSchema();
        $pdo = getDatabaseConnection();
        $stmt = $pdo->prepare('UPDATE chatbot_faqs SET is_active = :active WHERE id = :id');
        $ok = $stmt->execute([
            'active' => $active ? 1 : 0,
            'id'     => $id,
        ]);
        self::invalidateCaches();

        return $ok;
    }

    public static function delete(int $id): bool
    {
        self::ensureSchema();
        $pdo = getDatabaseConnection();
        $stmt = $pdo->prepare('DELETE FROM chatbot_faqs WHERE id = :id');
        $ok = $stmt->execute(['id' => $id]);
        self::invalidateCaches();

        return $ok;
    }

    /** @return array<string, int> */
    public static function getStats(): array
    {
        self::ensureSchema();
        $pdo = getDatabaseConnection();
        $stmt = $pdo->query(
            'SELECT COUNT(*) AS total, SUM(is_active = 1) AS active FROM chatbot_faqs'
        );
        $row = $stmt->fetch() ?: [];

        return [
            'total'  => (int) ($row['total'] ?? 0),
            'active' => (int) ($row['active'] ?? 0),
        ];
    }

    /** @param array<string, mixed> $row */
    private static function format(array $row): array
    {
        $keywords = $row['keywords'] ?? '[]';
        if (is_string($keywords)) {
            $decoded = json_decode($keywords, true);
            $keywords = is_array($decoded) ? $decoded : [];
        }

        $category = self::normalizeCategory((string) ($row['category'] ?? 'general'));

        return [
            'id'                  => (int) $row['id'],
            'question'            => (string) $row['question'],
            'answer'              => (string) $row['answer'],
            'keywords'            => is_array($keywords) ? array_values($keywords) : [],
            'category'            => $category,
            'categoryLabel'       => self::categoryLabel($category),
            'priority'            => (int) ($row['priority'] ?? 50),
            'matchScoreThreshold' => (float) ($row['match_score_threshold'] ?? 0.4),
            'isActive'            => (int) ($row['is_active'] ?? 1) === 1,
            'createdAt'           => (string) ($row['created_at'] ?? ''),
            'updatedAt'           => (string) ($row['updated_at'] ?? ''),
        ];
    }

    /** @param array<string, mixed> $input @return array<string, mixed> */
    private static function sanitize(array $input): array
    {
        $keywords = $input['keywords'] ?? [];
        if (is_string($keywords)) {
            $keywords = array_values(array_filter(array_map('trim', preg_split('/[,;]+/', $keywords) ?: [])));
        }
        if (!is_array($keywords)) {
            $keywords = [];
        }

        $isActiveRaw = $input['isActive'] ?? $input['is_active'] ?? true;
        if (is_string($isActiveRaw)) {
            $isActive = in_array(strtolower($isActiveRaw), ['1', 'true', 'yes', 'on'], true);
        } else {
            $isActive = (bool) $isActiveRaw;
        }

        return [
            'question'              => self::clip((string) ($input['question'] ?? ''), 500),
            'answer'                => self::clip((string) ($input['answer'] ?? ''), 10000),
            'keywords'              => json_encode(array_values($keywords), JSON_UNESCAPED_UNICODE),
            'category'              => self::normalizeCategory((string) ($input['category'] ?? 'general')),
            'priority'              => max(0, min(100, (int) ($input['priority'] ?? 50))),
            'match_score_threshold' => max(0.1, min(1.0, (float) ($input['matchScoreThreshold'] ?? $input['match_score_threshold'] ?? 0.4))),
            'is_active'             => $isActive ? 1 : 0,
        ];
    }

    private static function invalidateCaches(): void
    {
        $repoFile = dirname(__DIR__) . '/chatbot/includes/ChatbotRepository.php';
        if (is_file($repoFile)) {
            require_once $repoFile;
            ChatbotRepository::invalidateContentCache();
        }
    }

    private static function clip(string $value, int $max): string
    {
        $value = trim($value);

        return strlen($value) <= $max ? $value : substr($value, 0, $max);
    }
}
