<?php

declare(strict_types=1);

namespace App\Helper;

use Elao\Enum\Attribute\EnumCase;
use Elao\Enum\ReadableEnumInterface;
use Elao\Enum\ReadableEnumTrait;

enum EmailConfirmationType: string implements ReadableEnumInterface
{
    use ReadableEnumTrait;

    #[EnumCase('Verify e-mail')]
    case VERIFY_EMAIL = 'verify_email';
    #[EnumCase('Request Account Deletion')]
    case REQUEST_ACCOUNT_DELETION = 'request_account_deletion';
}
