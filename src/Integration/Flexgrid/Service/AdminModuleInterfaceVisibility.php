<?php

declare(strict_types=1);

namespace Flexgrid\Modules\AdminCore\Integration\Flexgrid\Service;

use Flexgrid\Auth\Auth;

final class AdminModuleInterfaceVisibility
{
    public static function isVisibleForCurrentUserClass(string $class): bool
    {
        $module = AdminModuleState::moduleNameForClass($class);
        if ($module === null) {
            return true;
        }

        return self::isVisibleForCurrentUser($module);
    }

    public static function isVisibleForCurrentUser(string $module): bool
    {
        if ($module === 'AdminCore') {
            return true;
        }
        self::assertModule($module);

        if (Auth::getAll('Flexgrid') === []) {
            return true;
        }

        $auth = Auth::get('Flexgrid');
        $user = $auth->getUser();
        if ($user === null) {
            return true;
        }

        return self::isVisibleForUser($module, (int)$user->getId(), $auth->getIsDevUser());
    }

    public static function isVisibleForUser(string $module, int $userId, bool $isDevUser = false): bool
    {
        if ($module === 'AdminCore' || $isDevUser) {
            return true;
        }
        self::assertModule($module);
        if ($userId <= 0) {
            return true;
        }

        $data = self::read();
        return ($data['users'][(string)$userId][$module]['hidden'] ?? false) !== true;
    }

    public static function setHiddenForUser(string $module, int $userId, bool $hidden, int $actorId): void
    {
        self::assertModule($module);
        if ($module === 'AdminCore' || $userId <= 0 || $actorId <= 0) {
            throw new \InvalidArgumentException('Ongeldige module of gebruiker.');
        }

        $directory = dirname(self::path());
        if (!is_dir($directory) && !mkdir($directory, 0770, true) && !is_dir($directory)) {
            throw new \RuntimeException('De configuratiemap kon niet worden aangemaakt.');
        }
        $lock = fopen(self::path() . '.lock', 'c');
        if ($lock === false || !flock($lock, LOCK_EX)) {
            throw new \RuntimeException('De interfaceconfiguratie kon niet worden vergrendeld.');
        }
        try {
            $data = self::read();
            $timestamp = gmdate('c');
            $data['users'][(string)$userId][$module] = [
                'hidden' => $hidden,
                'updated_at' => $timestamp,
                'updated_by' => $actorId,
            ];
            $data['history'][] = [
                'user_id' => $userId,
                'module' => $module,
                'hidden' => $hidden,
                'changed_at' => $timestamp,
                'changed_by' => $actorId,
            ];
            $temp = self::path() . '.' . bin2hex(random_bytes(8)) . '.tmp';
            $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            if ($json === false || file_put_contents($temp, $json, LOCK_EX) === false || !rename($temp, self::path())) {
                @unlink($temp);
                throw new \RuntimeException('De interfaceconfiguratie kon niet worden opgeslagen.');
            }
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }

    private static function read(): array
    {
        if (!is_file(self::path())) {
            return ['version' => 1, 'users' => [], 'history' => []];
        }
        $data = json_decode((string)file_get_contents(self::path()), true);
        if (!is_array($data) || !is_array($data['users'] ?? null) || !is_array($data['history'] ?? null)) {
            throw new \RuntimeException('De interfaceconfiguratie bevat ongeldige JSON.');
        }
        return $data;
    }

    private static function path(): string
    {
        return rtrim(__ROOTDIR__, '/\\') . '/Files/Config/AdminModuleInterfaces.json';
    }

    private static function assertModule(string $module): void
    {
        if (preg_match('/^Admin[A-Za-z0-9]+$/D', $module) !== 1) {
            throw new \InvalidArgumentException('Ongeldige administratiemodule.');
        }
    }
}
