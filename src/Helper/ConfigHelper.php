<?php

declare(strict_types=1);

namespace App\Helper;

use App\Entity\Config;
use App\Entity\Enum\ConfigName;
use App\Entity\Enum\ConfigType;
use App\Repository\ConfigRepository;
use Carbon\CarbonImmutable;
use Doctrine\ORM\EntityManagerInterface;
use RuntimeException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

readonly class ConfigHelper
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ConfigRepository $configRepository,
        private ValidatorInterface $validator,
    ) {
    }

    public function setConfig(ConfigName $key, CarbonImmutable|int|string|bool $value): void
    {
        $config = $this->configRepository->findOneBy([
            'configKey' => $key,
        ]);

        if (!$config instanceof Config) {
            $config = new Config();
            $config->setConfigKey($key);
        }

        $value = $this->getStringValue($key, $value);

        $config->setValue($value);
        $this->saveConfig($config);
    }

    public function removeConfig(Config $config): void
    {
        $this->entityManager->remove($config);
        $this->entityManager->flush();
    }

    public function getConfigValue(ConfigName|Config $key): CarbonImmutable|int|string|null|bool
    {
        if ($key instanceof Config) {
            $config = $key;
            $key = $config->getConfigKey();
        } else {
            $config = $this->configRepository->findOneBy([
                'configKey' => $key,
            ]);
        }

        if ($config instanceof Config) {
            return match ($key->getConfigType()) {
                ConfigType::DATETIME => CarbonImmutable::create($config->getValue()),
                ConfigType::INT => intval($config->getValue()),
                ConfigType::BOOLEAN => $config->getValue() === 'yes',
                ConfigType::STRING, ConfigType::SECRET => $config->getValue(),
                default => throw new RuntimeException('Invalid config type.'),
            };
        } elseif ($key->getDefault() !== null) {
            return $key->getDefault();
        }

        return null;
    }

    /**
     * @param array<ConfigName> $configNames
     * @return array<string, mixed>
     */
    public function getMultipleConfigValues(array $configNames): array
    {
        $configs = $this->configRepository->getMultipleConfigValuesIndexedByKey($configNames);
        $configValues = [];
        foreach ($configNames as $configName) {
            $configValues[$configName->value] = isset($configs[$configName->value]) ? $this->getConfigValue($configs[$configName->value]) : $configName->getDefault();
        }

        return $configValues;
    }

    private function saveConfig(Config $config): void
    {
        $validationErrors = $this->validator->validate($config);
        if (count($validationErrors) > 0) {
            $errorMsg = '';
            foreach ($validationErrors as $error) {
                $errorMsg .= (string) $error->getMessage();
            }
            throw new RuntimeException($errorMsg);
        }
        $this->entityManager->persist($config);
        $this->entityManager->flush();
    }

    private function getStringValue(ConfigName $key, CarbonImmutable|int|string|bool $value): string
    {
        switch ($key->getConfigType()) {
            case ConfigType::DATETIME:
                if (!$value instanceof CarbonImmutable) {
                    throw new RuntimeException('Invalid value for ConfigType::DATETIME.');
                }
                return $value->format('Y-m-d H:i:s');
            case ConfigType::INT:
                if (!is_int($value)) {
                    throw new RuntimeException('Invalid value for ConfigType::INT.');
                }
                return (string) $value;
            case ConfigType::BOOLEAN:
                if (!is_bool($value)) {
                    throw new RuntimeException('Invalid value for ConfigType::BOOLEAN.');
                }
                return $value ? 'yes' : 'no';
            case ConfigType::STRING:
            case ConfigType::SECRET:
                if (!is_string($value)) {
                    throw new RuntimeException('Invalid value for ' . $key->getConfigType()->getReadable());
                }
                return $value;
            default:
                throw new RuntimeException('Invalid config type.');
        }
    }
}
