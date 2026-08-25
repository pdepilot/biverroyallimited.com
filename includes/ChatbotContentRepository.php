<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/database.php';

final class ChatbotContentRepository
{
    /** @return list<array<string, mixed>> */
    public static function getIntents(): array
    {
        $pdo = getDatabaseConnection();
        $stmt = $pdo->query('SELECT * FROM chatbot_intents ORDER BY priority DESC, name ASC');

        return array_map([self::class, 'formatIntent'], $stmt->fetchAll());
    }

    public static function getIntentById(int $id): ?array
    {
        $pdo = getDatabaseConnection();
        $stmt = $pdo->prepare('SELECT * FROM chatbot_intents WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row ? self::formatIntent($row) : null;
    }

    /** @param array<string, mixed> $input */
    public static function saveIntent(array $input): int
    {
        $pdo = getDatabaseConnection();
        $id = (int) ($input['id'] ?? 0);
        $keywords = self::encodeKeywords($input['keywords'] ?? []);
        $data = [
            'intent_key'            => self::clip((string) ($input['intentKey'] ?? $input['intent_key'] ?? ''), 64),
            'name'                  => self::clip((string) ($input['name'] ?? ''), 120),
            'description'           => self::clip((string) ($input['description'] ?? ''), 2000),
            'keywords'              => $keywords,
            'priority'              => max(0, min(100, (int) ($input['priority'] ?? 50))),
            'confidence_threshold'  => max(0.1, min(1.0, (float) ($input['confidenceThreshold'] ?? 0.35))),
            'is_active'             => filter_var($input['isActive'] ?? true, FILTER_VALIDATE_BOOLEAN) ? 1 : 0,
        ];

        if ($data['intent_key'] === '' || $data['name'] === '') {
            throw new InvalidArgumentException('Intent key and name are required.');
        }

        if ($id > 0) {
            $data['id'] = $id;
            $stmt = $pdo->prepare(
                'UPDATE chatbot_intents SET intent_key=:intent_key, name=:name, description=:description,
                 keywords=:keywords, priority=:priority, confidence_threshold=:confidence_threshold, is_active=:is_active
                 WHERE id=:id'
            );
            $stmt->execute($data);
            self::invalidateCache();
            return $id;
        }

        $stmt = $pdo->prepare(
            'INSERT INTO chatbot_intents (intent_key, name, description, keywords, priority, confidence_threshold, is_active)
             VALUES (:intent_key, :name, :description, :keywords, :priority, :confidence_threshold, :is_active)'
        );
        $stmt->execute($data);
        self::invalidateCache();

        return (int) $pdo->lastInsertId();
    }

    public static function deleteIntent(int $id): bool
    {
        $pdo = getDatabaseConnection();
        $stmt = $pdo->prepare('DELETE FROM chatbot_intents WHERE id = :id');
        $ok = $stmt->execute(['id' => $id]);
        self::invalidateCache();

        return $ok;
    }

    /** @return list<array<string, mixed>> */
    public static function getResponses(?int $intentId = null): array
    {
        $pdo = getDatabaseConnection();
        if ($intentId !== null) {
            $stmt = $pdo->prepare(
                'SELECT r.*, i.name AS intent_name FROM chatbot_responses r
                 JOIN chatbot_intents i ON i.id = r.intent_id
                 WHERE r.intent_id = :intent_id ORDER BY r.weight DESC, r.id ASC'
            );
            $stmt->execute(['intent_id' => $intentId]);
        } else {
            $stmt = $pdo->query(
                'SELECT r.*, i.name AS intent_name FROM chatbot_responses r
                 JOIN chatbot_intents i ON i.id = r.intent_id
                 ORDER BY i.name ASC, r.weight DESC'
            );
        }

        return array_map([self::class, 'formatResponse'], $stmt->fetchAll());
    }

    /** @param array<string, mixed> $input */
    public static function saveResponse(array $input): int
    {
        $pdo = getDatabaseConnection();
        $id = (int) ($input['id'] ?? 0);
        $data = [
            'intent_id'     => (int) ($input['intentId'] ?? $input['intent_id'] ?? 0),
            'response_text' => self::clip((string) ($input['responseText'] ?? $input['response_text'] ?? ''), 10000),
            'weight'        => max(1, min(100, (int) ($input['weight'] ?? 1))),
            'is_active'     => filter_var($input['isActive'] ?? true, FILTER_VALIDATE_BOOLEAN) ? 1 : 0,
        ];

        if ($data['intent_id'] <= 0 || $data['response_text'] === '') {
            throw new InvalidArgumentException('Intent and response text are required.');
        }

        if ($id > 0) {
            $data['id'] = $id;
            $stmt = $pdo->prepare(
                'UPDATE chatbot_responses SET intent_id=:intent_id, response_text=:response_text,
                 weight=:weight, is_active=:is_active WHERE id=:id'
            );
            $stmt->execute($data);
            self::invalidateCache();
            return $id;
        }

        $stmt = $pdo->prepare(
            'INSERT INTO chatbot_responses (intent_id, response_text, weight, is_active)
             VALUES (:intent_id, :response_text, :weight, :is_active)'
        );
        $stmt->execute($data);
        self::invalidateCache();

        return (int) $pdo->lastInsertId();
    }

    public static function deleteResponse(int $id): bool
    {
        $pdo = getDatabaseConnection();
        $stmt = $pdo->prepare('DELETE FROM chatbot_responses WHERE id = :id');
        $ok = $stmt->execute(['id' => $id]);
        self::invalidateCache();

        return $ok;
    }

    /** @return list<array<string, mixed>> */
    public static function getKnowledge(): array
    {
        $pdo = getDatabaseConnection();
        $stmt = $pdo->query('SELECT * FROM chatbot_knowledgebase ORDER BY priority DESC, title ASC');

        return array_map([self::class, 'formatKnowledge'], $stmt->fetchAll());
    }

    /** @param array<string, mixed> $input */
    public static function saveKnowledge(array $input): int
    {
        $pdo = getDatabaseConnection();
        $id = (int) ($input['id'] ?? 0);
        $data = [
            'title'                 => self::clip((string) ($input['title'] ?? ''), 255),
            'content'               => self::clip((string) ($input['content'] ?? ''), 20000),
            'keywords'              => self::encodeKeywords($input['keywords'] ?? []),
            'category'              => self::clip((string) ($input['category'] ?? 'general'), 80),
            'priority'              => max(0, min(100, (int) ($input['priority'] ?? 50))),
            'match_score_threshold' => max(0.1, min(1.0, (float) ($input['matchScoreThreshold'] ?? 0.35))),
            'is_active'             => filter_var($input['isActive'] ?? true, FILTER_VALIDATE_BOOLEAN) ? 1 : 0,
        ];

        if ($data['title'] === '' || $data['content'] === '') {
            throw new InvalidArgumentException('Title and content are required.');
        }

        if ($id > 0) {
            $data['id'] = $id;
            $stmt = $pdo->prepare(
                'UPDATE chatbot_knowledgebase SET title=:title, content=:content, keywords=:keywords,
                 category=:category, priority=:priority, match_score_threshold=:match_score_threshold, is_active=:is_active
                 WHERE id=:id'
            );
            $stmt->execute($data);
            self::invalidateCache();
            return $id;
        }

        $stmt = $pdo->prepare(
            'INSERT INTO chatbot_knowledgebase (title, content, keywords, category, priority, match_score_threshold, is_active)
             VALUES (:title, :content, :keywords, :category, :priority, :match_score_threshold, :is_active)'
        );
        $stmt->execute($data);
        self::invalidateCache();

        return (int) $pdo->lastInsertId();
    }

    public static function deleteKnowledge(int $id): bool
    {
        $pdo = getDatabaseConnection();
        $stmt = $pdo->prepare('DELETE FROM chatbot_knowledgebase WHERE id = :id');
        $ok = $stmt->execute(['id' => $id]);
        self::invalidateCache();

        return $ok;
    }

    /** @param array<string, mixed> $row */
    private static function formatIntent(array $row): array
    {
        return [
            'id'                   => (int) $row['id'],
            'intentKey'            => (string) $row['intent_key'],
            'name'                 => (string) $row['name'],
            'description'          => (string) ($row['description'] ?? ''),
            'keywords'             => self::decodeKeywords($row['keywords'] ?? '[]'),
            'priority'             => (int) ($row['priority'] ?? 50),
            'confidenceThreshold'  => (float) ($row['confidence_threshold'] ?? 0.35),
            'isActive'             => (bool) ($row['is_active'] ?? true),
        ];
    }

    /** @param array<string, mixed> $row */
    private static function formatResponse(array $row): array
    {
        return [
            'id'           => (int) $row['id'],
            'intentId'     => (int) $row['intent_id'],
            'intentName'   => (string) ($row['intent_name'] ?? ''),
            'responseText' => (string) $row['response_text'],
            'weight'       => (int) ($row['weight'] ?? 1),
            'isActive'     => (bool) ($row['is_active'] ?? true),
        ];
    }

    /** @param array<string, mixed> $row */
    private static function formatKnowledge(array $row): array
    {
        return [
            'id'                  => (int) $row['id'],
            'title'               => (string) $row['title'],
            'content'             => (string) $row['content'],
            'keywords'            => self::decodeKeywords($row['keywords'] ?? '[]'),
            'category'            => (string) ($row['category'] ?? 'general'),
            'priority'            => (int) ($row['priority'] ?? 50),
            'matchScoreThreshold' => (float) ($row['match_score_threshold'] ?? 0.35),
            'isActive'            => (bool) ($row['is_active'] ?? true),
        ];
    }

    /** @return list<string> */
    private static function decodeKeywords(mixed $raw): array
    {
        if (is_array($raw)) {
            return array_values(array_filter(array_map('strval', $raw)));
        }
        $decoded = json_decode((string) $raw, true);

        return is_array($decoded) ? array_values(array_filter(array_map('strval', $decoded))) : [];
    }

    /** @param mixed $keywords */
    private static function encodeKeywords(mixed $keywords): string
    {
        if (is_string($keywords)) {
            $keywords = array_values(array_filter(array_map('trim', preg_split('/[,;]+/', $keywords) ?: [])));
        }

        return json_encode(is_array($keywords) ? $keywords : [], JSON_UNESCAPED_UNICODE);
    }

    private static function invalidateCache(): void
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
