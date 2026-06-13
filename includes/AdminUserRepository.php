<?php
/**
 * Admin user CRUD with roles and permissions.
 */
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/database.php';
require_once __DIR__ . '/AdminPermissions.php';
require_once __DIR__ . '/AuthSecurity.php';

final class AdminUserRepository
{
    public static function ensureSchema(): void
    {
        static $done = false;
        if ($done) {
            return;
        }

        $pdo = getDatabaseConnection();
        $alters = [
            "ALTER TABLE admin_users ADD COLUMN role VARCHAR(32) NOT NULL DEFAULT 'administrator'",
            "ALTER TABLE admin_users ADD COLUMN permissions_json TEXT DEFAULT NULL",
            "ALTER TABLE admin_users ADD COLUMN last_login_at DATETIME DEFAULT NULL",
            "ALTER TABLE admin_users ADD COLUMN created_by INT UNSIGNED DEFAULT NULL",
            "ALTER TABLE admin_users ADD COLUMN suspended_at DATETIME DEFAULT NULL",
            "ALTER TABLE admin_users ADD COLUMN suspended_by INT UNSIGNED DEFAULT NULL",
        ];

        foreach ($alters as $sql) {
            try {
                $pdo->exec($sql);
            } catch (PDOException $e) {
                if (!str_contains($e->getMessage(), 'Duplicate column')) {
                    // ignore
                }
            }
        }

        $first = $pdo->query('SELECT MIN(id) AS id FROM admin_users')->fetch();
        if ($first && !empty($first['id'])) {
            $pdo->prepare("UPDATE admin_users SET role = 'super_admin' WHERE id = :id AND role IN ('', 'administrator')")
                ->execute(['id' => (int) $first['id']]);
        }

        try {
            $pdo->exec('ALTER TABLE admin_audit_log MODIFY COLUMN event_type VARCHAR(64) NOT NULL');
        } catch (PDOException $e) {
            // ignore
        }

        $done = true;
    }

    /** @return list<array<string, mixed>> */
    public static function listAll(): array
    {
        self::ensureSchema();
        $pdo = getDatabaseConnection();
        $stmt = $pdo->query(
            'SELECT id, email, full_name, role, permissions_json, is_active,
                    last_login_at, created_at, updated_at, created_by, suspended_at
             FROM admin_users ORDER BY created_at ASC'
        );

        return array_map([self::class, 'format'], $stmt ? $stmt->fetchAll() : []);
    }

    /** @return array<string, mixed>|null */
    public static function getById(int $id): ?array
    {
        self::ensureSchema();
        $pdo = getDatabaseConnection();
        $stmt = $pdo->prepare(
            'SELECT id, email, full_name, role, permissions_json, is_active, password_hash,
                    last_login_at, created_at, updated_at, created_by, suspended_at, suspended_by
             FROM admin_users WHERE id = :id LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row ? self::format($row, true) : null;
    }

    /**
     * @param array{email:string,full_name:string,password:string,role:string,permissions?:list<string>,use_custom_permissions?:bool} $data
     */
    public static function create(array $data, int $createdBy): int
    {
        self::ensureSchema();

        $email = mb_strtolower(trim($data['email'] ?? ''));
        $name = trim($data['full_name'] ?? '');
        $password = (string) ($data['password'] ?? '');
        $role = AdminPermissions::sanitizeRole((string) ($data['role'] ?? 'viewer'));

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Valid email is required.');
        }
        if ($name === '') {
            throw new InvalidArgumentException('Full name is required.');
        }
        if (strlen($password) < 8) {
            throw new InvalidArgumentException('Password must be at least 8 characters.');
        }

        $pdo = getDatabaseConnection();
        $dup = $pdo->prepare('SELECT id FROM admin_users WHERE email = :email LIMIT 1');
        $dup->execute(['email' => $email]);
        if ($dup->fetch()) {
            throw new InvalidArgumentException('An admin with this email already exists.');
        }

        $permsJson = self::buildPermissionsJson($data, $role);

        $stmt = $pdo->prepare(
            'INSERT INTO admin_users (email, password_hash, full_name, role, permissions_json, is_active, created_by)
             VALUES (:email, :hash, :name, :role, :perms, 1, :created_by)'
        );
        $stmt->execute([
            'email'       => $email,
            'hash'        => password_hash($password, PASSWORD_DEFAULT),
            'name'        => $name,
            'role'        => $role,
            'perms'       => $permsJson,
            'created_by'  => $createdBy > 0 ? $createdBy : null,
        ]);

        $id = (int) $pdo->lastInsertId();
        AuthSecurity::auditLog('admin_created', $createdBy, 'Created admin user #' . $id . ' (' . $email . ')');

        return $id;
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function update(int $id, array $data, int $actorId): void
    {
        self::ensureSchema();
        $existing = self::getById($id);
        if (!$existing) {
            throw new InvalidArgumentException('Admin user not found.');
        }

        $email = mb_strtolower(trim((string) ($data['email'] ?? $existing['email'])));
        $name = trim((string) ($data['full_name'] ?? $existing['full_name']));
        $role = AdminPermissions::sanitizeRole((string) ($data['role'] ?? $existing['role']));

        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $name === '') {
            throw new InvalidArgumentException('Valid name and email are required.');
        }

        $pdo = getDatabaseConnection();
        $dup = $pdo->prepare('SELECT id FROM admin_users WHERE email = :email AND id != :id LIMIT 1');
        $dup->execute(['email' => $email, 'id' => $id]);
        if ($dup->fetch()) {
            throw new InvalidArgumentException('Email already in use.');
        }

        self::guardSuperAdmin($existing, $role, $actorId, $id);

        $permsJson = self::buildPermissionsJson($data, $role);
        $params = [
            'email' => $email,
            'name'  => $name,
            'role'  => $role,
            'perms' => $permsJson,
            'id'    => $id,
        ];

        $sql = 'UPDATE admin_users SET email = :email, full_name = :name, role = :role, permissions_json = :perms';

        if (!empty($data['password'])) {
            $password = (string) $data['password'];
            if (strlen($password) < 8) {
                throw new InvalidArgumentException('Password must be at least 8 characters.');
            }
            $sql .= ', password_hash = :hash';
            $params['hash'] = password_hash($password, PASSWORD_DEFAULT);
        }

        $sql .= ' WHERE id = :id';
        $pdo->prepare($sql)->execute($params);

        AuthSecurity::auditLog('admin_updated', $actorId, 'Updated admin user #' . $id);
    }

    public static function suspend(int $id, int $actorId): void
    {
        self::ensureSchema();
        if ($id === $actorId) {
            throw new InvalidArgumentException('You cannot suspend your own account.');
        }

        $existing = self::getById($id);
        if (!$existing) {
            throw new InvalidArgumentException('Admin user not found.');
        }

        self::guardLastSuperAdmin($id, (string) $existing['role']);

        $pdo = getDatabaseConnection();
        $stmt = $pdo->prepare(
            'UPDATE admin_users SET is_active = 0, suspended_at = NOW(), suspended_by = :by WHERE id = :id'
        );
        $stmt->execute(['id' => $id, 'by' => $actorId]);

        AuthSecurity::auditLog('admin_suspended', $actorId, 'Suspended admin #' . $id);
    }

    public static function reactivate(int $id, int $actorId): void
    {
        self::ensureSchema();
        $pdo = getDatabaseConnection();
        $stmt = $pdo->prepare(
            'UPDATE admin_users SET is_active = 1, suspended_at = NULL, suspended_by = NULL WHERE id = :id'
        );
        $stmt->execute(['id' => $id]);

        AuthSecurity::auditLog('admin_reactivated', $actorId, 'Reactivated admin #' . $id);
    }

    public static function delete(int $id, int $actorId): void
    {
        self::ensureSchema();
        if ($id === $actorId) {
            throw new InvalidArgumentException('You cannot delete your own account.');
        }

        $existing = self::getById($id);
        if (!$existing) {
            throw new InvalidArgumentException('Admin user not found.');
        }

        self::guardLastSuperAdmin($id, (string) $existing['role']);

        $pdo = getDatabaseConnection();
        $activeCount = (int) $pdo->query('SELECT COUNT(*) FROM admin_users WHERE is_active = 1')->fetchColumn();
        if ($activeCount <= 1 && (int) $existing['is_active'] === 1) {
            throw new InvalidArgumentException('Cannot delete the only active admin account.');
        }

        $pdo->prepare('DELETE FROM admin_users WHERE id = :id')->execute(['id' => $id]);
        AuthSecurity::auditLog('admin_deleted', $actorId, 'Deleted admin #' . $id);
    }

    public static function recordLogin(int $adminId): void
    {
        self::ensureSchema();
        $pdo = getDatabaseConnection();
        $pdo->prepare('UPDATE admin_users SET last_login_at = NOW() WHERE id = :id')->execute(['id' => $adminId]);
    }

    /** @param array<string, mixed> $row */
    private static function format(array $row, bool $internal = false): array
    {
        $perms = AdminPermissions::resolveForUser(
            (string) ($row['role'] ?? 'viewer'),
            isset($row['permissions_json']) ? (string) $row['permissions_json'] : null
        );

        $out = [
            'id'           => (int) $row['id'],
            'email'        => (string) $row['email'],
            'full_name'    => (string) ($row['full_name'] ?? ''),
            'role'         => (string) ($row['role'] ?? 'viewer'),
            'role_label'   => AdminPermissions::roles()[(string) ($row['role'] ?? 'viewer')] ?? 'Viewer',
            'permissions'  => $perms,
            'is_active'    => (int) ($row['is_active'] ?? 0),
            'status'       => (int) ($row['is_active'] ?? 0) ? 'active' : 'suspended',
            'last_login_at'=> $row['last_login_at'] ?? null,
            'created_at'   => $row['created_at'] ?? null,
            'updated_at'   => $row['updated_at'] ?? null,
            'suspended_at' => $row['suspended_at'] ?? null,
            'has_custom_permissions' => !empty($row['permissions_json']),
        ];

        if ($internal && isset($row['password_hash'])) {
            $out['password_hash'] = (string) $row['password_hash'];
        }

        return $out;
    }

    /** @param array<string, mixed> $data */
    private static function buildPermissionsJson(array $data, string $role): ?string
    {
        if (empty($data['use_custom_permissions'])) {
            return null;
        }

        $perms = AdminPermissions::filterValid(
            is_array($data['permissions'] ?? null) ? $data['permissions'] : []
        );

        if ($perms === []) {
            return null;
        }

        return json_encode($perms, JSON_UNESCAPED_UNICODE);
    }

    /** @param array<string, mixed> $existing */
    private static function guardSuperAdmin(array $existing, string $newRole, int $actorId, int $targetId): void
    {
        if ((string) $existing['role'] === 'super_admin' && $newRole !== 'super_admin' && $actorId === $targetId) {
            throw new InvalidArgumentException('You cannot downgrade your own super admin role.');
        }
    }

    private static function guardLastSuperAdmin(int $id, string $role): void
    {
        if ($role !== 'super_admin') {
            return;
        }

        $pdo = getDatabaseConnection();
        $count = (int) $pdo->query(
            "SELECT COUNT(*) FROM admin_users WHERE role = 'super_admin' AND is_active = 1"
        )->fetchColumn();

        if ($count <= 1) {
            throw new InvalidArgumentException('Cannot remove or suspend the only super admin.');
        }
    }
}
