<?php

declare(strict_types=1);

namespace App\Enum;

enum CanalNotification: string
{
    case EMAIL = 'EMAIL';
    case SMS = 'SMS';
    case PUSH = 'PUSH';

    public function libelle(): string
    {
        return match ($this) {
            self::EMAIL => 'E-mail',
            self::SMS => 'SMS',
            self::PUSH => 'Notification interne',
        };
    }
}
