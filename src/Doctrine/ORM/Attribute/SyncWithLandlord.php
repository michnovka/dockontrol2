<?php

declare(strict_types=1);

namespace App\Doctrine\ORM\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class SyncWithLandlord
{
}
