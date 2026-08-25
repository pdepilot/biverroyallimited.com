<?php
/**
 * Database operations for property transactions (receipts & certificates).
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/database.php';

final class TransactionRepository
{
    public const TYPES = ['purchase', 'rent', 'sale'];
    public const PAYMENT_STATUSES = ['paid', 'part', 'pending'];

    private static bool $schemaReady = false;

    public static function ensureSchema(): void
    {
        if (self::$schemaReady) {
            return;
        }

        $pdo = getDatabaseConnection();
        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS transactions (
                id INT AUTO_INCREMENT PRIMARY KEY,
                reference VARCHAR(40) NOT NULL UNIQUE,
                customer_id INT DEFAULT NULL,
                customer_name VARCHAR(150) NOT NULL,
                customer_email VARCHAR(190) DEFAULT NULL,
                customer_phone VARCHAR(40) DEFAULT NULL,
                customer_address VARCHAR(255) DEFAULT NULL,
                property_id INT DEFAULT NULL,
                property_title VARCHAR(200) DEFAULT NULL,
                property_location VARCHAR(200) DEFAULT NULL,
                transaction_type VARCHAR(20) NOT NULL DEFAULT "purchase",
                amount BIGINT NOT NULL DEFAULT 0,
                amount_paid BIGINT NOT NULL DEFAULT 0,
                currency VARCHAR(5) NOT NULL DEFAULT "NGN",
                payment_method VARCHAR(50) DEFAULT NULL,
                payment_status VARCHAR(20) NOT NULL DEFAULT "paid",
                transaction_date DATE NOT NULL,
                description TEXT DEFAULT NULL,
                issued_by VARCHAR(150) DEFAULT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_tx_type (transaction_type),
                INDEX idx_tx_status (payment_status),
                INDEX idx_tx_customer (customer_id),
                INDEX idx_tx_date (transaction_date)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );

        self::$schemaReady = true;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function getAll(?string $search = null, ?string $type = null, ?string $status = null, int $limit = 500): array
    {
        self::ensureSchema();
        $pdo = getDatabaseConnection();

        $sql = 'SELECT * FROM transactions WHERE 1=1';
        $params = [];

        if ($search !== null && $search !== '') {
            $sql .= ' AND (reference LIKE :q OR customer_name LIKE :q OR customer_email LIKE :q OR property_title LIKE :q)';
            $params['q'] = '%' . $search . '%';
        }
        if ($type !== null && in_array($type, self::TYPES, true)) {
            $sql .= ' AND transaction_type = :type';
            $params['type'] = $type;
        }
        if ($status !== null && in_array($status, self::PAYMENT_STATUSES, true)) {
            $sql .= ' AND payment_status = :status';
            $params['status'] = $status;
        }

        $sql .= ' ORDER BY transaction_date DESC, id DESC LIMIT ' . max(1, min($limit, 1000));

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        return array_map([self::class, 'format'], $stmt->fetchAll());
    }

    public static function getById(int $id): ?array
    {
        self::ensureSchema();
        $pdo = getDatabaseConnection();
        $stmt = $pdo->prepare('SELECT * FROM transactions WHERE id = :id LIMIT 1');
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
        $data['reference'] = 'TEMP-' . bin2hex(random_bytes(8));

        $stmt = $pdo->prepare(
            'INSERT INTO transactions
                (reference, customer_id, customer_name, customer_email, customer_phone, customer_address,
                 property_id, property_title, property_location, transaction_type, amount, amount_paid,
                 currency, payment_method, payment_status, transaction_date, description, issued_by)
             VALUES
                (:reference, :customer_id, :customer_name, :customer_email, :customer_phone, :customer_address,
                 :property_id, :property_title, :property_location, :transaction_type, :amount, :amount_paid,
                 :currency, :payment_method, :payment_status, :transaction_date, :description, :issued_by)'
        );
        $stmt->execute($data);

        $id = (int) $pdo->lastInsertId();

        // Human-friendly reference now that we know the id.
        $reference = self::buildReference($id);
        $pdo->prepare('UPDATE transactions SET reference = :ref WHERE id = :id')
            ->execute(['ref' => $reference, 'id' => $id]);

        return $id;
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
            'UPDATE transactions SET
                customer_id = :customer_id,
                customer_name = :customer_name,
                customer_email = :customer_email,
                customer_phone = :customer_phone,
                customer_address = :customer_address,
                property_id = :property_id,
                property_title = :property_title,
                property_location = :property_location,
                transaction_type = :transaction_type,
                amount = :amount,
                amount_paid = :amount_paid,
                currency = :currency,
                payment_method = :payment_method,
                payment_status = :payment_status,
                transaction_date = :transaction_date,
                description = :description,
                issued_by = :issued_by
             WHERE id = :id'
        );

        return $stmt->execute($data);
    }

    public static function delete(int $id): bool
    {
        self::ensureSchema();
        $pdo = getDatabaseConnection();
        $stmt = $pdo->prepare('DELETE FROM transactions WHERE id = :id');
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
                COALESCE(SUM(amount_paid), 0) AS revenue,
                SUM(transaction_type = 'purchase') AS purchases,
                SUM(transaction_type = 'rent') AS rentals
             FROM transactions"
        )->fetch() ?: [];

        return [
            'total'     => (int) ($row['total'] ?? 0),
            'revenue'   => (int) ($row['revenue'] ?? 0),
            'purchases' => (int) ($row['purchases'] ?? 0),
            'rentals'   => (int) ($row['rentals'] ?? 0),
        ];
    }

    public static function typeLabel(string $type): string
    {
        return match ($type) {
            'purchase' => 'Property Purchase',
            'rent'     => 'Property Rental',
            'sale'     => 'Property Sale',
            default    => 'Transaction',
        };
    }

    public static function certificateTitle(string $type): string
    {
        return match ($type) {
            'purchase' => 'Certificate of Ownership',
            'rent'     => 'Certificate of Tenancy',
            'sale'     => 'Certificate of Sale',
            default    => 'Certificate of Transaction',
        };
    }

    private static function buildReference(int $id): string
    {
        return 'BRH-' . date('Y') . '-' . str_pad((string) $id, 5, '0', STR_PAD_LEFT);
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private static function format(array $row): array
    {
        return [
            'id'               => (int) $row['id'],
            'reference'        => (string) $row['reference'],
            'customerId'       => $row['customer_id'] !== null ? (int) $row['customer_id'] : null,
            'customerName'     => (string) $row['customer_name'],
            'customerEmail'    => (string) ($row['customer_email'] ?? ''),
            'customerPhone'    => (string) ($row['customer_phone'] ?? ''),
            'customerAddress'  => (string) ($row['customer_address'] ?? ''),
            'propertyId'       => $row['property_id'] !== null ? (int) $row['property_id'] : null,
            'propertyTitle'    => (string) ($row['property_title'] ?? ''),
            'propertyLocation' => (string) ($row['property_location'] ?? ''),
            'transactionType'  => (string) ($row['transaction_type'] ?? 'purchase'),
            'amount'           => (int) ($row['amount'] ?? 0),
            'amountPaid'       => (int) ($row['amount_paid'] ?? 0),
            'currency'         => (string) ($row['currency'] ?? 'NGN'),
            'paymentMethod'    => (string) ($row['payment_method'] ?? ''),
            'paymentStatus'    => (string) ($row['payment_status'] ?? 'paid'),
            'transactionDate'  => (string) ($row['transaction_date'] ?? ''),
            'description'      => (string) ($row['description'] ?? ''),
            'issuedBy'         => (string) ($row['issued_by'] ?? ''),
            'createdAt'        => (string) ($row['created_at'] ?? ''),
            'updatedAt'        => (string) ($row['updated_at'] ?? ''),
        ];
    }

    /**
     * @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    private static function sanitize(array $input): array
    {
        $type = strtolower(trim((string) ($input['transactionType'] ?? $input['transaction_type'] ?? 'purchase')));
        $status = strtolower(trim((string) ($input['paymentStatus'] ?? $input['payment_status'] ?? 'paid')));
        $date = trim((string) ($input['transactionDate'] ?? $input['transaction_date'] ?? ''));
        if ($date === '' || strtotime($date) === false) {
            $date = date('Y-m-d');
        } else {
            $date = date('Y-m-d', (int) strtotime($date));
        }

        $amount = self::parseAmount((string) ($input['amount'] ?? '0'));
        $amountPaid = array_key_exists('amountPaid', $input) || array_key_exists('amount_paid', $input)
            ? self::parseAmount((string) ($input['amountPaid'] ?? $input['amount_paid'] ?? '0'))
            : $amount;

        $customerId = (int) ($input['customerId'] ?? $input['customer_id'] ?? 0);
        $propertyId = (int) ($input['propertyId'] ?? $input['property_id'] ?? 0);

        return [
            'customer_id'       => $customerId > 0 ? $customerId : null,
            'customer_name'     => self::clip((string) ($input['customerName'] ?? ''), 150),
            'customer_email'    => self::clip(strtolower(trim((string) ($input['customerEmail'] ?? ''))), 190) ?: null,
            'customer_phone'    => self::clip((string) ($input['customerPhone'] ?? ''), 40) ?: null,
            'customer_address'  => self::clip((string) ($input['customerAddress'] ?? ''), 255) ?: null,
            'property_id'       => $propertyId > 0 ? $propertyId : null,
            'property_title'    => self::clip((string) ($input['propertyTitle'] ?? ''), 200) ?: null,
            'property_location' => self::clip((string) ($input['propertyLocation'] ?? ''), 200) ?: null,
            'transaction_type'  => in_array($type, self::TYPES, true) ? $type : 'purchase',
            'amount'            => $amount,
            'amount_paid'       => $amountPaid,
            'currency'          => self::clip((string) ($input['currency'] ?? 'NGN'), 5) ?: 'NGN',
            'payment_method'    => self::clip((string) ($input['paymentMethod'] ?? ''), 50) ?: null,
            'payment_status'    => in_array($status, self::PAYMENT_STATUSES, true) ? $status : 'paid',
            'transaction_date'  => $date,
            'description'       => self::clip((string) ($input['description'] ?? ''), 5000) ?: null,
            'issued_by'         => self::clip((string) ($input['issuedBy'] ?? ''), 150) ?: null,
        ];
    }

    private static function parseAmount(string $raw): int
    {
        $digits = preg_replace('/[^\d]/', '', $raw) ?? '';
        return $digits !== '' ? (int) $digits : 0;
    }

    private static function clip(string $value, int $max): string
    {
        $value = trim($value);
        return strlen($value) <= $max ? $value : substr($value, 0, $max);
    }
}
