<?php

namespace App\Console\Commands;

use App\Services\PublishScheduledPayroll;
use Illuminate\Console\Command;

class PublishScheduledPayslips extends Command
{
    protected $signature = 'payslips:publish-scheduled';

    protected $description = '公開日時を迎えた給与明細を公開して社員へ通知します';

    public function handle(PublishScheduledPayroll $publisher): int
    {
        $count = $publisher->handle();
        $this->info("{$count}件の給与明細バッチを公開しました。");

        return self::SUCCESS;
    }
}
