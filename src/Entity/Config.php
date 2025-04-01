<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Enum\ConfigName;
use App\Entity\Enum\ConfigType;
use App\Repository\ConfigRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[ORM\Entity(repositoryClass: ConfigRepository::class)]
class Config
{
    #[ORM\Id]
    #[ORM\Column(name: '`key`', type: 'string', length: 63, enumType: ConfigName::class)]
    private ConfigName $configKey;

    #[ORM\Column(type: 'text')]
    private string $value;

    #[Assert\Callback]
    public function validateConfigValue(ExecutionContextInterface $context): void
    {
        $configType = $this->configKey->getConfigType();
        if ($configType === ConfigType::INT && preg_match('/^-?\d+$/', $this->value) !== 1) {
            $context->buildViolation('Invalid integer value for config: ' . $this->configKey->getReadable())
                ->atPath('value')
                ->addViolation();
        } elseif ($configType === ConfigType::BOOLEAN && !in_array($this->value, ['yes', 'no'])) {
            $context->buildViolation('invalid string value for config,' . $this->configKey->getReadable())
                ->atPath('value')
                ->addViolation();
        } elseif ($configType === ConfigType::DATETIME && preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $this->value) !== 1) {
            $context->buildViolation('invalid datetime value for config,' . $this->configKey->getReadable())
                ->atPath('value')
                ->addViolation();
        }
    }

    public function getConfigKey(): ConfigName
    {
        return $this->configKey;
    }

    public function setConfigKey(ConfigName $configKey): self
    {
        $this->configKey = $configKey;

        return $this;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function setValue(string $value): self
    {
        $this->value = $value;

        return $this;
    }
}
