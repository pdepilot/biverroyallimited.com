<?php
declare(strict_types=1);

require_once __DIR__ . '/PropertyRepository.php';
require_once __DIR__ . '/ContactRepository.php';
require_once __DIR__ . '/TestimonialRepository.php';

final class AdminAnalyticsService
{
    /** @return array<string, mixed> */
    public static function getReport(): array
    {
        $properties   = PropertyRepository::getAdminStats();
        $submissions  = PropertyRepository::getPublicSubmissionStats();
        $contacts     = ContactRepository::getStats();
        $testimonials = TestimonialRepository::getStats();

        return [
            'kpis' => [
                'properties'   => (int) ($properties['total'] ?? 0),
                'submissions'  => (int) ($submissions['total'] ?? 0),
                'testimonials' => (int) ($testimonials['published'] ?? 0),
                'inquiries'    => (int) ($contacts['total'] ?? 0),
            ],
            'trends' => [
                'properties'   => self::monthOverMonthTrend('properties'),
                'submissions'  => self::monthOverMonthTrend('properties', "source = 'public'"),
                'testimonials' => self::monthOverMonthTrend('testimonials'),
                'inquiries'    => self::monthOverMonthTrend('contact_inquiries'),
            ],
            'monthlyListings'  => self::fillLastMonths(PropertyRepository::getMonthlyListingCounts(6), 6),
            'monthlyInquiries' => self::fillLastMonths(ContactRepository::getMonthlyInquiryCounts(6), 6),
            'propertyTypes'    => [
                'sale' => (int) ($properties['forSale'] ?? 0),
                'rent' => (int) ($properties['forRent'] ?? 0),
            ],
            'ratingDistribution' => array_values(TestimonialRepository::getRatingDistribution()),
            'recentProperties'     => array_map(static function (array $p): array {
                return [
                    'title'     => (string) ($p['title'] ?? ''),
                    'price'     => (int) ($p['price'] ?? 0),
                    'type'      => (string) ($p['type'] ?? 'sale'),
                    'createdAt' => (string) ($p['createdAt'] ?? $p['created_at'] ?? ''),
                ];
            }, PropertyRepository::getRecent(5)),
        ];
    }

    /** @return array{value:int,direction:string,label:string} */
    private static function monthOverMonthTrend(string $table, string $extraWhere = ''): array
    {
        $pdo = getDatabaseConnection();
        $allowed = ['properties', 'testimonials', 'contact_inquiries'];
        if (!in_array($table, $allowed, true)) {
            return ['value' => 0, 'direction' => 'neutral', 'label' => 'vs last month'];
        }

        $where = $extraWhere !== '' ? " AND {$extraWhere}" : '';
        $stmt = $pdo->query(
            "SELECT
                SUM(YEAR(created_at) = YEAR(CURDATE()) AND MONTH(created_at) = MONTH(CURDATE()){$where}) AS current_month,
                SUM(YEAR(created_at) = YEAR(DATE_SUB(CURDATE(), INTERVAL 1 MONTH)) AND MONTH(created_at) = MONTH(DATE_SUB(CURDATE(), INTERVAL 1 MONTH)){$where}) AS previous_month
             FROM {$table}"
        );
        $row = $stmt->fetch() ?: [];
        $current  = (int) ($row['current_month'] ?? 0);
        $previous = (int) ($row['previous_month'] ?? 0);

        if ($previous === 0) {
            $pct = $current > 0 ? 100 : 0;
        } else {
            $pct = (int) round((($current - $previous) / $previous) * 100);
        }

        $direction = $pct > 0 ? 'up' : ($pct < 0 ? 'down' : 'neutral');

        return [
            'value'     => abs($pct),
            'direction' => $direction,
            'label'     => 'vs last month',
        ];
    }

    /**
     * @param list<array{label:string,count:int}> $rows
     * @return list<array{label:string,count:int}>
     */
    private static function fillLastMonths(array $rows, int $months): array
    {
        $map = [];
        foreach ($rows as $row) {
            $label = (string) $row['label'];
            $key = strlen($label) > 3 ? substr($label, 0, 3) : $label;
            $map[$key] = ($map[$key] ?? 0) + (int) $row['count'];
        }

        $result = [];
        $now = new DateTimeImmutable('first day of this month');
        for ($i = $months - 1; $i >= 0; $i--) {
            $date = $now->modify("-{$i} months");
            $label = $date->format('M');
            $result[] = [
                'label' => $label,
                'count' => $map[$label] ?? 0,
            ];
        }

        return $result;
    }
}
