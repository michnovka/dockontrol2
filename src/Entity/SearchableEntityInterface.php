<?php

declare(strict_types=1);

namespace App\Entity;

use App\Controller\CP\SearchAPIInterface;

interface SearchableEntityInterface
{
    public function getTwigDisplayValue(): string;

    /**
     * @return class-string<SearchAPIInterface>
     */
    public static function getSearchAPIController(): string;
}
