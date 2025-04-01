<?php

declare(strict_types=1);

namespace App\Helper;

readonly class CustomPaginator
{
    /**
     * @return array<int, int|string>
     */
    public function generatePagination(int $currentPage, bool $hasNextPage): array
    {
        $pages = [];

        if ($currentPage > 1) {
            $pages[] = 1;
        }

        if ($currentPage > 3) {
            $pages[] = '...';
        }

        if ($currentPage > 2) {
            $pages[] = $currentPage - 1;
        }

        $pages[] = $currentPage;

        if ($hasNextPage) {
            $pages[] = $currentPage + 1;
            $pages[] = '...';
        }

        return $pages;
    }
}
