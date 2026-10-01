<?php

declare(strict_types=1);

namespace Flexgrid\Modules\AdminCore\Integration\Flexgrid\Controller;

use Flexgrid\Autowire\ControllerResolver;
use Flexgrid\Modules\AdminCore\Service\AdminPeriod;
use Flexgrid\Modules\AdminCore\Integration\Flexgrid\Service\AdministrationPanelService;
use Flexgrid\Response\AjaxResponse;
use Flexgrid\Response\PageResponse;
use Flexgrid\Response\TemplateResponse;
use Flexgrid\Utils\Request\Request;

/** @FG\Controller [name=AdminCore,type=Module,icon=fas fa-grid-2,level=2] */
final class AdminCoreController
{
    public function setPeriod(): AjaxResponse
    {
        $request = new Request();
        $route = $request->get('route', '');
        $destinations = [
            'quotes' => 'AdminQuote/quotes',
            'expenses' => 'AdminExpense/expenses',
            'invoices' => 'AdminInvoice/invoices',
            'quarter' => 'AdminReport/quarter',
            'year' => 'AdminReport/year',
        ];
        $response = new AjaxResponse();
        if (strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? '')) !== 'POST') {
            $response->success = false;
            $response->error = 'De periode kan alleen via het formulier worden gewijzigd.';
            return $response;
        }
        if (!is_string($route) || !isset($destinations[$route])) {
            $response->success = false;
            $response->error = 'Op deze pagina kan de periode niet worden gewijzigd.';
            return $response;
        }
        try {
            $year = $request->get('year', '');
            $quarter = $request->get('quarter', '');
            if ((!is_string($year) && !is_int($year)) || (!is_string($quarter) && !is_int($quarter))) {
                throw new \InvalidArgumentException('Ongeldig jaar of kwartaal.');
            }
            $year = (string)$year;
            $quarter = (string)$quarter;
            if (($year !== '' && preg_match('/^[0-9]{4}$/D', $year) !== 1)
                || ($quarter !== '' && preg_match('/^[1-4]$/D', $quarter) !== 1)
                || ($year === '' && in_array($route, ['quarter', 'year'], true))) {
                throw new \InvalidArgumentException('Ongeldig jaar of kwartaal.');
            }
            AdminPeriod::select($year === '' ? null : (int)$year, $year === '' || $quarter === '' ? null : (int)$quarter);
        } catch (\InvalidArgumentException $exception) {
            $response->success = false;
            $response->error = $exception->getMessage();
            return $response;
        }
        $response->success = true;
        $destination = in_array($route, ['quarter', 'year'], true)
            ? ($quarter === '' ? $destinations['year'] : $destinations['quarter'])
            : $destinations[$route];
        $response->redirect = rtrim(__DOMAIN__, '/') . '/Flexgrid/' . $destination;
        return $response;
    }

    /** @FG\Template [Flexgrid=true,Flexgridpanel=true] */
    public function getAdministrationPanel()
    {
        PageResponse::addAsset('Flexgrid/Flexgrid/src/Html/Admin/Css/AdminUi.scss');
        PageResponse::addAsset('Flexgrid/Flexgrid/src/Html/Admin/Css/DashboardPanels.scss');

        return new TemplateResponse(
            'Flexgrid/Modules/AdminCore/src/Integration/Flexgrid/Templates/AdministrationPanel.php',
            ['links' => (new AdministrationPanelService(ControllerResolver::getAll()))->getLinks()]
        );
    }
}
