<?php

declare(strict_types=1);

namespace App\Entity;

interface ActionInterface
{
    /** @return array<string, mixed>|null */
    public function getActionPayload(): ?array;

    public function getDockontrolNode(): ?DockontrolNode;
}
