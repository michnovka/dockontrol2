<?php

declare(strict_types=1);

namespace App\Security\Expression;

use App\Entity\Enum\UserRole;
use Symfony\Component\ExpressionLanguage\Expression;

class RoleRequired extends Expression
{
    public function __construct(UserRole ...$roles)
    {
        parent::__construct($this->generateRolesExpression(...$roles));
    }

    private function generateRolesExpression(UserRole ...$roles): string
    {
        $expression = 'is_granted("ROLE_SUPER_ADMIN")';

        foreach ($roles as $role) {
            $expression .= ' or is_granted("' . $role->value . '")';
        }

        return $expression;
    }
}
