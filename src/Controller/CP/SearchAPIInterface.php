<?php

declare(strict_types=1);

namespace App\Controller\CP;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

interface SearchAPIInterface
{
    public function searchAPI(Request $request): JsonResponse;
}
