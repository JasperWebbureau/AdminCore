<?php

declare(strict_types=1);

namespace Flexgrid\Modules\AdminCore\Integration\Flexgrid\Controller;

use Flexgrid\Autowire\ControllerResolver;
use Flexgrid\Auth\Auth;
use Flexgrid\Auth\Entity\AuthUser;
use Flexgrid\Flexgrid;
use Flexgrid\Html\Table\TableRenderer;
use Flexgrid\Html\Table\TrustedHtml;
use Flexgrid\Modules\AdminCore\Service\AdminPeriod;
use Flexgrid\Modules\AdminCore\Integration\Flexgrid\Service\AdministrationPanelService;
use Flexgrid\Modules\AdminCore\Integration\Flexgrid\Service\AdminModuleCatalog;
use Flexgrid\Modules\AdminCore\Integration\Flexgrid\Service\AdminModuleState;
use Flexgrid\Modules\AdminCore\Integration\Flexgrid\Service\AdminModuleInterfaceVisibility;
use Flexgrid\Response\AjaxResponse;
use Flexgrid\Response\PageResponse;
use Flexgrid\Response\TemplateResponse;
use Flexgrid\Utils\Request\Request;

/** @FG\Controller [name=AdminCore,type=Module,icon=fas fa-grid-2,level=2] */
final class AdminCoreController
{
    public function modules()
    {
        $auth = Auth::get('Flexgrid');
        if (!$auth->getIsDevUser()) {
            http_response_code(403);
            return 'Geen toegang tot modulebeheer.';
        }

        $users = [];
        foreach ($auth->getUserRepository()->select(true)->where('namespace = ?', ['Flexgrid'])->orderBy('email:ASC')->get() as $user) {
            if (!$user instanceof AuthUser || (int)$user->getId() <= 0 || $user->getIsWebbureauNuUser()) {
                continue;
            }
            $users[(int)$user->getId()] = [
                'id' => (int)$user->getId(),
                'label' => (string)$user->getEmail(),
            ];
        }
        $requestedUserId = strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET')) === 'POST'
            ? ($_POST['user_id'] ?? null)
            : ($_GET['user'] ?? null);
        $selectedUserId = is_scalar($requestedUserId) && ctype_digit((string)$requestedUserId)
            && isset($users[(int)$requestedUserId])
            ? (int)$requestedUserId
            : (int)(array_key_first($users) ?? 0);

        $catalog = new AdminModuleCatalog(
            rtrim(__ROOTDIR__, '/\\') . '/Flexgrid/Modules',
            defined('__GIT_ACCOUNT__') ? (string)__GIT_ACCOUNT__ : '',
            defined('__GIT_TOKEN__') ? (string)__GIT_TOKEN__ : ''
        );
        $error = '';
        if (strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET')) === 'POST') {
            $token = $_POST['_csrf'] ?? '';
            if (!is_string($token) || !$auth->getCsrfGuard()->validate($token)) {
                http_response_code(403);
                return 'Ongeldige beveiligingstoken.';
            }
            $name = $_POST['module'] ?? '';
            $action = $_POST['action'] ?? '';
            try {
                if (!is_string($name) || !is_string($action)) {
                    throw new \InvalidArgumentException('Ongeldige actie.');
                }
                if ($action === 'toggle') {
                    $path = rtrim(__ROOTDIR__, '/\\') . '/Flexgrid/Modules/' . $name;
                    if (preg_match('/^Admin[A-Za-z0-9]+$/D', $name) !== 1 || !is_dir($path)) {
                        throw new \InvalidArgumentException('De module is niet geïnstalleerd.');
                    }
                    AdminModuleState::setEnabled($name, ($_POST['enabled'] ?? '') === '1');
                    $message = $name . ' is ' . (AdminModuleState::isEnabled($name) ? 'ingeschakeld.' : 'uitgeschakeld.');
                } elseif ($action === 'toggle_interface') {
                    $path = rtrim(__ROOTDIR__, '/\\') . '/Flexgrid/Modules/' . $name;
                    if (preg_match('/^Admin[A-Za-z0-9]+$/D', $name) !== 1 || !is_dir($path)) {
                        throw new \InvalidArgumentException('De module is niet geïnstalleerd.');
                    }
                    if (!is_scalar($requestedUserId) || !ctype_digit((string)$requestedUserId)
                        || !isset($users[(int)$requestedUserId])) {
                        throw new \InvalidArgumentException('Kies een bestaande CMS-gebruiker.');
                    }
                    $hidden = ($_POST['interface_hidden'] ?? '') === '1';
                    AdminModuleInterfaceVisibility::setHiddenForUser($name, $selectedUserId, $hidden, (int)$auth->getUser()->getId());
                    $message = 'Interface van ' . $name . ' is voor ' . $users[$selectedUserId]['label']
                        . ($hidden ? ' verborgen.' : ' zichtbaar.');
                } elseif ($action === 'sync') {
                    $message = $catalog->installOrUpdate($name);
                } else {
                    throw new \InvalidArgumentException('Onbekende actie.');
                }
                Flexgrid::redirect(rtrim(__DOMAIN__, '/') . '/Flexgrid/AdminCore/modules?user=' . $selectedUserId
                    . '&message=' . rawurlencode($message), 303);
            } catch (\Throwable $exception) {
                $error = $exception->getMessage();
            }
        }

        $modules = $catalog->getModules();
        $base = rtrim(__DOMAIN__, '/') . '/Flexgrid/AdminCore/modules';
        $csrf = $auth->getCsrfGuard()->getToken();
        $escape = static function (string $value): string {
            return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        };
        $rows = [];
        foreach ($modules as $module) {
            $name = $module['name'];
            $hidden = '<input type="hidden" name="_csrf" value="' . $escape($csrf) . '" style="--cw:12">'
                . '<input type="hidden" name="module" value="' . $escape($name) . '" style="--cw:12">'
                . '<input type="hidden" name="user_id" value="' . $selectedUserId . '" style="--cw:12">';
            $actions = '';
            if ($module['installed'] && $name !== 'AdminCore') {
                $actions .= '<form method="post" action="' . $escape($base) . '" class="admin-module-form">'
                    . $hidden . '<input type="hidden" name="action" value="toggle" style="--cw:12">'
                    . '<div class="admin-module-controls" style="--cw:12"><label><input type="checkbox" name="enabled" value="1"'
                    . ($module['enabled'] ? ' checked' : '') . '> Installatie actief</label>'
                    . '<button class="button button-small button-secondary" type="submit">Opslaan</button></div></form>';
                if ($selectedUserId > 0) {
                    $interfaceHidden = !AdminModuleInterfaceVisibility::isVisibleForUser($name, $selectedUserId);
                    $actions .= '<form method="post" action="' . $escape($base) . '" class="admin-module-form">'
                        . $hidden . '<input type="hidden" name="action" value="toggle_interface" style="--cw:12">'
                        . '<div class="admin-module-controls" style="--cw:12"><label><input type="checkbox" name="interface_hidden" value="1"'
                        . ($interfaceHidden ? ' checked' : '') . '> Interface uitzetten</label>'
                        . '<button class="button button-small button-secondary" type="submit">Opslaan</button></div></form>';
                }
            }
            if (!$module['installed'] || $module['updatable']) {
                $actions .= '<form method="post" action="' . $escape($base) . '" class="admin-module-form">'
                    . $hidden . '<input type="hidden" name="action" value="sync" style="--cw:12">'
                    . '<div style="--cw:12"><button class="button button-small button-primary" type="submit">'
                    . ($module['replaceable'] ? 'Git-versie plaatsen (backup)' : ($module['installed'] ? 'Update' : 'Installeren'))
                    . '</button></div></form>';
            }
            $status = !$module['installed'] ? 'Niet geïnstalleerd' : ($module['enabled'] ? 'Actief' : 'Uitgeschakeld');
            $rows[] = [
                'id' => $name,
                'cells' => [
                    'module' => ['value' => $name, 'title' => true, 'secondary' => $module['description']],
                    'version' => $module['version'] !== '' ? $module['version'] : '—',
                    'status' => ['value' => $status, 'badge' => $module['enabled'] ? 'success' : 'neutral'],
                    'interface' => $selectedUserId === 0 || !$module['installed'] || $name === 'AdminCore'
                        ? '—'
                        : (AdminModuleInterfaceVisibility::isVisibleForUser($name, $selectedUserId) ? 'Zichtbaar' : 'Verborgen'),
                    'source' => $module['remote'] ? 'GitHub' : 'Alleen lokaal',
                ],
                'actions' => new TrustedHtml($actions),
            ];
        }
        $table = new TableRenderer([
            'id' => 'admin-modules',
            'label' => 'Administratiemodules',
            'columns' => [
                ['key' => 'module', 'label' => 'Module'],
                ['key' => 'version', 'label' => 'Versie'],
                ['key' => 'status', 'label' => 'Installatie'],
                ['key' => 'interface', 'label' => 'Interface gebruiker'],
                ['key' => 'source', 'label' => 'Bron'],
            ],
            'rows' => $rows,
            'empty' => ['title' => 'Geen Admin-modules gevonden', 'message' => 'Controleer GitHub-toegang en de Modules-map.'],
        ]);
        PageResponse::addAsset('Flexgrid/Modules/AdminCore/src/Integration/Flexgrid/Templates/Css/Modules.scss');
        appendIconAndTitleToHeader('fas fa-cubes', 'Modulebeheer', 'Administratie');
        return new TemplateResponse(
            'Flexgrid/Modules/AdminCore/src/Integration/Flexgrid/Templates/Modules.php',
            [
                'table' => $table,
                'users' => array_values($users),
                'selectedUserId' => $selectedUserId,
                'baseUrl' => $base,
                'warning' => $catalog->getWarning(),
                'error' => $error,
                'message' => is_string($_GET['message'] ?? null) ? $_GET['message'] : '',
            ]
        );
    }

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
            [
                'links' => (new AdministrationPanelService(ControllerResolver::getAll()))->getLinks(),
                'canManageModules' => Auth::get('Flexgrid')->getIsDevUser(),
            ]
        );
    }
}
