<?php

namespace App\Enums;

enum UserPermission: int
{
    case Employee = 1;
    case CompanyManager = 9;

    public function label(): string
    {
        return match ($this) {
            self::Employee => '一般社員',
            self::CompanyManager => '社員管理者',
        };
    }

    public function canManageCompany(): bool
    {
        return $this === self::CompanyManager;
    }
}
