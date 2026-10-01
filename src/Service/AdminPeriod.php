<?php

declare(strict_types=1);

namespace Flexgrid\Modules\AdminCore\Service;

/** One administration-wide period, persisted for the current browser session. */
final class AdminPeriod
{
    private const SESSION_KEY = 'flexgrid_admin_period';

    public static function selected(): array
    {
        $stored = $_SESSION[self::SESSION_KEY] ?? [];
        if (!is_array($stored)) {
            $stored = [];
        }
        $year = array_key_exists('year', $stored)
            ? ($stored['year'] === null ? null : (int)$stored['year'])
            : (int)date('Y');
        $quarter = array_key_exists('quarter', $stored)
            ? ($stored['quarter'] === null ? null : (int)$stored['quarter'])
            : (int)ceil((int)date('n') / 3);
        return [
            'year' => $year === null || ($year >= 2000 && $year <= 2200) ? $year : (int)date('Y'),
            'quarter' => $year === null ? null : ($quarter === null || ($quarter >= 1 && $quarter <= 4) ? $quarter : (int)ceil((int)date('n') / 3)),
        ];
    }

    public static function selectedForReport(): array
    {
        $selected = self::selected();
        if ($selected['year'] !== null) {
            return $selected;
        }
        $stored = $_SESSION[self::SESSION_KEY] ?? [];
        $lastYear = is_array($stored) ? (int)($stored['last_year'] ?? date('Y')) : (int)date('Y');
        return ['year' => $lastYear >= 2000 && $lastYear <= 2200 ? $lastYear : (int)date('Y'), 'quarter' => null];
    }

    public static function select(?int $year, ?int $quarter): void
    {
        if (($year !== null && ($year < 2000 || $year > 2200))
            || ($quarter !== null && ($quarter < 1 || $quarter > 4 || $year === null))) {
            throw new \InvalidArgumentException('Ongeldig jaar of kwartaal.');
        }
        $reportYear = self::selectedForReport()['year'];
        $_SESSION[self::SESSION_KEY] = [
            'year' => $year,
            'quarter' => $quarter,
            'last_year' => $year ?? $reportYear,
        ];
    }
}
