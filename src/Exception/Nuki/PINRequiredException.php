<?php

declare(strict_types=1);

namespace App\Exception\Nuki;

use Exception;

class PINRequiredException extends Exception implements NukiExceptionInterface
{
}
