<?php

namespace App\Support;

class PayslipLayouts
{
    public static function types(): array
    {
        return [
            'standard' => '標準給与明細（左右2列）',
            'compact' => '月額明細（項目集約）',
        ];
    }

    public static function slots(string $layoutType = 'standard'): array
    {
        $count = $layoutType === 'compact' ? 8 : 12;
        $slots = [];
        foreach (['left' => '左列', 'right' => '右列'] as $prefix => $label) {
            for ($i = 1; $i <= $count; $i++) {
                $code = sprintf('%s_%02d', $prefix, $i);
                $slots[$code] = sprintf('%s %02d', $label, $i);
            }
        }

        return $slots;
    }
}
