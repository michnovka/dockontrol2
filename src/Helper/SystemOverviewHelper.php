<?php

declare(strict_types=1);

namespace App\Helper;

use Carbon\CarbonImmutable;
use Doctrine\DBAL\Exception;
use Doctrine\ORM\EntityManagerInterface;
use Meilisearch\Client;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Process\Process;

readonly class SystemOverviewHelper
{
    public const string DEFAULT_PASSWORD_AND_SECRET = 'ChangeMe';

    public function __construct(
        private EntityManagerInterface $entityManager,
        private RedisHelper $redisHelper,
        private Client $meilisearchClient,
        private ParameterBagInterface $parameterBag,
    ) {
    }

    /**
     * @return array{extensions: array<int, string>, memoryLimit: string, maxExecutionTime: string, uploadMaxFilesize: string, version: string}
     */
    public function getPHPInfo(): array
    {
        /** @var string $phpVersion*/
        $phpVersion = phpversion();
        /** @var string $memoryLimit*/
        $memoryLimit = ini_get('memory_limit');
        /** @var string $maxExecutionTime*/
        $maxExecutionTime = ini_get('max_execution_time');
        /** @var string $uploadMaxSize*/
        $uploadMaxSize = ini_get('upload_max_filesize');
        $phpExtensions = new Process(['php', '-m']);
        $phpExtensions->run();
        $phpExtensions = $phpExtensions->getOutput();
        $phpExtensionsArray = array_filter(explode("\n", $phpExtensions), function ($value) {
            return !in_array($value, ['[PHP Modules]', '[Zend Modules]']) && !empty($value);
        });

        return [
            'version' => $phpVersion,
            'memoryLimit' => $memoryLimit,
            'maxExecutionTime' => $maxExecutionTime,
            'uploadMaxFilesize' => $uploadMaxSize,
            'extensions' => $phpExtensionsArray,
        ];
    }

    /**
     * @throws Exception
     * @return array{isDBUsingUtf8mb4: bool, size: false|mixed, type: null|string, usingDefaultPassword: bool, version: string}
     */
    public function getDBInfo(string $dbURL): array
    {
        $dbConnection = $this->entityManager->getConnection();
        $dbType = explode(':', $dbURL)[0];
        $dbPass = parse_url($dbURL)['pass'] ?? null;
        /** @var string $dbVersion*/
        $dbVersion = $dbConnection->fetchOne('SELECT VERSION()');
        if (str_contains(strtolower($dbVersion), 'mariadb')) {
            $dbType = 'MariaDB';
        }
        $dbVersion = explode('-', $dbVersion)[0];
        $dbSize = 'SELECT SUM(ROUND(((data_length + index_length) / 1024 / 1024), 2)) AS database_size FROM information_schema.TABLES WHERE table_schema = "' . $dbConnection->getDatabase() . '";';
        $dbSize = $dbConnection->fetchOne($dbSize);
        $isDBUsingUtf8mb4 = $dbConnection->fetchOne('SELECT DEFAULT_CHARACTER_SET_NAME FROM information_schema.SCHEMATA S WHERE schema_name = "' . $dbConnection->getDatabase() . '"') == 'utf8mb4';

        return [
            'type' => $dbType,
            'version' => $dbVersion,
            'size' => $dbSize,
            'isDBUsingUtf8mb4' => $isDBUsingUtf8mb4,
            'usingDefaultPassword' => $dbPass === self::DEFAULT_PASSWORD_AND_SECRET,
        ];
    }

    /**
     * @return array{deviceName: string, os: string, cpuModel: string, memory: string, memPeak: int}
     */
    public function getOSInfo(): array
    {
        $osInfo = explode(' ', php_uname());
        $deviceName = $osInfo[1];
        $os = $osInfo[0] . ' ' . $osInfo[2] . ' ' . end($osInfo);

        $cpuModelProcess = new Process(['bash', '-c', "cat /proc/cpuinfo | grep 'model name' | uniq"]);
        $cpuModelProcess->run();
        $output = trim($cpuModelProcess->getOutput());
        $cpuModel = explode(': ', $output)[1];

        $cpuCoresProcess = new Process(['nproc']);
        $cpuCoresProcess->run();
        $cpuCores = trim($cpuCoresProcess->getOutput());

        $cpuModelInfo = $cpuModel . ' (' . $cpuCores . ' cores)';

        $process = new Process(['bash', '-c', "grep MemTotal /proc/meminfo | awk '{print $2}'"]);
        $process->run();
        $memoryKb = intval(trim($process->getOutput()));
        $memoryMb = $memoryKb / 1024;
        $memory = number_format((float) $memoryMb / 1024.0, 2) . ' GB';
        $memPeak = intval((float) memory_get_peak_usage(true) / 1024.0 / 1024.0);

        return [
            'deviceName' => $deviceName,
            'os' => $os,
            'cpuModel' => $cpuModelInfo,
            'memory' => $memory,
            'memPeak' => $memPeak,
        ];
    }

    /**
     * @return array{total: string, used: string, available: string, percent: string}
     * */
    public function getDiskUsage(): array
    {
        $process = new Process(['bash', '-c', 'df -h --total | grep total']);
        $process->run();

        $output = trim($process->getOutput());
        $parts = preg_split('/\s+/', $output);

        return [
            'total' => $parts[1] ?? 'Unknown',
            'used' => $parts[2] ?? 'Unknown',
            'available' => $parts[3] ?? 'Unknown',
            'percent' => $parts[4] ?? 'Unknown',
        ];
    }

    /**
     * @return array<string, array<string>|bool>
     */
    public function getRedisInfo(): array
    {
        $redis = $this->redisHelper->getRedisInstance();

        /** @var array $info*/
        $info = $redis->info();

        return [
            'Server' => [
                'redisVersion' => $info['valkey_version'] ?? 'N/A',
                'os' => $info['os'] ?? 'N/A',
                'uptime' => $info['uptime_in_days'] ?? 'N/A',
            ],
            'Memory' => [
                'usedMemory' => $info['used_memory_human'] ?? 'N/A',
                'memoryPeak' => $info['used_memory_peak_human'] ?? 'N/A',
                'memoryFragmentationRatio' => $info['mem_fragmentation_ratio'] ?? 'N/A',
            ],
            'Clients' => [
                'connectedClients' => $info['connected_clients'] ?? 'N/A',
                'blockedClients' => $info['blocked_clients'] ?? 'N/A',
            ],
            'Statistics' => [
                'totalConnectionsReceived' => $info['total_connections_received'] ?? 'N/A',
                'totalCommandsProcessed' => $info['total_commands_processed'] ?? 'N/A',
                'keyspaceHits' => $info['keyspace_hits'] ?? 'N/A',
                'keyspaceMisses' => $info['keyspace_misses'] ?? 'N/A',
            ],
            'usingDefaultPassword' => $redis->getAuth() === self::DEFAULT_PASSWORD_AND_SECRET,
        ];
    }

    /**
     * @return array<string|bool>
     */
    public function getMeilisearchInfo(): array
    {
        /** @var array $status*/
        $status = $this->meilisearchClient->health();
        $version = $this->meilisearchClient->version();
        $stats = $this->meilisearchClient->stats();

        $totalIndexes = count($stats['indexes']);

        $databaseSizeBytes = $stats['databaseSize'] ?? 0;
        $databaseSizeMB = $databaseSizeBytes / (1024 * 1024);

        $lastUpdate = $stats['lastUpdate'] ?? null;
        if ($lastUpdate) {
            $lastUpdateFormatted = CarbonImmutable::parse($lastUpdate)->format('Y-m-d H:i:s');
        } else {
            $lastUpdateFormatted = 'No updates yet';
        }

        return [
            'version' => $version['pkgVersion'],
            'commitDate' => CarbonImmutable::parse($version['commitDate'])->format('Y-m-d'),
            'totalIndexes' => $totalIndexes,
            'databaseSizeMB' => number_format($databaseSizeMB, 2),
            'lastUpdateTime' => $lastUpdateFormatted,
            'status' => $status['status'],
            'usingDefaultPassword' => $this->parameterBag->get('meili_api_key') === self::DEFAULT_PASSWORD_AND_SECRET,
        ];
    }


    public function usingDefaultAppSecret(): bool
    {
        return $this->parameterBag->get('app_secret') === self::DEFAULT_PASSWORD_AND_SECRET;
    }
}
