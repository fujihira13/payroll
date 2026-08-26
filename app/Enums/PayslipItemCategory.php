<?php

namespace App\Enums;

enum PayslipItemCategory: string
{
    case Earning = 'earning';
    case Deduction = 'deduction';
    case Information = 'information';

    public function label(): string
    {
        return match ($this) {
            self::Earning => '支給',
            self::Deduction => '控除',
            self::Information => '勤怠・その他',
        };
    }
}
