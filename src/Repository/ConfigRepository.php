<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Config;
use App\Entity\Enum\ConfigName;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Config>
 */
class ConfigRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Config::class);
    }

    /**
     * @return array<string, Config>
     */
    public function getAllIndexedByKey(): array
    {
        $allConfigs = $this->createQueryBuilder('c')->getQuery()->getResult();
        $result = [];

        /** @var Config $config*/
        foreach ($allConfigs as $config) {
            $result[$config->getConfigKey()->value] = $config;
        }

        return $result;
    }

    /**
     * @param ConfigName[] $configNames
     * @return array<string, Config>
     */
    public function getMultipleConfigValuesIndexedByKey(array $configNames): array
    {
        $configs = $this->createQueryBuilder('c')
            ->where('c.configKey IN (:configKeys)')
            ->setParameter('configKeys', $configNames)
            ->getQuery()
            ->getResult();
        $result = [];

        /** @var Config $config*/
        foreach ($configs as $config) {
            $result[$config->getConfigKey()->value] = $config;
        }

        return $result;
    }
}
