<?php

declare(strict_types=1);

namespace App\Tests\Entity\Enum;

use App\Entity\Enum\ConfigType;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionException;
use Symfony\Component\Form\AbstractType;

class ConfigTypeTest extends TestCase
{
    /**
     * @throws ReflectionException
     */
    public function testConfigTypeIsInstanceOfType(): void
    {
        $enumCases = ConfigType::cases();

        foreach ($enumCases as $enumCase) {
            $inputType = $enumCase->getInputType();
            $reflectionFormTypeClass = new ReflectionClass($inputType);
            $this->assertInstanceOf(AbstractType::class, $reflectionFormTypeClass->newInstance());
        }
    }
}
