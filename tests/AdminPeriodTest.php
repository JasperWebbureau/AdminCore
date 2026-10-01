<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';
require_once dirname(__DIR__) . '/src/Service/AdminPeriod.php';

use Flexgrid\Modules\AdminCore\Service\AdminPeriod;

$previous = $_SESSION['flexgrid_admin_period'] ?? null;
try {
    unset($_SESSION['flexgrid_admin_period']);
    $default = AdminPeriod::selected();
    adminCoreAssert($default['year'] === (int)date('Y') && $default['quarter'] >= 1 && $default['quarter'] <= 4, 'Periode moet een geldig sessiestandaard hebben.');
    AdminPeriod::select(2024, 2);
    adminCoreAssert(AdminPeriod::selected() === ['year' => 2024, 'quarter' => 2], 'Periode moet jaar en kwartaal in de sessie bewaren.');
    adminCoreAssertThrows(InvalidArgumentException::class, function (): void { AdminPeriod::select(2024, 5); }, 'Ongeldig kwartaal moet worden geweigerd.');
    adminCoreAssert(AdminPeriod::selected() === ['year' => 2024, 'quarter' => 2], 'Ongeldige selectie mag de vorige keuze niet vervangen.');
    AdminPeriod::select(2024, null);
    adminCoreAssert(AdminPeriod::selected() === ['year' => 2024, 'quarter' => null], 'Alles bij kwartaal moet een volledig jaar selecteren.');
    AdminPeriod::select(null, null);
    adminCoreAssert(AdminPeriod::selected() === ['year' => null, 'quarter' => null], 'Alles bij jaar moet alle jaren tonen.');
    adminCoreAssert(AdminPeriod::selectedForReport() === ['year' => 2024, 'quarter' => null], 'Rapportage moet het laatst gekozen concrete jaar behouden.');
    adminCoreAssertThrows(InvalidArgumentException::class, function (): void { AdminPeriod::select(null, 3); }, 'Een specifiek kwartaal zonder jaar moet worden geweigerd.');
} finally {
    if ($previous === null) { unset($_SESSION['flexgrid_admin_period']); }
    else { $_SESSION['flexgrid_admin_period'] = $previous; }
}

echo "AdminCore period tests passed.\n";
