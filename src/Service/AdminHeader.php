<?php

declare(strict_types=1);

namespace Flexgrid\Modules\AdminCore\Service;

use Flexgrid\Autowire\ControllerResolver;
use Flexgrid\Event\AjaxEvent;
use Flexgrid\Flexgrid;
use Flexgrid\Modules\AdminCore\Integration\Flexgrid\Controller\AdminCoreController;
use Flexgrid\Modules\AdminCore\Integration\Flexgrid\Service\AdministrationPanelService;
use Flexgrid\Response\PageResponse;
use Flexgrid\Response\TemplateResponse;

final class AdminHeader
{
    /**
     * @param array $buttons Link definitions or existing action templates.
     * @param string $periodRoute Explicit editable overview route; empty means read-only.
     */
    public static function AdminAddHeader(array $buttons = [], string $periodRoute = ''): void
    {
        $routes = ['quotes', 'expenses', 'invoices', 'quarter', 'year'];
        if ($periodRoute !== '' && !in_array($periodRoute, $routes, true)) {
            throw new \InvalidArgumentException('Deze route kan de administratieperiode niet wijzigen.');
        }
        $event = new AjaxEvent(AdminCoreController::class, 'setPeriod');
        $event->setMinimumAccessLevel(2);
        PageResponse::addAsset('Flexgrid/Modules/AdminCore/src/Integration/Flexgrid/Templates/Css/AdminHeader.scss');
        PageResponse::addAsset('Flexgrid/Modules/AdminCore/src/Integration/Flexgrid/Templates/Js/AdminHeader.js');
        $period = in_array($periodRoute, ['quarter', 'year'], true) ? AdminPeriod::selectedForReport() : AdminPeriod::selected();
        if ($periodRoute === 'year') {
            $period['quarter'] = null;
        }
        Flexgrid::getApp()->appendMainHeader(new TemplateResponse(
            'Flexgrid/Modules/AdminCore/src/Integration/Flexgrid/Templates/AdminHeader.php',
            [
                'buttons' => $buttons,
                'links' => array_values(array_filter(
                    (new AdministrationPanelService(ControllerResolver::getAll()))->getLinks(),
                    static function (array $link): bool {
                        return strpos($link['id'], 'admin') === 0 && $link['id'] !== 'admindashboard';
                    }
                )),
                'period' => $period,
                'periodRoute' => $periodRoute,
                'periodAction' => $event->getName(),
            ]
        ));
    }
}
