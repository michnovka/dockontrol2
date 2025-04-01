<?php

declare(strict_types=1);

namespace App\Twig\Components;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent]
final class Alert
{
    public string $type = 'success';
    public string $message;

    public function getType(): string
    {
        return match ($this->type) {
            'info' => 'info',
            'error', 'danger' => 'danger',
            'success' => 'success',
            'warning' => 'warning',
        };
    }
}
