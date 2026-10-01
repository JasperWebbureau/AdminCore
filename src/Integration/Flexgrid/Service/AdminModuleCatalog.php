<?php

declare(strict_types=1);

namespace Flexgrid\Modules\AdminCore\Integration\Flexgrid\Service;

final class AdminModuleCatalog
{
    private $root;
    private $account;
    private $token;
    private $warning = '';

    public function __construct(string $root, string $account, string $token)
    {
        $this->root = rtrim($root, '/\\');
        $this->account = trim($account);
        $this->token = trim($token);
        if (preg_match('/^[A-Za-z0-9-]+$/D', $this->account) !== 1) {
            throw new \RuntimeException('Git-account is niet geconfigureerd.');
        }
    }

    public function getModules(): array
    {
        try {
            $remote = $this->remoteModules();
        } catch (\RuntimeException $exception) {
            $remote = [];
            $this->warning = $exception->getMessage();
        }
        $modules = [];
        foreach (glob($this->root . '/Admin*', GLOB_ONLYDIR) ?: [] as $path) {
            $name = basename($path);
            if (self::validName($name)) {
                $modules[$name] = $this->describe($name, $remote[$name] ?? null);
            }
        }
        foreach ($remote as $name => $repo) {
            if (!isset($modules[$name])) {
                $modules[$name] = $this->describe($name, $repo);
            }
        }
        ksort($modules);
        return array_values($modules);
    }

    public function getWarning(): string
    {
        return $this->warning;
    }

    public function installOrUpdate(string $name): string
    {
        if (!self::validName($name)) {
            throw new \InvalidArgumentException('Ongeldige modulenaam.');
        }
        $remote = $this->remoteModules();
        if (!isset($remote[$name])) {
            throw new \RuntimeException('Deze module is niet gevonden binnen het ingestelde Git-account.');
        }
        $path = $this->root . '/' . $name;
        $url = $this->repositoryUrl($name);
        $branch = $remote[$name]['default_branch'];
        if (!is_dir($path)) {
            $stage = $this->stagePath($name);
            try {
                $this->git(['clone', '--quiet', '--single-branch', '--branch', $branch, $url, $stage], $this->root);
                if (!rename($stage, $path)) {
                    throw new \RuntimeException('De gedownloade module kon niet worden geplaatst.');
                }
            } catch (\Throwable $exception) {
                throw new \RuntimeException($exception->getMessage() . ' Controleer eventuele stagingmap: ' . basename($stage), 0, $exception);
            }
            return $name . ' is geïnstalleerd. Voer een bevoegde force_aw=true-scan uit om controllers en entities te registreren.';
        }

        if (!is_dir($path . '/.git')) {
            $stage = $this->stagePath($name);
            $backup = $this->root . '/.' . $name . '-backup-' . date('YmdHis') . '-' . bin2hex(random_bytes(4));
            $this->git(['clone', '--quiet', '--single-branch', '--branch', $branch, $url, $stage], $this->root);
            if (!rename($path, $backup)) {
                throw new \RuntimeException('De bestaande module kon niet naar een backupmap worden verplaatst. Stagingmap: ' . basename($stage));
            }
            if (!rename($stage, $path)) {
                if (!rename($backup, $path)) {
                    throw new \RuntimeException('Plaatsen en terugzetten mislukt. Herstel de module uit ' . basename($backup) . '.');
                }
                throw new \RuntimeException('De nieuwe module kon niet worden geplaatst. De oude map is hersteld.');
            }
            return $name . ' is vanuit GitHub geplaatst. De oude map staat in ' . basename($backup)
                . '. Controleer de verschillen en voer een bevoegde force_aw=true-scan uit.';
        }
        $origin = trim($this->git(['remote', 'get-url', 'origin'], $path));
        if (!$this->matchesOrigin($origin, $name)) {
            throw new \RuntimeException('De Git-remote wijkt af van het ingestelde account.');
        }
        if (trim($this->git(['status', '--porcelain', '--untracked-files=normal'], $path)) !== '') {
            throw new \RuntimeException('De module bevat lokale wijzigingen. Commit of verplaats die eerst.');
        }
        $branch = trim($this->git(['symbolic-ref', '--short', 'HEAD'], $path));
        if ($branch !== $remote[$name]['default_branch']) {
            throw new \RuntimeException('De huidige branch wijkt af van de standaardbranch.');
        }
        $before = trim($this->git(['rev-parse', 'HEAD'], $path));
        $this->git(['fetch', '--quiet', '--no-tags', 'origin', $branch], $path);
        $this->git(['merge', '--quiet', '--ff-only', 'FETCH_HEAD'], $path);
        $after = trim($this->git(['rev-parse', 'HEAD'], $path));
        return $before === $after
            ? $name . ' is al actueel.'
            : $name . ' is bijgewerkt. Voer een bevoegde force_aw=true-scan uit als metadata of entities zijn veranderd.';
    }

    private function describe(string $name, ?array $repo): array
    {
        $path = $this->root . '/' . $name;
        $installed = is_dir($path);
        $version = $installed && is_file($path . '/version.txt') ? trim((string)file_get_contents($path . '/version.txt')) : '';
        $git = $installed && is_dir($path . '/.git');
        $origin = '';
        if ($git) {
            try {
                $origin = trim($this->git(['remote', 'get-url', 'origin'], $path));
            } catch (\RuntimeException $exception) {
                $origin = '';
            }
        }
        return [
            'name' => $name,
            'installed' => $installed,
            'enabled' => $installed && AdminModuleState::isEnabled($name),
            'version' => $version,
            'remote' => $repo !== null,
            'updatable' => $repo !== null && (!$git || $this->matchesOrigin($origin, $name)),
            'replaceable' => $repo !== null && $installed && !$git,
            'description' => $repo['description'] ?? '',
        ];
    }

    private function remoteModules(): array
    {
        if ($this->token === '') {
            throw new \RuntimeException('Git-token is niet geconfigureerd.');
        }
        $repos = [];
        for ($page = 1; $page <= 10; $page++) {
            $items = $this->request('https://api.github.com/user/repos?affiliation=owner,organization_member&visibility=all&per_page=100&page=' . $page);
            foreach ($items as $item) {
                if (!is_array($item)) {
                    continue;
                }
                $name = (string)($item['name'] ?? '');
                if (!self::validName($name) || strcasecmp((string)($item['owner']['login'] ?? ''), $this->account) !== 0) {
                    continue;
                }
                $branch = (string)($item['default_branch'] ?? '');
                if (preg_match('/^[A-Za-z0-9._-]+$/D', $branch) !== 1) {
                    continue;
                }
                $repos[$name] = ['default_branch' => $branch, 'description' => (string)($item['description'] ?? '')];
            }
            if (count($items) < 100) {
                return $repos;
            }
        }
        throw new \RuntimeException('Te veel repositories om veilig een volledige catalogus te tonen.');
    }

    private function request(string $url): array
    {
        if (!function_exists('curl_init')) {
            throw new \RuntimeException('De PHP cURL-extensie is nodig voor de modulecatalogus.');
        }
        $curl = curl_init($url);
        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $this->token, 'Accept: application/vnd.github+json', 'X-GitHub-Api-Version: 2022-11-28'],
            CURLOPT_USERAGENT => 'Flexgrid-AdminCore',
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => 15,
        ]);
        $body = curl_exec($curl);
        $status = (int)curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
        if (!is_string($body) || $status !== 200) {
            throw new \RuntimeException('GitHub-catalogus is niet bereikbaar (HTTP ' . $status . '). Controleer token en repo-toegang.');
        }
        $data = json_decode($body, true);
        if (!is_array($data) || ($data !== [] && array_keys($data) !== range(0, count($data) - 1))) {
            throw new \RuntimeException('GitHub stuurde geen geldige repositorylijst terug.');
        }
        return $data;
    }

    private function git(array $arguments, string $cwd): string
    {
        $command = 'git';
        foreach ($arguments as $argument) {
            $command .= ' ' . escapeshellarg($argument);
        }
        $environment = getenv();
        if (!is_array($environment)) {
            $environment = [];
        }
        $environment['GIT_TERMINAL_PROMPT'] = '0';
        $environment['GIT_CONFIG_COUNT'] = '1';
        $environment['GIT_CONFIG_KEY_0'] = 'http.https://github.com/.extraheader';
        $environment['GIT_CONFIG_VALUE_0'] = 'Authorization: Basic ' . base64_encode('x-access-token:' . $this->token);
        $pipes = [];
        $process = proc_open($command, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, $cwd, $environment);
        if (!is_resource($process)) {
            throw new \RuntimeException('Git kon niet worden gestart.');
        }
        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        if (proc_close($process) !== 0) {
            throw new \RuntimeException('Git-actie mislukt: ' . trim((string)$stderr));
        }
        return (string)$stdout;
    }

    private function repositoryUrl(string $name): string
    {
        return 'https://github.com/' . $this->account . '/' . $name . '.git';
    }

    private function stagePath(string $name): string
    {
        return $this->root . '/.' . $name . '-install-' . bin2hex(random_bytes(6));
    }

    private function matchesOrigin(string $origin, string $name): bool
    {
        return strcasecmp(rtrim($origin, '/'), $this->repositoryUrl($name)) === 0
            || strcasecmp(rtrim($origin, '/'), 'https://github.com/' . $this->account . '/' . $name) === 0;
    }

    private static function validName(string $name): bool
    {
        return preg_match('/^Admin[A-Za-z0-9]+$/D', $name) === 1;
    }
}
