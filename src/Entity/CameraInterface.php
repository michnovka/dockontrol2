<?php

declare(strict_types=1);

namespace App\Entity;

interface CameraInterface
{
    public function getDockontrolNode(): DockontrolNode;

    /**
     * @return array{protocol: string, host: string, login: string, channel: string}
     */
    public function getDockontrolNodePayload(): array;
}
