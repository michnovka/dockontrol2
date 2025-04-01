<?php

declare(strict_types=1);

namespace App\Exception\Nuki;

use Exception;

class Password1Mismatch extends Exception implements NukiExceptionInterface
{
}
