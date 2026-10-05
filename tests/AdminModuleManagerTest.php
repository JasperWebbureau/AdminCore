<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';
require_once dirname(__DIR__) . '/src/Integration/Flexgrid/Service/AdminModuleState.php';
require_once dirname(__DIR__) . '/src/Integration/Flexgrid/Service/AdminModuleInterfaceVisibility.php';
require_once dirname(__DIR__) . '/src/Integration/Flexgrid/Service/AdminModuleCatalog.php';

use Flexgrid\Modules\AdminCore\Integration\Flexgrid\Service\AdminModuleCatalog;
use Flexgrid\Modules\AdminCore\Integration\Flexgrid\Service\AdminModuleState;
use Flexgrid\Modules\AdminCore\Integration\Flexgrid\Service\AdminModuleInterfaceVisibility;

$testRoot = sys_get_temp_dir() . '/admin-core-modules-' . bin2hex(random_bytes(6));
if (!defined('__ROOTDIR__')) {
    define('__ROOTDIR__', $testRoot);
}
mkdir($testRoot . '/Flexgrid/Modules/AdminExample', 0770, true);
adminCoreAssert(AdminModuleState::isEnabled('AdminExample'), 'Geïnstalleerde modules zijn standaard actief.');
AdminModuleState::setEnabled('AdminExample', false);
adminCoreAssert(!AdminModuleState::isEnabledForClass('Flexgrid\\Modules\\AdminExample\\Controller\\Example'), 'Uitschakelen moet de modulenaamruimte blokkeren.');
adminCoreAssert(AdminModuleState::isEnabledForClass('Flexgrid\\Other\\Example'), 'Andere Flexgrid-onderdelen blijven beschikbaar.');
adminCoreAssert(AdminModuleState::isEnabled('AdminCore'), 'AdminCore blijft altijd actief.');
adminCoreAssertThrows(InvalidArgumentException::class, function (): void { AdminModuleState::setEnabled('AdminCore', false); }, 'AdminCore mag niet worden uitgeschakeld.');
AdminModuleState::setEnabled('AdminExample', true);
adminCoreAssert(AdminModuleState::isEnabled('AdminExample'), 'Module moet opnieuw ingeschakeld kunnen worden.');
adminCoreAssert(AdminModuleState::moduleNameForClass('Flexgrid\\Modules\\AdminExample\\Controller\\Example') === 'AdminExample', 'De modulenaam moet uit de class volgen.');

adminCoreAssert(AdminModuleInterfaceVisibility::isVisibleForUser('AdminExample', 21), 'Interfaces zijn standaard zichtbaar.');
AdminModuleInterfaceVisibility::setHiddenForUser('AdminExample', 21, true, 1);
adminCoreAssert(!AdminModuleInterfaceVisibility::isVisibleForUser('AdminExample', 21), 'De gekozen gebruiker mag de interface niet zien.');
adminCoreAssert(AdminModuleInterfaceVisibility::isVisibleForUser('AdminExample', 22), 'Andere gebruikers behouden hun interface.');
adminCoreAssert(AdminModuleInterfaceVisibility::isVisibleForUser('AdminExample', 21, true), 'Devusers blijven toegang houden.');
AdminModuleInterfaceVisibility::setHiddenForUser('AdminExample', 21, false, 1);
adminCoreAssert(AdminModuleInterfaceVisibility::isVisibleForUser('AdminExample', 21), 'De interface moet weer zichtbaar kunnen worden.');
$history = json_decode((string)file_get_contents($testRoot . '/Files/Config/AdminModuleInterfaces.json'), true);
adminCoreAssert(count($history['history'] ?? []) === 2, 'JSON moet beide wijzigingen bewaren.');
adminCoreAssertThrows(InvalidArgumentException::class, function (): void { AdminModuleInterfaceVisibility::setHiddenForUser('AdminCore', 21, true, 1); }, 'AdminCore-interface mag niet worden verborgen.');

$catalog = new AdminModuleCatalog($testRoot . '/Flexgrid/Modules', 'ExampleAccount', '');
$modules = $catalog->getModules();
adminCoreAssert(count($modules) === 1 && $modules[0]['name'] === 'AdminExample', 'Lokale modules blijven zichtbaar zonder GitHub-toegang.');
adminCoreAssert($catalog->getWarning() !== '', 'GitHub-fout moet zichtbaar zijn op het beheerscherm.');
adminCoreAssertThrows(InvalidArgumentException::class, function () use ($catalog): void { $catalog->installOrUpdate('../Example'); }, 'Ongeldige modulenaam moet worden geweigerd.');

unlink($testRoot . '/Files/Config/AdminModules.json');
unlink($testRoot . '/Files/Config/AdminModules.json.lock');
unlink($testRoot . '/Files/Config/AdminModuleInterfaces.json');
unlink($testRoot . '/Files/Config/AdminModuleInterfaces.json.lock');
rmdir($testRoot . '/Files/Config');
rmdir($testRoot . '/Files');
rmdir($testRoot . '/Flexgrid/Modules/AdminExample');
rmdir($testRoot . '/Flexgrid/Modules');
rmdir($testRoot . '/Flexgrid');
rmdir($testRoot);

echo "AdminCore module manager tests passed.\n";
