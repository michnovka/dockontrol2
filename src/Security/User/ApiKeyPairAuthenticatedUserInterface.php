<?php

declare(strict_types=1);

namespace App\Security\User;

use Symfony\Component\Security\Core\User\UserInterface;

interface ApiKeyPairAuthenticatedUserInterface extends UserInterface
{
    public function getPrivateKey(): string;
}
