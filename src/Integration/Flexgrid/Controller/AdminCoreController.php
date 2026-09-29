<?php

declare(strict_types=1);

namespace Flexgrid\Modules\AdminCore\Integration\Flexgrid\Controller;

use Flexgrid\Autowire\ControllerResolver;
use Flexgrid\Modules\AdminCore\Integration\Flexgrid\Service\AdministrationPanelService;
use Flexgrid\Response\PageResponse;
use Flexgrid\Response\TemplateResponse;

/** @FG\Controller [name=AdminCore,type=Module,icon=fas fa-grid-2,level=2] */
final class AdminCoreController
{
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
