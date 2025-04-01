<?php

declare(strict_types=1);

namespace App\Exception\Nuki;

use Exception;

class TooManyTries extends Exception implements NukiExceptionInterface
{
}
