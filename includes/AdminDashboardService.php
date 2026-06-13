<?php
declare(strict_types=1);

require_once __DIR__ . '/PropertyRepository.php';
require_once __DIR__ . '/ContactRepository.php';
require_once __DIR__ . '/BannerService.php';
require_once __DIR__ . '/SiteSettingsService.php';

final class AdminDashboardService
{
    /** @return array<string, mixed> */
    public static function getOverview(): array
    {
        $properties   = PropertyRepository::getAdminStats();
        $submissions  = PropertyRepository::getPublicSubmissionStats();
        $contacts     = ContactRepository::getStats();
        $promo        = BannerService::promoConfig();
        $promoFlier   = BannerService::promoFlierUrls($promo);

        return [
            'stats' => [
                'properties'  => $properties,
                'submissions' => $submissions,
                'contacts'    => [
                    'total'    => (int) ($contacts['total'] ?? 0),
                    'new'      => (int) ($contacts['new_count'] ?? 0),
                    'read'     => (int) ($contacts['read_count'] ?? 0),
                    'replied'  => (int) ($contacts['replied_count'] ?? 0),
                    'archived' => (int) ($contacts['archived_count'] ?? 0),
                ],
                'promo' => [
                    'enabled'  => !empty($promo['enabled']),
                    'hasFlier' => $promoFlier['hasFlier'],
                    'headline' => (string) ($promo['headline'] ?? ''),
                ],
            ],
            'recentProperties'  => PropertyRepository::getRecent(5),
            'recentSubmissions' => array_slice(
                PropertyRepository::getPublicSubmissions('pending', null, 5),
                0,
                5
            ),
            'recentContacts' => array_slice(ContactRepository::getAllInquiries(null, null), 0, 5),
            'monthlyListings' => PropertyRepository::getMonthlyListingCounts(12),
            'links' => [
                'properties'  => 'admin-property.php',
                'submissions' => 'admin-list-your-property.php',
                'contacts'    => 'admin-contact.php',
                'promo'       => 'admin-promo-banner.php',
                'settings'    => 'admin-setting.php',
            ],
        ];
    }

    /** @return array<string, mixed> */
    public static function exportAll(): array
    {
        return [
            'exportedAt'  => date('c'),
            'properties'  => PropertyRepository::getAll(500),
            'submissions' => PropertyRepository::getPublicSubmissions(null, null, 500),
            'contacts'    => ContactRepository::getAllInquiries(),
            'siteSettings'=> SiteSettingsService::get(),
            'promoBanner' => BannerService::promoConfig(),
        ];
    }
}
