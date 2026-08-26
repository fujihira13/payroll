<?php

namespace App\Enums;

enum PayrollBatchStatus: string
{
    case Draft = 'draft';
    case Scheduled = 'scheduled';
    case Published = 'published';

    public function label(): string
    {
        return match ($this) {
            self::Draft => '下書き',
            self::Scheduled => '承認済み・公開待ち',
            self::Published => '公開済み',
        };
    }
}
