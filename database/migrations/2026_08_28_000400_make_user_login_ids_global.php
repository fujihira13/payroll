<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $duplicateLoginIds = DB::table('users')
            ->select('login_id')
            ->whereNotNull('login_id')
            ->groupBy('login_id')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('login_id');

        if ($duplicateLoginIds->isNotEmpty()) {
            throw new RuntimeException('重複するログインIDを解消してから再実行してください: '.$duplicateLoginIds->join(', '));
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['company_id', 'login_id']);
            $table->unique('login_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['login_id']);
            $table->unique(['company_id', 'login_id']);
        });
    }
};
