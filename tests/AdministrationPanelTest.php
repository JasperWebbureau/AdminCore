<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

use Flexgrid\Modules\AdminCore\Integration\Flexgrid\Service\AdministrationPanelService;

final class AdministrationPanelControllerStub
{
    private $name;
    private $icon;
    private $meta;
    private $accessible;

    public function __construct(string $name, string $icon, array $meta, bool $accessible = true)
    {
        $this->name = $name;
        $this->icon = $icon;
        $this->meta = $meta;
        $this->accessible = $accessible;
    }

    public function getName(): string { return $this->name; }
    public function getIcon(): string { return $this->icon; }
    public function getRawMeta($key = null) { return $key === null ? $this->meta : ($this->meta[$key] ?? null); }
    public function canAccess(bool $allowIfAuthUnavailable = false): bool { return $this->accessible; }
}

$service = new AdministrationPanelService([
    new AdministrationPanelControllerStub('AdminInvoice', 'fas fa-file-invoice', [
        'administrationPanel' => true,
        'administrationLabel' => 'Facturen',
        'administrationRoute' => 'invoices',
        'administrationPriority' => '40',
    ]),
    new AdministrationPanelControllerStub('AdminDashboard', 'fas fa-chart-line', [
        'administrationPanel' => true,
        'administrationLabel' => 'Overzicht',
        'administrationRoute' => 'dashboard',
        'administrationPriority' => '10',
    ]),
    new AdministrationPanelControllerStub('AdminSecret', 'fas fa-lock', [
        'administrationPanel' => true,
        'administrationLabel' => 'Verborgen',
        'administrationRoute' => 'index',
    ], false),
    new AdministrationPanelControllerStub('Unrelated', 'fas fa-cube', []),
    new AdministrationPanelControllerStub('Unsafe', 'fas fa-cube', [
        'administrationPanel' => true,
        'administrationLabel' => 'Onveilig',
        'administrationRoute' => '../outside',
    ]),
]);
$links = $service->getLinks();

adminCoreAssert(count($links) === 2, 'Alleen actieve, toegankelijke en geldige administratiemodules mogen zichtbaar zijn.');
adminCoreAssert($links[0]['label'] === 'Overzicht' && $links[1]['label'] === 'Facturen', 'Administratielinks moeten stabiel op prioriteit staan.');
adminCoreAssert($links[1]['path'] === '/Flexgrid/AdminInvoice/invoices', 'De route moet uitsluitend uit gevalideerde controllermetadata worden opgebouwd.');

$modulesRoot = dirname(__DIR__, 2);
$panelAnnotations = [];
$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($modulesRoot, FilesystemIterator::SKIP_DOTS));
foreach ($iterator as $file) {
    if (!$file->isFile() || substr($file->getFilename(), -4) !== '.php' || strpos(str_replace('\\', '/', $file->getPathname()), '/src/Controller/') === false && strpos(str_replace('\\', '/', $file->getPathname()), '/src/Integration/Flexgrid/Controller/') === false) {
        continue;
    }
    $content = (string)file_get_contents($file->getPathname());
    if (strpos($content, 'Flexgridpanel' . '=true') !== false) { $panelAnnotations[] = str_replace('\\', '/', $file->getPathname()); }
}
adminCoreAssert(count($panelAnnotations) === 1 && strpos($panelAnnotations[0], '/AdminCore/') !== false, 'Administratiemodules moeten exact één gezamenlijk Core-dashboardpaneel registreren.');

$template = (string)file_get_contents($modulesRoot . '/AdminCore/src/Integration/Flexgrid/Templates/AdministrationPanel.php');
$style = (string)file_get_contents(dirname($modulesRoot) . '/Flexgrid/src/Html/Admin/Css/DashboardPanels.scss');
adminCoreAssert(strpos($template, 'foreach ($links as $link)') !== false && strpos($template, 'AdminInvoice') === false, 'Het Core-paneel moet dynamisch renderen zonder modulelinks hard te coderen.');
adminCoreAssert(strpos($template, 'dashboard-panel__link') !== false, 'Het Core-paneel moet de gedeelde dashboardpanel-stijl gebruiken.');
adminCoreAssert(preg_match('/#[0-9a-fA-F]{3,8}\b/', $style) !== 1, 'Het Core-paneel mag geen losse kleurwaarden bevatten.');
adminCoreAssert(strpos($style, 'var(--admin-color-') !== false, 'Het Core-paneel moet gedeelde Admin UI-kleurvariabelen gebruiken.');

echo "AdminCore administration panel tests passed.\n";
