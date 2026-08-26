<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 意図しない個人情報やサンプル給与を投入しないため、空にしています。
        // 初回システム管理者は payroll:create-system-admin コマンドで作成します。
    }
}
