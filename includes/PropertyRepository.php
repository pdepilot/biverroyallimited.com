<?php
/**
 * Database operations for property listings (admin + public site).
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/database.php';
require_once __DIR__ . '/site_paths.php';

class PropertyRepository
{
    /**
     * @return list<array<string, mixed>>
     */
    public static function getAll(int $limit = 100, ?string $type = null, ?string $search = null): array
    {
        $pdo = getDatabaseConnection();
        [$sql, $params] = self::buildListQuery(false, false, $type, $search, 'newest');
        $limit = max(1, min($limit, 500));
        $sql .= ' LIMIT ' . (int) $limit;

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        return array_map([self::class, 'formatProperty'], $stmt->fetchAll());
    }

    /**
     * Public submissions queue for admin review.
     *
     * @return list<array<string, mixed>>
     */
    public static function getPublicSubmissions(?string $status = null, ?string $search = null, int $limit = 100): array
    {
        $pdo = getDatabaseConnection();
        $sql = 'SELECT * FROM properties WHERE source = \'public\'';
        $params = [];

        if ($status !== null && $status !== '' && in_array($status, ['pending', 'approved', 'rejected'], true)) {
            $sql .= ' AND approval_status = :status';
            $params['status'] = $status;
        }

        if ($search !== null && $search !== '') {
            $sql .= ' AND (title LIKE :q OR location LIKE :q OR owner_name LIKE :q OR owner_email LIKE :q OR owner_phone LIKE :q)';
            $params['q'] = '%' . $search . '%';
        }

        $sql .= ' ORDER BY created_at DESC LIMIT ' . max(1, min($limit, 500));

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        return array_map([self::class, 'formatProperty'], $stmt->fetchAll());
    }

    /**
     * @return array{total:int,pending:int,approved:int,rejected:int}
     */
    public static function getPublicSubmissionStats(): array
    {
        $pdo = getDatabaseConnection();
        $stmt = $pdo->query(
            "SELECT
                COUNT(*) AS total,
                SUM(approval_status = 'pending') AS pending,
                SUM(approval_status = 'approved') AS approved,
                SUM(approval_status = 'rejected') AS rejected
             FROM properties
             WHERE source = 'public'"
        );
        $row = $stmt->fetch() ?: [];

        return [
            'total'    => (int) ($row['total'] ?? 0),
            'pending'  => (int) ($row['pending'] ?? 0),
            'approved' => (int) ($row['approved'] ?? 0),
            'rejected' => (int) ($row['rejected'] ?? 0),
        ];
    }

    /**
     * Approved listings only — used by the public site.
     *
     * @return list<array<string, mixed>>
     */
    public static function getPublic(
        int $limit = 100,
        ?string $type = null,
        ?string $search = null,
        string $sort = 'newest'
    ): array {
        $pdo = getDatabaseConnection();
        [$sql, $params] = self::buildListQuery(true, false, $type, $search, $sort);
        $limit = max(1, min($limit, 500));
        $sql .= ' LIMIT ' . (int) $limit;

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        return array_map([self::class, 'formatProperty'], $stmt->fetchAll());
    }

    /**
     * Advanced search for the AI chatbot assistant.
     *
     * @param array{
     *   type?: ?string,
     *   search?: ?string,
     *   minPrice?: ?int,
     *   maxPrice?: ?int,
     *   locations?: list<string>,
     *   category?: ?string,
     *   luxury?: bool,
     *   commercial?: bool,
     *   cheapest?: bool,
     *   limit?: int,
     *   sort?: string
     * } $filters
     * @return list<array<string, mixed>>
     */
    public static function searchForChatbot(array $filters): array
    {
        $pdo = getDatabaseConnection();
        $sql = 'SELECT * FROM properties WHERE approval_status = \'approved\'';
        $params = [];

        if (self::hasListingStatusColumn($pdo)) {
            $sql .= ' AND listing_status = \'available\'';
        }

        $type = $filters['type'] ?? null;
        if ($type !== null && $type !== '' && in_array($type, ['sale', 'rent'], true)) {
            $sql .= ' AND type = :type';
            $params['type'] = $type;
        }

        $minPrice = isset($filters['minPrice']) ? (int) $filters['minPrice'] : null;
        $maxPrice = isset($filters['maxPrice']) ? (int) $filters['maxPrice'] : null;
        if ($minPrice !== null && $minPrice > 0) {
            $sql .= ' AND price >= :min_price';
            $params['min_price'] = $minPrice;
        }
        if ($maxPrice !== null && $maxPrice > 0) {
            $sql .= ' AND price <= :max_price';
            $params['max_price'] = $maxPrice;
        }

        $search = $filters['search'] ?? null;
        if ($search !== null && $search !== '') {
            $like = '%' . $search . '%';
            $sql .= ' AND (title LIKE :q_title OR location LIKE :q_location OR description LIKE :q_desc OR property_category LIKE :q_cat)';
            $params['q_title'] = $like;
            $params['q_location'] = $like;
            $params['q_desc'] = $like;
            $params['q_cat'] = $like;
        }

        $locations = $filters['locations'] ?? [];
        if ($locations !== []) {
            $parts = [];
            foreach (array_values($locations) as $i => $loc) {
                $like = '%' . $loc . '%';
                $parts[] = "(location LIKE :loc{$i}_l OR title LIKE :loc{$i}_t OR description LIKE :loc{$i}_d)";
                $params["loc{$i}_l"] = $like;
                $params["loc{$i}_t"] = $like;
                $params["loc{$i}_d"] = $like;
            }
            $sql .= ' AND (' . implode(' OR ', $parts) . ')';
        }

        if (!empty($filters['commercial'])) {
            $commercialLike = '%commercial%';
            $sql .= ' AND (property_category LIKE :comm_cat OR title LIKE :comm_title OR description LIKE :comm_desc)';
            $params['comm_cat'] = $commercialLike;
            $params['comm_title'] = $commercialLike;
            $params['comm_desc'] = $commercialLike;
        }

        if (!empty($filters['luxury'])) {
            $sql .= ' AND price >= :luxury_min';
            $params['luxury_min'] = 50_000_000;
        }

        $sort = $filters['sort'] ?? 'newest';
        if (!empty($filters['cheapest'])) {
            $sort = 'price_low';
        } elseif (!empty($filters['luxury'])) {
            $sort = 'price_high';
        }

        switch ($sort) {
            case 'price_low':
                $sql .= ' ORDER BY price ASC, created_at DESC';
                break;
            case 'price_high':
                $sql .= ' ORDER BY price DESC, created_at DESC';
                break;
            default:
                $sql .= ' ORDER BY created_at DESC';
                break;
        }

        $limit = max(1, min((int) ($filters['limit'] ?? 8), 20));
        $sql .= ' LIMIT ' . $limit;

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        return array_map([self::class, 'formatProperty'], $stmt->fetchAll());
    }

    private static function hasListingStatusColumn(PDO $pdo): bool
    {
        static $has = null;
        if ($has !== null) {
            return $has;
        }
        try {
            $stmt = $pdo->query(
                "SHOW COLUMNS FROM properties LIKE 'listing_status'"
            );
            $has = (bool) $stmt->fetch();
        } catch (PDOException) {
            $has = false;
        }
        return $has;
    }

    public static function getById(int $id): ?array
    {
        $pdo = getDatabaseConnection();
        $stmt = $pdo->prepare('SELECT * FROM properties WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row ? self::formatProperty($row) : null;
    }

    public static function getPublicById(int $id): ?array
    {
        $property = self::getById($id);

        if ($property === null || $property['approvalStatus'] !== 'approved') {
            return null;
        }

        return $property;
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function create(array $data): array
    {
        $data['source'] = $data['source'] ?? 'admin';
        $data['approvalStatus'] = $data['approvalStatus'] ?? 'approved';

        return self::insertProperty($data);
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function createPublicSubmission(array $data): array
    {
        $data['source'] = 'public';
        $data['approvalStatus'] = 'pending';

        return self::insertProperty($data);
    }

    public static function setApprovalStatus(int $id, string $status, ?string $adminNotes = null): ?array
    {
        $existing = self::getById($id);
        if ($existing === null) {
            return null;
        }

        $pdo = getDatabaseConnection();
        $stmt = $pdo->prepare(
            'UPDATE properties SET approval_status = :status, admin_notes = :notes WHERE id = :id'
        );
        $stmt->execute([
            'id'     => $id,
            'status' => self::normalizeStatus($status),
            'notes'  => $adminNotes,
        ]);

        return self::getById($id);
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function update(int $id, array $data): ?array
    {
        $existing = self::getById($id);
        if ($existing === null) {
            return null;
        }

        $pdo = getDatabaseConnection();
        $rawStmt = $pdo->prepare('SELECT gallery_urls FROM properties WHERE id = :id LIMIT 1');
        $rawStmt->execute(['id' => $id]);
        $rawGallery = ($rawStmt->fetch()['gallery_urls'] ?? null);

        $stmt = $pdo->prepare(
            'UPDATE properties SET
                title = :title,
                price = :price,
                type = :type,
                location = :location,
                bedrooms = :bedrooms,
                bathrooms = :bathrooms,
                area = :area,
                image_url = :image_url,
                video_url = :video_url,
                gallery_urls = :gallery_urls,
                description = :description,
                approval_status = :approval_status,
                listing_purpose = :listing_purpose,
                property_category = :property_category,
                property_address = :property_address,
                property_features = :property_features,
                ownership_status = :ownership_status,
                property_size = :property_size,
                owner_name = :owner_name,
                owner_email = :owner_email,
                owner_phone = :owner_phone,
                contact_method = :contact_method,
                admin_notes = :admin_notes
             WHERE id = :id'
        );
        $stmt->execute([
            'id'                => $id,
            'title'             => $data['title'] ?? $existing['title'],
            'price'             => (int) ($data['price'] ?? $existing['price']),
            'type'              => self::normalizeType($data['type'] ?? $existing['type']),
            'location'          => $data['location'] ?? $existing['location'],
            'bedrooms'          => max(0, (int) ($data['bedrooms'] ?? $existing['bedrooms'])),
            'bathrooms'         => max(0, (int) ($data['bathrooms'] ?? $existing['bathrooms'])),
            'area'              => max(0, (int) ($data['area'] ?? $existing['area'])),
            'image_url'         => array_key_exists('imageUrl', $data) ? ($data['imageUrl'] ?: null) : $existing['imageUrl'],
            'video_url'         => array_key_exists('videoUrl', $data) ? ($data['videoUrl'] ?: null) : $existing['videoUrl'],
            'gallery_urls'      => array_key_exists('galleryUrls', $data)
                ? self::encodeGallery($data['galleryUrls'])
                : $rawGallery,
            'description'       => array_key_exists('description', $data) ? ($data['description'] ?: null) : $existing['description'],
            'approval_status'   => self::normalizeStatus($data['approvalStatus'] ?? $existing['approvalStatus']),
            'listing_purpose'   => $data['listingPurpose'] ?? $existing['listingPurpose'],
            'property_category' => $data['propertyCategory'] ?? $existing['propertyCategory'],
            'property_address'  => $data['propertyAddress'] ?? $existing['propertyAddress'],
            'property_features'   => $data['propertyFeatures'] ?? $existing['propertyFeatures'],
            'ownership_status'  => $data['ownershipStatus'] ?? $existing['ownershipStatus'],
            'property_size'     => $data['propertySize'] ?? $existing['propertySize'],
            'owner_name'        => $data['ownerName'] ?? $existing['ownerName'],
            'owner_email'       => $data['ownerEmail'] ?? $existing['ownerEmail'],
            'owner_phone'       => $data['ownerPhone'] ?? $existing['ownerPhone'],
            'contact_method'    => $data['contactMethod'] ?? $existing['contactMethod'],
            'admin_notes'       => array_key_exists('adminNotes', $data) ? ($data['adminNotes'] ?: null) : $existing['adminNotes'],
        ]);

        return self::getById($id);
    }

    public static function delete(int $id): bool
    {
        $pdo = getDatabaseConnection();
        $stmt = $pdo->prepare('DELETE FROM properties WHERE id = :id');
        $stmt->execute(['id' => $id]);

        return $stmt->rowCount() > 0;
    }

    /**
     * @param array<string, mixed> $data
     */
    private static function insertProperty(array $data): array
    {
        $pdo = getDatabaseConnection();
        $stmt = $pdo->prepare(
            'INSERT INTO properties (
                title, price, type, location, bedrooms, bathrooms, area,
                image_url, video_url, gallery_urls, description, approval_status, source,
                owner_name, owner_email, owner_phone, contact_method,
                listing_purpose, property_category, property_address,
                property_features, ownership_status, property_size, admin_notes
             ) VALUES (
                :title, :price, :type, :location, :bedrooms, :bathrooms, :area,
                :image_url, :video_url, :gallery_urls, :description, :approval_status, :source,
                :owner_name, :owner_email, :owner_phone, :contact_method,
                :listing_purpose, :property_category, :property_address,
                :property_features, :ownership_status, :property_size, :admin_notes
             )'
        );
        $stmt->execute([
            'title'             => $data['title'],
            'price'             => (int) $data['price'],
            'type'              => self::normalizeType($data['type'] ?? 'sale'),
            'location'          => $data['location'],
            'bedrooms'          => max(0, (int) ($data['bedrooms'] ?? 2)),
            'bathrooms'         => max(0, (int) ($data['bathrooms'] ?? 2)),
            'area'              => max(0, (int) ($data['area'] ?? 0)),
            'image_url'         => $data['imageUrl'] ?? null,
            'video_url'         => $data['videoUrl'] ?? null,
            'gallery_urls'      => self::encodeGallery($data['galleryUrls'] ?? []),
            'description'       => $data['description'] ?? null,
            'approval_status'   => self::normalizeStatus($data['approvalStatus'] ?? 'pending'),
            'source'            => ($data['source'] ?? 'admin') === 'public' ? 'public' : 'admin',
            'owner_name'        => $data['ownerName'] ?? null,
            'owner_email'       => $data['ownerEmail'] ?? null,
            'owner_phone'       => $data['ownerPhone'] ?? null,
            'contact_method'    => $data['contactMethod'] ?? null,
            'listing_purpose'   => $data['listingPurpose'] ?? null,
            'property_category' => $data['propertyCategory'] ?? null,
            'property_address'  => $data['propertyAddress'] ?? null,
            'property_features'   => $data['propertyFeatures'] ?? null,
            'ownership_status'  => $data['ownershipStatus'] ?? null,
            'property_size'     => $data['propertySize'] ?? null,
            'admin_notes'       => $data['adminNotes'] ?? null,
        ]);

        $id = (int) $pdo->lastInsertId();
        $property = self::getById($id);

        if ($property === null) {
            throw new RuntimeException('Failed to load created property.');
        }

        return $property;
    }

    /**
     * @return array{0:string,1:array<string, mixed>}
     */
    private static function buildListQuery(
        bool $publicOnly,
        bool $publicSourceOnly,
        ?string $type,
        ?string $search,
        string $sort
    ): array {
        $sql = 'SELECT * FROM properties WHERE 1=1';
        $params = [];

        if ($publicOnly) {
            $sql .= ' AND approval_status = \'approved\'';
        }

        if ($publicSourceOnly) {
            $sql .= ' AND source = \'public\'';
        }

        if ($type !== null && $type !== '' && in_array($type, ['sale', 'rent'], true)) {
            $sql .= ' AND type = :type';
            $params['type'] = $type;
        }

        if ($search !== null && $search !== '') {
            $like = '%' . $search . '%';
            $sql .= ' AND (title LIKE :q_title OR location LIKE :q_location OR description LIKE :q_desc)';
            $params['q_title'] = $like;
            $params['q_location'] = $like;
            $params['q_desc'] = $like;
        }

        switch ($sort) {
            case 'price_low':
                $sql .= ' ORDER BY price ASC, created_at DESC';
                break;
            case 'price_high':
                $sql .= ' ORDER BY price DESC, created_at DESC';
                break;
            default:
                $sql .= ' ORDER BY created_at DESC';
                break;
        }

        return [$sql, $params];
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    public static function formatProperty(array $row): array
    {
        $imageUrl = $row['image_url'] ?? null;
        $videoUrl = $row['video_url'] ?? null;
        $gallery = self::decodeGallery($row['gallery_urls'] ?? null);
        $storedImages = array_values(array_filter(array_merge($imageUrl ? [$imageUrl] : [], $gallery)));
        $images = publicAssetUrls($storedImages);
        $imagePublic = publicAssetUrl($imageUrl);
        $videoPublic = publicAssetUrl($videoUrl);
        $videos = $videoPublic ? [$videoPublic] : [];

        return [
            'id'               => (int) $row['id'],
            '_id'              => (string) $row['id'],
            'title'            => $row['title'],
            'price'            => (int) $row['price'],
            'type'             => $row['type'],
            'location'         => $row['location'],
            'bedrooms'         => (int) ($row['bedrooms'] ?? 2),
            'bathrooms'        => (int) ($row['bathrooms'] ?? 2),
            'area'             => (int) ($row['area'] ?? 0),
            'imageUrl'         => $imagePublic,
            'videoUrl'         => $videoPublic,
            'galleryUrls'      => publicAssetUrls($gallery),
            'images'           => $images,
            'videos'           => $videos,
            'storedImages'     => $storedImages,
            'storedVideo'      => $videoUrl,
            'description'      => $row['description'] ?? null,
            'approvalStatus'   => $row['approval_status'],
            'source'           => $row['source'] ?? 'admin',
            'ownerName'        => $row['owner_name'] ?? null,
            'ownerEmail'       => $row['owner_email'] ?? null,
            'ownerPhone'       => $row['owner_phone'] ?? null,
            'contactMethod'    => $row['contact_method'] ?? null,
            'listingPurpose'   => $row['listing_purpose'] ?? null,
            'propertyCategory' => $row['property_category'] ?? null,
            'propertyAddress'  => $row['property_address'] ?? null,
            'propertyFeatures' => $row['property_features'] ?? null,
            'ownershipStatus'  => $row['ownership_status'] ?? null,
            'propertySize'     => $row['property_size'] ?? null,
            'adminNotes'       => $row['admin_notes'] ?? null,
            'createdAt'        => $row['created_at'],
            'updatedAt'        => $row['updated_at'],
        ];
    }

    private static function normalizeType(string $type): string
    {
        return $type === 'rent' ? 'rent' : 'sale';
    }

    private static function normalizeStatus(string $status): string
    {
        return in_array($status, ['pending', 'approved', 'rejected'], true) ? $status : 'pending';
    }

    /**
     * @param mixed $gallery
     */
    private static function encodeGallery($gallery): ?string
    {
        if (!is_array($gallery) || $gallery === []) {
            return null;
        }

        $paths = array_values(array_filter(array_map(static function ($item) {
            return is_string($item) && $item !== '' ? $item : null;
        }, $gallery)));

        return $paths === [] ? null : json_encode($paths, JSON_UNESCAPED_SLASHES);
    }

    /**
     * @return list<string>
     */
    private static function decodeGallery(?string $json): array
    {
        if ($json === null || $json === '') {
            return [];
        }

        $decoded = json_decode($json, true);
        if (!is_array($decoded)) {
            return [];
        }

        return array_values(array_filter($decoded, static fn ($item) => is_string($item) && $item !== ''));
    }

    /** @return array<string, int> */
    public static function getAdminStats(): array
    {
        $pdo = getDatabaseConnection();
        $stmt = $pdo->query(
            "SELECT
                COUNT(*) AS total,
                SUM(approval_status = 'approved') AS approved,
                SUM(approval_status = 'pending') AS pending,
                SUM(approval_status = 'rejected') AS rejected,
                SUM(type = 'sale') AS for_sale,
                SUM(type = 'rent') AS for_rent
             FROM properties"
        );
        $row = $stmt->fetch() ?: [];

        return [
            'total'    => (int) ($row['total'] ?? 0),
            'approved' => (int) ($row['approved'] ?? 0),
            'pending'  => (int) ($row['pending'] ?? 0),
            'rejected' => (int) ($row['rejected'] ?? 0),
            'forSale'  => (int) ($row['for_sale'] ?? 0),
            'forRent'  => (int) ($row['for_rent'] ?? 0),
        ];
    }

    /**
     * @return list<array{label:string,count:int}>
     */
    public static function getMonthlyListingCounts(int $months = 12): array
    {
        $pdo = getDatabaseConnection();
        $months = max(1, min($months, 24));
        $stmt = $pdo->prepare(
            "SELECT DATE_FORMAT(created_at, '%Y-%m') AS month_key,
                    DATE_FORMAT(created_at, '%b %Y') AS label,
                    COUNT(*) AS count
             FROM properties
             WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL :months MONTH)
             GROUP BY month_key, label
             ORDER BY month_key ASC"
        );
        $stmt->execute(['months' => $months - 1]);
        $rows = $stmt->fetchAll();

        return array_map(static fn (array $row): array => [
            'label' => (string) $row['label'],
            'count' => (int) $row['count'],
        ], $rows);
    }

    /** @return list<array<string, mixed>> */
    public static function getRecent(int $limit = 5): array
    {
        return self::getAll(max(1, min($limit, 20)));
    }
}
