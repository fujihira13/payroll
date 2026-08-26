<?php

namespace App\Enums;

enum UserRole: string
{
    case SystemAdmin = 'system_admin';
    case CompanyAdmin = 'company_admin';
    case Employee = 'employee';

    public function label(): string
    {
        return match ($this) {
            self::SystemAdmin => 'システム管理者',
            self::CompanyAdmin => '会社管理者',
            self::Employee => '社員',
        };
    }
}
