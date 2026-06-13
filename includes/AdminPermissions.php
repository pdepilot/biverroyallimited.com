<?php
/**
 * Admin roles and permission checks.
 */
declare(strict_types=1);

require_once __DIR__ . '/AuthSecurity.php';

final class AdminPermissions
{
    public const PERM_DASHBOARD   = 'dashboard.view';
    public const PERM_PROPERTIES  = 'properties.manage';
    public const PERM_ANALYTICS   = 'analytics.view';
    public const PERM_TESTIMONIALS = 'testimonials.manage';
    public const PERM_LOCATIONS   = 'locations.manage';
    public const PERM_LISTINGS    = 'listings.manage';
    public const PERM_CONTACTS    = 'contacts.manage';
    public const PERM_EMAIL       = 'email.manage';
    public const PERM_SMTP        = 'smtp.manage';
    public const PERM_SUBSCRIBERS = 'subscribers.manage';
    public const PERM_CHATBOT     = 'chatbot.manage';
    public const PERM_PROMO       = 'promo.manage';
    public const PERM_SETTINGS    = 'settings.manage';
    public const PERM_ADMINS      = 'admins.manage';

    /** @return array<string, string> */
    public static function allPermissions(): array
    {
        return [
            self::PERM_DASHBOARD    => 'View Dashboard',
            self::PERM_PROPERTIES   => 'Manage Properties',
            self::PERM_ANALYTICS    => 'View Analytics',
            self::PERM_TESTIMONIALS => 'Manage Testimonials',
            self::PERM_LOCATIONS    => 'Manage Service Areas',
            self::PERM_LISTINGS     => 'Manage List Submissions',
            self::PERM_CONTACTS     => 'Manage Inquiries',
            self::PERM_EMAIL        => 'Email Center',
            self::PERM_SMTP         => 'SMTP Settings',
            self::PERM_SUBSCRIBERS  => 'Manage Subscribers',
            self::PERM_CHATBOT      => 'Live Chat & Leads',
            self::PERM_PROMO        => 'Promo Banner',
            self::PERM_SETTINGS     => 'Site Settings',
            self::PERM_ADMINS       => 'Manage Admin Users',
        ];
    }

    /** @return array<string, string> */
    public static function roles(): array
    {
        return [
            'super_admin'   => 'Super Admin',
            'administrator' => 'Administrator',
            'manager'       => 'Manager',
            'editor'        => 'Editor',
            'viewer'        => 'Viewer (Read-only)',
        ];
    }

    /** @return list<string> */
    public static function permissionsForRole(string $role): array
    {
        $map = [
            'super_admin' => array_keys(self::allPermissions()),
            'administrator' => [
                self::PERM_DASHBOARD, self::PERM_PROPERTIES, self::PERM_ANALYTICS,
                self::PERM_TESTIMONIALS, self::PERM_LOCATIONS, self::PERM_LISTINGS,
                self::PERM_CONTACTS, self::PERM_EMAIL, self::PERM_SMTP,
                self::PERM_SUBSCRIBERS, self::PERM_CHATBOT, self::PERM_PROMO,
                self::PERM_SETTINGS,
            ],
            'manager' => [
                self::PERM_DASHBOARD, self::PERM_PROPERTIES, self::PERM_LISTINGS,
                self::PERM_CONTACTS, self::PERM_EMAIL, self::PERM_SUBSCRIBERS,
            ],
            'editor' => [
                self::PERM_DASHBOARD, self::PERM_TESTIMONIALS,
                self::PERM_LOCATIONS, self::PERM_PROMO,
            ],
            'viewer' => [
                self::PERM_DASHBOARD, self::PERM_ANALYTICS,
            ],
        ];

        return $map[$role] ?? $map['viewer'];
    }

    /** @return list<string> */
    public static function resolveForUser(?string $role, ?string $permissionsJson): array
    {
        $role = self::sanitizeRole($role ?? 'viewer');

        if ($permissionsJson !== null && $permissionsJson !== '') {
            $decoded = json_decode($permissionsJson, true);
            if (is_array($decoded) && $decoded !== []) {
                return self::filterValid(array_map('strval', $decoded));
            }
        }

        return self::permissionsForRole($role);
    }

    public static function sanitizeRole(string $role): string
    {
        return array_key_exists($role, self::roles()) ? $role : 'viewer';
    }

    /** @param list<string> $perms */
    public static function filterValid(array $perms): array
    {
        $valid = array_keys(self::allPermissions());

        return array_values(array_unique(array_filter(
            $perms,
            static fn (string $p): bool => in_array($p, $valid, true)
        )));
    }

    public static function loadSessionPermissions(int $adminId): void
    {
        AuthSecurity::initSession();
        if (!class_exists('AdminUserRepository', false)) {
            require_once __DIR__ . '/AdminUserRepository.php';
        }

        $admin = AdminUserRepository::getById($adminId);
        if (!$admin) {
            $_SESSION['admin_role'] = 'viewer';
            $_SESSION['admin_permissions'] = [];
            return;
        }

        $_SESSION['admin_role'] = (string) ($admin['role'] ?? 'viewer');
        $_SESSION['admin_permissions'] = self::resolveForUser(
            (string) ($admin['role'] ?? 'viewer'),
            isset($admin['permissions_json']) ? (string) $admin['permissions_json'] : null
        );
    }

    public static function has(string $permission): bool
    {
        AuthSecurity::initSession();
        $perms = $_SESSION['admin_permissions'] ?? [];

        return in_array($permission, $perms, true);
    }

    public static function require(string $permission): void
    {
        if (!self::has($permission)) {
            if (self::isApiRequest()) {
                header('Content-Type: application/json');
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'You do not have permission to perform this action.']);
                exit;
            }

            header('Location: admin-dashboard.php?error=forbidden');
            exit;
        }
    }

    /** @return array<string, string> */
    public static function navPermissionMap(): array
    {
        return [
            'dashboard'    => self::PERM_DASHBOARD,
            'properties'   => self::PERM_PROPERTIES,
            'analytics'    => self::PERM_ANALYTICS,
            'testimonials' => self::PERM_TESTIMONIALS,
            'locations'    => self::PERM_LOCATIONS,
            'listings'     => self::PERM_LISTINGS,
            'contacts'     => self::PERM_CONTACTS,
            'email'        => self::PERM_EMAIL,
            'smtp'         => self::PERM_SMTP,
            'subscribers'  => self::PERM_SUBSCRIBERS,
            'chatbot'      => self::PERM_CHATBOT,
            'promo'        => self::PERM_PROMO,
            'settings'     => self::PERM_SETTINGS,
            'admins'       => self::PERM_ADMINS,
        ];
    }

    public static function canAccessNav(string $navKey): bool
    {
        $map = self::navPermissionMap();
        if (!isset($map[$navKey])) {
            return true;
        }

        return self::has($map[$navKey]);
    }

    private static function isApiRequest(): bool
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '';

        return str_contains($uri, '/api/');
    }
}
