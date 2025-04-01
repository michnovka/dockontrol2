<?php

declare(strict_types=1);

namespace App\Exception\Nuki;

use Exception;

class APICallFailed extends Exception implements NukiExceptionInterface
{
}
