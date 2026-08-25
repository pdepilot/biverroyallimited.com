<?php
/**
 * Blog posts for the public blog.php page and admin management.
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/database.php';

final class BlogRepository
{
    public const CATEGORIES = [
        'market-insights' => 'Market Insights',
        'buying-guides'   => 'Buying Guides',
        'selling-tips'    => 'Selling Tips',
        'investment'      => 'Investment',
        'lifestyle'       => 'Lifestyle',
        'company-news'    => 'Company News',
    ];

    public static function ensureSchema(): void
    {
        static $done = false;
        if ($done) {
            return;
        }

        $pdo = getDatabaseConnection();
        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS blog_posts (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                title VARCHAR(255) NOT NULL,
                slug VARCHAR(255) NOT NULL,
                excerpt TEXT NULL,
                content MEDIUMTEXT NOT NULL,
                cover_image VARCHAR(512) NOT NULL DEFAULT \'\',
                category VARCHAR(80) NOT NULL DEFAULT \'market-insights\',
                author_name VARCHAR(120) NOT NULL DEFAULT \'Biver Royalty Homes\',
                is_published TINYINT(1) NOT NULL DEFAULT 0,
                published_at DATETIME NULL DEFAULT NULL,
                view_count INT UNSIGNED NOT NULL DEFAULT 0,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY uq_blog_posts_slug (slug),
                KEY idx_blog_posts_published (is_published, published_at),
                KEY idx_blog_posts_category (category, is_published)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );

        self::seedDefaultsIfEmpty($pdo);
        $done = true;
    }

    /** @return list<array<string, mixed>> */
    public static function getAll(bool $publishedOnly = false): array
    {
        self::ensureSchema();
        $pdo = getDatabaseConnection();
        $sql = 'SELECT * FROM blog_posts';
        if ($publishedOnly) {
            $sql .= ' WHERE is_published = 1';
        }
        $sql .= ' ORDER BY COALESCE(published_at, created_at) DESC, id DESC';

        return array_map([self::class, 'format'], $pdo->query($sql)->fetchAll());
    }

    /** @return list<array<string, mixed>> */
    public static function getPublic(?string $category = null, ?string $search = null): array
    {
        self::ensureSchema();
        $pdo = getDatabaseConnection();
        $sql = 'SELECT * FROM blog_posts WHERE is_published = 1';
        $params = [];

        if ($category !== null && $category !== '' && $category !== 'all') {
            $sql .= ' AND category = :category';
            $params['category'] = self::normalizeCategory($category);
        }

        if ($search !== null && trim($search) !== '') {
            $sql .= ' AND (title LIKE :q OR excerpt LIKE :q OR content LIKE :q OR author_name LIKE :q)';
            $params['q'] = '%' . trim($search) . '%';
        }

        $sql .= ' ORDER BY COALESCE(published_at, created_at) DESC, id DESC';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        return array_map([self::class, 'format'], $stmt->fetchAll());
    }

    /** @return array<string, mixed>|null */
    public static function getById(int $id): ?array
    {
        self::ensureSchema();
        $stmt = getDatabaseConnection()->prepare('SELECT * FROM blog_posts WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row ? self::format($row) : null;
    }

    /** @return array<string, mixed>|null */
    public static function getBySlug(string $slug, bool $publishedOnly = true): ?array
    {
        self::ensureSchema();
        $slug = self::normalizeSlug($slug);
        if ($slug === '') {
            return null;
        }

        $sql = 'SELECT * FROM blog_posts WHERE slug = :slug';
        if ($publishedOnly) {
            $sql .= ' AND is_published = 1';
        }
        $sql .= ' LIMIT 1';
        $stmt = getDatabaseConnection()->prepare($sql);
        $stmt->execute(['slug' => $slug]);
        $row = $stmt->fetch();

        return $row ? self::format($row) : null;
    }

    /** @param array<string, mixed> $input */
    public static function create(array $input): int
    {
        self::ensureSchema();
        $data = self::sanitizeInput($input);
        $slug = self::uniqueSlug($data['slug'] !== '' ? $data['slug'] : $data['title']);

        $pdo = getDatabaseConnection();
        $stmt = $pdo->prepare(
            'INSERT INTO blog_posts
                (title, slug, excerpt, content, cover_image, category, author_name, is_published, published_at)
             VALUES
                (:title, :slug, :excerpt, :content, :cover, :category, :author, :published, :published_at)'
        );
        $stmt->execute([
            'title'        => $data['title'],
            'slug'         => $slug,
            'excerpt'      => $data['excerpt'],
            'content'      => $data['content'],
            'cover'        => $data['coverImage'],
            'category'     => $data['category'],
            'author'       => $data['authorName'],
            'published'    => $data['isPublished'] ? 1 : 0,
            'published_at' => $data['isPublished'] ? ($data['publishedAt'] ?: date('Y-m-d H:i:s')) : null,
        ]);

        return (int) $pdo->lastInsertId();
    }

    /** @param array<string, mixed> $input */
    public static function update(int $id, array $input): void
    {
        self::ensureSchema();
        $existing = self::getById($id);
        if ($existing === null) {
            throw new InvalidArgumentException('Blog post not found.');
        }

        $data = self::sanitizeInput(array_merge([
            'title'       => $existing['title'],
            'slug'        => $existing['slug'],
            'excerpt'     => $existing['excerpt'],
            'content'     => $existing['content'],
            'coverImage'  => $existing['coverImage'],
            'category'    => $existing['category'],
            'authorName'  => $existing['authorName'],
            'isPublished' => $existing['isPublished'],
            'publishedAt' => $existing['publishedAt'],
        ], $input));

        $slug = self::uniqueSlug($data['slug'] !== '' ? $data['slug'] : $data['title'], $id);
        $publishedAt = $existing['publishedAt'];
        if ($data['isPublished']) {
            $publishedAt = $data['publishedAt'] ?: ($publishedAt ?: date('Y-m-d H:i:s'));
        }

        $stmt = getDatabaseConnection()->prepare(
            'UPDATE blog_posts SET
                title = :title,
                slug = :slug,
                excerpt = :excerpt,
                content = :content,
                cover_image = :cover,
                category = :category,
                author_name = :author,
                is_published = :published,
                published_at = :published_at
             WHERE id = :id'
        );
        $stmt->execute([
            'id'           => $id,
            'title'        => $data['title'],
            'slug'         => $slug,
            'excerpt'      => $data['excerpt'],
            'content'      => $data['content'],
            'cover'        => $data['coverImage'],
            'category'     => $data['category'],
            'author'       => $data['authorName'],
            'published'    => $data['isPublished'] ? 1 : 0,
            'published_at' => $data['isPublished'] ? $publishedAt : null,
        ]);
    }

    public static function setPublished(int $id, bool $published): void
    {
        self::ensureSchema();
        $existing = self::getById($id);
        if ($existing === null) {
            throw new InvalidArgumentException('Blog post not found.');
        }

        $publishedAt = $published
            ? ($existing['publishedAt'] ?: date('Y-m-d H:i:s'))
            : null;

        $stmt = getDatabaseConnection()->prepare(
            'UPDATE blog_posts SET is_published = :p, published_at = :pa WHERE id = :id'
        );
        $stmt->execute([
            'id' => $id,
            'p'  => $published ? 1 : 0,
            'pa' => $publishedAt,
        ]);
    }

    public static function delete(int $id): void
    {
        self::ensureSchema();
        $stmt = getDatabaseConnection()->prepare('DELETE FROM blog_posts WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    public static function incrementViews(int $id): void
    {
        self::ensureSchema();
        $stmt = getDatabaseConnection()->prepare(
            'UPDATE blog_posts SET view_count = view_count + 1 WHERE id = :id AND is_published = 1'
        );
        $stmt->execute(['id' => $id]);
    }

    /** @return array{total:int,published:int} */
    public static function getStats(): array
    {
        self::ensureSchema();
        $row = getDatabaseConnection()->query(
            'SELECT COUNT(*) AS total, SUM(is_published = 1) AS published FROM blog_posts'
        )->fetch() ?: [];

        return [
            'total'     => (int) ($row['total'] ?? 0),
            'published' => (int) ($row['published'] ?? 0),
        ];
    }

    public static function categoryLabel(string $category): string
    {
        $key = self::normalizeCategory($category);

        return self::CATEGORIES[$key] ?? ucwords(str_replace(['_', '-'], ' ', $key));
    }

    public static function normalizeCategory(string $category): string
    {
        $category = strtolower(trim($category));
        $category = preg_replace('/[^a-z0-9_-]+/', '-', $category) ?? 'market-insights';
        $category = trim($category, '-_');
        if ($category === '') {
            return 'market-insights';
        }

        return array_key_exists($category, self::CATEGORIES) ? $category : substr($category, 0, 80);
    }

    public static function normalizeSlug(string $slug): string
    {
        $slug = strtolower(trim($slug));
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug) ?? '';
        $slug = trim($slug, '-');

        return substr($slug, 0, 220);
    }

    public static function uniqueSlug(string $base, ?int $ignoreId = null): string
    {
        self::ensureSchema();
        $slug = self::normalizeSlug($base);
        if ($slug === '') {
            $slug = 'post-' . date('YmdHis');
        }

        $pdo = getDatabaseConnection();
        $candidate = $slug;
        $i = 2;
        while (true) {
            $sql = 'SELECT id FROM blog_posts WHERE slug = :slug';
            $params = ['slug' => $candidate];
            if ($ignoreId !== null) {
                $sql .= ' AND id <> :id';
                $params['id'] = $ignoreId;
            }
            $sql .= ' LIMIT 1';
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            if (!$stmt->fetch()) {
                return $candidate;
            }
            $candidate = substr($slug, 0, 200) . '-' . $i;
            $i++;
        }
    }

    /** @param array<string, mixed> $row */
    private static function format(array $row): array
    {
        $category = (string) ($row['category'] ?? 'market-insights');

        return [
            'id'          => (int) ($row['id'] ?? 0),
            'title'       => (string) ($row['title'] ?? ''),
            'slug'        => (string) ($row['slug'] ?? ''),
            'excerpt'     => (string) ($row['excerpt'] ?? ''),
            'content'     => (string) ($row['content'] ?? ''),
            'coverImage'  => (string) ($row['cover_image'] ?? ''),
            'category'    => $category,
            'categoryLabel' => self::categoryLabel($category),
            'authorName'  => (string) ($row['author_name'] ?? 'Biver Royalty Homes'),
            'isPublished' => (bool) ($row['is_published'] ?? false),
            'publishedAt' => (string) ($row['published_at'] ?? ''),
            'viewCount'   => (int) ($row['view_count'] ?? 0),
            'createdAt'   => (string) ($row['created_at'] ?? ''),
            'updatedAt'   => (string) ($row['updated_at'] ?? ''),
        ];
    }

    /**
     * @param array<string, mixed> $input
     * @return array{title:string,slug:string,excerpt:string,content:string,coverImage:string,category:string,authorName:string,isPublished:bool,publishedAt:string}
     */
    private static function sanitizeInput(array $input): array
    {
        $title = trim((string) ($input['title'] ?? ''));
        $content = trim((string) ($input['content'] ?? ''));
        if ($title === '' || $content === '') {
            throw new InvalidArgumentException('Title and content are required.');
        }

        $isPublished = filter_var($input['isPublished'] ?? $input['is_published'] ?? false, FILTER_VALIDATE_BOOLEAN);

        return [
            'title'       => self::clip($title, 255),
            'slug'        => self::normalizeSlug((string) ($input['slug'] ?? '')),
            'excerpt'     => self::clip(trim((string) ($input['excerpt'] ?? '')), 1000),
            'content'     => $content,
            'coverImage'  => self::clip(trim((string) ($input['coverImage'] ?? $input['cover_image'] ?? '')), 512),
            'category'    => self::normalizeCategory((string) ($input['category'] ?? 'market-insights')),
            'authorName'  => self::clip(trim((string) ($input['authorName'] ?? $input['author_name'] ?? 'Biver Royalty Homes')) ?: 'Biver Royalty Homes', 120),
            'isPublished' => $isPublished,
            'publishedAt' => trim((string) ($input['publishedAt'] ?? $input['published_at'] ?? '')),
        ];
    }

    private static function clip(string $value, int $max): string
    {
        if (function_exists('mb_substr')) {
            return mb_substr($value, 0, $max);
        }

        return substr($value, 0, $max);
    }

    private static function seedDefaultsIfEmpty(PDO $pdo): void
    {
        $count = (int) $pdo->query('SELECT COUNT(*) FROM blog_posts')->fetchColumn();
        if ($count > 0) {
            return;
        }

        $defaults = [
            [
                'title' => 'How to Buy Land Safely in Imo State',
                'slug' => 'buy-land-safely-imo-state',
                'excerpt' => 'A practical checklist for verifying title, surveying plots, and avoiding common land scams in Owerri and across Imo State.',
                'content' => "Buying land in Imo State can be one of the smartest long-term investments — when due diligence is done properly.\n\nStart with verified ownership documents: Certificate of Occupancy (C of O), Governor’s Consent where applicable, survey plan, and a clear receipt trail. Never rely on verbal assurances alone.\n\nNext, work with a licensed surveyor and a trusted real estate partner to confirm boundaries, road access, and flooding risk. At Biver Royalty Homes, every listing we recommend goes through integrity checks before we present it to clients.\n\nFinally, put agreements in writing and involve a qualified lawyer for conveyancing. A little patience at this stage protects your capital for decades.",
                'category' => 'buying-guides',
                'author' => 'Biver Royalty Homes',
            ],
            [
                'title' => 'Why Owerri Remains a Strong Property Market',
                'slug' => 'owerri-property-market-outlook',
                'excerpt' => 'From infrastructure growth to residential demand, here is why Owerri continues to attract homebuyers and investors.',
                'content' => "Owerri’s real estate story is shaped by steady population growth, expanding commercial corridors, and demand for quality family homes.\n\nInvestors are looking beyond speculative plots to finished homes and well-planned estates — places where families can live with dignity and security.\n\nAt Biver Royalty Homes, we focus on properties that balance lifestyle and value: verified titles, practical layouts, and locations with lasting demand.\n\nWhether you are buying your first home or expanding a portfolio, local market knowledge remains your strongest advantage.",
                'category' => 'market-insights',
                'author' => 'Biver Royalty Homes',
            ],
            [
                'title' => '5 Tips Before Listing Your Property for Sale',
                'slug' => 'tips-before-listing-property',
                'excerpt' => 'Presentation, pricing, and paperwork — the essentials that help your property sell faster and with fewer surprises.',
                'content' => "A strong listing begins long before the first viewing.\n\n1. Gather documents: title papers, utility receipts, and clear ownership proof.\n2. Price with the market — not emotion. Overpricing slows serious buyers.\n3. Present the home well: clean spaces, good lighting, and honest photos.\n4. Be transparent about defects; trust closes deals faster than polish alone.\n5. Partner with an agency that screens buyers and manages negotiations professionally.\n\nWhen you list with Biver Royalty Homes, we handle presentation, qualified enquiries, and guided next steps so you can sell with confidence.",
                'category' => 'selling-tips',
                'author' => 'Biver Royalty Homes',
            ],
        ];

        $stmt = $pdo->prepare(
            'INSERT INTO blog_posts
                (title, slug, excerpt, content, cover_image, category, author_name, is_published, published_at)
             VALUES
                (:title, :slug, :excerpt, :content, \'\', :category, :author, 1, NOW())'
        );
        foreach ($defaults as $post) {
            $stmt->execute($post);
        }
    }
}
