<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

if (!defined('__DOMAIN__')) { define('__DOMAIN__', 'http://localhost/project'); }
$template = dirname(__DIR__) . '/src/Integration/Flexgrid/Templates/AdminHeader.php';
$buttons = [['label' => 'Nieuwe factuur', 'href' => '/create', 'icon' => 'fas fa-plus']];
$links = [['label' => 'Klanten', 'path' => '/Flexgrid/AdminCustomer/customers', 'icon' => 'fas fa-users']];
$period = ['year' => 2026, 'quarter' => 3];
$periodAction = 'admin-set-period';
$periodRoute = '';
ob_start(); include $template; $readonly = ob_get_clean();
adminCoreAssert(strpos($readonly, 'AdminDashboard/dashboard') !== false && strpos($readonly, 'AdminCustomer/customers') !== false, 'Header moet home en toegankelijke modulelinks tonen.');
$homeAvailable = false;
ob_start(); include $template; $withoutHome = ob_get_clean();
adminCoreAssert(strpos($withoutHome, 'AdminDashboard/dashboard') === false, 'Verborgen dashboard mag geen home-link in het adminmenu houden.');
$homeAvailable = true;
adminCoreAssert(strpos($readonly, 'Q3 2026') !== false && strpos($readonly, 'admin-shared-header__period-readonly') !== false, 'Niet-geschikte route toont een vergrendelde periode.');
adminCoreAssert(strpos($readonly, '<form') === false, 'Niet-geschikte route mag geen periodeformulier tonen.');
$periodRoute = 'invoices';
ob_start(); include $template; $editableMarkup = ob_get_clean();
adminCoreAssert(strpos($editableMarkup, 'name="year"') !== false && strpos($editableMarkup, 'name="quarter"') !== false && strpos($editableMarkup, 'name="route" value="invoices"') !== false, 'Overzichtsroute moet jaar en kwartaal kunnen wijzigen.');
adminCoreAssert(strpos($editableMarkup, 'ajax="true"') !== false, 'Periodewijziging moet gedeelde AJAX-afhandeling gebruiken.');
$period = ['year' => 2026, 'quarter' => null];
ob_start(); include $template; $allQuarters = ob_get_clean();
adminCoreAssert(strpos($allQuarters, '<option value="" selected>Alles</option>') !== false, 'Kwartaalfilter moet Alles aanbieden.');
adminCoreAssert(strpos($allQuarters, 'Q 2026') === false && strpos($allQuarters, '2026') !== false, 'Periodebadge mag geen leeg kwartaal als Q tonen.');
$period = ['year' => null, 'quarter' => null];
ob_start(); include $template; $allYears = ob_get_clean();
adminCoreAssert(strpos($allYears, 'Alle jaren') !== false, 'Periodebadge moet alle jaren tonen.');
adminCoreAssert(strpos($allYears, '<select name="year" data-admin-period-year><option value="" selected>Alles</option>') !== false, 'Jaarfilter van lijsten moet Alles aanbieden.');
adminCoreAssert(strpos($allYears, 'data-admin-period-quarter disabled') !== false, 'Kwartaal moet uitgeschakeld zijn bij alle jaren.');
$periodRoute = '';
ob_start(); include $template; $allYearsReadonly = ob_get_clean();
adminCoreAssert(strpos($allYearsReadonly, 'Alle jaren') !== false && strpos($allYearsReadonly, '<form') === false, 'Andere adminroutes moeten Alle jaren vergrendeld tonen.');
$periodRoute = 'year';
$period = ['year' => 2024, 'quarter' => null];
ob_start(); include $template; $report = ob_get_clean();
adminCoreAssert(strpos($report, '<select name="year" data-admin-period-year><option value=""') === false, 'Rapportage mag geen Alle jaren-optie aanbieden.');
adminCoreAssert(stripos($editableMarkup, '<header') === false && stripos($editableMarkup, '<footer') === false, 'Adminheader mag geen globale header- of footer-tags gebruiken.');

echo "AdminCore shared header tests passed.\n";
