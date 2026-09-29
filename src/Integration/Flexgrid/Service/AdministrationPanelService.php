<?php

declare(strict_types=1);

namespace Flexgrid\Modules\AdminCore\Integration\Flexgrid\Service;

final class AdministrationPanelService
{
    private $controllers;

    public function __construct(array $controllers)
    {
        $this->controllers = $controllers;
    }

    public function getLinks(): array
    {
        $links = [];
        foreach ($this->controllers as $controller) {
            if (!is_object($controller)
                || !method_exists($controller, 'getRawMeta')
                || !method_exists($controller, 'getName')
                || !method_exists($controller, 'getIcon')
            ) {
                continue;
            }

            $meta = $controller->getRawMeta();
            if (!is_array($meta) || ($meta['administrationPanel'] ?? false) !== true) {
                continue;
            }
            if (method_exists($controller, 'canAccess') && !$controller->canAccess(false)) {
                continue;
            }

            $name = trim((string)$controller->getName());
            $label = trim((string)($meta['administrationLabel'] ?? ''));
            $route = trim((string)($meta['administrationRoute'] ?? ''), " \t\n\r\0\x0B/");
            if (preg_match('/^[A-Za-z][A-Za-z0-9]*$/D', $name) !== 1
                || $label === ''
                || strlen($label) > 80
                || ($route !== '' && preg_match('#^[A-Za-z0-9_/-]+$#D', $route) !== 1)
            ) {
                continue;
            }

            $path = '/Flexgrid/' . $name . ($route !== '' ? '/' . $route : '');
            $links[$name] = [
                'id' => strtolower($name),
                'label' => $label,
                'icon' => trim((string)$controller->getIcon()) ?: 'fas fa-cube',
                'path' => $path,
                'priority' => is_numeric($meta['administrationPriority'] ?? null) ? (int)$meta['administrationPriority'] : 100,
            ];
        }

        $links = array_values($links);
        usort($links, function (array $left, array $right): int {
            if ($left['priority'] === $right['priority']) {
                return strcmp($left['label'], $right['label']);
            }
            return $left['priority'] <=> $right['priority'];
        });

        return $links;
    }
}
