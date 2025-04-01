<?php

declare(strict_types=1);

namespace App\Tests\Entity\Enum;

use App\Entity\Enum\CronType;
use PHPUnit\Framework\TestCase;

class CronTypeTest extends TestCase
{
    public function testAllEnumCasesHasRunEveryXMinutes(): void
    {
        $enumCases = CronType::cases();

        foreach ($enumCases as $enumCase) {
            $runEveryXMinutes = $enumCase->getRunEveryXMinutes();
            $this->assertNotEmpty($runEveryXMinutes, "Enum case '{$enumCase->name}' does not have a valid 'runEveryXMinutes'.");
        }
    }
}
