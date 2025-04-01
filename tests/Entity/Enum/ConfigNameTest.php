<?php

declare(strict_types=1);

namespace App\Tests\Entity\Enum;

use App\Entity\Enum\ConfigGroup;
use App\Entity\Enum\ConfigName;
use App\Entity\Enum\ConfigType;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class ConfigNameTest extends TestCase
{
    public function testAllEnumCasesHaveConfigTypeDefined(): void
    {
        $enumCases = ConfigName::cases();

        foreach ($enumCases as $enumCase) {
            try {
                $configType = $enumCase->getConfigType();
                $this->assertNotEmpty($configType, "Enum case '{$enumCase->name}' does not have a valid config_type.");
                $this->assertInstanceOf(ConfigType::class, $configType);
            } catch (InvalidArgumentException) {
                $this->fail("Enum case '{$enumCase->name}' is missing the 'config_type' extra.");
            }
        }
    }

    public function testAllEnumCasesHaveConfigGroupDefined(): void
    {
        $enumCases = ConfigName::cases();

        foreach ($enumCases as $enumCase) {
            try {
                $configType = $enumCase->getConfigGroup();
                $this->assertNotEmpty($configType, "Enum case '{$enumCase->name}' does not have a valid config_group.");
                $this->assertInstanceOf(ConfigGroup::class, $configType);
            } catch (InvalidArgumentException) {
                $this->fail("Enum case '{$enumCase->name}' is missing the 'config_group' extra.");
            }
        }
    }
}
