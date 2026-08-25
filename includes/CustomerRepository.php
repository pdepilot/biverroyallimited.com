<?php
/**
 * Database operations for customer (CRM) records.
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/database.php';

final class CustomerRepository
{
    public const TYPES = ['buyer', 'seller', 'renter', 'tenant', 'landlord', 'investor', 'other'];
    public const STATUSES = ['active', 'lead', 'vip', 'inactive'];

    private static bool $schemaReady = false;

    /**
     * Create the customers table on first use so no manual migration is required.
     */
    public static function ensureSchema(): void
    {
        if (self::$schemaReady) {
            return;
        }

        $pdo = getDatabaseConnection();
        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS customers (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(150) NOT NULL,
                email VARCHAR(190) DEFAULT NULL,
                phone VARCHAR(40) DEFAULT NULL,
                address VARCHAR(255) DEFAULT NULL,
                city VARCHAR(100) DEFAULT NULL,
                state VARCHAR(100) DEFAULT NULL,
                customer_type VARCHAR(30) NOT NULL DEFAULT "buyer",
                status VARCHAR(20) NOT NULL DEFAULT "active",
                source VARCHAR(50) DEFAULT "manual",
                notes TEXT DEFAULT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_customers_status (status),
                INDEX idx_customers_type (customer_type),
                INDEX idx_customers_email (email)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );

        self::$schemaReady = true;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function getAll(?string $search = null, ?string $status = null, ?string $type = null, int $limit = 500): array
    {
        self::ensureSchema();
        $pdo = getDatabaseConnection();

        $sql = 'SELECT * FROM customers WHERE 1=1';
        $params = [];

        if ($search !== null && $search !== '') {
            $sql .= ' AND (name LIKE :q OR email LIKE :q OR phone LIKE :q OR city LIKE :q OR state LIKE :q)';
            $params['q'] = '%' . $search . '%';
        }
        if ($status !== null && in_array($status, self::STATUSES, true)) {
            $sql .= ' AND status = :status';
            $params['status'] = $status;
        }
        if ($type !== null && in_array($type, self::TYPES, true)) {
            $sql .= ' AND customer_type = :type';
            $params['type'] = $type;
        }

        $sql .= ' ORDER BY created_at DESC LIMIT ' . max(1, min($limit, 1000));

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        return array_map([self::class, 'format'], $stmt->fetchAll());
    }

    public static function getById(int $id): ?array
    {
        self::ensureSchema();
        $pdo = getDatabaseConnection();
        $stmt = $pdo->prepare('SELECT * FROM customers WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row ? self::format($row) : null;
    }

    /**
     * @param array<string, mixed> $input
     */
    public static function create(array $input): int
    {
        self::ensureSchema();
        $pdo = getDatabaseConnection();
        $data = self::sanitize($input);

        $stmt = $pdo->prepare(
            'INSERT INTO customers
                (name, email, phone, address, city, state, customer_type, status, source, notes)
             VALUES
                (:name, :email, :phone, :address, :city, :state, :customer_type, :status, :source, :notes)'
        );
        $stmt->execute($data);

        return (int) $pdo->lastInsertId();
    }

    /**
     * @param array<string, mixed> $input
     */
    public static function update(int $id, array $input): bool
    {
        self::ensureSchema();
        $pdo = getDatabaseConnection();
        $data = self::sanitize($input);
        $data['id'] = $id;

        $stmt = $pdo->prepare(
            'UPDATE customers SET
                name = :name,
                email = :email,
                phone = :phone,
                address = :address,
                city = :city,
                state = :state,
                customer_type = :customer_type,
                status = :status,
                source = :source,
                notes = :notes
             WHERE id = :id'
        );

        return $stmt->execute($data);
    }

    public static function delete(int $id): bool
    {
        self::ensureSchema();
        $pdo = getDatabaseConnection();
        $stmt = $pdo->prepare('DELETE FROM customers WHERE id = :id');
        $stmt->execute(['id' => $id]);

        return $stmt->rowCount() > 0;
    }

    /**
     * @return array<string, int>
     */
    public static function getStats(): array
    {
        self::ensureSchema();
        $pdo = getDatabaseConnection();
        $row = $pdo->query(
            "SELECT
                COUNT(*) AS total,
                SUM(status = 'active') AS active,
                SUM(status = 'lead') AS leads,
                SUM(status = 'vip') AS vip
             FROM customers"
        )->fetch() ?: [];

        return [
            'total'  => (int) ($row['total'] ?? 0),
            'active' => (int) ($row['active'] ?? 0),
            'leads'  => (int) ($row['leads'] ?? 0),
            'vip'    => (int) ($row['vip'] ?? 0),
        ];
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private static function format(array $row): array
    {
        return [
            'id'           => (int) $row['id'],
            'name'         => (string) $row['name'],
            'email'        => (string) ($row['email'] ?? ''),
            'phone'        => (string) ($row['phone'] ?? ''),
            'address'      => (string) ($row['address'] ?? ''),
            'city'         => (string) ($row['city'] ?? ''),
            'state'        => (string) ($row['state'] ?? ''),
            'customerType' => (string) ($row['customer_type'] ?? 'buyer'),
            'status'       => (string) ($row['status'] ?? 'active'),
            'source'       => (string) ($row['source'] ?? 'manual'),
            'notes'        => (string) ($row['notes'] ?? ''),
            'createdAt'    => (string) ($row['created_at'] ?? ''),
            'updatedAt'    => (string) ($row['updated_at'] ?? ''),
        ];
    }

    /**
     * @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    private static function sanitize(array $input): array
    {
        $type = strtolower(trim((string) ($input['customerType'] ?? $input['customer_type'] ?? 'buyer')));
        $status = strtolower(trim((string) ($input['status'] ?? 'active')));

        return [
            'name'          => self::clip((string) ($input['name'] ?? ''), 150),
            'email'         => self::clip(strtolower(trim((string) ($input['email'] ?? ''))), 190) ?: null,
            'phone'         => self::clip((string) ($input['phone'] ?? ''), 40) ?: null,
            'address'       => self::clip((string) ($input['address'] ?? ''), 255) ?: null,
            'city'          => self::clip((string) ($input['city'] ?? ''), 100) ?: null,
            'state'         => self::clip((string) ($input['state'] ?? ''), 100) ?: null,
            'customer_type' => in_array($type, self::TYPES, true) ? $type : 'buyer',
            'status'        => in_array($status, self::STATUSES, true) ? $status : 'active',
            'source'        => self::clip((string) ($input['source'] ?? 'manual'), 50) ?: 'manual',
            'notes'         => self::clip((string) ($input['notes'] ?? ''), 5000) ?: null,
        ];
    }

    private static function clip(string $value, int $max): string
    {
        $value = trim($value);
        return strlen($value) <= $max ? $value : substr($value, 0, $max);
    }
}
