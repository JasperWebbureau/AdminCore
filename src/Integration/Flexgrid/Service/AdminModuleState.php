<?php

declare(strict_types=1);

namespace Flexgrid\Modules\AdminCore\Integration\Flexgrid\Service;

final class AdminModuleState
{
    public static function isEnabledForClass(string $class): bool
    {
        if (!preg_match('/^Flexgrid\\\\Modules\\\\(Admin[A-Za-z0-9]+)\\\\/', ltrim($class, '\\'), $match)) {
            return true;
        }

        return self::isEnabled($match[1]);
    }

    public static function isEnabled(string $name): bool
    {
        if ($name === 'AdminCore') {
            return true;
        }
        self::assertName($name);
        $state = self::read();
        return ($state[$name] ?? true) === true;
    }

    public static function setEnabled(string $name, bool $enabled): void
    {
        self::assertName($name);
        if ($name === 'AdminCore') {
            throw new \InvalidArgumentException('AdminCore kan niet worden uitgeschakeld.');
        }
        $directory = dirname(self::path());
        if (!is_dir($directory) && !mkdir($directory, 0770, true) && !is_dir($directory)) {
            throw new \RuntimeException('De configuratiemap kon niet worden aangemaakt.');
        }
        $lock = fopen(self::path() . '.lock', 'c');
        if ($lock === false || !flock($lock, LOCK_EX)) {
            throw new \RuntimeException('De moduleconfiguratie kon niet worden vergrendeld.');
        }
        try {
            $state = self::read();
            $state[$name] = $enabled;
            ksort($state);
            $temp = self::path() . '.' . bin2hex(random_bytes(8)) . '.tmp';
            $json = json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            if ($json === false || file_put_contents($temp, $json, LOCK_EX) === false || !rename($temp, self::path())) {
                @unlink($temp);
                throw new \RuntimeException('De moduleconfiguratie kon niet worden opgeslagen.');
            }
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }

    private static function read(): array
    {
        $path = self::path();
        if (!is_file($path)) {
            return [];
        }
        $data = json_decode((string)file_get_contents($path), true);
        if (!is_array($data)) {
            throw new \RuntimeException('De moduleconfiguratie bevat ongeldige JSON.');
        }
        return $data;
    }

    private static function path(): string
    {
        return rtrim(__ROOTDIR__, '/\\') . '/Files/Config/AdminModules.json';
    }

    private static function assertName(string $name): void
    {
        if (preg_match('/^Admin[A-Za-z0-9]+$/D', $name) !== 1) {
            throw new \InvalidArgumentException('Ongeldige administratiemodule.');
        }
    }
}
