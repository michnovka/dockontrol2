<?php

declare(strict_types=1);

namespace App\Event;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\EventDispatcher\Event;

class LocaleEvent extends Event
{
    public function __construct(protected string $locale, protected Request $request)
    {
    }

    public function getLocale(): string
    {
        return $this->locale;
    }

    public function getRequest(): Request
    {
        return $this->request;
    }
}
